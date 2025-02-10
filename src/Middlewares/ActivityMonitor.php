<?php

/**
 * PHP version 8
 *
 * @category Library
 * @package  Middlewares
 * @author   Hendri Nursyahbani <hendrinursyahbani@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.0.4
 * @link     https://github.com/spotlibs
 */

declare(strict_types=1);

namespace Spotlibs\PhpLib\Middlewares;

use Closure;
use Spotlibs\PhpLib\Services\Metadata;
use StdClass;
use Spotlibs\PhpLib\Logs\Log;
use Spotlibs\PhpLib\Services\Context;

/**
 * ActivityMonitor
 *
 * @category StandardMiddleware
 * @package  Middlewares
 * @author   Hendri Nursyahbani <hendrinursyahbani@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
class ActivityMonitor
{
    private Context $contextService;
    private array $fileData = [];

    /**
     * Create instance of ServiceActivity
     *
     * @param \Spotlibs\PhpLib\Services\Context $contextService context instance
     */
    public function __construct(Context $contextService)
    {
        $this->contextService = $contextService;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request http request instance
     * @param \Closure                 $next    next middleware in the pipeline
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $meta = new Metadata();
        $meta->api_key = $request->header('X-Api-Key');
        $meta->app = $request->header('X-App');
        $meta->authorization = $request->header('Authorization');
        $meta->cache_control = $request->header('Cache-Control');
        $meta->device_id = $request->header('X-Device-ID');
        $meta->forwarded_for = $request->header('X-Forwarded-For');
        $meta->path_gateway = $request->header('X-Path-Gateway');
        $meta->req_id = $request->header('X-Request-ID');
        $meta->req_kode_uker = $request->header('X-Request-Kode-Uker');
        $meta->req_nama_uker = $request->header('X-Request-Nama-Uker');
        $meta->req_jenis_uker = $request->header('X-Request-Jenis-Uker');
        $meta->req_kode_jabatan = $request->header('X-Request-Kode-Jabatan');
        $meta->req_kode_main_uker = $request->header('X-Request-Kode-MainUker');
        $meta->req_kode_region = $request->header('X-Request-Kode-Region');
        $meta->req_nama = $request->header('X-Request-Nama');
        $meta->req_nama_jabatan = $request->header('X-Request-Nama-Jabatan');
        $meta->req_tags = $request->header('X-Request-Tags');
        $meta->req_user = $request->header('X-Request-User');
        $meta->request_from = $request->header('X-Request-From');
        $meta->user_agent = $request->header('User-Agent');
        $meta->version_app = $request->header('X-Version-App');
        $meta->identifier = $request->server('REQUEST_URI');
        $this->contextService->set(Metadata::class, $meta);

        $this->contextService->set('method', $request->method());
        $this->contextService->set('Content-Type', $request->header('Content-Type'));
        $this->contextService->set('Accept', $request->header('Accept'));
        $this->contextService->set('Accept-Encoding', $request->header('Accept-Encoding'));

        $this->logFileRequest($request);
        $this->contextService->set('fileData', $this->fileData);

        return $next($request);
    }

    /**
     * Handle tasks after the response has been sent to the browser.
     *
     * @param \Illuminate\Http\Request  $request  http request instance
     * @param \Illuminate\Http\Response $response http response instance
     *
     * @return mixed
     */
    public function terminate($request, $response)
    {
        $fileData = (array) $this->contextService->get('fileData');
        $log = new StdClass();
        $log->app_name = getenv('APP_NAME');
        $log->host = getenv('HTTP_HOST');
        $log->clientip = $request->header('X-Forwarded-For') !== null ? $request->header('X-Forwarded-For') : $request->ip();
        $log->clientapp = $request->header('X-App') !== null ? $request->header('X-App') : null;
        $log->path = $request->getPathInfo();
        $log->path_alias = $request->header('X-Path-Gateway') !== null ? $request->header('X-Path-Gateway') : null;
        $log->requestFrom = $request->header('X-Request-From') !== null ? $request->header('X-Request-From') : null;
        $log->requestUser = $request->header('X-Request-User') !== null ? $request->header('X-Request-User') : null;
        $log->deviceID = $request->header('X-Device-ID') !== null ? $request->header('X-Device-ID') : null;
        $log->requestID = $request->header('X-Request-ID') !== null ? $request->header('X-Request-ID') : null;
        $log->requestTags = $request->header('X-Request-Tags') !== null ? $request->header('X-Request-Tags') : null;
        $log->versionApp = $request->header('X-Version-App') !== null ? $request->header('X-Version-App') : null;
        $log->requestBody = strlen(json_encode($request->all())) > 5000 ? 'more than 5000 characters' : $request->all();
        if (count($fileData) > 0 && $log->requestBody != 'more than 5000 characters') {
            $log->requestBody = array_merge($log->requestBody, $fileData);
        }
        // hashing secret information
        if (isset($log->requestBody['password'])) {
            $log->requestBody['password'] = hash('sha256', $log->requestBody['password']);
        }
        $responseObjContent = json_decode($response->getContent());
        if (strlen($response->getContent()) > 5000 && isset($responseObjContent->responseData)) {
            $responseObjContent->responseData = 'more than 5000 characters';
        }
        $log->responseBody = $request->getPathInfo() !== '/docs' ? $responseObjContent : ['responseCode' => '00', 'responseDesc' => 'Sukses API Docs'];
        $log->responseTime = round((microtime(true) - $request->server('REQUEST_TIME_FLOAT')) * 1000);
        $log->httpCode = $response->status();
        $log->memoryUsage = memory_get_usage();
        $log->requestAt = \DateTime::createFromFormat(
            'U.u',
            number_format((float) $request->server('REQUEST_TIME_FLOAT'), 6, '.', '')
        )
            ->setTimezone(new \DateTimeZone('Asia/Jakarta'))
            ->format(\DateTimeInterface::RFC3339_EXTENDED);
        Log::activity()->info((array) $log);
    }

    /**
     * Write request file details to log
     *
     * @param \Illuminate\Http\Request $request pointer of http request
     *
     * @return void
     */
    private function logFileRequest(&$request): void
    {
        foreach ($request->allFiles() as $key => $value) {
            $this->fileData[$key] = [];
            if (is_array($files = $request->file($key))) {
                foreach ($files as $file) {
                    $this->fileData[$key][] = [
                        'filename' => $file->getClientOriginalName(),
                        'mimetype' => $file->getMimeType(),
                        'size' => $file->getSize()
                    ];
                }
                continue;
            }
            $this->fileData[$key][] = [
                'filename' => $value->getClientOriginalName(),
                'mimetype' => $value->getMimeType(),
                'size' => $value->getSize()
            ];
        }
    }
}
