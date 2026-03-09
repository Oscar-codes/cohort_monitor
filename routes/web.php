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
use App\Controllers\ReportController;
use App\Controllers\ImportCohortController;
use App\Controllers\AccountController;
use App\Controllers\CoachCalendarController;

// ─── Auth (public) ───────────────────────────────────────────
$router->get('/login',  [AuthController::class, 'showLogin'], 'auth.login');
$router->post('/login', [AuthController::class, 'login'],     'auth.login.post');
$router->get('/logout', [AuthController::class, 'logout'],    'auth.logout');

// ─── Dashboard ───────────────────────────────────────────────
$router->get('/', [DashboardController::class, 'index'], 'dashboard');

// ─── Account (self-service, all authenticated) ─────────────
$router->get('/account',          [AccountController::class, 'profile'],        'account.profile');
$router->post('/account',         [AccountController::class, 'updateProfile'],  'account.update');
$router->post('/account/password',[AccountController::class, 'changePassword'], 'account.password');

// ─── Cohorts ─────────────────────────────────────────────────
$router->get('/cohorts',                  [CohortController::class, 'index'],   'cohorts.index');
$router->get('/cohorts/create',           [CohortController::class, 'create'],  'cohorts.create');
$router->get('/cohorts/import',           [ImportCohortController::class, 'showForm'],         'cohorts.import');
$router->post('/cohorts/import',          [ImportCohortController::class, 'handleImport'],     'cohorts.import.post');
$router->get('/cohorts/import/template',  [ImportCohortController::class, 'downloadTemplate'], 'cohorts.import.template');
$router->post('/cohorts',                 [CohortController::class, 'store'],   'cohorts.store');
$router->get('/cohorts/{id}',             [CohortController::class, 'show'],    'cohorts.show');
$router->get('/cohorts/{id}/edit',        [CohortController::class, 'edit'],    'cohorts.edit');
$router->put('/cohorts/{id}',             [CohortController::class, 'update'],  'cohorts.update');
$router->delete('/cohorts/{id}',          [CohortController::class, 'destroy'], 'cohorts.destroy');

// ─── Cohort comments ────────────────────────────────────────
$router->post('/cohorts/{id}/comments', [CommentController::class, 'store'], 'comments.store');

// ─── Users (admin) ──────────────────────────────────────────
$router->get('/users',             [UserController::class, 'index'],   'users.index');
$router->get('/users/create',      [UserController::class, 'create'],  'users.create');
$router->post('/users',            [UserController::class, 'store'],   'users.store');
$router->get('/users/{id}/edit',   [UserController::class, 'edit'],    'users.edit');
$router->put('/users/{id}',        [UserController::class, 'update'],  'users.update');
$router->delete('/users/{id}',     [UserController::class, 'destroy'], 'users.destroy');
$router->post('/users/{id}/toggle-status',  [UserController::class, 'toggleStatus'],  'users.toggle');
$router->post('/users/{id}/reset-password', [UserController::class, 'resetPassword'], 'users.reset');

// ─── Marketing (admin + marketing) ─────────────────────────
$router->get('/marketing',                    [MarketingController::class, 'index'],  'marketing.index');
$router->get('/cohorts/{id}/marketing',       [MarketingController::class, 'show'],   'marketing.show');
$router->post('/cohorts/{id}/marketing',      [MarketingController::class, 'update'], 'marketing.update');

// ─── Reports (all authenticated) ────────────────────────────
$router->get('/reports',              [ReportController::class, 'index'],       'reports.index');
$router->get('/reports/export/excel', [ReportController::class, 'exportExcel'], 'reports.export.excel');
$router->get('/reports/export/pdf',   [ReportController::class, 'exportPdf'],   'reports.export.pdf');

// ─── Alerts (all authenticated) ─────────────────────────────
$router->get('/alerts', [AlertController::class, 'index'], 'alerts.index');

// ─── Coach Calendar (admin) ──────────────────────────────────
$router->get('/coaches', [CoachCalendarController::class, 'index'], 'coaches.index');
