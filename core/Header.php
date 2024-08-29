<?php

namespace core;

class Header
{

    public static function add(string $key, $data): void
    {
        $key = ucfirst(strtolower($key));
        $data = join(',', (array)$data);
        header("$key: $data");
    }

    public static function accessControl(string $key, $data): void
    {
        self::add("Access-Control-Allow-$key", $data);
    }
}