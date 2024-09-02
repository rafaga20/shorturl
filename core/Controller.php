<?php

namespace core;

class Controller
{
    protected Request $request;
    private array $data;
    private string $controller_name;

    public function __construct(Request $request)
    {
        $this->controller_name = (new \ReflectionClass(static::class))->getShortName();
        $this->request = $request;
        $this->data = Session::get('__CONTROLLER_DATA__') ?? [];
        Pool::init();
    }

    public function __set($name, $value)
    {
        $this->data[$this->controller_name][$name] = $value;
        Session::set('__CONTROLLER_DATA__', $this->data);
    }

    public function __get($name): mixed
    {
        return $this->data[$this->controller_name][$name] ?? null;
    }

    protected function clean(): void
    {
        unset($this->data[$this->controller_name]);
        Session::set('__CONTROLLER_DATA__', $this->data);
    }

    public static function controller(string $function, bool $open = false, bool $api = false): ConfigController
    {
        return new ConfigController(static::class, $function, $open, $api);
    }

    public function isAccessible(ConfigController $cfg): bool
    {
        return (new \ReflectionMethod($this, $cfg->function))->isPublic();
    }

    protected function view(string $template, array $data = [], string $parent = ''): View
    {
        $view = new View($this->request, $template, $data, $parent);
        $view->addVariable('token',
            (new Html('input', attributes: [
                'type' => 'hidden',
                'name' => 'token',
                'value' => $this->request->token
            ], closed: false))->getHtml(),
            true
        );
        $view->addVariable('TOKEN', $this->request->token);

        return $view;
    }

    protected function log($data, int $level = Constant::LOG_INFO): void
    {
        $this->request->log($data, $level);
    }

    protected function response(array $data = []): Request
    {
        return $this->request->send([
            ...$data,
            'request_status' => true,
            'request_code' => 200
        ]);
    }

    protected function responseError(string $message, int $code = 200): Request
    {
        return $this->request->send([
            'request_message' => $message,
            'request_status' => false,
            'request_code' => $code
        ]);
    }

    protected function debug($data): void
    {
        echo '<pre>';
        echo json_encode($data ?? 'NULL') ?? $data;
        echo '</pre>';
        exit;
    }
}