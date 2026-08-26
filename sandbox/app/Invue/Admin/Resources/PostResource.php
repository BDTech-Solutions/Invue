<?php

namespace App\Invue\Admin\Resources;

use App\Models\Post;
use Invue\Panels\Resource;

class PostResource extends Resource
{
    protected static string $model = Post::class;
}
