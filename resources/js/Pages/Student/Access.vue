<template>
  <div class="min-h-screen bg-gradient-to-br from-indigo-500 via-indigo-600 to-purple-700 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <div class="text-center mb-6">
        <div class="text-5xl mb-2">📝</div>
        <h1 class="text-2xl font-bold text-white">Asesmen Sumatif</h1>
        <p class="text-indigo-100 text-sm mt-1">Masukkan token ujian dari guru Anda</p>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-6">
        <form @submit.prevent="submit" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Token Ujian</label>
            <input ref="tokenInput" v-model="token" type="text" maxlength="6" placeholder="ABC123"
              @input="token = token.toUpperCase()"
              class="w-full text-center text-3xl tracking-[0.4em] font-mono font-bold py-4 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 uppercase" />
            <p v-if="error" class="mt-2 text-sm text-red-600 text-center">{{ error }}</p>
          </div>
          <button type="submit" :disabled="token.length !== 6 || loading"
            class="w-full py-3 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 disabled:opacity-40 disabled:cursor-not-allowed transition">
            {{ loading ? 'Memeriksa...' : 'Cek Token' }}
          </button>
        </form>

        <!-- Langkah 2: pilih nama -->
        <div v-if="students.length" class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700 space-y-4">
          <div>
            <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Ujian</p>
            <p class="font-semibold text-gray-900 dark:text-white">{{ sessionInfo?.name }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
              {{ sessionInfo?.question_count }} soal · {{ sessionInfo?.duration_minutes }} menit
            </p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Pilih Nama Anda</label>
            <select v-model="selectedStudent"
              class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-2.5">
              <option value="" disabled>— Pilih nama —</option>
              <option v-for="s in students" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
          </div>
          <a :href="'/ujian/' + sessionInfo.id + '/prepare?student_id=' + selectedStudent"
            :class="['block w-full py-3 text-center font-semibold rounded-xl transition',
              selectedStudent
                ? 'bg-green-600 text-white hover:bg-green-700'
                : 'bg-gray-200 text-gray-400 pointer-events-none dark:bg-gray-700']">
            Lanjut
          </a>
        </div>
      </div>

      <p class="text-center text-indigo-200 text-xs mt-6">Versi: {{ version }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  version: String,
  sessionInfo: Object,
  students: Array,
  errors: Object,
})

const token = ref('')
const loading = ref(false)
const students = ref(props.students ?? [])
const sessionInfo = ref(props.sessionInfo ?? null)
const selectedStudent = ref('')
const tokenInput = ref(null)
const error = ref(props.errors?.token ?? '')

watch(() => props.students, (v) => { students.value = v ?? [] })
watch(() => props.sessionInfo, (v) => { sessionInfo.value = v ?? null })
watch(() => props.errors?.token, (v) => { error.value = v ?? '' })

onMounted(() => tokenInput.value?.focus())

function submit() {
  if (token.value.length !== 6) return
  loading.value = true
  router.post('/ujian/check', { token: token.value }, {
    preserveScroll: true,
    preserveState: true,
    onFinish: () => (loading.value = false),
    onError: (e) => { error.value = e.token ?? 'Terjadi kesalahan.' },
  })
}
</script>
