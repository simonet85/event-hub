<?php

namespace App\Support;

use App\Models\Event;

/**
 * Palette et polices des e-mails d'un événement : couleurs du thème de l'hôte, contrastes vérifiés (WCAG).
 * Les clients de messagerie ignorent les variables CSS : les gabarits reçoivent des valeurs en clair.
 */
final readonly class MailTheme
{
    public function __construct(
        public string $paper,
        public string $accent,
        public string $accentText,
        public string $onAccent,
        public string $ink,
        public string $muted,
        public string $line,
        public string $panel,
        public string $serif,
        public string $sans,
    ) {}

    public static function for(Event $event): self
    {
        $theme = $event->resolvedTheme();
        $paper = Color::isHex($theme['secondary_color']) ? $theme['secondary_color'] : '#fbf6ee';
        $accent = Color::isHex($theme['primary_color']) ? $theme['primary_color'] : '#4e6b48';

        return new self(
            paper: $paper,
            accent: $accent,
            accentText: Color::readableAccent($accent, '#fffdf9'),
            onAccent: Color::onAccent($accent),
            ink: Color::INK,
            muted: '#66554a',
            line: '#e4d6bf',
            panel: '#faf5ea',
            serif: "'Cormorant Garamond', Georgia, 'Times New Roman', serif",
            sans: "'DM Sans', Arial, Helvetica, sans-serif",
        );
    }
}
