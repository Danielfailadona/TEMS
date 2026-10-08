<?php

namespace App\Console\Commands;

use App\Models\Zone;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeocodeMissingZones extends Command
{
    protected $signature = 'zones:geocode-missing';
    protected $description = 'Geocode zones that are missing latitude/longitude using OpenStreetMap Nominatim';

    public function handle(): int
    {
        $zones = Zone::whereNull('center_latitude')
            ->orWhereNull('center_longitude')
            ->get();

        if ($zones->isEmpty()) {
            $this->info('No zones missing coordinates.');
            return self::SUCCESS;
        }

        $this->info("Found {$zones->count()} zones to geocode.");

        $bar = $this->output->createProgressBar($zones->count());
        $bar->start();

        foreach ($zones as $zone) {
            $query = $zone->address ?? $zone->description ?? $zone->name;
            if (empty($query)) {
                $this->warn("Zone {$zone->id} has no address/description/name to geocode.");
                $bar->advance();
                continue;
            }

            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'TEMS (https://transenfo-1.onrender.com)'
                ])->get('https://nominatim.openstreetmap.org/search', [
                    'format' => 'jsonv2',
                    'q' => $query,
                    'limit' => 1,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if (!empty($data)) {
                        $lat = $data[0]['lat'];
                        $lon = $data[0]['lon'];
                        $zone->update([
                            'center_latitude' => $lat,
                            'center_longitude' => $lon,
                        ]);
                        $this->line("Geocoded zone {$zone->id} ({$zone->name}) => {$lat}, {$lon}");
                    } else {
                        $this->warn("No results for zone {$zone->id} ({$zone->name})");
                    }
                } else {
                    $this->error("Failed request for zone {$zone->id}: " . $response->status());
                }
            } catch (\Throwable $e) {
                Log::error('Geocode error for zone ' . $zone->id, ['error' => $e->getMessage()]);
                $this->error("Exception for zone {$zone->id}: " . $e->getMessage());
            }

            // Respect Nominatim rate limit (1 request per second)
            sleep(1);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Geocoding completed.');

        return self::SUCCESS;
    }
}