<?php

declare(strict_types=1);

namespace App\Cms\Application;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class PublicContentResolver
{
    public function __construct(private readonly PublicSlugRegistry $slugs, #[AutowireIterator('shopro.public_content_handler')] private readonly iterable $handlers) {}
    public function resolve(string $slug, Request $request, string $locale = ''): ?Response
    {
        $owner=$this->slugs->owner($slug,$locale);if($owner===null)return null;
        foreach($this->handlers as $handler)if($handler->supports($owner['type']))return $handler->render($owner['type'],$owner['id'],$request,$locale);
        return null;
    }
}
