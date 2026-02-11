<?php

declare(strict_types=1);

namespace Tests\Logs;

use Tests\TestCase;
use Spotlibs\PhpLib\Logs\Log;
use Spotlibs\PhpLib\Services\Context;
use Spotlibs\PhpLib\Services\Metadata;

class LogTest extends TestCase
{
    private function setContext(): void
    {
        $meta = new Metadata();
        $meta->req_id = '123123';
        $meta->identifier = 'spotlibs-unittest';
        $context = app(Context::class);
        $context->set(Metadata::class, $meta);
    }

    private function setContext2(): void
    {
        $meta = new Metadata();
        $meta->task_id = '123123';
        $meta->identifier = 'spotlibs-unittest';
        $context = app(Context::class);
        $context->set(Metadata::class, $meta);
    }

    private string $expected = '{"test":"let me know","taskname":null,"hostname":"--- IGNORE ---","traceID":"123123","identifier":"spotlibs-unittest"}';

    public function testCreateRuntimeErrorLog()
    {
        $this->setContext();
        $mockData = ['test' => 'let me know'];
        Log::runtime()->error($mockData);
        $file = file("./storage/logs/runtime.log");
        $last_line = end($file);
        $logDetail = explode('SPOTLIBS_MICROSERVICE.local.', $last_line)[1];
        $temp = explode(':: ', $logDetail);
        $logLevel = $temp[0];
        $logMessage = trim($temp[1]);
        $logMessage = json_encode($this->removeHostname(json_decode($logMessage, true)));
        $this->assertEquals('ERROR', $logLevel);
        $this->assertEquals($this->expected, $logMessage);
    }
    public function testCreateRuntimeInfoLog()
    {
        $this->setContext2();
        $mockData = ['test' => 'let me know'];
        Log::runtime()->info($mockData);
        $file = file("./storage/logs/runtime.log");
        $last_line = end($file);
        $logDetail = explode('SPOTLIBS_MICROSERVICE.local.', $last_line)[1];
        $temp = explode(':: ', $logDetail);
        $logLevel = $temp[0];
        $logMessage = trim($temp[1]);
        $logMessage = json_encode($this->removeHostname(json_decode($logMessage, true)));
        $this->assertEquals('INFO', $logLevel);
        $this->assertEquals($this->expected, $logMessage);
    }
    public function testCreateRuntimeWarningLog()
    {
        $this->setContext();
        $mockData = ['test' => 'let me know'];
        Log::runtime()->warning($mockData);
        $file = file("./storage/logs/runtime.log");
        $last_line = end($file);
        $logDetail = explode('SPOTLIBS_MICROSERVICE.local.', $last_line)[1];
        $temp = explode(':: ', $logDetail);
        $logLevel = $temp[0];
        $logMessage = trim($temp[1]);
        $logMessage = json_encode($this->removeHostname(json_decode($logMessage, true)));
        $this->assertEquals('WARNING', $logLevel);
        $this->assertEquals($this->expected, $logMessage);
    }
    public function testCreateActivityInfoLog()
    {
        $this->setContext();
        $mockData = ['test' => 'let me know'];
        Log::activity()->info($mockData);
        $file = file("./storage/logs/activity.log");
        $last_line = end($file);
        $logDetail = explode('SPOTLIBS_MICROSERVICE.local.', $last_line)[1];
        $temp = explode(':: ', $logDetail);
        $logLevel = $temp[0];
        $logMessage = trim($temp[1]);
        $logMessage = json_encode($this->removeHostname(json_decode($logMessage, true)));
        $this->assertEquals('INFO', $logLevel);
        $this->assertEquals($this->expected, $logMessage);
    }
    public function testCreateWorkerInfoLog()
    {
        $this->setContext();
        $mockData = ['test' => 'let me know'];
        Log::worker()->info($mockData);
        $file = file("./storage/logs/worker.log");
        $last_line = end($file);
        $logDetail = explode('SPOTLIBS_MICROSERVICE.local.', $last_line)[1];
        $temp = explode(':: ', $logDetail);
        $logLevel = $temp[0];
        $logMessage = trim($temp[1]);
        $logMessage = json_encode($this->removeHostname(json_decode($logMessage, true)));
        $this->assertEquals('INFO', $logLevel);
        $this->assertEquals($this->expected, $logMessage);
    }
    private function removeHostname(array $logData): array
    {
        $logData['hostname'] = '--- IGNORE ---';
        return $logData;
    }
}