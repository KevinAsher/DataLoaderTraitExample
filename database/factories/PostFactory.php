<?php

use Faker\Generator as Faker;

$factory->define(App\Post::class, function (Faker $faker) {
    return [
        'title' => $faker->realText(70),
        'body' => $faker->realText(191),
    ];
});
