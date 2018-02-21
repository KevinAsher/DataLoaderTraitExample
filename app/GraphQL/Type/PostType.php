<?php

namespace App\GraphQL\Type;

use GraphQL\Type\Definition\Type;
use Folklore\GraphQL\Support\Type as BaseType;
use GraphQL;
use App\Post;


class PostType extends BaseType
{

    protected $attributes = [
        'name' => 'PostType',
        'description' => 'A type'
    ];

    public function fields()
    {
        return [
            'id'            => ['type' => Type::nonNull(Type::string())],
            'title'         => ['type' => Type::nonNull(Type::string())],
            'body'          => ['type' => Type::nonNull(Type::string())],
            'author'        => ['type' => GraphQL::type('User')],
            'likes'         => ['type' => Type::int()],  
        ];
    }

    protected function resolveAuthorField($root, $args)
    {
        return $root->batchLoadUser();
    }

    protected function resolveLikesField($root, $args)
    {
        return $root->batchLoadLikesCount();
    }
}
