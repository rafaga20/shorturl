<?php

namespace core;

class Config
{
    public static function init(): void
    {
        self::autoload();
        self::timezone();
        self::handler();
    }

    private static function autoload(): void
    {
        spl_autoload_register(function ($class) {
            if (!file_exists($filename = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php')) {
                return;
            }

            include $filename;
        });
    }

    private static function handler(): void
    {
        error_reporting(E_ERROR);

        set_exception_handler(function ($ex) {
            Http::internalError(null, join("\n", [
                $ex->getMessage(),
                $ex->getFile() . ':' . $ex->getLine(),
                $ex->getTraceAsString()
            ]));
        });

        set_error_handler(function () {
            $params = func_get_args();
            Http::internalError(null, join("\n", [
                $params[1] ?? '', ($params[2] ?? '') . ':' . ($params[3] ?? '')
            ]));
        });
    }

    private static function timezone(): void
    {
        date_default_timezone_set(Constant::TIMEZONE);
    }

    public static function crypt(string $data): string
    {
        return @openssl_encrypt($data, "AES-128-CBC", Constant::KEY_CRYPT);
    }

    public static function decrypt(string $text): string
    {
        return @openssl_decrypt($text, 'AES-128-CBC', Constant::KEY_CRYPT);
    }

    public static function generateJWT(array $data): string
    {
        return @base64_encode(self::crypt(json_encode($data)));
    }

    public static function decodeJWT(string $jwt): mixed
    {
        return json_decode(self::decrypt(base64_decode($jwt) ?? ''), true);
    }
}