<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exception\HttpException;
use Illuminate\Validation\ValidationException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $exception)
    {
        // Force unified JSON responses for any API route (starting with /api)
        if ($request->is('api/*') || $request->wantsJson()) {
            if ($exception instanceof ValidationException) {
                $payload = [
                    'message' => 'Ошибка валидации',
                    'data' => $exception->errors(),
                    'timestamp' => now()->toISOString(),
                    'success' => false,
                ];

                return response()->json($payload, 422);
            }

            if ($exception instanceof AuthenticationException) {
                $payload = [
                    'message' => $exception->getMessage() ?: 'Unauthenticated',
                    'data' => null,
                    'timestamp' => now()->toISOString(),
                    'success' => false,
                ];

                return response()->json($payload, 401);
            }

            if ($exception instanceof ModelNotFoundException) {
                $payload = [
                    'message' => 'Ресурс не найден',
                    'data' => null,
                    'timestamp' => now()->toISOString(),
                    'success' => false,
                ];

                return response()->json($payload, 404);
            }

            if ($exception instanceof HttpException) {
                $status = $exception->getStatusCode();
                $payload = [
                    'message' => $exception->getMessage() ?: 'HTTP error',
                    'data' => null,
                    'timestamp' => now()->toISOString(),
                    'success' => false,
                ];

                return response()->json($payload, $status);
            }

            // Fallback for other exceptions
            $payload = [
                'message' => $exception->getMessage() ?: 'Server Error',
                'data' => null,
                'timestamp' => now()->toISOString(),
                'success' => false,
            ];

            $status = method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 500;

            return response()->json($payload, $status);
        }

        return parent::render($request, $exception);
    }

    /**
     * Convert an authentication exception into an unauthenticated response.
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->is('api/*') || $request->wantsJson()) {
            $payload = [
                'message' => $exception->getMessage() ?: 'Unauthenticated',
                'data' => null,
                'timestamp' => now()->toISOString(),
                'success' => false,
            ];

            return response()->json($payload, 401);
        }

        return redirect()->guest(route('login'));
    }

    /**
     * Format validation exceptions for JSON responses.
     */
    protected function invalidJson($request, ValidationException $exception)
    {
        return response()->json([
            'message' => 'Ошибка валидации',
            'data' => $exception->errors(),
            'timestamp' => now()->toISOString(),
            'success' => false,
        ], $exception->status);
    }
}
