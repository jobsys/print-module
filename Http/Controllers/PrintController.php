<?php

namespace Modules\Print\Http\Controllers;


use App\Http\Controllers\BaseManagerController;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Print\Entities\PrintTemplate;
use Modules\Starter\Emnus\State;

class PrintController extends BaseManagerController
{

    public function pagePrint()
    {
        $print_templates = PrintTemplate::where('parent_id', 0)->get(['id', 'display_name', 'parent_id'])->toArray();


        return Inertia::render('PagePrint@Print', [
            'parentTemplates' => $print_templates,
        ]);
    }

    public function pagePrintDesign()
    {
        $id = request('id');
        $print_template = PrintTemplate::find($id);
        if (!$print_template) {
            return Inertia::render('NudePagePrintDesigner@Print', [
                'error' => '打印模板不存在'
            ]);
        }

        return Inertia::render('NudePagePrintDesigner@Print', [
            'printTemplate' => $print_template,
        ]);

    }

    public function templateItems(Request $request)
    {
        $display_name = $request->input('display_name', false);


        $items = PrintTemplate::when($display_name, function ($query) use ($display_name) {
            return $query->where('display_name', 'like', "%{$display_name}%");
        })->get(['id', 'creator_id', 'parent_id', 'display_name', 'name', 'business_variables', 'description', 'created_at', 'updated_at']);

        if ($request->input('classify')) {
            $items = land_classify($items);
        }

        log_access('查看打印模板列表');

        return $this->json($items);
    }

    public function templateItem(Request $request, $id)
    {
        $item = PrintTemplate::find($id);

        if (!$item) {
            return $this->json(null, State::NOT_FOUND);
        }

        log_access('查看打印模板详情', $id);
        return $this->json($item);
    }

    public function templateEdit(Request $request)
    {
        list($input, $error) = land_form_validate(
            $request->only(['id', 'parent_id', 'display_name', 'description']),
            ['display_name' => 'bail|required|string'],
            ['display_name' => '打印模板名称'],
        );

        if ($error) {
            return $this->message($error);
        }

        $unique = land_is_model_unique($input, PrintTemplate::class, 'display_name', true);

        if (!$unique) {
            return $this->message('该模板名称已经存在');
        }

        // 只会存在一个原型模板，即使是以其它非原型模板为原型，也会将其原型设置为原型模板的原型
        if (isset($input['parent_id']) && $input['parent_id']) {
            $parent_template = PrintTemplate::find($input['parent_id']);
            if (!$parent_template) {
                return $this->message('模板原型不存在');
            }
            $input['parent_id'] = $parent_template->parent_id;
        }else{
            $input['parent_id'] = 0;
        }


        if (isset($input['id']) && $input['id']) {
            $result = PrintTemplate::where('id', $input['id'])->update($input);
        } else {
            $name = pinyin_abbr($input['display_name']);

            $index = 1;
            while (!land_is_model_unique(['name' => $name], PrintTemplate::class, 'name', true)) {
                $name = $name . $index;
                $index += 1;
            }
            $input['name'] = $name;
            $input['creator_id'] = $this->login_user_id;
            $result = PrintTemplate::create($input);
        }


        log_access(isset($input['id']) && $input['id'] ? '编辑打印模板' : '新建打印模板', $input['id'] ?? $result->id);

        return $this->json(null, $result ? State::SUCCESS : State::FAIL);
    }

    public function templateVariableEdit(Request $request)
    {
        $id = $request->input('id');
        $business_variables = $request->input('business_variables');

        $print_template = PrintTemplate::find($id);

        if (!$print_template) {
            return $this->message('打印模板不存在');
        }

        $print_template->business_variables = $business_variables;
        $print_template->save();
        return $this->json();
    }

    public function templateDesignEdit(Request $request)
    {
        $id = $request->input('id');
        $template = $request->input('template');
        $template_html = $request->input('template_html');

        $print_template = PrintTemplate::find($id);

        if (!$print_template) {
            return $this->message('打印模板不存在');
        }

        $print_template->template = $template;
        $print_template->template_html = $template_html;
        $print_template->save();
        return $this->json();
    }

    public function templateDelete(Request $request)
    {
        $id = $request->input('id');
        $item = PrintTemplate::where('id', $id)->first();

        if (!$item) {
            return $this->json(null, State::NOT_ALLOWED);
        }

        if ($item->parent_id == 0 && PrintTemplate::where('parent_id', $item->parent_id)->exists()) {
            return $this->message('该模板下存在子模板，不能删除');
        }

        $result = $item->delete();

        log_access('删除打印模板', $id);
        return $this->json(null, $result ? State::SUCCESS : State::FAIL);
    }

    public function templateCopy(Request $request){
        $id = $request->input('id');

        $print_template = PrintTemplate::find($id);

        if (!$print_template) {
            return $this->message('打印模板不存在');
        }

        $new_print_template = $print_template->replicate();
        $new_print_template->display_name = $new_print_template->display_name . '_' . now()->format('YmdHis');
        $new_print_template->creator_id = $this->login_user_id;
        $new_print_template->parent_id = $print_template->parent_id ?: $print_template->id;
        $new_print_template->created_at = now();
        $new_print_template->save();

        return $this->json();
    }

}
