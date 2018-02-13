<?php
namespace App\DataLoader;

use App\Post;
use App\User;
use Facades\App\DataLoader\PostsByUserLoader;

class PostLoader extends Loader
{
	public function batchLoad($ids)
	{
		// dd($ids);
		$collection = Post::whereIn('id', $ids)->get();

		return $this->promiseAdapter->createFulfilled($collection);
	}

	public function loadByUserId($id)
	{

		return PostsByUserLoader::loadMany([$id])->then(function ($posts) {

			$posts = $posts[0];			

			foreach ($posts as $post) {
				$this->prime($post['id'], $post);
			}

			return $this->loadMany(array_column($posts, 'id'));
		});
	}


}