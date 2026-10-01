<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SupabaseInit extends Command
{
    protected $signature = 'supabase:init';

    protected $description = 'Ensure the public storage bucket exists in Supabase Storage';

    public function handle(): int
    {
        $base = rtrim((string) config('supabase.url'), '/');
        $key = (string) config('supabase.service_role_key');
        $bucket = config('supabase.bucket', 'evidence');

        if ($base === '' || $key === '' || $bucket === '') {
            $this->warn('Supabase URL / service role key / bucket not configured; skipping bucket init.');

            return self::SUCCESS;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$key,
            'apikey' => $key,
        ])->post($base.'/storage/v1/bucket', [
            'id' => $bucket,
            'name' => $bucket,
            'public' => true,
        ]);

        if ($response->successful()) {
            $this->info("Storage bucket '{$bucket}' created.");

            return self::SUCCESS;
        }

        $this->info("Storage bucket '{$bucket}' check (HTTP {$response->status()}): ".$response->json('message', $response->body()));

        return self::SUCCESS;
    }
}