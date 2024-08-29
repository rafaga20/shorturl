<?php

namespace core;

class PoolDB extends \PDO
{
    private int $expire_at = 0;
    private bool $in_use = false;

    public function __construct(string $host, string $dbname, string $user, string $pass)
    {
        parent::__construct("mysql:host=$host;dbname=$dbname", $user, $pass);
        $this->reset();
    }

    public function isExpire(): bool
    {
        return $this->expire_at < time();
    }

    public function reset(): void
    {
        $this->expire_at = strtotime('+30 seconds');
        $this->in_use = false;
    }

    public function inUse(bool|null $use = null): bool
    {
        if (!is_null($use)) {
            $this->in_use = $use;
        }

        return $this->in_use;
    }
}