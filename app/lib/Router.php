<?php
declare(strict_types=1);

/** Minimal router: exact paths and {param} segments that match [a-z0-9-]+ only. */
final class Router
{
    private array $routes = [];

    public function get(string $pattern, callable $handler): void
    {
        $this->add(['GET', 'HEAD'], $pattern, $handler);
    }

    public function post(string $pattern, callable $handler): void
    {
        $this->add(['POST'], $pattern, $handler);
    }

    public function any(string $pattern, callable $handler): void
    {
        $this->add(['GET', 'HEAD', 'POST'], $pattern, $handler);
    }

    private function add(array $methods, string $pattern, callable $handler): void
    {
        $regex = '#^' . preg_replace('#\{([a-z_]+)\}#', '(?P<$1>[a-z0-9-]+)', $pattern) . '$#';
        $this->routes[] = [$methods, $regex, $handler];
    }

    public function dispatch(string $method, string $path): void
    {
        $pathMatched = false;
        foreach ($this->routes as [$methods, $regex, $handler]) {
            if (!preg_match($regex, $path, $m)) {
                continue;
            }
            $pathMatched = true;
            if (!in_array($method, $methods, true)) {
                continue;
            }
            $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
            $out = $handler(...$params);
            if (is_string($out)) {
                echo $out;
            }
            return;
        }
        if ($pathMatched) {
            http_response_code(405);
            header('Allow: GET, POST');
            echo 'Method not allowed';
            return;
        }
        abort(404);
    }
}
