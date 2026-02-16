<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @php
        $isAdmin  = Auth::guard('web')->check();
        $isClient = Auth::guard('kas_client')->check();
        $mode     = $isAdmin ? 'ADMIN' : ($isClient ? 'KAS Client' : '');
    @endphp
    <title>{{ $mode ? $mode . ' | ' : '' }}RIIID KAS Manager</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/uikit@3.25.11/dist/css/uikit.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/uikit@3.25.11/dist/js/uikit.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/uikit@3.25.11/dist/js/uikit-icons.min.js"></script>
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
</head>
<body>

<header class="uk-background-muted" uk-sticky>
    <div class="uk-container uk-container-expand" style="padding-left:45px; padding-right:45px;">
        <nav class="uk-navbar-container uk-navbar-transparent" uk-navbar>

            <div class="uk-navbar-left">
                {{-- Logo --}}
                @php
                    use App\Models\AppSetting;
                    $logoUrl  = AppSetting::getValue('logo_url', 'https://www.r3d.de/images/svg/r3d-logo_green_ng.svg');
                    $siteName = AppSetting::getValue('site_name', 'R3D KAS Manager');
                @endphp

                <a href="/" class="uk-logo uk-padding-small uk-padding-remove-horizontal">
                    <img src="{{ $logoUrl }}" alt="{{ $siteName }}" height="32">
                </a>

                {{-- Role label (ADMIN / KAS Client) --}}
                @if($isAdmin)
                    <span class="uk-label uk-label-danger uk-margin-small-left">ADMIN</span>
                @elseif($isClient)
                    <span class="uk-label uk-label-success uk-margin-small-left">KAS Client</span>
                @endif
            </div>

            <div class="uk-navbar-right">
                {{-- Desktop search --}}
                <div class="uk-visible@m">
                    <form class="uk-search uk-search-default uk-margin-right uk-margin-large-right" style="font-size: 0.85rem;">
                        <span uk-search-icon></span>
                        <input class="uk-search-input" type="search" placeholder="Suche...">
                    </form>

                    {{-- Back to Admin --}}
                    @if(session('impersonate') && Auth::guard('kas_client')->check())
                        <form action="{{ route('kas-clients.impersonate.leave') }}" method="POST" class="uk-display-inline">
                            @csrf
                            <button type="submit" class="uk-button uk-button-danger uk-button-small uk-margin-small-right">
                                ← Zurück zum Admin-Panel
                            </button>
                        </form>
                    @endif

                    {{-- Logout --}}
                    <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                        @csrf
                        <button type="submit" class="uk-button uk-button-text">Abmelden</button>
                    </form>
                </div>

                {{-- Burger toggle for mobile --}}
                <a class="uk-navbar-toggle uk-hidden@m" href="#offcanvas-nav" uk-toggle>
                    <span uk-navbar-toggle-icon></span>
                </a>
            </div>
        </nav>
    </div>
</header>

{{-- Offcanvas for mobile --}}
<div id="offcanvas-nav" uk-offcanvas="overlay: true">
            <div class="uk-offcanvas-bar">
                <ul class="uk-nav uk-nav-default">
                    @if(Auth::guard('kas_client')->check())
                        <li><a href="{{ route('client.dashboard') }}"><span uk-icon="home" class="uk-margin-small-right"></span>Dashboard</a></li>
                        <li><a href="{{ route('client.domains.index') }}"><span uk-icon="world" class="uk-margin-small-right"></span>Domain</a></li>
                        <li><a href="{{ route('client.subdomains.index') }}"><span uk-icon="grid" class="uk-margin-small-right"></span>Subdomain</a></li>
                        <li><a href="{{ route('client.mailboxes.index') }}"><span uk-icon="mail" class="uk-margin-small-right"></span>E-Mail-Postfach</a></li>
                        <li><a href="{{ route('client.mailforwards.index') }}"><span uk-icon="reply" class="uk-margin-small-right"></span>E-Mail-Weiterleitung</a></li>
                        <li><a href="{{ route('client.ftp.index') }}"><span uk-icon="folder" class="uk-margin-small-right"></span>FTP</a></li>
                        <li><a href="{{ route('client.databases.index') }}"><span uk-icon="database" class="uk-margin-small-right"></span>Datenbanken</a></li>
                        <li><a href="{{ route('client.dns.index') }}"><span uk-icon="settings" class="uk-margin-small-right"></span>DNS-Einstellungen</a></li>
                        <li><a href="{{ route('client.ssl.index') }}"><span uk-icon="lock" class="uk-margin-small-right"></span>SSL-Schutz</a></li>
                        <li><a href="{{ route('client.statistics.index') }}"><span uk-icon="bar-chart" class="uk-margin-small-right"></span>Statistik</a></li>
                        <li><a href="{{ route('client.recipes.index') }}"><span uk-icon="nut" class="uk-margin-small-right"></span>Rezepte</a></li>
                    @elseif(Auth::guard('web')->check())
                        <li><a href="{{ route('dashboard') }}"><span uk-icon="home" class="uk-margin-small-right"></span>Startseite</a></li>
                        <li><a href="{{ route('kas-clients.index') }}"><span uk-icon="thumbnails" class="uk-margin-small-right"></span>Accounts</a></li>
                <li><a href="{{ route('admin.mailboxes.index') }}"><span uk-icon="mail" class="uk-margin-small-right"></span>Mailkonten</a></li>
                <li><a href="{{ route('admin.mailforwards.index') }}"><span uk-icon="reply" class="uk-margin-small-right"></span>Weiterleitungen</a></li>
                <li><a href="{{ route('docs') }}"><span uk-icon="file-text" class="uk-margin-small-right"></span>Doku</a></li>
                <li><a href="{{ route('stats') }}"><span uk-icon="grid" class="uk-margin-small-right"></span>Stats</a></li>
                <li class="uk-nav-divider"></li>
                <li><a href="{{ route('users.index') }}"><span uk-icon="users" class="uk-margin-small-right"></span>User Management</a></li>
                <li><a href="{{ route('config.index') }}"><span uk-icon="settings" class="uk-margin-small-right"></span>Einstellungen</a></li>
            @endif

            @if(session('impersonate') && Auth::guard('kas_client')->check())
                <li class="uk-nav-divider"></li>
                <li>
                    <form action="{{ route('kas-clients.impersonate.leave') }}" method="POST">
                        @csrf
                        <button type="submit" class="uk-button uk-button-danger uk-button-small uk-width-1-1">
                            ← Zurück zum Admin-Panel
                        </button>
                    </form>
                </li>
            @endif

            <li class="uk-nav-divider"></li>
            <li>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="uk-button uk-button-text uk-text-danger">Abmelden</button>
                </form>
            </li>
        </ul>
    </div>
</div>

<main class="uk-section uk-section-default">
    <div class="uk-container uk-container-expand" style="padding-left:45px; padding-right:45px;">
        <div class="uk-grid-large" uk-grid>
            <aside class="uk-width-1-6@m uk-visible@m uk-border-right">
                <ul class="uk-nav uk-nav-default">
                    @if(Auth::guard('kas_client')->check())
                        <li><a href="{{ route('client.dashboard') }}"><span uk-icon="home" class="uk-margin-small-right"></span>Dashboard</a></li>
                        <li><a href="{{ route('client.domains.index') }}"><span uk-icon="world" class="uk-margin-small-right"></span>Domain</a></li>
                        <li><a href="{{ route('client.subdomains.index') }}"><span uk-icon="grid" class="uk-margin-small-right"></span>Subdomain</a></li>
                        <li><a href="{{ route('client.mailboxes.index') }}"><span uk-icon="mail" class="uk-margin-small-right"></span>E-Mail-Postfach</a></li>
                        <li><a href="{{ route('client.mailforwards.index') }}"><span uk-icon="reply" class="uk-margin-small-right"></span>E-Mail-Weiterleitung</a></li>
                        <li><a href="{{ route('client.ftp.index') }}"><span uk-icon="folder" class="uk-margin-small-right"></span>FTP</a></li>
                        <li><a href="{{ route('client.databases.index') }}"><span uk-icon="database" class="uk-margin-small-right"></span>Datenbanken</a></li>
                        <li><a href="{{ route('client.dns.index') }}"><span uk-icon="settings" class="uk-margin-small-right"></span>DNS-Einstellungen</a></li>
                        <li><a href="{{ route('client.ssl.index') }}"><span uk-icon="lock" class="uk-margin-small-right"></span>SSL-Schutz</a></li>
                        <li><a href="{{ route('client.statistics.index') }}"><span uk-icon="bar-chart" class="uk-margin-small-right"></span>Statistik</a></li>
                        <li><a href="{{ route('client.recipes.index') }}"><span uk-icon="nut" class="uk-margin-small-right"></span>Rezepte</a></li>
                    @elseif(Auth::guard('web')->check())
                        <li><a href="{{ route('dashboard') }}"><span uk-icon="home" class="uk-margin-small-right"></span>Startseite</a></li>
                        <li><a href="{{ route('kas-clients.index') }}"><span uk-icon="thumbnails" class="uk-margin-small-right"></span>Accounts</a></li>
                        <li><a href="{{ route('admin.mailboxes.index') }}"><span uk-icon="mail" class="uk-margin-small-right"></span>Mailkonten</a></li>
                        <li><a href="{{ route('admin.mailforwards.index') }}"><span uk-icon="reply" class="uk-margin-small-right"></span>Weiterleitungen</a></li>
                        <li><a href="{{ route('docs') }}"><span uk-icon="file-text" class="uk-margin-small-right"></span>Doku</a></li>
                        <li><a href="{{ route('stats') }}"><span uk-icon="grid" class="uk-margin-small-right"></span>Stats</a></li>
                        <li class="uk-nav-divider"></li>
                        <li><a href="{{ route('users.index') }}"><span uk-icon="users" class="uk-margin-small-right"></span>User Management</a></li>
                        <li><a href="{{ route('config.index') }}"><span uk-icon="settings" class="uk-margin-small-right"></span>Einstellungen</a></li>
                    @endif
                </ul>
            </aside>

            <section id="content" class="uk-width-expand uk-padding-remove-left">
                @yield('content')
            </section>

            <aside class="uk-width-1-6@m uk-visible@m uk-border-left uk-padding-small">
                <h4 class="uk-heading-line"><span>Hinweise</span></h4>
                @hasSection('hints')
                    @yield('hints')
                @else
                    @include('partials.hints')
                @endif
            </aside>
        </div>
    </div>
</main>

<footer class="uk-background-muted uk-padding-small uk-text-center" style="padding-left:45px; padding-right:45px;">
    <p>© 2025 R3D Internet Dienstleistungen · <a href="#">Impressum</a> · <a href="#">Datenschutz</a></p>
</footer>

</body>
</html>
