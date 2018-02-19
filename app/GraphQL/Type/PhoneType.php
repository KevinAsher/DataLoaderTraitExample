<?php

namespace App\GraphQL\Type;

use GraphQL\Type\Definition\Type;
use Folklore\GraphQL\Support\Type as BaseType;
use GraphQL;

class PhoneType extends BaseType
{
    protected $attributes = [
        'name' => 'PhoneType',
        'description' => 'A type'
    ];

    public function fields()
    {
        return [
            'number' => ['type' => Type::string()],
            'user' =>   ['type' => Graphql::type('User')],
        ];
    }
    
    protected function resolveUserField($root)
    {
        return $root->batchLoadUser();
    }
}
