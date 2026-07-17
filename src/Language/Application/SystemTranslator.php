<?php

declare(strict_types=1);

namespace App\Language\Application;

use App\Language\Domain\Entity\Language;
use App\Language\Domain\Entity\Translation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class SystemTranslator
{
    /** @var array<string, string> */
    private array $cache = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function translate(string $key): string
    {
        $language = $this->currentLanguage() ?? $this->defaultLanguage();
        $code = $language?->getCode() ?? 'pl';
        $cacheKey = $code.':'.$key;
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        try {
            $translation = $language ? $this->entityManager->getRepository(Translation::class)->findOneBy(['language' => $language, 'key' => $key]) : null;
            if ($translation && '' !== trim($translation->getValue())) {
                return $this->cache[$cacheKey] = $translation->getValue();
            }
        } catch (\Throwable) {
        }

        $phrase = SystemTranslationCatalog::phrases()[$key] ?? null;

        return $this->cache[$cacheKey] = $phrase[$code] ?? $phrase['pl'] ?? $key;
    }

    public function locale(): string
    {
        return $this->requestStack->getCurrentRequest()?->getLocale() ?: 'pl';
    }

    private function currentLanguage(): ?Language
    {
        return $this->requestStack->getCurrentRequest()?->attributes->get('_shopro_language');
    }

    private function defaultLanguage(): ?Language
    {
        try {
            return $this->entityManager->getRepository(Language::class)->findOneBy(['defaultLanguage' => true]);
        } catch (\Throwable) {
            return null;
        }
    }
}
