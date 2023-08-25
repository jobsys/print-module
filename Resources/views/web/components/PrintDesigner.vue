<template>
	<div>
		<a-card v-show="!isPreviewOnly">
			<a-row :gutter="[8, 0]" style="margin-bottom: 10px">
				<a-col :span="20">
					<a-space>
						<!-- 纸张设置 -->
						<a-button-group>
							<a-button
								v-for="(value, type) in state.paperTypes"
								:type="currentPageType === type ? 'primary' : 'info'"
								@click="setPaper(type, value)"
								:key="type"
							>
								{{ type }}
							</a-button>
							<a-popover v-model="state.paperPopVisible" title="设置纸张宽高(mm)" trigger="click">
								<template #content>
									<a-input-group compact style="margin: 10px 10px">
										<a-input
											type="number"
											v-model="state.paperWidth"
											style="width: 100px; text-align: center"
											placeholder="宽(mm)"
										/>
										<a-input
											style="width: 30px; border-left: 0; pointer-events: none; background-color: #fff"
											placeholder="~"
											disabled
										/>
										<a-input
											type="number"
											v-model="state.paperHeight"
											style="width: 100px; text-align: center; border-left: 0"
											placeholder="高(mm)"
										/>
									</a-input-group>
									<a-button type="primary" style="width: 100%" @click="otherPaper">确定</a-button>
								</template>
								<a-button :type="'other' === currentPageType ? 'primary' : ''">自定义纸张</a-button>
							</a-popover>
						</a-button-group>
						<a-button type="text" @click="changeScale(false)">
							<template #icon>
								<ZoomOutOutlined />
							</template>
						</a-button>
						<a-input-number
							:value="state.scale.value"
							:min="state.scale.min"
							:max="state.scale.max"
							:step="0.1"
							disabled
							style="width: 70px"
							:formatter="(value) => `${(value * 100).toFixed(0)}%`"
							:parser="(value) => value.replace('%', '')"
						/>
						<a-button type="text" @click="changeScale(true)">
							<template #icon>
								<ZoomInOutlined />
							</template>
						</a-button>
						<!-- 预览/打印 -->
						<a-button-group>
							<a-button type="primary" @click="onPreview">
								<template #icon>
									<EyeOutlined />
								</template>
								预览
							</a-button>
							<a-button type="primary" @click="onPrint">
								<template #icon>
									<PrinterOutlined />
								</template>
								直接打印
							</a-button>
						</a-button-group>
						<!-- 保存/清空 -->
						<a-button-group>
							<NewbieButton type="primary" :fetcher="state.saveFetcher" icon="SaveOutlined" @click="onSave"> 保存 </NewbieButton>
							<a-popconfirm title="是否确认清空?" @confirm="clearPaper">
								<template #icon>
									<QuestionCircleOutlined style="color: red" />
								</template>
								<a-button type="danger">
									清空
									<template #icon>
										<CloseOutlined />
									</template>
								</a-button>
							</a-popconfirm>
						</a-button-group>
					</a-space>
				</a-col>
			</a-row>
			<a-row :gutter="[8, 0]">
				<a-col :span="4">
					<a-card>
						<a-row>
							<a-col :span="24" class="rect-printElement-types elementContainerRef" ref="elementContainerRef"></a-col>
						</a-row>
					</a-card>
				</a-col>
				<a-col :span="14">
					<a-card class="card-design">
						<div id="designerContainerRef" class="hiprint-printTemplate" ref="designerContainerRef"></div>
					</a-card>
				</a-col>
				<a-col :span="6" class="params_setting_container">
					<a-card>
						<a-row class="hiprint-layout-side">
							<div id="PrintElementOptionSetting"></div>
						</a-row>
					</a-card>
				</a-col>
			</a-row>
		</a-card>

		<a-modal
			:open="state.preview.visible"
			:mask-closable="false"
			:body-style="{ padding: 0, minHeight: '300px' }"
			:style="{ top: isPreviewOnly ? 0 : `20px` }"
			:width="`${state.currentPaper.width}mm`"
			:mask-style="{ backgroundColor: isPreviewOnly ? '#333' : 'rgba(0, 0, 0, .5)' }"
			:closable="!isPreviewOnly"
			:footer="null"
			@cancel="state.preview.visible = false"
		>
			<a-spin :spinning="state.preview.isLoading" style="min-height: 300px">
				<div v-html="state.preview.html" ref="previewHtmlRef"></div>
			</a-spin>
			<template #title>
				<a-space>
					<div style="margin-right: 20px">打印预览</div>
					<a-button :loading="state.preview.isWaiting" type="primary" @click.stop="onPreviewPrint">
						<template #icon>
							<PrinterOutlined />
						</template>
						打印
					</a-button>
					<a-button type="primary" @click.stop="onSaveToPdf">
						<template #icon>
							<SaveOutlined />
						</template>
						另存为 PDF
					</a-button>
				</a-space>
			</template>
		</a-modal>
	</div>
</template>

<script setup>
import { computed, inject, onMounted, reactive, ref } from "vue"
import {
	CloseOutlined,
	EyeOutlined,
	PrinterOutlined,
	QuestionCircleOutlined,
	SaveOutlined,
	ZoomInOutlined,
	ZoomOutOutlined,
} from "@ant-design/icons-vue"
import { NewbieButton } from "jobsys-newbie"
import { message } from "ant-design-vue"
import { hiprint } from "vue-plugin-hiprint"
import { useFetch } from "jobsys-newbie/hooks"
import { isObject } from "lodash-es"

const route = inject("route")

const props = defineProps({
	variables: { type: Array, default: () => [] },
	template: { type: Object, default: () => ({}) },
	printData: { type: [Object, Array], default: () => null },
	config: { type: Object, default: () => ({}) },
})

let hiprintTemplate

const elementContainerRef = ref(null)
const designerContainerRef = ref(null)
const previewHtmlRef = ref(null)

const state = reactive({
	currentPaper: {
		type: "A4",
		width: 210,
		height: 296.6,
	},
	// 纸张类型
	paperTypes: {
		A3: {
			width: 420,
			height: 296.6,
		},
		A4: {
			width: 210,
			height: 296.6,
		},
		A5: {
			width: 210,
			height: 147.6,
		},
		B3: {
			width: 500,
			height: 352.6,
		},
		B4: {
			width: 250,
			height: 352.6,
		},
		B5: {
			width: 250,
			height: 175.6,
		},
	},

	scale: {
		value: 1,
		max: 5,
		min: 0.5,
	},
	// 自定义纸张
	paperPopVisible: false,
	paperWidth: 220,
	paperHeight: 80,
	// 打印预览
	preview: {
		visible: false,
		isLoading: true,
		isWaiting: false,
		printData: props.printData || {},
		html: "",
	},

	saveFetcher: { loading: false },
})

const commonTableParams = {
	editable: true,
	columnDisplayEditable: true, //列显示是否能编辑
	columnDisplayIndexEditable: true, //列顺序显示是否能编辑
	columnTitleEditable: true, //列标题是否能编辑
	columnResizable: true, //列宽是否能调整
	columnAlignEditable: true, //列对齐是否调整
	isEnableEditField: true, //编辑字段
	isEnableContextMenu: true, //开启右键菜单 默认true
	isEnableInsertRow: true, //插入行
	isEnableDeleteRow: true, //删除行
	isEnableInsertColumn: true, //插入列
	isEnableDeleteColumn: true, //删除列
	isEnableMergeCell: true, //合并单元格
}

console.log("printData", props.printData)

const isPreviewOnly = computed(
	() =>
		props.printData &&
		((isObject(props.printData) && Object.keys(props.printData).length) || (Array.isArray(props.printData) && props.printData.length)),
)

console.log("isPreviewOnly", isPreviewOnly)

const currentPageType = computed(() => {
	let type = "other"
	const types = state.paperTypes

	Object.keys(types).forEach((key) => {
		const item = types[key]
		const { width, height } = state.currentPaper
		if (item.width === width && item.height === height) {
			type = key
		}
	})
	return type
})

/**
 * 设置纸张大小
 * @param type [A3, A4, A5, B3, B4, B5, other]
 * @param {Object} value
 */
const setPaper = (type, value) => {
	try {
		if (state.paperTypes[type]) {
			state.currentPaper = { type, width: value.width, height: value.height }
			hiprintTemplate.setPaper(value.width, value.height)
		} else {
			state.currentPaper = { type: "other", width: value.width, height: value.height }
			hiprintTemplate.setPaper(value.width, value.height)
		}
	} catch (error) {
		message.error(`操作失败: ${error}`)
	}
}

const changeScale = (big) => {
	let scaleValue = state.scale.value
	if (big) {
		scaleValue += 0.1
		if (scaleValue > state.scale.max) scaleValue = 5
	} else {
		scaleValue -= 0.1
		if (scaleValue < state.scale.min) scaleValue = 0.5
	}
	if (hiprintTemplate) {
		// scaleValue: 放大缩小值, false: 不保存(不传也一样), 如果传 true, 打印时也会放大
		hiprintTemplate.zoom(scaleValue, true)
		state.scale.value = scaleValue
	}
}

const otherPaper = () => {
	const value = {
		width: state.paperWidth,
		height: state.paperHeight,
	}
	state.paperPopVisible = false
	setPaper("other", value)
}

const clearPaper = () => {
	try {
		hiprintTemplate.clear()
	} catch (error) {
		message.error(`操作失败: ${error}`)
	}
}

const onPreview = () => {
	state.preview.visible = true
	state.preview.isLoading = true
	setTimeout(() => {
		state.preview.html = hiprintTemplate.getHtml(state.preview.printData).html()
		state.preview.isLoading = false
	}, 1000)
}

const onSave = async () => {
	const res = await useFetch(state.saveFetcher).post(route("api.manager.print.template.design.edit"), {
		id: props.config.id,
		template: hiprintTemplate.getJson(),
		template_html: hiprintTemplate.getHtml(state.preview.printData).html(),
	})

	if (res.status === "SUCCESS") {
		message.success("保存成功")
	}
}

const onSaveToPdf = () => {
	hiprintTemplate.toPdf(state.preview.printData, "打印预览")
}

const onPreviewPrint = () => {
	state.preview.isWaiting = true
	hiprintTemplate.print(
		state.preview.printData,
		{},
		{
			callback: () => {
				state.preview.isWaiting = false
			},
		},
	)
}

const onPrint = () => {
	hiprintTemplate.print(state.preview.printData)
}

const createProviders = () => {
	const addElementTypes = (context) => {
		const defaultProviders = [
			new hiprint.PrintElementTypeGroup("通用", [
				{
					tid: "defaultProviderModule.hline",
					title: "横线",
					type: "hline",
				},
				{
					tid: "defaultProviderModule.vline",
					title: "竖线",
					type: "vline",
				},
				{
					tid: "defaultProviderModule.rect",
					title: "矩形",
					type: "rect",
				},
				{
					tid: "defaultProviderModule.oval",
					title: "椭圆",
					type: "oval",
				},
				{
					tid: "defaultProviderModule.customText",
					title: "文本",
					customText: "自定义文本",
					custom: true,
					type: "text",
				},
				{
					tid: "defaultProviderModule.longText",
					title: "长文本",
					type: "longText",
					formatter(title, value, options, templateData) {
						templateData = templateData || {}
						//使用则表达式将符合 ${value} 格式的多个内容替换为 templateData 中的 value
						return title.replace(/\$\{(\w+)\}/g, (match, key) => {
							return templateData[key] || ""
						})
					},
					options: {
						width: 200,
						testData: "这里的数据很长",
					},
				},
				{
					tid: "defaultProviderModule.barcode",
					title: "条形码",
					type: "text",
					options: {
						testData: "XS888888888",
						height: 32,
						fontSize: 12,
						lineHeight: 18,
						fontWeight: "700",
						textAlign: "left",
						textContentVerticalAlign: "middle",
						textType: "barcode",
					},
				},
				{
					tid: "defaultProviderModule.qrcode",
					title: "二维码",
					data: "XS888888888",
					type: "text",
					options: {
						testData: "XS888888888",
						height: 32,
						fontSize: 12,
						lineHeight: 18,
						fontWeight: "700",
						textAlign: "left",
						textContentVerticalAlign: "middle",
						textType: "qrcode",
					},
				},
				{
					tid: "defaultProviderModule.table",
					title: "表格",
					type: "table",
					columns: [[{}]],
					...commonTableParams,
				},

				{
					tid: "defaultProviderModule.image",
					title: "图片",
					type: "image",
					options: {
						src: `data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAYAAACqaXHeAAAAAXNSR0IArs4c6QAAAwxJREFUeF7tmj9oFEEUxt+qJzF30YjRC9joIZoi54GFVdC73uIKi5DCMrViJQhJQLASrVOmCCksrrDPRVJZCJsNcoqcNkIuRo3m7hSPsPIkK7m4OzO7s28zw87AVjt/3veb780b7taClDcr5frBADAOSDkBkwIpN4A5BE0KmBRIOQGhFLB33FnLgpsAUNaEV91yYa44ZNV58XIB7Imf4U2k4nvXhbnSkDXLio0JwNlxy64FyyqKE43JcqHCcgITgM677wHiuYAJYK3t4u7rkvdBpqhfzVmVoJfSADLOOuDTnZoUdWXS/WgAoOiTDx72ifnx+BH0iuNJC+StRwNgcHEJTiwu/bf4lxc1XkBJv6cBcOZW1VeIgi6gAYD2xzQ42FLjAL8z4OfUpIqHIY0DcOe9CnDMWQcUL3sALmw0AB9sd0bH/j4xNDoAMQT3b4r771fBbm/1TVnKjcCTSxOyy6gPwE+8pzoGCGoDYImPCYK6AETExwBBTQBhxEtCSA4AnuDeQYYnOOavX4siXgJCMgD8RPmVMhnxESHQA2CJ2g8hDvERINACEBHlpcLBOi9b4AVLJB0AEfGyInnjBSDQAFBBvGA6xA9AJfECEOIFoKJ4DoT4AKgsngEhHgA6iA+AIA9AJ/EehH33DzkACxuNsvcjhV9J2m13oLfZ4lWrvvdHslk4ns+HGhOl8x6E6ACm39ofap+bF1iLd9acKLHBQKEAR3PZSGPDDKqeLXycv1K6GDSG+cfIxOvlb++628OsBX+3WtBrbYaJCTL5c4k4AIO6PDi8vXqtcpoMQCjlh9DZADAOIE4BrAK7nTapuWUqBnkK/Go2ASFQNpmKQQ4gShUIAwtLpcy9gRxAGDGH0VcKwG3nVf3l90/4dZi27cap8yvPi9cDv3JhXoSmG/bd2lbzqbbqAaA6Urg3P1Z6FukihIN0dgFv91Ef9ztB7IROeNP9OsO7FqviFMz70UzWZlnfi1UIgCrCKOIwACio6jSncYBOu0URq3EABVWd5jQO0Gm3KGI1DqCgqtOcfwCkbM5QNMEp0gAAAABJRU5ErkJggg==`,
					},
				},
			]),
		]

		if (props.variables && props.variables.length) {
			const providers = []
			props.variables.forEach((variable) => {
				if (variable.type === "table") {
					variable.columns = [variable.columns]
					const provider = {
						tid: `defaultProviderModule.${Math.random()}`,
						...variable,
						...commonTableParams,
					}
					providers.push(provider)
				} else {
					const options = {}
					;["field", "data", "title"].forEach((key) => {
						if (Object.hasOwn(variable, key)) {
							if (key === "data") {
								options.testData = variable.data
							} else {
								options[key] = variable[key]
							}
						}
					})

					const provider = {
						tid: `defaultProviderModule.${Math.random()}`,
						options,
						...variable,
					}
					providers.push(provider)
				}
			})

			defaultProviders.push(new hiprint.PrintElementTypeGroup("业务变量", providers))
		}

		context.removePrintElementTypes("defaultProviderModule")
		context.addPrintElementTypes("defaultProviderModule", defaultProviders)
	}
	return {
		addElementTypes,
	}
}

const init = () => {
	hiprint.setConfig({ panel: { default: { paperHeader: 20 } } })
	hiprint.init({
		providers: [createProviders()],
	})
	elementContainerRef.value.innerHTML = ""
	hiprint.PrintElementTypeManager.build(".elementContainerRef", "defaultProviderModule")
	designerContainerRef.value.innerHTML = ""

	// 手动设置页眉页脚
	if (props.template) {
		if (props.template.panels.length && !props.template.panels[0].paperHeader) {
			props.template.panels[0].paperHeader = 49.5
			props.template.panels[0].paperFooter = 780
		}
	}

	hiprintTemplate = new hiprint.PrintTemplate({
		template: props.template,
		settingContainer: "#PrintElementOptionSetting",
		paginationContainer: ".hiprint-printPagination",
		onImageChooseClick: (target) => {
			// 创建input，模拟点击，然后 target.refresh 更新
			const input = document.createElement("input")
			input.setAttribute("type", "file")
			input.click()
			input.onchange = (e) => {
				const { files } = e.target
				const file = files[0]
				if (file) {
					const reader = new FileReader()
					//通过文件流行文件转换成Base64字行串
					reader.readAsDataURL(file)
					//转换成功后
					reader.onloadend = () => {
						// 通过 target.refresh 更新图片元素
						target.refresh(reader.result)
					}
				}
			}
			input.remove()
		},
	})
	hiprintTemplate.design("#designerContainerRef")

	// 获取当前放大比例, 当zoom时传true 才会有
	state.scaleValue = hiprintTemplate.editingPanel.scale || 1

	if (isPreviewOnly.value) {
		onPreview()
	}

	console.log(hiprintTemplate)
}

onMounted(() => {
	init()
})
</script>
<style scoped lang="less">
:deep(.hiprint-printElement-type > li > ul > li > a) {
	padding: 4px 4px;
	color: #1296db;
	line-height: 1;
	height: auto !important;
	text-overflow: ellipsis;
}

:deep(.hiprint-printPaper) {
	z-index: 0;
}
</style>
