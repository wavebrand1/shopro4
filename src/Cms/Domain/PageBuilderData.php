<?php

declare(strict_types=1);

namespace App\Cms\Domain;

use App\Media\Domain\MediaPath;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class PageBuilderData
{
    private const LINK_KEYS = ['url', 'primaryUrl', 'secondaryUrl'];

    public static function validate(mixed $value, ExecutionContextInterface $context): void
    {
        if ($value === null || $value === '') return;

        try {
            $data = json_decode((string) $value, true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $context->buildViolation('validation.page_builder.invalid_json')->addViolation();

            return;
        }

        if (!is_array($data)) {
            $context->buildViolation('validation.page_builder.invalid_json')->addViolation();

            return;
        }

        if (self::containsUnsafeLink($data)) {
            $context->buildViolation('validation.page_builder.link_invalid')->addViolation();
        }

        if (self::containsUnsafeImage($data)) {
            $context->buildViolation('validation.page_builder.image_invalid')->addViolation();
        }
    }

    private static function containsUnsafeLink(array $node): bool
    {
        foreach ($node as $key => $value) {
            if (in_array($key, self::LINK_KEYS, true) && is_string($value) && $value !== '' && !MenuLink::isSafe($value)) {
                return true;
            }

            if (is_array($value) && self::containsUnsafeLink($value)) return true;
        }

        return false;
    }

    private static function containsUnsafeImage(array $node): bool
    {
        if (($node['type'] ?? null) === 'image') {
            $src = $node['data']['src'] ?? '';
            if ($src !== '' && (!is_string($src) || !MediaPath::isSafePublicUploadUrl($src))) return true;
        }

        foreach ($node as $value) {
            if (is_array($value) && self::containsUnsafeImage($value)) return true;
        }

        return false;
    }
}
