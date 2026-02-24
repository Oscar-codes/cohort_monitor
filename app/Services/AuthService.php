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
    public function attempt(string $username, string $password): ?array
    {
        $user = $this->userRepo->findByUsername($username);

        if (!$user) {
            return null;
        }

        if (empty($user['is_active'])) {
            return null;
        }

        if (!password_verify($password, $user['password_hash'])) {
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
            'entity_id'   => $user['id'],
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
                'entity_id'   => $userId,
            ]);
        }
        Auth::logout();
    }
}
