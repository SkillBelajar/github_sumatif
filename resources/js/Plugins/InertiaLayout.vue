<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 flex flex-col">
    <nav class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex items-center">
            <span class="text-xl font-bold text-indigo-600 dark:text-indigo-400">📝 Asesmen Sumatif</span>
          </div>
          <div class="flex items-center space-x-4">
            <slot name="nav" />
          </div>
        </div>
      </div>
    </nav>

    <div class="flex flex-1">
      <!-- Sidebar guru -->
      <aside v-if="$page.props.auth?.user?.role === 'teacher'"
        class="w-64 shrink-0 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 hidden md:block">
        <nav class="p-4 space-y-1">
          <p class="px-3 pt-2 pb-3 text-xs font-semibold uppercase tracking-wider text-gray-400">Menu Guru</p>
          <a href="/teacher/dashboard"
            :class="['block px-3 py-2 rounded-lg text-sm font-medium',
              $page.url === '/teacher/dashboard'
                ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-200'
                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700']">
            Dasbor
          </a>
          <a href="/teacher/classrooms"
            :class="['block px-3 py-2 rounded-lg text-sm font-medium',
              $page.url.startsWith('/teacher/classrooms')
                ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-200'
                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700']">
            Kelas &amp; Siswa
          </a>
          <a href="/teacher/learning-objectives"
            :class="['block px-3 py-2 rounded-lg text-sm font-medium',
              $page.url.startsWith('/teacher/learning-objectives')
                ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-200'
                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700']">
            Tujuan Pembelajaran
          </a>
          <a href="/teacher/question-bank"
            :class="['block px-3 py-2 rounded-lg text-sm font-medium',
              $page.url.startsWith('/teacher/question-bank')
                ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-200'
                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700']">
            Bank Soal
          </a>
          <a href="/teacher/assessments"
            :class="['block px-3 py-2 rounded-lg text-sm font-medium',
              $page.url.startsWith('/teacher/assessments')
                ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-200'
                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700']">
            Sesi Asesmen
          </a>
          <p class="px-3 pt-4 pb-1 text-xs font-semibold uppercase tracking-wider text-gray-400">Bantuan</p>
          <a href="/panduan"
            :class="['block px-3 py-2 rounded-lg text-sm font-medium',
              $page.url.startsWith('/panduan')
                ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-200'
                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700']">
            Panduan
          </a>
          <p class="px-3 pt-4 text-xs text-gray-400">Versi: {{ $page.props.appVersion }}</p>
        </nav>
      </aside>

      <!-- Sidebar admin -->
      <aside v-else-if="$page.props.auth?.user?.role === 'admin'"
        class="w-64 shrink-0 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 hidden md:block">
        <nav class="p-4 space-y-1">
          <p class="px-3 pt-2 pb-3 text-xs font-semibold uppercase tracking-wider text-gray-400">Menu Admin</p>
          <a href="/admin/dashboard"
            :class="['block px-3 py-2 rounded-lg text-sm font-medium',
              $page.url === '/admin/dashboard'
                ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-200'
                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700']">
            Dasbor
          </a>
          <p class="px-3 pt-4 pb-1 text-xs font-semibold uppercase tracking-wider text-gray-400">Bantuan</p>
          <a href="/panduan"
            :class="['block px-3 py-2 rounded-lg text-sm font-medium',
              $page.url.startsWith('/panduan')
                ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-200'
                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700']">
            Panduan
          </a>
          <p class="px-3 pt-4 text-xs text-gray-400">Versi: {{ $page.props.appVersion }}</p>
        </nav>
      </aside>

      <div class="flex-1 min-w-0">
        <main>
          <slot />
        </main>
      </div>
    </div>

    <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 py-4 mt-auto">
      <div class="max-w-7xl mx-auto px-4 text-center text-sm text-gray-500 dark:text-gray-400">
        Versi: {{ $page.props.appVersion }}
      </div>
    </footer>

    <FlashToast />
  </div>
</template>

<script setup>
import FlashToast from '../Components/FlashToast.vue'
</script>
