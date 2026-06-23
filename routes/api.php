<?php

use App\Models\Citation;
use App\Models\EnforcerLocation;
use App\Models\Payment;
use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn (Request $r) => $r->user());

    Route::get('/citations', fn (Request $r) => Citation::with(['violationType'])
        ->when($r->plate_number, fn ($q, $pn) => $q->where('vehicle_plate', $pn))
        ->when($r->status, fn ($q, $s) => $q->where('status', $s))
        ->latest('issued_at')
        ->paginate(20));

    Route::get('/citations/{citation}', fn (Citation $citation) => $citation->load(['violationType', 'payment', 'evidence', 'appeals']));

    Route::get('/payments', fn () => Payment::with('citation', 'cashier')->whereNotNull('paid_at')->latest('paid_at')->paginate(20));

    Route::post('/location', function (Request $r) {
        $r->validate(['latitude' => 'required|numeric', 'longitude' => 'required|numeric', 'accuracy_m' => 'nullable|numeric']);

        return EnforcerLocation::updateOrCreate(
            ['user_id' => $r->user()->id],
            [
                'latitude' => $r->latitude,
                'longitude' => $r->longitude,
                'accuracy_m' => $r->accuracy_m,
                'status' => 'active',
                'last_seen_at' => now(),
            ]
        );
    });

    Route::post('/location/offline', function (Request $r) {
        EnforcerLocation::where('user_id', $r->user()->id)->update(['status' => 'offline']);
        return response()->json(['status' => 'offline']);
    });

    Route::get('/zones', fn () => Zone::with('team:id,name')->get(['id', 'name', 'center_latitude', 'center_longitude', 'radius_m', 'team_id']));
});
