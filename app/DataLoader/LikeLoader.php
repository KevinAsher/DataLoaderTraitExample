<?php
namespace App\DataLoader;

use App\Like;

class LikeLoader extends Loader
{

  public function batchLoad($ids)
  {
    $collection = Like::whereIn('id', $ids)->get();

    return $this->promiseAdapter->createFulfilled($collection);
  }
}