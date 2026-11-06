<template>
  <div class="min-h-screen bg-gray-100 dark:bg-gray-900 flex flex-col">
    <!-- Header fokus ujian: tanpa menu/navbar lain -->
    <header class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 py-3 sticky top-0 z-10">
      <div class="max-w-3xl mx-auto flex items-center justify-between gap-3">
        <div class="min-w-0">
          <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ attempt.session_name }}</p>
          <p class="text-xs text-gray-500 dark:text-gray-400">{{ attempt.student_name }}</p>
        </div>
        <div class="flex items-center gap-3 shrink-0">
          <span :class="['text-lg font-mono font-bold tabular-nums px-3 py-1 rounded-lg',
            remaining <= 60 ? 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-200' : 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-200']">
            {{ clock }}
          </span>
          <span class="text-xs text-gray-400">{{ answered }}/{{ total }} dijawab</span>
        </div>
      </div>
    </header>

    <main class="flex-1 max-w-3xl mx-auto w-full px-4 py-6">
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-6 mb-4">
        <div class="flex items-center gap-2 mb-4">
          <span class="text-xs font-semibold text-gray-400">SOAL {{ current.number }} / {{ total }}</span>
          <span class="px-2 py-0.5 text-xs font-semibold rounded bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
            {{ current.type_label }}
          </span>
          <span v-if="savedAt" class="ml-auto text-xs text-green-600 dark:text-green-400">✓ tersimpan</span>
        </div>

        <p class="text-base text-gray-900 dark:text-white whitespace-pre-line leading-relaxed no-select">{{ current.content }}</p>
        <p v-if="current.objective" class="mt-2 text-xs text-gray-400">TP: {{ current.objective }}</p>
      </div>

      <!-- Pilihan ganda / benar-salah -->
      <div v-if="current.options" class="space-y-2 mb-4">
        <button v-for="(opt, key) in current.options" :key="key"
          @click="choose(key)"
          :class="['w-full text-left px-4 py-3 rounded-xl border-2 transition flex items-start gap-3',
            answers[current.id] === key
              ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/40'
              : 'border-gray-200 dark:border-gray-700 hover:border-indigo-300']">
          <span :class="['w-7 h-7 rounded-full flex items-center justify-center text-sm font-bold shrink-0',
            answers[current.id] === key ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300']">
            {{ key }}
          </span>
          <span class="text-sm text-gray-800 dark:text-gray-200 pt-0.5">{{ opt }}</span>
        </button>
      </div>

      <!-- Isian / esai -->
      <div v-else class="mb-4">
        <textarea v-model="answers[current.id]" rows="5" placeholder="Tulis jawaban Anda di sini..."
          class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"></textarea>
      </div>

      <div class="flex justify-between items-center gap-3">
        <button @click="prev" :disabled="index === 0"
          class="px-5 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-40">
          ← Sebelumnya
        </button>

        <button v-if="index < total - 1" @click="next"
          class="px-5 py-2.5 text-sm bg-indigo-600 text-white rounded-xl hover:bg-indigo-700">
          Selanjutnya →
        </button>

        <button v-else @click="confirmSubmit"
          class="px-6 py-2.5 text-sm bg-green-600 text-white font-semibold rounded-xl hover:bg-green-700">
          Kirim Ujian ✓
        </button>
      </div>
    </main>

    <footer class="text-center text-xs text-gray-400 py-3">Versi: {{ version }}</footer>

    <div v-if="violation" class="fixed inset-0 z-50 bg-red-900/95 flex flex-col items-center justify-center text-white text-center p-6">
      <h2 class="text-2xl font-bold mb-2">Ujian Dibatalkan</h2>
      <p class="mb-4">Terdeteksi pindah tab atau membuka tab lain selama ujian.</p>
      <p class="text-red-200 text-sm">Soal akan mengulang dalam 3 detik…</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  version: String,
  attempt: Object,
})

const questions = ref(props.attempt.questions)
const answers = ref({ ...props.attempt.answers })
const index = ref(0)
const savedAt = ref(null)
const violation = ref(false)
let graceTimer = null

const sessionId = props.attempt.session_id
const tabId = Math.random().toString(36).slice(2)
const storageKey = 'ujian:tab:' + sessionId

function triggerViolation() {
  if (violation.value) return
  violation.value = true
  clearInterval(tick)
  clearTimeout(graceTimer)
  setTimeout(() => { window.location.href = '/ujian?cheat=1' }, 3000)
}

function startGrace() { clearTimeout(graceTimer); graceTimer = setTimeout(triggerViolation, 3000) }
function clearGrace() { clearTimeout(graceTimer) }

// Anti copy-paste: blokir klik kanan, salin, potong, tempel, dan shortcut keyboard
function blockCP(e) { e.preventDefault() }
function blockShortcut(e) {
  const ctrl = navigator.platform.toUpperCase().includes('MAC') ? e.metaKey : e.ctrlKey
  if (ctrl && ['c', 'v', 'x', 'a'].includes(e.key.toLowerCase())) e.preventDefault()
}

function onStorage(e) {
  if (e.key === storageKey && e.newValue && e.newValue !== tabId) triggerViolation()
}
function onVisibilityChange() {
  if (document.hidden) startGrace()
  else clearGrace()
}

const current = computed(() => questions.value[index.value])
const total = computed(() => questions.value.length)
const answered = computed(() => Object.values(answers.value).filter(v => v !== null && v !== undefined && v !== '').length)

// Timer dari deadline server; selisih waktu lokal hanya untuk tampilan.
const deadline = new Date(props.attempt.deadline_at).getTime()
const remaining = ref(Math.max(0, Math.floor((deadline - Date.now()) / 1000)))
const clock = computed(() => {
  const s = remaining.value
  return String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0')
})

let tick = null
onMounted(() => {
  tick = setInterval(() => {
    remaining.value = Math.max(0, Math.floor((deadline - Date.now()) / 1000))
    if (remaining.value === 0) submit()
  }, 1000)

  try { localStorage.setItem(storageKey, tabId) } catch (e) {}

  window.addEventListener('storage', onStorage)
  document.addEventListener('visibilitychange', onVisibilityChange)
  window.addEventListener('blur', startGrace)
  window.addEventListener('focus', clearGrace)
  document.addEventListener('contextmenu', blockCP, true)
  document.addEventListener('copy', blockCP, true)
  document.addEventListener('cut', blockCP, true)
  document.addEventListener('paste', blockCP, true)
  document.addEventListener('keydown', blockShortcut, true)
})
onUnmounted(() => {
  clearInterval(tick)
  clearTimeout(graceTimer)
  window.removeEventListener('storage', onStorage)
  document.removeEventListener('visibilitychange', onVisibilityChange)
  window.removeEventListener('blur', startGrace)
  window.removeEventListener('focus', clearGrace)
  document.removeEventListener('contextmenu', blockCP, true)
  document.removeEventListener('copy', blockCP, true)
  document.removeEventListener('cut', blockCP, true)
  document.removeEventListener('paste', blockCP, true)
  document.removeEventListener('keydown', blockShortcut, true)
  try { localStorage.removeItem(storageKey) } catch (e) {}
})

// Autosave: jawaban yang berubah langsung dikirim ke server.
watch(() => ({ ...answers.value }), (val, old) => {
  if (!old) return
  for (const id of Object.keys(val)) {
    if (val[id] !== old[id]) save(id, val[id])
  }
}, { deep: true })

let saving = false
async function save(questionId, answer) {
  if (saving) return
  saving = true
  try {
    await fetch('/ujian/' + props.attempt.session_id + '/answer', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
      },
      body: JSON.stringify({
        student_id: props.attempt.student_id,
        question_id: Number(questionId),
        student_answer: answer ?? '',
      }),
    })
    savedAt.value = Date.now()
  } finally {
    saving = false
  }
}

function choose(key) {
  answers.value = { ...answers.value, [current.value.id]: key }
}

function next() { if (index.value < total.value - 1) index.value++ }
function prev() { if (index.value > 0) index.value-- }

function confirmSubmit() {
  const unanswered = total.value - answered.value
  const msg = unanswered > 0
    ? `Masih ${unanswered} soal belum dijawab. Kirim sekarang?`
    : 'Kirim ujian sekarang? Jawaban tidak bisa diubah setelah ini.'
  if (!confirm(msg)) return
  submit()
}

function submit() {
  router.post('/ujian/' + props.attempt.session_id + '/submit', {
    student_id: props.attempt.student_id,
  })
}
</script>

<style scoped>
.no-select {
  user-select: none;
  -webkit-user-select: none;
}
</style>
