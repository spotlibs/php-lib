<?php

declare(strict_types=1);

namespace Tests\Libraries;

class User
{
    protected int $id;
    protected string $email;
    protected string $first_name;
    public string $last_name;
    protected string $avatar;
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
    }
}