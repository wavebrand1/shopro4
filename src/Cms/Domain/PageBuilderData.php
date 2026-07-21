<?php

declare(strict_types=1);

namespace App\Cms\Domain;

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
}
