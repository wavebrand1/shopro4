<?php

declare(strict_types=1);

namespace App\Cms\Application;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('shopro.sitemap_provider')]
interface SitemapProvider
{
    /** @return list<array{location:string,modified:\DateTimeInterface,alternates:list<array{language:string,location:string}>}> */
    public function entries(): array;
}
