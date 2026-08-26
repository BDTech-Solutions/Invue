<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Invue\Tables\TableQuery;

class InvueTablesDemoController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('InvueTablesDemo', [
            'users' => TableQuery::for(User::query())
                ->searchable(['name', 'email'])
                ->sortable(['name', 'email', 'created_at'])
                ->filterable(['role', 'is_active'])
                ->defaultSort('created_at', 'desc')
                ->paginate($request),
        ]);
    }
}
