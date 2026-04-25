<?php

use Spatie\Csp\AddCspHeaders;
use Illuminate\Http\Request;
use App\Http\Middleware\HandleTimezone;
use Illuminate\Foundation\Application;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\LocaleMiddleware;
use App\Http\Middleware\DisableEloquentStrictMode;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpFoundation\Response;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        apiPrefix: 'api/v1',
        health: '/up',

    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(LocaleMiddleware::class);

        $middleware->web(append: [
            SecurityHeaders::class,
            AddCspHeaders::class,
        ]);

        $middleware->api(append: [
            HandleTimezone::class,
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        // $middleware->append(App\Http\Middleware\CloudflareTrustProxiesMiddleware::class);
        $middleware->append(App\Http\Middleware\HttpsRedirect::class);

        $middleware->alias([
            // 'verified' => App\Http\Middleware\EnsureEmailIsVerified::class,
            'can-access-admin-boards' => App\Http\Middleware\CanAccessAdminBoards::class,
            'disable-eloquent-strict-mode' => DisableEloquentStrictMode::class,
            'role' => Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'normalize-callback-query' => App\Http\Middleware\NormalizeCallbackQuery::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            '/api/v1/broadcasting/auth',
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {
         $exceptions->shouldRenderJsonWhen(function (Request $request) {
            return $request->is('api/*') || $request->expectsJson();
        });

        $exceptions->renderable(function (InvalidSignatureException $e) {
            return response()->view('errors.link-expired', status: 403);
        });

        $exceptions->renderable(function (TransactionException $e, $request) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        });
        $exceptions->renderable(function (PaymentGatewayException $e, $request) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        });

        $exceptions->renderable(function (Throwable $e, Request $request) {
            $previous = $e->getPrevious();
            if ($previous instanceof ModelNotFoundException) {
                $fullModel = $previous->getModel();
                // App\Models\Order

                $model = str($fullModel)->afterLast('\\');
                // Order

                return response()->json([
                    'errors' => $model . ' not found',
                    'status' => Response::HTTP_NOT_FOUND,
                ], Response::HTTP_NOT_FOUND);
            }
        });

        $exceptions->respond(function (Response $response) {
            if ($response->getStatusCode() === 419) {
                return back()->with([
                    'message' => 'The page expired, please try again.',
                ]);
            }

            return $response;
        });
    })->create();
