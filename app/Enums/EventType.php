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

    /**
     * Préréglage du thème (valeurs claires du Design System « Papier & lumière »). Les valeurs choisies
     * par l'hôte, stockées dans `theme_config`, l'emportent toujours sur ce préréglage.
     *
     * @return array{primary_color: string, secondary_color: string, font_family: string}
     */
    public function defaultTheme(): array
    {
        return match ($this) {
            self::Wedding, self::Other => ['primary_color' => '#4e6b48', 'secondary_color' => '#fbf6ee', 'font_family' => 'serif'],
            self::Baptism => ['primary_color' => '#42698f', 'secondary_color' => '#fbf6ee', 'font_family' => 'serif'],
            self::Birthday => ['primary_color' => '#a0432a', 'secondary_color' => '#fbf6ee', 'font_family' => 'serif'],
            self::Party => ['primary_color' => '#7a3d6f', 'secondary_color' => '#fbf6ee', 'font_family' => 'serif'],
        };
    }

    /**
     * Ancien préréglage « Éditorial chic » (plusieurs couleurs échouaient au contraste AA).
     * Sert uniquement à migrer les événements qui n'ont jamais personnalisé leur thème.
     *
     * @return array{primary_color: string, secondary_color: string, font_family: string}
     */
    public function legacyTheme(): array
    {
        return match ($this) {
            self::Wedding => ['primary_color' => '#b08968', 'secondary_color' => '#fdf6ee', 'font_family' => 'script'],
            self::Baptism => ['primary_color' => '#6b9ac4', 'secondary_color' => '#f2f7fb', 'font_family' => 'serif'],
            self::Birthday => ['primary_color' => '#e76f51', 'secondary_color' => '#fff4ef', 'font_family' => 'body'],
            self::Party => ['primary_color' => '#7b2cbf', 'secondary_color' => '#f6eefc', 'font_family' => 'body'],
            self::Other => ['primary_color' => '#4b5563', 'secondary_color' => '#f3f4f6', 'font_family' => 'serif'],
        };
    }

    /** Accent du mode sombre (jetons `--accent-*` du thème sombre), utilisé par le thème Fête. */
    public function darkAccent(): string
    {
        return match ($this) {
            self::Wedding, self::Other => '#a4c19c',
            self::Baptism => '#9bbfe2',
            self::Birthday => '#e59a80',
            self::Party => '#d3a3c8',
        };
    }

    /** Classe posée sur la racine de la page : elle fixe `--accent` (voir resources/css/app.css). */
    public function cssClass(): string
    {
        return match ($this) {
            self::Wedding, self::Other => 'ev-mariage',
            self::Baptism => 'ev-bapteme',
            self::Birthday => 'ev-anniversaire',
            self::Party => 'ev-fete',
        };
    }

    /** Le thème Fête s'affiche en mode sombre (data-theme="dark"), sauf fond personnalisé par l'hôte. */
    public function prefersDarkTheme(): bool
    {
        return $this === self::Party;
    }
}
