<?php

namespace core;

use controller\Index;

/**
 * @property string $controller
 * @property string $function
 */
class Endpoint
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function __set(string $name, $value): void
    {
        $this->data[$name] = $value;
    }

    public function __get(string $name): mixed
    {
        return $this->data[$name] ?? null;
    }

    public static function endpoints(): array
    {
        return [
            '/index' => Index::controller('index', true),
            '/redirect/{id}' => Index::controller('redirect', true)
        ];
    }

    public static function get(Request $request): ConfigController|null
    {
        $endpoint = self::clean($request);
        $endpoints = self::endpoints();
        if ($endpoints[$endpoint] ?? false) {
            return $endpoints[$endpoint];
        }

        $endpoint = explode('/', $endpoint);
        $controllers = array_filter($endpoints, function ($endpt) use ($endpoint) {
            if (count(explode('/', $endpt)) != count($endpoint)) {
                return false;
            }

            $endpoint = array_values(array_filter($endpoint));
            if (!self::isVar($endpt)) {
                return false;
            }

            foreach (array_values(array_filter(explode('/', $endpt))) as $key => $value) {
                if ($value == $endpoint[$key]) {
                    continue;
                }

                if (!self::isVar($value)) {
                    return false;
                }
            }

            return true;
        }, ARRAY_FILTER_USE_KEY);

        if (!$endpoint) {
            return null;
        }

        $endpoint_key = array_filter(explode('/', array_key_first($controllers)), 'self::isVar');
        $endpoint_key = array_map(fn($var) => str_replace(['{', '}'], '', $var), $endpoint_key);

        $controller = array_shift($controllers);
        foreach ($endpoint_key as $key => $value) {
            $controller->addParam($value, $endpoint[$key]);
        }

        return $controller;
    }

    private static function isVar(string $text): bool
    {
        preg_match('/\{([a-zA-Z_]+)\}/', $text, $matches);
        return !!$matches;
    }

    private static function clean(Request $request): string
    {
        $parse = parse_url($request->getEndpoint());
        return '/' . strtolower(join('/', array_filter(explode('/', $parse['path'] ?? '/'))));
    }

    public static function getUrl(): string
    {
        return match (User::isAuth()) {
            false => Constant::AUTH_ENDPOINT_DEFAULT,
            default => Constant::NO_AUTH_ENDPOINT_DEFAULT
        };
    }
}