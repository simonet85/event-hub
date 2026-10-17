<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Convertit une adresse en coordonnées via Nominatim (OpenStreetMap).
 *
 * Nominatim refuse les adresses trop détaillées ou répétitives : on essaie donc l'adresse complète,
 * puis des variantes plus courtes (premier élément + ville, nom du lieu + ville). Une adresse
 * introuvable renvoie null sans jamais lever d'exception : la carte reste alors telle quelle.
 */
class Geocoder
{
    private const MAX_ATTEMPTS = 4;

    /**
     * @return array{latitude: float, longitude: float}|null
     */
    public function locate(?string $address, ?string $placeName = null): ?array
    {
        if (! config('services.geocoding.enabled') || blank($address)) {
            return null;
        }

        $queries = array_slice($this->candidates((string) $address, $placeName), 0, self::MAX_ATTEMPTS);

        foreach ($queries as $i => $query) {
            if ($i > 0) {
                // Nominatim limite à 1 requête par seconde.
                usleep((int) config('services.geocoding.delay_ms') * 1000);
            }

            // Seules les adresses trouvées sont mises en cache : un échec réseau ne doit pas être mémorisé.
            $key = 'geocode:'.sha1($query);
            $found = Cache::get($key) ?? $this->search($query);

            if ($found !== null) {
                Cache::put($key, $found, now()->addDays(30));

                return $found;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public function candidates(string $address, ?string $placeName = null): array
    {
        $segments = $this->segments($address);
        $city = count($segments) >= 2 ? $segments[count($segments) - 2] : null;

        $candidates = [implode(', ', $segments)];

        if ($city !== null) {
            $candidates[] = $segments[0].', '.$city;
            if (filled($placeName)) {
                $candidates[] = trim($placeName).', '.$city;
            }
            if (count($segments) > 2) {
                $candidates[] = implode(', ', array_slice($segments, -3));
            }
        }

        return array_values(array_unique(array_filter($candidates)));
    }

    /**
     * Éléments de l'adresse sans doublons consécutifs ni apostrophes typographiques.
     *
     * @return list<string>
     */
    private function segments(string $address): array
    {
        $address = str_replace(['’', '‘'], "'", $address);
        $segments = [];

        foreach (explode(',', $address) as $part) {
            $part = trim($part);
            if ($part !== '' && ! in_array(mb_strtolower($part), array_map('mb_strtolower', $segments), true)) {
                $segments[] = $part;
            }
        }

        return $segments;
    }

    /**
     * @return array{latitude: float, longitude: float}|null
     */
    private function search(string $query): ?array
    {
        try {
            $response = Http::withHeaders(['User-Agent' => config('services.geocoding.user_agent')])
                ->timeout(5)
                ->get(config('services.geocoding.url'), ['format' => 'jsonv2', 'limit' => 1, 'q' => $query]);
        } catch (Throwable) {
            return null;
        }

        $first = $response->successful() ? ($response->json()[0] ?? null) : null;

        if (! is_array($first) || ! isset($first['lat'], $first['lon'])) {
            return null;
        }

        return ['latitude' => (float) $first['lat'], 'longitude' => (float) $first['lon']];
    }
}
