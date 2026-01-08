<?php

/**
 * PHP version 8
 *
 * @category Library
 * @package  Libraries
 * @author   Mufthi Ryanda <mufthi.ryanda@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.3.7
 * @link     https://github.com/spotlibs
 */

declare(strict_types=1);

namespace Spotlibs\PhpLib\Libraries\Filesystem;

use InvalidArgumentException;
use Laravel\Lumen\Application;
use Spotlibs\PhpLib\Libraries\Filesystem\Adapters\MinioAdapter;
use Spotlibs\PhpLib\Libraries\Filesystem\Adapters\NfsAdapter;
use Spotlibs\PhpLib\Libraries\Filesystem\Contracts\CustomFilesystemInterface;

/**
 * CustomFilesystemManager
 *
 * Manager for creating and managing filesystem adapters
 *
 * @category Library
 * @package  Libraries
 * @author   Mufthi Ryanda <mufthi.ryanda@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
class CustomFilesystemManager
{
    /**
     * Application instance.
     *
     * @var Application
     */
    protected $app;

    /**
     * Resolved disk instances.
     *
     * @var array<string, CustomFilesystemInterface>
     */
    protected array $disks = [];

    /**
     * Custom driver creators.
     *
     * @var array<string, callable>
     */
    protected array $customCreators = [];

    /**
     * Constructor.
     *
     * @param Application $app Application instance
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Get a filesystem disk instance.
     *
     * @param string|null $name Disk name
     *
     * @return CustomFilesystemInterface
     *
     * @throws InvalidArgumentException
     */
    public function disk(?string $name = null): CustomFilesystemInterface
    {
        $name = $name ?? $this->getDefaultDisk();

        return $this->disks[$name] ??= $this->resolve($name);
    }

    /**
     * Resolve the given disk.
     *
     * @param string $name Disk name
     *
     * @return CustomFilesystemInterface
     *
     * @throws InvalidArgumentException
     */
    protected function resolve(string $name): CustomFilesystemInterface
    {
        $config = $this->getConfig($name);

        if (empty($config)) {
            throw new InvalidArgumentException("Disk [{$name}] is not configured.");
        }

        $driver = $config['driver'] ?? null;

        if (empty($driver)) {
            throw new InvalidArgumentException("Disk [{$name}] does not have a configured driver.");
        }

        // Check for custom creator first
        if (isset($this->customCreators[$driver])) {
            return $this->callCustomCreator($driver, $config);
        }

        // Use built-in drivers
        $method = 'create' . ucfirst($driver) . 'Driver';

        if (method_exists($this, $method)) {
            return $this->{$method}($config);
        }

        throw new InvalidArgumentException("Driver [{$driver}] is not supported.");
    }

    /**
     * Create NFS driver instance.
     *
     * @param array $config Configuration array
     *
     * @return NfsAdapter
     */
    protected function createNfsDriver(array $config): NfsAdapter
    {
        return new NfsAdapter($config);
    }

    /**
     * Create MinIO driver instance.
     *
     * @param array $config Configuration array
     *
     * @return MinioAdapter
     */
    protected function createMinioDriver(array $config): MinioAdapter
    {
        return new MinioAdapter($config);
    }

    /**
     * Call a custom driver creator.
     *
     * @param string $driver Driver name
     * @param array  $config Configuration array
     *
     * @return CustomFilesystemInterface
     */
    protected function callCustomCreator(string $driver, array $config): CustomFilesystemInterface
    {
        return $this->customCreators[$driver]($this->app, $config);
    }

    /**
     * Register a custom driver creator.
     *
     * @param string   $driver   Driver name
     * @param callable $callback Creator callback
     *
     * @return $this
     */
    public function extend(string $driver, callable $callback): self
    {
        $this->customCreators[$driver] = $callback;

        return $this;
    }

    /**
     * Get the default disk name.
     *
     * @return string
     */
    public function getDefaultDisk(): string
    {
        return $this->app['config']['custom-filesystems.default'] ?? 'nfs';
    }

    /**
     * Set the default disk name.
     *
     * @param string $name Disk name
     *
     * @return void
     */
    public function setDefaultDisk(string $name): void
    {
        $this->app['config']['custom-filesystems.default'] = $name;
    }

    /**
     * Get the configuration for a disk.
     *
     * @param string $name Disk name
     *
     * @return array
     */
    protected function getConfig(string $name): array
    {
        return $this->app['config']["custom-filesystems.disks.{$name}"] ?? [];
    }

    /**
     * Forget a resolved disk instance.
     *
     * @param string $name Disk name
     *
     * @return $this
     */
    public function forgetDisk(string $name): self
    {
        unset($this->disks[$name]);

        return $this;
    }

    /**
     * Forget all resolved disk instances.
     *
     * @return $this
     */
    public function forgetAllDisks(): self
    {
        $this->disks = [];

        return $this;
    }

    /**
     * Get all resolved disk instances.
     *
     * @return array<string, CustomFilesystemInterface>
     */
    public function getDisks(): array
    {
        return $this->disks;
    }

    /**
     * Dynamically call the default disk instance.
     *
     * @param string $method     Method name
     * @param array  $parameters Method parameters
     *
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        return $this->disk()->$method(...$parameters);
    }
}