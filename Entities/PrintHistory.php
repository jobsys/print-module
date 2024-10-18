<?php

namespace Modules\Print\Entities;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrintHistory extends Model
{

    public function template(): BelongsTo
    {
        return $this->belongsTo(PrintTemplate::class);
    }
}
