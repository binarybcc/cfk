<?php

declare(strict_types=1);

namespace CFK\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

/**
 * Admin Authentication Middleware
 *
 * Verifies that user is logged in as admin before allowing access to admin routes.
 * Redirects to login page if not authenticated.
 */
class AdminAuthMiddleware implements MiddlewareInterface
{
    /**
     * Process the request
     *
     * @param Request $request PSR-7 request
     * @param RequestHandler $handler Request handler
     * @return Response PSR-7 response
     */
    public function process(Request $request, RequestHandler $handler): Response
    {
        // Check if user is logged in (session-based authentication)
        // The isLoggedIn() function checks $_SESSION['cfk_admin_id']
        if (! $this->isAuthenticated()) {
            // Redirect to admin login page
            $response = new SlimResponse();
            $response = $response
                ->withHeader('Location', baseUrl('admin/login.php'))
                ->withStatus(302);

            return $response;
        }

        // User is authenticated, proceed with request
        return $handler->handle($request);
    }

    /**
     * Check if admin is authenticated
     *
     * @return bool True if authenticated, false otherwise
     */
    private function isAuthenticated(): bool
    {
        // Regenerate session if needed (security measure)
        $this->regenerateSessionIfNeeded();

        // Check for admin session
        return isset($_SESSION['cfk_admin_id']) && ! empty($_SESSION['cfk_admin_id']);
    }

    /**
     * Regenerate session ID periodically for security
     *
     * @return void
     */
    private function regenerateSessionIfNeeded(): void
    {
        // Only regenerate if not done recently
        if (! isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
}
