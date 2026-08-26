<?php

namespace App\Http\Controllers\Invue\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invue\Admin\PostRequest;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Invue\Notifications\Notification;
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
        $post = Post::create($request->validated());

        Notification::make()
            ->title('Post criado')
            ->body("\"{$post->title}\" foi salvo.")
            ->success()
            ->send();

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

        Notification::make()
            ->title('Post atualizado')
            ->body("\"{$post->title}\" foi salvo.")
            ->success()
            ->send();

        return to_route('invue.admin.posts.index');
    }

    public function destroy(Post $post): RedirectResponse
    {
        $post->delete();

        Notification::make()
            ->title('Post removido')
            ->color('gray')
            ->send();

        return to_route('invue.admin.posts.index');
    }
}
