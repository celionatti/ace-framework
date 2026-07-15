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
     * Dump and Die — Premium debug dump with rich metadata.
     * Accepts multiple arguments: dd($user, $request, $config);
     */
    function dd(mixed ...$vars): void
    {
        // Get caller details
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $caller = $backtrace[0] ?? [];
        $file = $caller['file'] ?? 'Unknown';
        $line = $caller['line'] ?? 0;

        // Clean up file path for display
        $displayFile = str_replace('\\', '/', $file);
        if (defined('ROOT_DIR')) {
            $displayFile = str_replace(str_replace('\\', '/', ROOT_DIR) . '/', '', $displayFile);
        }

        $isCli = php_sapi_name() === 'cli';
        $isDev = (($_ENV['APP_ENV'] ?? 'development') === 'development');
        $varCount = count($vars);
        $memUsage = round(memory_get_usage(true) / 1024 / 1024, 2);
        $peakMem = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
        $timestamp = date('Y-m-d H:i:s');

        // CLI mode — enhanced terminal output
        if ($isCli) {
            echo "\n\033[1;33m⚡ Ace Debug Dump (dd)\033[0m\n";
            echo "\033[90m   File: {$displayFile}:{$line}\033[0m\n";
            echo "\033[90m   Time: {$timestamp} | Memory: {$memUsage}MB (peak: {$peakMem}MB)\033[0m\n";
            echo "\033[90m   " . str_repeat('─', 60) . "\033[0m\n";
            foreach ($vars as $i => $var) {
                if ($varCount > 1) {
                    echo "\033[1;36m   #{$i} \033[90m(" . _ace_dd_type_label($var) . ")\033[0m\n";
                }
                var_dump($var);
                echo "\n";
            }
            exit(1);
        }

        // Clear output buffers
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        if (!headers_sent()) {
            header('HTTP/1.1 500 Internal Server Error');
            header('Content-Type: text/html; charset=utf-8');
        }

        // Request context
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'N/A';
        $requestUri = $_SERVER['REQUEST_URI'] ?? 'N/A';
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'N/A';
        $phpVersion = PHP_VERSION;

        echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⚡ Ace DD</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{background:#0b1120;color:#e2e8f0;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace;padding:24px;min-height:100vh;line-height:1.6}
        .dd-wrap{max-width:1200px;margin:0 auto}
        .dd-prod-warn{background:linear-gradient(135deg,#7f1d1d,#991b1b);color:#fca5a5;padding:12px 24px;text-align:center;font-weight:700;font-size:13px;border-radius:12px 12px 0 0;letter-spacing:.5px}
        .dd-card{background:#141c2e;border-radius:12px;box-shadow:0 20px 60px -15px rgba(0,0,0,.6);border:1px solid #1e293b;overflow:hidden;margin-bottom:24px}
        .dd-header{background:linear-gradient(135deg,#0f172a 0%,#1a1f3a 100%);padding:18px 24px;border-bottom:1px solid #1e293b;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px}
        .dd-brand{display:flex;align-items:center;gap:10px}
        .dd-brand-icon{font-size:20px}
        .dd-brand-text{font-weight:800;font-size:16px;background:linear-gradient(135deg,#38bdf8,#818cf8);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
        .dd-meta{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
        .dd-badge{font-size:11px;padding:4px 10px;border-radius:6px;font-weight:600;border:1px solid}
        .dd-badge-file{color:#38bdf8;border-color:#1e3a5f;background:rgba(56,189,248,.08)}
        .dd-badge-count{color:#a78bfa;border-color:#3b2f63;background:rgba(167,139,250,.08)}
        .dd-badge-mem{color:#34d399;border-color:#1a3a2a;background:rgba(52,211,153,.08)}
        .dd-context{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1px;background:#1e293b;border-bottom:1px solid #1e293b}
        .dd-ctx-item{background:#111827;padding:10px 20px;font-size:12px}
        .dd-ctx-label{color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:1px;font-size:10px;margin-bottom:2px}
        .dd-ctx-val{color:#cbd5e1}
        .dd-body{padding:20px 24px}
        .dd-item{margin-bottom:16px;background:#0f172a;border:1px solid #1e293b;border-radius:10px;overflow:hidden}
        .dd-item:last-child{margin-bottom:0}
        .dd-item-header{display:flex;align-items:center;gap:10px;padding:12px 16px;background:#0d1425;border-bottom:1px solid #1e293b;cursor:default}
        .dd-item-idx{color:#64748b;font-size:11px;font-weight:700;min-width:24px}
        .dd-type-badge{font-size:10px;padding:3px 8px;border-radius:4px;font-weight:700;text-transform:uppercase;letter-spacing:.5px}
        .dd-type-string{color:#34d399;background:rgba(52,211,153,.12);border:1px solid rgba(52,211,153,.2)}
        .dd-type-integer,.dd-type-float,.dd-type-double{color:#38bdf8;background:rgba(56,189,248,.12);border:1px solid rgba(56,189,248,.2)}
        .dd-type-boolean{color:#fb923c;background:rgba(251,146,60,.12);border:1px solid rgba(251,146,60,.2)}
        .dd-type-null{color:#94a3b8;background:rgba(148,163,184,.12);border:1px solid rgba(148,163,184,.2)}
        .dd-type-array{color:#a78bfa;background:rgba(167,139,250,.12);border:1px solid rgba(167,139,250,.2)}
        .dd-type-object{color:#f472b6;background:rgba(244,114,182,.12);border:1px solid rgba(244,114,182,.2)}
        .dd-type-resource{color:#facc15;background:rgba(250,204,21,.12);border:1px solid rgba(250,204,21,.2)}
        .dd-item-size{color:#475569;font-size:11px;margin-left:auto}
        .dd-item-body{padding:14px 16px;overflow-x:auto}
        .dd-item-body pre{margin:0;font-size:13px;line-height:1.7;white-space:pre-wrap;word-break:break-word}
        .dd-toggle{cursor:pointer;user-select:none;color:#475569;font-size:11px;transition:color .15s}.dd-toggle:hover{color:#94a3b8}
        .dd-collapsed{display:none}
        .dd-key-int{color:#f43f5e}.dd-key-str{color:#e2e8f0}
        .dd-val-str{color:#34d399}.dd-val-num{color:#38bdf8}.dd-val-bool{color:#fb923c;font-weight:700}.dd-val-null{color:#64748b;font-weight:700}
        .dd-val-obj{color:#f472b6;font-weight:700}.dd-val-res{color:#facc15;font-weight:700}
        .dd-strlen{color:#475569;font-size:11px;font-style:italic}
        .dd-arrow{color:#334155}
        .dd-copy{position:absolute;top:8px;right:8px;background:rgba(255,255,255,.06);border:1px solid #334155;color:#94a3b8;padding:4px 10px;border-radius:6px;font-size:11px;cursor:pointer;font-family:inherit;transition:all .15s}.dd-copy:hover{background:rgba(255,255,255,.12);color:#e2e8f0}
        .dd-item-body-wrap{position:relative}
        .dd-footer{padding:12px 24px;border-top:1px solid #1e293b;text-align:center;font-size:11px;color:#334155}
    </style>
</head>
<body>
<div class="dd-wrap">';

        if (!$isDev) {
            echo '<div class="dd-prod-warn">⚠️ WARNING: Debug dump active in production! Remove all dd() calls before deploying.</div>';
        }

        echo '<div class="dd-card">
    <div class="dd-header">
        <div class="dd-brand">
            <span class="dd-brand-icon">⚡</span>
            <span class="dd-brand-text">Ace Debug Dump</span>
        </div>
        <div class="dd-meta">
            <span class="dd-badge dd-badge-file">' . htmlspecialchars($displayFile) . ':' . $line . '</span>
            <span class="dd-badge dd-badge-count">' . $varCount . ' variable' . ($varCount !== 1 ? 's' : '') . '</span>
            <span class="dd-badge dd-badge-mem">' . $memUsage . 'MB / ' . $peakMem . 'MB peak</span>
        </div>
    </div>
    <div class="dd-context">
        <div class="dd-ctx-item"><div class="dd-ctx-label">Method</div><div class="dd-ctx-val">' . htmlspecialchars($requestMethod) . '</div></div>
        <div class="dd-ctx-item"><div class="dd-ctx-label">URI</div><div class="dd-ctx-val">' . htmlspecialchars($requestUri) . '</div></div>
        <div class="dd-ctx-item"><div class="dd-ctx-label">IP</div><div class="dd-ctx-val">' . htmlspecialchars($clientIp) . '</div></div>
        <div class="dd-ctx-item"><div class="dd-ctx-label">Time</div><div class="dd-ctx-val">' . $timestamp . '</div></div>
        <div class="dd-ctx-item"><div class="dd-ctx-label">PHP</div><div class="dd-ctx-val">' . $phpVersion . '</div></div>
    </div>
    <div class="dd-body">';

        foreach ($vars as $i => $var) {
            $typeLabel = _ace_dd_type_label($var);
            $typeCss = strtolower(str_replace(' ', '-', $typeLabel));
            $sizeInfo = _ace_dd_size_info($var);
            $uid = 'dd_' . $i . '_' . mt_rand(1000, 9999);

            echo '<div class="dd-item">
                <div class="dd-item-header">
                    <span class="dd-item-idx">#' . $i . '</span>
                    <span class="dd-type-badge dd-type-' . $typeCss . '">' . $typeLabel . '</span>
                    ' . ($sizeInfo ? '<span class="dd-item-size">' . $sizeInfo . '</span>' : '') . '
                </div>
                <div class="dd-item-body-wrap">
                    <button class="dd-copy" onclick="let t=this.closest(\'.dd-item-body-wrap\').querySelector(\'pre\').innerText;navigator.clipboard.writeText(t);this.textContent=\'Copied!\';setTimeout(()=>this.textContent=\'Copy\',1500)">Copy</button>
                    <div class="dd-item-body"><pre>' . _ace_dd_format($var, 0, $uid) . '</pre></div>
                </div>
            </div>';
        }

        echo '</div>
    <div class="dd-footer">Ace Framework • Debug Dump • ' . $timestamp . '</div>
</div></div>
<script>
function aceToggle(id){var e=document.getElementById(id);var a=document.getElementById(id+"_arrow");if(e){if(e.classList.contains("dd-collapsed")){e.classList.remove("dd-collapsed");if(a)a.textContent="▼"}else{e.classList.add("dd-collapsed");if(a)a.textContent="▶"}}}
</script>
</body></html>';
        exit(1);
    }
}

if (!function_exists('_ace_dd_type_label')) {
    /**
     * Get a human-readable type label for a variable.
     */
    function _ace_dd_type_label(mixed $var): string
    {
        if ($var === null) return 'null';
        if (is_bool($var)) return 'boolean';
        if (is_int($var)) return 'integer';
        if (is_float($var)) return 'float';
        if (is_string($var)) return 'string';
        if (is_array($var)) return 'array';
        if (is_object($var)) return 'object';
        if (is_resource($var)) return 'resource';
        return 'unknown';
    }
}

if (!function_exists('_ace_dd_size_info')) {
    /**
     * Get size/length info for a variable.
     */
    function _ace_dd_size_info(mixed $var): string
    {
        if (is_string($var)) return strlen($var) . ' chars';
        if (is_array($var)) return count($var) . ' items';
        if (is_object($var)) return count((array) $var) . ' properties';
        return '';
    }
}

if (!function_exists('_ace_dd_format')) {
    /**
     * Recursively format a variable for the debug dump with collapsible sections.
     */
    function _ace_dd_format(mixed $var, int $depth = 0, string $uid = ''): string
    {
        $indent = str_repeat('  ', $depth);
        $maxDepth = 10;

        if ($depth > $maxDepth) {
            return '<span class="dd-val-null">… (max depth reached)</span>';
        }

        if ($var === null) {
            return '<span class="dd-val-null">null</span>';
        }
        if (is_bool($var)) {
            return '<span class="dd-val-bool">' . ($var ? 'true' : 'false') . '</span>';
        }
        if (is_int($var) || is_float($var)) {
            return '<span class="dd-val-num">' . $var . '</span>';
        }
        if (is_string($var)) {
            $display = htmlspecialchars($var);
            // Truncate very long strings visually
            $len = strlen($var);
            if ($len > 500) {
                $display = htmlspecialchars(substr($var, 0, 500)) . '<span class="dd-strlen">… truncated</span>';
            }
            return '<span class="dd-val-str">"' . $display . '"</span> <span class="dd-strlen">(length=' . $len . ')</span>';
        }
        if (is_array($var)) {
            if (empty($var)) {
                return '<span class="dd-val-null">[] <span class="dd-strlen">(empty)</span></span>';
            }
            $count = count($var);
            $toggleId = $uid . '_arr_' . $depth . '_' . mt_rand(100, 999);
            $output = '<span class="dd-type-badge dd-type-array" style="font-size:11px;vertical-align:middle">array:' . $count . '</span> ';
            $output .= '<span class="dd-toggle" id="' . $toggleId . '_arrow" onclick="aceToggle(\'' . $toggleId . '\')">▼</span>' . "\n";
            $output .= '<span id="' . $toggleId . '">';
            foreach ($var as $key => $val) {
                $fKey = is_int($key)
                    ? '<span class="dd-key-int">' . $key . '</span>'
                    : '<span class="dd-key-str">"' . htmlspecialchars($key) . '"</span>';
                $output .= $indent . '  ' . $fKey . ' <span class="dd-arrow">=></span> ' . _ace_dd_format($val, $depth + 1, $toggleId . '_' . $key) . "\n";
            }
            $output .= '</span>';
            return $output;
        }
        if (is_object($var)) {
            $className = get_class($var);
            $props = (array) $var;
            if (empty($props)) {
                return '<span class="dd-val-obj">' . $className . '</span> <span class="dd-strlen">{}</span>';
            }
            $toggleId = $uid . '_obj_' . $depth . '_' . mt_rand(100, 999);
            $output = '<span class="dd-val-obj">' . htmlspecialchars($className) . '</span> ';
            $output .= '<span class="dd-toggle" id="' . $toggleId . '_arrow" onclick="aceToggle(\'' . $toggleId . '\')">▼</span>' . "\n";
            $output .= '<span id="' . $toggleId . '">';
            foreach ($props as $key => $val) {
                // Decode PHP's internal property naming
                $visibility = '';
                if (str_contains($key, "\0*\0")) {
                    $key = str_replace("\0*\0", '', $key);
                    $visibility = '<span class="dd-strlen">#protected</span> ';
                } elseif (str_contains($key, "\0")) {
                    $key = preg_replace('/\0[^\0]+\0/', '', $key);
                    $visibility = '<span class="dd-strlen">-private</span> ';
                }
                $fKey = '<span class="dd-key-str">' . htmlspecialchars($key) . '</span>';
                $output .= $indent . '  ' . $visibility . $fKey . ' <span class="dd-arrow">=></span> ' . _ace_dd_format($val, $depth + 1, $toggleId . '_' . $key) . "\n";
            }
            $output .= '</span>';
            return $output;
        }
        if (is_resource($var)) {
            return '<span class="dd-val-res">resource(' . get_resource_type($var) . ')</span>';
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

if (!function_exists('slugify')) {
    /**
     * Generate a URL-friendly slug from a string.
     * Transliterates non-ASCII characters, removes special chars, and handles custom delimiters.
     *
     * @param string $text The input string to slugify
     * @param string $divider The word divider (default is '-')
     * @return string The generated slug
     */
    function slugify(string $text, string $divider = '-'): string
    {
        // Transliterate non-ASCII characters to ASCII (e.g. "München" -> "Munchen")
        if (class_exists('Transliterator') || function_exists('transliterator_transliterate')) {
            $text = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
        } else {
            // Fallback transliteration via iconv if Transliterator is unavailable
            $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        }

        // Lowercase the text (in case transliterator was not used/installed or iconv was used)
        $text = strtolower($text);

        // Replace non-alphanumeric characters with the divider
        $text = preg_replace('/[^a-z0-9]+/', $divider, $text);

        // Remove duplicate dividers
        $text = preg_replace('/' . preg_quote($divider, '/') . '+/', $divider, $text);

        // Trim leading and trailing dividers
        return trim($text, $divider);
    }
}
