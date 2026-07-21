<?php

declare(strict_types=1);

namespace App\Cms\Application;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

final class PageBuilderSanitizer
{
    public function __construct(
        #[Autowire(service: 'html_sanitizer.sanitizer.app.page_content')]
        private readonly HtmlSanitizerInterface $htmlSanitizer,
    ) {}

    public function sanitize(string $json): string
    {
        try {
            $data = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return '[]';
        }

        if (!is_array($data)) return '[]';

        $this->sanitizeNode($data);

        return json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function sanitizeNode(array &$node): void
    {
        if (($node['type'] ?? null) === 'rich_text' && isset($node['data']['content'])) {
            $node['data']['content'] = $this->htmlSanitizer->sanitize((string) $node['data']['content']);
        }

        foreach ($node as &$value) {
            if (is_array($value)) $this->sanitizeNode($value);
        }
        unset($value);
    }
}
