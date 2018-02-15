<?php

namespace App\GraphQL\Type;

use GraphQL\Type\Definition\Type;
use Folklore\GraphQL\Support\Type as BaseType;
use GraphQL;

class Like extends BaseType
{
    protected $attributes = [
        'name' => 'Like',
        'description' => 'A type'
    ];

    public function fields()
    {
        return [
            'author' => ['type' => GraphQL::type('User')],

        ];
    }
}
