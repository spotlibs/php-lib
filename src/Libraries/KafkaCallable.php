<?php

/**
 * PHP version 8
 *
 * @category Library
 * @package  Exceptions
 * @author   Hendri Nursyahbani <hendrinursyahbani@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.0.4
 * @link     https://github.com/spotlibs
 */

declare(strict_types=1);

namespace Spotlibs\PhpLib\Libraries;

use Illuminate\Support\Facades\Log;
use Spotlibs\PhpLib\Libraries\Kafka as KafkaLibrary;
use RdKafka\Message;
use RdKafka\Producer;
use RdKafka\KafkaConsumer;

/**
 * KafkaCallable
 *
 * @category Library
 * @package  Libraries
 * @author   Hendri Nursyahbani <hendrinursyahbani@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
class KafkaCallable extends KafkaLibrary
{
    /**
     * Summary of deliveryReportCallback
     *
     * @param \RdKafka\Producer $producer producer instance
     * @param \RdKafka\Message  $message  message to produce
     *
     * @return void
     */
    public static function deliveryReportCallback(Producer $producer, Message $message)
    {
        if ($message->err == RD_KAFKA_RESP_ERR_NO_ERROR) {
            // phpcs:ignore
            Log::channel('runtime')->info("deliveryReportCallback triggered. Message delivered successfully to Topic: {$message->topic_name}. Partition: {$message->partition}. Offset: {$message->offset}. Key: {$message->key}. Timestamp: {$message->timestamp}");
        } else {
            // phpcs:ignore
            Log::channel('runtime')->error("deliveryReportCallback triggered. Message delivery failed: " . $message->errstr() . ". Topic: {$message->topic_name}. Partition: {$message->partition}. Offset: {$message->offset}. Key: {$message->key}. Timestamp: {$message->timestamp}");
        }
    }

    /**
     * Summary of errorProduceCallback
     *
     * @param \RdKafka\Producer $producer producer instance
     * @param int               $err      error code
     * @param string            $reason   error message
     *
     * @return void
     */
    public static function errorProduceCallback(Producer $producer, int $err, string $reason)
    {
        Log::channel('runtime')->error("errorProduceCallback triggered. Kafka producer error: " . rd_kafka_err2str($err) . ". Reason: {$reason}");
    }

    /**
     * Summary of errorConsumeCallback
     *
     * @param \RdKafka\KafkaConsumer $consumer consumer instance
     * @param int                    $err      error code
     * @param string                 $reason   error message
     *
     * @return void
     */
    public static function errorConsumeCallback(KafkaConsumer $consumer, int $err, string $reason)
    {
            // phpcs:ignore
        Log::channel('runtime')->error("errorConsumeCallback triggered. Kafka producer error: " . rd_kafka_err2str($err) . ". Reason: {$reason}" . " [Client ID: " . env('APP_NAME') . '-' . gethostname() . "]");
    }

    /**
     * Summary of rebalanceCallback
     *
     * @param \RdKafka\KafkaConsumer $consumer   consumer instance
     * @param int                    $err        error code
     * @param array                  $partitions list of partitions
     *
     * @return void
     */
    public static function rebalanceCallback(KafkaConsumer $consumer, int $err, array $partitions)
    {
        if ($err == RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS) {
            foreach ($partitions as $partition) {
                // phpcs:ignore
                Log::channel('runtime')->info("rebalanceCallback triggered. Status: Assigned partition. Topic: {$partition->getTopic()}. Partition: {$partition->getPartition()}. Offset: {$partition->getOffset()}");
            }
            $consumer->assign($partitions);
        } elseif ($err == RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS) {
            foreach ($partitions as $partition) {
                // phpcs:ignore
                Log::channel('runtime')->info("rebalanceCallback triggered. Status: Revoked partition. Topic: {$partition->getTopic()}. Partition: {$partition->getPartition()}. Offset: {$partition->getOffset()}");
            }
            $consumer->assign(null);
        } else {
            Log::channel('runtime')->error("rebalanceCallback triggered. Rebalance error: " . rd_kafka_err2str($err));
        }
    }

    /**
     * Summary of consumeCallback
     *
     * @param mixed $message callback message
     *
     * @return void
     */
    public static function consumeCallback($message)
    {
        $topic_name = $message->getTopicName() ?? null;
        $partition = $message->getPartition() ?? null;
        $offset = $message->getOffset() ?? null;
        $key = $message->getKey() ?? null;
        $timestamp = $message->getTimestamp() ?? null;
        Log::channel('runtime')->info("consumeCallback triggered. Topic: {$topic_name}. Partition: {$partition}. Offset: {$offset}. Key: {$key}. Timestamp: {$timestamp}");
    }

    /**
     * Summary of logCallback
     *
     * @param mixed $kafka    kafka instance
     * @param mixed $level    log level
     * @param mixed $facility kafka facility
     * @param mixed $message  log message usually in JSON format
     *
     * @return void
     */
    public static function logCallback($kafka, $level, $facility, $message)
    {
        if ($level == LOG_DEBUG || $level == LOG_INFO || $level == LOG_NOTICE) {
            Log::channel('runtime')->info("logCallback triggered. Kafka log [{$facility}]: {$message}");
        } elseif ($level == LOG_WARNING || $level == LOG_ALERT) {
            Log::channel('runtime')->warning("logCallback triggered. Kafka log [{$facility}]: {$message}");
        } elseif ($level == LOG_ERR || $level == LOG_CRIT || $level == LOG_EMERG) {
            Log::channel('runtime')->error("logCallback triggered. Kafka log [{$facility}]: {$message}");
        } else {
            Log::channel('runtime')->info("logCallback triggered. Kafka log [{$facility}]: {$message}");
        }
    }

    /**
     * Summary of offsetCommitCallback
     *
     * @param mixed $consumer   consumer instance
     * @param mixed $err        error code
     * @param mixed $partitions list of partitions
     *
     * @return void
     */
    public static function offsetCommitCallback($consumer, $err, $partitions)
    {
        if ($err) {
            Log::channel('runtime')->error("offsetCommitCallback triggered. Offset commit failed: " . rd_kafka_err2str($err) . " [Client ID: " . env('APP_NAME') . '-' . gethostname() . "]");
        } else {
            $partitionDetails = array_map(
                function ($partition) {
                    return [
                    'topic' => $partition->getTopic(),
                    'partition' => $partition->getPartition(),
                    'offset' => $partition->getOffset()
                    ];
                },
                $partitions
            );
            Log::channel('runtime')->info("offsetCommitCallback triggered. Offset commit succeeded: " . json_encode($partitionDetails) . " [Client ID: " . env('APP_NAME') . '-' . gethostname() . "]");
        }
    }
}
