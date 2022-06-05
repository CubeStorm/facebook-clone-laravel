<?php

namespace Tests\Feature\Friendship\Actions;

use App\Models\Friendship;
use App\Models\User;
use App\Notifications\FriendshipInvitationAccepted;
use Tests\TestCase;

class AcceptTest extends TestCase
{
    private User $user;
    private User $friend;

    private string $friendshipsTable = 'friendships';
    private string $notificationsTable = 'notifications';

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->createOne();
        $this->friend = User::factory()->createOne();
    }

    public function testCannotUseWhenNotAuthorized()
    {
        $response = $this->postJson('/api/friendship/accept');
        $response->assertStatus(401);
    }

    public function testCanAcceptInvitation()
    {
        Friendship::factory()->createOne([
            'user_id' => $this->friend->id,
            'friend_id' => $this->user->id,
            'status' => 'PENDING',
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/friendship/accept', [
            'user_id' => $this->friend->id,
        ]);

        $response->assertCreated();
        $this->assertDatabaseCount($this->friendshipsTable, 1);
        $this->assertDatabaseHas($this->friendshipsTable, [
            'user_id' => $this->friend->id,
            'friend_id' => $this->user->id,
            'status' => 'CONFIRMED',
        ]);
    }

    public function testAcceptInvitationSendsNotification()
    {
        Friendship::factory()->createOne([
            'user_id' => $this->friend->id,
            'friend_id' => $this->user->id,
            'status' => 'PENDING',
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/friendship/accept', [
            'user_id' => $this->friend->id,
        ]);

        $response->assertCreated();
        $this->assertDatabaseCount($this->notificationsTable, 1);
        $this->assertDatabaseHas($this->notificationsTable, [
            'type' => FriendshipInvitationAccepted::class,
            'notifiable_id' => $this->friend->id,
        ]);
    }

    public function testCannotAcceptInvitationWhichNotExists()
    {
        $response = $this->actingAs($this->user)->postJson('/api/friendship/accept', [
            'user_id' => $this->friend->id,
        ]);

        $response->assertUnprocessable();
    }

    public function testCannotAcceptOwn()
    {
        Friendship::factory()->createOne([
            'user_id' => $this->user->id,
            'friend_id' => $this->friend->id,
            'status' => 'PENDING',
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/friendship/accept', [
            'user_id' => $this->friend->id,
        ]);

        $response->assertUnprocessable();
    }

    public function testCannotAcceptInvitationWhichIsAlreadyConfirmed()
    {
        Friendship::factory()->createOne([
            'user_id' => $this->user->id,
            'friend_id' => $this->friend->id,
            'status' => 'CONFIRMED',
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/friendship/accept', [
            'user_id' => $this->friend->id,
        ]);

        $response->assertUnprocessable();
    }

    public function testCannotAcceptInvitationWhenInviterNotExistsNow()
    {
        Friendship::factory()->createOne([
            'user_id' => 99999,
            'friend_id' => $this->user->id,
            'status' => 'PENDING',
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/friendship/accept', [
            'user_id' => 99999,
        ]);

        $response->assertUnprocessable();
    }
}