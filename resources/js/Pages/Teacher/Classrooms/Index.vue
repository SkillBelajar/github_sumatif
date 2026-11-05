<template>
  <Layout>
    <template #nav>
      <form @submit.prevent="logout">
        <button type="submit" class="text-sm text-gray-500 hover:text-red-500">Logout</button>
      </form>
    </template>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Kelas &amp; Siswa</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          Siswa yang muncul di dropdown ujian adalah siswa kelas yang dipilih saat membuat Sesi Asesmen.
        </p>
      </div>

      <div v-if="$page.props.flash?.success" class="mb-4 rounded-xl bg-green-50 dark:bg-green-900/30 px-4 py-3 text-sm text-green-800 dark:text-green-200">
        {{ $page.props.flash.success }}
      </div>
      <div v-if="$page.props.flash?.error" class="mb-4 rounded-xl bg-red-50 dark:bg-red-900/30 px-4 py-3 text-sm text-red-800 dark:text-red-200">
        {{ $page.props.flash.error }}
      </div>

      <!-- Buat kelas -->
      <form @submit.prevent="createClassroom" class="mb-6 flex gap-2">
        <input v-model="newClassroom" type="text" placeholder="Nama kelas baru (mis. VII-A)"
          class="flex-1 rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" />
        <button type="submit" :disabled="!newClassroom.trim()"
          class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white text-sm font-medium rounded-xl">
          + Kelas
        </button>
      </form>

      <p v-if="!classrooms.length" class="text-gray-500 dark:text-gray-400 text-sm">Belum ada kelas.</p>

      <div v-for="c in classrooms" :key="c.id" class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 mb-4">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
            {{ c.name }}
            <span class="ml-2 text-sm font-normal text-gray-500">{{ c.students.length }} siswa</span>
          </h2>
          <button @click="removeClassroom(c)" class="text-xs text-red-500 hover:text-red-700">Hapus kelas</button>
        </div>

        <!-- Tambah siswa manual -->
        <form @submit.prevent="addStudent(c)" class="mt-3 flex gap-2">
          <input v-model="studentDrafts[c.id]" type="text" placeholder="Nama siswa..."
            class="flex-1 rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" />
          <button type="submit" :disabled="!(studentDrafts[c.id] || '').trim()"
            class="px-3 py-2 bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white text-sm font-medium rounded-xl">
            + Siswa
          </button>
        </form>

        <!-- Import CSV -->
        <div class="mt-2 flex items-center gap-3">
          <label class="text-xs text-gray-500 dark:text-gray-400 cursor-pointer hover:text-indigo-600">
            Import CSV (1 kolom "Nama")
            <input type="file" accept=".csv,.txt" class="hidden" @change="importCsv(c, $event)" />
          </label>
        </div>

        <!-- Daftar siswa -->
        <ul class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-2">
          <li v-for="s in c.students" :key="s.id"
            class="flex items-center justify-between px-3 py-2 rounded-lg bg-gray-50 dark:bg-gray-700/50 text-sm text-gray-700 dark:text-gray-200">
            {{ s.name }}
            <button @click="removeStudent(c, s)" class="text-xs text-gray-400 hover:text-red-500">✕</button>
          </li>
        </ul>
        <p v-if="!c.students.length" class="mt-3 text-xs text-amber-600 dark:text-amber-400">
          Belum ada siswa — dropdown "Pilih Nama Anda" akan kosong untuk ujian kelas ini.
        </p>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import Layout from '../../../Plugins/InertiaLayout.vue'

const props = defineProps({ classrooms: Array })

const newClassroom = ref('')
const studentDrafts = reactive({})

function createClassroom() {
  router.post('/teacher/classrooms', { name: newClassroom.value.trim() }, {
    preserveScroll: true,
    onSuccess: () => { newClassroom.value = '' },
  })
}

function removeClassroom(c) {
  if (!confirm('Hapus kelas ' + c.name + ' dan seluruh siswanya?')) return
  router.delete('/teacher/classrooms/' + c.id, { preserveScroll: true })
}

function addStudent(c) {
  const name = (studentDrafts[c.id] || '').trim()
  if (!name) return
  router.post('/teacher/classrooms/' + c.id + '/students', { name }, {
    preserveScroll: true,
    onSuccess: () => { studentDrafts[c.id] = '' },
  })
}

function removeStudent(c, s) {
  router.delete('/teacher/classrooms/' + c.id + '/students/' + s.id, { preserveScroll: true })
}

// Inertia tak bisa multipart via router.post — pakai form biasa supaya file terupload.
function importCsv(c, event) {
  const file = event.target.files?.[0]
  if (!file) return
  const form = document.createElement('form')
  form.method = 'POST'
  form.action = '/teacher/classrooms/' + c.id + '/students/import'
  form.enctype = 'multipart/form-data'
  form.style.display = 'none'

  const token = document.querySelector('meta[name="csrf-token"]')?.content ?? ''
  form.innerHTML = '<input type="hidden" name="_token" value="' + token + '">' +
    '<input type="file" name="file">'

  const fileInput = form.querySelector('input[type=file]')
  const dt = new DataTransfer()
  dt.items.add(file)
  fileInput.files = dt.files

  document.body.appendChild(form)
  form.submit()
}

function logout() {
  router.post('/logout')
}
</script>
