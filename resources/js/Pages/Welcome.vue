<template>
  <Layout>
    <template #nav>
      <Link v-if="$page.props.auth?.user" :href="route('admin.dashboard')"
        class="text-sm text-gray-600 dark:text-gray-300 hover:text-indigo-600">
      Dasbor
      </Link>
      <template v-else>
        <Link href="/login"
          class="px-4 py-2 text-sm font-medium text-indigo-600 border border-indigo-600 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-900">
        Masuk
        </Link>
        <Link href="/register"
          class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
        Daftar sebagai Guru
        </Link>
      </template>
    </template>

    <!-- Hero Section -->
    <div class="relative bg-gradient-to-br from-indigo-600 to-purple-700 text-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 lg:py-28">
        <div class="text-center">
          <h1 class="text-4xl font-bold tracking-tight sm:text-5xl md:text-6xl">
            Asesmen Sumatif
            <br>
            <span class="text-yellow-300">Kurikulum Merdeka</span>
          </h1>
          <p class="mt-6 max-w-2xl mx-auto text-lg text-indigo-100">
            Platform digital asesmen formatif &amp; sumatif per Tujuan Pembelajaran. Guru buat soal dengan mudah, siswa
            mengerjakan via token, nilai otomatis langsung jadi!
          </p>
          <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center">
            <Link href="/login"
              class="px-8 py-3 bg-white text-indigo-700 font-semibold rounded-lg shadow hover:bg-gray-100 transition">
            Masuk
            </Link>
            <Link href="/register"
              class="px-8 py-3 bg-yellow-400 text-indigo-900 font-semibold rounded-lg shadow hover:bg-yellow-300 transition">
            Daftar sebagai Guru
            </Link>
          </div>
        </div>
      </div>
    </div>

    <!-- Features -->
    <div class="py-16 bg-white dark:bg-gray-900">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Fitur Unggulan</h2>
          <p class="mt-2 text-gray-500 dark:text-gray-400">Semua yang Anda butuhkan untuk asesmen Kurikulum Merdeka</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div class="text-center p-6 rounded-xl bg-indigo-50 dark:bg-gray-800">
            <div class="text-4xl mb-4">📝</div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Bank Soal per TP</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Buat dan kelola soal asesmen sesuai Tujuan Pembelajaran Kurikulum Merdeka</p>
          </div>
          <div class="text-center p-6 rounded-xl bg-purple-50 dark:bg-gray-800">
            <div class="text-4xl mb-4">⚡</div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Penilaian Instan</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Nilai otomatis per Tujuan Pembelajaran dengan umpan balik langsung untuk siswa</p>
          </div>
          <div class="text-center p-6 rounded-xl bg-pink-50 dark:bg-gray-800">
            <div class="text-4xl mb-4">📱</div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Mobile-Friendly</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Akses melalui browser di perangkat apapun — mobile maupun desktop</p>
          </div>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import Layout from '../Plugins/InertiaLayout.vue'
</script>
