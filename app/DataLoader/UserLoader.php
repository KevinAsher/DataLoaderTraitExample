<?php
namespace App\DataLoader;
use App\User;
use Facades\App\DataLoader\PostLoader;
use Facades\App\DataLoader\PostsByUserLoader;
use App\Post;

class UserLoader extends Loader {

    protected $userIdsBeingFollowed = [];

    public function batchLoad($ids)
    {
        // $followerIds = [];

        // if (!empty($this->userIdsBeingFollowed)) {
        //     $followerIds = DB::table('follow_users')->whereIn('followee_id', $this->userIdsBeingFollowed)->get()->pluck('follower_id');
        // }

        // $dedupedIds = array_unique(array_merge($ids, $followerIds));

        $collection = User::whereIn('id', $ids)->get();

        $sorted = array_flip($ids);

        foreach ($collection as $user) $sorted[$user->id] = $user;

        $collection = collect(array_values($sorted));
        
        // return $this->promiseAdapter->createFulfilled($collection->keyBy('id'));
        return $this->promiseAdapter->createFulfilled($collection);
    }

    public function loadFollowers(User $user, $args)
    {
        $this->userIdsBeingFollowed[] = $user->id;
    }

    public function loadAll()
    {
        $collection = User::all();

        foreach ($collection as $user) {
            $this->prime($user->id, $user);
        }

        // return $this->loadMany($collection->pluck('id')->all());
        return $collection;
    }   

    public function loadPosts($id)
    {
        return PostLoader::loadByUserId($id);
    }
}