<?php

declare(strict_types=1);

namespace Tests\Feature\Friendship;

use App\Enums\FriendshipStatus;
use App\Models\Friendship;
use App\Models\User;
use Tests\TestCase;

class FriendsTest extends TestCase
{
    private User $user;

    private string $friendsRoute;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->createOne();
        $this->friendsRoute = route('api.friendship.friends', $this->user->id);
    }

    public function testCannotUseWhenNotAuthorized(): void
    {
        $response = $this->getJson($this->friendsRoute);
        $response->assertUnauthorized();
    }

    public function testCanUseWhenAuthorized(): void
    {
        $response = $this->actingAs($this->user)->getJson($this->friendsRoute);
        $response->assertOk();
    }

    public function testReturnFriendsInvitedAndWhichSendInvites(): void
    {
        $users = User::factory(50)->create();

        Friendship::factory(4)
            ->create([
                'user_id' => $this->user->id,
                'friend_id' => fn () => $this->faker->unique->randomElement($users->pluck('id')),
                'status' => FriendshipStatus::CONFIRMED,
            ]);

        Friendship::factory(4)
            ->create([
                'user_id' => fn () => $this->faker->unique->randomElement($users->pluck('id')),
                'friend_id' => $this->user->id,
                'status' => FriendshipStatus::CONFIRMED,
            ]);

        $response = $this->actingAs($this->user)->getJson($this->friendsRoute);

        $response->assertOk()->assertJsonCount(8);
    }

    public function testReturnFriendsWhenUserHasOnlyInvitedFriends(): void
    {
        $users = User::factory(50)->create();

        Friendship::factory(9)
            ->create([
                'user_id' => $this->user->id,
                'friend_id' => fn () => $this->faker->unique->randomElement($users->pluck('id')),
                'status' => FriendshipStatus::CONFIRMED,
            ]);

        $response = $this->actingAs($this->user)->getJson($this->friendsRoute);

        $response->assertOk()->assertJsonCount(9);
    }

    public function testReturnFriendsWhenUserHasOnlyFriendsWhichInvite(): void
    {
        $users = User::factory(50)->create();

        Friendship::factory(4)
            ->create([
                'user_id' => fn () => $this->faker->unique->randomElement($users->pluck('id')),
                'friend_id' => $this->user->id,
                'status' => FriendshipStatus::CONFIRMED,
            ]);

        $response = $this->actingAs($this->user)->getJson($this->friendsRoute);

        $response->assertOk()->assertJsonCount(4);
    }
}
