<?php

namespace Ace;

use Exception;

class Router
{
    protected array $routes = [];
    protected array $namedRoutes = [];
    public Request $request;
    public Response $response;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Register GET route
     */
    public function get(string $path, $callback): Route
    {
        $this->routes['get'][$path] = $callback;
        return new Route($this, 'get', $path, $callback);
    }

    /**
     * Register POST route
     */
    public function post(string $path, $callback): Route
    {
        $this->routes['post'][$path] = $callback;
        return new Route($this, 'post', $path, $callback);
    }

    /**
     * Register an explicit name for a route path.
     */
    public function registerName(string $name, string $path): void
    {
        $this->namedRoutes[$name] = $path;
    }

    /**
     * Get the route path associated with an explicit name.
     */
    public function getPathByName(string $name): ?string
    {
        return $this->namedRoutes[$name] ?? null;
    }

    public function resolveRouteName(string $name): ?string
    {
        // 1. Check explicit named routes
        if (isset($this->namedRoutes[$name])) {
            return $this->namedRoutes[$name];
        }

        // 2. Check auto-naming convention in registered routes
        foreach ($this->routes as $method => $routes) {
            foreach ($routes as $path => $callback) {
                // Convention A: with parameter name (e.g. /blog/{id} -> blog.id)
                $autoNameWithParam = str_replace(['{', '}'], '', $path);
                $autoNameWithParam = trim($autoNameWithParam, '/');
                $autoNameWithParam = str_replace('/', '.', $autoNameWithParam);

                if ($autoNameWithParam === $name) {
                    return $path;
                }

                // Convention B: without parameter name (e.g. /blog/{id} -> blog)
                $cleanPath = preg_replace('/\{[a-zA-Z0-9_]+\}/', '', $path);
                $cleanPath = preg_replace('/\/+/', '/', $cleanPath);
                $autoNameWithoutParam = trim($cleanPath, '/');
                $autoNameWithoutParam = str_replace('/', '.', $autoNameWithoutParam);

                if ($autoNameWithoutParam === $name) {
                    return $path;
                }
            }
        }

        return null;
    }

    /**
     * Resolve the request URI and method to a registered route callback
     */
    public function resolve(): mixed
    {
        $path = $this->request->getPath();
        $method = $this->request->method();

        // Exact match
        $callback = $this->routes[$method][$path] ?? false;

        if ($callback === false) {
            // Check dynamic parameter matches (e.g. /users/{id})
            foreach ($this->routes[$method] ?? [] as $route => $routeCallback) {
                // Convert path like '/users/{id}' to regex pattern
                $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $route);
                $pattern = "@^" . $pattern . "$@";

                if (preg_match($pattern, $path, $matches)) {
                    array_shift($matches); // Remove the full match
                    return $this->executeCallback($routeCallback, $matches);
                }
            }

            throw new Exception("Page not found", 404);
        }

        return $this->executeCallback($callback);
    }

    /**
     * Execute route callback (Closure, String View, or Controller Action)
     */
    protected function executeCallback($callback, array $params = []): mixed
    {
        if (is_string($callback)) {
            return $this->renderView($callback);
        }

        if (is_array($callback)) {
            // callback is [Controller::class, 'action']
            $controllerClass = $callback[0];
            $action = $callback[1];

            // Instantiation via Container
            $controller = Application::$app->container->resolve($controllerClass);
            Application::$app->controller = $controller;
            $controller->action = $action;

            // Resolve and execute controller middlewares
            foreach ($controller->getMiddlewares() as $middleware) {
                $middleware->execute($action);
            }

            // Call action
            $result = call_user_func_array([$controller, $action], array_merge([$this->request], $params));

            if ($result instanceof JsonResource) {
                return $result->toResponse($this->request);
            }

            return $result;
        }

        if (is_callable($callback)) {
            $result = call_user_func_array($callback, array_merge([$this->request], $params));

            if ($result instanceof JsonResource) {
                return $result->toResponse($this->request);
            }

            return $result;
        }

        throw new Exception("Route callback is not valid", 500);
    }

    /**
     * Render layout and view templates
     */
    public function renderView(string $view, array $params = []): string
    {
        return Application::$app->view->render($view, $params);
    }
}

