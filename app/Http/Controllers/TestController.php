<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Models\User;

class TestController extends Controller
{
    public function __invoke()
    {
        $user = User::firstWhere('last_name', 'Witas');
        
        $friendsId = collect([
            ...$user->invitedFriends->pluck('id'),
            ...$user->invitedByFriends->pluck('id')
        ]);

        $posts = Post::with('author:id,first_name,last_name,profile_image,background_image')
            ->whereIn('author_id', $friendsId)
            ->latest()
            ->paginate(15, [
                'id',
                'content',
                'author_id',
                'created_at',
                'updated_at'
            ]);

        return response()->json(PostResource::collection($posts));
    }
}