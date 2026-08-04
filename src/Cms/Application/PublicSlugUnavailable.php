<?php

declare(strict_types=1);

namespace App\Cms\Application;

final class PublicSlugUnavailable extends \RuntimeException
{
    public function __construct(public readonly string $slug, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf('Public slug "%s" is already reserved.', $slug), 0, $previous);
    }
}
