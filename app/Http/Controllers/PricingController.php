<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\View\View;

class PricingController extends Controller
{
    public function index(): View
    {
        return view('pricing', [
            'plans' => Plan::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(),
        ]);
    }
}
