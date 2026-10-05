<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class SupabaseStorage
{
    public static function bucket(): string
    {
        return config('supabase.bucket', 'evidence');
    }

    public static function baseUrl(): string
    {
        return rtrim((string) config('supabase.url'), '/');
    }

    public static function key(): string
    {
        return (string) config('supabase.service_role_key');
    }

    public static function enabled(): bool
    {
        return self::baseUrl() !== '' && self::key() !== '';
    }

    public static function publicUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (! self::enabled()) {
            return asset('storage/'.ltrim($path, '/'));
        }

        return self::baseUrl().'/storage/v1/object/public/'.self::bucket().'/'.ltrim($path, '/');
    }

    public static function put(string $path, UploadedFile $file): string
    {
        $realPath = $file->getRealPath();
        $mime = $file->getMimeType() ?: 'application/octet-stream';
        $objectPath = ltrim($path, '/');
        $url = self::baseUrl().'/storage/v1/object/'.self::bucket().'/'.$objectPath;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.self::key(),
            'apikey' => self::key(),
            'Content-Type' => $mime,
        ])->withBody(file_get_contents($realPath), $mime)
            ->post($url);

        if (! $response->successful()) {
            throw new RuntimeException('Supabase upload failed for ['.$objectPath.'] (HTTP '.$response->status().'): '.$response->body());
        }

        if (! self::exists($path)) {
            throw new RuntimeException('Supabase upload reported success but object is missing for ['.$objectPath.'].');
        }

        return $path;
    }

    public static function exists(string $path): bool
    {
        if (! self::enabled()) {
            return Storage::disk('public')->exists($path);
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.self::key(),
        ])->send('HEAD', self::baseUrl().'/storage/v1/object/'.self::bucket().'/'.ltrim($path, '/'));

        return $response->status() === 200;
    }

    protected static array $existsCache = [];

    public static function has(string $path): bool
    {
        if (blank($path)) {
            return false;
        }

        if (! array_key_exists($path, self::$existsCache)) {
            self::$existsCache[$path] = self::exists($path);
        }

        return self::$existsCache[$path];
    }
}