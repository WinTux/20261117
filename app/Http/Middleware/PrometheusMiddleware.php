<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Prometheus\CollectorRegistry;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class PrometheusMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $registry = app(CollectorRegistry::class);
        $method = $request->method();
        $endpoint = $request->route()?->uri() ?: $request->path();
        $startedAt = hrtime(true);

        $requestsInProgress = $registry->getOrRegisterGauge(
            'app',
            'http_requests_in_progress',
            'Peticiones HTTP que se están procesando actualmente',
            ['method', 'endpoint']
        );
        $requestsInProgress->inc([$method, $endpoint]);

        $requestSize = $request->headers->get('Content-Length');
        $requestSizeBytes = $requestSize !== null
            ? (float) $requestSize
            : (float) strlen((string) $request->getContent());

        try {
            $response = $next($request);
            $statusCode = $response->getStatusCode();
        } catch (Throwable $exception) {
            $statusCode = 500;
            throw $exception;
        } finally {
            $durationSeconds = (hrtime(true) - $startedAt) / 1_000_000_000;
            $status = (string) $statusCode;

            $requestsTotal = $registry->getOrRegisterCounter(
                'app',
                'http_requests_total',
                'Número total de peticiones HTTP',
                ['method', 'endpoint', 'status']
            );
            $requestsTotal->inc([$method, $endpoint, $status]);

            $requestDuration = $registry->getOrRegisterHistogram(
                'app',
                'http_request_duration_seconds',
                'Tiempo de ejecución de cada petición HTTP',
                ['method', 'endpoint']
            );
            $requestDuration->observe($durationSeconds, [$method, $endpoint]);

            $errorCounter = $registry->getOrRegisterCounter(
                'app',
                'http_request_errors_total',
                'Total de peticiones HTTP que generan errores 4xx o 5xx',
                ['method', 'endpoint', 'status']
            );
            if ($statusCode >= 400) {
                $errorCounter->inc([$method, $endpoint, $status]);
            }

            $responsesTotal = $registry->getOrRegisterCounter(
                'app',
                'http_responses_total',
                'Respuestas HTTP agrupadas por código HTTP',
                ['status']
            );
            $responsesTotal->inc([$status]);

            $requestSizeHistogram = $registry->getOrRegisterHistogram(
                'app',
                'http_request_size_bytes',
                'Tamaño del cuerpo de las peticiones HTTP',
                ['method', 'endpoint'],
                [128, 512, 1024, 4096, 16384, 65536, 262144, 1048576]
            );
            $requestSizeHistogram->observe($requestSizeBytes, [$method, $endpoint]);

            if (isset($response)) {
                $responseSize = $response->headers->get('Content-Length');
                $responseSizeBytes = $responseSize !== null
                    ? (float) $responseSize
                    : (float) strlen((string) $response->getContent());

                $responseSizeHistogram = $registry->getOrRegisterHistogram(
                    'app',
                    'http_response_size_bytes',
                    'Tamaño del cuerpo de las respuestas HTTP',
                    ['method', 'endpoint'],
                    [128, 512, 1024, 4096, 16384, 65536, 262144, 1048576]
                );
                $responseSizeHistogram->observe($responseSizeBytes, [$method, $endpoint]);
            }

            $requestsInProgress->dec([$method, $endpoint]);
        }

        return $response;
    }
}
