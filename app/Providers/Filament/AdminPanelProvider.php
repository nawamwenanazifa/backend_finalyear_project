<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;

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
                'primary' => Color::hex('#800020'), // Fyn Bridals Maroon
                'secondary' => Color::hex('#D4AF37'), // Gold accent
            ])
            ->brandName('Fyn Bridals Admin')
            ->favicon(asset('favicon.ico'))
            ->renderHook(
                'styles.before',
                fn (): string => '<style>
                    body, .fi-main {
                        background-color: #FBF9F5 !important;
                    }
                    .fi-sidebar {
                        background-color: #FFFFFF !important;
                        border-right: 1px solid #E5E5E5 !important;
                    }
                    .fi-card {
                        background-color: #FFFFFF !important;
                        border-radius: 12px !important;
                    }
                    .fi-topbar {
                        background-color: #FFFFFF !important;
                        border-bottom: 1px solid #E5E5E5 !important;
                    }
                </style>'
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \Filament\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                \Filament\Widgets\AccountWidget::class,
                \Filament\Widgets\FilamentInfoWidget::class,
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