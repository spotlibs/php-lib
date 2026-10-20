<?php

/**
 * PHP version 8.3
 *
 * @category Application
 * @package  Libraries\Storage
 * @author   Mufthi Ryanda <mufthi.ryan@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.0.1
 * @link     https://github.com/
 */

declare(strict_types=1);

namespace Spotlibs\PhpLib\Libraries\Storage;

use Spotlibs\PhpLib\Logs\Log;

/**
 * LogResolver
 *
 * Handles latency tracking and logging aggregation for storage operations.
 *
 * @category Library
 * @package  Libraries\Storage
 * @author   Mufthi Ryanda <mufthi.ryan@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/
 */
class LogResolver
{
    /**
     * @var array<string, float> Tracks execution time in milliseconds.
     */
    private array $latencies = [];

    /**
     * Execute a callback and record its execution time in milliseconds.
     *
     * @param string   $processName Name of the process to track
     * @param callable $callback    The process to execute
     *
     * @return mixed Result of the callback
     */
    public function track(string $processName, callable $callback): mixed
    {
        $start = hrtime(true);
        $result = $callback();
        $end = hrtime(true);

        // Convert nanoseconds to milliseconds
        $this->latencies[$processName] = ($end - $start) / 1e6;

        return $result;
    }

    /**
     * Aggregate and log the latency for all tracked processes, then reset the tracker.
     *
     * @param string $eventName The primary event name (e.g., 'OPTIMIZED_ZIP_COMPLETED')
     * @param array  $context   Additional context data (e.g., ['zip_name' => $zipName])
     *
     * @return void
     */
    public function logAggregate(string $eventName, array $context = []): void
    {
        $totalMs = array_sum($this->latencies);

        $payload = array_merge([
            'event' => $eventName,
            'total_latency_ms' => round($totalMs, 2),
            'steps_latency_ms' => array_map(fn($ms) => round($ms, 2), $this->latencies),
        ], $context);

        Log::runtime()->info($payload);

        $this->reset();
    }

    /**
     * Reset tracked latencies.
     *
     * @return void
     */
    public function reset(): void
    {
        $this->latencies = [];
    }
}