<?php

namespace Modules\Print\Entities;


use Illuminate\Database\Eloquent\Relations\HasMany;
use Kalnoy\Nestedset\NodeTrait;
use Modules\Permission\Traits\Authorisations;
use Modules\Starter\Entities\BaseModel;

class PrintTemplate extends BaseModel
{
	use NodeTrait, Authorisations;

	protected $model_name = '打印模板';


	protected $casts = [
		'template' => 'array',
		'business_variables' => 'array',
	];

	protected $hidden = [
		'_lft',
		'_rgt',
	];

	public function histories(): HasMany
	{
		return $this->hasMany(PrintHistory::class);
	}
}
