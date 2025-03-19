<?php

namespace RocketSourcer\Core\Http;

class Request
{
    private array $query;
    private array $request;
    private array $attributes;
    private array $cookies;
    private array $files;
    private array $server;
    private ?string $content;
    private array $headers;
    private string $path;
    private string $method;

    public function __construct(
        array $query = [],
        array $request = [],
        array $attributes = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        ?string $content = null
    ) {
        $this->query = $query;
        $this->request = $request;
        $this->attributes = $attributes;
        $this->cookies = $cookies;
        $this->files = $files;
        $this->server = array_change_key_case($server, CASE_UPPER);
        $this->content = $content;
        $this->headers = $this->initializeHeaders();
        $this->path = parse_url($this->server['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        $this->method = $this->server['REQUEST_METHOD'] ?? 'GET';
    }

    public static function createFromGlobals(): self
    {
        return new static(
            $_GET,
            $_POST,
            [],
            $_COOKIE,
            $_FILES,
            $_SERVER,
            file_get_contents('php://input')
        );
    }

    private function initializeHeaders(): array
    {
        $headers = [];
        foreach ($this->server as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $headers[$name] = mb_convert_encoding($value, 'UTF-8', 'auto');
            }
        }
        return $headers;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPathInfo(): string
    {
        $pathInfo = $this->server['PATH_INFO'] ?? '';
        if (empty($pathInfo)) {
            $pathInfo = $this->server['REQUEST_URI'] ?? '';
            if (($pos = strpos($pathInfo, '?')) !== false) {
                $pathInfo = substr($pathInfo, 0, $pos);
            }
        }
        return '/' . trim($pathInfo, '/');
    }

    public function get(string $key, $default = null)
    {
        return $this->query[$key] ?? $this->request[$key] ?? $this->attributes[$key] ?? $default;
    }

    public function query(string $key, $default = null)
    {
        return $this->query[$key] ?? $default;
    }

    public function request(string $key, $default = null)
    {
        return $this->request[$key] ?? $default;
    }

    public function attributes(string $key, $default = null)
    {
        return $this->attributes[$key] ?? $default;
    }

    public function cookie(string $key, $default = null)
    {
        return $this->cookies[$key] ?? $default;
    }

    public function file(string $key)
    {
        return $this->files[$key] ?? null;
    }

    public function server(string $key, $default = null)
    {
        return $this->server[strtoupper($key)] ?? $default;
    }

    public function header(string $key, $default = null)
    {
        return $this->headers[$key] ?? $default;
    }

    public function getContent(): ?string
    {
        if ($this->content === null) {
            return null;
        }
        return mb_convert_encoding($this->content, 'UTF-8', 'auto');
    }

    public function isMethod(string $method): bool
    {
        return $this->getMethod() === strtoupper($method);
    }

    public function isAjax(): bool
    {
        return $this->header('X-Requested-With') === 'XMLHttpRequest';
    }

    public function isJson(): bool
    {
        return strpos($this->header('Content-Type', ''), 'application/json') === 0;
    }

    public function getJsonContent(): ?array
    {
        if ($this->isJson()) {
            return json_decode($this->getContent(), true);
        }
        return null;
    }

    public function validate(array $rules): array
    {
        $errors = [];
        foreach ($rules as $field => $rule) {
            $value = $this->get($field);
            
            if (is_array($rule)) {
                foreach ($rule as $validation) {
                    if ($error = $this->validateField($field, $value, $validation)) {
                        $errors[$field][] = $error;
                    }
                }
            } else {
                if ($error = $this->validateField($field, $value, $rule)) {
                    $errors[$field][] = $error;
                }
            }
        }
        return $errors;
    }

    private function validateField(string $field, $value, string $rule): ?string
    {
        [$rule, $param] = array_pad(explode(':', $rule, 2), 2, null);

        switch ($rule) {
            case 'required':
                return empty($value) ? "{$field} is required" : null;
            case 'email':
                return !filter_var($value, FILTER_VALIDATE_EMAIL) ? "{$field} must be a valid email" : null;
            case 'min':
                return strlen($value) < $param ? "{$field} must be at least {$param} characters" : null;
            case 'max':
                return strlen($value) > $param ? "{$field} must not exceed {$param} characters" : null;
            case 'numeric':
                return !is_numeric($value) ? "{$field} must be numeric" : null;
            default:
                return null;
        }
    }

    public function getCookie(string $name, $default = null)
    {
        return $this->cookies[$name] ?? $default;
    }

    public function getHeader(string $name, $default = null)
    {
        $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $this->server[$serverKey] ?? $default;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function isKorean(string $text): bool
    {
        return preg_match('/[\x{3130}-\x{318F}\x{AC00}-\x{D7AF}]/u', $text) > 0;
    }
}