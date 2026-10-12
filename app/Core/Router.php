<?php
declare(strict_types=1);
namespace App\Core;

final class Router
{
    private array $routes = ['GET' => [], 'POST' => [], 'HEAD' => []];

    public function get(string $path, array $handler, array $middleware = []): void
    {
        $this->routes['GET'][$this->norm($path)]  = ['handler' => $handler, 'mw' => $middleware];
        $this->routes['HEAD'][$this->norm($path)] = ['handler' => $handler, 'mw' => $middleware];
    }
    public function post(string $path, array $handler, array $middleware = []): void
    {
        $this->routes['POST'][$this->norm($path)] = ['handler' => $handler, 'mw' => $middleware];
    }
    private function norm(string $p): string { return '/' . trim($p, '/'); }

    public function dispatch(): void
    {
        $method = Request::method();
        $path   = Request::path();
        $lookup = ($method === 'HEAD' && isset($this->routes['HEAD'])) ? 'HEAD' : $method;
        if (!isset($this->routes[$lookup])) $lookup = 'GET';

        $route = $this->routes[$lookup][$path] ?? null;
        if (!$route) {
            foreach ($this->routes[$lookup] as $pattern => $r) {
                $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $pattern) . '$#';
                if (preg_match($regex, $path, $m)) {
                    $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
                    $this->run($r, $params);
                    return;
                }
            }
            Response::abort(404, '404 — Halaman tidak ditemukan');
        }
        $this->run($route, []);
    }
    private function run(array $route, array $params): void
    {
        foreach ($route['mw'] as $mw) (new $mw())->handle();
        [$class, $method] = $route['handler'];
        (new $class())->$method(...array_values($params));
    }
}
