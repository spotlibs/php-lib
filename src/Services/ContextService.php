<?php

/**
 * PHP version 8
 *
 * @category Library
 * @package  Services
 * @author   Hendri Nursyahbani <hendrinursyahbani@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.0.3
 * @link     https://github.com/spotlibs
 */

declare(strict_types=1);

namespace Spotlibs\PhpLib\Services;

/**
 * ContextService
 *
 * Standard validation static methods
 *
 * @category StandardService
 * @package  Services
 * @author   Hendri Nursyahbani <hendrinursyahbani@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
class ContextService
{
    protected $data = [];

    /**
     * Set new key value to context
     *
     * @param string $key   context key
     * @param mixed  $value context value
     *
     * @return void
     */
    public function set(string $key, mixed $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Get context value based on given context key
     *
     * @param string $key given key
     *
     * @return mixed
     */
    public function get(string $key)
    {
        return $this->data[$key] ?? null;
    }
}
