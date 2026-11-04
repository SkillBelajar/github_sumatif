<template>
  <Layout>
    <template #nav>
      <form @submit.prevent="logout">
        <button type="submit" class="text-sm text-gray-500 hover:text-red-500">Logout</button>
      </form>
    </template>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dasbor Admin</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Selamat datang, Administrator</p>
      </div>

      <!-- Widgets -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 border-l-4 border-indigo-500">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Guru Terdaftar</p>
              <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ totalTeachers }}</p>
            </div>
            <div class="text-3xl">👨‍🏫</div>
          </div>
        </div>
      </div>

      <!-- Teachers Table -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Daftar Guru Terdaftar</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-700">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Email</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama Sekolah</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tanggal Daftar</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="teacher in teachers" :key="teacher.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ teacher.name }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ teacher.email }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ teacher.school_name }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ formatDate(teacher.created_at) }}</td>
              </tr>
              <tr v-if="teachers.length === 0">
                <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">Belum ada guru yang terdaftar.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import Layout from '../../Plugins/InertiaLayout.vue'

defineProps({
  totalTeachers: Number,
  teachers: Array,
})

function formatDate(dateString) {
  return new Date(dateString).toLocaleDateString('id-ID', {
    day: '2-digit', month: 'long', year: 'numeric'
  })
}

function logout() {
  const form = useForm({})
  form.post('/logout')
}
</script>

<script>
import { useForm } from '@inertiajs/vue3'
export default { layout: false }
</script>
