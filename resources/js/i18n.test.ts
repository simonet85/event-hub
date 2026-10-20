import { readdirSync, readFileSync, statSync } from 'node:fs';
import { join } from 'node:path';
import { describe, expect, it } from 'vitest';
import { translate } from './i18n';

const root = __dirname;

const files = (dir: string): string[] =>
    readdirSync(dir).flatMap((name) => {
        const path = join(dir, name);

        return statSync(path).isDirectory() ? files(path) : [path];
    });

const english: Record<string, string> = JSON.parse(readFileSync(join(root, '..', '..', 'lang', 'en.json'), 'utf8'));

/** Clés littérales des appels __('…') / __("…") des fichiers Vue et TypeScript (guillemets échappés pris en compte). */
function literalKeys(): { file: string; key: string }[] {
    const found: { file: string; key: string }[] = [];
    const pattern = /__\(\s*(?:'((?:[^'\\]|\\.)*)'|"((?:[^"\\]|\\.)*)")/g;

    for (const file of files(root).filter((f) => f.endsWith('.vue') || (f.endsWith('.ts') && !f.endsWith('.test.ts')))) {
        const source = readFileSync(file, 'utf8');
        for (const match of source.matchAll(pattern)) {
            const raw = match[1] ?? match[2];
            found.push({ file: file.replace(root, '').replaceAll('\\', '/'), key: raw.replace(/\\(['"\\])/g, '$1') });
        }
    }

    return found;
}

describe('traduction', () => {
    it('renvoie le texte français (la clé) quand aucune traduction n\'existe, avec les remplacements', () => {
        expect(translate('Bonjour :name', { name: 'Awa' })).toBe('Bonjour Awa');
    });

    it('remplace chaque occurrence d\'un paramètre', () => {
        expect(translate(':n sur :n', { n: 3 })).toBe('3 sur 3');
    });
});

describe('version anglaise', () => {
    it('chaque texte de l\'interface a une traduction dans lang/en.json', () => {
        const missing = literalKeys().filter(({ key }) => !(key in english));

        expect(missing).toEqual([]);
    });

    it('les traductions gardent exactement les mêmes paramètres :nom que le texte français', () => {
        const params = (text: string) => (text.match(/:[a-z]+/g) ?? []).sort();
        const broken = Object.entries(english).filter(([key, value]) => JSON.stringify(params(key)) !== JSON.stringify(params(value)));

        expect(broken).toEqual([]);
    });

    it('aucune traduction n\'est vide', () => {
        expect(Object.entries(english).filter(([, value]) => value.trim() === '')).toEqual([]);
    });

    it('les libellés envoyés par le serveur (statuts, types) sont traduits', () => {
        for (const key of ['Mariage', 'Baptême', 'Anniversaire', 'Fête', 'Autre', 'En attente', 'Confirmée', 'Échouée', 'Expirée', 'À vérifier', 'Présent(e)', 'Absent(e)']) {
            expect(english[key], key).toBeTruthy();
        }
    });
});
