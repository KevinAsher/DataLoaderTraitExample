<?php
namespace App\DataLoader;

use App\Post;
use DB;

class PostsByUserLoader extends Loader
{

  public function batchLoad($userIds)
  {
    // dd($userIds);
    $collection = Post::whereIn('user_id', $userIds)->get();

    $sorted = array_flip($userIds);


    foreach ($userIds as $id) {      
      
      $sorted[$id] = $collection->where('user_id', $id)->toArray();
      // dd($sorted[$id]);
    }

    return $this->promiseAdapter->createFulfilled(array_values($sorted));
  }
}