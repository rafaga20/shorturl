<?php

namespace core;

class Controller
{
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
        Pool::init();
    }

    public static function controller(string $function, bool $open = false, bool $api = false): ConfigController
    {
        return new ConfigController(static::class, $function, $open, $api);
    }

    public function isAccessible(string $function): bool
    {
        return (new \ReflectionMethod($this, $function))->isPublic();
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