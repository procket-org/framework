<?php

namespace Procket\Framework\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Procket\Framework\MiddlewareInterface;
use Random\RandomException;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class StartSession implements MiddlewareInterface
{
    /**
     * @inheritDoc
     * @throws RandomException
     */
    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        $request = $this->attachSession($request);

        $response = $next($request);

        return $this->handleSession($request, $response);
    }

    /**
     * Attach session to the request
     *
     * @param Request $request
     * @return Request
     * @throws RandomException
     */
    protected function attachSession(Request $request): Request
    {
        $config = procket()->getSessionConfig();
        if (!$config['enable'] || $request->hasSession()) {
            return $request;
        }

        $session = procket()->getSessionStore();
        $session->setId($request->cookies->get($session->getName()));
        $session->setRequestOnHandler($request);
        $session->start();
        $request->setLaravelSession($session);

        $hitsLottery = random_int(1, $config['lottery'][1]) <= $config['lottery'][0];
        if ($hitsLottery) {
            $session->getHandler()->gc($config['lifetime'] * 60);
        }

        return $request;
    }

    /**
     * Add session cookie to the response and save the session
     *
     * @param Request $request
     * @param SymfonyResponse $response
     * @return SymfonyResponse
     */
    protected function handleSession(Request $request, SymfonyResponse $response): SymfonyResponse
    {
        $config = procket()->getSessionConfig();
        if (!$config['enable'] || !$request->hasSession()) {
            return $response;
        }

        $session = $request->session();
        if (
            $request->isMethod('GET') &&
            !$request->ajax() &&
            !$request->prefetch() &&
            !$request->isPrecognitive()
        ) {
            $session->setPreviousUrl($request->fullUrl());
        }

        $cookieExpirationDate = $config['expire_on_close'] ? 0 : Date::instance(
            Carbon::now()->addRealMinutes($config['lifetime'])
        );
        $response->headers->setCookie(new Cookie(
            $session->getName(),
            $session->getId(),
            $cookieExpirationDate,
            $config['path'],
            $config['domain'],
            $config['secure'] ?? false,
            $config['http_only'] ?? true,
            false,
            $config['same_site'] ?? null,
            $config['partitioned'] ?? false
        ));
        if (!$request->isPrecognitive()) {
            $session->save();
        }

        return $response;
    }
}