<template>
  <div class="min-h-screen bg-gradient-to-br from-green-500 via-green-600 to-emerald-700 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8 text-center">
        <div class="text-5xl mb-3">✅</div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Ujian Selesai</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-300">{{ session.name }}</p>
        <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
          Jawaban Anda sudah tersimpan. Hasil akan diumumkan oleh guru.
        </p>
        <a href="/ujian" class="inline-block mt-6 px-6 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700">
          Kembali
        </a>
      </div>
      <p class="text-center text-green-100 text-xs mt-4">Versi: {{ version }}</p>
    </div>
  </div>
</template>

<script setup>
defineProps({ version: String, session: Object })
</script>
