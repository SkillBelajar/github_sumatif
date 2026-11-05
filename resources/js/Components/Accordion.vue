<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl shadow mb-3 overflow-hidden">
    <button type="button" @click="isOpen = !isOpen"
      class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
      <span class="font-semibold text-gray-900 dark:text-white text-sm sm:text-base">{{ title }}</span>
      <span class="text-gray-400 transition-transform shrink-0 ml-3"
        :style="{ transform: isOpen ? 'rotate(180deg)' : 'none' }">▾</span>
    </button>
    <div v-if="isOpen" class="px-5 pb-5 pt-1 border-t border-gray-100 dark:border-gray-700">
      <slot />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  title: String,
  open: { type: Boolean, default: false },
})

const isOpen = ref(props.open)
</script>
