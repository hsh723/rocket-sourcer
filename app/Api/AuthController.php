<?php

namespace RocketSourcer\Api;

use RocketSourcer\Core\Http\Request;
use RocketSourcer\Core\Http\Response;
use RocketSourcer\Services\Auth\AuthService;
use Psr\Log\LoggerInterface;

class AuthController extends BaseController
{
    public function login(Request $request): Response
    {
        $validation = $this->validateRequest($request, [
            'email' => 'required|email',
            'password' => 'required|min:6'
        ]);

        if ($validation) {
            return $validation;
        }

        $email = $request->get('email');

        if ($this->auth->hasTooManyLoginAttempts($email)) {
            return $this->error(
                'Too many login attempts. Please try again later.',
                429,
                ['attempts_left' => 0]
            );
        }

        $tokens = $this->auth->attempt($email, $request->get('password'));

        if (!$tokens) {
            $attempts = $this->auth->incrementLoginAttempts($email);
            $attemptsLeft = max(5 - $attempts, 0);

            return $this->error(
                'Invalid credentials',
                401,
                ['attempts_left' => $attemptsLeft]
            );
        }

        $this->auth->clearLoginAttempts($email);
        $this->logInfo('User logged in successfully', ['email' => $email]);

        return $this->response([
            'tokens' => $tokens,
            'user' => $this->user()->toArray()
        ]);
    }

    public function logout(Request $request): Response
    {
        $token = $this->getBearerToken($request);
        if (!$token) {
            return $this->error('No token provided', 401);
        }

        if ($this->auth->logout($token)) {
            $this->logInfo('User logged out successfully');
            return $this->response(['message' => 'Successfully logged out']);
        }

        return $this->error('Logout failed', 500);
    }

    public function refresh(Request $request): Response
    {
        $token = $request->get('refresh_token');
        if (!$token) {
            return $this->error('No refresh token provided', 401);
        }

        $tokens = $this->auth->refresh($token);
        if (!$tokens) {
            return $this->error('Invalid refresh token', 401);
        }

        $this->logInfo('Token refreshed successfully');
        return $this->response(['tokens' => $tokens]);
    }

    public function me(Request $request): Response
    {
        $token = $this->getBearerToken($request);
        if (!$token || !$this->auth->authenticate($token)) {
            return $this->error('Unauthorized', 401);
        }

        return $this->response([
            'user' => $this->user()->toArray()
        ]);
    }

    private function getBearerToken(Request $request): ?string
    {
        $header = $request->header('Authorization');
        if (!$header || !preg_match('/Bearer\s+(.+)/', $header, $matches)) {
            return null;
        }

        return $matches[1];
    }
}