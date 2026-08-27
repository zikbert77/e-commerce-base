<?php

namespace App\Enum;

enum ContentLocale: string
{
    case EN = 'en';
    case UK = 'uk';
    case DE = 'de';

    public function label(): string
    {
        return match ($this) {
            self::EN => 'English (en)',
            self::UK => 'Ukrainian (uk)',
            self::DE => 'German (de)',
        };
    }

    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_map(static fn (self $c) => $c->value, self::cases());
    }
}
