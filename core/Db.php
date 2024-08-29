<?php

namespace core;

class Db
{
    public static function select(string $table, array $column = [], array $conditions = []): array
    {
        if (!$db = Pool::get()) {
            return [];
        }

        $column = $column ? ('`' . join('`,`', $column) . '`') : '*';
        $conditions = self::prepareCondition($conditions);
        $statement = $db->query(sprintf('SELECT %s FROM `%s` WHERE %s', $column, $table, $conditions));

        Pool::set($db);

        return $statement->fetchAll(\PDO::FETCH_NAMED);
    }

    public static function get(string $table, $value, string $columna_name): array
    {
        if (!$myConn = Pool::get()) {
            return [];
        }

        $conditions = self::prepareCondition([$columna_name => $value]);
        $query = sprintf('SELECT * FROM `%s` WHERE %s LIMIT 1', $table, $conditions);
        $result = $myConn->query($query)->fetchAll(PoolDB::FETCH_NAMED)[0] ?? [];
        Pool::set($myConn);

        return $result;
    }

    public static function query(string $query): array
    {
        if (!$myConn = Pool::get()) {
            return [];
        }

        $result = $myConn->query($query)->fetchAll(\PDO::FETCH_NAMED);
        Pool::set($myConn);

        return $result;
    }

    public static function insert(string $table, array $data): mixed
    {
        if (array_key_first($data) == 0) {
            $columns = join('`,`', array_keys($data[0]));
            $values = array_map(fn($row) => self::prepareValue($row), $data);
            $values = join(', ', $values);
        } else {
            $columns = join('`,`', array_keys($data));
            $values = self::prepareValue($data);
        }

        if (!$myConn = Pool::get()) {
            return false;
        }

        $query = sprintf(
            'INSERT INTO `%s`(`%s`) VALUES %s;',
            $table, $columns, $values
        );

        $myConn->prepare($query)->execute();
        Pool::set($myConn);

        return $myConn->lastInsertId();
    }

    public static function delete(string $table, array $condition = []): bool
    {
        if (!$myConn = Pool::get()) {
            return false;
        }

        $condition = self::prepareCondition($condition);
        $query = sprintf('DELETE FROM `%s` WHERE %s', $table, $condition);
        $stmt = $myConn->prepare($query);
        $stmt->execute();

        Pool::set($myConn);

        return $stmt->rowCount() > 0;
    }

    public static function update(string $table, array $data, array $conditions = []): bool
    {
        if (!$myConn = Pool::get()) {
            return false;
        }

        $conditions = self::prepareCondition($conditions);
        $data = join(', ', array_map(
            fn($col, $val) => "`$col`=" . self::cleanValue($val),
            array_keys($data), $data
        ));

        $query = sprintf('UPDATE `%s` SET %s WHERE %s', $table, $data, $conditions);
        $stmt = $myConn->prepare($query);
        $stmt->execute();

        Pool::set($myConn);

        return $stmt->rowCount() > 0;
    }

    private static function prepareValue(array $row): string
    {
        return '("' . join('","', $row) . '")';
    }

    private static function prepareCondition(array $conditions): string
    {
        $new_conditions = [];
        foreach ($conditions as $col => $value) {
            if (is_array($value)) {
                $value = join('","', array_map(fn($val) => self::cleanValue($val), $value));
                $condition = match ($col[0]) {
                    '!' => "`%s` NOT IN(\"%s\")",
                    default => "`%s` IN(\"%s\")"
                };
            } else {
                $value = self::cleanValue($value, match ($col[0]) {
                    '%' => '%',
                    default => '"'
                });
                $condition = match ($col[0]) {
                    '!' => match ($value) {
                        'null' => "`%s` IS NOT NULL",
                        default => "`%s` <> %s"
                    },
                    '%' => "`%s` LIKE \"%s\"",
                    default => match ($value) {
                        'null' => '`%s` IS NULL',
                        default => "`%s` = %s"
                    }
                };
            }

            self::removeOperator($col);
            $new_conditions[$col] = sprintf($condition, $col, $value);
        }

        return join(' AND ', $new_conditions) ?: 1;
    }

    private static function cleanValue($value, string $delimiter = '"'): string
    {
        if (is_null($value)) {
            return "null";
        }

        return join('', [
            $delimiter,
            str_replace('"', "\"", $value),
            $delimiter
        ]);
    }

    private static function removeOperator(string &$col): void
    {
        $col = str_split($col);
        if (in_array($col[0], ['!', '%'])) {
            array_shift($col);
        }
        $col = join($col);
    }
}