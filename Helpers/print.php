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
        $print_templates = PrintTemplate::when($name, function ($query) use ($name) {
            $query->where('name', 'like', '%' . $name . '%');
        })->get(['id', 'name', 'display_name', 'parent_id']);

        return $print_templates->map(fn(PrintTemplate $item) => [
            'label' => $item->display_name,
            'value' => $item->id
        ])->toArray();
    }
}
