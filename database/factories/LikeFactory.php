<?php

use Faker\Generator as Faker;
use Illuminate\Support\Facades\DB;

$factory->define(App\Like::class, function (Faker $faker) {

    // return [
    //     'user_id' => App\User::inRandomOrder()->first()->id
    //     'post_id' => App\Post::orderBy('id')
    // ];
});
