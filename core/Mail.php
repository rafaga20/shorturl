<?php

namespace core;

/**
 * @method static setAlias(string $alias)
 * @method static getAlias()
 */
class Mail
{
    private const DEFAULT_ALIAS = 'Information';
    private static array $data = [];

    public static function __callStatic(string $method, $params): mixed
    {
        preg_match('/([a-z]+)([A-Za-z]+)/', $method, $matches);
        if (count($matches) !== 3) {
            return null;
        }

        if ($matches[1] == 'set') {
            return self::$data[$matches[2]] = $params[0] ?? null;
        }

        if ($matches[1] == 'get') {
            return self::$data[$matches[2]] ?? null;
        }

        return null;
    }

    public static function send(array|string $to, string $subject, string $text, string $from_email = Constant::EMAIL_USER): bool
    {
        $to = array_map(function ($email, $alias) {
            if (is_int($alias)) {
                $alias = ucfirst(substr($email, 0, strpos($email, '@')));
            }
            return "$alias <$email>";
        }, (array)$to, array_keys((array)$to));

        $alias = ucwords(self::getAlias() ?? self::DEFAULT_ALIAS);
        self::setAlias(self::DEFAULT_ALIAS);

        return mail(join(', ', $to), $subject, $text, [
            'From' => "$alias <$from_email>",
            'Reply-To' => Constant::EMAIL_USER,
            'X-Mailer' => 'PHP/' . phpversion(),
            'MIME-Version' => '1.0',
            'Content-type' => 'text/html; charset=UTF-8'
        ]);
    }
}