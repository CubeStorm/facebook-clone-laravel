<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\FriendshipStatus;
use App\Http\Requests\Friendship\AcceptRequest;
use App\Http\Requests\Friendship\DestroyRequest;
use App\Http\Requests\Friendship\InviteRequest;
use App\Http\Requests\Friendship\RejectRequest;
use App\Http\Resources\UserResource;
use App\Models\Friendship;
use App\Models\User;
use App\Notifications\FriendshipRequestAccepted;
use App\Notifications\FriendshipRequestSent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FriendshipController extends Controller
{
    public function friends(User $user): JsonResponse
    {
        $user->load(['invitedFriends', 'invitedByFriends']);

        $friends = collect([
            ...$user->invitedFriends,
            ...$user->invitedByFriends,
        ])->paginate(10);

        return response()->json(UserResource::collection($friends));
    }

    public function suggests(Request $request): JsonResponse
    {
        $user = $request->user();

        $users = User::whereNotIn('id', [
            $user->id,
            ...$user->invitedFriends->pluck('id'),
            ...$user->invitedByFriends->pluck('id'),
            ...$user->receivedInvites->pluck('id'),
            ...$user->sendedInvites->pluck('id'),
            ...$user->receivedBlocks->pluck('id'),
            ...$user->sendedBlocks->pluck('id'),
        ])->paginate(10);

        return response()->json(UserResource::collection($users));
    }

    public function invites(Request $request): JsonResponse
    {
        $user = $request->user()->load('receivedInvites');
        $users = $user->receivedInvites;

        return response()->json(UserResource::collection($users->paginate(10)));
    }

    public function invite(InviteRequest $request): JsonResponse
    {
        $data = $request->validated();
        $friend = User::findOrFail($data['friend_id']);
        $userId = $request->user()->id;

        Friendship::create([
            'user_id' => $userId,
            'friend_id' => $friend->id,
            'status' => FriendshipStatus::PENDING,
        ]);

        $friend->notify(new FriendshipRequestSent($userId));

        return response()->json(new UserResource($friend), 201);
    }

    public function accept(AcceptRequest $request): JsonResponse
    {
        $data = $request->validated();
        $friend = User::findOrFail($data['friend_id']);
        $userId = $request->user()->id;

        Friendship::where([
            ['user_id', $friend->id],
            ['friend_id', $userId],
        ])->update([
            'status' => FriendshipStatus::CONFIRMED,
        ]);

        $friend->notify(new FriendshipRequestAccepted($userId));

        return response()->json(new UserResource($friend));
    }

    public function reject(RejectRequest $request): JsonResponse
    {
        $data = $request->validated();
        $friend = User::findOrFail($data['friend_id']);

        Friendship::where([
            ['user_id', $friend->id],
            ['friend_id', $request->user()->id],
        ])->update([
            'status' => FriendshipStatus::BLOCKED,
        ]);

        return response()->json(new UserResource($friend));
    }

    public function destroy(DestroyRequest $request): JsonResponse
    {
        $data = $request->validated();
        $friend = User::findOrFail($data['friend_id']);
        $userId = $request->user()->id;

        Friendship::query()
            ->relation($userId, $friend->id)
            ->where('status', FriendshipStatus::CONFIRMED)
            ->firstOrFail()
            ->delete();

        return response()->json(new UserResource($friend));
    }
}