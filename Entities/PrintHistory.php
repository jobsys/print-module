<?php

namespace Modules\Print\Entities;


use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Starter\Entities\BaseModel;

class PrintHistory extends BaseModel
{

    public function template(): BelongsTo
    {
        return $this->belongsTo(PrintTemplate::class);
    }
}
