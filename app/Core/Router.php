<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @param array<string, array{0:class-string,1:string}> $routes keyed by "METHOD /path" */
    public static function dispatch(array $routes): void
    {
        $method = Request::method();
        $path = Request::uri();
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/') ?: '/';
        }

        foreach ($routes as $pattern => $handler) {
            $parts = explode(' ', (string) $pattern, 2);
            if (count($parts) !== 2) {
                continue;
            }
            [$m, $p] = $parts;
            if (strtoupper($m) !== $method) {
                continue;
            }
            $regex = self::patternToRegex($p);
            if (preg_match($regex, $path, $matches)) {
                [$class, $action] = $handler;
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $controller = new $class();
                $controller->{$action}(...array_values($params));

                return;
            }
        }

        Response::abort(404, 'Page not found');
    }

    private static function patternToRegex(string $pattern): string
    {
        $pattern = '#^' . preg_replace('#\{([a-zA-Z_]+)\}#', '(?P<$1>[^/]+)', $pattern) . '$#';

        return $pattern;
    }
}
