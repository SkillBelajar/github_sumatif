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
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Sesi Asesmen</h1>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Buat sesi ujian, rakit soal dari Bank Soal, lalu aktifkan dengan token.</p>
        </div>
        <button @click="openCreate"
          class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">
          + Buat Sesi
        </button>
      </div>

      <div v-if="sessions.length" class="space-y-3">
        <div v-for="s in sessions" :key="s.id"
          class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 border border-gray-200 dark:border-gray-700">
          <div class="flex flex-wrap justify-between items-start gap-4">
            <div class="min-w-0">
              <div class="flex items-center gap-2 flex-wrap">
                <span :class="statusClass(s.status)" class="px-2 py-0.5 text-xs font-semibold rounded">
                  {{ statusLabel(s.status) }}
                </span>
                <span class="text-xs font-mono text-gray-500 dark:text-gray-400">Token: {{ s.token }}</span>
                <span class="text-xs text-gray-400">{{ s.duration_minutes }} menit</span>
              </div>
              <p class="mt-2 font-semibold text-gray-900 dark:text-white">{{ s.name }}</p>
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ s.questions_count }} soal · {{ s.classrooms_count }} kelas
              </p>
            </div>
            <div class="flex flex-wrap gap-2 shrink-0">
              <a :href="'/teacher/assessments/' + s.id + '/questions'"
                class="text-xs px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                Rakit Soal
              </a>
              <button @click="openEdit(s)" class="text-xs px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">Edit</button>
              <button @click="remove(s)" class="text-xs px-3 py-1.5 border border-red-300 text-red-600 rounded-lg hover:bg-red-50">Hapus</button>
            </div>
          </div>
        </div>
      </div>
      <div v-else class="bg-white dark:bg-gray-800 rounded-xl shadow p-10 text-center">
        <div class="text-4xl mb-3">📋</div>
        <p class="text-gray-500 dark:text-gray-400">Belum ada sesi asesmen. Klik "Buat Sesi" untuk memulai.</p>
      </div>
    </div>

    <!-- Modal form -->
    <div v-if="showModal" class="fixed inset-0 z-40 bg-black/50 flex items-start justify-center p-4 overflow-y-auto">
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-lg my-8">
        <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h2 class="font-semibold text-gray-900 dark:text-white">{{ editing ? 'Edit Sesi' : 'Buat Sesi Asesmen' }}</h2>
          <button @click="closeModal" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>

        <form @submit.prevent="submit" class="p-6 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Ujian *</label>
            <input v-model="form.name" type="text" placeholder="Sumatif Tengah Semester Ganjil"
              class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" />
            <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Durasi (menit) *</label>
            <input v-model.number="form.duration_minutes" type="number" min="1" max="600"
              class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" />
            <p v-if="form.errors.duration_minutes" class="mt-1 text-xs text-red-600">{{ form.errors.duration_minutes }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Token Ujian</label>
            <div class="flex gap-2">
              <input v-model="form.token" type="text" maxlength="16" placeholder="Kosongkan = auto-generate"
                class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm font-mono uppercase" />
              <button type="button" @click="form.token = randomToken()"
                class="px-3 py-2 text-xs border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                Generate
              </button>
            </div>
            <p class="mt-1 text-xs text-gray-400">5-16 karakter huruf/angka. Siswa memakai token ini untuk masuk ujian.</p>
            <p v-if="form.errors.token" class="mt-1 text-xs text-red-600">{{ form.errors.token }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kelas Peserta</label>
            <div class="max-h-40 overflow-y-auto rounded-lg border border-gray-300 dark:border-gray-600 p-3 space-y-2">
              <label v-for="c in allClassrooms" :key="c" class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                <input type="checkbox" :value="c" v-model="form.classroom_ids"
                  class="rounded border-gray-300 dark:border-gray-600 text-indigo-600" />
                {{ c }}
                <span v-if="!props.classrooms.includes(c)" class="text-xs text-green-600">(baru)</span>
              </label>
              <p v-if="!allClassrooms.length" class="text-xs text-gray-400">Belum ada kelas. Ketik di bawah untuk menambah.</p>
            </div>
            <div class="flex gap-2 mt-2">
              <input v-model="newClassroom" type="text" placeholder="Tambah kelas baru (mis. VII-A)"
                @keyup.enter.prevent="addClassroom"
                class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" />
              <button type="button" @click="addClassroom"
                class="px-3 py-2 text-xs border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                Tambah
              </button>
            </div>
            <p v-if="form.errors.classroom_ids" class="mt-1 text-xs text-red-600">{{ form.errors.classroom_ids }}</p>
          </div>

          <div class="flex justify-end gap-2 pt-2">
            <button type="button" @click="closeModal"
              class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
              Batal
            </button>
            <button type="submit" :disabled="form.processing"
              class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 disabled:opacity-50">
              Simpan
            </button>
          </div>
        </form>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import Layout from '../../../Plugins/InertiaLayout.vue'
import { useForm, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const props = defineProps({ sessions: Array, classrooms: Array })

const showModal = ref(false)
const editing = ref(null)
const newClassroom = ref('')

// Kelas dari server + yang baru diketik user (belum tersimpan) — langsung muncul di checklist.
const allClassrooms = computed(() => {
  const set = new Set([...(props.classrooms ?? []), ...form.classroom_ids])
  return [...set].sort()
})

const form = useForm({
  name: '',
  duration_minutes: 60,
  token: '',
  classroom_ids: [],
})

function randomToken() {
  const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'
  return Array.from({ length: 6 }, () => chars[Math.floor(Math.random() * chars.length)]).join('')
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

function openCreate() {
  editing.value = null
  form.reset()
  form.clearErrors()
  form.token = randomToken()
  showModal.value = true
}

function openEdit(s) {
  editing.value = s
  form.clearErrors()
  form.name = s.name
  form.duration_minutes = s.duration_minutes
  form.token = s.token
  form.classroom_ids = s.classroom_names ?? []
  showModal.value = true
}

function addClassroom() {
  const v = newClassroom.value.trim()
  if (!v) return
  if (!form.classroom_ids.includes(v)) form.classroom_ids.push(v)
  newClassroom.value = ''
}

function closeModal() {
  showModal.value = false
  editing.value = null
}

function submit() {
  if (editing.value) {
    form.put('/teacher/assessments/' + editing.value.id, {
      preserveScroll: true,
      onSuccess: closeModal,
    })
  } else {
    form.post('/teacher/assessments', {
      preserveScroll: true,
      onSuccess: () => (showModal.value = false),
    })
  }
}

function remove(s) {
  if (!confirm('Hapus sesi "' + s.name + '"?')) return
  router.delete('/teacher/assessments/' + s.id, { preserveScroll: true })
}

function logout() {
  router.post('/logout')
}
</script>
