<template>
  <div class="min-h-screen bg-gradient-to-br from-indigo-500 via-indigo-600 to-purple-700 flex items-center justify-center p-4">
    <div class="w-full max-w-lg">
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8">
        <div class="text-center mb-6">
          <div class="text-4xl mb-2">🎯</div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ session.name }}</h1>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Halo, {{ student.name }} — baca dulu sebelum mulai.</p>
        </div>

        <div class="grid grid-cols-2 gap-3 mb-6">
          <div class="bg-indigo-50 dark:bg-gray-700 rounded-xl p-4 text-center">
            <p class="text-3xl font-bold text-indigo-600 dark:text-indigo-300">{{ session.question_count }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Jumlah Soal</p>
          </div>
          <div class="bg-indigo-50 dark:bg-gray-700 rounded-xl p-4 text-center">
            <p class="text-3xl font-bold text-indigo-600 dark:text-indigo-300">{{ session.duration_minutes }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Menit</p>
          </div>
        </div>

        <div v-if="session.kktp.length" class="mb-6">
          <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Kriteria Ketercapaian TP (KKTP)</p>
          <div class="space-y-2 max-h-48 overflow-y-auto">
            <div v-for="(k, i) in session.kktp" :key="i"
              class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3 text-sm">
              <p class="font-medium text-gray-800 dark:text-gray-100">
                {{ k.level }}<span v-if="k.min_score !== null" class="text-gray-400"> (≥{{ k.min_score }})</span>
              </p>
              <p v-if="k.criteria" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ k.criteria }}</p>
            </div>
          </div>
        </div>

        <div class="bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 rounded-xl p-4 mb-6 text-sm text-amber-800 dark:text-amber-200">
          <p class="font-semibold mb-1">⚠️ Perhatian</p>
          <ul class="list-disc list-inside space-y-0.5 text-xs">
            <li>Waktu mulai dihitung saat tombol ditekan, timer jalan dari server.</li>
            <li>Jawaban tersimpan otomatis setiap kali Anda memilih.</li>
            <li>Refresh halaman aman — jawaban & waktu tidak hilang.</li>
            <li>Setelah "Kirim Ujian", jawaban tidak bisa diubah lagi.</li>
          </ul>
        </div>

        <button @click="start" :disabled="starting"
          class="w-full py-5 text-xl font-bold bg-green-600 text-white rounded-2xl hover:bg-green-700 active:scale-[0.98] transition shadow-lg disabled:opacity-50">
          {{ starting ? 'MEMBUKA UJIAN...' : '🚀 MULAI UJIAN' }}
        </button>

        <a href="/ujian" class="block text-center text-xs text-gray-400 hover:text-gray-600 mt-4">Batalkan, kembali ke token</a>
      </div>

      <p class="text-center text-indigo-200 text-xs mt-4">Versi: {{ version }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  version: String,
  session: Object,
  student: Object,
})

const starting = ref(false)

function start() {
  starting.value = true
  router.post('/ujian/' + props.session.id + '/start', { student_id: props.student.id }, {
    onFinish: () => (starting.value = false),
  })
}
</script>
