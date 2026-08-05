<?php

declare(strict_types=1);

namespace App\Cms\Application;

use App\Language\Domain\Entity\Language;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('shopro.menu_content_provider')]
interface MenuContentProvider
{
    public function key(): string;
    public function label(): string;
    public function moduleCode(): string;

    /** @return array<string, int> Content IDs indexed by user-facing labels. */
    public function choices(): array;

    public function url(int $id, ?Language $language): ?string;
}
