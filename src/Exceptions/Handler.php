<?php

declare(strict_types=1);

namespace Spotlibs\PhpLib\Exceptions;

use Exception;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Spotlibs\PhpLib\Exceptions\DataNotFoundException;
use Spotlibs\PhpLib\Responses\StdResponse;
use Throwable;


class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ValidationException::class
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Throwable  $exception
     * @return void
     *
     * @throws \Exception
     */
    public function report(Throwable $exception)
    {
        // parent::report($exception);
        if(!$exception instanceof ExceptionInterface && !$exception instanceof NotFoundHttpException && !$exception instanceof ValidationException) {
            if (!config('app.debug')) {
                Log::channel('runtime')->error('['.$exception->getCode().'] "'.$exception->getMessage().'" on line '.$exception->getLine().' of file '.$exception->getFile().' [requestID:'.(app()->request->header('X-Request-ID') ?? null).']');
            } else {
                parent::report($exception);
            }
        }
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  mixed  $request
     * @param  \Throwable  $exception
     * @return \Illuminate\Http\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception): Response
    {
        if($exception instanceof NotFoundHttpException || $exception instanceof HttpException)
        {
            $exception = new UnsupportedException("Route --".$request->getPathInfo()."-- not found in ".ENV('APP_NAME')."!");
        }

        if($exception instanceof ModelNotFoundException) {
            $exception = new DataNotFoundException("Data tidak ditemukan");
        }

        $responseValidation = [];
        if($exception instanceof ValidationException) {
            foreach($exception->errors() as $value)
            {
                array_push($responseValidation, implode(",", $value));
            }
            $exception = new ParameterException("Parameter inputan anda salah! " . $responseValidation[0] ?? null, null, $responseValidation);
        }

        if($exception instanceof QueryException && ENV('APP_ENV') === 'production') {
            $exception = new Exception("Query exception happens, see runtime error for more details.");
        }
        if(!$exception instanceof ExceptionInterface) {
            $exception = new RuntimeException(
                (ENV('APP_DEBUG') === true ? $exception->getMessage().' '.$exception->getFile().' Ln.'.$exception->getLine() : 'Terjadi kesalahan, mohon coba beberapa saat lagi yaa...')
            );
        }

        return StdResponse::error($exception);
    }
}