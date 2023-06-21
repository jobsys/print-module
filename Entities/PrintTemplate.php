<?php

namespace Modules\Print\Entities;


use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Permission\Traits\Authorisations;
use Modules\Starter\Entities\BaseModel;

class PrintTemplate extends BaseModel
{

    use Authorisations;

    protected $casts = [
        'template' => 'array',
        'business_variables' => 'array',
    ];

    public function histories(): HasMany
    {
        return $this->hasMany(PrintHistory::class);
    }
}
