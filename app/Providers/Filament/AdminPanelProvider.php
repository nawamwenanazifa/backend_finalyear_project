<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use App\Filament\Widgets\NotificationWidget;
use App\Filament\Widgets\SalesStats;
use App\Filament\Widgets\StockAlert;

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
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->renderHook(
                'panels::sidebar.nav.before',
                fn (): string => '<div class="px-3 py-2">
                    <a href="https://wa.me/256788967418?text=' . urlencode('Hello! I am from Fyn Bridals. How can I help you?') . '" 
                       target="_blank"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg bg-green-500 text-white hover:bg-green-600 transition">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12.032 2.002c-5.52 0-10 4.48-10 10 0 1.826.498 3.59 1.45 5.106L2 22.002l5.026-1.422c1.447.83 3.106 1.272 4.832 1.272 5.52 0 10-4.48 10-10s-4.48-10-10-10z"/>
                        </svg>
                        <span class="text-sm font-medium">WhatsApp Support</span>
                    </a>
                </div>'
            )
            ->renderHook(
                'panels::head.end',
                fn (): string => <<<HTML
                <style>
                    .fi-sidebar-header {
                        display: flex !important;
                        align-items: center !important;
                        gap: 12px !important;
                        background: #ffffff !important;
                        border-bottom: 1px solid #f0ebe3 !important;
                        padding: 16px !important;
                    }
                    .fi-sidebar-header img {
                        border-radius: 50% !important;
                        width: 52px !important;
                        height: 52px !important;
                        object-fit: cover !important;
                        border: 2px solid #D4AF37 !important;
                        padding: 2px !important;
                    }
                    .fi-sidebar-header::after {
                        content: "Fyn Bridals" !important;
                        font-size: 18px !important;
                        font-weight: bold !important;
                        color: #570013 !important;
                        font-family: Georgia, serif !important;
                        margin-left: 12px !important;
                    }
                    .fi-sidebar {
                        background: #ffffff !important;
                        border-right: 1px solid #f0ebe3 !important;
                    }
                    .fi-sidebar-nav {
                        overflow-y: auto !important;
                    }
                    
                    /* FORCE WHITE TEXT ON ACTIVE MENU ITEMS - MULTIPLE SELECTORS */
                    .fi-sidebar-item-active a,
                    .fi-sidebar-item-active button,
                    .fi-sidebar-item-active .fi-sidebar-item-label,
                    .fi-sidebar-item-active span,
                    .fi-sidebar-nav .fi-sidebar-item-active,
                    .fi-sidebar-nav .fi-sidebar-item-active .fi-sidebar-item-label,
                    .fi-sidebar-nav .fi-sidebar-item-active span {
                        background: #570013 !important;
                        color: #ffffff !important;
                        font-weight: 600 !important;
                    }
                    
                    /* Force all text inside active item to be white */
                    .fi-sidebar-item-active *,
                    .fi-sidebar-item-active a *,
                    .fi-sidebar-item-active button *,
                    .fi-sidebar-nav .fi-sidebar-item-active * {
                        color: #ffffff !important;
                    }
                    
                    /* Force icons to be white */
                    .fi-sidebar-item-active svg,
                    .fi-sidebar-nav .fi-sidebar-item-active svg {
                        color: #ffffff !important;
                        stroke: #ffffff !important;
                    }
                    
                    /* Unselected menu items hover */
                    .fi-sidebar-nav a:not(.fi-sidebar-item-active):hover,
                    .fi-sidebar-nav button:not(.fi-sidebar-item-active):hover {
                        background: #f0ebe3 !important;
                        color: #570013 !important;
                    }
                    
                    .fi-sidebar-nav a:not(.fi-sidebar-item-active):hover *,
                    .fi-sidebar-nav button:not(.fi-sidebar-item-active):hover * {
                        color: #570013 !important;
                    }
                    
                    /* Keep active item white on hover */
                    .fi-sidebar-item-active:hover,
                    .fi-sidebar-item-active:hover *,
                    .fi-sidebar-item-active a:hover,
                    .fi-sidebar-item-active a:hover * {
                        background: #570013 !important;
                        color: #ffffff !important;
                    }
                    
                    /* Main content background */
                    .fi-main, body { 
                        background: #FBF9F5 !important; 
                    }
                    .fi-card {
                        background: white !important;
                        border-radius: 16px !important;
                        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03) !important;
                    }

                    /* Login Page Specific Styling */
                    main.fi-simple-main {
                        background-color: #570013 !important;
                        background-image: radial-gradient(circle at center, #78001a 0%, #570013 100%) !important;
                    }
                    main.fi-simple-main .fi-card {
                        background-color: #ffffff !important;
                        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2) !important;
                        border: 1px solid rgba(255, 255, 255, 0.2) !important;
                        padding: 2rem !important;
                    }
                    /* Ensure text on login page labels is visible */
                    main.fi-simple-main .fi-card label,
                    main.fi-simple-main .fi-card h2 {
                        color: #570013 !important;
                        font-weight: 600 !important;
                    }
                    main.fi-simple-main .fi-logo {
                        filter: drop-shadow(0px 2px 4px rgba(0,0,0,0.5));
                    }
                    /* Form input fields on login page */
                    main.fi-simple-main .fi-input {
                        background-color: #f9f9f9 !important;
                        border: 1px solid #e0e0e0 !important;
                    }
                </style>
                HTML
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \Filament\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                NotificationWidget::class,
                SalesStats::class,
                StockAlert::class,
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