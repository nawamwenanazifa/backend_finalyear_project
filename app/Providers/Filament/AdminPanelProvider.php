<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use App\Filament\Pages\Chat;
use Filament\Navigation\NavigationItem;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::hex('#570013'),
            ])
            ->brandName('Fyn Bridals')
            ->brandLogo(fn () => asset('images/fyn-bridals-logo.png'))
            ->brandLogoHeight('3rem')
            ->favicon(asset('favicon.ico'))
            ->globalSearch(true)
            ->renderHook(
                'panels::head.end',
                fn (): string => <<<HTML
                <style>
                    /* =============================================
                       HIDE LOGO ON LOGIN PAGE
                    ============================================= */
                    .fi-simple-layout .fi-logo,
                    .fi-simple-layout img,
                    .fi-simple-layout .fi-brand-logo {
                        display: none !important;
                    }

                    /* =============================================
                       CIRCULAR LOGO IN SIDEBAR
                    ============================================= */
                    .fi-sidebar-header img,
                    .fi-sidebar img {
                        border-radius: 50% !important;
                        width: 52px !important;
                        height: 52px !important;
                        object-fit: cover !important;
                        border: 2px solid #570013 !important;
                        padding: 2px !important;
                        background: white !important;
                    }

                    /* =============================================
                       SIDEBAR — white, scrollable
                    ============================================= */
                    .fi-sidebar, aside {
                        background: #ffffff !important;
                        border-right: 1px solid #f0ebe3 !important;
                        overflow-y: auto !important;
                        height: 100vh !important;
                    }

                    .fi-sidebar-header {
                        background: #ffffff !important;
                        border-bottom: 1px solid #f0ebe3 !important;
                        position: sticky !important;
                        top: 0 !important;
                        z-index: 10 !important;
                    }

                    .fi-sidebar-nav {
                        background: #ffffff !important;
                        overflow-y: auto !important;
                        scrollbar-width: thin !important;
                        scrollbar-color: #e0d8d0 transparent !important;
                    }

                    .fi-sidebar-nav::-webkit-scrollbar { width: 4px; }
                    .fi-sidebar-nav::-webkit-scrollbar-thumb {
                        background: #e0d8d0;
                        border-radius: 4px;
                    }

                    /* Sidebar items */
                    .fi-sidebar-item a,
                    .fi-sidebar-item button {
                        color: #4a4a4a !important;
                        border-radius: 10px !important;
                        margin: 2px 8px !important;
                    }

                    .fi-sidebar-item a:hover {
                        background: #fdf5f7 !important;
                        color: #570013 !important;
                    }

                    .fi-sidebar-item-active > a,
                    .fi-sidebar-item a[aria-current="page"] {
                        background: #fdf0f2 !important;
                        color: #570013 !important;
                        border-left: 3px solid #570013 !important;
                        font-weight: 600 !important;
                    }

                    .fi-sidebar-item a svg { color: #888 !important; }
                    .fi-sidebar-item-active > a svg,
                    .fi-sidebar-item a[aria-current="page"] svg {
                        color: #570013 !important;
                    }

                    /* =============================================
                       TOP BAR — white
                    ============================================= */
                    header.fi-topbar, .fi-topbar {
                        background: #ffffff !important;
                        border-bottom: 1px solid #f0ebe3 !important;
                        box-shadow: 0 1px 4px rgba(0,0,0,0.06) !important;
                    }

                    .fi-topbar svg { color: #570013 !important; }

                    /* =============================================
                       PAGE HEADING — hide default "Dashboard"
                    ============================================= */
                    h1.fi-header-heading {
                        display: none !important;
                    }

                    /* =============================================
                       MAIN BACKGROUND
                    ============================================= */
                    .fi-main, main, body {
                        background: #FBF9F5 !important;
                    }

                    /* =============================================
                       CARDS
                    ============================================= */
                    .fi-card, [class*="fi-wi-"] {
                        background: white !important;
                        border-radius: 16px !important;
                        border: 1px solid #f0ebe3 !important;
                        box-shadow: 0 2px 8px rgba(0,0,0,0.05) !important;
                    }

                    /* =============================================
                       AVATAR — hide broken image, show initials via CSS
                    ============================================= */
                    .fi-avatar {
                        position: relative !important;
                        border-radius: 50% !important;
                        overflow: visible !important;
                        background: #570013 !important;
                        width: 40px !important;
                        height: 40px !important;
                        min-width: 40px !important;
                        display: flex !important;
                        align-items: center !important;
                        justify-content: center !important;
                    }

                    .fi-avatar img {
                        display: none !important;
                    }

                    /* =============================================
                       TABLE
                    ============================================= */
                    th.fi-ta-header-cell {
                        background: #570013 !important;
                    }
                    th.fi-ta-header-cell span,
                    th.fi-ta-header-cell button { color: white !important; }
                    .fi-ta-row:hover td { background: #fdf5f7 !important; }

                    /* =============================================
                       BUTTONS
                    ============================================= */
                    .fi-btn-color-primary {
                        background: #570013 !important;
                        border: none !important;
                        border-radius: 8px !important;
                    }
                    .fi-btn-color-primary:hover { background: #800020 !important; }

                    /* =============================================
                       HIDE FILAMENT BRANDING
                    ============================================= */
                    .fi-wi-filament-info-widget { display: none !important; }

                    /* =============================================
                       SEARCH BAR STYLING
                    ============================================= */
                    .fi-global-search-field input {
                        background: #f5f0eb !important;
                        border-radius: 24px !important;
                        border: 1px solid #e8e0d8 !important;
                    }
                </style>
                HTML
            )
            ->renderHook(
                'panels::body.end',
                fn (): string => <<<'SCRIPT'
                <script>
                    (function() {
                        function applyAll() {
                            applyInitials();
                            replaceHeading();
                        }

                        // ── Initials Avatar ──────────────────────────────
                        function applyInitials() {
                            document.querySelectorAll('.fi-avatar').forEach(function(avatar) {
                                if (avatar.querySelector('.fi-initials')) return;

                                var img = avatar.querySelector('img');
                                var name = img ? (img.alt || '') : '';

                                // Skip if name is empty or generic
                                if (!name || name === 'Avatar') return;

                                // Build initials
                                var parts = name.trim().split(/\s+/);
                                var initials = parts.length >= 2
                                    ? parts[0][0] + parts[1][0]
                                    : name.substring(0, 2);
                                initials = initials.toUpperCase();

                                // Style avatar circle
                                avatar.style.cssText = [
                                    'width:40px', 'height:40px', 'min-width:40px',
                                    'border-radius:50%', 'background:#570013',
                                    'display:flex', 'align-items:center',
                                    'justify-content:center', 'overflow:hidden',
                                    'border:none'
                                ].join(';');

                                // Hide image
                                if (img) img.style.display = 'none';

                                // Add initials text
                                var span = document.createElement('span');
                                span.className = 'fi-initials';
                                span.textContent = initials;
                                span.style.cssText = 'color:white;font-weight:700;font-size:14px;font-family:sans-serif;letter-spacing:0.5px;line-height:1;';
                                avatar.appendChild(span);
                            });
                        }

                        // ── Replace "Dashboard" heading ──────────────────
                        function replaceHeading() {
                            document.querySelectorAll('h1').forEach(function(el) {
                                if (el.textContent.trim() === 'Dashboard') {
                                    el.textContent = 'Fyn Bridals';
                                    el.style.cssText = 'color:#570013;font-weight:700;display:block;font-size:1.75rem;';
                                }
                            });
                        }

                        // Run on load
                        if (document.readyState === 'loading') {
                            document.addEventListener('DOMContentLoaded', applyAll);
                        } else {
                            applyAll();
                        }

                        // Run after every Livewire page navigation
                        document.addEventListener('livewire:navigated', applyAll);
                        document.addEventListener('livewire:load', applyAll);

                        // MutationObserver for dynamic content
                        var debounce;
                        new MutationObserver(function() {
                            clearTimeout(debounce);
                            debounce = setTimeout(applyAll, 100);
                        }).observe(document.body, { childList: true, subtree: true });
                    })();
                </script>
                SCRIPT
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \Filament\Pages\Dashboard::class,
                Chat::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                \Filament\Widgets\AccountWidget::class,
            ])
            ->middleware([
                \Illuminate\Cookie\Middleware\EncryptCookies::class,
                \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
                \Illuminate\Session\Middleware\StartSession::class,
                \Illuminate\View\Middleware\ShareErrorsFromSession::class,
                \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
                \Illuminate\Routing\Middleware\SubstituteBindings::class,
            ])
            ->authMiddleware([
                \Illuminate\Auth\Middleware\Authenticate::class,
            ]);
    }
}