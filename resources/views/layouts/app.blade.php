<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('itevcms.app_name'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @stack('styles')
</head>
<body class="app-shell{{ ($isEnforcerMobile ?? false) ? ' enforcer-mobile' : '' }}">
<div class="d-flex" style="overflow-x:hidden;">
    <aside class="sidebar animate-on-load">
        <div class="sidebar-brand">
            <img src="{{ asset('images/transpo_enfo_orig.png') }}" alt="TEMs" class="sidebar-logo">
            <div class="sidebar-brand-title">TEMs</div>
            <div class="sidebar-brand-sub">Transportation Enforcement Management System</div>
        </div>

        <nav class="sidebar-nav">
            @foreach ($navGroups ?? [] as $group)
                <div class="sidebar-group">
                    <div class="sidebar-group-label">{{ $group['label'] }}</div>
                    @foreach ($group['items'] as $item)
                        @php
                            $isActive = request()->routeIs($item['route']) ||
                                        request()->routeIs(str_replace('.index', '.*', $item['route']));
                        @endphp
                        <a href="{{ route($item['route']) }}"
                           class="nav-link {{ $isActive ? 'active' : '' }}">
                            <i class="bi bi-{{ $item['icon'] }} sidebar-nav-icon"></i>
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            @endforeach
        </nav>
    </aside>

    <div class="main-content flex-grow-1 d-flex flex-column">
        @if (!($isEnforcerMobile ?? false))
        <nav class="navbar navbar-expand-lg bg-white border-bottom px-3 d-lg-none">
            <span class="navbar-brand mb-0 h6">{{ config('itevcms.app_name') }}</span>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mobileNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mobileNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    @foreach ($navGroups ?? [] as $group)
                        <li class="nav-item dropdown-header text-muted small text-uppercase fw-semibold px-3 py-1 mt-2">{{ $group['label'] }}</li>
                        @foreach ($group['items'] as $item)
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route($item['route']) }}">
                                    <i class="bi bi-{{ $item['icon'] }} me-2"></i>{{ $item['label'] }}
                                </a>
                            </li>
                        @endforeach
                    @endforeach
                </ul>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary btn-sm w-100 mt-2">Logout</button>
                </form>
            </div>
        </nav>
        @endif

        <header class="topbar px-4 px-lg-5 py-3 border-bottom animate-on-load">
            <div>
                <div class="text-muted small">Operations console</div>
                <h2 class="h4 mb-0">@yield('title', 'Dashboard')</h2>
            </div>
            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('notifications.index') }}" class="position-relative text-decoration-none text-dark topbar-icon-btn">
                    <i class="bi bi-bell fs-5"></i>
                    <span class="notification-badge position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notificationCount" style="font-size:0.6rem; display:none;">0</span>
                </a>

                <div class="dropdown">
                    <button class="btn btn-link text-dark text-decoration-none dropdown-toggle d-flex align-items-center gap-2 p-0 border-0" data-bs-toggle="dropdown" aria-expanded="false" id="userDropdown">
                        <span class="status-dot text-success"><i class="bi bi-circle-fill" style="font-size:0.6rem;"></i></span>
                        <span class="fw-semibold small d-none d-md-inline">{{ auth()->user()->name }}</span>
                        <span class="text-muted small d-none d-lg-inline">{{ auth()->user()->role->label() }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="userDropdown" style="min-width: 230px;">
                        <li>
                            <div class="dropdown-header text-wrap">
                                <div class="fw-semibold">{{ auth()->user()->name }}</div>
                                <small class="text-muted">{{ auth()->user()->email }}</small>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person-circle me-2"></i>My Profile</a></li>
                        <li><a class="dropdown-item" href="{{ route('settings.index') }}"><i class="bi bi-gear me-2"></i>Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Logout</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="p-4 p-lg-5 flex-grow-1 page-bg">
            @include('components.alerts')
            @yield('content')
        </main>
    </div>
</div>

@stack('scripts')

@if ($isEnforcerMobile ?? false)
<nav class="enforcer-tab-bar" aria-label="Enforcer quick nav">
  <a href="{{ route('dashboard') }}" class="tab-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
    <i class="bi bi-speedometer2"></i><span>Dashboard</span>
  </a>
  <a href="{{ route('citations.index') }}" class="tab-item {{ request()->routeIs('citations.*') ? 'active' : '' }}">
    <i class="bi bi-file-earmark-text"></i><span>Citations</span>
  </a>
  <a href="{{ route('citations.create') }}" class="tab-item tab-fab" aria-label="Issue Citation">
    <i class="bi bi-plus-lg"></i>
  </a>
  <a href="{{ route('enforcer.zone') }}" class="tab-item {{ request()->routeIs('enforcer.zone') ? 'active' : '' }}">
    <i class="bi bi-geo-alt-fill"></i><span>My Zone</span>
  </a>
  <button class="tab-item" type="button" id="enforcerMoreBtn" aria-expanded="false" aria-controls="enforcerMoreMenu">
    <i class="bi bi-three-dots"></i><span>More</span>
  </button>
</nav>
<div class="enforcer-more-backdrop" id="enforcerMoreBackdrop"></div>
<div class="enforcer-more-sheet" id="enforcerMoreMenu" role="dialog" aria-label="More options">
  <div class="enforcer-more-header">
    <span class="fw-semibold">More</span>
    <button type="button" class="enforcer-more-close" id="enforcerMoreClose" aria-label="Close menu">
      <i class="bi bi-x-lg"></i>
    </button>
  </div>
  <div class="enforcer-more-grid">
    <a href="{{ route('appeals.index') }}" class="enforcer-more-item">
      <i class="bi bi-exclamation-circle"></i><span>Appeals</span>
    </a>
    <a href="{{ route('clamping-requests.index') }}" class="enforcer-more-item">
      <i class="bi bi-inbox"></i><span>Clamp Req</span>
    </a>
    <a href="{{ route('archives.index') }}" class="enforcer-more-item">
      <i class="bi bi-archive"></i><span>My Archives</span>
    </a>
    <a href="{{ route('profile.edit') }}" class="enforcer-more-item">
      <i class="bi bi-person-circle"></i><span>My Profile</span>
    </a>
    <a href="{{ route('notifications.index') }}" class="enforcer-more-item">
      <i class="bi bi-bell"></i><span>Notifications</span>
    </a>
    <a href="{{ route('settings.index') }}" class="enforcer-more-item">
      <i class="bi bi-gear"></i><span>Settings</span>
    </a>
    <form action="{{ route('logout') }}" method="POST" class="enforcer-more-item">
      @csrf
      <button type="submit" class="enforcer-more-btn text-danger"><i class="bi bi-box-arrow-right"></i><span>Logout</span></button>
    </form>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const moreBtn = document.getElementById('enforcerMoreBtn');
    const sheet = document.getElementById('enforcerMoreMenu');
    const backdrop = document.getElementById('enforcerMoreBackdrop');
    const closeBtn = document.getElementById('enforcerMoreClose');
    if (!moreBtn || !sheet || !backdrop) return;

    function setOpen(open) {
        sheet.classList.toggle('is-open', open);
        backdrop.classList.toggle('is-visible', open);
        moreBtn.classList.toggle('active', open);
        moreBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        moreBtn.querySelector('i').className = open ? 'bi bi-x-lg' : 'bi bi-three-dots';
    }

    moreBtn.addEventListener('click', () => setOpen(!sheet.classList.contains('is-open')));
    closeBtn?.addEventListener('click', () => setOpen(false));
    backdrop.addEventListener('click', () => setOpen(false));
    document.addEventListener('keydown', e => { if (e.key === 'Escape') setOpen(false); });
});
</script>
@endif

<style>
    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        width: 220px;
        min-width: 220px;
        max-width: 220px;
        height: 100vh;
        z-index: 1030;
        background: linear-gradient(160deg, var(--itevcms-primary) 0%, #0a1f35 100%);
        box-shadow: 20px 0 45px rgba(15, 23, 42, 0.15);
        display: flex !important;
        flex-direction: column !important;
        overflow: hidden;
    }

    .main-content {
        margin-left: 220px;
        min-height: 100vh;
        min-width: 0;
    }

    .min-width-0 {
        min-width: 0;
    }

    @media (max-width: 991.98px) {
        .main-content {
            margin-left: 0;
        }
    }

    .sidebar-brand {
        height: 190px;
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }

    .sidebar-logo {
        width: 100px;
        height: 100px;
        object-fit: contain;
        margin-bottom: 0.5rem;
    }

    .sidebar-brand-title {
        font-size: 1.1rem;
        font-weight: 800;
        color: #fff;
        letter-spacing: 0.04em;
        line-height: 1.2;
    }

    .sidebar-brand-sub {
        width: 140px;
        height: 36px;
        font-size: 0.65rem;
        line-height: 1.2;
        font-weight: 500;
        color: rgba(255,255,255,0.55);
        text-align: center;
        overflow: hidden;
        margin-top: 0.15rem;
    }

    .sidebar-nav {
        flex: 1 1 auto;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 0.5rem 0;
        display: block;
        min-height: 0;
    }

    .sidebar-group {
        margin-bottom: 0.25rem;
    }

    .sidebar-group-label {
        padding: 1rem 1rem 0.35rem;
        font-size: 0.65rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: rgba(255,255,255,0.35);
    }

    .sidebar-nav .nav-link {
        display: flex;
        align-items: center;
        white-space: nowrap;
        gap: 0.75rem;
        padding: 0.6rem 1rem;
        color: rgba(255,255,255,0.75);
        border-radius: 0;
        border-left: 3px solid transparent;
        transition: all 0.15s ease;
        font-size: 0.875rem;
        margin: 0 0.5rem;
        border-radius: 0.5rem;
    }

    .sidebar-nav .nav-link:hover {
        background: rgba(255,255,255,0.1);
        color: #fff;
    }

    .sidebar-nav .nav-link.active {
        background: rgba(255,255,255,0.14);
        color: #fff;
        border-left-color: #fff;
        font-weight: 600;
    }

    .sidebar-nav-icon {
        font-size: 1.1rem;
        width: 1.25rem;
        text-align: center;
        flex-shrink: 0;
    }

    .sidebar-nav::-webkit-scrollbar {
        width: 4px;
    }

    .sidebar-nav::-webkit-scrollbar-thumb {
        background: rgba(255,255,255,0.15);
        border-radius: 4px;
    }

    .topbar-icon-btn {
        transition: color 0.15s ease;
    }

    .topbar-icon-btn:hover {
        color: var(--itevcms-accent) !important;
    }

    #userDropdown:focus {
        box-shadow: none;
    }

    #userDropdown::after {
        font-size: 0.7rem;
        color: #999;
    }

    .dropdown-header {
        white-space: normal;
    }

    .topbar {
        position: relative;
        z-index: 1020;
        background: var(--itevcms-card);
    }

    .topbar .dropdown-menu {
        z-index: 1055;
    }

    .page-bg {
        background-color: var(--itevcms-surface);
    }

    .sticky-save {
        position: sticky;
        bottom: 0;
        z-index: 1025;
        background: #fff;
        border-top: 1px solid #e5e7eb;
        padding: 1rem 0;
        margin-top: 2rem;
    }

    .zone-map {
        width: 100%;
        height: 500px;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const badge = document.getElementById('notificationCount');
    if (!badge) return;

    function updateBadge() {
        fetch('{{ route("api.notifications.unread") }}')
            .then(r => r.json())
            .then(d => {
                if (d.count > 0) { badge.textContent = d.count; badge.style.display = 'inline'; }
                else { badge.style.display = 'none'; }
            })
            .catch(() => {});
    }

    updateBadge();
    setInterval(updateBadge, 30000);
});
</script>
</body>
</html>
