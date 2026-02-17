<?php
/**
 * R3D KAS Manager - Append Workspace to Redirects Middleware
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvorak
 * @version   0.28.20-alpha
 * @date      2026-02-17
 * @license   MIT License
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AppendWorkspaceToRedirects
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (!workspace_isolation_enabled()) {
            return $response;
        }

        $workspace = (string) ($request->attributes->get('workspace') ?? $request->query('w', ''));

        if (!$response instanceof RedirectResponse || !ResolveWorkspace::isValidWorkspace($workspace)) {
            return $response;
        }

        $targetUrl = $response->getTargetUrl();
        $parts = parse_url($targetUrl);
        if (!is_array($parts)) {
            return $response;
        }

        if (isset($parts['host']) && $parts['host'] !== $request->getHost()) {
            return $response;
        }

        parse_str((string) ($parts['query'] ?? ''), $query);
        if (isset($query[ResolveWorkspace::QUERY_KEY]) && ResolveWorkspace::isValidWorkspace((string) $query[ResolveWorkspace::QUERY_KEY])) {
            return $response;
        }

        $query[ResolveWorkspace::QUERY_KEY] = $workspace;
        $rebuilt = $this->buildUrl($parts, $query);
        $response->setTargetUrl($rebuilt);

        return $response;
    }

    /**
     * @param array<string,mixed> $parts
     * @param array<string,mixed> $query
     */
    private function buildUrl(array $parts, array $query): string
    {
        $queryString = http_build_query($query);
        $path = (string) ($parts['path'] ?? '');

        if (isset($parts['scheme'], $parts['host'])) {
            $authority = $parts['scheme'] . '://' . $parts['host'];
            if (isset($parts['port'])) {
                $authority .= ':' . $parts['port'];
            }
            $url = $authority . $path;
        } else {
            $url = $path;
        }

        if ($queryString !== '') {
            $url .= '?' . $queryString;
        }

        if (isset($parts['fragment']) && $parts['fragment'] !== '') {
            $url .= '#' . $parts['fragment'];
        }

        return $url;
    }
}
