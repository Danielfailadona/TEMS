<?php

use App\Http\Controllers\AppealController;
use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\CitationController;
use App\Http\Controllers\CitizenPortalController;
use App\Http\Controllers\ClampingController;
use App\Http\Controllers\ClampingRequestController;
use App\Http\Controllers\ImpoundingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FrontDeskController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OwnerPortalController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PayMongoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ZoneController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/welcome', fn () => view('welcome'))->name('welcome');

Route::get('/', fn () => view('welcome'));

Route::get('/health', fn () => response('ok', 200));

Route::middleware('guest')->group(function () {
    Route::get('account-procedure', [LoginController::class, 'accountProcedure'])->name('account.procedure');
    Route::post('account-procedure', [LoginController::class, 'store'])->middleware('throttle:5,5')->name('account.procedure.store');

    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->middleware('throttle:5,5');
    
    Route::get('register', [LoginController::class, 'showRegister'])->name('register');
    Route::post('register', [LoginController::class, 'storeRegister'])->middleware('throttle:5,5');

    // Password Reset via Supabase
    Route::get('password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->middleware('throttle:3,5')->name('password.email');
    Route::get('password/reset/callback', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('password/reset', [ResetPasswordController::class, 'reset'])->middleware('throttle:3,5')->name('password.update');

    // Citizen Portal - Public access
    Route::prefix('citizen')->name('citizen.')->group(function () {
        Route::get('citation/lookup', [CitizenPortalController::class, 'citationLookup'])->name('citation.lookup');
        Route::get('citation/search', [CitizenPortalController::class, 'citationSearch'])->name('citation.search');
        Route::get('citation/{citation}', [CitizenPortalController::class, 'citationDetail'])->name('citation.detail');
        Route::get('request-clamping', [CitizenPortalController::class, 'clampingRequest'])->name('clamping.show');
        Route::post('request-clamping', [CitizenPortalController::class, 'storeClampingRequest'])->middleware('throttle:5,5')->name('clamping.store');
        Route::get('clamping/success', [CitizenPortalController::class, 'clampingSuccess'])->name('clamping.success');
    });

    // PayMongo Webhook (public, verified by signature)
    Route::post('webhook/paymongo', [PayMongoController::class, 'webhook'])->name('webhook.paymongo');
});

Route::get('account/pending', [LoginController::class, 'pending'])->name('account.pending');

// Email Verification
Route::get('email/verify/callback', fn () => view('auth.verify-callback'))->name('verification.callback');
Route::post('email/verify', [VerificationController::class, 'verify'])->name('verification.verify');

Route::middleware('auth')->group(function () {
    Route::get('email/verify', [VerificationController::class, 'showNotice'])->name('verification.notice');
    Route::post('email/verification-notification', [VerificationController::class, 'resend'])->middleware('throttle:3,1')->name('verification.resend');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('logout', function () {
        return redirect()->route('dashboard');
    });
});

Route::middleware(['auth', 'active', 'approved'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('citations', CitationController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('citations/{citation}/refer-impounding', [CitationController::class, 'referToImpounding'])->name('citations.refer-impounding');
    Route::resource('payments', PaymentController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);
    Route::resource('clamping', ClampingController::class)->only(['index', 'create', 'store', 'show']);
    Route::prefix('clamping-requests')->name('clamping-requests.')->controller(ClampingRequestController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('{clampingRequest}', 'show')->name('show');
        Route::post('{clampingRequest}/approve', 'approve')->name('approve');
        Route::post('{clampingRequest}/reject', 'reject')->name('reject');
        Route::post('{clampingRequest}/assign', 'assign')->name('assign');
        Route::post('{clampingRequest}/resolve', 'resolve')->name('resolve');
    });
    Route::prefix('impounding')->name('impounding.')->controller(ImpoundingController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('{clamping}/print-release', 'printRelease')->name('print-release');
        Route::get('{clamping}', 'show')->name('show');
        Route::post('{clamping}/mark-paid', 'markPaid')->name('mark-paid');
        Route::post('{clamping}/mark-waiting-release', 'markWaitingRelease')->name('mark-waiting-release');
        Route::post('{clamping}/process-release', 'processRelease')->name('process-release');
    });

    Route::resource('appeals', AppealController::class)->except(['destroy']);
    Route::resource('teams', TeamController::class);
    Route::patch('teams/{team}/toggle-active', [TeamController::class, 'toggleActive'])->name('teams.toggle-active');
    Route::post('teams/{team}/zones/toggle', [TeamController::class, 'toggleZone'])->name('teams.zones.toggle');
    Route::resource('zones', ZoneController::class);
    Route::patch('zones/{zone}/toggle-active', [ZoneController::class, 'toggleActive'])->name('zones.toggle-active');
    Route::get('tracking', [TrackingController::class, 'index'])->name('tracking.index');
    Route::get('tracking/locations', [TrackingController::class, 'locations'])->name('tracking.locations');

    Route::get('my-zone', [ZoneController::class, 'myZone'])->name('enforcer.zone');

    // Online Payment
    Route::get('payments/{payment}/success', [PayMongoController::class, 'success'])->name('payments.online.success');
    Route::get('payments/{payment}/cancel', [PayMongoController::class, 'cancel'])->name('payments.online.cancel');
    Route::post('citations/{citation}/checkout', [PayMongoController::class, 'checkout'])->name('citations.checkout');

    // User management
    Route::resource('users', UserController::class)->except(['show', 'destroy']);
    Route::post('users/{user}/approve', [UserController::class, 'approve'])->name('users.approve');
    Route::post('users/{user}/reject', [UserController::class, 'reject'])->name('users.reject');
    Route::post('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::post('users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
    Route::get('users/{user}/devices', [UserController::class, 'devices'])->name('users.devices');
    Route::delete('devices/{device}/force-logout', [UserController::class, 'forceLogout'])->name('devices.force-logout');

    // Notifications
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllRead');
    Route::delete('notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::get('api/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('api.notifications.unread');
    Route::get('api/notifications/latest', [NotificationController::class, 'latest'])->name('api.notifications.latest');

    // Front Desk
    Route::get('front-desk', [FrontDeskController::class, 'index'])->name('frontdesk.index');
    Route::get('front-desk/search', [FrontDeskController::class, 'search'])->name('frontdesk.search');

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('revenue', [ReportController::class, 'revenue'])->name('revenue');
        Route::get('citations', [ReportController::class, 'citations'])->name('citations');
        Route::get('enforcer-performance', [ReportController::class, 'enforcerPerformance'])->name('enforcer-performance');
    });

    // Archives
    Route::get('archives', [ArchiveController::class, 'index'])->name('archives.index');

    // Advanced Search
    Route::get('search', [SearchController::class, 'index'])->name('search.index');
    Route::post('search/save', [SearchController::class, 'save'])->name('search.save');
    Route::delete('search/{search}', [SearchController::class, 'destroy'])->name('search.destroy');

    // Audit Logs
    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

    // Profile
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');

    // Settings
    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');

    // Enforcer GPS location (web)
    Route::post('location', function (Request $request) {
        $request->validate(['latitude' => 'required|numeric', 'longitude' => 'required|numeric', 'accuracy_m' => 'nullable|numeric']);
        $user = auth()->user();
        if ($user->isRole(\App\Enums\Role::Enforcer, \App\Enums\Role::ClampingOfficer)) {
            return \App\Models\EnforcerLocation::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                    'accuracy_m' => $request->accuracy_m,
                    'status' => 'active',
                    'last_seen_at' => now(),
                ]
            );
        }
        return response()->json(['error' => 'Unauthorized'], 403);
    })->name('location.update');

    // Owner Portal
    Route::prefix('owner')->name('owner.')->group(function () {
        Route::get('citations', [OwnerPortalController::class, 'citations'])->name('citations');
        Route::get('vehicles', fn () => view('owner.vehicles'))->name('vehicles');
        Route::get('clamping', [OwnerPortalController::class, 'clamping'])->name('clamping');
    });
});
