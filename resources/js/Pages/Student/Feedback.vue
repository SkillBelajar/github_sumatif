<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-100 via-blue-50 to-indigo-100 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 py-8 px-4">
    <div class="max-w-2xl mx-auto">

      <div class="text-center mb-6">
        <div class="text-5xl mb-2">🎉</div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Ujian Selesai</h1>
        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">{{ session.name }} &middot; {{ result.student_name }}</p>
      </div>

      <!-- Rekap nilai per TP -->
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Rekap Nilai per Tujuan Pembelajaran</h2>

        <div v-if="!result.scores_per_tp.length" class="text-sm text-gray-500 dark:text-gray-400">
          Belum ada nilai untuk direkap.
        </div>

        <div v-for="(tp, i) in result.scores_per_tp" :key="tp.tp_id ?? i" class="mb-3 last:mb-0">
          <div class="flex items-center justify-between text-sm">
            <span class="font-medium text-gray-700 dark:text-gray-200">TP {{ i + 1 }}: {{ tp.tp_name }}</span>
            <span
              class="font-bold tabular-nums"
              :class="tp.is_passed ? 'text-green-600 dark:text-green-400' : 'text-red-700 dark:text-red-400'"
            >
              {{ tp.score }} ({{ tp.is_passed ? '✓ Tuntas' : '✗ Belum Tuntas' }})
            </span>
          </div>
          <div class="h-2 mt-1.5 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
            <div
              class="h-full rounded-full transition-all"
              :class="tp.is_passed ? 'bg-green-500' : 'bg-red-600'"
              :style="{ width: tp.score + '%' }"
            />
          </div>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Benar {{ tp.correct }} dari {{ tp.total }} soal</p>
        </div>
      </div>

      <!-- Daftar soal & pembahasan -->
      <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3 px-1">Soal &amp; Pembahasan</h2>

      <div
        v-for="item in review"
        :key="item.question_id"
        class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-5 mb-4 border-l-4"
        :class="item.needs_review
          ? 'border-amber-500'
          : item.is_correct ? 'border-green-500' : 'border-red-700'"
      >
        <div class="flex items-start gap-3">
          <span
            class="shrink-0 w-7 h-7 rounded-full flex items-center justify-center text-sm font-bold text-white"
            :class="item.needs_review ? 'bg-amber-500' : item.is_correct ? 'bg-green-600' : 'bg-red-700'"
          >{{ item.number }}</span>
          <div class="flex-1 min-w-0">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">
              {{ item.type_label }}
              <template v-if="item.objective"> &middot; {{ item.objective }}</template>
            </p>
            <p class="text-gray-900 dark:text-white whitespace-pre-wrap">{{ item.content }}</p>

            <div class="mt-3 space-y-1.5 text-sm">
              <p class="flex gap-2">
                <span class="text-gray-500 dark:text-gray-400 shrink-0">Jawaban Anda:</span>
                <span class="font-medium" :class="item.needs_review ? 'text-amber-700 dark:text-amber-400' : item.is_correct ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400'">
                  {{ labelOf(item) }}
                </span>
              </p>
            </div>

            <div
              v-if="item.needs_review"
              class="mt-3 rounded-xl bg-amber-50 dark:bg-amber-900/20 px-4 py-3 text-sm text-amber-800 dark:text-amber-300"
            >
              Jawaban esai/isian akan dinilai guru. Tunggu hasil review.
            </div>

            <div
              v-else-if="item.explanation"
              class="mt-3 rounded-xl px-4 py-3 text-sm"
              :class="item.is_correct
                ? 'bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-300'
                : 'bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300'"
            >
              <strong>Pembahasan:</strong> {{ item.explanation }}
            </div>
          </div>
        </div>
      </div>

      <div class="text-center mt-8 space-y-3">
        <a
          :href="`/ujian/${session.id}/prepare?student_id=${result.student_id}`"
          class="inline-block px-8 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl transition-colors"
        >Kerjakan Ulang</a>
        <div>
          <a
            href="/ujian"
            class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline"
          >Kembali ke Halaman Ujian</a>
        </div>
        <p class="text-gray-400 dark:text-gray-500 text-xs mt-2">Ujian ulang bebas — nilai terakhir yang dihitung.</p>
        <p class="text-gray-400 dark:text-gray-500 text-xs">Versi: {{ version }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  version: String,
  session: Object,
  result: Object,
  review: Array,
})

// Opsi soal bisa objek {A: '...'} atau array; tampilkan teks, bukan hanya huruf.
function labelOf(item) {
  if (item.student_answer && item.options && typeof item.options === 'object') {
    return item.options[item.student_answer] ?? item.student_answer
  }
  return item.student_answer || 'Tidak dijawab'
}
</script>
