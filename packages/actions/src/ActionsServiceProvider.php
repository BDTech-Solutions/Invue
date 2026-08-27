<?php

namespace Invue\Actions;

use Illuminate\Support\ServiceProvider;

/**
 * No PHP-side builder on purpose: an Action is authored where every other
 * piece of UI is authored in Invue — the `.vue` page/column, as a plain
 * prop (`{ label, icon, color, url, method, ... }`), same "no PHP UI
 * builder" rule invue/forms and invue/tables already follow. What PHP
 * *does* own is the data an Action's `visible`/`url` reacts to — see
 * Invue\Tables\TableQuery::authorize().
 */
class ActionsServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void {}
}
