# Citation Page Crash — "SSL certificate problem" (cURL error 60)

## Summary

Opening a citation detail page (`/citations/{id}`) crashed with a **500 Internal
Server Error**:

```
Illuminate\Http\Client\ConnectionException
cURL error 60: SSL certificate OpenSSL verify result: unable to get local issuer
certificate (20) for https://vpkbzwfqedbovfxcljdr.supabase.co/storage/v1/object/evidence/citations/17/....jpg
```

This is a **local development environment problem, not a bug in the application
code**. Production is unaffected.

---

## The problem, in plain words

Think of it like this:

- When you open a citation, the page asks the server: *"Is there a photo for this
  citation in Supabase storage?"*
- To ask that, your PHP server makes a **phone call** to Supabase over the
  internet (HTTPS).
- Every website gives your server an **ID card** (SSL certificate). Your server
  checks that ID card against a **list of trusted authorities** before talking.
- Your PHP install (`C:\php-8.3`) has **no copy of that trusted list** — that is
  the missing piece.
- So the server refuses to connect ("I can't verify this website's ID") and throws
  **error 60**, crashing the whole page with a 500.

The important bit: **the photo itself is fine.** Your browser can display it. The
crash only happens because your *server* is doing a background check and does not
have the "trusted list".

### Where it happens in the code

1. `resources/views/citations/show.blade.php:69` calls
   `\App\Services\SupabaseStorage::has($item->file_path)` to decide whether to show
   each evidence thumbnail.
2. `SupabaseStorage::has()` → `exists()` makes a **server-side** HTTPS request:

   ```php
   // app/Services/SupabaseStorage.php:82
   $response = Http::withHeaders([
       'Authorization' => 'Bearer '.self::key(),
   ])->send('HEAD', self::baseUrl().'/storage/v1/object/'.self::bucket().'/'.ltrim($path, '/'));
   ```

3. That cURL call fails with **error 60** because PHP has no CA bundle
   configured, so the whole request 500s.
4. `SupabaseStorage::publicUrl()` (line 46) only builds a URL string and makes
   **no** HTTP call, so the `<img>` tag itself is fine — the *browser* loads the
   image using its own trust store.

### Why it only breaks locally

The active PHP interpreter is the standalone build at `C:\php-8.3`
(`Loaded Configuration File => C:\php-8.3\php.ini`), **not** XAMPP's PHP. In that
`php.ini`, the CA-bundle settings are commented out:

```ini
;curl.cainfo =          ; line 1931
;openssl.cafile=        ; line 1940
```

With no CA bundle, every outgoing HTTPS request from PHP fails verification —
this affects Supabase uploads (`put()`), PayMongo, and mail too, not just this
page. On production (the Render/Linux server) the trusted list is already
present, so it works there.

---

## The fix, in plain words

Give PHP that "trusted list" (a file called a **CA bundle**). One already exists on
this machine:

```
C:\xampp\apache\bin\curl-ca-bundle.crt
```

(135 certificates, ~215 KB — verified valid.)

Tell PHP where it is in `C:\php-8.3\php.ini` — uncomment/set these two lines:

```ini
curl.cainfo = "C:\xampp\apache\bin\curl-ca-bundle.crt"
openssl.cafile = "C:\xampp\apache\bin\curl-ca-bundle.crt"
```

Then **restart** the server (stop and re-run `php artisan serve`). Like rebooting,
so it reads the new settings.

> Verified: with this bundle, a TLS request to the Supabase host succeeds
> (`curl_errno = 0`); without it, verification fails.

### Optional: use a fresh bundle at a stable path

Instead of pointing at XAMPP's copy, download a current bundle and keep it with
your PHP build:

- Download <https://curl.se/ca/cacert.pem>
- Save as e.g. `C:\php-8.3\extras\ssl\cacert.pem`
- Point `curl.cainfo` / `openssl.cafile` at that path instead.

---

## Two levels of fix

| | What it does | Where | Effect |
|---|---|---|---|
| **1. php.ini fix** (the real fix) | Tells PHP where its trusted list is | This machine only, not the repo | Citations, uploads, online payments, email all start working locally |
| **2. Code guard** (optional) | Makes the page *not crash* even if the server cannot reach storage; it just hides the thumbnail | Application code (`app/Services/SupabaseStorage.php`) | Page always loads; no white 500 screen |

**Why you might also want #2:** right now, any time the server cannot reach
Supabase (no internet, wrong trusted list, Supabase down), the page dies with a
500. A guard turns that into a graceful "image unavailable" instead of a crash.

**Suggested guard:** wrap the HTTP call in `SupabaseStorage::exists()` (and
`has()`) with a `try/catch` for `Illuminate\Http\Client\ConnectionException` /
`RequestException`, returning `false` on failure:

```php
public static function exists(string $path): bool
{
    if (! self::enabled()) {
        return Storage::disk('public')->exists($path);
    }

    try {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.self::key(),
        ])->send('HEAD', self::baseUrl().'/storage/v1/object/'.self::bucket().'/'.ltrim($path, '/'));

        return $response->status() === 200;
    } catch (\Illuminate\Http\Client\ConnectionException | \Illuminate\Http\Client\RequestException) {
        return false;
    }
}
```

(`put()` should probably keep throwing, since an upload failure is a real error —
but it could get a clearer message.)

---

## Action checklist

- [ ] Edit `C:\php-8.3\php.ini`: set `curl.cainfo` and `openssl.cafile` to the CA
      bundle path (or a fresh `cacert.pem`).
- [ ] Restart `php artisan serve` (and Apache, if used).
- [ ] Re-open `/citations/17` — the evidence thumbnail should load and the 500 is gone.
- [ ] (Optional) Add the `try/catch` guard to `SupabaseStorage.php` and commit it.
- [ ] Nothing needs committing for fix #1 — it is a machine-local setting.

---

## Key facts (for reference)

- Error: `cURL error 60: SSL certificate ... unable to get local issuer certificate`.
- Active PHP: `C:\php-8.3\php.exe`; loaded ini: `C:\php-8.3\php.ini`.
- Missing settings: `curl.cainfo` (line 1931) and `openssl.cafile` (line 1940),
  both commented out.
- CA bundle available: `C:\xampp\apache\bin\curl-ca-bundle.crt`.
- Crash site: `app/Services/SupabaseStorage.php:82` via
  `resources/views/citations/show.blade.php:69`.
- Scope: local only; production has a system trust store and is unaffected.
