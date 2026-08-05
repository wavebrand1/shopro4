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
        public ?string $template = null,
        public ?string $editorJavascript = null,
        public ?string $stylesheet = null,
        /** @var list<string> fields containing trusted HTML edited through the rich-text editor */
        public array $htmlFields = [],
    ) {
        if (!preg_match('/^[a-z][a-z0-9_]{1,63}$/', $type)) throw new \InvalidArgumentException('Invalid Page Builder component type: '.$type);
        if ($moduleCode !== null && !preg_match('/^[a-z][a-z0-9_-]{1,79}$/', $moduleCode)) throw new \InvalidArgumentException('Invalid Page Builder component module: '.$moduleCode);
        if ($label === '' || $help === '' || $icon === '') throw new \InvalidArgumentException('Page Builder component metadata cannot be empty: '.$type);
        if ($template !== null && ($template === '' || str_contains($template, '..'))) throw new \InvalidArgumentException('Invalid Page Builder component Twig template: '.$type);
        if ($editorJavascript !== null && ($editorJavascript === '' || !str_starts_with($editorJavascript, '/'))) throw new \InvalidArgumentException('Invalid Page Builder editor script: '.$type);
        if ($stylesheet !== null && ($stylesheet === '' || !str_starts_with($stylesheet, '/'))) throw new \InvalidArgumentException('Invalid Page Builder stylesheet: '.$type);
        foreach ($htmlFields as $field) if (!preg_match('/^[a-z][a-zA-Z0-9_]{0,63}$/', $field)) throw new \InvalidArgumentException('Invalid Page Builder HTML field: '.$field);
    }
}
