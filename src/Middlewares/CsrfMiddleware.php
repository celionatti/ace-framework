<?php

namespace Ace\Middlewares;

use Ace\Application;
use Ace\Middleware;
use Exception;

class CsrfMiddleware extends Middleware
{
    /**
     * Run the CSRF verification check
     */
    protected function run(): void
    {
        $request = Application::$app->request;

        if ($request->isPost()) {
            $body = $request->getBody();
            $submittedToken = $body['csrf_token'] ?? '';
            $sessionToken = Application::$app->session->getCsrfToken();

            if (empty($submittedToken) || !hash_equals($sessionToken, $submittedToken)) {
                throw new Exception("CSRF token validation failed. Unauthorized request.", 403);
            }
        }
    }
}
