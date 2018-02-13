<?php

namespace App\GraphQL\Query;

use GraphQL;
use GraphQL\Type\Definition\Type;
use Folklore\GraphQL\Support\Query;
use App\User;
use GraphQL\Type\Definition\ResolveInfo;

use Overblog\DataLoader\DataLoader;
use Overblog\DataLoader\Promise\Adapter\Webonyx\GraphQL\SyncPromiseAdapter;
use Overblog\PromiseAdapter\Adapter\WebonyxGraphQLSyncPromiseAdapter;
use Facades\App\DataLoader\UserLoader;

class UserQuery extends Query
{
    protected $attributes = [
        'name' => 'user'
    ];

    public function type()
    {
        // return GraphQL::type('User');
        return Type::listOf(GraphQL::type('User'));
    }

    public function args()
    {
        return [
            // 'id' => ['name' => 'id', 'type' => Type::nonNull(Type::id())],
            'id' => ['name' => 'id', 'type' => Type::id()],
            'email' => ['name' => 'email', 'type' => Type::string()],
        ];
    }

    public function resolve($root, $args, $context, ResolveInfo $info)
    {
        // $fields = $info->getFieldSelection(2);

        // $users = User::query();


        // if (isset($args['id'])) {
        //     $users->where('id' , $args['id']);
        // } elseif (isset($args['email'])) {
        //     $users->where('email', $args['email']);
        // }

        // if (isset($fields['posts'])) {
        //     $users->with('posts');
        // }

        // if (isset($fields['followers'])) {
        //     $users->with('followers');
        // }

        // if (isset($fields['followees'])) {
        //     $users->with('followees');
        // }

        // $result = $users->get();
        // var_dump('inside resolver of UsersQuery');
        
        if (isset($args['id']))
            return User::batchLoadMany([(int)$args['id']]);
        else 
            return \App\User::all();
        // return User::batchLoad((int)$args['id']);
        
        
        // return $result->isEmpty() ? null : $result;
    }
}