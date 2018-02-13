<?php

namespace App\GraphQL\Type;

use GraphQL\Type\Definition\Type;
use Folklore\GraphQL\Support\Type as GraphQLType;
use GraphQL;

use Facades\App\DataLoader\PostLoader;
use Facades\App\DataLoader\UserLoader;
use Facades\App\DataLoader\UserPostsLoader;
use App\User;

class UserType extends GraphQLType
{
    protected $attributes = [
        'name' => 'User',
        'description' => 'A user'
    ];


    /*
    * Uncomment following line to make the type input object.
    * http://graphql.org/learn/schema/#input-types
    */
    // protected $inputObject = true;

    public function fields()
    {
        return [
            'id' => ['type' => Type::nonNull(Type::string())],
            'email' => [
                'type' => Type::string(),
                'description' => 'The email of user'
            ],
            'name' => [
                'type' => Type::string(),
            ],
            'posts' => ['name' => 'posts', 'type' => Type::listOf(GraphQL::type('Post'))],
            'followers' => [
                'type' => Type::listOf(GraphQL::type('User')),
                // 'type' => Type::string(),
            ],
            'followees' => ['type' => Type::listOf(GraphQL::type('User'))],

        ];
    }

    // If you want to resolve the field yourself, you can declare a method
    // with the following format resolve[FIELD_NAME]Field()
    protected function resolveEmailField($root, $args)
    {
        return $root->email;
    }

    protected function resolvePostsField($root, $args)
    {
        // return UserLoader::loadPosts($root->id);
        return $root->batchLoadManyPosts();
    }

    protected function resolveFollowersField($root, $args)
    {
       return UserLoader::loadFollowers($root, $args);
    }
}