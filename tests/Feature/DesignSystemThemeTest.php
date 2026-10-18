<?php

use App\Enums\EventType;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

/** Contraste WCAG entre deux couleurs #rrggbb. */
function contrast(string $a, string $b): float
{
    $luminance = function (string $hex): float {
        $n = hexdec(ltrim($hex, '#'));
        $channel = fn (int $v) => ($v /= 255) <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;

        return 0.2126 * $channel(($n >> 16) & 255) + 0.7152 * $channel(($n >> 8) & 255) + 0.0722 * $channel($n & 255);
    };

    [$light, $dark] = [max($luminance($a), $luminance($b)), min($luminance($a), $luminance($b))];

    return ($light + 0.05) / ($dark + 0.05);
}

test('les préréglages de thème respectent le Design System « Papier & lumière »', function () {
    expect(EventType::Wedding->defaultTheme())->toBe(['primary_color' => '#4e6b48', 'secondary_color' => '#fbf6ee', 'font_family' => 'serif'])
        ->and(EventType::Baptism->defaultTheme()['primary_color'])->toBe('#42698f')
        ->and(EventType::Birthday->defaultTheme()['primary_color'])->toBe('#a0432a')
        ->and(EventType::Party->defaultTheme()['primary_color'])->toBe('#7a3d6f');
});

test('chaque préréglage atteint 4.5:1 pour le texte sur le fond (AA)', function (EventType $type) {
    $theme = $type->defaultTheme();

    expect(contrast($theme['primary_color'], $theme['secondary_color']))->toBeGreaterThanOrEqual(4.5);
})->with(EventType::cases());

test('l\'ancien préréglage échouait au contraste AA, ce qui justifie le changement', function () {
    $legacy = EventType::Wedding->legacyTheme();

    expect(contrast($legacy['primary_color'], $legacy['secondary_color']))->toBeLessThan(4.5);
});

test('chaque type fixe une classe ev-* et seul le thème Fête est sombre', function () {
    expect(EventType::Wedding->cssClass())->toBe('ev-mariage')
        ->and(EventType::Baptism->cssClass())->toBe('ev-bapteme')
        ->and(EventType::Birthday->cssClass())->toBe('ev-anniversaire')
        ->and(EventType::Party->cssClass())->toBe('ev-fete')
        ->and(EventType::Party->prefersDarkTheme())->toBeTrue()
        ->and(EventType::Wedding->prefersDarkTheme())->toBeFalse();
});

test('la page publique expose la classe ev-*, le préréglage et le thème sombre', function () {
    $event = Event::factory()->for(User::factory())->create(['type' => EventType::Party, 'theme_config' => EventType::Party->defaultTheme()]);

    $this->get("/e/{$event->slug}")->assertInertia(fn (Assert $page) => $page
        ->where('event.css_class', 'ev-fete')
        ->where('event.dark_theme', true)
        ->where('event.theme_preset.primary_color', '#7a3d6f')
        ->where('event.theme_config.primary_color', '#7a3d6f'));
});

test('la création garde les couleurs choisies par l\'hôte par-dessus le préréglage', function () {
    $this->actingAs(User::factory()->create())->post('/events', [
        'title' => 'Mariage',
        'type' => 'wedding',
        'event_date' => now()->addMonth()->format('Y-m-d H:i:s'),
        'theme_config' => ['primary_color' => '#112233'],
    ])->assertRedirect();

    $theme = Event::firstWhere('title', 'Mariage')->theme_config;
    expect($theme['primary_color'])->toBe('#112233')->and($theme['secondary_color'])->toBe('#fbf6ee');
});

test('la migration convertit les thèmes jamais personnalisés et respecte les autres', function () {
    $untouched = Event::factory()->for(User::factory())->create(['type' => EventType::Wedding]);
    $custom = Event::factory()->for(User::factory())->create(['type' => EventType::Baptism]);

    DB::table('events')->where('id', $untouched->id)->update(['theme_config' => json_encode(EventType::Wedding->legacyTheme())]);
    DB::table('events')->where('id', $custom->id)->update(['theme_config' => json_encode(['primary_color' => '#123456', 'secondary_color' => '#f2f7fb', 'font_family' => 'serif'])]);

    (include database_path('migrations/2026_10_18_225728_migrate_legacy_event_themes.php'))->up();

    expect($untouched->fresh()->theme_config)->toBe(EventType::Wedding->defaultTheme())
        ->and($custom->fresh()->theme_config['primary_color'])->toBe('#123456');
});
