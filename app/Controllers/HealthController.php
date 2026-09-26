<?php
namespace App\Controllers;

use App\Core\Controller;

/**
 * HealthController
 *
 * Provides a public, unauthenticated endpoint for Railway healthchecks.
 */
class HealthController extends Controller
{
    public function check(): void
    {
        http_response_code(200);
        header('Content-Type: text/plain');
        echo 'OK';
    }
}

