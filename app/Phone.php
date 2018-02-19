<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\DataLoaderTrait;

class Phone extends Model
{
    use DataLoaderTrait;

    public function user() : BelongsTo
    {
        return $this->belongsTo('App\User');
    }
}
