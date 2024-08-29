<?php

namespace core;

class Pool
{
    private static PoolDB|null $myConnection;

    public static function init(): void
    {
        self::$myConnection = self::create();
    }

    public static function get(): PoolDB|null
    {
        if (self::$myConnection->isExpire()) {
            self::$myConnection = null;
            return self::create();
        }

        if (self::$myConnection->inUse()) {
            return null;
        }

        self::$myConnection->inUse(true);

        return self::$myConnection;
    }

    public static function set(PoolDB $myConn): void
    {
        $myConn->reset();
        self::$myConnection = $myConn;
    }

    private static function create(): PoolDB|null
    {
        try {
            return new PoolDB(Constant::SQL_LOCALHOST, Constant::SQL_DATABASE, Constant::SQL_USERNAME, Constant::SQL_PASSWORD);
        } catch (\PDOException $ex) {
            return null;
        }
    }
}