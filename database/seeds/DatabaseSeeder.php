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
                $user->posts()->saveMany(factory(App\Post::class, rand(1, 10))->make());
                // $posts->each(function($post) use ($user) {
                //     $likes = $post->likes()->saveMany(factory(App\Like::class, rand(1, 10))->make());
                // });

                $randomUser = App\User::inRandomOrder()->first();
                
                if ($randomUser->getKey() !== $user->getKey()) {
                    $user->followers()->save($randomUser);
                }

                $randomUser = App\User::inRandomOrder()->first();

                if ($randomUser->getKey() !== $user->getKey()) {
                    $user->followees()->save($randomUser);
                }

            });

        $usersAndPostsWithoutLike = DB::table('users')
                                        ->select('users.id as u_id', 'posts.id as p_id')
                                        ->crossJoin('posts')
                                        ->whereNotExists(function($query) {
                                            $query->select(DB::raw(1))
                                                  ->from('likes')
                                                  ->whereRaw('users.id <> likes.user_id')
                                                  ->whereRaw('posts.id <> likes.post_id');
                                        })
                                        ->inRandomOrder()
                                        ->take(1000)
                                        ->get();


        $usersAndPostsWithoutLike->each(function($row) {
            App\Like::create([
                'user_id' => $row->u_id,
                'post_id' => $row->p_id,
            ]);
        });
    }
}
