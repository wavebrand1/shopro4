<?php

declare(strict_types=1);

namespace App\Tests\Unit\Cms;

use App\Cms\Domain\PageBuilderData;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

final class PageBuilderDataTest extends TestCase
{
    #[DataProvider('validData')]
    public function testAcceptsValidBuilderData(string $json): void
    {
        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects(self::never())->method('buildViolation');

        PageBuilderData::validate($json, $context);
    }

    public static function validData(): iterable
    {
        yield 'empty project' => ['[]'];
        yield 'internal and nested links' => ['[{"data":{"primaryUrl":"/kontakt","items":[{"url":"#start"},{"url":"https://example.com"}]}}]'];
        yield 'local image source' => ['[{"type":"image","data":{"src":"/uploads/gallery/photo.webp"}}]'];
    }

    #[DataProvider('invalidData')]
    public function testRejectsInvalidBuilderData(string $json, string $message): void
    {
        $violation = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violation->expects(self::once())->method('addViolation');
        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects(self::once())->method('buildViolation')->with($message)->willReturn($violation);

        PageBuilderData::validate($json, $context);
    }

    public static function invalidData(): iterable
    {
        yield 'malformed JSON' => ['{', 'validation.page_builder.invalid_json'];
        yield 'scalar JSON' => ['1', 'validation.page_builder.invalid_json'];
        yield 'protocol relative URL' => ['[{"data":{"primaryUrl":"//evil.example"}}]', 'validation.page_builder.link_invalid'];
        yield 'nested executable URL' => ['[{"data":{"items":[{"url":"javascript:alert(1)"}]}}]', 'validation.page_builder.link_invalid'];
        yield 'external image URL' => ['[{"type":"image","data":{"src":"https://evil.example/uploads/photo.webp"}}]', 'validation.page_builder.image_invalid'];
        yield 'traversing image URL' => ['[{"type":"image","data":{"src":"/uploads/%2e%2e/.env"}}]', 'validation.page_builder.image_invalid'];
    }
}
