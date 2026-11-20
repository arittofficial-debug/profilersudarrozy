<?php

namespace App\Dtos;

class UserGateProfile
{
    public function __construct(
        public readonly string $id,
        public readonly string $username,
        public readonly ?string $email,
        public readonly string $fullName,
        public readonly string $status,
        public readonly array $roles,
        public readonly bool $isSuperAdmin,
    ) {}

    public static function fromArray(array $data): self
    {
        $roles = self::normalizeRoles($data['roles'] ?? []);
        $superAdminRole = strtoupper((string) config('usergate.super_admin_role', 'SUPER_ADMIN'));

        $isSuperAdmin = filter_var($data['is_super_admin'] ?? false, FILTER_VALIDATE_BOOLEAN)
            || in_array($superAdminRole, $roles, true);

        return new self(
            id: (string) ($data['id'] ?? ''),
            username: (string) ($data['username'] ?? $data['login'] ?? ''),
            email: isset($data['email']) ? (string) $data['email'] : null,
            fullName: (string) ($data['full_name'] ?? $data['name'] ?? $data['username'] ?? ''),
            status: strtoupper((string) ($data['status'] ?? 'ACTIVE')),
            roles: $roles,
            isSuperAdmin: $isSuperAdmin,
        );
    }

    private static function normalizeRoles(mixed $roles): array
    {
        if (! is_array($roles)) {
            return [];
        }

        $result = [];

        foreach ($roles as $role) {
            if (is_array($role)) {
                $result[] = strtoupper((string) ($role['slug'] ?? $role['name'] ?? $role['role'] ?? ''));
            } else {
                $result[] = strtoupper((string) $role);
            }
        }

        return array_values(array_filter(array_unique($result)));
    }
}
