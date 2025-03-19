<?php

namespace RocketSourcer\Core\Http;

class Response
{
    private string $body;
    private int $statusCode;
    private array $headers;
    private array $cookies;

    public function __construct(
        string $body = '',
        int $statusCode = 200,
        array $headers = [],
        array $cookies = []
    ) {
        $this->body = $body;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->cookies = $cookies;
    }

    public static function json($data, int $status = 200, array $headers = []): self
    {
        $headers['Content-Type'] = 'application/json';
        return new static(
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $status,
            $headers
        );
    }

    public static function html(string $content, int $status = 200, array $headers = []): self
    {
        $headers['Content-Type'] = 'text/html; charset=UTF-8';
        return new static($content, $status, $headers);
    }

    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->headers[$name] = $value;
        return $clone;
    }

    public function withHeaders(array $headers): self
    {
        $clone = clone $this;
        $clone->headers = array_merge($clone->headers, $headers);
        return $clone;
    }

    public function withCookie(
        string $name,
        string $value,
        int $expires = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httpOnly = true
    ): self {
        $clone = clone $this;
        $clone->cookies[$name] = [
            'value' => $value,
            'expires' => $expires,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => $httpOnly
        ];
        return $clone;
    }

    public function withStatus(int $code): self
    {
        $clone = clone $this;
        $clone->statusCode = $code;
        return $clone;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getCookies(): array
    {
        return $this->cookies;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->statusCode);

            foreach ($this->headers as $name => $value) {
                header("$name: $value");
            }

            foreach ($this->cookies as $name => $params) {
                setcookie(
                    $name,
                    $params['value'],
                    $params['expires'],
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }
        }

        echo $this->body;
    }

    public function redirect(string $url, int $status = 302): self
    {
        return $this->withHeader('Location', $url)->withStatus($status);
    }

    public function notFound(string $message = 'Not Found'): self
    {
        return static::json(['error' => $message], 404);
    }

    public function unauthorized(string $message = 'Unauthorized'): self
    {
        return static::json(['error' => $message], 401);
    }

    public function forbidden(string $message = 'Forbidden'): self
    {
        return static::json(['error' => $message], 403);
    }

    public function badRequest(string $message = 'Bad Request'): self
    {
        return static::json(['error' => $message], 400);
    }

    public function internalServerError(string $message = 'Internal Server Error'): self
    {
        return static::json(['error' => $message], 500);
    }
}