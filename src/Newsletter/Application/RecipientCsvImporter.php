<?php

declare(strict_types=1);

namespace App\Newsletter\Application;

final class RecipientCsvImporter
{
    private const MAX_RECIPIENTS = 10_000;

    /** @return list<string> */
    public function import(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) throw new \RuntimeException('Nie można odczytać pliku CSV.');
        try {
            $firstLine = fgets($handle);
            if ($firstLine === false) return [];
            $delimiter = $this->delimiter($firstLine);
            rewind($handle);
            $emails = [];
            while (($row = fgetcsv($handle, 0, $delimiter, '"', '\\')) !== false) {
                foreach ($row as $cell) {
                    $value = mb_strtolower(trim((string) $cell, " \t\n\r\0\x0B\xEF\xBB\xBF"));
                    if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) continue;
                    $emails[$value] = true;
                    if (count($emails) > self::MAX_RECIPIENTS) throw new \LengthException('Plik zawiera więcej niż 10 000 unikalnych adresów.');
                }
            }
            return array_keys($emails);
        } finally {
            fclose($handle);
        }
    }

    private function delimiter(string $line): string
    {
        $counts = [];
        foreach ([',', ';', "\t"] as $delimiter) $counts[$delimiter] = substr_count($line, $delimiter);
        arsort($counts);
        $delimiter = (string) array_key_first($counts);
        return ($counts[$delimiter] ?? 0) > 0 ? $delimiter : ',';
    }
}
