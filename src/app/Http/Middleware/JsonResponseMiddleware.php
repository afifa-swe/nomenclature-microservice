<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JsonResponseMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response)
    {
        // If response is already JSON with our keys, leave it
        $content = $response->getContent();

        if ($this->isUnifiedJson($content)) {
            return;
        }

        $status = $response->getStatusCode();

        if ($status === 404) {
            $payload = [
                'message' => 'Ресурс не найден',
                'data' => null,
                'timestamp' => now()->toISOString(),
                'success' => false,
            ];
            $response->setContent(json_encode($payload));
            $response->headers->set('Content-Type', 'application/json');
            $response->setStatusCode(404);
            return;
        }

        if ($status === 405) {
            $payload = [
                'message' => 'Метод не разрешён',
                'data' => null,
                'timestamp' => now()->toISOString(),
                'success' => false,
            ];
            $response->setContent(json_encode($payload));
            $response->headers->set('Content-Type', 'application/json');
            $response->setStatusCode(405);
            return;
        }

        if ($status === 500) {
            $payload = [
                'message' => 'Внутренняя ошибка сервера',
                'data' => null,
                'timestamp' => now()->toISOString(),
                'success' => false,
            ];
            $response->setContent(json_encode($payload));
            $response->headers->set('Content-Type', 'application/json');
            $response->setStatusCode(500);
            return;
        }

        // For other non-2xx responses, standardize minimally
        if ($status >= 400 && $status < 600) {
            $payload = [
                'message' => 'Ошибка',
                'data' => null,
                'timestamp' => now()->toISOString(),
                'success' => false,
            ];
            $response->setContent(json_encode($payload));
            $response->headers->set('Content-Type', 'application/json');
            $response->setStatusCode($status);
            return;
        }
    }

    protected function isUnifiedJson($content)
    {
        if (empty($content)) return false;
        $decoded = json_decode($content, true);
        if (!is_array($decoded)) return false;
        return array_key_exists('message', $decoded)
            && array_key_exists('data', $decoded)
            && array_key_exists('timestamp', $decoded)
            && array_key_exists('success', $decoded);
    }
}
