<?php

namespace Invue\Infolists;

use Illuminate\Support\ServiceProvider;

/**
 * No PHP-side builder, no backend query helper — unlike invue/tables'
 * TableQuery, a single already-fetched record needs no pagination/
 * search/sort whitelisting, so there's nothing here for PHP to own.
 * `Inertia::render('Posts/Show', ['post' => $post])` is the entire
 * backend; the Vue page composes Infolist/Entry/Section around
 * invue/tables' existing column components. See the package README.
 */
class InfolistsServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void {}
}
