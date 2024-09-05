<?php 

declare(strict_types=1);

namespace Spotlibs\PhpLib\Services;

class ContextService
{
    protected $data = [];

    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }

    public function get($key)
    {
        return $this->data[$key] ?? null;
    }

}