<?php

namespace RocketSourcer\Api;

use RocketSourcer\Services\Auth\AuthService;
use RocketSourcer\Core\Contracts\RequestInterface;
use RocketSourcer\Core\Contracts\ResponseInterface;

class AuthController extends BaseController
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * 로그인 처리
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    public function login(RequestInterface $request): ResponseInterface
    {
        try {
            $data = $this->validate($request, [
                'email' => 'required|email',
                'password' => 'required|min:6'
            ]);

            $result = $this->authService->login($data['email'], $data['password']);
            return $this->success($result);

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * 토큰 갱신
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    public function refresh(RequestInterface $request): ResponseInterface
    {
        try {
            $data = $this->validate($request, [
                'refresh_token' => 'required'
            ]);

            $result = $this->authService->refresh($data['refresh_token']);
            return $this->success($result);

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * 로그아웃
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    public function logout(RequestInterface $request): ResponseInterface
    {
        try {
            $data = $this->validate($request, [
                'refresh_token' => 'required'
            ]);

            $this->authService->logout($data['refresh_token']);
            return $this->success(null, 'Successfully logged out');

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * 현재 사용자 정보 조회
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    public function me(RequestInterface $request): ResponseInterface
    {
        $user = $request->getAttribute('user');
        return $this->success($user->toArray());
    }
} 