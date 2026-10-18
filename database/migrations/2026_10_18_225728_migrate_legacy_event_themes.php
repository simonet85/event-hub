<?php

use App\Enums\EventType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Les événements qui n'ont jamais personnalisé leur thème (theme_config identique à l'ancien
     * préréglage « Éditorial chic ») passent au préréglage « Papier & lumière ». Les thèmes choisis
     * par les hôtes ne sont jamais modifiés.
     */
    public function up(): void
    {
        $this->swap(fn (EventType $type) => [$type->legacyTheme(), $type->defaultTheme()]);
    }

    public function down(): void
    {
        $this->swap(fn (EventType $type) => [$type->defaultTheme(), $type->legacyTheme()]);
    }

    /** @param  Closure(EventType): array{0: array<string, string>, 1: array<string, string>}  $themes */
    private function swap(Closure $themes): void
    {
        DB::table('events')->orderBy('id')->each(function ($row) use ($themes) {
            $type = EventType::tryFrom($row->type);
            $current = json_decode((string) $row->theme_config, true);

            if ($type === null || ! is_array($current)) {
                return;
            }

            [$from, $to] = $themes($type);

            ksort($current);
            ksort($from);

            if ($current === $from) {
                DB::table('events')->where('id', $row->id)->update(['theme_config' => json_encode($to)]);
            }
        });
    }
};
