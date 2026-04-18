<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\RepairRequest;
use App\Models\Room;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

final class PublicSiteMetrics
{
    public function landingPayload(): array
    {
        return [
            'plans' => $this->plans(),
            'publicStats' => $this->publicStats(),
            'featuredProperty' => $this->featuredProperty(),
            'propertyHighlights' => $this->propertyHighlights(),
        ];
    }

    public function pricingPayload(): array
    {
        return [
            'plans' => $this->plans(),
            'publicStats' => $this->publicStats(),
            'featuredProperty' => $this->featuredProperty(),
        ];
    }

    public function plans(): Collection
    {
        if (! Schema::hasTable('plans')) {
            return new Collection();
        }

        return Plan::query()
            ->where('is_active', true)
            ->orderByRaw("JSON_EXTRACT(COALESCE(limits, '{}'), '$.recommended') DESC")
            ->orderBy('sort_order')
            ->get();
    }

    public function publicStats(): array
    {
        if (! $this->hasOperationalTables()) {
            return $this->emptyStats();
        }

        $roomsTotal = Room::query()->count();
        $roomsOccupied = Room::query()->where('status', 'occupied')->count();
        $tenantQuery = $this->tenantQuery();

        return [
            'tenants_total' => (clone $tenantQuery)->count(),
            'rooms_total' => $roomsTotal,
            'rooms_occupied' => $roomsOccupied,
            'rooms_vacant' => Room::query()->where('status', 'vacant')->count(),
            'occupancy_rate' => $roomsTotal > 0 ? (int) round(($roomsOccupied / $roomsTotal) * 100) : 0,
            'monthly_revenue' => (float) Payment::query()
                ->where('status', 'approved')
                ->whereYear('payment_date', now()->year)
                ->whereMonth('payment_date', now()->month)
                ->sum('amount'),
            'pending_payments' => Payment::query()->where('status', 'pending')->count(),
            'overdue_invoices' => Invoice::query()->where('status', 'overdue')->count(),
            'open_repairs' => $this->openRepairsCount(),
        ];
    }

    public function featuredProperty(): ?array
    {
        return $this->propertyHighlights()->first();
    }

    public function propertyHighlights(): Collection
    {
        if (! $this->hasOperationalTables()) {
            return collect();
        }

        return $this->tenantQuery()
            ->withCount([
                'rooms',
                'rooms as occupied_rooms_count' => fn ($query) => $query->where('status', 'occupied'),
            ])
            ->orderByDesc('rooms_count')
            ->orderBy('name')
            ->take(2)
            ->get()
            ->map(fn (Tenant $tenant) => $this->tenantSnapshot($tenant));
    }

    private function tenantSnapshot(Tenant $tenant): array
    {
        $roomsTotal = (int) ($tenant->rooms_count ?? 0);
        $roomsOccupied = (int) ($tenant->occupied_rooms_count ?? 0);

        return [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'rooms_total' => $roomsTotal,
            'rooms_occupied' => $roomsOccupied,
            'occupancy_rate' => $roomsTotal > 0 ? (int) round(($roomsOccupied / $roomsTotal) * 100) : 0,
            'monthly_revenue' => (float) Payment::query()
                ->where('tenant_id', $tenant->id)
                ->where('status', 'approved')
                ->whereYear('payment_date', now()->year)
                ->whereMonth('payment_date', now()->month)
                ->sum('amount'),
            'pending_payments' => Payment::query()
                ->where('tenant_id', $tenant->id)
                ->where('status', 'pending')
                ->count(),
            'overdue_invoices' => Invoice::query()
                ->where('tenant_id', $tenant->id)
                ->where('status', 'overdue')
                ->count(),
            'open_repairs' => $this->openRepairsCount($tenant->id),
        ];
    }

    private function openRepairsCount(?int $tenantId = null): int
    {
        if (! Schema::hasTable('repair_requests')) {
            return 0;
        }

        $query = RepairRequest::query()->whereNotIn('status', ['resolved', 'completed']);

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->count();
    }

    private function hasOperationalTables(): bool
    {
        return Schema::hasTable('tenants')
            && Schema::hasTable('rooms')
            && Schema::hasTable('invoices')
            && Schema::hasTable('payments');
    }

    private function tenantQuery(): Builder
    {
        if (! Schema::hasColumn('tenants', 'deleted_at')) {
            return Tenant::query()->withoutGlobalScopes();
        }

        return Tenant::query();
    }

    private function emptyStats(): array
    {
        return [
            'tenants_total' => 0,
            'rooms_total' => 0,
            'rooms_occupied' => 0,
            'rooms_vacant' => 0,
            'occupancy_rate' => 0,
            'monthly_revenue' => 0.0,
            'pending_payments' => 0,
            'overdue_invoices' => 0,
            'open_repairs' => 0,
        ];
    }
}