<?php
namespace App\DataLoader;

use App\Post;
use DB;

class PostLikesCount extends Loader
{

  public function batchLoad($postIds)
  {

    // $collection = Post::whereIn('id', $userIds)->withCount('likes')->get()->pluck('likes_count');
    $collection = Like::whereIn('post_id', $postIds)->withCount('likes')->get()->pluck('likes_count');

    return $this->promiseAdapter->createFulfilled($collection);
  }
}