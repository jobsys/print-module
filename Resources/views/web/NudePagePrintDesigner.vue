<template>
	<PrintDesigner :variables="variables" :template="template" :print-data="printData" :config="{ id: printTemplate.id }" />
</template>

<script setup>
import PrintDesigner from "./components/PrintDesigner.vue"
import { computed } from "vue"

const props = defineProps({
	printTemplate: {
		type: Object,
		default: () => null,
	},
	printData: {
		type: [Object, Array],
		default: () => ({}),
	},
})

const variables = computed(() => props.printTemplate.business_variables)

const template = computed(() => props.printTemplate.template)
</script>
