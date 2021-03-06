<?php

declare(strict_types=1);

namespace Tests\Feature\Comments\Posts;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Tests\TestCase;

class IndexTest extends TestCase
{
    private User $user;
    private Post $post;

    private string $route;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->createOne();
        $this->post = Post::factory()->createOne();
        $this->route = route('api.comments.posts.index', $this->post->id);
    }

    public function testCannotUseAsUnauthorized(): void
    {
        $response = $this->getJson($this->route);
        $response->assertUnauthorized();
    }

    public function testCanUseAsAuthorized(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson($this->route);

        $response->assertOk();
    }

    public function testReturnEmptyResponseWhenPostHasNoComments(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson($this->route);

        $response->assertOk()
            ->assertJsonCount(0);
    }

    public function testReturnCommentsProperly(): void
    {
        $this->generateComments(1);

        $response = $this->actingAs($this->user)
            ->getJson($this->route);

        $response->assertOk()
            ->assertJsonCount(1);
    }

    public function testReturnCommentsWhichAuthorsIsLoggedUser(): void
    {
        $this->generateComments(5);

        $response = $this->actingAs($this->user)
            ->getJson($this->route);

        $response->assertOk()
            ->assertJsonCount(5);
    }

    public function testReturnCommentsWhichAuthorsIsFriend(): void
    {
        $friend = User::factory()->createOne();

        $this->generateComments(8, $friend->id);

        $response = $this->actingAs($this->user)
            ->getJson($this->route);

        $response->assertOk()
            ->assertJsonCount(8);
    }

    public function testCannotReturnCommentsFromAnotherPost(): void
    {
        $anotherPost = Post::factory()->createOne();

        $this->generateComments(12, postId: $anotherPost->id);

        $response = $this->actingAs($this->user)
            ->getJson($this->route);

        $response->assertOk()
            ->assertJsonCount(0);
    }

    public function testReturnMaxTenComments(): void
    {
        $this->generateComments(14);

        $response = $this->actingAs($this->user)
            ->getJson($this->route);

        $response->assertOk()
            ->assertJsonCount(10);
    }

    public function testCanFetchMoreCommentsFromSecondPage(): void
    {
        $this->generateComments(14);

        $response = $this->actingAs($this->user)->getJson($this->route.'?page=2');
        $response->assertOk()
            ->assertJsonCount(4);
    }

    public function testCannotReturnCommentsFromPostWhichNotExists(): void
    {
        $this->generateComments(8, postId: 99999);

        $response = $this->actingAs($this->user)
            ->getJson(route('api.comments.posts.index', 999999));

        $response->assertNotFound();
    }

    private function generateComments(int $count, int $authorId = null, int $postId = null): void
    {
        Comment::factory($count)->create([
            'resource' => 'POST',
            'author_id' => $authorId ?? $this->user->id,
            'resource_id' => $postId ?? $this->post->id,
        ]);
    }
}
