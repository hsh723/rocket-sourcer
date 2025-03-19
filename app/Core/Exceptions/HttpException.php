<?php

namespace RocketSourcer\Core\Exceptions;

class HttpException extends \Exception
{
    public function __construct(
        string $message = "",
        int $code = 0,
        \Throwable|null $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
