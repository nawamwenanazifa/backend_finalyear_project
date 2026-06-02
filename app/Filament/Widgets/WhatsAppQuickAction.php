<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class WhatsAppQuickAction extends Widget
{
    protected static string $view = 'filament.widgets.whatsapp-quick-action';
    protected int | string | array $columnSpan = 1;
    
    public function getAdminWhatsAppNumber(): string
    {
        return '0788967418';
    }
}