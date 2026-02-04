<?php

$userRole = $user['role'] ?? 'user';

function isRole(string $role): bool {
    global $userRole;
    return $userRole === $role;
}

function isUser(): bool { return isRole('user'); }
function isDriver(): bool { return isRole('driver'); }
function isAdmin(): bool { return isRole('admin'); }

function requireRole(string ...$roles): void {
    global $userRole;
    if (!in_array($userRole, $roles, true)) {
        http_response_code(403);
        exit('Access denied');
    }
}
