<?php declare(strict_types=1);

namespace Spotlibs\PhpLib\Middlewares;

use Closure;
use StdClass;
use Illuminate\Support\Facades\Log;
use Spotlibs\PhpLib\Services\ContextService;

class ServiceActivity
{
    private ContextService $contextService;

    /**
     * Create instance of ServiceActivity
     * @param \Spotlibs\PhpLib\Services\ContextService $contextService
     */
    public function __construct(ContextService $contextService)
    {
        $this->contextService = $contextService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $this->contextService->set('method', $request->method());
        $this->contextService->set('User-Agent', $request->header('User-Agent'));
        $this->contextService->set('Content-Type', $request->header('Content-Type'));
        $this->contextService->set('Accept', $request->header('Accept'));
        $this->contextService->set('Accept-Encoding', $request->header('Accept-Encoding'));
        $this->contextService->set('Cache-Control', $request->header('Cache-Control'));
        $this->contextService->set('X-Forwarded-For', $request->header('X-Forwarded-For'));
        $this->contextService->set('X-Request-From', $request->header('X-Request-From'));
        $this->contextService->set('X-Device-ID', $request->header('X-Device-ID'));
        $this->contextService->set('X-App', $request->header('X-App'));
        $this->contextService->set('X-Version-App', $request->header('X-Version-App'));
        $this->contextService->set('X-Request-ID', $request->header('X-Request-ID'));
        $this->contextService->set('X-Request-User', $request->header('X-Request-User'));
        $this->contextService->set('X-Request-Nama', $request->header('X-Request-Nama'));
        $this->contextService->set('X-Request-Kode-Jabatan', $request->header('X-Request-Kode-Jabatan'));
        $this->contextService->set('X-Request-Nama-Jabatan', $request->header('X-Request-Nama-Jabatan'));
        $this->contextService->set('X-Request-Kode-Uker', $request->header('X-Request-Kode-Uker'));
        $this->contextService->set('X-Request-Nama-Uker', $request->header('X-Request-Nama-Uker'));
        $this->contextService->set('X-Request-Jenis-Uker', $request->header('X-Request-Jenis-Uker'));
        $this->contextService->set('X-Request-Kode-MainUker', $request->header('X-Request-Kode-MainUker'));
        $this->contextService->set('X-Request-Kode-Region', $request->header('X-Request-Kode-Region'));
        $this->contextService->set('X-Path-Gateway', $request->header('X-Path-Gateway'));
        $this->contextService->set('Authorization', $request->header('Authorization'));
        $this->contextService->set('X-Api-Key', $request->header('X-Api-Key'));
        
        return $next($request);
    }

    /**
     * Handle tasks after the response has been sent to the browser.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response  $response
     * @return mixed
     */
    public function terminate($request, $response)
    {
        $log = new StdClass;
        $log->app_name = getenv('APP_NAME');
        $log->host = getenv('HTTP_HOST');
        $log->clientip = $request->header('X-Forwarded-For') !== null ? $request->header('X-Forwarded-For') : $request->ip();
        $log->clientapp = $request->header('X-App') !== null ? $request->header('X-App') : null;
        $log->path = $request->getPathInfo();
        $log->path_alias = $request->header('X-Path-Gateway') !== null ? $request->header('X-Path-Gateway') : null;
        $log->requestID = $request->header('X-Request-ID') !== null ? $request->header('X-Request-ID') : null;
        $log->requestFrom = $request->header('X-Request-From') !== null ? $request->header('X-Request-From') : null;
        $log->requestUser = $request->header('X-Request-User') !== null ? $request->header('X-Request-User') : null;
        $log->deviceID = $request->header('X-Device-ID') !== null ? $request->header('X-Device-ID') : null;
        $log->requestTags = $request->header('X-Request-Tags') !== null ? $request->header('X-Request-Tags') : null;
        $log->requestBody = strlen(json_encode($request->all())) < 3000 ? $request->all() : null;
        
        # hashing secret information
        if(isset($log->requestBody['password'])) {
            $log->requestBody['password'] = hash('sha256', $log->requestBody['password']);
        }
        $responseObjContent = json_decode($response->getContent());
        if(strlen($response->getContent()) > 5000 && isset($responseObjContent->responseData)) {
            unset($responseObjContent->responseData);
        }
        $log->responseBody = $request->getPathInfo() !== '/docs' ? $responseObjContent : ['responseCode' => '00', 'responseDesc' => 'Sukses API Docs'];
        $log->responseTime = round((microtime(true) - $request->server('REQUEST_TIME_FLOAT'))*1000);
        $log->httpCode = $response->status();
        $log->memoryUsage = memory_get_usage();
        $log->requestAt = \DateTime::createFromFormat(
            'U.u', 
            number_format((float) $request->server('REQUEST_TIME_FLOAT'), 6, '.', '')
        )
            ->setTimezone(new \DateTimeZone('Asia/Jakarta'))
            ->format(\DateTimeInterface::RFC3339_EXTENDED);
        Log::channel('activity')->info(json_encode($log));
    }
}
