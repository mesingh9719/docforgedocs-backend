<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Tax;
use Illuminate\Http\Request;

class MasterDataController extends Controller
{
    /**
     * Get list of active currencies.
     */
    public function currencies()
    {
        $currencies = Currency::where('is_active', true)->get();
        return response()->json(['data' => $currencies]);
    }

    /**
     * Get list of active tax rates.
     */
    public function taxRates()
    {
        $taxes = Tax::where('is_active', true)->get();
        return response()->json(['data' => $taxes]);
    }
}
