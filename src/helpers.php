<?php

use Ace\Application;

if (!function_exists('app')) {
    /**
     * Get the application instance.
     */
    function app(): Application
    {
        return Application::$app;
    }
}

if (!function_exists('env')) {
    /**
     * Get environment variables.
     */
    function env(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? $default;
    }
}

if (!function_exists('config')) {
    /**
     * Get configuration variables using dot notation.
     */
    function config(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $config = Application::$app->config;
        foreach ($keys as $k) {
            if (is_array($config) && array_key_exists($k, $config)) {
                $config = $config[$k];
            } else {
                return $default;
            }
        }
        return $config;
    }
}

if (!function_exists('view')) {
    /**
     * Render a view template.
     */
    function view(string $view, array $params = []): string
    {
        return Application::$app->view->render($view, $params);
    }
}

if (!function_exists('redirect')) {
    /**
     * Redirect to a given URL path.
     */
    function redirect(string $url): void
    {
        Application::$app->response->redirect($url);
    }
}

if (!function_exists('route')) {
    /**
     * Generate relative URL path for route linking.
     */
    function route(string $path): string
    {
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        return $basePath . '/' . ltrim($path, '/');
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Get the CSRF token from the session.
     */
    function csrf_token(): string
    {
        return Application::$app->session->getCsrfToken();
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Generate a CSRF token hidden input field.
     */
    function csrf_field(): string
    {
        $token = csrf_token();
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }
}

if (!function_exists('user')) {
    /**
     * Get the currently authenticated user, or null if guest.
     */
    function user(): ?\Ace\Model
    {
        return Application::$app->user;
    }
}

if (!function_exists('hasRole')) {
    /**
     * Check if the current user has a specific role.
     *
     * Usage in views: @if(hasRole('admin')) ... @endif
     */
    function hasRole(string $slug): bool
    {
        $user = Application::$app->user;
        return $user && $user->hasRole($slug);
    }
}

if (!function_exists('hasPermission')) {
    /**
     * Check if the current user has a specific permission.
     *
     * Usage in views: @if(hasPermission('edit-posts')) ... @endif
     */
    function hasPermission(string $slug): bool
    {
        $user = Application::$app->user;
        return $user && $user->hasPermission($slug);
    }
}

if (!function_exists('xss_clean')) {
    /**
     * Clean dynamic variables against XSS attacks.
     */
    function xss_clean(mixed $value): mixed
    {
        return \Ace\XssSanitizer::clean($value);
    }
}

if (!function_exists('guard')) {
    /**
     * Sanitize a value for safe database storage (Input Guard).
     *
     *   $clean = guard($userInput);
     */
    function guard(mixed $value): mixed
    {
        return \Ace\Guard::input($value);
    }
}

if (!function_exists('e')) {
    /**
     * Escape a value for safe HTML output (Output Guard).
     *
     *   <p><?= e($name) ?></p>
     */
    function e(mixed $value): string
    {
        return \Ace\Guard::output($value);
    }
}

if (!function_exists('asset')) {
    /**
     * Generate URL for a static asset located in public/assets.
     *
     *   <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
     */
    function asset(string $path): string
    {
        return route('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('upload')) {
    /**
     * Generate URL for a user upload file located in public/uploads.
     *
     *   <img src="<?= upload('avatars/user.png') ?>">
     */
    function upload(string $path): string
    {
        return route('uploads/' . ltrim($path, '/'));
    }
}

if (!function_exists('public_path')) {
    /**
     * Generate URL for any file located in the public directory root.
     *
     *   <link rel="shortcut icon" href="<?= public_path('favicon.ico') ?>">
     */
    function public_path(string $path = ''): string
    {
        return route($path);
    }
}




