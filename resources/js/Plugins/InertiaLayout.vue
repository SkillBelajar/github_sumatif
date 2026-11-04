<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
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
    <main>
      <slot />
    </main>
    <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 py-4 mt-auto">
      <div class="max-w-7xl mx-auto px-4 text-center text-sm text-gray-500 dark:text-gray-400">
        Versi: 0.12
      </div>
    </footer>
  </div>
</template>
