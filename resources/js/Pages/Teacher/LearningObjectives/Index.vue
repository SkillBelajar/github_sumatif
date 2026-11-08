<template>
  <Layout>
    <template #nav>
      <form @submit.prevent="logout">
        <button type="submit" class="text-sm text-gray-500 hover:text-red-500">Logout</button>
      </form>
    </template>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="flex justify-between items-start mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Tujuan Pembelajaran (TP)</h1>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Kelola TP beserta rubrik KKTP per mata pelajaran.</p>
        </div>
        <button @click="openCreate"
          class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">
          + Tambah TP
        </button>
      </div>

      <!-- List -->
      <div v-if="objectives.length" class="space-y-3">
        <div v-for="lo in objectives" :key="lo.id"
          class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 border border-gray-200 dark:border-gray-700">
          <div class="flex justify-between items-start gap-4">
            <div class="min-w-0">
              <div class="flex items-center gap-2 flex-wrap">
                <span class="px-2 py-0.5 text-xs font-semibold rounded bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-200">
                  {{ lo.subject_name }}
                </span>
                <span class="text-xs text-gray-400">{{ lo.questions_count }} soal</span>
              </div>
              <p class="mt-2 text-sm text-gray-900 dark:text-white whitespace-pre-line">{{ lo.name }}</p>

              <div v-if="lo.kktp_rubric?.length" class="mt-3 grid grid-cols-2 md:grid-cols-4 gap-2">
                <div v-for="(r, i) in lo.kktp_rubric" :key="i"
                  class="rounded-lg bg-gray-50 dark:bg-gray-700 p-2">
                  <p class="text-xs font-semibold text-gray-700 dark:text-gray-200">
                    {{ r.level }}<span v-if="r.min_score !== null && r.min_score !== ''" class="opacity-60"> (≥{{ r.min_score }})</span>
                  </p>
                  <p v-if="r.criteria" class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ r.criteria }}</p>
                </div>
              </div>
            </div>
            <div class="flex gap-2 shrink-0">
              <button @click="openEdit(lo)" class="text-xs px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">Edit</button>
              <button @click="remove(lo)" class="text-xs px-3 py-1.5 border border-red-300 text-red-600 rounded-lg hover:bg-red-50">Hapus</button>
            </div>
          </div>
        </div>
      </div>
      <div v-else class="bg-white dark:bg-gray-800 rounded-xl shadow p-10 text-center">
        <div class="text-4xl mb-3">🎯</div>
        <p class="text-gray-500 dark:text-gray-400">Belum ada Tujuan Pembelajaran. Klik "Tambah TP" untuk memulai.</p>
      </div>
    </div>

    <!-- Modal form -->
    <div v-if="showModal" class="fixed inset-0 z-40 bg-black/50 flex items-start justify-center p-4 overflow-y-auto">
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-2xl my-8">
        <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h2 class="font-semibold text-gray-900 dark:text-white">{{ editing ? 'Edit TP' : 'Tambah TP' }}</h2>
          <button @click="closeModal" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>

        <form @submit.prevent="submit" class="p-6 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Mata Pelajaran *</label>
            <select v-model="form.subject_id"
              class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
              <option value="" disabled>Pilih mata pelajaran</option>
              <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
            <p v-if="form.errors.subject_id" class="mt-1 text-xs text-red-600">{{ form.errors.subject_id }}</p>
            <p v-if="!subjects.length" class="mt-2 text-xs text-amber-600">
              Belum ada mata pelajaran.
              <a href="/teacher/subjects" class="font-medium text-indigo-600 hover:text-indigo-500 underline">Tambah mata pelajaran dulu</a>
            </p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Deskripsi TP *</label>
            <textarea v-model="form.name" rows="3" placeholder="Contoh: Peserta didik mampu menyelesaikan operasi hitung bilangan bulat."
              class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"></textarea>
            <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
          </div>

          <div>
            <div class="flex justify-between items-center mb-2">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Rubrik KKTP</label>
              <button type="button" @click="addLevel" class="text-xs text-indigo-600 hover:underline">+ Tambah level</button>
            </div>
            <div class="space-y-2">
              <div v-for="(r, i) in form.kktp_rubric" :key="i" class="flex gap-2 items-start">
                <input v-model="r.level" placeholder="Level (mis. Baik)" class="w-32 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs" />
                <input v-model="r.min_score" type="number" min="0" max="100" placeholder="Nilai" class="w-20 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs" />
                <input v-model="r.criteria" placeholder="Kriteria" class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs" />
                <button type="button" @click="form.kktp_rubric.splice(i, 1)" class="text-red-500 text-sm px-1">&times;</button>
              </div>
              <p v-if="!form.kktp_rubric.length" class="text-xs text-gray-400">Belum ada level rubrik.</p>
            </div>
          </div>

          <div class="flex justify-end gap-2 pt-2">
            <button type="button" @click="closeModal" class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg">Batal</button>
            <button type="submit" :disabled="form.processing"
              class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50">
              {{ editing ? 'Simpan Perubahan' : 'Simpan' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Layout from '../../../Plugins/InertiaLayout.vue'

const props = defineProps({
  objectives: { type: Array, default: () => [] },
  subjects: { type: Array, default: () => [] },
})

const DEFAULT_LEVELS = ['Perlu Bimbingan', 'Cukup', 'Baik', 'Sangat Baik']

const showModal = ref(false)
const editing = ref(null)
const form = useForm({ subject_id: '', name: '', kktp_rubric: [] })

function blankRubric() {
  return DEFAULT_LEVELS.map((level, i) => ({ level, min_score: [60, 70, 80, 90][i], criteria: '' }))
}

function openCreate() {
  editing.value = null
  form.reset()
  form.clearErrors()
  form.kktp_rubric = blankRubric()
  showModal.value = true
}

function openEdit(lo) {
  editing.value = lo
  form.clearErrors()
  form.subject_id = lo.subject_id ?? ''
  form.name = lo.name
  form.kktp_rubric = lo.kktp_rubric?.length
    ? lo.kktp_rubric.map(r => ({ level: r.level, min_score: r.min_score ?? '', criteria: r.criteria ?? '' }))
    : blankRubric()
  showModal.value = true
}

function addLevel() {
  form.kktp_rubric.push({ level: '', min_score: '', criteria: '' })
}

function closeModal() {
  showModal.value = false
}

function submit() {
  if (editing.value) {
    form.put(`/teacher/learning-objectives/${editing.value.id}`, { onSuccess: closeModal })
  } else {
    form.post('/teacher/learning-objectives', { onSuccess: closeModal })
  }
}

function remove(lo) {
  if (confirm(`Hapus TP "${lo.name.slice(0, 50)}..."? Soal terkait juga akan terhapus.`)) {
    useForm({}).delete(`/teacher/learning-objectives/${lo.id}`)
  }
}

function logout() {
  useForm({}).post('/logout')
}
</script>
