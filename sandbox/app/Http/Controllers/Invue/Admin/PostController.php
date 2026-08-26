<?php

namespace App\Http\Controllers\Invue\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invue\Admin\PostRequest;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Invue\Tables\TableQuery;

class PostController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Invue/Admin/Posts/Index', [
            'posts' => TableQuery::for(Post::query())
                ->searchable(['title', 'body'])
                ->sortable(['title'])
                ->defaultSort('created_at', 'desc')
                ->paginate($request),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Invue/Admin/Posts/Create');
    }

    public function store(PostRequest $request): RedirectResponse
    {
        Post::create($request->validated());

        return to_route('invue.admin.posts.index');
    }

    public function edit(Post $post): Response
    {
        return Inertia::render('Invue/Admin/Posts/Edit', [
            'post' => $post,
        ]);
    }

    public function update(PostRequest $request, Post $post): RedirectResponse
    {
        $post->update($request->validated());

        return to_route('invue.admin.posts.index');
    }

    public function destroy(Post $post): RedirectResponse
    {
        $post->delete();

        return to_route('invue.admin.posts.index');
    }
}
