<?php

namespace App\Support;

/**
 * Fichiers statiques nécessaires à l'affichage de points d'entrée Vite, d'après public/build/manifest.json :
 * le fichier de chaque entrée, ses dépendances statiques (récursivement) et leurs feuilles de style.
 * Les imports dynamiques ne sont pas suivis : ce sont d'autres pages, que l'on ne veut pas embarquer.
 */
class ViteAssets
{
    public function __construct(private readonly ?string $manifestPath = null, private readonly ?string $hotFile = null) {}

    /**
     * @param  list<string>  $entries  Clés du manifeste (ex. « resources/js/app.ts »).
     * @return list<string> Chemins publics (« /build/assets/app-abc.js »), sans doublon ; vide en mode développement.
     */
    public function forEntries(array $entries): array
    {
        $manifest = $this->manifest();

        if ($manifest === []) {
            return [];
        }

        $files = [];
        $seen = [];

        $visit = function (string $key) use (&$visit, &$files, &$seen, $manifest): void {
            if (isset($seen[$key]) || ! isset($manifest[$key])) {
                return;
            }
            $seen[$key] = true;
            $chunk = $manifest[$key];

            foreach ([$chunk['file'] ?? null, ...($chunk['css'] ?? [])] as $file) {
                if (is_string($file)) {
                    $files[] = '/build/'.ltrim($file, '/');
                }
            }

            foreach ($chunk['imports'] ?? [] as $import) {
                $visit($import);
            }
        };

        foreach ($entries as $entry) {
            $visit($entry);
        }

        return array_values(array_unique($files));
    }

    /** @return array<string, array<string, mixed>> */
    private function manifest(): array
    {
        // En développement (serveur Vite lancé), le manifeste peut être périmé et les fichiers ne sont pas servis tels quels.
        if (is_file($this->hotFile ?? public_path('hot'))) {
            return [];
        }

        $path = $this->manifestPath ?? public_path('build/manifest.json');

        if (! is_file($path)) {
            return [];
        }

        $manifest = json_decode((string) file_get_contents($path), true);

        return is_array($manifest) ? $manifest : [];
    }
}
