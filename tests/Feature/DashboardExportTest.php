<?php

use App\Models\Contribution;
use App\Models\Event;
use App\Models\Guest;
use App\Models\GuestbookEntry;
use App\Models\Photo;
use App\Models\User;
use App\Services\EventReport;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

beforeEach(function () {
    $this->host = User::factory()->create();
    $this->event = Event::factory()->for($this->host)->create();
});

function guest(Event $event, array $attrs = []): Guest
{
    return Guest::factory()->for($event)->create($attrs);
}

function readXlsx(string $path): array
{
    $reader = new XlsxReader;
    $reader->open($path);
    $sheets = [];

    foreach ($reader->getSheetIterator() as $sheet) {
        $rows = [];
        foreach ($sheet->getRowIterator() as $row) {
            $rows[] = $row->toArray();
        }
        $sheets[$sheet->getName()] = $rows;
    }

    $reader->close();

    return $sheets;
}

// ───────────── Tableau de bord ─────────────

test('le tableau de bord calcule présents, absents, attente, couverts et taux de réponse', function () {
    guest($this->event, ['rsvp_status' => 'accepted', 'companions_count' => 2]);
    guest($this->event, ['rsvp_status' => 'accepted', 'companions_count' => 0]);
    guest($this->event, ['rsvp_status' => 'declined']);
    guest($this->event, ['rsvp_status' => 'pending']);

    $this->actingAs($this->host)->get("/events/{$this->event->slug}/dashboard")->assertOk()->assertInertia(fn ($page) => $page
        ->component('Events/Dashboard')
        ->where('rsvp.total', 4)->where('rsvp.accepted', 2)->where('rsvp.declined', 1)->where('rsvp.pending', 1)
        ->where('rsvp.headcount', 4)->where('rsvp.response_rate', 75));
});

test('un tableau de bord vide ne divise pas par zéro', function () {
    $this->actingAs($this->host)->get("/events/{$this->event->slug}/dashboard")
        ->assertInertia(fn ($page) => $page->where('rsvp.response_rate', 0)->where('rsvp.headcount', 0)->has('tables', 0)->has('dietary', 0));
});

test('les régimes ne listent que les présents avec un régime renseigné', function () {
    guest($this->event, ['name' => 'Alice', 'rsvp_status' => 'accepted', 'companions_count' => 1, 'dietary_restrictions' => 'Sans gluten', 'table_name' => 'Table 1']);
    guest($this->event, ['rsvp_status' => 'accepted', 'dietary_restrictions' => null]);
    guest($this->event, ['rsvp_status' => 'declined', 'dietary_restrictions' => 'Végétarien']);

    $this->actingAs($this->host)->get("/events/{$this->event->slug}/dashboard")->assertInertia(fn ($page) => $page
        ->has('dietary', 1)->where('dietary.0.guest', 'Alice')->where('dietary.0.restriction', 'Sans gluten')
        ->where('dietary.0.seats', 2)->where('dietary.0.table', 'Table 1'));
});

test('le plan de table regroupe par table, exclut les absents et place « sans table » en dernier', function () {
    guest($this->event, ['rsvp_status' => 'accepted', 'companions_count' => 1, 'table_name' => 'Table 2']);
    guest($this->event, ['rsvp_status' => 'accepted', 'companions_count' => 0, 'table_name' => 'Table 1']);
    guest($this->event, ['rsvp_status' => 'pending', 'table_name' => 'Table 1']);
    guest($this->event, ['rsvp_status' => 'declined', 'table_name' => 'Table 1']);
    guest($this->event, ['rsvp_status' => 'accepted', 'companions_count' => 0, 'table_name' => null]);

    $this->actingAs($this->host)->get("/events/{$this->event->slug}/dashboard")->assertInertia(fn ($page) => $page
        ->has('tables', 3)
        ->where('tables.0', ['table' => 'Table 1', 'guests' => 2, 'headcount' => 2])
        ->where('tables.1', ['table' => 'Table 2', 'guests' => 1, 'headcount' => 2])
        ->where('tables.2', ['table' => 'Sans table', 'guests' => 1, 'headcount' => 1]));
});

test('le tableau de bord résume la cagnotte et la modération', function () {
    $this->event->update(['cagnotte_enabled' => true, 'cagnotte_goal' => 100000]);
    $g = guest($this->event);
    Contribution::factory()->for($g)->confirmed()->create(['amount' => 25000]);
    Contribution::factory()->for($g)->create(['amount' => 9999, 'status' => 'pending']);
    GuestbookEntry::factory()->for($g)->create();
    Photo::factory()->for($g)->count(2)->create();

    $this->actingAs($this->host)->get("/events/{$this->event->slug}/dashboard")->assertInertia(fn ($page) => $page
        ->where('cagnotte.raised', 25000)->where('cagnotte.goal', 100000)->where('cagnotte.pending', 1)
        ->where('moderation.entries', 1)->where('moderation.photos', 2));
});

test('sans cagnotte activée, le tableau de bord ne l\'expose pas', function () {
    $this->actingAs($this->host)->get("/events/{$this->event->slug}/dashboard")->assertInertia(fn ($page) => $page->where('cagnotte', null));
});

test('un autre utilisateur ou un visiteur n\'accède pas au tableau de bord', function () {
    $this->actingAs(User::factory()->create())->get("/events/{$this->event->slug}/dashboard")->assertForbidden();
    auth()->logout();
    $this->get("/events/{$this->event->slug}/dashboard")->assertRedirect('/login');
});

// ───────────── Attribution des tables ─────────────

test('l\'hôte attribue une table à un invité', function () {
    $g = guest($this->event);

    $this->actingAs($this->host)->patch("/events/{$this->event->slug}/guests/{$g->id}", ['table_name' => '  Table des mariés '])->assertRedirect();

    expect($g->fresh()->table_name)->toBe('Table des mariés');
});

test('une table vide retire l\'attribution', function () {
    $g = guest($this->event, ['table_name' => 'Table 4']);

    $this->actingAs($this->host)->patch("/events/{$this->event->slug}/guests/{$g->id}", ['table_name' => '  '])->assertRedirect();

    expect($g->fresh()->table_name)->toBeNull();
});

test('le nom de table est limité à 100 caractères', function () {
    $g = guest($this->event);

    $this->actingAs($this->host)->patch("/events/{$this->event->slug}/guests/{$g->id}", ['table_name' => str_repeat('x', 101)])->assertSessionHasErrors('table_name');
});

test('on ne peut pas attribuer une table à l\'invité d\'un autre événement ni sans en être l\'hôte', function () {
    $foreign = Guest::factory()->create();
    $mine = guest($this->event);

    $this->actingAs($this->host)->patch("/events/{$this->event->slug}/guests/{$foreign->id}", ['table_name' => 'X'])->assertNotFound();
    $this->actingAs(User::factory()->create())->patch("/events/{$this->event->slug}/guests/{$mine->id}", ['table_name' => 'X'])->assertForbidden();
    expect($mine->fresh()->table_name)->toBeNull()->and($foreign->fresh()->table_name)->toBeNull();
});

// ───────────── Exports ─────────────

test('le CSV est lisible dans Excel : BOM UTF-8, séparateur point-virgule, en-têtes français', function () {
    guest($this->event, ['name' => 'Élodie', 'email' => 'e@example.com', 'phone' => '+33 6 12 34 56 78', 'rsvp_status' => 'accepted', 'companions_count' => 1, 'table_name' => 'Table 1', 'dietary_restrictions' => 'Sans lactose']);

    $response = $this->actingAs($this->host)->get("/events/{$this->event->slug}/exports/guests.csv")->assertOk();
    $body = $response->streamedContent();

    expect($body)->toStartWith("\xEF\xBB\xBF".'Nom;Email;Téléphone;Réponse;Accompagnants;Couverts;Table;"Régime alimentaire";Chanson;Message')
        ->and($body)->toContain('Élodie;e@example.com;"+33 6 12 34 56 78";')->toContain('Présent(e);1;2;"Table 1"')->toContain('Sans lactose');
    expect($response->headers->get('Content-Disposition'))->toContain("{$this->event->slug}-invites.csv");
});

test('les cellules saisies par les invités ne peuvent pas injecter de formule', function (string $payload) {
    guest($this->event, ['name' => $payload, 'email' => null, 'phone' => null, 'rsvp_status' => 'accepted', 'dietary_restrictions' => $payload, 'favorite_song' => $payload, 'rsvp_message' => $payload]);

    $csv = $this->actingAs($this->host)->get("/events/{$this->event->slug}/exports/guests.csv")->streamedContent();

    $handle = fopen('php://memory', 'r+');
    fwrite($handle, substr($csv, 3));
    rewind($handle);
    fgetcsv($handle, 0, ';', '"', '');
    $row = fgetcsv($handle, 0, ';', '"', '');

    foreach ([0, 7, 8, 9] as $column) {
        expect($row[$column])->toBe("'".$payload);
    }
    foreach ($row as $cell) {
        expect($cell)->not->toMatch('/^[=+\-@\t\r]/');
    }
})->with(['=HYPERLINK("http://evil","clic")', '+1+1', '-2+3', '@SUM(A1)']);

test('un numéro de téléphone international n\'est pas altéré mais une formule déguisée l\'est', function () {
    expect(EventReport::safeCell('+33 6 12 34 56 78'))->toBe('+33 6 12 34 56 78')
        ->and(EventReport::safeCell('+33612345678'))->toBe('+33612345678')
        ->and(EventReport::safeCell('+1+1'))->toBe("'+1+1")
        ->and(EventReport::safeCell('+33-6'))->toBe("'+33-6");
});

test('le CSV échappe les points-virgules et les guillemets', function () {
    guest($this->event, ['name' => 'Dupont; "Jean"', 'rsvp_status' => 'accepted']);

    $csv = $this->actingAs($this->host)->get("/events/{$this->event->slug}/exports/guests.csv")->streamedContent();

    expect($csv)->toContain('"Dupont; ""Jean"""');
});

test('le classeur Excel contient les trois feuilles avec les bonnes données', function () {
    guest($this->event, ['name' => 'Alice', 'rsvp_status' => 'accepted', 'companions_count' => 2, 'table_name' => 'Table 1', 'dietary_restrictions' => 'Végétarien']);
    guest($this->event, ['name' => 'Bob', 'rsvp_status' => 'declined']);
    guest($this->event, ['name' => 'Chloé', 'rsvp_status' => 'pending']);

    $response = $this->actingAs($this->host)->get("/events/{$this->event->slug}/exports/guests.xlsx")->assertOk();
    expect($response->headers->get('Content-Type'))->toBe('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $sheets = readXlsx($response->baseResponse->getFile()->getPathname());

    expect(array_keys($sheets))->toBe(['Invités', 'Régimes', 'Plan de table']);
    expect($sheets['Invités'][0])->toBe(EventReport::guestHeaders());
    expect(array_column($sheets['Invités'], 0))->toBe(['Nom', 'Alice', 'Bob', 'Chloé']);
    expect($sheets['Invités'][1][3])->toBe('Présent(e)')->and($sheets['Invités'][1][5])->toBe(3);
    expect($sheets['Régimes'][1])->toBe(['Alice', 'Table 1', 'Végétarien', 3]);
    expect(array_column($sheets['Plan de table'], 1))->toBe(['Invité', 'Alice', 'Chloé']);
});

test('le classeur Excel neutralise aussi les formules', function () {
    guest($this->event, ['name' => '=cmd|calc', 'rsvp_status' => 'accepted', 'dietary_restrictions' => '=1+1']);

    $response = $this->actingAs($this->host)->get("/events/{$this->event->slug}/exports/guests.xlsx");
    $sheets = readXlsx($response->baseResponse->getFile()->getPathname());

    expect($sheets['Invités'][1][0])->toBe("'=cmd|calc")
        ->and($sheets['Régimes'][1][0])->toBe("'=cmd|calc")
        ->and($sheets['Régimes'][1][2])->toBe("'=1+1");
});

test('les PDF traiteur et plan de table sont de vrais PDF', function (string $path) {
    guest($this->event, ['name' => 'Alice', 'rsvp_status' => 'accepted', 'table_name' => 'Table 1', 'dietary_restrictions' => 'Sans gluten']);

    $response = $this->actingAs($this->host)->get("/events/{$this->event->slug}/exports/{$path}")->assertOk();

    expect($response->headers->get('Content-Type'))->toBe('application/pdf')
        ->and($response->getContent())->toStartWith('%PDF')
        ->and(strlen($response->getContent()))->toBeGreaterThan(1000);
    expect($response->headers->get('Content-Disposition'))->toContain("{$this->event->slug}-".($path === 'traiteur.pdf' ? 'traiteur' : 'plan-de-table').'.pdf');
})->with(['traiteur.pdf', 'plan-de-table.pdf']);

test('les gabarits PDF échappent le contenu saisi par les invités', function () {
    $g = guest($this->event, ['name' => '<script>alert(1)</script>', 'rsvp_status' => 'accepted', 'table_name' => '<b>T</b>', 'dietary_restrictions' => '<img src=x onerror=alert(1)>']);
    $report = new EventReport($this->event);

    $html = view('pdf.caterer', ['event' => $this->event, 'rsvp' => $report->rsvp(), 'dietary' => $report->dietary(), 'dietarySeats' => 1])->render()
        .view('pdf.seating', ['event' => $this->event, 'tables' => $report->seating(), 'rsvp' => $report->rsvp()])->render();

    expect($html)->not->toContain('<script>alert(1)</script>')->not->toContain('<img src=x')->not->toContain('<b>T</b>')
        ->and($html)->toContain('&lt;script&gt;');
});

test('les exports sont réservés à l\'hôte', function (string $path) {
    guest($this->event);

    $this->actingAs(User::factory()->create())->get("/events/{$this->event->slug}/exports/{$path}")->assertForbidden();
    auth()->logout();
    $this->get("/events/{$this->event->slug}/exports/{$path}")->assertRedirect('/login');
})->with(['guests.xlsx', 'guests.csv', 'traiteur.pdf', 'plan-de-table.pdf']);

test('les exports d\'un événement vide fonctionnent', function (string $path) {
    $this->actingAs($this->host)->get("/events/{$this->event->slug}/exports/{$path}")->assertOk();
})->with(['guests.xlsx', 'guests.csv', 'traiteur.pdf', 'plan-de-table.pdf']);

test('seul le contenu de l\'événement demandé est exporté', function () {
    guest($this->event, ['name' => 'Alice']);
    Guest::factory()->create(['name' => 'Intrus']);

    $csv = $this->actingAs($this->host)->get("/events/{$this->event->slug}/exports/guests.csv")->streamedContent();

    expect($csv)->toContain('Alice')->not->toContain('Intrus');
});

test('safeCell neutralise uniquement les débuts de cellule dangereux', function (string|int $in, string|int $out) {
    expect(EventReport::safeCell($in))->toBe($out);
})->with([
    ['=1+1', "'=1+1"], ['+1+1', "'+1+1"], ['+33612345678', '+33612345678'], ['-5', "'-5"], ['@x', "'@x"], ["\tx", "'\tx"], ["\rx", "'\rx"],
    ['Alice', 'Alice'], ['a=b', 'a=b'], ['', ''], [42, 42],
]);
