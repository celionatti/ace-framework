<?php

namespace Ace\Middlewares;

use Ace\Application;
use Ace\Middleware;
use Exception;

class CsrfMiddleware extends Middleware
{
    /**
     * Run the CSRF verification check.
     *
     * Validates CSRF tokens on all state-changing HTTP methods:
     * POST, PUT, PATCH, DELETE.
     *
     * Uses timing-safe comparison and enforces token expiration.
     */
    protected function run(): void
    {
        $request = Application::$app->request;
        $method = strtoupper($request->method());

        // Only validate on state-changing methods
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $body = $request->getBody();
            $submittedToken = $body['csrf_token'] ?? '';

            // Use the Session's dedicated validation method (handles expiry + timing-safe check)
            if (!Application::$app->session->validateCsrfToken($submittedToken)) {
                throw new Exception("CSRF token validation failed. Unauthorized request.", 403);
            }
        }
    }
}
