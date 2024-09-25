<?php

declare(strict_types=1);

namespace Tests\Libraries;

use ReflectionClass;

class Response
{
    public int $page;
    public int $per_page;
    public int $total;
    public int $total_pages;
    /** @var \Tests\Libraries\User[] */
    public array $data;
    public Support $support;

    public function __construct(array $data = [])
    {
        $reflector = new ReflectionClass(static::class);
        foreach ($data as $key => $value) {
            $prop = $reflector->getProperty($key);
            if (!$prop->getType()->isBuiltin()) {
                $classname = $prop->getType()->getName();
                $this->{$key} = new $classname($value);
                continue;
            } elseif ($prop->getType()->getName() == 'array') {
                if (isset($value[0]) && is_array($value[0])) {
                    $classname = trim($prop->getDocComment(),"/** @var */");
                    $classname = str_replace("[]", "", $classname);
                    $newArr = [];
                    foreach ($value as $val) {
                        array_push($newArr, new $classname($val));
                    }
                    $this->{$key} = $newArr;
                    continue;
                }
            }
            $this->{$key} = $value;
        }
    }
}