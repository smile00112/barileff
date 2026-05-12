<?php

namespace Webkul\Core\Exceptions;

use App\Logging\TelegramNotifier;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as BaseHandler;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends BaseHandler
{
    /**
     * Report the exception and send a Telegram notification for server-level errors.
     */
    public function report(Throwable $e): void
    {
        parent::report($e);

        $isClientError = $e instanceof HttpException && $e->getStatusCode() < 500;

        if (! $isClientError && ! $e instanceof ValidationException) {
            TelegramNotifier::reportException($e);
        }
    }

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        if (config('app.debug')) {
            return;
        }

        $this->handleAuthenticationException();

        $this->handleHttpException();

        $this->handleValidationException();

        $this->handleServerException();
    }

    /**
     * Handle the authentication exception.
     */
    protected function handleAuthenticationException(): void
    {
        $this->renderable(function (AuthenticationException $exception, Request $request) {
            $namespace = $request->is(config('app.admin_url').'/*') ? 'admin' : 'shop';

            if ($request->wantsJson()) {
                return response()->json(['error' => trans("{$namespace}::app.errors.401.description")], 401);
            }

            if ($namespace !== 'admin') {
                return redirect()->guest(route('shop.customer.session.index'));
            }

            return redirect()->guest(route('admin.session.create'));
        });
    }

    /**
     * Handle the http exceptions.
     */
    protected function handleHttpException(): void
    {
        $this->renderable(function (HttpException $exception, Request $request) {
            $namespace = $request->is(config('app.admin_url').'/*') ? 'admin' : 'shop';

            $errorCode = in_array($exception->getStatusCode(), [401, 403, 404, 503])
                ? $exception->getStatusCode()
                : 500;

            if ($request->wantsJson()) {
                return response()->json([
                    'error' => trans("{$namespace}::app.errors.{$errorCode}.title"),
                    'description' => trans("{$namespace}::app.errors.{$errorCode}.description"),
                ], $errorCode);
            }

            $viewPath = "{$namespace}::errors.{$errorCode}";

            if (! view()->exists($viewPath)) {
                $viewPath = "{$namespace}::errors.index";
            }

            return response()->view($viewPath, compact('errorCode'), $errorCode);
        });
    }

    /**
     * Handle validation exceptions.
     */
    protected function handleValidationException(): void
    {
        $this->renderable(function (ValidationException $exception, Request $request) {
            return parent::convertValidationExceptionToResponse($exception, $request);
        });
    }

    /**
     * Handle the server exceptions.
     */
    protected function handleServerException(): void
    {
        $this->renderable(function (Throwable $throwable, Request $request) {
            $namespace = $request->is(config('app.admin_url').'/*') ? 'admin' : 'shop';

            $errorCode = 500;

            if ($request->wantsJson()) {
                return response()->json([
                    'error' => trans("{$namespace}::app.errors.{$errorCode}.title"),
                    'description' => trans("{$namespace}::app.shop.errors.{$errorCode}.description"),
                ], $errorCode);
            }

            $viewPath = "{$namespace}::errors.{$errorCode}";

            if (! view()->exists($viewPath)) {
                $viewPath = "{$namespace}::errors.index";
            }

            return response()->view($viewPath, compact('errorCode'), $errorCode);
        });
    }
}
