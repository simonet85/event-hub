<?php

namespace App\Enums;

enum EventType: string
{
    case Wedding = 'wedding';
    case Baptism = 'baptism';
    case Birthday = 'birthday';
    case Party = 'party';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Wedding => 'Mariage',
            self::Baptism => 'Baptême',
            self::Birthday => 'Anniversaire',
            self::Party => 'Fête',
            self::Other => 'Autre',
        };
    }

    /** @return array{primary_color: string, secondary_color: string, font_family: string} */
    public function defaultTheme(): array
    {
        return match ($this) {
            self::Wedding => ['primary_color' => '#b08968', 'secondary_color' => '#fdf6ee', 'font_family' => 'script'],
            self::Baptism => ['primary_color' => '#6b9ac4', 'secondary_color' => '#f2f7fb', 'font_family' => 'serif'],
            self::Birthday => ['primary_color' => '#e76f51', 'secondary_color' => '#fff4ef', 'font_family' => 'body'],
            self::Party => ['primary_color' => '#7b2cbf', 'secondary_color' => '#f6eefc', 'font_family' => 'body'],
            self::Other => ['primary_color' => '#4b5563', 'secondary_color' => '#f3f4f6', 'font_family' => 'serif'],
        };
    }
}
