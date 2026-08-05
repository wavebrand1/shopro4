<?php

declare(strict_types=1);

namespace App\Cms\Application;

use App\Language\Domain\Entity\Language;
use App\Module\Application\ModuleAvailability;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class MenuContentRegistry
{
    /** @var array<string, MenuContentProvider> */
    private array $providers = [];

    public function __construct(
        #[AutowireIterator('shopro.menu_content_provider')] iterable $providers,
        private readonly ModuleAvailability $modules,
    ) {
        foreach ($providers as $provider) {
            $this->providers[$provider->key()] = $provider;
        }
    }

    /** @return array<string, array<string, string>> */
    public function formChoices(): array
    {
        $groups = [];
        foreach ($this->providers as $provider) {
            if (!$this->modules->isEnabled($provider->moduleCode())) continue;
            foreach ($provider->choices() as $label => $id) {
                $groups[$provider->label()][$label] = $provider->key().':'.$id;
            }
        }
        return $groups;
    }

    public function resolve(?string $reference, ?Language $language): ?string
    {
        if (!is_string($reference) || !preg_match('/^([a-z0-9_.-]+):(\d+)$/', $reference, $match)) return null;
        $provider = $this->providers[$match[1]] ?? null;
        if (!$provider || !$this->modules->isEnabled($provider->moduleCode())) return null;
        return $provider->url((int) $match[2], $language);
    }
}
