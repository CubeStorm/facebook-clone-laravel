<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Post\StoreRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\File;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $authorsId = collect([
            $user->id,
            ...$user->invitedFriends->pluck('id'),
            ...$user->invitedByFriends->pluck('id')
        ]);

        $posts = Post::with('author:id,first_name,last_name,profile_image,background_image')
            ->withCount([
                'likes',
                'likes as isLiked' => fn($query) => $query->where('user_id', $user->id)
            ])
            ->whereIn('author_id', $authorsId)
            ->latest()
            ->paginate(15, [
                'id',
                'content',
                'images',
                'author_id',
                'created_at',
                'updated_at'
            ]);

        return response()->json(PostResource::collection($posts));
    }

    public function store(StoreRequest $request): JsonResponse
    {
        $user = $request->user();

        $paths = [];

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('public/posts');
                
                $paths[] = str_replace('public', '', $path);
            }
        }

        $post = Post::create([
            'content' => $request->validated()['content'],
            'author_id' => $user->id,
            'images' => $paths
        ]);
        
        return response()->json([
            'data' => new PostResource($post),
            'message' => 'Post was created'
        ],);
    }
}