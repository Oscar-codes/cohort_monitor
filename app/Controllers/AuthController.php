<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Services\AuthService;

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
        $username = trim($this->input('username', ''));
        $password = $this->input('password', '');

        if (empty($username) || empty($password)) {
            Auth::flash('login_error', 'Ingrese usuario y contraseña.');
            $this->redirect('/login');
            return;
        }

        $user = $this->authService->attempt($username, $password);

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
