<?php

namespace App\Services;

use App\Models\Event;
use GdImage;
use Illuminate\Support\Facades\Storage;

/**
 * Image de partage (Open Graph, 1200 × 630) d'un événement, pour les aperçus WhatsApp et messageries.
 *
 * Elle ne contient que des informations de l'événement (type, titre, date, lieu) : jamais le nom d'un
 * invité ni aucune donnée personnelle. Les couleurs suivent le thème de l'événement (Design System
 * « Papier & lumière »). L'image est mise en cache sur disque et régénérée dès que l'événement change.
 */
class ShareImage
{
    public const WIDTH = 1200;

    public const HEIGHT = 630;

    /** À incrémenter quand la mise en page change : invalide toutes les images en cache. */
    private const LAYOUT_VERSION = 1;

    private const INK_LIGHT_THEME = '#2a1f17';

    private const INK_DARK_THEME = '#f4ece0';

    /**
     * GD avec FreeType et les polices sont-ils disponibles ? Sans eux, on n'annonce pas d'image de partage
     * (pas d'og:image, route en 404) plutôt que de répondre par une erreur 500.
     */
    public function available(): bool
    {
        return extension_loaded('gd') && function_exists('imagettftext') && is_file($this->font('cormorant-garamond-600'));
    }

    /** Chemin du PNG en cache, généré au besoin. */
    public function path(Event $event): string
    {
        $disk = Storage::disk('local');
        $name = "share-images/{$event->id}-{$this->fingerprint($event)}.png";

        if (! $disk->exists($name)) {
            foreach ($disk->files('share-images') as $old) {
                if (str_starts_with(basename($old), $event->id.'-')) {
                    $disk->delete($old);
                }
            }

            $disk->put($name, $this->render($event));
        }

        return $disk->path($name);
    }

    /** Empreinte des seules données qui changent le rendu. */
    public function fingerprint(Event $event): string
    {
        return substr(sha1(json_encode([
            self::LAYOUT_VERSION,
            $event->title,
            $event->type->value,
            $event->event_date->toIso8601String(),
            $event->location_name,
            $event->theme_config,
        ])), 0, 16);
    }

    public function render(Event $event): string
    {
        $palette = $this->palette($event);
        $image = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        imageantialias($image, true);

        $color = fn (string $hex) => $this->allocate($image, $hex);

        imagefilledrectangle($image, 0, 0, self::WIDTH, self::HEIGHT, $color($palette['surface']));
        // Cadre fin « papier » et bandeau d'accent en pied d'image.
        imagerectangle($image, 36, 36, self::WIDTH - 37, self::HEIGHT - 37, $color($palette['line']));
        imagefilledrectangle($image, 0, self::HEIGHT - 16, self::WIDTH, self::HEIGHT, $color($palette['accent']));

        $title = $this->fitTitle($event->title, 980);
        $date = $this->dateLine($event);
        $venue = trim((string) $event->location_name);

        $lineHeight = (int) round($title['size'] * 1.15);
        $blockHeight = 24 + 40 + count($title['lines']) * $lineHeight + 32 + 16 + 32 + 44 + ($venue !== '' ? 12 + 34 : 0);
        // Zone utile : entre le cadre (36 px) et le bandeau d'accent (16 px), centrée verticalement.
        $top = 36 + (int) round(((self::HEIGHT - 16 - 72) - $blockHeight) / 2);

        $this->spaced($image, $event->type->label(), $top, 24, $color($palette['muted']), $this->font('dm-sans-600'), 5);
        $top += 24 + 40;

        foreach ($title['lines'] as $i => $line) {
            $this->centered($image, $line, $top + $i * $lineHeight + (int) round($title['size'] * 0.9), $title['size'], $color($palette['ink']), $this->font('cormorant-garamond-600'));
        }
        $top += count($title['lines']) * $lineHeight + 32;

        $this->divider($image, $top, $color($palette['line']), $color($palette['gold']));
        $top += 16 + 32;

        $this->centered($image, $date, $top + 34, 36, $color($palette['ink']), $this->font('dm-sans-400'));
        if ($venue !== '') {
            $this->centered($image, $venue, $top + 44 + 12 + 26, 30, $color($palette['muted']), $this->font('dm-sans-400'));
        }

        ob_start();
        imagepng($image, null, 6);
        $png = (string) ob_get_clean();

        return $png;
    }

    /**
     * Couleurs du thème de l'événement : préréglage du type, mode sombre pour la Fête, et couleurs
     * choisies par l'hôte (qui l'emportent) avec une encre choisie pour rester lisible.
     *
     * @return array{surface: string, ink: string, muted: string, line: string, gold: string, accent: string}
     */
    public function palette(Event $event): array
    {
        $preset = $event->type->defaultTheme();
        $theme = array_merge($preset, array_filter((array) $event->theme_config));

        $customSurface = strtolower($theme['secondary_color']) !== strtolower($preset['secondary_color']);
        $customAccent = strtolower($theme['primary_color']) !== strtolower($preset['primary_color']);
        $dark = $event->type->prefersDarkTheme() && ! $customSurface;

        $surface = $dark ? '#1d1711' : $theme['secondary_color'];
        $ink = $dark ? self::INK_DARK_THEME : ($customSurface ? $this->readableInk($surface) : self::INK_LIGHT_THEME);

        return [
            'surface' => $surface,
            'ink' => $ink,
            'muted' => $dark ? '#c4b5a3' : $this->mix($ink, $surface, 0.28),
            'line' => $dark ? '#3f352a' : $this->mix($ink, $surface, 0.18),
            'gold' => $dark ? '#d9b36a' : '#7a5c1c',
            'accent' => $dark && ! $customAccent ? $event->type->darkAccent() : $theme['primary_color'],
        ];
    }

    /** Date en français : « Samedi 12 décembre 2026 · 16 h 00 ». */
    public function dateLine(Event $event): string
    {
        $date = $event->event_date->copy()->locale('fr');

        return mb_convert_case($date->translatedFormat('l'), MB_CASE_TITLE).' '.$date->translatedFormat('j F Y').' · '.$date->format('H \h i');
    }

    /**
     * Plus grande taille pour laquelle le titre tient : ≤ 92 px sur 1 ou 2 lignes, ≤ 72 px sur 3 lignes.
     *
     * @return array{size: int, lines: list<string>}
     */
    private function fitTitle(string $title, int $maxWidth): array
    {
        $font = $this->font('cormorant-garamond-600');
        $words = preg_split('/\s+/u', trim($title)) ?: [$title];

        // Une esperluette reste collée au mot qui suit : jamais « Clara & » en fin de ligne.
        $words = array_reduce($words, function (array $merged, string $word) {
            if ($merged !== [] && str_ends_with($merged[array_key_last($merged)], ' &') || ($merged !== [] && $merged[array_key_last($merged)] === '&')) {
                $merged[array_key_last($merged)] .= " {$word}";

                return $merged;
            }

            $merged[] = $word;

            return $merged;
        }, []);

        for ($size = 92; $size >= 44; $size -= 4) {
            $lines = [];
            $current = '';

            foreach ($words as $word) {
                $try = $current === '' ? $word : "{$current} {$word}";

                if ($current !== '' && $this->width($try, $size, $font) > $maxWidth) {
                    $lines[] = $current;
                    $current = $word;
                } else {
                    $current = $try;
                }
            }
            $lines[] = $current;

            $fits = collect($lines)->every(fn (string $line) => $this->width($line, $size, $font) <= $maxWidth);

            if (count($lines) <= 3 && $fits && (count($lines) < 3 || $size <= 72)) {
                return ['size' => $size, 'lines' => $lines];
            }
        }

        // Titre très long : on tronque la troisième ligne plutôt que de déborder.
        $lines = array_slice($lines, 0, 3);
        $lines[2] = rtrim(mb_substr($lines[2], 0, 28)).'…';

        return ['size' => 44, 'lines' => $lines];
    }

    private function width(string $text, int $size, string $font): int
    {
        $box = imagettfbbox($size, 0, $font, $text);

        return (int) abs($box[2] - $box[0]);
    }

    private function centered(GdImage $image, string $text, int $baseline, int $size, int $color, string $font): void
    {
        imagettftext($image, $size, 0, (int) round((self::WIDTH - $this->width($text, $size, $font)) / 2), $baseline, $color, $font, $text);
    }

    /** Texte en capitales espacées (GD ne gère pas l'espacement des lettres). */
    private function spaced(GdImage $image, string $text, int $y, int $size, int $color, string $font, int $spacing): void
    {
        $text = mb_strtoupper($text);
        $chars = mb_str_split($text);
        $widths = array_map(fn (string $c) => $this->width($c === ' ' ? 'i' : $c, $size, $font) + ($c === ' ' ? $size / 2 : 0), $chars);
        $total = array_sum($widths) + $spacing * (count($chars) - 1);
        $x = (self::WIDTH - $total) / 2;

        foreach ($chars as $i => $char) {
            imagettftext($image, $size, 0, (int) round($x), $y + $size, $color, $font, $char);
            $x += $widths[$i] + $spacing;
        }
    }

    /** Filets de part et d'autre d'un petit losange doré. */
    private function divider(GdImage $image, int $y, int $line, int $gold): void
    {
        $cx = self::WIDTH / 2;
        $cy = $y + 8;

        imageline($image, (int) $cx - 150, $cy, (int) $cx - 20, $cy, $line);
        imageline($image, (int) $cx + 20, $cy, (int) $cx + 150, $cy, $line);
        imagefilledpolygon($image, [(int) $cx, $cy - 8, (int) $cx + 8, $cy, (int) $cx, $cy + 8, (int) $cx - 8, $cy], $gold);
    }

    private function font(string $name): string
    {
        return resource_path("fonts/{$name}.ttf");
    }

    private function allocate(GdImage $image, string $hex): int
    {
        [$r, $g, $b] = $this->rgb($hex);

        return (int) imagecolorallocate($image, $r, $g, $b);
    }

    /** @return array{0: int, 1: int, 2: int} */
    private function rgb(string $hex): array
    {
        $n = hexdec(ltrim($hex, '#'));

        return [($n >> 16) & 255, ($n >> 8) & 255, $n & 255];
    }

    /** Mélange $from vers $to ; $amount = part de $to (0 = $from, 1 = $to). */
    private function mix(string $from, string $to, float $amount): string
    {
        $a = $this->rgb($from);
        $b = $this->rgb($to);

        return sprintf('#%02x%02x%02x', ...array_map(fn ($i) => (int) round($a[$i] + ($b[$i] - $a[$i]) * $amount), [0, 1, 2]));
    }

    private function luminance(string $hex): float
    {
        [$r, $g, $b] = array_map(fn (int $v) => ($v /= 255) <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4, $this->rgb($hex));

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    private function contrast(string $a, string $b): float
    {
        [$light, $dark] = [max($this->luminance($a), $this->luminance($b)), min($this->luminance($a), $this->luminance($b))];

        return ($light + 0.05) / ($dark + 0.05);
    }

    private function readableInk(string $surface): string
    {
        return $this->contrast(self::INK_LIGHT_THEME, $surface) >= $this->contrast(self::INK_DARK_THEME, $surface) ? self::INK_LIGHT_THEME : self::INK_DARK_THEME;
    }
}
