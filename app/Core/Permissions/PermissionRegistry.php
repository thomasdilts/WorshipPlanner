<?php

namespace App\Core\Permissions;

class PermissionRegistry
{
    /** @var array<string, list<string>> module_key => permission names */
    protected array $permissions = [];

    /**
     * @param  list<string>  $permissions
     */
    public function register(string $moduleKey, array $permissions): void
    {
        $existing = $this->permissions[$moduleKey] ?? [];
        $this->permissions[$moduleKey] = array_values(array_unique([
            ...$existing,
            ...$permissions,
        ]));
    }

    /**
     * @return array<string, list<string>>
     */
    public function allByModule(): array
    {
        return $this->permissions;
    }

    /**
     * @return list<string>
     */
    public function all(): array
    {
        return array_values(array_unique(array_merge(...array_values($this->permissions)) ?: []));
    }

    /**
     * @return list<string>
     */
    public function forModule(string $moduleKey): array
    {
        return $this->permissions[$moduleKey] ?? [];
    }
}
