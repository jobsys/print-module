<?php

namespace Modules\Print\Services;


use Inertia\Inertia;
use Inertia\Response;
use Modules\Print\Entities\PrintTemplate;

class PrintService
{
    /**
     * 打印
     * @param int $template_id 打印模板id
     * @param object|array $data 打印数据, 如果为数组，表示打印多份
     * @return Response
     */
    public function print(int $template_id, object|array $data): Response
    {
        $print_template = PrintTemplate::find($template_id);

        if (!$print_template) {
            return Inertia::render('NudePagePrintDesigner@Print', [
                'error' => '打印模板不存在'
            ]);
        }

        return Inertia::render('NudePagePrintDesigner@Print', [
            'printTemplate' => $print_template,
            'printData' => $data
        ]);
    }

    /**
     * 初始化打印模板
     * @param string $display_name
     * @param string $description
     * @return void
     */
    public function createTemplate(string $display_name, string $description): void
    {
        $template = new PrintTemplate();

        $template->display_name = $display_name;
        $template->name = pinyin_abbr($display_name);
        $template->creator_id = config('conf.super_admin_id');
        $template->parent_id = 0;
        $template->description = $description;
        $template->save();
    }
}
