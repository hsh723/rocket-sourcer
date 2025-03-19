<?php

namespace RocketSourcer\Services\Coupang\Response;

class ApiResponse
{
    protected bool $success;
    protected ?string $code;
    protected ?string $message;
    protected mixed $data;
    protected ?array $raw;

    public function __construct(
        bool $success,
        ?string $code = null,
        ?string $message = null,
        mixed $data = null,
        ?array $raw = null
    ) {
        $this->success = $success;
        $this->code = $code;
        $this->message = $message;
        $this->data = $data;
        $this->raw = $raw;
    }

    /**
     * 성공 여부 반환
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * 응답 코드 반환
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * 응답 메시지 반환
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * 응답 데이터 반환
     */
    public function getData(): mixed
    {
        return $this->data;
    }

    /**
     * 원본 데이터 반환
     */
    public function getRaw(): ?array
    {
        return $this->raw;
    }

    /**
     * 배열로 변환
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'code' => $this->code,
            'message' => $this->message,
            'data' => $this->data,
        ];
    }

    /**
     * 성공 응답 생성
     */
    public static function success(mixed $data = null, ?string $message = null, ?array $raw = null): self
    {
        return new self(true, '200', $message, $data, $raw);
    }

    /**
     * 실패 응답 생성
     */
    public static function error(string $code, string $message, ?array $raw = null): self
    {
        return new self(false, $code, $message, null, $raw);
    }
} 