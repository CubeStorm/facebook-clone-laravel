<?php

declare(strict_types=1);

namespace Tests\Feature\Likes;

use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Tests\TestCase;

class StoreTest extends TestCase
{
    private User $user;
    private Post $post;

    private string $likesStoreRoute;

    private string $likesTable = 'likes';

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->createOne();
        $this->post = Post::factory()->createOne();
        $this->likesStoreRoute = route('api.likes.store');
    }

    public function testCannotUseAsUnauthorized(): void
    {
        $response = $this->postJson($this->likesStoreRoute);
        $response->assertUnauthorized();
    }

    public function testCanUseAsAuthorized(): void
    {
        $response = $this->actingAs($this->user)->postJson($this->likesStoreRoute, [
            'post_id' => $this->post->id,
        ]);

        $response->assertCreated();
    }

    public function testPassedEmptyValueIsTreatingAsNullValue(): void
    {
        $response = $this->actingAs($this->user)->postJson($this->likesStoreRoute, [
            'post_id' => '',
        ]);

        $response->assertJsonValidationErrorFor('post_id');
    }

    public function testCannotCreateLikeForPostWhichNotExists(): void
    {
        $response = $this->actingAs($this->user)->postJson($this->likesStoreRoute, [
            'post_id' => 99999,
        ]);

        $response->assertUnprocessable();
        $this->assertDatabaseCount($this->likesTable, 0);
    }

    public function testCannotCreateLikeForPostWhichIsAlreadyLikedByLoggedUser(): void
    {
        Like::factory()->createOne([
            'user_id' => $this->user->id,
            'post_id' => $this->post->id,
        ]);

        $response = $this->actingAs($this->user)->postJson($this->likesStoreRoute, [
            'post_id' => $this->post->id,
        ]);

        $response->assertJsonValidationErrorFor('post_id');
        $this->assertDatabaseCount($this->likesTable, 1);
    }

    public function testCanCreateLike(): void
    {
        $response = $this->actingAs($this->user)->postJson($this->likesStoreRoute, [
            'post_id' => $this->post->id,
        ]);

        $response->assertCreated();
        $this->assertDatabaseCount($this->likesTable, 1);
    }

    public function testCannotPassNoPostId(): void
    {
        $response = $this->actingAs($this->user)->postJson($this->likesStoreRoute);
        $response->assertJsonValidationErrorFor('post_id');
    }

    public function testCanLikePostWhichIsLikedByAnotherUser(): void
    {
        $friend = User::factory()->createOne();

        $this->generateLike($friend->id);

        $response = $this->actingAs($this->user)->postJson($this->likesStoreRoute, [
            'post_id' => $this->post->id,
        ]);

        $response->assertCreated();
    }

    public function testResponseHasProperlyLikesCount(): void
    {
        $friends = User::factory(2)->create();

        $this->generateLike($friends[0]->id);
        $this->generateLike($friends[1]->id);

        $response = $this->actingAs($this->user)->postJson($this->likesStoreRoute, [
            'post_id' => $this->post->id,
        ]);

        $response->assertCreated()
            ->assertJsonFragment([
                'data' => [
                    'likesCount' => 3,
                ],
            ]);
    }

    private function generateLike(int $userId = null): void
    {
        Like::factory()->createOne([
            'user_id' => $userId ?? $this->user->id,
            'post_id' => $this->post->id,
        ]);
    }
}