<?php

namespace Ace;

class Response
{
    /**
     * Set HTTP response status code
     */
    public function setStatusCode(int $code): void
    {
        http_response_code($code);
    }

    /**
     * Set HTTP response header
     */
    public function setHeader(string $name, string $value): void
    {
        if (!headers_sent()) {
            header("$name: $value");
        }
    }

    /**
     * Redirect to another URL, auto-detecting base subdirectories for local environment
     */
    public function redirect(string $url, int $statusCode = 302, array $flashData = []): void
    {
        $this->setStatusCode($statusCode);

        if (!empty($flashData)) {
            $session = Application::$app->session;
            if ($session) {
                foreach ($flashData as $key => $value) {
                    $session->setFlash($key, $value);
                }
            }
        }

        // If it's an absolute URL, redirect directly
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            if (!headers_sent()) {
                header('Location: ' . $url);
            }
            if (php_sapi_name() !== 'cli') {
                exit;
            }
            return;
        }

        // Prepend base folder for local subdirectory installations (e.g. /mvc/public)
        if (str_starts_with($url, '/')) {
            $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
            $baseDir = dirname($scriptName);
            $baseDir = str_replace('\\', '/', $baseDir);

            if ($baseDir !== '/' && !empty($baseDir)) {
                $url = rtrim($baseDir, '/') . $url;
            }
        }

        if (!headers_sent()) {
            header('Location: ' . $url);
        }
        if (php_sapi_name() !== 'cli') {
            exit;
        }
    }

    /**
     * Return JSON response
     */
    public function json(array $data, int $statusCode = 200): void
    {
        $this->setStatusCode($statusCode);
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        echo json_encode($data);
        if (php_sapi_name() !== 'cli') {
            exit;
        }
    }
}
