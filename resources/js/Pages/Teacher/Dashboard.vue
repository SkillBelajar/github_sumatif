<template>
  <Layout>
    <template #nav>
      <form @submit.prevent="logout">
        <button type="submit" class="text-sm text-gray-500 hover:text-red-500">Logout</button>
      </form>
    </template>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dasbor Guru</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Selamat bekerja! Berikut ringkasan aktivitas Anda.</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 border-l-4 border-green-500">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Sesi Asesmen</p>
              <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">0</p>
            </div>
            <div class="text-3xl">📋</div>
          </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 border-l-4 border-blue-500">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Bank Soal</p>
              <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">0</p>
            </div>
            <div class="text-3xl">📚</div>
          </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 border-l-4 border-purple-500">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Hasil Asesmen</p>
              <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">0</p>
            </div>
            <div class="text-3xl">📊</div>
          </div>
        </div>
      </div>

      <div class="mt-8 bg-white dark:bg-gray-800 rounded-xl shadow p-8 text-center">
        <div class="text-4xl mb-4">🚀</div>
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Siap Memulai!</h2>
        <p class="mt-2 text-gray-500 dark:text-gray-400">Fitur pembuatan soal, sesi asesmen, dan analitik akan segera hadir di slice berikutnya.</p>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import Layout from '../../Plugins/InertiaLayout.vue'

function logout() {
  const form = useForm({})
  form.post('/logout')
}
</script>

<script>
import { useForm } from '@inertiajs/vue3'
export default { layout: false }
</script>
