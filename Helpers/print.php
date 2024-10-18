<?php

namespace Modules\Print\Helpers;

use Modules\Print\Entities\PrintTemplate;

if (!function_exists('print_get_template_options')) {

	/**
	 * 获取打印模板选项
	 * @param $name? string|null
	 * @return array
	 */
	function print_get_template_options(string $name = null): array
	{
		return PrintTemplate::when($name, function ($query) use ($name) {
			$query->where('name', 'like', '%' . $name . '%');
		})->get(['id', 'name'])->map(fn(PrintTemplate $item) => [
			'label' => $item->name,
			'value' => $item->id
		])->toArray();
	}
}
