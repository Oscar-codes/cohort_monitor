<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Services\UserService;

/**
 * UserController — Admin-only user management (CRUD).
 */
class UserController extends Controller
{
    private UserService $userService;

    public function __construct()
    {
        Auth::requireRole('admin');
        $this->userService = new UserService();
    }

    /** List all users. */
    public function index(): void
    {
        $users = $this->userService->getAllUsers();
        $this->view('users.index', [
            'pageTitle'  => 'Gestión de Usuarios',
            'activePage' => 'users',
            'users'      => $users,
        ]);
    }

    /** Show create form. */
    public function create(): void
    {
        $this->view('users.create', [
            'pageTitle'  => 'Nuevo Usuario',
            'activePage' => 'users',
        ]);
    }

    /** Store a new user. */
    public function store(): void
    {
        try {
            $data = [
                'username'  => $this->input('username'),
                'email'     => $this->input('email'),
                'full_name' => $this->input('full_name'),
                'role'      => $this->input('role'),
                'password'  => $this->input('password'),
                'is_active' => $this->input('is_active', 1),
            ];
            $this->userService->createUser($data);
            Auth::flash('success', 'Usuario creado exitosamente.');
            $this->redirect('/users');
        } catch (\InvalidArgumentException $e) {
            Auth::flash('error', $e->getMessage());
            $this->redirect('/users/create');
        }
    }

    /** Show edit form. */
    public function edit(string $id): void
    {
        $user = $this->userService->getUserById((int) $id);
        if (!$user) {
            http_response_code(404);
            $this->view('errors.404', ['pageTitle' => 'No Encontrado'], null);
            return;
        }

        $this->view('users.edit', [
            'pageTitle'  => 'Editar: ' . $user['full_name'],
            'activePage' => 'users',
            'user'       => $user,
        ]);
    }

    /** Update a user. */
    public function update(string $id): void
    {
        try {
            $data = [
                'username'  => $this->input('username'),
                'email'     => $this->input('email'),
                'full_name' => $this->input('full_name'),
                'role'      => $this->input('role'),
                'password'  => $this->input('password'), // empty = keep current
                'is_active' => $this->input('is_active', 1),
            ];
            $this->userService->updateUser((int) $id, $data);
            Auth::flash('success', 'Usuario actualizado.');
            $this->redirect('/users');
        } catch (\InvalidArgumentException $e) {
            Auth::flash('error', $e->getMessage());
            $this->redirect('/users/' . $id . '/edit');
        }
    }

    /** Delete a user. */
    public function destroy(string $id): void
    {
        try {
            $this->userService->deleteUser((int) $id);
            Auth::flash('success', 'Usuario eliminado.');
        } catch (\InvalidArgumentException $e) {
            Auth::flash('error', $e->getMessage());
        }
        $this->redirect('/users');
    }
}
