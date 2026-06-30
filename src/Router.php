<?php

namespace Ace;

use Exception;

class Router
{
    protected array $routes = [];
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
    public function get(string $path, $callback): void
    {
        $this->routes['get'][$path] = $callback;
    }

    /**
     * Register POST route
     */
    public function post(string $path, $callback): void
    {
        $this->routes['post'][$path] = $callback;
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

