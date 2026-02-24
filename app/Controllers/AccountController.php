<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Services\UserService;

/**
 * AccountController — Self-service profile & password management.
 * Available to all authenticated users.
 */
class AccountController extends Controller
{
    private UserService $userService;

    public function __construct()
    {
        Auth::requireLogin();
        $this->userService = new UserService();
    }

    /** Show the user's own profile page with edit forms. */
    public function profile(): void
    {
        $user = $this->userService->getUserById(Auth::id());

        $this->view('account.profile', [
            'pageTitle'  => 'Mi Cuenta',
            'activePage' => 'account',
            'user'       => $user,
        ]);
    }

    /** Handle profile update (name + email). */
    public function updateProfile(): void
    {
        try {
            $data = [
                'full_name' => trim($this->input('full_name', '')),
                'email'     => trim($this->input('email', '')),
            ];

            $this->userService->updateProfile(Auth::id(), $data);
            Auth::flash('success', 'Perfil actualizado correctamente.');
        } catch (\InvalidArgumentException $e) {
            Auth::flash('error', $e->getMessage());
        }

        $this->redirect('/account');
    }

    /** Handle password change. */
    public function changePassword(): void
    {
        $currentPassword = $this->input('current_password', '');
        $newPassword      = $this->input('new_password', '');
        $confirmPassword  = $this->input('confirm_password', '');

        if ($newPassword !== $confirmPassword) {
            Auth::flash('error', 'La nueva contraseña y su confirmación no coinciden.');
            $this->redirect('/account');
            return;
        }

        try {
            $this->userService->changePassword(Auth::id(), $currentPassword, $newPassword);
            Auth::flash('success', 'Contraseña actualizada correctamente.');
        } catch (\InvalidArgumentException $e) {
            Auth::flash('error', $e->getMessage());
        }

        $this->redirect('/account');
    }
}
