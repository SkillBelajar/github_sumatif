<template>
  <Layout>
    <!-- Hero Section -->
    <div class="relative bg-white dark:bg-gray-900">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 lg:py-24">
        <div class="text-center">
          <h1 class="text-4xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-5xl md:text-6xl">
            <span class="block">Asesmen Sumatif</span>
            <span class="block text-indigo-600 dark:text-indigo-400">Mandiri Siswa</span>
          </h1>
          <p class="mt-3 max-w-md mx-auto text-base text-gray-500 dark:text-gray-400 sm:text-lg md:mt-5 md:text-xl md:max-w-3xl">
            Aplikasi Asesmen Digital Formatif &amp; Sumatif per Tujuan Pembelajaran — Kurikulum Merdeka
          </p>
          <div class="mt-5 max-w-md mx-auto sm:flex sm:justify-center md:mt-8">
            <div class="rounded-md shadow">
              <a href="#" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 md:py-4 md:text-lg md:px-10">
                Mulai Asesmen
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Features Grid -->
    <div class="py-12 bg-gray-50 dark:bg-gray-800">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
          <div class="bg-white dark:bg-gray-900 rounded-lg shadow p-6">
            <div class="text-3xl mb-4">📝</div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Bank Soal per TP</h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
              Guru dapat membuat dan mengelola soal asesmen sesuai Tujuan Pembelajaran Kurikulum Merdeka.
            </p>
          </div>
          <div class="bg-white dark:bg-gray-900 rounded-lg shadow p-6">
            <div class="text-3xl mb-4">⚡</div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Penilaian Instan</h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
              Nilai otomatis per Tujuan Pembelajaran dengan umpan balik langsung untuk siswa.
            </p>
          </div>
          <div class="bg-white dark:bg-gray-900 rounded-lg shadow p-6">
            <div class="text-3xl mb-4">📱</div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Mobile-Friendly</h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
              Akses melalui browser di perangkat apapun — mobile maupun desktop.
            </p>
          </div>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import Layout from '../Plugins/InertiaLayout.vue'
</script>
