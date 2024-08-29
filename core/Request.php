<?php

namespace core;

/**
 * @property array $get
 * @property array $post
 * @property array $rawBody
 * @property array $request
 * @property array $response
 * @property string $id
 * @property string $token
 * @method getMethod()
 * @method getEndpoint()
 * @method getIp()
 * @method getHost()
 * @method getWebMasterEmail()
 * @method getTimestamp()
 * @method getDuration()
 * @method getHeader(string $key)
 */
class Request extends Header
{
    private array $data, $events = [];
    private float $mtime;

    public function __construct()
    {
        $token = uniqid('TOKEN');
        if (($token_data = Session::get('__TOKEN__')) && $token_data['expire_at'] > time()) {
            $token = $token_data['token'];
        } else {
            Session::set('__TOKEN__', [
                'expire_at' => strtotime(Constant::SESSION_EXPIRE),
                'token' => $token
            ]);
        }

        $this->mtime = microtime(true);
        $this->data = [
            'get' => $_GET,
            'post' => $_POST,
            'request' => $_REQUEST,
            'token' => $token,
            'rawBody' => json_decode(file_get_contents('php://input'), true) ?? [],
            'response' => [],
            'id' => uniqid()
        ];
    }

    public function __call(string $function, array $params): mixed
    {
        return match ($function) {
            'getMethod' => $_SERVER['REQUEST_METHOD'] ?? null,
            'getEndpoint' => $_SERVER['REQUEST_URI'] ?? null,
            'getIp' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'getHost' => $_SERVER['HTTP_HOST'] ?? null,
            'getWebMasterEmail' => $_SERVER['SERVER_ADMIN'] ?? '',
            'getTimestamp' => date('Y-m-d h:i:s', (int)$this->mtime),
            'getDuration' => microtime(true) - $this->mtime,
            'getHeader' => $_SERVER[$params[0]] ?? null,
            default => null
        };
    }

    public function __get(string $name): mixed
    {
        return $this->data[$name] ?? null;
    }

    public function __set(string $name, mixed $data): void
    {
        if ($name != 'response') {
            $this->data[$name] = $data;
        }
    }

    public function send(mixed $data): Request
    {
        $this->data['response'] = $this->getStructResponse($data, $data['request_status'] ?? false);

        if ($this->events['onSend'] ?? false) {
            $this->events['onSend']($this);
        }

        return $this;
    }

    public function on(string $event, callable $callback): void
    {
        $this->events['on' . ucfirst(strtolower($event))] = $callback;
    }

    public function log($message, int $log_level = Constant::LOG_INFO): void
    {
        if ($log_level < Constant::LOG_LEVEL) {
            return;
        }

        if ($this->events['onLog'] ?? false) {
            $export = str_split(var_export($message, true));
            array_pop($export);
            array_shift($export);
            $this->events['onLog']($this, join($export), $this->getLogLevelText($log_level), $log_level);
        }
    }

    private function getLogLevelText(int $log_level): string
    {
        return match ($log_level) {
            Constant::LOG_ERROR => 'ERROR',
            Constant::LOG_WARNING => 'WARNING',
            Constant::LOG_INTERNAL_ERROR => 'INTERNAL_ERROR',
            default => 'INFO'
        };
    }

    public function isAuthorized(ConfigController $controller): bool
    {
        if ($controller->isApi() && $this->checkToken()) {
            return true;
        }

        return $controller->isOpen() || User::isAuth();
    }

    public function isToken(): bool
    {
        if ($this->getMethod() === 'GET') {
            return true;
        }

        return $this->checkToken();
    }

    private function checkToken(): bool
    {
        $session = Session::get('__TOKEN__');
        if (!($this->request['token'] ?? false)) {
            return false;
        }

        if ($session && $session['expire_at'] <= time()) {
            return false;
        }

        if ($session && $session['token'] === $this->request['token']) {
            return true;
        }

        return false;
    }

    public function isMethod(string ...$methods): bool
    {
        $methods = array_map('strtolower', $methods);
        return in_array(strtolower($this->getMethod()), $methods);
    }

    private function getStructResponse($data, bool $status = true): array
    {
        $struct = [
            'result' => array_filter($data,
                fn($key) => !in_array($key, ['request_status', 'request_result', 'request_message', 'request_code']),
                ARRAY_FILTER_USE_KEY
            ),
            'message' => '',
            'status' => $status,
            'status_code' => '',
        ];

        if ($data['request_message'] ?? false) {
            $struct['message'] = $data['request_message'];
        }

        if ($data['request_code'] ?? false) {
            $struct['status_code'] = $data['request_code'];
        }

        return array_filter($struct);
    }
}