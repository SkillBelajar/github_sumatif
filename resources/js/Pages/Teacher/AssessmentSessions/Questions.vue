<template>
  <Layout>
    <template #nav>
      <form @submit.prevent="logout">
        <button type="submit" class="text-sm text-gray-500 hover:text-red-500">Logout</button>
      </form>
    </template>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="flex flex-wrap justify-between items-start gap-3 mb-6">
        <div>
          <a href="/teacher/assessments" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">&larr; Sesi Asesmen</a>
          <h1 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ session.name }}</h1>
          <div class="mt-1 flex items-center gap-2 flex-wrap text-sm text-gray-500 dark:text-gray-400">
            <span :class="statusClass(session.status)" class="px-2 py-0.5 text-xs font-semibold rounded">
              {{ statusLabel(session.status) }}
            </span>
            <span>Token: <span class="font-mono font-semibold text-gray-800 dark:text-gray-200">{{ session.token }}</span></span>
            <span>· {{ session.duration_minutes }} menit</span>
          </div>
        </div>
        <div class="flex flex-wrap gap-2">
          <button @click="saveQuestions" :disabled="form.processing"
            class="px-4 py-2 border border-indigo-300 text-indigo-700 dark:text-indigo-300 text-sm font-medium rounded-lg hover:bg-indigo-50 dark:hover:bg-gray-700 disabled:opacity-50">
            Simpan Pilihan Soal
          </button>
          <button v-if="session.status !== 'active'" @click="activate" :disabled="!selected.length"
            class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 disabled:opacity-40">
            Aktifkan Ujian
          </button>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 mb-5">
        <div class="flex items-center justify-between gap-3">
          <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
            Soal terpilih: <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ selected.length }}</span>
          </p>
          <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer select-none">
            <input type="checkbox" :checked="allVisibleChecked" :disabled="!allVisibleIds.length"
              @change="toggleAll"
              class="rounded border-gray-300 dark:border-gray-600 text-indigo-600" />
            {{ filter.per_page === 'all' ? 'Centang semua soal (' + (questions.total ?? allVisibleIds.length) + ')' : 'Centang semua soal yang tampil' }}
          </label>
        </div>
      </div>

      <!-- Filter -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 mb-5 grid grid-cols-1 md:grid-cols-4 gap-3">
        <select v-model="filter.subject_id" @change="applyFilter"
          class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
          <option value="">Semua Mata Pelajaran</option>
          <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
        </select>
        <select v-model="filter.learning_objective_id" @change="applyFilter"
          class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
          <option value="">Semua TP</option>
          <option v-for="o in filteredObjectives" :key="o.id" :value="o.id">
            {{ o.subject_name }} — {{ o.name }}
          </option>
        </select>
        <select v-model="filter.type" @change="applyFilter"
          class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
          <option value="">Semua Tipe</option>
          <option v-for="(label, key) in types" :key="key" :value="key">{{ label }}</option>
        </select>
        <select v-model="filter.per_page" @change="applyFilter"
          class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
          <option value="20">Tampilkan: 20</option>
          <option value="50">Tampilkan: 50</option>
          <option value="100">Tampilkan: 100</option>
          <option value="all">Tampilkan: Semua</option>
        </select>
        <button @click="resetFilter" class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
          Reset Filter
        </button>
      </div>

      <!-- List soal -->
      <div v-if="questions.data.length" class="space-y-3">
        <label v-for="q in questions.data" :key="q.id"
          :class="['block bg-white dark:bg-gray-800 rounded-xl shadow p-5 border cursor-pointer transition',
            selected.includes(q.id) ? 'border-indigo-500 ring-1 ring-indigo-500' : 'border-gray-200 dark:border-gray-700']">
          <div class="flex items-start gap-3">
            <input type="checkbox" :value="q.id" v-model="selected"
              class="mt-1 rounded border-gray-300 dark:border-gray-600 text-indigo-600" />
            <div class="min-w-0 flex-1">
              <div class="flex items-center gap-2 flex-wrap">
                <span class="px-2 py-0.5 text-xs font-semibold rounded bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-200">
                  {{ q.subject_name }}
                </span>
                <span class="px-2 py-0.5 text-xs font-semibold rounded bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                  {{ q.type_label }}
                </span>
                <span class="text-xs text-gray-400">{{ q.phase }}</span>
              </div>
              <p class="mt-2 text-sm text-gray-900 dark:text-white whitespace-pre-line">{{ q.content }}</p>
              <p class="mt-1 text-xs text-gray-400">TP: {{ q.objective_name }}</p>
            </div>
          </div>
        </label>

        <div class="flex gap-2 justify-center pt-4">
          <button v-for="link in questions.links" :key="link.label" v-html="link.label"
            :disabled="!link.url" @click="goPage(link.url)"
            :class="['px-3 py-1.5 text-sm rounded-lg border',
              link.active
                ? 'bg-indigo-600 text-white border-indigo-600'
                : 'border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700']" />
        </div>
      </div>
      <div v-else class="bg-white dark:bg-gray-800 rounded-xl shadow p-10 text-center">
        <div class="text-4xl mb-3">📚</div>
        <p class="text-gray-500 dark:text-gray-400">Belum ada soal yang cocok dengan filter.</p>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import Layout from '../../../Plugins/InertiaLayout.vue'
import { router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const props = defineProps({
  session: Object,
  questions: Object,
  objectives: Array,
  subjects: Array,
  types: Object,
  filters: Object,
})

const selected = ref([...props.session.selected_question_ids])
const filter = ref({
  subject_id: props.filters.subject_id ?? '',
  learning_objective_id: props.filters.learning_objective_id ?? '',
  type: props.filters.type ?? '',
  per_page: props.filters.per_page ?? '20',
})

const form = ref({ processing: false })

const filteredObjectives = computed(() =>
  filter.value.subject_id
    ? props.objectives.filter(o => o.subject_id === Number(filter.value.subject_id))
    : props.objectives
)

const allVisibleIds = computed(() => props.questions.data.map(q => q.id))
const allVisibleChecked = computed(() =>
  allVisibleIds.value.length > 0 &&
  allVisibleIds.value.every(id => selected.value.includes(id))
)

function toggleAll() {
  if (allVisibleChecked.value) {
    selected.value = selected.value.filter(id => !allVisibleIds.value.includes(id))
  } else {
    selected.value = [...new Set([...selected.value, ...allVisibleIds.value])]
  }
}

function statusLabel(s) {
  return { draft: 'Draft', active: 'Aktif', closed: 'Ditutup' }[s] ?? s
}

function statusClass(s) {
  return {
    draft: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
    active: 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-200',
    closed: 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-200',
  }[s]
}

function applyFilter() {
  router.get('/teacher/assessments/' + props.session.id + '/questions', filter.value, {
    preserveState: true,
    replace: true,
  })
}

function resetFilter() {
  filter.value = { subject_id: '', learning_objective_id: '', type: '', per_page: '20' }
  applyFilter()
}

function goPage(url) {
  if (url) router.get(url, {}, { preserveState: true })
}

function saveQuestions() {
  form.value.processing = true
  router.post('/teacher/assessments/' + props.session.id + '/questions', {
    question_ids: selected.value,
  }, {
    preserveScroll: true,
    onFinish: () => (form.value.processing = false),
  })
}

function activate() {
  if (!confirm('Aktifkan ujian ini? Siswa bisa mulai mengerjakan dengan token.')) return
  router.post('/teacher/assessments/' + props.session.id + '/activate', {}, { preserveScroll: true })
}

function logout() {
  router.post('/logout')
}
</script>
