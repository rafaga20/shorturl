<?php

namespace core;

class User
{
    private static array $data;

    public static function init(): void
    {
        if (!(self::$data = Session::get(Constant::SESSION_USER_KEY) ?? [])) {
            return;
        }

        if (self::$data['session_expire'] <= time()) {
            self::logout();
            Http::redirect();
        }
    }

    public static function get($key = null): mixed
    {
        if (!self::isAuth()) {
            return false;
        }

        if (!$key) {
            return self::$data['user_data'];
        }

        return self::$data['user_data'][$key] ?? false;
    }

    public static function set(array $userData): void
    {
        Session::set(Constant::SESSION_USER_KEY, [
            'auth' => true,
            'user_data' => $userData,
            'session_start' => time()
        ]);
    }

    public static function logout(): void
    {
        Session::destroy();
    }

    public static function isAuth(): bool
    {
        return self::$data['auth'] ?? false;
    }
}