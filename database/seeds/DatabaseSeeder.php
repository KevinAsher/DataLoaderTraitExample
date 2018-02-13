<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(App\User::class, 50)->create()
            ->each(function ($user) {
                $posts = $user->posts()->saveMany(factory(App\Post::class, rand(1, 10))->make());
                $posts->each(function($post) use ($user) {
                    $likes = $post->likes()->saveMany(factory(App\Like::class, rand(1, 10))->make());
                });

                $randomUser = App\User::inRandomOrder()->first();
                
                if ($randomUser->getKey() !== $user->getKey()) {
                    $user->followers()->save($randomUser);
                }

                $randomUser = App\User::inRandomOrder()->first();

                if ($randomUser->getKey() !== $user->getKey()) {
                    $user->followees()->save($randomUser);
                }

            });
    }
}
