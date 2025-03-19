<?php

namespace RocketSourcer\Core\Middleware;

use RocketSourcer\Core\Http\Request;
use RocketSourcer\Core\Http\Response;

interface MiddlewareInterface
{
    public function process(Request $request, callable $next): Response;
}
