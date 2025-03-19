<?php

namespace RocketSourcer\Core\Middleware;

use RocketSourcer\Core\Http\Request;
use RocketSourcer\Core\Http\Response;

class MiddlewarePipeline
{
    private array $middlewares = [];
    
    public function pipe(MiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }
    
    public function process(Request $request, callable $handler): Response
    {
        $pipeline = array_reduce(
            array_reverse($this->middlewares),
            function ($next, $middleware) {
                return function ($request) use ($middleware, $next) {
                    return $middleware->process($request, $next);
                };
            },
            $handler
        );
        
        return $pipeline($request);
    }
}
