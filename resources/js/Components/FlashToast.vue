<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const page = usePage()
const success = computed(() => page.props.flash?.success)
const error = computed(() => page.props.flash?.error)
const importErrors = computed(() => page.props.flash?.import_errors)

import { watch, ref } from 'vue'
const visible = ref(true)
watch([success, error], () => { visible.value = true })
</script>

<template>
  <div v-if="visible && (success || error)" class="fixed top-4 right-4 z-50 max-w-md space-y-2">
    <div v-if="success" class="bg-green-600 text-white px-4 py-3 rounded-lg shadow-lg text-sm flex justify-between items-start gap-4">
      <span>{{ success }}</span>
      <button @click="visible = false" class="font-bold">&times;</button>
    </div>
    <div v-if="error" class="bg-red-600 text-white px-4 py-3 rounded-lg shadow-lg text-sm">
      <div class="flex justify-between items-start gap-4">
        <span>{{ error }}</span>
        <button @click="visible = false" class="font-bold">&times;</button>
      </div>
      <ul v-if="importErrors?.length" class="mt-2 list-disc list-inside text-xs opacity-90">
        <li v-for="(e, i) in importErrors" :key="i">{{ e }}</li>
      </ul>
    </div>
  </div>
</template>
