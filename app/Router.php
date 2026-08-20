<?php
namespace Phlash;

class Router
{
    private array $routes = [];

    public function get(string $pattern, callable $handler): void
    {
        $this->map('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable $handler): void
    {
        $this->map('POST', $pattern, $handler);
    }

    private function map(string $method, string $pattern, callable $handler): void
    {
        $regex = preg_replace('#\{([a-z_]+)\}#', '(?P<$1>[^/]+)', $pattern);
        $this->routes[] = [$method, '#^' . $regex . '$#u', $handler];
    }

    public function dispatch(string $method, string $path): void
    {
        $path = '/' . trim($path, '/');
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }
        foreach ($this->routes as [$m, $regex, $handler]) {
            if ($m !== $method) {
                continue;
            }
            if (preg_match($regex, $path, $matches)) {
                $params = [];
                foreach ($matches as $k => $v) {
                    if (!is_int($k)) {
                        $params[$k] = urldecode($v);
                    }
                }
                if ($params) {
                    $handler($params);
                } else {
                    $handler();
                }
                return;
            }
        }
        View::notFound();
    }
}
