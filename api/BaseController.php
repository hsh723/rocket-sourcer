<?php

namespace RocketSourcer\Api;

use RocketSourcer\Core\Response;
use RocketSourcer\Core\Contracts\RequestInterface;
use RocketSourcer\Core\Contracts\ResponseInterface;

abstract class BaseController
{
    /**
     * 성공 응답 반환
     *
     * @param mixed $data
     * @param string $message
     * @param int $status
     * @return ResponseInterface
     */
    protected function success($data = null, string $message = 'Success', int $status = 200): ResponseInterface
    {
        return Response::success($data, $message, $status);
    }

    /**
     * 에러 응답 반환
     *
     * @param string $message
     * @param int $status
     * @param mixed $errors
     * @return ResponseInterface
     */
    protected function error(string $message, int $status = 400, $errors = null): ResponseInterface
    {
        return Response::error($message, $status, $errors);
    }

    /**
     * 권한 검사
     *
     * @param string $permission
     * @param RequestInterface $request
     * @return bool
     */
    protected function checkPermission(string $permission, RequestInterface $request): bool
    {
        // 사용자 권한 검사 로직 구현
        $user = $request->getAttribute('user');
        return $user && in_array($permission, $user->getPermissions());
    }

    /**
     * 입력값 유효성 검사
     *
     * @param RequestInterface $request
     * @param array $rules
     * @return array
     */
    protected function validate(RequestInterface $request, array $rules): array
    {
        try {
            return $request->validate($rules);
        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException($e->getMessage(), 422);
        }
    }
} 