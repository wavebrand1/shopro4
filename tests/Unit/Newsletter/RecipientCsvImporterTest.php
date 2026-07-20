<?php

declare(strict_types=1);

namespace App\Tests\Unit\Newsletter;

use App\Newsletter\Application\RecipientCsvImporter;
use PHPUnit\Framework\TestCase;

final class RecipientCsvImporterTest extends TestCase
{
    public function testImportsUniqueValidAddressesFromSemicolonCsv(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'shopro-csv-');
        self::assertIsString($path);
        file_put_contents($path, "name;email;note\nJan;JAN@example.com;A\nAnna;anna@example.com;B\nDuplikat;jan@example.com;C\nBłąd;not-an-email;D\n");
        try {
            self::assertSame(['jan@example.com', 'anna@example.com'], (new RecipientCsvImporter())->import($path));
        } finally {
            unlink($path);
        }
    }

    public function testImportsAddressesFromTabSeparatedFileWithBom(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'shopro-csv-');
        self::assertIsString($path);
        file_put_contents($path, "\xEF\xBB\xBFemail\tname\nfirst@example.com\tOne\nsecond@example.com\tTwo\n");
        try {
            self::assertSame(['first@example.com', 'second@example.com'], (new RecipientCsvImporter())->import($path));
        } finally {
            unlink($path);
        }
    }
}
