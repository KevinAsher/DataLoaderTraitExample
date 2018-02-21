<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\GraphQL\DataLoader\DataLoaderTrait;

class Phone extends Model
{
    use DataLoaderTrait;

    protected $guarded = [];

    public function user() : BelongsTo
    {
        return $this->belongsTo('App\User');
    }
}
