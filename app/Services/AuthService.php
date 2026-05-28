<?php

namespace App\Services;

use App\Core\Auth;
use App\Repositories\UserRepository;
use App\Repositories\AuditRepository;

/**
 * AuthService — Login / logout business logic.
 */
class AuthService
{
    private UserRepository  $userRepo;
    private AuditRepository $auditRepo;

    public function __construct()
    {
        $this->userRepo  = new UserRepository();
        $this->auditRepo = new AuditRepository();
    }

    /**
     * Attempt to authenticate a user.
     *
     * @return array|null  User row on success, null on failure.
     */
    public function attempt(string $identifier, string $password): ?array
    {
        $normalizedIdentifier = trim($identifier);
        $user = $this->userRepo->findByLoginIdentifier($normalizedIdentifier);

        if (!$user) {
            error_log('[auth] login failed: user not found for identifier=' . $normalizedIdentifier);
            return null;
        }

        if (empty($user['is_active'])) {
            error_log('[auth] login failed: inactive user id=' . (string) ($user['id'] ?? 'unknown'));
            return null;
        }

        $passwordHash = (string) ($user['password_hash'] ?? '');
        $isValidPassword = password_verify($password, $passwordHash);

        // Compatibility path for legacy plain-text rows: migrate to bcrypt after first valid login.
        if (!$isValidPassword && $passwordHash !== '' && hash_equals($passwordHash, $password)) {
            $isValidPassword = true;
            $this->userRepo->updatePasswordHash((int) $user['id'], password_hash($password, PASSWORD_DEFAULT));
        }

        if (!$isValidPassword) {
            error_log('[auth] login failed: invalid password for user id=' . (string) ($user['id'] ?? 'unknown'));
            return null;
        }

        // Store in session
        Auth::login($user);

        // Update last_login timestamp
        $this->userRepo->updateLastLogin((int) $user['id']);

        // Audit
        $this->auditRepo->log([
            'user_id'     => $user['id'],
            'action'      => 'login',
            'entity_type' => 'user',
            'entity_key'  => (string) $user['id'],
        ]);

        return $user;
    }

    /** Log out current user. */
    public function logout(): void
    {
        $userId = Auth::id();
        if ($userId) {
            $this->auditRepo->log([
                'user_id'     => $userId,
                'action'      => 'logout',
                'entity_type' => 'user',
                'entity_key'  => (string) $userId,
            ]);
        }
        Auth::logout();
    }
}
