<?php

namespace Spotlibs\PhpLib\Database;

use Illuminate\Support\Facades\Log;
use Illuminate\Database\Events\QueryExecuted;

class DatabaseQueryLogger
{
    private int $slowQueryThreshold;
    private array $monitoredTables;

    public function __construct(int $slowQueryThreshold = 1000, array $monitoredTables = [])
    {
        $this->slowQueryThreshold = $slowQueryThreshold;
        $this->monitoredTables = $monitoredTables;
    }

    public function log(QueryExecuted $query): void
    {
        // Skip if no need log
        if (!$this->shouldLog()){
            return;
        }

        // Skip if no tables configured
        if (empty($this->monitoredTables)) {
            return;
        }

        $tableName = $this->extractTableName($query->sql);

        // Only log if table is in monitored list
        if (!$tableName || !in_array($tableName, $this->monitoredTables)) {
            return;
        }

        $logData = [
            'table' => $tableName,
            'latency_ms' => round($query->time, 2),
            'connection' => $query->connectionName,
        ];

        if ($query->time > $this->slowQueryThreshold) {
            Log::warning('Slow DB Query Latency', $logData);
        } else {
            Log::info('DB Query Latency', $logData);
        }
    }

    private function extractTableName(string $sql): ?string
    {
        // Match common SQL patterns
        if (preg_match('/(?:from|into|update|join)\s+[`"]?(\w+)[`"]?/i', $sql, $matches)) {
            return $matches[1];
        }
        return null;
    }

    public function shouldLog(): bool
    {
        return config('database.query_log_enabled', false);
    }
}