<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (PostTooLargeException $exception, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Юкланмаларнинг умумий ҳажми 128 МБ дан ошмаслиги керак.',
                ], 413);
            }

            $target = url()->previous();
            $separator = str_contains($target, '?') ? '&' : '?';

            return redirect()->to($target.$separator.'upload_error=1');
        });

        $exceptions->render(function (HttpException $exception, Request $request) {
            if ($exception->getStatusCode() !== 419
                || ! $exception->getPrevious() instanceof TokenMismatchException) {
                return null;
            }

            $message = 'Сессия муддати тугади. Саҳифа янгиланди, амални қайта бажаринг.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 419);
            }

            if ($request->routeIs('public.applications.submit')) {
                return redirect(route('landing').'#ariza')
                    ->withInput($request->except('_token'))
                    ->withErrors(['session' => $message]);
            }

            if (! $request->user()) {
                return redirect()->route('login')
                    ->withInput($request->only('email'))
                    ->withErrors(['session' => $message]);
            }

            $target = match ($request->route()?->getName()) {
                'applications.transition', 'applications.survey' => route(
                    'applications.show',
                    $request->route('application')
                ),
                'applications.store' => route('applications.create'),
                'contracts.action' => route('contracts.show', $request->route('contract')),
                default => route('dashboard'),
            };

            return redirect($target)->withErrors(['session' => $message]);
        });
    })->create();
