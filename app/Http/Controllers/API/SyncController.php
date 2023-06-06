<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Console\Commands\SyncOdooData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class SyncController extends Controller
{
    public function syncEmployees(Request $request)
    {
        Artisan::call(SyncOdooData::class);
        $output = Artisan::output();

        return response()->json([
            'message' => 'Employee synchronization triggered successfully.',
            'output' => $output,
        ]);
    }
}
