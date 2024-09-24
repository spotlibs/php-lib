<?php

declare(strict_types=1);

namespace Tests\Logs;

use Laravel\Lumen\Testing\TestCase;
use Spotlibs\PhpLib\Logs\Log;

class LogTest extends TestCase
{
    public function createApplication()
    {
        return require __DIR__.'/../../bootstrap/app.php';
    }

    public function testCreateRuntimeErrorLog()
    {
        $mockData = ['test' => 'let me know'];
        Log::runtime()->error($mockData);
        $file = file("./storage/logs/runtime.log");
        $last_line = end($file);
        $logDetail = explode('SPOTLIBS_MICROSERVICE.local.', $last_line)[1];
        $temp = explode(':: ', $logDetail);
        $logLevel = $temp[0];
        $logMessage = trim($temp[1]);
        $this->assertEquals('ERROR', $logLevel);
        $this->assertEquals(json_encode($mockData), $logMessage);
    }
    public function testCreateRuntimeInfoLog()
    {
        $mockData = ['test' => 'let me know'];
        Log::runtime()->info($mockData);
        $file = file("./storage/logs/runtime.log");
        $last_line = end($file);
        $logDetail = explode('SPOTLIBS_MICROSERVICE.local.', $last_line)[1];
        $temp = explode(':: ', $logDetail);
        $logLevel = $temp[0];
        $logMessage = trim($temp[1]);
        $this->assertEquals('INFO', $logLevel);
        $this->assertEquals(json_encode($mockData), $logMessage);
    }
    public function testCreateRuntimeWarningLog()
    {
        $mockData = ['test' => 'let me know'];
        Log::runtime()->warning($mockData);
        $file = file("./storage/logs/runtime.log");
        $last_line = end($file);
        $logDetail = explode('SPOTLIBS_MICROSERVICE.local.', $last_line)[1];
        $temp = explode(':: ', $logDetail);
        $logLevel = $temp[0];
        $logMessage = trim($temp[1]);
        $this->assertEquals('WARNING', $logLevel);
        $this->assertEquals(json_encode($mockData), $logMessage);
    }
    public function testCreateActivityInfoLog()
    {
        $mockData = ['test' => 'let me know'];
        Log::activity()->info($mockData);
        $file = file("./storage/logs/activity.log");
        $last_line = end($file);
        $logDetail = explode('SPOTLIBS_MICROSERVICE.local.', $last_line)[1];
        $temp = explode(':: ', $logDetail);
        $logLevel = $temp[0];
        $logMessage = trim($temp[1]);
        $this->assertEquals('INFO', $logLevel);
        $this->assertEquals(json_encode($mockData), $logMessage);
    }
    public function testCreateWorkerInfoLog()
    {
        $mockData = ['test' => 'let me know'];
        Log::worker()->info($mockData);
        $file = file("./storage/logs/worker.log");
        $last_line = end($file);
        $logDetail = explode('SPOTLIBS_MICROSERVICE.local.', $last_line)[1];
        $temp = explode(':: ', $logDetail);
        $logLevel = $temp[0];
        $logMessage = trim($temp[1]);
        $this->assertEquals('INFO', $logLevel);
        $this->assertEquals(json_encode($mockData), $logMessage);
    }
}