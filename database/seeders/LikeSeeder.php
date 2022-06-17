<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Foundation\Testing\WithFaker;

class LikeSeeder extends Seeder
{
    use WithFaker;

    public function __construct()
    {
        $this->setUpFaker();
    }

    public function run(User $user, int $count): void
    {
        $posts = Post::pluck('id');

        Like::factory($count)->create([
            'user_id' => $user->id,
            'post_id' => fn () => $this->faker->unique->randomElement($posts),
        ]);
    }
}
