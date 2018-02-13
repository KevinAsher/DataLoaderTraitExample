<?php
namespace App\DataLoader;

use DB;

class AuthorLoader extends Loader
{

  public function batchLoad($postIds)
  {

    $collection = DB::table('posts')
                      ->join('users', 'users.id', '=', 'posts.user_id')
                      ->whereIn('posts.id', $postIds)->get();

    return $this->promiseAdapter->createFulfilled($collection);
  }
}