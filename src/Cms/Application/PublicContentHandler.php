<?php

declare(strict_types=1);

namespace App\Cms\Application;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AutoconfigureTag('shopro.public_content_handler')]
interface PublicContentHandler
{
    public function supports(string $ownerType): bool;
    public function render(string $ownerType, int $ownerId, Request $request, string $locale = ''): ?Response;
}
