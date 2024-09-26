<?php

declare(strict_types=1);

namespace Tests\Libraries;

class Support
{
    public string $url;
    protected string $text;
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
    }
}