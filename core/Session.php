<?php

namespace core;

class Session
{
    public static function init(): void
    {
        session_start();
        $_SESSION[session_id()] = $_SESSION[session_id()] ?? [];
    }

    public static function set(string $key, mixed $data): void
    {
        $_SESSION[session_id()][$key] = [
            'expire_at' => strtotime(Constant::SESSION_EXPIRE),
            'data' => $data
        ];
    }

    public static function get(string $key): mixed
    {
        if (!($session = ($_SESSION[session_id()][$key] ?? null))) {
            return null;
        }

        if ($session['expire_at'] <= time()) {
            self::delete($key);
            return $session['data'];
        }

        $_SESSION[session_id()][$key]['expire_at'] = strtotime(Constant::SESSION_EXPIRE);

        return $session['data'];
    }

    public static function delete(string $key): bool
    {
        if (!array_key_exists($key, $_SESSION[session_id()])) {
            return false;
        }

        unset($_SESSION[session_id()][$key]);

        return true;
    }

    public static function destroy(): void
    {
        session_destroy();
    }
}