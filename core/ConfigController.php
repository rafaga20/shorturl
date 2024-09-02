<?php

namespace core;

/**
 * @method isApi()
 * @method isOpen()
 */
class ConfigController
{
    private array $data;

    public function __construct(string $class, string $function, bool $open, bool $api)
    {
        $this->class = $class;
        $this->function = $function;
        $this->setOpen($open);
        $this->setApi($api);
        $this->param = [];
    }

    public function __set(string $name, mixed $value): void
    {
        $this->data[$name] = $value;
    }

    public function __get(string $name): mixed
    {
        return $this->data[$name] ?? null;
    }

    public function __call(string $method, array $params): mixed
    {
        preg_match("/^([a-z]+)([a-zA-Z]+)$/", $method, $matches);
        if (count($matches) != 3) {
            return null;
        }

        $var_name = strtolower($matches[2]);
        return match ($matches[1]) {
            'set' => (fn() => $this->$var_name = $params[0] ?? null)(),
            'get' => $this->$var_name ?? null,
            'is' => !!$this->$var_name,
            default => null
        };
    }

    public function addParam(string $name, $value): void
    {
        $this->data['param'][$name] = $value;
    }

    public function call(Request $request, array ...$params): mixed
    {
        $view = new View($request, parent: 'index');
        $controller = new $this->class($request);
        if (!$controller->isAccessible($this)) {
            $view->setTemplate('response.404');
            return $view;
        }

        return $controller->{$this->function}($request, ...$params, ...$this->data['param']);
    }
}