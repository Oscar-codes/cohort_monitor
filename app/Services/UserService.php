<?php

namespace App\Services;

use App\Repositories\UserRepository;
use App\Repositories\AuditRepository;
use App\Core\Auth;

/**
 * UserService — Business logic for user management (admin & self-service).
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

    // ─── Admin actions ──────────────────────────────────

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
     * Delete a user (admin action). Prevents self-deletion and last-admin deletion.
     */
    public function deleteUser(int $id): bool
    {
        if ($id === Auth::id()) {
            throw new \InvalidArgumentException('No puedes eliminar tu propia cuenta.');
        }

        $existing = $this->userRepo->findById($id);
        if (!$existing) {
            throw new \InvalidArgumentException('Usuario no encontrado.');
        }

        // Prevent deleting the last active admin
        if ($existing['role'] === 'admin' && $existing['is_active']) {
            $admins = array_filter($this->userRepo->findAll(), fn($u) => $u['role'] === 'admin' && $u['is_active']);
            if (count($admins) <= 1) {
                throw new \InvalidArgumentException('No se puede eliminar el último administrador activo.');
            }
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

    /**
     * Toggle user active/inactive status (admin action).
     */
    public function toggleStatus(int $id): bool
    {
        if ($id === Auth::id()) {
            throw new \InvalidArgumentException('No puedes desactivar tu propia cuenta.');
        }

        $user = $this->userRepo->findById($id);
        if (!$user) {
            throw new \InvalidArgumentException('Usuario no encontrado.');
        }

        $newStatus = $user['is_active'] ? 0 : 1;

        // Prevent deactivating the last active admin
        if ($user['role'] === 'admin' && $user['is_active']) {
            $admins = array_filter($this->userRepo->findAll(), fn($u) => $u['role'] === 'admin' && $u['is_active']);
            if (count($admins) <= 1) {
                throw new \InvalidArgumentException('No se puede desactivar el último administrador activo.');
            }
        }

        $result = $this->userRepo->update($id, ['is_active' => $newStatus]);

        $this->auditRepo->log([
            'user_id'     => Auth::id(),
            'action'      => $newStatus ? 'activate_user' : 'deactivate_user',
            'entity_type' => 'user',
            'entity_id'   => $id,
            'old_values'  => ['is_active' => $user['is_active']],
            'new_values'  => ['is_active' => $newStatus],
        ]);

        return (bool) $newStatus;
    }

    /**
     * Reset a user's password to a random string (admin action).
     * Returns the generated plain-text password.
     */
    public function resetPassword(int $id): string
    {
        if ($id === Auth::id()) {
            throw new \InvalidArgumentException('Usa "Cambiar contraseña" en Mi Cuenta para tu propia clave.');
        }

        $user = $this->userRepo->findById($id);
        if (!$user) {
            throw new \InvalidArgumentException('Usuario no encontrado.');
        }

        $newPassword = $this->generateRandomPassword();
        $this->userRepo->update($id, [
            'password_hash' => password_hash($newPassword, PASSWORD_BCRYPT),
        ]);

        $this->auditRepo->log([
            'user_id'     => Auth::id(),
            'action'      => 'reset_password',
            'entity_type' => 'user',
            'entity_id'   => $id,
            'new_values'  => ['username' => $user['username']],
        ]);

        return $newPassword;
    }

    // ─── Self-service actions ───────────────────────────

    /**
     * Update the current user's own profile (name + email).
     */
    public function updateProfile(int $id, array $data): bool
    {
        $existing = $this->userRepo->findById($id);
        if (!$existing) {
            throw new \InvalidArgumentException('Usuario no encontrado.');
        }

        if (empty($data['full_name'])) {
            throw new \InvalidArgumentException('El nombre completo es obligatorio.');
        }
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Se requiere un correo electrónico válido.');
        }

        // Check email uniqueness
        $byEmail = $this->userRepo->findByEmail($data['email']);
        if ($byEmail && (int) $byEmail['id'] !== $id) {
            throw new \InvalidArgumentException('El correo electrónico ya está en uso por otro usuario.');
        }

        $result = $this->userRepo->update($id, [
            'full_name' => $data['full_name'],
            'email'     => $data['email'],
        ]);

        $this->auditRepo->log([
            'user_id'     => $id,
            'action'      => 'update_profile',
            'entity_type' => 'user',
            'entity_id'   => $id,
            'old_values'  => ['full_name' => $existing['full_name'], 'email' => $existing['email']],
            'new_values'  => ['full_name' => $data['full_name'], 'email' => $data['email']],
        ]);

        // Refresh session data
        $_SESSION['user']['full_name'] = $data['full_name'];
        $_SESSION['user']['email']     = $data['email'];

        return $result;
    }

    /**
     * Change the current user's password (self-service).
     */
    public function changePassword(int $id, string $currentPassword, string $newPassword): bool
    {
        $user = $this->userRepo->findById($id);
        if (!$user) {
            throw new \InvalidArgumentException('Usuario no encontrado.');
        }

        if (!password_verify($currentPassword, $user['password_hash'])) {
            throw new \InvalidArgumentException('La contraseña actual es incorrecta.');
        }

        if (strlen($newPassword) < 8) {
            throw new \InvalidArgumentException('La nueva contraseña debe tener al menos 8 caracteres.');
        }

        $result = $this->userRepo->update($id, [
            'password_hash' => password_hash($newPassword, PASSWORD_BCRYPT),
        ]);

        $this->auditRepo->log([
            'user_id'     => $id,
            'action'      => 'change_password',
            'entity_type' => 'user',
            'entity_id'   => $id,
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

    /**
     * Generate a random password (12 chars, mixed).
     */
    private function generateRandomPassword(int $length = 12): string
    {
        $chars = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#$';
        $password = '';
        $max = strlen($chars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, $max)];
        }
        return $password;
    }
}
