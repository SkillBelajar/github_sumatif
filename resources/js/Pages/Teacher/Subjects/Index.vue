<template>
  <Layout>
    <template #nav>
      <form @submit.prevent="logout">
        <button type="submit" class="text-sm text-gray-500 hover:text-red-500">Logout</button>
      </form>
    </template>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Mata Pelajaran</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          Subject milikmu. Pilih ini saat membuat Tujuan Pembelajaran atau soal.
        </p>
      </div>

      <div v-if="$page.props.flash?.success" class="mb-4 rounded-xl bg-green-50 dark:bg-green-900/30 px-4 py-3 text-sm text-green-800 dark:text-green-200">
        {{ $page.props.flash.success }}
      </div>
      <div v-if="$page.props.flash?.error" class="mb-4 rounded-xl bg-red-50 dark:bg-red-900/30 px-4 py-3 text-sm text-red-800 dark:text-red-200">
        {{ $page.props.flash.error }}
      </div>

      <form @submit.prevent="add" class="mb-6 flex flex-wrap gap-2">
        <input v-model="name" type="text" placeholder="Nama mata pelajaran (mis. Matematika)"
          class="flex-1 min-w-[200px] rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" />
        <input v-model="code" type="text" placeholder="Kode (opsional)"
          class="w-32 rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" />
        <button type="submit" :disabled="!name.trim()"
          class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white text-sm font-medium rounded-xl">
          + Mata Pelajaran
        </button>
      </form>

      <p v-if="!subjects.length" class="text-gray-500 dark:text-gray-400 text-sm">Belum ada mata pelajaran.</p>

      <ul class="space-y-2">
        <li v-for="s in subjects" :key="s.id"
          class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 flex items-center justify-between">
          <div>
            <span class="font-medium text-gray-900 dark:text-white">{{ s.name }}</span>
            <span v-if="s.code" class="ml-2 text-xs text-gray-500 dark:text-gray-400">{{ s.code }}</span>
            <span class="ml-2 text-xs text-gray-400">{{ s.objectives_count }} TP</span>
          </div>
          <button @click="remove(s)" class="text-xs text-red-500 hover:text-red-700">Hapus</button>
        </li>
      </ul>
    </div>
  </Layout>
</template>

<script setup>
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import Layout from '../../../Plugins/InertiaLayout.vue'

const props = defineProps({ subjects: { type: Array, default: () => [] } })

const name = ref('')
const code = ref('')

function add() {
  useForm({ name: name.value.trim(), code: code.value.trim() })
    .post('/teacher/subjects', {
      onSuccess: () => { name.value = ''; code.value = '' },
    })
}

function remove(s) {
  if (!confirm(`Hapus mata pelajaran "${s.name}"?`)) return
  router.delete(`/teacher/subjects/${s.id}`)
}
</script>
