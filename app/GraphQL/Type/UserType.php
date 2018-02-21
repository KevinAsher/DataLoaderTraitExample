<?php

namespace App\GraphQL\Type;

use GraphQL\Type\Definition\Type;
use Folklore\GraphQL\Support\Type as GraphQLType;
use GraphQL;
use App\User;
use GraphQL\Type\Definition\ResolveInfo;

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
        $fieldArr = [
            'id'        => Type::nonNull(Type::string()),
            'email'     => Type::string(),
            'name'      => Type::string(),
            'phone'     => ['type' => GraphQL::type('Phone')],
            'posts'     => ['type' => Type::listOf(GraphQL::type('Post'))],
            'followers' => ['type' => Type::listOf(GraphQL::type('User'))],
            'followees' => ['type' => Type::listOf(GraphQL::type('User'))]
        ];

        return formatGraphqlFields($fieldArr, [$this, 'resolveField']);
    }

    public function resolveField($root, $args, $context, ResolveInfo $info) 
    {
        clock($root, $args, $context, $info->fieldName);
        return $root->{$info->fieldName};
    }

    // If you want to resolve the field yourself, you can declare a method
    // with the following format resolve[FIELD_NAME]Field()
    // protected function resolveEmailField($root, $args)
    // {
    //     return $root->email;
    // }

    protected function resolvePostsField($root, $args)
    {
        // return UserLoader::loadPosts($root->id);
        return $root->batchLoadPosts();
    }

    protected function resolveFollowersField($root, $args)
    {
        
        
        $result = $root->batchLoadFollowers();
        
        return $result;
    }

    protected function resolveFolloweesField($root, $args)
    {
        return $root->batchLoadFollowees();
    }

    protected function resolvePhoneField($root, $args)
    {
        return $root->batchLoadPhone();
    }
}