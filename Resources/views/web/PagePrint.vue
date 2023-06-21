<template>
	<a-result v-if="error" status="warning" title="操作提醒" :sub-title="error"></a-result>

	<div v-else>
		<NewbieTable
			ref="list"
			:url="route('api.manager.print.template.items', { classify: 1 })"
			:columns="columns()"
			:page="false"
			:filter-form="false"
			:fetched="myFetched"
			:table-props="{ defaultExpandAllRows: true }"
		>
			<template #functional>
				<NewbieButton v-if="$auth('api.manager.print.template.edit')" type="primary" icon="PlusOutlined" @click="onEdit(false)"
					>新增模板
				</NewbieButton>
			</template>
		</NewbieTable>
		<NewbieModal v-model:visible="state.showEditor" title="打印模板编辑">
			<NewbieEdit
				ref="edit"
				:fetch-url="state.url"
				:auto-load="!!state.url"
				:submit-url="route('api.manager.print.template.edit')"
				:card-wrapper="false"
				full-width
				:form="getForm()"
				:on-close="closeEditor"
				:submit-disabled="!$auth('api.manager.print.template.edit')"
				@after-success="closeEditor(true)"
			/>
		</NewbieModal>

		<NewbieModal v-model:visible="state.showVariableEditor" title="业务参数设置" :width="1000">
			<a-button type="primary" class="mb-1" @click="() => onAddVariable()">
				<template #icon>
					<NewbieIcon icon="PlusOutlined"></NewbieIcon>
				</template>
				添加参数
			</a-button>
			<a-table :data-source="state.variablesForm" :columns="getVariableColumns()" size="small" :pagination="false">
				<template #bodyCell="{ column, index }">
					<div v-if="'title' === column.dataIndex">
						<a-input v-model:value="state.variablesForm[index][column.dataIndex]" placeholder="请填写参数名称"></a-input>
					</div>
					<div v-else-if="'field' === column.dataIndex">
						<a-input v-model:value="state.variablesForm[index][column.dataIndex]" placeholder="请填写参数键名"></a-input>
					</div>
					<div v-else-if="'type' === column.dataIndex">
						<a-select
							v-model:value="state.variablesForm[index][column.dataIndex]"
							placeholder="请选择参数类型"
							:options="state.variableTypeOptions"
							@change="(value) => onChangeVariableType(value, index)"
						></a-select>
					</div>
					<div v-else-if="'data' === column.dataIndex">
						<a-input v-model:value="state.variablesForm[index][column.dataIndex]" placeholder="请填写测试数据"></a-input>
					</div>
					<template v-else-if="column.dataIndex === 'operation'">
						<div class="table-actions-wrapper">
							<a-button type="text" size="small" @click="onDeleteVariable(index)">
								<template #icon>
									<NewbieIcon icon="DeleteOutlined"></NewbieIcon>
								</template>
								删除
							</a-button>
						</div>
					</template>
				</template>
				<template #expandedRowRender="{ record, index }">
					<div v-if="record.type === 'table'">
						<a-button type="primary" class="mb-1" @click="() => onAddTableVariable(index)">
							<template #icon>
								<NewbieIcon icon="PlusOutlined"></NewbieIcon>
							</template>
							添加表格参数
						</a-button>
						<a-table :columns="getTableVariableColumns()" :data-source="record.columns" :pagination="false">
							<template #bodyCell="{ column, index: subIndex }">
								<div v-if="'title' === column.dataIndex">
									<a-input
										v-model:value="state.variablesForm[index].columns[subIndex][column.dataIndex]"
										placeholder="请填写参数名称"
									></a-input>
								</div>
								<div v-else-if="'field' === column.dataIndex">
									<a-input
										v-model:value="state.variablesForm[index].columns[subIndex][column.dataIndex]"
										placeholder="请填写数据键名"
									></a-input>
								</div>
								<div v-else-if="'type' === column.dataIndex">
									<a-select
										:options="state.tableVariableTypeOptions"
										v-model:value="state.variablesForm[index].columns[subIndex][column.dataIndex]"
										placeholder="请选择参数类型"
									></a-select>
								</div>
								<div v-else-if="'align' === column.dataIndex">
									<a-select
										:options="state.tableAlignOptions"
										v-model:value="state.variablesForm[index].columns[subIndex][column.dataIndex]"
										placeholder="请选择对齐方式"
									></a-select>
								</div>
								<div v-else-if="'width' === column.dataIndex">
									<a-input-number
										v-model:value="state.variablesForm[index].columns[subIndex][column.dataIndex]"
										placeholder="请填写列宽度"
									></a-input-number>
								</div>
								<template v-else-if="column.dataIndex === 'operation'">
									<div class="table-actions-wrapper">
										<a-button type="text" size="small" @click="onDeleteTableVariable(index, subIndex)">
											<template #icon>
												<NewbieIcon icon="DeleteOutlined"></NewbieIcon>
											</template>
											删除
										</a-button>
									</div>
								</template>
							</template>
						</a-table>
					</div>
				</template>
			</a-table>

			<a-divider></a-divider>

			<div class="text-center">
				<NewbieButton :fetcher="state.variableFetcher" type="primary" @click="onSaveVariable">保存</NewbieButton>
			</div>
		</NewbieModal>
	</div>
</template>

<script setup>
import { NewbieButton, NewbieEdit, NewbieModal, NewbieTable, useTableActions } from "@web/components"
import { useFetch } from "@/js/hooks/web/network"
import { useModalConfirm } from "@/js/hooks/web/interact"
import { useProcessStatusSuccess } from "@/js/hooks/web/form"
import { message } from "ant-design-vue"
import { inject, reactive, ref } from "vue"
import { router } from "@inertiajs/vue3"
import NewbieIcon from "@web/components/NewbieIcon.vue"

const props = defineProps({
	error: { type: String, default: "" },
	parentTemplates: {
		type: Array,
		default: () => [],
	},
})

const list = ref(null)
const edit = ref(null)

const parentOptions = props.parentTemplates.map((item) => ({ label: item.display_name, value: item.id }))
parentOptions.unshift({ label: "全新模板", value: 0 })

const route = inject("route")

const state = reactive({
	showEditor: false,
	url: "",
	parentOptions,
	currentItem: null,

	showVariableEditor: false,
	variablesForm: [],
	variableTypeOptions: [
		{ label: "文本", value: "text" },
		{ label: "表格", value: "table" },
	],
	tableVariableTypeOptions: [
		{ label: "文本", value: "text" },
		{ label: "二维码", value: "qrcode" },
	],
	tableAlignOptions: [
		{ label: "左对齐", value: "left" },
		{ label: "居中", value: "center" },
		{ label: "右对齐", value: "right" },
	],

	variableFetcher: { loading: false },
})

const myFetched = (res) => {
	state.options = res.result
	return {
		items: res.result.map((item) => {
			item.key = item.id
			item.children = item.children
				? item.children.map((child) => {
						child.key = child.id
						return child
				  })
				: null

			return item
		}),
	}
}

const getForm = () => {
	return [
		{
			key: "display_name",
			title: "模板名称",
			required: true,
		},
		{
			key: "parent_id",
			title: "模板原型",
			type: "select",
			options: state.parentOptions,
		},
		{
			key: "description",
			title: "模板描述",
			type: "textarea",
		},
	]
}

const onEdit = (item) => {
	state.url = item ? route("api.manager.print.template.item", { id: item.id }) : ""
	state.showEditor = true
}

const onEditVariables = (item) => {
	state.currentItem = item
	state.variablesForm = item.business_variables || []
	state.showVariableEditor = true
}

const onAddVariable = () => {
	state.variablesForm.push({
		title: "",
		field: "",
		type: "text",
	})
}

const onChangeVariableType = (value, index) => {
	if (value === "table") {
		state.variablesForm[index].columns = []
	} else {
		delete state.variablesForm[index].columns
	}
}

const onDeleteVariable = (index) => {
	state.variablesForm.splice(index, 1)
}

const onSaveVariable = async () => {
	const res = await useFetch(state.variableFetcher).post(route("api.manager.print.template.variable.edit"), {
		id: state.currentItem.id,
		business_variables: state.variablesForm,
	})

	useProcessStatusSuccess(res, () => {
		message.success("保存成功")
		list.value.doFetch()
		state.showVariableEditor = false
	})
}

const getVariableColumns = () => [
	{
		title: "参数名称",
		dataIndex: "title",
		width: 80,
	},
	{
		title: "参数键名",
		dataIndex: "field",
		width: 80,
	},
	{
		title: "参数类型",
		dataIndex: "type",
		width: 80,
	},
	{
		title: "测试数据",
		dataIndex: "data",
		width: 80,
	},
	{
		title: "操作",
		dataIndex: "operation",
		width: 200,
	},
]

const onAddTableVariable = (index) => {
	state.variablesForm[index].columns.push({
		title: "",
		field: "",
		type: "text",
		width: 80,
		align: "left",
	})
}

const onDeleteTableVariable = (index, subIndex) => {
	state.variablesForm[index].columns.splice(subIndex, 1)
}

const getTableVariableColumns = () => [
	{ title: "表头名称", dataIndex: "title", width: 100 },
	{ title: "参数键名", dataIndex: "field", width: 100 },
	{ title: "参数类型", dataIndex: "type", width: 100 },
	{ title: "宽度", dataIndex: "width", width: 100 },
	{ title: "对齐方式", dataIndex: "align", width: 100 },
	{ title: "操作", dataIndex: "operation", width: 100 },
]

const closeEditor = (isRefresh) => {
	if (isRefresh) {
		list.value.doFetch()
	}
	state.showEditor = false
}

const onDelete = (item) => {
	const modal = useModalConfirm(
		`您确认要删除 ${item.display_name} 吗？`,
		async () => {
			try {
				const res = await useFetch().post(route("api.manager.print.template.delete"), { id: item.id })
				modal.destroy()
				useProcessStatusSuccess(res, () => {
					message.success("删除成功")
					list.value.doFetch()
				})
			} catch (e) {
				modal.destroy(e)
			}
		},
		true,
	)
}

const onCopy = (item) => {
	const modal = useModalConfirm(
		`您确认要复制 ${item.display_name} 吗？`,
		async () => {
			try {
				const res = await useFetch().post(route("api.manager.print.template.copy"), { id: item.id })
				modal.destroy()
				useProcessStatusSuccess(res, () => {
					message.success("复制成功")
					list.value.doFetch()
				})
			} catch (e) {
				modal.destroy(e)
			}
		},
		true,
	)
}

const columns = () => {
	return [
		{
			title: "模板名称",
			width: 200,
			dataIndex: "display_name",
			key: "name",
			ellipsis: true,
		},
		{
			title: "模板描述",
			width: 200,
			dataIndex: "description",
			key: "description",
			ellipsis: true,
		},
		{
			title: "操作",
			width: 160,
			key: "operation",
			align: "center",
			fixed: "right",
			customRender({ record }) {
				return useTableActions([
					{
						name: "编辑",
						props: {
							icon: "EditOutlined",
							size: "small",
							auth: "api.manager.print.template.edit",
						},
						action() {
							onEdit(record)
						},
					},
					{
						name: "设计模板",
						props: {
							icon: "FormatPainterOutlined",
							size: "small",
							auth: "api.manager.print.template.design.edit",
						},
						action() {
							router.visit(route("page.manager.print.design", { id: record.id }))
						},
					},

					{
						name: "其它",
						props: {
							size: "small",
						},
						children: [
							{
								name: "复制模板",
								props: {
									icon: "CopyOutlined",
									size: "small",
									auth: "api.manager.print.template.copy",
								},
								action() {
									onCopy(record)
								},
							},
							{
								name: "业务参数",
								props: {
									icon: "AppstoreOutlined",
									size: "small",
									auth: "api.manager.print.template.variable.edit",
								},
								action() {
									onEditVariables(record)
								},
							},
							{
								name: "删除",
								props: {
									icon: "DeleteOutlined",
									size: "small",
									auth: "api.manager.print.template.delete",
								},
								action() {
									onDelete(record)
								},
							},
						],
					},
				])
			},
		},
	]
}
</script>
