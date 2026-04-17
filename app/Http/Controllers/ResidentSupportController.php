<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\RepairRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResidentSupportController extends Controller
{
    public function createRepairRequest(Customer $customer): View
    {
        abort_if($customer->tenant?->status === 'suspended', 403, 'This service is currently unavailable.');

        $customer->loadMissing('room', 'tenant');

        return view('resident.repair-request', [
            'customer' => $customer,
        ]);
    }

    public function storeRepairRequest(Request $request, Customer $customer): RedirectResponse
    {
        abort_if($customer->tenant?->status === 'suspended', 403, 'This service is currently unavailable.');

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
        ]);

        RepairRequest::query()->create([
            'tenant_id' => $customer->tenant_id,
            'customer_id' => $customer->id,
            'room_id' => $customer->room_id,
            'source' => 'line_rich_menu',
            'title' => $validated['title'],
            'description' => $validated['description'],
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        return back()->with('status', 'ส่งคำขอแจ้งซ่อมเรียบร้อยแล้ว เจ้าของหอจะติดต่อกลับโดยเร็วที่สุด');
    }
}
