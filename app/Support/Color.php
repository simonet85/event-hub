<?php

namespace App\Support;

/**
 * Calculs de contraste (WCAG) pour les gabarits qui n'ont pas accès aux variables CSS du site : e-mails et PDF.
 * Même logique que resources/js/lib/contrast.ts.
 */
class Color
{
    public const ON_ACCENT_LIGHT = '#fffaf2';

    public const ON_ACCENT_DARK = '#1d1711';

    public const INK = '#2a1f17';

    public static function isHex(?string $value): bool
    {
        return is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1;
    }

    public static function luminance(string $hex): float
    {
        $n = hexdec(ltrim($hex, '#'));
        $channel = static fn (int $v): float => ($v /= 255) <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;

        return 0.2126 * $channel(($n >> 16) & 255) + 0.7152 * $channel(($n >> 8) & 255) + 0.0722 * $channel($n & 255);
    }

    public static function contrast(string $a, string $b): float
    {
        $la = self::luminance($a);
        $lb = self::luminance($b);

        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }

    /** Couleur de texte à poser sur un fond d'accent : celle des deux qui contraste le mieux. */
    public static function onAccent(string $accent): string
    {
        return self::contrast($accent, self::ON_ACCENT_LIGHT) >= self::contrast($accent, self::ON_ACCENT_DARK)
            ? self::ON_ACCENT_LIGHT
            : self::ON_ACCENT_DARK;
    }

    /** Couleur de texte d'accent lisible sur le fond (4,5:1) ; à défaut, l'encre du Design System. */
    public static function readableAccent(string $accent, string $surface): string
    {
        return self::contrast($accent, $surface) >= 4.5 ? $accent : self::INK;
    }
}
