<?php

namespace core;

class Http
{
    private static ConfigController|null $controller;
    private static Request $request;

    public static function init(): void
    {
        self::$request = new Request();
        $view = new View(self::$request, parent: 'index');

        Header::accessControl('origin', self::$request->getHost());
        Header::accessControl('methods', Constant::ALLOW_METHODS);
        Header::accessControl('headers', Constant::ALLOW_HEADERS);

        self::events(self::$request);
        $view->setTemplate('response.404');
        if (($validation = self::validation(self::$request, $view)) instanceof View) {
            self::send($validation->get());
        }

        if (!self::$request->isToken()) {
            $view->setTemplate('response.403');
            self::send($view->get());
        }

        $result = self::$controller->call(self::$request);

        if ($result instanceof View) {
            self::send($result->get());
        }

        if ($result instanceof Request) {
            self::send(json_encode($result->response), [Constant::CONTENT_TYPE => Constant::CONTENT_TYPE_JSON]);
        }
    }

    private static function validation(Request $request, View $view): View|bool
    {
        if (Constant::IP_ALLOW && !in_array(self::$request->getIp(), Constant::IP_ALLOW)) {
            $view->setTemplate('response.403');
            return $view;
        }

        if (!in_array($request->getMethod(), Constant::ALLOW_METHODS)) {
            $view->setTemplate('response.403');
            return $view;
        }

        if (count(array_filter(explode('/', $request->getEndpoint()))) < 1) {
            self::redirect(Endpoint::getUrl());
        }

        if (!self::$controller = Endpoint::get($request)) {
            return $view;
        }

        if (!$request->isAuthorized(self::$controller)) {
            self::redirect(Endpoint::getUrl());
        }

        return true;
    }

    private static function events(Request $request): void
    {
        $request->on('log', fn() => self::log(...func_get_args()));
    }

    private static function log(Request $request, string $message, string $log_level, int $level): void
    {
        if ($level !== Constant::LOG_INTERNAL_ERROR) {
            error_log(
                sprintf(
                    '[%s][%s][%s][%s] %s',
                    $request->getIp(),
                    $request->id,
                    $request->getEndpoint(),
                    strtoupper($log_level),
                    $message
                )
            );
            return;
        }

        self::internalError($request, $message);
    }

    public static function internalError(Request|null $request, string $message): void
    {
        if (!($request = $request ?? self::$request)) {
            return;
        }

        $error = new View(template: 'email.error.internal');
        $error->addVariable('message', $message, true);
        $error->addVariable('ip', $request->getIp(), true);
        $error->addVariable('endpoint', $request->getEndpoint(), true);
        $error->addVariable('host_url', 'https://' . $request->getHost(), true);
        $error->addVariable('request_id', $request->id, true);
        $error->addVariable('date', $request->getTimestamp(), true);
        $error->addVariable('request_duration', $request->getDuration(), true);

        Mail::send(Constant::EMAIL_ERROR, 'Internal Error', $error->get());
    }

    public static function request(string $url, string $method = 'GET', array $data = [], array $header = []): mixed
    {
        $method = strtoupper($method);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        if ($method != 'GET') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);

        return json_decode($result ?: '', true) ?? '';
    }

    public static function redirect(string $url = ''): void
    {
        if (!$url) {
            $url = Endpoint::getUrl();
        }

        self::send('', ['Location' => $url]);
    }

    private static function send(array|string $response, array $header = []): void
    {
        $response = (string)(is_array($response) ? json_encode($response) : $response);
        if (!$header) {
            $header = [
                Constant::CONTENT_TYPE => Constant::CONTENT_TYPE_HTML,
                Constant::CACHE => Constant::CACHE_NONE
            ];
        }
        $header[Constant::CONTENT_LENGTH] = strlen($response);

        foreach ($header as $key => $value) {
            Header::add($key, $value);
        }

        exit($response);
    }
}