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
use App\Controllers\AuthController;
use App\Controllers\UserController;
use App\Controllers\MarketingController;
use App\Controllers\AlertController;
use App\Controllers\CommentController;

// ─── Auth (public) ───────────────────────────────────────────
$router->get('/login',  [AuthController::class, 'showLogin'], 'auth.login');
$router->post('/login', [AuthController::class, 'login'],     'auth.login.post');
$router->get('/logout', [AuthController::class, 'logout'],    'auth.logout');

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

// ─── Cohort comments ────────────────────────────────────────
$router->post('/cohorts/{id}/comments', [CommentController::class, 'store'], 'comments.store');

// ─── Users (admin) ──────────────────────────────────────────
$router->get('/users',             [UserController::class, 'index'],   'users.index');
$router->get('/users/create',      [UserController::class, 'create'],  'users.create');
$router->post('/users',            [UserController::class, 'store'],   'users.store');
$router->get('/users/{id}/edit',   [UserController::class, 'edit'],    'users.edit');
$router->put('/users/{id}',        [UserController::class, 'update'],  'users.update');
$router->delete('/users/{id}',     [UserController::class, 'destroy'], 'users.destroy');

// ─── Marketing (admin + marketing) ─────────────────────────
$router->get('/marketing',                    [MarketingController::class, 'index'],  'marketing.index');
$router->get('/cohorts/{id}/marketing',       [MarketingController::class, 'show'],   'marketing.show');
$router->post('/cohorts/{id}/marketing',      [MarketingController::class, 'update'], 'marketing.update');

// ─── Alerts (admin) ─────────────────────────────────────────
$router->get('/alerts', [AlertController::class, 'index'], 'alerts.index');
