<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Overblog\DataLoader\DataLoader;
use Illuminate\Support\Facades\DB;

class Post extends Model
{
    use Traits\DataLoaderTrait;
    
    protected $guarded = [];

    public function user() : BelongsTo
    {
        return $this->belongsTo('App\User');
    }

    public function likes() : HasMany
    {
        return $this->hasMany('App\Like');
    }

    protected static function boot() {
        parent::boot();

        self::$relationDataLoaders['likescount'] = new DataLoader(function($keys) {
            $collection = Like::selectRaw('post_id, COUNT(*) as likes')
                                ->whereIn('post_id', $keys)
                                ->groupBy('post_id')
                                ->get();

            return self::$promiseAdapter->createFulfilled(self::orderOnePerKey($collection, $keys, 'post_id'));
        }, self::$promiseAdapter);
    }

    public function batchLoadLikesCount() {
        return self::$relationDataLoaders['likescount']->load($this->id)
                    ->then(function($obj) { 
                        if ($obj !== NULL) {
                            return $obj->likes;
                        }
                        return $obj;
                    });
    }
}
