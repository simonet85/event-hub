<?php

namespace App\Http\Controllers;

use App\Enums\ContributionStatus;
use App\Http\Requests\CagnotteSettingsRequest;
use App\Http\Requests\GiftRequest;
use App\Models\Contribution;
use App\Models\Event;
use App\Models\Gift;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CagnotteController extends Controller
{
    public const MAX_GIFTS = 100;

    public function index(Event $event): Response
    {
        Gate::authorize('view', $event);

        $byStatus = $event->contributions()->selectRaw('status, count(*) as total, sum(amount) as amount')->groupBy('status')->get()->keyBy(fn ($row) => $row->status->value);

        return Inertia::render('Events/Cagnotte', [
            'event' => [
                'title' => $event->title,
                'slug' => $event->slug,
                'cagnotte_enabled' => $event->cagnotte_enabled,
                'cagnotte_goal' => $event->cagnotte_goal,
                'cagnotte_message' => $event->cagnotte_message,
            ],
            'stats' => [
                'raised' => (int) ($byStatus[ContributionStatus::Confirmed->value]->amount ?? 0),
                'confirmed' => (int) ($byStatus[ContributionStatus::Confirmed->value]->total ?? 0),
                'pending' => (int) ($byStatus[ContributionStatus::Pending->value]->total ?? 0),
                'to_review' => (int) ($byStatus[ContributionStatus::Mismatch->value]->total ?? 0),
            ],
            'gifts' => $event->gifts()->orderBy('id')->get()->map(fn (Gift $gift) => [
                'id' => $gift->id,
                'name' => $gift->name,
                'description' => $gift->description,
                'target_amount' => $gift->target_amount,
                'raised' => $gift->raised(),
            ]),
            'contributions' => $event->contributions()->with(['guest:id,name', 'gift:id,name'])->latest()->paginate(50)->through(fn (Contribution $c) => [
                'id' => $c->id,
                'guest' => $c->guest->name,
                'gift' => $c->gift?->name,
                'amount' => $c->amount,
                'message' => $c->message,
                'status' => $c->status->value,
                'status_label' => __($c->status->label()),
                'created_at' => $c->created_at->toIso8601String(),
            ]),
        ]);
    }

    public function updateSettings(CagnotteSettingsRequest $request, Event $event): RedirectResponse
    {
        Gate::authorize('update', $event);

        $event->update($request->validated());

        return back()->with('cagnotte_saved', true);
    }

    public function storeGift(GiftRequest $request, Event $event): RedirectResponse
    {
        Gate::authorize('update', $event);

        if ($event->gifts()->count() >= self::MAX_GIFTS) {
            return back()->withErrors(['name' => __('La liste est limitée à :max cadeaux.', ['max' => self::MAX_GIFTS])]);
        }

        $event->gifts()->create($request->validated());

        return back();
    }

    public function destroyGift(Event $event, Gift $gift): RedirectResponse
    {
        Gate::authorize('update', $event);
        abort_unless($gift->event_id === $event->id, 404);

        $gift->delete();

        return back();
    }
}
