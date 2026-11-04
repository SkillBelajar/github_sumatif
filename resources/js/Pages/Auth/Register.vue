<template>
  <Layout>
    <template #nav>
      <Link href="/" class="text-sm text-gray-500 hover:text-gray-700">← Kembali</Link>
    </template>

    <div class="min-h-[calc(100vh-200px)] flex items-center justify-center px-4 py-12">
      <div class="w-full max-w-md">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8">
          <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Daftar sebagai Guru</h1>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Buat akun untuk mulai membuat asesmen</p>
          </div>

          <form @submit.prevent="submit" class="space-y-5">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Lengkap</label>
              <input v-model="form.name" type="text" required
                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                placeholder="Nama Lengkap" />
              <p v-if="errors.name" class="mt-1 text-sm text-red-500">{{ errors.name }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
              <input v-model="form.email" type="email" required
                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                placeholder="email@contoh.com" />
              <p v-if="errors.email" class="mt-1 text-sm text-red-500">{{ errors.email }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Sekolah</label>
              <input v-model="form.school_name" type="text" required
                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                placeholder="SMK Negeri 1 ..." />
              <p v-if="errors.school_name" class="mt-1 text-sm text-red-500">{{ errors.school_name }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password</label>
              <input v-model="form.password" type="password" required minlength="8"
                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                placeholder="Min. 8 karakter" />
              <p v-if="errors.password" class="mt-1 text-sm text-red-500">{{ errors.password }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Konfirmasi Password</label>
              <input v-model="form.password_confirmation" type="password" required
                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                placeholder="Ulangi password" />
            </div>

            <button type="submit" :disabled="processing"
              class="w-full py-2.5 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition disabled:opacity-50">
              {{ processing ? 'Memproses...' : 'Daftar' }}
            </button>
          </form>

          <p class="mt-6 text-center text-sm text-gray-500 dark:text-gray-400">
            Sudah punya akun?
            <Link href="/login" class="text-indigo-600 hover:text-indigo-500 font-medium">Masuk</Link>
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
  name: '',
  email: '',
  school_name: '',
  password: '',
  password_confirmation: '',
})

const processing = computed(() => form.processing)

function submit() {
  form.post('/register', {
    onFinish: () => form.reset('password', 'password_confirmation'),
  })
}
</script>

<script>
import { computed } from 'vue'
export default { layout: false }
</script>
