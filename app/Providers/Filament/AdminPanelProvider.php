<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use App\Filament\Pages\Chat;

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
                'panels::head.end',
                fn (): string => <<<HTML
                <style>
                    /* =============================================
                       HIDE LOGO ONLY ON LOGIN PAGE
                    ============================================= */
                    .fi-simple-layout .fi-logo,
                    .fi-simple-layout img,
                    .fi-simple-layout .fi-brand-logo {
                        display: none !important;
                    }

                    /* =============================================
                       SIDEBAR LOGO - KEEP VISIBLE
                    ============================================= */
                    .fi-sidebar-header img {
                        border-radius: 50% !important;
                        width: 52px !important;
                        height: 52px !important;
                        object-fit: cover !important;
                        border: 2px solid #D4AF37 !important;
                        padding: 2px !important;
                        background: white !important;
                    }

                    /* =============================================
                       SIDEBAR — SCROLLABLE
                    ============================================= */
                    .fi-sidebar {
                        background: #ffffff !important;
                        border-right: 1px solid #f0ebe3 !important;
                        display: flex !important;
                        flex-direction: column !important;
                        height: 100vh !important;
                        overflow: hidden !important;
                    }

                    .fi-sidebar-header {
                        background: #ffffff !important;
                        border-bottom: 1px solid #f0ebe3 !important;
                        flex-shrink: 0 !important;
                        padding: 16px !important;
                    }

                    .fi-sidebar-nav {
                        flex: 1 1 0% !important;
                        overflow-y: auto !important;
                        overflow-x: hidden !important;
                        background: #ffffff !important;
                        scrollbar-width: thin !important;
                        scrollbar-color: #ddd transparent !important;
                        padding-bottom: 20px !important;
                    }

                    .fi-sidebar-nav::-webkit-scrollbar { width: 4px !important; }
                    .fi-sidebar-nav::-webkit-scrollbar-track { background: transparent !important; }
                    .fi-sidebar-nav::-webkit-scrollbar-thumb {
                        background: #ddd !important;
                        border-radius: 4px !important;
                    }
                    .fi-sidebar-nav::-webkit-scrollbar-thumb:hover {
                        background: #bbb !important;
                    }

                    /* Sidebar items */
                    .fi-sidebar-item a,
                    .fi-sidebar-item button {
                        color: #4a4a4a !important;
                        border-radius: 8px !important;
                        margin: 2px 12px !important;
                    }

                    .fi-sidebar-item a:hover {
                        background: #fdf5f7 !important;
                        color: #570013 !important;
                    }

                    .fi-sidebar-item-active > a,
                    .fi-sidebar-item a[aria-current="page"] {
                        background: #570013 !important;
                        color: #ffffff !important;
                        font-weight: 600 !important;
                    }

                    .fi-sidebar-item a svg { color: #888 !important; }
                    .fi-sidebar-item-active > a svg,
                    .fi-sidebar-item a[aria-current="page"] svg { color: #fff !important; }

                    /* =============================================
                       TOP BAR — Search and User
                    ============================================= */
                    .fi-topbar {
                        background: #ffffff !important;
                        border-bottom: 1px solid #f0ebe3 !important;
                        box-shadow: 0 1px 4px rgba(0,0,0,0.06) !important;
                        padding: 12px 24px !important;
                    }

                    /* Search bar styling */
                    .fi-global-search {
                        width: 320px !important;
                    }
                    
                    .fi-global-search input {
                        border-radius: 24px !important;
                        background: #f5f5f5 !important;
                        border: 1px solid #e0e0e0 !important;
                        padding: 8px 16px !important;
                        font-size: 14px !important;
                    }
                    
                    .fi-global-search input:focus {
                        border-color: #570013 !important;
                        outline: none !important;
                        box-shadow: 0 0 0 2px rgba(87, 0, 19, 0.1) !important;
                    }

                    .fi-global-search svg {
                        color: #570013 !important;
                    }

                    /* User menu styling */
                    .fi-user-menu {
                        display: flex !important;
                        align-items: center !important;
                        gap: 12px !important;
                    }
                    
                    /* User Initials Avatar */
                    .fi-user-initials {
                        width: 38px !important;
                        height: 38px !important;
                        border-radius: 50% !important;
                        background: #570013 !important;
                        display: flex !important;
                        align-items: center !important;
                        justify-content: center !important;
                        color: white !important;
                        font-weight: 700 !important;
                        font-size: 15px !important;
                        cursor: pointer !important;
                        transition: transform 0.2s ease !important;
                        box-shadow: 0 2px 5px rgba(0,0,0,0.1) !important;
                    }
                    
                    .fi-user-initials:hover {
                        transform: scale(1.05) !important;
                        background: #800020 !important;
                    }
                    
                    /* Hide default filament avatar */
                    .fi-avatar {
                        display: none !important;
                    }

                    /* =============================================
                       MAIN CONTENT
                    ============================================= */
                    .fi-main, main, body { 
                        background: #FBF9F5 !important; 
                    }

                    h1.fi-header-heading {
                        color: #570013 !important;
                        font-weight: 700 !important;
                    }

                    /* Cards */
                    .fi-card, [class*="fi-wi-"] {
                        background: white !important;
                        border-radius: 16px !important;
                        border: 1px solid #f0ebe3 !important;
                        box-shadow: 0 2px 8px rgba(0,0,0,0.05) !important;
                    }

                    /* Table Header */
                    th.fi-ta-header-cell { background: #570013 !important; }
                    th.fi-ta-header-cell span,
                    th.fi-ta-header-cell button { color: white !important; }
                    .fi-ta-row:hover td { background: #fdf5f7 !important; }

                    /* Buttons */
                    .fi-btn-color-primary {
                        background: #570013 !important;
                        border: none !important;
                        border-radius: 8px !important;
                    }
                    .fi-btn-color-primary:hover { background: #800020 !important; }

                    /* Hide Filament Branding */
                    .fi-wi-filament-info-widget { display: none !important; }
                </style>
                HTML
            )
            ->renderHook(
                'panels::body.end',
                fn (): string => <<<'SCRIPT'
                <script>
                (function () {
                    function getUserInitials() {
                        // Try to get user name from multiple sources
                        var userName = '';
                        
                        // Look for user name in various places
                        var nameElements = document.querySelectorAll('.fi-user-menu span, .fi-user-menu .text-sm, [class*="user-name"]');
                        for (var i = 0; i < nameElements.length; i++) {
                            var text = nameElements[i].textContent.trim();
                            if (text && text !== 'User' && text !== 'Account' && text.length > 1 && text.length < 50) {
                                userName = text;
                                break;
                            }
                        }
                        
                        if (!userName) {
                            // Try to get from welcome message
                            var welcomeText = document.body.innerText;
                            var match = welcomeText.match(/Welcome\s+([A-Za-z\s]+?)(?:\n|$)/i);
                            if (match && match[1]) {
                                userName = match[1].trim();
                            }
                        }
                        
                        if (userName && userName !== 'User' && userName !== '') {
                            var words = userName.split(/\s+/).filter(Boolean);
                            var initials = '';
                            if (words.length >= 2) {
                                initials = (words[0][0] + words[1][0]).toUpperCase();
                            } else if (words[0] && words[0].length >= 2) {
                                initials = words[0].substring(0, 2).toUpperCase();
                            } else if (words[0]) {
                                initials = words[0][0].toUpperCase();
                            } else {
                                initials = 'U';
                            }
                            return { initials: initials, name: userName };
                        }
                        return null;
                    }
                    
                    function createInitialsAvatar() {
                        var userData = getUserInitials();
                        if (!userData) return;
                        
                        var userMenu = document.querySelector('.fi-user-menu');
                        if (!userMenu) return;
                        
                        // Check if already added
                        if (userMenu.querySelector('.custom-initials-avatar')) return;
                        
                        // Hide existing avatar
                        var existingAvatar = userMenu.querySelector('.fi-avatar, .fi-avatar-wrapper');
                        if (existingAvatar) {
                            existingAvatar.style.display = 'none';
                        }
                        
                        // Create new avatar with initials
                        var avatarDiv = document.createElement('div');
                        avatarDiv.className = 'custom-initials-avatar fi-user-initials';
                        avatarDiv.textContent = userData.initials;
                        avatarDiv.setAttribute('title', userData.name);
                        
                        // Insert at the beginning of user menu
                        userMenu.insertBefore(avatarDiv, userMenu.firstChild);
                    }
                    
                    function run() {
                        setTimeout(createInitialsAvatar, 200);
                        setTimeout(createInitialsAvatar, 500);
                        setTimeout(createInitialsAvatar, 1000);
                    }
                    
                    if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', run);
                    } else {
                        run();
                    }
                    
                    document.addEventListener('livewire:navigated', run);
                    if (typeof Livewire !== 'undefined') {
                        Livewire.hook('element.updated', function() {
                            setTimeout(createInitialsAvatar, 300);
                        });
                    }
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
            ->widgets([])
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