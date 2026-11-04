<template>
  <Layout>
    <template #nav>
      <Link href="/" class="text-sm text-gray-500 hover:text-gray-700">← Kembali</Link>
    </template>

    <div class="min-h-[calc(100vh-200px)] flex items-center justify-center px-4 py-12">
      <div class="w-full max-w-md">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8">
          <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Masuk</h1>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Masuk untuk mengakses akun Anda</p>
          </div>

          <form @submit.prevent="submit" class="space-y-5">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
              <input v-model="form.email" type="email" required autofocus
                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                placeholder="email@contoh.com" />
              <p v-if="errors.email" class="mt-1 text-sm text-red-500">{{ errors.email }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password</label>
              <input v-model="form.password" type="password" required
                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                placeholder="••••••••" />
              <p v-if="errors.password" class="mt-1 text-sm text-red-500">{{ errors.password }}</p>
            </div>

            <div class="flex items-center">
              <input v-model="form.remember" type="checkbox" id="remember"
                class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500" />
              <label for="remember" class="ml-2 text-sm text-gray-600 dark:text-gray-400">Ingat saya</label>
            </div>

            <button type="submit" :disabled="processing"
              class="w-full py-2.5 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition disabled:opacity-50">
              {{ processing ? 'Memproses...' : 'Masuk' }}
            </button>
          </form>

          <p class="mt-6 text-center text-sm text-gray-500 dark:text-gray-400">
            Belum punya akun?
            <Link href="/register" class="text-indigo-600 hover:text-indigo-500 font-medium">Daftar sebagai Guru</Link>
          </p>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import Layout from '../../Plugins/InertiaLayout.vue'

const props = defineProps({
  errors: Object,
})

const form = useForm({
  email: '',
  password: '',
  remember: false,
})

const processing = computed(() => form.processing)

function submit() {
  form.post('/login', {
    onFinish: () => form.reset('password'),
  })
}
</script>

<script>
import { computed } from 'vue'
export default { layout: false }
</script>
