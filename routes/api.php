<?php
 use App\Http\Controllers\ProductoController;
 use Illuminate\Support\Facades\Route;
 use Prometheus\CollectorRegistry;
 use Prometheus\Renderer\RenderTextFormat;
 Route::apiResource('productos', ProductoController::class);
 Route::get('/metrics', function () {
     $registry = app(CollectorRegistry::class);
     $renderer = new RenderTextFormat();
     $metrics = $registry->getMetricFamilySamples();
     return response($renderer->render($metrics), 200)
         ->header('Content-Type', RenderTextFormat::MIME_TYPE);
 });