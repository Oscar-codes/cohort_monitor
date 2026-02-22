<?php

/**
 * Web Routes
 *
 * Define all application routes here.
 * Each route maps a URI pattern to a [Controller::class, 'method'] pair.
 *
 * Available methods: $router->get(), $router->post(), $router->put(), $router->delete()
 *
 * Route parameters use {param} syntax:
 *   $router->get('/cohorts/{id}', [CohortController::class, 'show']);
 */

use App\Controllers\DashboardController;
use App\Controllers\CohortController;

// ─── Dashboard ───────────────────────────────────────────────
$router->get('/', [DashboardController::class, 'index'], 'dashboard');

// ─── Cohorts ─────────────────────────────────────────────────
$router->get('/cohorts',            [CohortController::class, 'index'],   'cohorts.index');
$router->get('/cohorts/create',     [CohortController::class, 'create'],  'cohorts.create');
$router->post('/cohorts',           [CohortController::class, 'store'],   'cohorts.store');
$router->get('/cohorts/{id}',       [CohortController::class, 'show'],    'cohorts.show');
$router->get('/cohorts/{id}/edit',  [CohortController::class, 'edit'],    'cohorts.edit');
$router->put('/cohorts/{id}',       [CohortController::class, 'update'],  'cohorts.update');
$router->delete('/cohorts/{id}',    [CohortController::class, 'destroy'], 'cohorts.destroy');
