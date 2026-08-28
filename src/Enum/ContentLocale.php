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

    public function displayName(): string
    {
        return match ($this) {
            self::EN => 'English',
            self::UK => 'Ukrainian',
            self::DE => 'German',
        };
    }

    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_map(static fn (self $c) => $c->value, self::cases());
    }

    public static function default(): string
    {
        return self::UK->value;
    }
}
