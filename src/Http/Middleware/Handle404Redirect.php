<?php

namespace OzkanOzcan\Laravel404To301\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use OzkanOzcan\Laravel404To301\Services\RedirectService;
use Symfony\Component\HttpFoundation\Response;

class Handle404Redirect
{
    public function __construct(protected RedirectService $service) {}

    /**
     * Intercept 404 responses and apply redirect rules or log missing URLs.
     *
     * Flow:
     *   1. Let the request through to get the actual response
     *   2. If response is NOT 404 → pass through unchanged
     *   3. If middleware is disabled in config → pass through unchanged
     *   4. If path matches an ignore_patterns glob → pass through unchanged
     *   5. Look up the path in the redirects table (via cache → DB)
     *   6. Rule found & active → increment hits, return redirect response
     *   7. No rule → record missing URL (DB and/or log), return original 404
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // Only process 404 responses
        if ($response->getStatusCode() !== 404) {
            return $response;
        }

        // Check if middleware is globally enabled
        if (! config('redirect404.enabled', true)) {
            return $response;
        }

        $path = '/' . ltrim($request->getPathInfo(), '/');

        // Skip ignored patterns (static assets, robots.txt, etc.)
        if ($this->isIgnored($path)) {
            return $response;
        }

        // Look up a redirect rule
        $redirect = $this->service->findRedirect($path);

        if ($redirect !== null) {
            $this->service->incrementHits($redirect);

            return redirect($redirect->to_url, $redirect->redirect_code);
        }

        // No rule found — record the miss
        $this->service->recordMissing($path, $request);

        return $response;
    }

    /**
     * Check whether the given path matches any of the configured ignore patterns.
     */
    protected function isIgnored(string $path): bool
    {
        $patterns = config('redirect404.ignore_patterns', []);

        foreach ($patterns as $pattern) {
            if (fnmatch($pattern, $path)) {
                return true;
            }
        }

        return false;
    }
}
