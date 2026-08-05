<?php

declare(strict_types=1);

namespace App\Media\Application;

final class FileUploadResult
{
    /** @param array<string, int> $rejections */
    public function __construct(
        public readonly int $uploaded,
        public readonly array $rejections,
        /** @var list<string> */
        public readonly array $urls = [],
    ) {}

    public function rejected(): int
    {
        return array_sum($this->rejections);
    }
}
