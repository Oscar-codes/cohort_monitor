<?php

namespace App\Services;

use App\Repositories\UserRepository;
use App\Repositories\AuditRepository;
use App\Core\Auth;

/**
 * UserService — Business logic for user management (admin only).
 */
class UserService
{
    private UserRepository  $userRepo;
    private AuditRepository $auditRepo;

    private const VALID_ROLES = ['admin', 'admissions_b2b', 'admissions_b2c', 'marketing'];

    public function __construct()
    {
        $this->userRepo  = new UserRepository();
        $this->auditRepo = new AuditRepository();
    }

    public function getAllUsers(): array
    {
        return $this->userRepo->findAll();
    }

    public function getUserById(int $id): ?array
    {
        return $this->userRepo->findById($id);
    }

    /**
     * Create a new user (admin action).
     */
    public function createUser(array $data): int
    {
        $this->validate($data);

        if ($this->userRepo->findByUsername($data['username'])) {
            throw new \InvalidArgumentException('El nombre de usuario ya existe.');
        }
        if ($this->userRepo->findByEmail($data['email'])) {
            throw new \InvalidArgumentException('El correo electrónico ya existe.');
        }

        $data['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);

        $id = $this->userRepo->create($data);

        $this->auditRepo->log([
            'user_id'     => Auth::id(),
            'action'      => 'create_user',
            'entity_type' => 'user',
            'entity_id'   => $id,
            'new_values'  => ['username' => $data['username'], 'role' => $data['role']],
        ]);

        return $id;
    }

    /**
     * Update an existing user (admin action).
     */
    public function updateUser(int $id, array $data): bool
    {
        $existing = $this->userRepo->findById($id);
        if (!$existing) {
            throw new \InvalidArgumentException('Usuario no encontrado.');
        }

        $this->validate($data, $id);

        // Check unique constraints
        $byUsername = $this->userRepo->findByUsername($data['username']);
        if ($byUsername && (int) $byUsername['id'] !== $id) {
            throw new \InvalidArgumentException('El nombre de usuario ya existe.');
        }
        $byEmail = $this->userRepo->findByEmail($data['email']);
        if ($byEmail && (int) $byEmail['id'] !== $id) {
            throw new \InvalidArgumentException('El correo electrónico ya existe.');
        }

        // Only hash if a new password is provided
        if (!empty($data['password'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        $result = $this->userRepo->update($id, $data);

        $this->auditRepo->log([
            'user_id'     => Auth::id(),
            'action'      => 'update_user',
            'entity_type' => 'user',
            'entity_id'   => $id,
            'old_values'  => ['username' => $existing['username'], 'role' => $existing['role']],
            'new_values'  => ['username' => $data['username'], 'role' => $data['role']],
        ]);

        return $result;
    }

    /**
     * Delete a user (admin action).
     */
    public function deleteUser(int $id): bool
    {
        // Prevent self-deletion
        if ($id === Auth::id()) {
            throw new \InvalidArgumentException('No puedes eliminar tu propia cuenta.');
        }

        $existing = $this->userRepo->findById($id);
        if (!$existing) {
            throw new \InvalidArgumentException('Usuario no encontrado.');
        }

        $result = $this->userRepo->delete($id);

        $this->auditRepo->log([
            'user_id'     => Auth::id(),
            'action'      => 'delete_user',
            'entity_type' => 'user',
            'entity_id'   => $id,
            'old_values'  => ['username' => $existing['username']],
        ]);

        return $result;
    }

    // ─── Validation ─────────────────────────────────────

    private function validate(array $data, ?int $excludeId = null): void
    {
        if (empty($data['username'])) {
            throw new \InvalidArgumentException('El nombre de usuario es obligatorio.');
        }
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Se requiere un correo electrónico válido.');
        }
        if (empty($data['full_name'])) {
            throw new \InvalidArgumentException('El nombre completo es obligatorio.');
        }
        if (empty($data['role']) || !in_array($data['role'], self::VALID_ROLES, true)) {
            throw new \InvalidArgumentException('Rol no válido.');
        }
        // Password required only on create (excludeId = null)
        if ($excludeId === null && empty($data['password'])) {
            throw new \InvalidArgumentException('La contraseña es obligatoria.');
        }
    }
}
