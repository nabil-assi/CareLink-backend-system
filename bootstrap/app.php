<?php

use App\Http\Controllers\Api\ChatController;
use App\Http\Middleware\CheckRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        then: function () {
            // تجميع كل المسارات تحت بادئة /api
            Route::prefix('api')->middleware('api')->group(function () {
                require base_path('routes/api/auth.php');
                require base_path('routes/api/admin.php');
                require base_path('routes/api/doctor.php');
                require base_path('routes/api/patient.php');

                // مسارات المحادثات محمية بـ sanctum
                Route::middleware('auth:sanctum')->group(function () {
                    Route::get('/conversations/{conversationId}/messages', [ChatController::class, 'getMessages']);
                    Route::post('/conversations/{conversationId}/messages', [ChatController::class, 'sendMessage']);
                });
            });
        },
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'checkRole' => CheckRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
