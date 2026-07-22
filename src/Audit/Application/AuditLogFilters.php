<?php

declare(strict_types=1);

namespace App\Audit\Application;

final readonly class AuditLogFilters
{
    public const TYPES = ['system', 'admin', 'user', 'site_user'];

    private function __construct(
        public ?string $from,
        public ?string $to,
        public ?string $type,
        public ?bool $important,
        public ?string $query,
    ) {}

    /** @param array<string, mixed> $input */
    public static function fromArray(array $input): self
    {
        $from = self::date($input['from'] ?? null);
        $to = self::date($input['to'] ?? null);
        if ($from !== null && $to !== null && $from > $to) [$from, $to] = [$to, $from];

        $type = is_string($input['type'] ?? null) && in_array($input['type'], self::TYPES, true) ? $input['type'] : null;
        $important = match ($input['important'] ?? null) {
            '1', 1, true => true,
            '0', 0, false => false,
            default => null,
        };
        $query = is_string($input['q'] ?? null) ? trim($input['q']) : '';

        return new self($from, $to, $type, $important, $query !== '' ? mb_substr($query, 0, 100) : null);
    }

    /** @return array{from:string,to:string,type:string,important:string,q:string} */
    public function toQuery(): array
    {
        return [
            'from' => $this->from ?? '',
            'to' => $this->to ?? '',
            'type' => $this->type ?? '',
            'important' => $this->important === null ? '' : ($this->important ? '1' : '0'),
            'q' => $this->query ?? '',
        ];
    }

    private static function date(mixed $value): ?string
    {
        if (!is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value) !== 1) return null;
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value ? $value : null;
    }
}
