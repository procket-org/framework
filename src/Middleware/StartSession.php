<?php

namespace Pocket\Framework\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Session\FileSessionHandler;
use Illuminate\Session\Store as SessionStore;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use InvalidArgumentException;
use Pocket\Framework\MiddlewareInterface;
use Random\RandomException;
use Symfony\Component\HttpFoundation\Cookie;

class StartSession implements MiddlewareInterface
{
    /**
     * @inheritDoc
     * @throws RandomException
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request = $this->attachSession($request);

        $response = $next($request);

        return $this->handleSession($request, $response);
    }

    /**
     * Get the session configuration
     *
     * @return array
     */
    protected function getSessionConfig(): array
    {
        return array_replace([
            'enable' => true,
            'lifetime' => env('SESSION_LIFETIME', 120),
            'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', false),
            'files' => SESSIONS_PATH,
            'lottery' => [2, 100],
            'cookie' => env('SESSION_COOKIE', 'APP_SESSION_ID'),
            'path' => env('SESSION_PATH', '/'),
            'domain' => env('SESSION_DOMAIN'),
            'secure' => env('SESSION_SECURE_COOKIE'),
            'http_only' => env('SESSION_HTTP_ONLY', true),
            'same_site' => env('SESSION_SAME_SITE', 'lax'),
            'partitioned' => env('SESSION_PARTITIONED_COOKIE', false),
        ], (array)config('session'));
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
        $config = $this->getSessionConfig();
        if (!$config['enable'] || $request->hasSession()) {
            return $request;
        }

        if (!ensure_directory($config['files'])) {
            throw new InvalidArgumentException(sprintf(
                "The directory for session path '%s' does not exist and failed to create",
                $config['files']
            ));
        }

        $session = new SessionStore(
            $config['cookie'],
            new FileSessionHandler(
                filesystem(),
                $config['files'],
                $config['lifetime']
            )
        );
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
     * @param Response $response
     * @return Response
     */
    protected function handleSession(Request $request, Response $response): Response
    {
        $config = $this->getSessionConfig();
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