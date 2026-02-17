<?php
/**
 * R3D KAS Manager - Workspace URL Helper
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvorak
 * @version   0.28.14-alpha
 * @date      2026-02-17
 * @license   MIT License
 */

use App\Http\Middleware\ResolveWorkspace;

if (!function_exists('route_w')) {
    /**
     * Build a route URL and automatically propagate the current workspace key.
     *
     * @param mixed $parameters
     */
    function route_w(string $name, $parameters = [], bool $absolute = true): string
    {
        if (!is_array($parameters)) {
            $parameters = [$parameters];
        }

        $workspace = app()->bound('workspace.key')
            ? (string) app('workspace.key')
            : (string) request()->attributes->get('workspace', request()->query('w', ''));

        if ($workspace !== '' && ResolveWorkspace::isValidWorkspace($workspace) && !array_key_exists('w', $parameters)) {
            $parameters['w'] = $workspace;
        }

        return route($name, $parameters, $absolute);
    }
}
