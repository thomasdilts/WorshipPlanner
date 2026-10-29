<?php

namespace App\Core\Modules;

final class ModuleDefinition
{
    /**
     * @param  list<string>  $requires
     * @param  array<string, string>  $settings  setting key => type hint (bool, string, int)
     */
    public function __construct(
        public readonly string $key,
        public readonly string $name,
        public readonly string $description = '',
        public readonly array $requires = [],
        public readonly array $settings = [],
        public readonly bool $required = false,
    ) {}
}
