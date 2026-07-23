<?php

declare(strict_types=1);

namespace App\Tests\Unit\Newsletter;

use App\Newsletter\Application\SafeDeliveryError;
use PHPUnit\Framework\TestCase;

final class SafeDeliveryErrorTest extends TestCase
{
    public function testItRedactsCredentialsAndSecrets(): void
    {
        $safe = (new SafeDeliveryError())->sanitize(
            'Connection smtp://mailer:super-secret@mail.example.test failed; token=abc123 password: hidden',
        );

        self::assertSame(
            'Connection smtp://[redacted]@mail.example.test failed; token=[redacted] password: [redacted]',
            $safe,
        );
        self::assertStringNotContainsString('super-secret', $safe);
        self::assertStringNotContainsString('abc123', $safe);
        self::assertStringNotContainsString('hidden', $safe);
    }

    public function testItNormalizesControlCharactersAndLimitsLength(): void
    {
        $safe = (new SafeDeliveryError())->sanitize("\0Failure\n".str_repeat('x', 600));

        self::assertStringStartsWith('Failure ', $safe);
        self::assertSame(500, mb_strlen($safe));
    }

    public function testItProvidesFallbackForEmptyMessage(): void
    {
        self::assertSame('Unknown delivery error.', (new SafeDeliveryError())->sanitize(" \n "));
    }
}
