<template>
  <Layout>
    <template #nav>
      <a v-if="$page.props.auth?.user" :href="'/admin/dashboard'"
        class="text-sm text-gray-600 dark:text-gray-300 hover:text-indigo-600">
      Dasbor
      </a>
      <template v-else>
        <a href="/login"
          class="px-4 py-2 text-sm font-medium text-indigo-600 border border-indigo-600 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-900">
        Masuk
        </a>
        <a href="/register"
          class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
        Daftar sebagai Guru
        </a>
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
            <a href="/login"
              class="px-8 py-3 bg-white text-indigo-700 font-semibold rounded-lg shadow hover:bg-gray-100 transition">
            Masuk
            </a>
            <a href="/register"
              class="px-8 py-3 bg-yellow-400 text-indigo-900 font-semibold rounded-lg shadow hover:bg-yellow-300 transition">
            Daftar sebagai Guru
            </a>

            <!-- Kartu Masuk Ujian (siswa, tanpa login) -->
            <div class="mt-10 max-w-sm mx-auto bg-white rounded-2xl shadow-2xl p-5 text-gray-900">
              <h2 class="text-lg font-bold text-center text-indigo-700">Anda Siswa?</h2>
              <p class="text-xs text-center text-gray-500 mt-0.5 mb-3">Masukkan kode ujian dari guru Anda</p>
              <form action="/ujian" method="get" class="space-y-3">
                <input type="text" name="token" maxlength="6" placeholder="ABC123" required
                  @input="$event.target.value = $event.target.value.toUpperCase()"
                  class="w-full text-center text-2xl tracking-[0.4em] font-mono font-bold py-3 rounded-xl border-2 border-gray-300 uppercase focus:border-indigo-500 focus:outline-none" />
                <button type="submit"
                  class="w-full py-3 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 transition">
                  Masuk Ujian
                </button>
              </form>
              <a href="/ujian" class="block text-center text-xs text-indigo-500 hover:underline mt-3">Atau buka halaman ujian &rarr;</a>
            </div>
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
