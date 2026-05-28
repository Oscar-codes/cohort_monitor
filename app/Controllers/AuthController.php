<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Services\AuthService;
use Throwable;

/**
 * AuthController — Login / Logout.
 */
class AuthController extends Controller
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    /** Show login form. */
    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirect('/');
            return;
        }

        $error = Auth::getFlash('login_error');
        $this->view('auth.login', [
            'pageTitle' => 'Iniciar Sesión',
            'error'     => $error,
        ], null); // no layout
    }

    /** Handle login POST. */
    public function login(): void
    {
        $identifier = trim($this->input('username', ''));
        $password = $this->input('password', '');

        if (empty($identifier) || empty($password)) {
            Auth::flash('login_error', 'Ingrese usuario/correo y contraseña.');
            $this->redirect('/login');
            return;
        }

        try {
            $user = $this->authService->attempt($identifier, $password);
        } catch (Throwable $e) {
            error_log('[auth] login error: ' . $e->getMessage());
            Auth::flash('login_error', 'No se pudo procesar la solicitud. Intenta nuevamente en unos segundos.');
            $this->redirect('/login');
            return;
        }

        if (!$user) {
            Auth::flash('login_error', 'Credenciales incorrectas o cuenta desactivada.');
            $this->redirect('/login');
            return;
        }

        $this->redirect('/');
    }

    /** Handle logout. */
    public function logout(): void
    {
        $this->authService->logout();
        $this->redirect('/login');
    }
}
