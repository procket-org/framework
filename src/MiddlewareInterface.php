<?php

namespace Procket\Framework;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

interface MiddlewareInterface
{
    /**
     * Handle an incoming request
     *
     * To pass the request deeper into the application,
     * you should call and return the ***$next*** callback with the ***$request***.
     *
     * @param Closure(Request $request): Response $next
     */
    public function handle(Request $request, Closure $next): Response;
}