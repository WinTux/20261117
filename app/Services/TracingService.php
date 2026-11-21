namespace App\Services;
class TracingService
{
    public static function iniciarSpan(string $name)
    {
        $start = microtime(true);
        return function () use ($name, $start) {
            $duration = microtime(true) - $start;
            logger()->info("Span '{$name}' finalizado. Duración: {$duration} segundos.");
        }
    }
}