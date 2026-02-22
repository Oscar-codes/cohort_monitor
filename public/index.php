<?php

/**
 * ============================================================
 *  Cohort Monitor — Application Entry Point
 * ============================================================
 *
 * All HTTP requests are funneled through this file.
 * Configure your web server to point the document root here.
 *
 * Apache  → public/.htaccess handles URL rewriting
 * Nginx   → configure try_files to route to public/index.php
 * PHP Dev → php -S localhost:8000 -t public
 */

// ─── Define the application root path ────────────────────────
define('APP_ROOT', dirname(__DIR__));

// ─── Load the bootstrap/autoloader ──────────────────────────
require_once APP_ROOT . '/bootstrap/app.php';

// ─── Create the router and load routes ──────────────────────
$router = new \App\Core\Router();

require_once APP_ROOT . '/routes/web.php';

// ─── Dispatch the request ───────────────────────────────────
$router->dispatch();
