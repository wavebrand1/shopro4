<?php

declare(strict_types=1);

namespace App\Cms\Application\PageBuilder;

final readonly class PageBuilderComponentDefinition
{
    public function __construct(
        public string $type,
        public ?string $moduleCode,
        public string $label,
        public string $help,
        public string $icon,
        public bool $preset = false,
        public bool $library = true,
    ) {
        if (!preg_match('/^[a-z][a-z0-9_]{1,63}$/', $type)) throw new \InvalidArgumentException('Invalid Page Builder component type: '.$type);
        if ($moduleCode !== null && !preg_match('/^[a-z][a-z0-9_-]{1,79}$/', $moduleCode)) throw new \InvalidArgumentException('Invalid Page Builder component module: '.$moduleCode);
        if ($label === '' || $help === '' || $icon === '') throw new \InvalidArgumentException('Page Builder component metadata cannot be empty: '.$type);
    }
}
