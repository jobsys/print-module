<?php

namespace Modules\Print\Http\Controllers;


use App\Http\Controllers\BaseManagerController;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Print\Entities\PrintTemplate;
use Modules\Starter\Enums\State;

class PrintController extends BaseManagerController
{

	public function pagePrint()
	{
		$print_templates = PrintTemplate::whereNull('parent_id')->get(['id', 'name', 'parent_id'])->toArray();

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

		$items = PrintTemplate::filterable()->whereNull('parent_id')->get();
		$items = land_get_closure_tree($items);

		return $this->json($items);
	}

	public function templateItem(Request $request, $id)
	{
		$item = PrintTemplate::find($id);

		if (!$item) {
			return $this->json(null, State::NOT_FOUND);
		}

		log_access('查看打印模板详情', $item);
		return $this->json($item);
	}

	public function templateEdit(Request $request)
	{
		list($input, $error) = land_form_validate(
			$request->only(['id', 'parent_id', 'name', 'description']),
			['name' => 'bail|required|string'],
			['name' => '打印模板名称'],
		);

		if ($error) {
			return $this->message($error);
		}

		$unique = land_is_model_unique($input, PrintTemplate::class, 'name', true);

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
		}

		if (isset($input['id']) && $input['id']) {
			$result = PrintTemplate::where('id', $input['id'])->update($input);
		} else {
			$input['slug'] = land_slug($input['name'], PrintTemplate::class);
			$input['creator_id'] = auth()->id();
			$result = PrintTemplate::create($input);
		}

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

		$print_template = PrintTemplate::find($id);

		if (!$print_template) {
			return $this->message('打印模板不存在');
		}

		$print_template->template = $template;
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

		if (!$item->parent_id && PrintTemplate::where('parent_id', $item->parent_id)->exists()) {
			return $this->message('该模板下存在子模板，不能删除');
		}

		$result = $item->delete();

		return $this->json(null, $result ? State::SUCCESS : State::FAIL);
	}

	public function templateCopy(Request $request)
	{
		$id = $request->input('id');

		$print_template = PrintTemplate::find($id);

		if (!$print_template) {
			return $this->message('打印模板不存在');
		}

		$new_print_template = $print_template->replicate();
		$new_print_template->name = $new_print_template->name . '_' . now()->format('YmdHis');
		$new_print_template->creator_id = auth()->id();
		$new_print_template->parent_id = $print_template->parent_id ?: $print_template->id;
		$new_print_template->created_at = now();
		$new_print_template->save();

		return $this->json();
	}

}
