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
     * Redirect to a given URL path with optional status code and session flash data.
     */
    function redirect(string $url, int $statusCode = 302, array $flashData = []): void
    {
        Application::$app->response->redirect($url, $statusCode, $flashData);
    }
}

if (!function_exists('route')) {
    /**
     * Generate relative URL path for route linking.
     *
     * @param string $name Route name (e.g. 'events.id' or 'login') or direct path
     * @param array|string|int $params Route parameters or query parameters
     * @return string
     */
    function route(string $name, array|string|int $params = []): string
    {
        $router = Application::$app->router;
        $resolvedPath = $router->resolveRouteName($name);

        if ($resolvedPath !== null) {
            $path = $resolvedPath;
        } else {
            // Fallback for direct paths
            $path = $name;
        }

        // Normalize scalar parameter to array if there's a dynamic parameter
        if (!is_array($params)) {
            preg_match_all('/\{([a-zA-Z0-9_]+)\??\}/', $path, $matches);
            if (!empty($matches[1])) {
                $params = [$matches[1][0] => $params];
            } else {
                $params = [];
            }
        }

        $queryParams = [];
        foreach ($params as $key => $val) {
            // Try both optional {key?} and required {key} placeholders
            $optionalPlaceholder = '{' . $key . '?}';
            $requiredPlaceholder = '{' . $key . '}';

            if (str_contains($path, $optionalPlaceholder)) {
                $path = str_replace($optionalPlaceholder, (string) $val, $path);
            } elseif (str_contains($path, $requiredPlaceholder)) {
                $path = str_replace($requiredPlaceholder, (string) $val, $path);
            } else {
                $queryParams[$key] = $val;
            }
        }

        // Remove any remaining unfilled optional placeholders (e.g. /{id?} with no value)
        $path = preg_replace('#/\{[a-zA-Z0-9_]+\?\}#', '', $path);

        if (!empty($queryParams)) {
            $path .= (str_contains($path, '?') ? '&' : '?') . http_build_query($queryParams);
        }

        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
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
        if (! $user) {
            return false;
        }

        // Prefer user->hasRole if available
        if (method_exists($user, 'hasRole')) {
            return $user->hasRole($slug);
        }

        // Fallback: check roles property (array of slugs) if present
        if (property_exists($user, 'roles') && is_array($user->roles)) {
            return in_array($slug, $user->roles, true);
        }

        return false;
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
        if (! $user) {
            return false;
        }

        if (method_exists($user, 'hasPermission')) {
            return $user->hasPermission($slug);
        }

        // Fallback: check permissions property (array of slugs) if present
        if (property_exists($user, 'permissions') && is_array($user->permissions)) {
            return in_array($slug, $user->permissions, true);
        }

        return false;
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

if (!function_exists('dd')) {
    /**
     * Dump and Die helper for debugging.
     */
    function dd(mixed ...$vars): void
    {
        // Get caller details
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $caller = $backtrace[0] ?? [];
        $file = $caller['file'] ?? 'Unknown';
        $line = $caller['line'] ?? 0;

        // Clean up file path
        $file = str_replace('\\', '/', $file);
        if (defined('ROOT_DIR')) {
            $file = str_replace(str_replace('\\', '/', ROOT_DIR) . '/', '', $file);
        }

        $isCli = php_sapi_name() === 'cli';
        $isDev = (($_ENV['APP_ENV'] ?? 'development') === 'development');

        if ($isCli) {
            echo "\n\033[1;33m⚡ Ace Debug Dump (dd) at {$file}:{$line}\033[0m\n";
            foreach ($vars as $var) {
                var_dump($var);
                echo "\n";
            }
            exit(1);
        }

        // Clear output buffers to ensure pristine debug output
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // Set response headers
        if (!headers_sent()) {
            header('HTTP/1.1 500 Internal Server Error');
            header('Content-Type: text/html; charset=utf-8');
        }

        echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ace DD Dump</title>
    <style>
        body {
            background-color: #0f172a;
            color: #e2e8f0;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            padding: 30px;
            margin: 0;
            line-height: 1.6;
        }
        .dd-container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: #1e293b;
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5), 0 8px 10px -6px rgba(0, 0, 0, 0.5);
            border: 1px solid #334155;
            overflow: hidden;
        }
        .dd-header {
            background-color: #0f172a;
            padding: 16px 24px;
            border-bottom: 1px solid #334155;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .dd-title {
            color: #38bdf8;
            font-weight: bold;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .dd-caller {
            color: #94a3b8;
            font-size: 13px;
            background-color: #1e293b;
            padding: 4px 10px;
            border-radius: 6px;
            border: 1px solid #334155;
        }
        .dd-body {
            padding: 24px;
            overflow-x: auto;
        }
        .dd-dump {
            margin: 0;
            font-size: 14px;
        }
        .dd-item {
            margin-bottom: 24px;
            padding-bottom: 24px;
            border-bottom: 1px solid #334155;
        }
        .dd-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        .prod-warning {
            background-color: #7f1d1d;
            color: #fca5a5;
            padding: 10px 20px;
            text-align: center;
            font-weight: bold;
            font-size: 13px;
            border-bottom: 1px solid #991b1b;
        }
    </style>
</head>
<body>';

        if (!$isDev) {
            echo '<div class="prod-warning">⚠️ WARNING: Running debug dump in production mode! Please remove all dd() calls from your codebase.</div>';
        }

        echo '
    <div class="dd-container">
        <div class="dd-header">
            <span class="dd-title">⚡ Ace Debug Dump</span>
            <span class="dd-caller">' . htmlspecialchars($file) . ':' . $line . '</span>
        </div>
        <div class="dd-body">';

        foreach ($vars as $var) {
            echo '<div class="dd-item"><pre class="dd-dump">';
            echo _ace_dd_format($var);
            echo '</pre></div>';
        }

        echo '  </div>
    </div>
</body>
</html>';
        exit(1);
    }
}

if (!function_exists('_ace_dd_format')) {
    /**
     * Internal formatting function for beautiful variable dumps.
     */
    function _ace_dd_format(mixed $var, int $depth = 0): string
    {
        $indent = str_repeat('    ', $depth);
        if ($var === null) {
            return '<span style="color: #64748b; font-weight: bold;">null</span>';
        }
        if (is_bool($var)) {
            $val = $var ? 'true' : 'false';
            return '<span style="color: #f97316; font-weight: bold;">' . $val . '</span>';
        }
        if (is_int($var) || is_float($var)) {
            return '<span style="color: #38bdf8;">' . $var . '</span>';
        }
        if (is_string($var)) {
            return '<span style="color: #10b981;">"' . htmlspecialchars($var) . '"</span> <span style="color: #64748b; font-size: 12px;">(length=' . strlen($var) . ')</span>';
        }
        if (is_array($var)) {
            if (empty($var)) {
                return '<span style="color: #cbd5e1;">[]</span>';
            }
            $count = count($var);
            $output = '<span style="color: #cbd5e1; font-weight: bold;">array:' . $count . '</span> [' . "\n";
            foreach ($var as $key => $val) {
                $formattedKey = is_int($key) ? '<span style="color: #f43f5e;">' . $key . '</span>' : '<span style="color: #e2e8f0;">"' . htmlspecialchars($key) . '"</span>';
                $output .= $indent . '    ' . $formattedKey . ' => ' . _ace_dd_format($val, $depth + 1) . ",\n";
            }
            $output .= $indent . ']';
            return $output;
        }
        if (is_object($var)) {
            $className = get_class($var);
            $properties = (array) $var;
            $output = '<span style="color: #a855f7; font-weight: bold;">object(' . $className . ')</span>' . ' {' . "\n";
            foreach ($properties as $key => $val) {
                $key = str_replace("\0*\0", 'protected:', $key);
                $key = str_replace("\0" . $className . "\0", 'private:', $key);
                $formattedKey = '<span style="color: #c084fc;">' . htmlspecialchars($key) . '</span>';
                $output .= $indent . '    ' . $formattedKey . ' => ' . _ace_dd_format($val, $depth + 1) . ",\n";
            }
            $output .= $indent . '}';
            return $output;
        }
        if (is_resource($var)) {
            return '<span style="color: #eab308; font-weight: bold;">resource</span> (' . get_resource_type($var) . ')';
        }
        return htmlspecialchars(print_r($var, true));
    }
}

if (!function_exists('back')) {
    /**
     * Redirect back to the previous page (HTTP Referer) or a fallback URL.
     *
     * @param string $fallback Fallback URL path if Referer is missing or external
     * @param int $statusCode HTTP status code (default 302)
     * @param array $flashData Session flash messages to carry over
     */
    function back(string $fallback = '/', int $statusCode = 302, array $flashData = []): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? $fallback;

        // Prevent open redirect vulnerabilities: Ensure referrer is internal
        $appUrl = env('APP_URL', '');
        if (!empty($appUrl)) {
            $host = parse_url($appUrl, PHP_URL_HOST);
            $refererHost = parse_url($referer, PHP_URL_HOST);

            // If the referer points to an external host, fallback to safe internal route
            if ($refererHost && $refererHost !== $host) {
                $referer = $fallback;
            }
        }

        redirect($referer, $statusCode, $flashData);
    }
}

if (!function_exists('flash_input')) {
    /**
     * Flash current request input data to the session.
     *
     * @param array|null $data The input data to flash (defaults to current request body)
     */
    function flash_input(?array $data = null): void
    {
        $request = Application::$app->request;
        $session = Application::$app->session;
        if ($request && $session) {
            $data = $data ?? $request->getBody();
            // Exclude sensitive fields from flashing
            $sensitiveFields = ['password', 'passwordConfirm', 'csrf_token'];
            foreach ($sensitiveFields as $field) {
                unset($data[$field]);
            }
            $session->setFlash('_old_input', $data);
        }
    }
}

if (!function_exists('old')) {
    /**
     * Retrieve the previous input value for a form field.
     * Checks both session-flashed old input and current request body.
     *
     * @param string $key The input field name
     * @param mixed $default Default value if field is missing
     * @return mixed
     */
    function old(string $key, mixed $default = null): mixed
    {
        // 1. Check session flashed old input
        $session = Application::$app->session;
        if ($session) {
            $oldInput = $session->getFlash('_old_input');
            if (is_array($oldInput) && isset($oldInput[$key])) {
                return $oldInput[$key];
            }
        }

        // 2. Check current request body input (in case of direct render on validation failure)
        $request = Application::$app->request;
        if ($request) {
            $body = $request->getBody();
            if (is_array($body) && isset($body[$key])) {
                return $body[$key];
            }
        }

        return $default;
    }
}

if (!function_exists('setting')) {
    /**
     * Get a setting from config/settings.json (with request-level caching and dot-notation support).
     */
    function setting(string $key, mixed $default = null): mixed
    {
        static $settings = null;

        if ($settings === null) {
            $settingsFile = Application::$ROOT_DIR . '/config/settings.json';
            if (file_exists($settingsFile)) {
                $content = @file_get_contents($settingsFile);
                $settings = json_decode($content, true);
                if (!is_array($settings)) {
                    $settings = [];
                }
            } else {
                $settings = [];
            }
        }

        // Support dot-notation access (e.g. setting('payment.provider'))
        $data = $settings;
        foreach (explode('.', $key) as $segment) {
            if (is_array($data) && array_key_exists($segment, $data)) {
                $data = $data[$segment];
            } else {
                return $default;
            }
        }

        return $data;
    }
}

if (!function_exists('format_price')) {
    /**
     * Format a price according to active currency settings.
     * Prevents division-by-zero errors, supports multiple currencies, and runs optimally.
     */
    function format_price(float|int $amount, ?string $currencyCode = null): string
    {
        $currency = strtoupper($currencyCode ?: setting('currency', 'NGN'));
        $exchangeRate = (float) setting('exchange_rate', 1500.00);

        // Prevent division-by-zero errors in production
        if ($exchangeRate <= 0) {
            $exchangeRate = 1.0;
        }

        switch ($currency) {
            case 'USD':
                return '$' . number_format($amount, 2);
            case 'EUR':
                return '€' . number_format($amount, 2);
            case 'GBP':
                return '£' . number_format($amount, 2);
            case 'NGN':
                return '₦' . number_format($amount, 2);
            case 'BOTH':
                // Base amount is assumed to be in NGN, convert to USD for secondary display
                $usdAmount = $amount / $exchangeRate;
                return '₦' . number_format($amount, 2) . ' ($' . number_format($usdAmount, 2) . ')';
            default:
                // Try dynamic symbol from configuration or default to currency code prefix
                $symbol = setting("currency_symbols.{$currency}", $currency . ' ');
                return $symbol . number_format($amount, 2);
        }
    }
}

if (!function_exists('event')) {
    /**
     * Dispatch an event to all registered listeners.
     *
     * @param string $event The event name
     * @param mixed ...$payload Data passed to listeners
     * @return array Listener responses
     */
    function event(string $event, mixed ...$payload): array
    {
        return \Ace\Event::dispatch($event, ...$payload);
    }
}
