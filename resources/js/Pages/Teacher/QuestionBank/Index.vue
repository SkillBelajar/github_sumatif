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
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Bank Soal</h1>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Kelola soal per Tujuan Pembelajaran.</p>
        </div>
        <div class="flex flex-wrap gap-2">
          <a :href="'/teacher/question-bank/template'"
            class="px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
            Unduh Template
          </a>
          <button @click="importOpen = true"
            class="px-3 py-2 text-sm border border-indigo-300 text-indigo-700 dark:text-indigo-300 rounded-lg hover:bg-indigo-50 dark:hover:bg-gray-700">
            Import Soal
          </button>
          <button @click="openCreate"
            class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">
            + Tambah Soal
          </button>
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
        <button @click="resetFilter" class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
          Reset Filter
        </button>
      </div>

      <!-- List soal -->
      <div v-if="questions.data.length" class="space-y-3">
        <div v-for="q in questions.data" :key="q.id"
          class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 border border-gray-200 dark:border-gray-700">
          <div class="flex justify-between items-start gap-4">
            <div class="min-w-0 flex-1">
              <div class="flex flex-wrap items-center gap-2">
                <span class="px-2 py-0.5 text-xs font-semibold rounded bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-200">{{ q.type_label }}</span>
                <span class="px-2 py-0.5 text-xs rounded bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">{{ q.subject_name }}</span>
                <span class="text-xs text-gray-400">{{ q.phase }}</span>
              </div>
              <p class="mt-2 text-sm text-gray-900 dark:text-white">{{ q.content }}</p>

              <ul v-if="q.options" class="mt-2 space-y-0.5">
                <li v-for="(text, key) in q.options" :key="key" class="text-xs"
                  :class="key === q.correct_answer ? 'text-green-700 dark:text-green-400 font-semibold' : 'text-gray-500 dark:text-gray-400'">
                  {{ key }}. {{ text }}<span v-if="key === q.correct_answer"> ✓</span>
                </li>
              </ul>
              <p v-else class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                Kunci: <span class="font-semibold text-green-700 dark:text-green-400">{{ q.correct_answer }}</span>
              </p>

              <p class="mt-1 text-xs text-gray-400">TP: {{ q.objective_name }}</p>
            </div>
            <div class="flex gap-2 shrink-0">
              <button @click="openEdit(q)" class="text-xs px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">Edit</button>
              <button @click="remove(q)" class="text-xs px-3 py-1.5 border border-red-300 text-red-600 rounded-lg hover:bg-red-50">Hapus</button>
            </div>
          </div>
        </div>
      </div>
      <div v-else class="bg-white dark:bg-gray-800 rounded-xl shadow p-10 text-center">
        <div class="text-4xl mb-3">📚</div>
        <p class="text-gray-500 dark:text-gray-400">Belum ada soal. Tambah manual atau import dari CSV.</p>
      </div>

      <!-- Pagination -->
      <div v-if="questions.links?.length > 3" class="mt-5 flex flex-wrap gap-1 justify-center">
        <template v-for="(l, i) in questions.links" :key="i">
          <a v-if="l.url" :href="l.url"
            :class="['px-3 py-1.5 text-sm rounded border', l.active ? 'bg-indigo-600 text-white border-indigo-600' : 'border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700']"
            v-html="l.label"></a>
          <span v-else class="px-3 py-1.5 text-sm text-gray-400" v-html="l.label"></span>
        </template>
      </div>
    </div>

    <!-- Modal tambah/edit soal -->
    <div v-if="showModal" class="fixed inset-0 z-40 bg-black/50 flex items-start justify-center p-4 overflow-y-auto">
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-2xl my-8">
        <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h2 class="font-semibold text-gray-900 dark:text-white">{{ editing ? 'Edit Soal' : 'Tambah Soal' }}</h2>
          <button @click="closeModal" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>

        <form @submit.prevent="submit" class="p-6 space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tujuan Pembelajaran *</label>
              <select v-model="form.learning_objective_id"
                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                <option value="" disabled>Pilih TP</option>
                <option v-for="o in objectives" :key="o.id" :value="o.id">{{ o.subject_name }} — {{ o.name }}</option>
              </select>
              <p v-if="form.errors.learning_objective_id" class="mt-1 text-xs text-red-600">{{ form.errors.learning_objective_id }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fase *</label>
              <select v-model="form.phase"
                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                <option v-for="p in phases" :key="p" :value="p">{{ p }}</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipe Soal *</label>
            <select v-model="form.type" @change="onTypeChange"
              class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
              <option v-for="(label, key) in types" :key="key" :value="key">{{ label }}</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Soal *</label>
            <textarea v-model="form.content" rows="3"
              class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"></textarea>
            <p v-if="form.errors.content" class="mt-1 text-xs text-red-600">{{ form.errors.content }}</p>
          </div>

          <!-- Opsi dinamis: hanya pilihan ganda -->
          <div v-if="form.type === 'multiple_choice'" class="space-y-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Pilihan Jawaban</label>
            <div v-for="key in ['A','B','C','D']" :key="key" class="flex items-center gap-2">
              <span class="w-6 text-sm font-semibold text-gray-500">{{ key }}</span>
              <input v-model="form.options[key]" :placeholder="`Opsi ${key}`"
                class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" />
            </div>
            <p v-if="form.errors['options.A']" class="text-xs text-red-600">{{ form.errors['options.A'] }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Kunci Jawaban *
              <span v-if="form.type === 'multiple_choice'" class="font-normal text-gray-400">(A/B/C/D)</span>
              <span v-else-if="form.type === 'true_false'" class="font-normal text-gray-400">(benar/salah)</span>
            </label>
            <select v-if="form.type === 'multiple_choice'" v-model="form.correct_answer"
              class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
              <option value="A">A</option><option value="B">B</option><option value="C">C</option><option value="D">D</option>
            </select>
            <select v-else-if="form.type === 'true_false'" v-model="form.correct_answer"
              class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
              <option value="benar">Benar</option><option value="salah">Salah</option>
            </select>
            <input v-else v-model="form.correct_answer" placeholder="Kunci jawaban"
              class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" />
            <p v-if="form.errors.correct_answer" class="mt-1 text-xs text-red-600">{{ form.errors.correct_answer }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Pembahasan / Umpan Balik</label>
            <textarea v-model="form.explanation" rows="2"
              class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"></textarea>
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

    <!-- Modal import -->
    <div v-if="importOpen" class="fixed inset-0 z-40 bg-black/50 flex items-start justify-center p-4 overflow-y-auto">
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-lg my-8">
        <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h2 class="font-semibold text-gray-900 dark:text-white">Import Soal (CSV)</h2>
          <button @click="importOpen = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <form @submit.prevent="submitImport" class="p-6 space-y-4">
          <p class="text-sm text-gray-500 dark:text-gray-400">
            Gunakan template CSV. Kolom: TP_ID, Fase, Tipe, Soal, Opsi_A..Opsi_D, Kunci_Jawaban, Pembahasan.
          </p>
          <input type="file" accept=".csv,.txt" @change="e => importForm.file = e.target.files[0]"
            class="block w-full text-sm text-gray-600 dark:text-gray-300" />
          <p v-if="importForm.errors.file" class="text-xs text-red-600">{{ importForm.errors.file }}</p>
          <div class="flex justify-end gap-2">
            <button type="button" @click="importOpen = false" class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg">Batal</button>
            <button type="submit" :disabled="importForm.processing || !importForm.file"
              class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50">
              {{ importForm.processing ? 'Mengimport...' : 'Import' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import Layout from '../../../Plugins/InertiaLayout.vue'

const props = defineProps({
  questions: { type: Object, required: true },
  objectives: { type: Array, default: () => [] },
  subjects: { type: Array, default: () => [] },
  types: { type: Object, default: () => ({}) },
  phases: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
})

const filter = ref({
  subject_id: props.filters.subject_id ?? '',
  learning_objective_id: props.filters.learning_objective_id ?? '',
  type: props.filters.type ?? '',
})

const filteredObjectives = computed(() => {
  if (!filter.value.subject_id) return props.objectives
  const subjectName = props.subjects.find(s => String(s.id) === String(filter.value.subject_id))?.name
  return props.objectives.filter(o => o.subject_name === subjectName)
})

function applyFilter() {
  router.get('/teacher/question-bank', filter.value, { preserveState: true, preserveScroll: true, replace: true })
}

function resetFilter() {
  filter.value = { subject_id: '', learning_objective_id: '', type: '' }
  applyFilter()
}

// --- Form soal ---
const showModal = ref(false)
const editing = ref(null)
const form = useForm({
  learning_objective_id: '',
  phase: 'Fase D',
  type: 'multiple_choice',
  content: '',
  options: { A: '', B: '', C: '', D: '' },
  correct_answer: 'A',
  explanation: '',
})

function onTypeChange() {
  if (form.type === 'multiple_choice') form.correct_answer = 'A'
  else if (form.type === 'true_false') form.correct_answer = 'benar'
  else form.correct_answer = ''
}

function openCreate() {
  editing.value = null
  form.reset()
  form.clearErrors()
  form.options = { A: '', B: '', C: '', D: '' }
  showModal.value = true
}

function openEdit(q) {
  editing.value = q
  form.clearErrors()
  form.learning_objective_id = props.objectives.find(o => o.name === q.objective_name)?.id ?? ''
  form.phase = q.phase
  form.type = q.type
  form.content = q.content
  form.options = { A: '', B: '', C: '', D: '', ...(q.options ?? {}) }
  form.correct_answer = q.correct_answer
  form.explanation = ''
  showModal.value = true
}

function closeModal() {
  showModal.value = false
}

function submit() {
  const payload = { ...form.data() }
  if (form.type !== 'multiple_choice') payload.options = null

  if (editing.value) {
    form.transform(() => payload).put(`/teacher/question-bank/${editing.value.id}`, { onSuccess: closeModal })
  } else {
    form.transform(() => payload).post('/teacher/question-bank', { onSuccess: closeModal })
  }
}

function remove(q) {
  if (confirm('Hapus soal ini?')) {
    useForm({}).delete(`/teacher/question-bank/${q.id}`)
  }
}

// --- Import ---
const importOpen = ref(false)
const importForm = useForm({ file: null })

function submitImport() {
  importForm.post('/teacher/question-bank/import', {
    forceFormData: true,
    onSuccess: () => { importOpen.value = false; importForm.reset() },
  })
}

function logout() {
  useForm({}).post('/logout')
}
</script>
