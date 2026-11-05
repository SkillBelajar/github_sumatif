<template>
  <Layout>
    <template #nav>
      <a :href="'/teacher/assessments/' + session.id + '/results/export'"
        class="text-sm px-4 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium">
        Unduh Laporan (CSV)
      </a>
    </template>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="mb-6 flex items-start justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ session.name }}</h1>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Token <span class="font-mono font-semibold">{{ session.token }}</span> &middot;
            Status {{ { draft: 'Draft', active: 'Aktif', closed: 'Ditutup' }[session.status] }}
          </p>
        </div>
        <a href="/teacher/assessments" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">&larr; Sesi Asesmen</a>
      </div>

      <!-- Metrik ringkasan -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 border-l-4 border-indigo-500">
          <p class="text-sm text-gray-500 dark:text-gray-400">Total Siswa Mengerjakan</p>
          <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ summary.total_students }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 border-l-4 border-blue-500">
          <p class="text-sm text-gray-500 dark:text-gray-400">Rata-rata Nilai Kelas</p>
          <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ summary.average ?? '-' }}</p>
        </div>
      </div>

      <!-- Tuntas vs Belum Tuntas per TP -->
      <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Ketuntasan per Tujuan Pembelajaran</h2>
      <p v-if="!summary.per_tp.length" class="text-sm text-gray-500 dark:text-gray-400 mb-8">Belum ada siswa yang menyelesaikan ujian ini.</p>

      <div v-for="tp in summary.per_tp" :key="tp.tp_id" class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 mb-4">
        <div class="flex items-center justify-between text-sm mb-2">
          <span class="font-medium text-gray-700 dark:text-gray-200">{{ tp.name }}</span>
          <span class="text-gray-500 dark:text-gray-400">Rata-rata {{ tp.average ?? '-' }}</span>
        </div>
        <!-- Bar ganda: hijau tuntas, merah belum -->
        <div class="flex h-3 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-700">
          <div class="bg-green-500" :style="{ width: pct(tp.passed, tp.answered) + '%' }" />
          <div class="bg-red-600" :style="{ width: pct(tp.failed, tp.answered) + '%' }" />
        </div>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1.5">
          <span class="text-green-600 dark:text-green-400 font-semibold">✓ {{ tp.passed }} tuntas</span>
          &middot;
          <span class="text-red-600 dark:text-red-400 font-semibold">* {{ tp.failed }} belum</span>
          &middot; {{ tp.answered }} mengerjakan TP ini
        </p>
      </div>

      <!-- Tabel rekap nilai -->
      <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3 mt-8">Rekapitulasi Nilai Siswa</h2>
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="border-b border-gray-200 dark:border-gray-700 text-left text-gray-500 dark:text-gray-400">
              <th class="px-4 py-3 font-medium">Nama Siswa</th>
              <th class="px-4 py-3 font-medium">Kelas</th>
              <th v-for="tp in tpColumns" :key="tp.key" class="px-4 py-3 font-medium whitespace-nowrap">
                {{ tp.label }}<span class="block text-xs font-normal text-gray-400">{{ tp.name }}</span>
              </th>
              <th class="px-4 py-3 font-medium">Nilai Akhir</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in rows" :key="row.result_id" class="border-b border-gray-100 dark:border-gray-700/50">
              <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ row.student_name }}</td>
              <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ row.classroom_name }}</td>
              <td v-for="tp in tpColumns" :key="tp.key" class="px-4 py-3 whitespace-nowrap">
                <span
                  v-if="row.scores[tp.key]"
                  class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold"
                  :class="row.scores[tp.key].is_passed
                    ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300'
                    : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'"
                >{{ row.scores[tp.key].label }}</span>
                <span v-else class="text-gray-400">-</span>
              </td>
              <td class="px-4 py-3 font-bold tabular-nums text-gray-900 dark:text-white">{{ row.final_score ?? '-' }}</td>
            </tr>
            <tr v-if="!rows.length">
              <td :colspan="3 + tpColumns.length" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                Belum ada hasil ujian.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <p class="text-gray-400 dark:text-gray-500 text-xs mt-8">Versi: 0.18</p>
    </div>
  </Layout>
</template>

<script setup>
import Layout from '../../../Plugins/InertiaLayout.vue'

defineProps({
  session: Object,
  summary: Object,
  tpColumns: Array,
  rows: Array,
})

function pct(part, total) {
  return total > 0 ? Math.round(part / total * 100) : 0
}
</script>
