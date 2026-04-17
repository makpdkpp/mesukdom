<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\SendLineMessageJob;
use App\Models\BroadcastMessage;
use App\Models\Customer;
use App\Models\Room;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BroadcastController extends Controller
{
    public function index(): View
    {
        return view('dashboard.broadcasts', [
            'broadcasts' => BroadcastMessage::query()->with('room')->latest('sent_at')->get(),
            'rooms' => Room::query()->orderBy('building')->orderBy('floor')->orderBy('room_number')->get(),
            'buildings' => Room::query()
                ->whereNotNull('building')
                ->select('building')
                ->distinct()
                ->orderBy('building')
                ->pluck('building'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'scope' => ['required', 'in:all,building,floor,room'],
            'message' => ['required', 'string', 'max:2000'],
            'building' => ['nullable', 'string', 'max:100'],
            'floor' => ['nullable', 'integer', 'min:1'],
            'room_id' => ['nullable', 'integer'],
        ]);

        $tenant = app(TenantContext::class)->tenant();
        abort_if(! $tenant, 403);

        $scope = $validated['scope'];
        $room = null;

        if ($scope === 'room') {
            $room = Room::query()->findOrFail((int) ($validated['room_id'] ?? 0));
        }

        $recipientQuery = Customer::query()
            ->with('room')
            ->whereNotNull('line_user_id');

        if ($scope === 'building') {
            $building = (string) ($validated['building'] ?? '');
            abort_if($building === '', 422, 'Building is required for building broadcast scope.');

            $recipientQuery->whereHas('room', function (Builder $query) use ($building): void {
                $query->where('building', $building);
            });
        }

        if ($scope === 'floor') {
            $floor = (int) ($validated['floor'] ?? 0);
            abort_if($floor < 1, 422, 'Floor is required for floor broadcast scope.');

            $recipientQuery->whereHas('room', function (Builder $query) use ($floor, $validated): void {
                $query->where('floor', $floor);

                if (! empty($validated['building'])) {
                    $query->where('building', $validated['building']);
                }
            });
        }

        if ($scope === 'room' && $room) {
            $recipientQuery->where('room_id', $room->id);
        }

        $recipients = $recipientQuery->get();

        $broadcast = BroadcastMessage::query()->create([
            'tenant_id' => $tenant->id,
            'scope' => $scope,
            'target_building' => $validated['building'] ?? null,
            'target_floor' => $validated['floor'] ?? null,
            'room_id' => $room?->id,
            'message' => $validated['message'],
            'recipient_count' => $recipients->count(),
            'sent_at' => now(),
        ]);

        foreach ($recipients as $customer) {
            SendLineMessageJob::dispatch(
                $tenant->id,
                'broadcast_sent',
                $customer->line_user_id,
                $validated['message'],
                $customer->name,
                $customer->id,
                [
                    'broadcast_message_id' => $broadcast->id,
                    'scope' => $scope,
                    'room_id' => $room?->id,
                ]
            );
        }

        $message = $recipients->isEmpty()
            ? 'Broadcast saved, but no linked LINE recipients matched the selected segment.'
            : 'Broadcast sent to '.$recipients->count().' resident(s).';

        return back()->with('status', $message);
    }
}
