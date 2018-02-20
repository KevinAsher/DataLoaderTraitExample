<?php

namespace App\GraphQL\Query;

use Folklore\GraphQL\Support\Query;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQL;
use App\Post;

class PostsQuery extends Query
{
    protected $attributes = [
        'name' => 'PostQuery',
        'description' => 'A query'
    ];

    public function type()
    {
        return Type::listOf(Graphql::type('Post'));
    }

    public function args()
    {
        return [
            'id' => ['name' => 'id', 'type' => Type::id()],
        ];
    }

    public function resolve($root, $args, $context, ResolveInfo $info)
    {
        // $fields = $info->getFieldSelection(1);

        // $posts = Post::query();

        // if (isset($args['id'])) {
        //     $posts->where('id', $args['id']);
        // }

        // if (isset($fields['author'])) {
        //     $posts->with('user');
        // }
        
        // if (isset($fields['likes'])) {
        //     $posts->withCount('likes');
        // }

        if (isset($args['id'])) {
            $result = Post::batchLoad([ (int) $args['id'] ]);
            // ->then(function ($r) {
            //     return nullIfArrayEmptyValues($r);
            // });
        } else {
            $result = Post::all();
        }

        return $result;
    }
}
