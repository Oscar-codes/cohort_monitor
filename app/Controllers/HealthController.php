<?php

namespace App\Controllers;

use App\Core\Controller;

/**
 * HealthController
 *
 * Public, unauthenticated liveness endpoint for platform healthchecks
 * (Railway, load balancers, uptime monitors). Deliberately does not
 * touch the database or session — it only confirms PHP is responding.
 */
class HealthController extends Controller
{
    public function check(): void
    {
        $this->json([
            'status' => 'ok',
            'time'   => date('c'),
        ]);
    }
}
