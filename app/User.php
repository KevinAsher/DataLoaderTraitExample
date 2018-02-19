<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use App\Traits\DataLoaderTrait;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Model
{
    use DataLoaderTrait;

    protected $guarded = [];

    public function phone() : HasOne
    {
        return $this->hasOne('App\Phone');
    }
    
    public function posts() : HasMany
    {
        return $this->hasMany('App\Post');
    }

    public function likes() : HasMany
    {
        return $this->hasMany('App\Like');
    }

    public function followers() : BelongsToMany
    {
        return $this->belongsToMany(self::class, 'follow_users', 'followee_id', 'follower_id');
    }

    public function followees() : BelongsToMany
    {
        return $this->belongsToMany(self::class, 'follow_users', 'follower_id', 'followee_id');
    }

    // protected static function boot()
    // {
    //     parent::boot();
    //     self::$relationDataLoaders['followers'] = new DataLoader(function ($keys) {
    //         $collection = DB::table('follow_users')
    //                             ->select('followee_id', 'follower_id')
    //                             ->addSelect('users.*')
    //                             ->join('users', 'follower_id', '=', 'users.id')
    //                             ->whereIn('followee_id', $keys)->get();
    //         return self::$promiseAdapter->createFulfilled(self::orderManyPerKey(self::hydrate($collection->all()), $keys, 'followee_id'));
    //     }, self::$promiseAdapter);

    //     self::$relationDataLoaders['followees'] = new DataLoader(function ($keys) {
    //         $collection = DB::table('follow_users')
    //             ->select('followee_id', 'follower_id')
    //             ->addSelect('users.*')
    //             ->join('users', 'followee_id', '=', 'users.id')
    //             ->whereIn('follower_id', $keys)->get();
    //         return self::$promiseAdapter->createFulfilled(self::orderManyPerKey(self::hydrate($collection->all()), $keys, 'follower_id'));
    //     }, self::$promiseAdapter);
    // }

    // public function batchLoadManyFollowers()
    // {
    //     return self::$relationDataLoaders['followers']->loadMany([$this->id])
    //                 ->then(function($followers){
    //                     $followers = $followers[0];
    //                     foreach($followers as $follower) {
    //                         self::$dataLoader->prime($follower->id, $follower);
    //                     }

    //                     return $this->batchLoadMany(array_column($followers, 'id'));
    //                 });
    // }

    // public function batchLoadManyFollowees() {
    //     return self::$relationDataLoaders['followees']->loadMany([$this->id])
    //             ->then(function ($followees) {
    //                 $followees = $followees[0];
    //                 foreach ($followees as $followee) {
    //                     self::$dataLoader->prime($followee->id, $followee);
    //                 }

    //                 return $this->batchLoadMany(array_column($followees, 'id'));
    //             });
    // }
}
