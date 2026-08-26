<?php

namespace App\Providers;

use Invue\Panels\Panel;
use Invue\Panels\PanelProvider;

class AdminPanelProvider extends PanelProvider
{
    public function panel(): Panel
    {
        return Panel::make('admin')
            ->path('admin')
            ->middleware(['web', 'auth']);
    }
}
