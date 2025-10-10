<?php

namespace Procket\Framework;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

interface MiddlewareInterface
{
    /**
     * Handle an incoming request
     *
     * To pass the request deeper into the application,
     * you should call and return the ***$next*** callback with the ***$request***.
     *
     * @param Closure(Request): (SymfonyResponse) $next
     */
    public function handle(Request $request, Closure $next): SymfonyResponse;
}