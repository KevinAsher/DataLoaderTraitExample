<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Overblog\DataLoader\DataLoader;

class User extends Model
{
    use Traits\DataLoaderTrait;

    protected $guarded = [];
    
    public function posts() : HasMany
    {
        return $this->hasMany('App\Post');
    }

    public function likes() : HasMany
    {
        return $this->hasMany('App\Like');
    }

    public function followers()
    {
        return $this->belongsToMany(self::class, 'follow_users', 'followee_id', 'follower_id');
    }

    public function followees()
    {
        return $this->belongsToMany(self::class, 'follow_users', 'follower_id', 'followee_id');
    }

    protected static function boot()
    {
        parent::boot();

        self::$relationDataLoaders['followers'] = new DataLoader(function ($keys) {
            $keys = self::normalizeKeyArray($keys);
            $collection = DB::table('follow_users')
                                ->select('followee_id', 'follower_id')
                                ->whereIn('followee_id', $keys)->get();

            return self::$promiseAdapter->createFulfilled(self::orderByKeys($collection, $keys, 'followee_id'));
        }, self::$promiseAdapter);
    }

    public function batchLoadLikesCount()
    {
        return self::$relationDataLoaders['followers']->loadMany([$this->id])
                    ->then(function($followers){
                        $this->batchLoadMany($followers->pluck('id')->all());
                    });
    }
}
