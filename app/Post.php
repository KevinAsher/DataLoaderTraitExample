<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Overblog\DataLoader\DataLoader;
use Illuminate\Support\Facades\DB;
use App\GraphQL\DataLoader\DataLoaderTrait;

class Post extends Model
{
    use DataLoaderTrait;
    
    protected $guarded = [];

    public function user() : BelongsTo
    {
        return $this->belongsTo('App\User');
    }

    public function likes() : HasMany
    {
        return $this->hasMany('App\Like');
    }

    public function batchLoadLikes() {
        return $this->createDataLoaderOnce('likescount', 'post_id', function ($keys) {
            $collection = Like::selectRaw('post_id, COUNT(*) as likes')
                                ->whereIn('post_id', $keys)
                                ->groupBy('post_id')
                                ->get();

            return $collection;
        })->load($this->id)->then(function($obj) {
            $obj = firstOrNull($obj);
            if (!empty($obj)) {
                return $obj->likes;
            }
            return 0;
        });
    }
}
