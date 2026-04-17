<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\PublicSiteMetrics;
use Illuminate\View\View;

class PricingController extends Controller
{
    public function index(PublicSiteMetrics $metrics): View
    {
        return view('pricing', $metrics->pricingPayload());
    }
}
