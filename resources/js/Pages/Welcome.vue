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
            <a href="/ujian"
              class="px-8 py-3 bg-green-500 text-white font-semibold rounded-lg shadow hover:bg-green-600 transition">
            Masuk Ujian
            </a>
          </div>
          <p class="mt-4 text-sm text-indigo-200">
            Siswa? Masuk ujian langsung dengan kode dari guru &mdash; tanpa daftar akun.
          </p>
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
    <!-- Standar Asesmen Sumatif -->
    <div class="py-16 bg-gray-50 dark:bg-gray-950">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Sesuai Standar Asesmen Sumatif</h2>
          <p class="mt-2 text-gray-500 dark:text-gray-400">
            Panduan Pembelajaran dan Asesmen — edisi revisi 2025, Kemendikdasmen/BSKAP
          </p>
          <p class="mt-1 text-sm text-gray-400 dark:text-gray-500">
            Payung hukum: Permendikbudristek No. 12 Tahun 2024 &amp; PP 57/2021 Standar Nasional Pendidikan
          </p>
        </div>

        <ol class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <li v-for="(item, i) in standar" :key="i"
            class="p-6 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
            <div class="flex items-start gap-3">
              <span class="flex-shrink-0 w-8 h-8 rounded-full bg-indigo-600 text-white text-sm font-bold flex items-center justify-center">
                {{ i + 1 }}
              </span>
              <div>
                <h3 class="font-semibold text-gray-900 dark:text-white">{{ item.judul }}</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ item.isi }}</p>
                <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">{{ item.contoh }}</p>
              </div>
            </div>
          </li>
        </ol>

        <div class="mt-10 p-6 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
          <h3 class="font-semibold text-gray-900 dark:text-white mb-3">Referensi</h3>
          <ul class="space-y-2 text-sm">
            <li v-for="ref in referensi" :key="ref.url">
              <a :href="ref.url" target="_blank" rel="noopener noreferrer"
                class="text-indigo-600 dark:text-indigo-400 hover:underline">{{ ref.teks }}</a>
              <span v-if="ref.catatan" class="text-gray-400 dark:text-gray-500"> — {{ ref.catatan }}</span>
            </li>
          </ul>
          <p class="mt-4 text-xs text-gray-400 dark:text-gray-500">
            Poin 1&ndash;7 bersumber dari panduan; poin 8&ndash;9 dari ISO/IEC 23988:2007 (pelengkap, belum menjadi SNI).
          </p>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import Layout from '../Plugins/InertiaLayout.vue'

const standar = [
  { judul: 'Soal per Tujuan Pembelajaran', isi: 'Soal disimpan di bank soal bertanda TP, baru digabung jadi kuis.', contoh: 'Mapel IPA kelas 9 → TP "menjelaskan induksi elektromagnetik" berisi 12 soal pilihan ganda.' },
  { judul: 'Kelas & fase sebagai label', isi: 'Satu bank soal saja; kelas dan fase jadi label yang bisa difilter.', contoh: '1 soal dipakai 9A dan 9C tanpa copy-paste.' },
  { judul: 'Instrumen beragam', isi: 'Pilihan ganda, benar-salah, isian singkat, dan esai singkat.', contoh: '15 PG + 2 esai singkat per kuis.' },
  { judul: 'KKTP jelas di awal', isi: 'Kriteria ketercapaian ditulis sebelum siswa mulai, bukan setelah dinilai.', contoh: 'Rubrik 4 tingkat: perlu bimbingan, cukup, baik, sangat baik — tampil di halaman depan kuis.' },
  { judul: 'Hasil per TP', isi: 'Nilai keluar per tujuan pembelajaran, bukan hanya satu angka akhir.', contoh: 'TP 3 (56–100 = tuntas): 62 ✓ | TP 5: 48 ✻ belum tuntas.' },
  { judul: 'Umpan balik instan', isi: 'Pembahasan muncul langsung setelah menjawab — sisi formatif agar siswa belajar mandiri.', contoh: 'Salah nomor 4 → keluar pembahasan 3 baris + tautan materi.' },
  { judul: 'Sesi pendek & bergelombang', isi: 'Asesmen sumatif dipecah beberapa kegiatan agar siswa tidak terburu-buru.', contoh: '3 kuis @20 menit (bukan 1 ujian 90 menit); rata-rata jadi nilai sumatif.' },
  { judul: 'Keadilan & keamanan', isi: 'Token sesi, waktu berjalan, satu kali submit, urutan soal diacak, UI sesederhana mungkin.', contoh: 'Tombol raksasa "Mulai", tanpa menu tersembunyi.' },
  { judul: 'Pelaporan ke guru', isi: 'Guru melihat siapa belum tuntas di TP mana, dan bisa mengekspor rekap per kelas.', contoh: 'Satu halaman: 9A 32 siswa, 5 siswa belum tuntas TP 5, tombol "Unduh CSV".' },
]

const referensi = [
  { teks: 'Panduan Pembelajaran dan Asesmen, edisi revisi 2025 (BSKAP/Kemendikdasmen)', url: 'https://uploads.belajar.id/document/files/Pembelajaran_dan_Asesmen_2025_01k17ee2v8pqwqybn0y9n8pcd1.pdf', catatan: '' },
  { teks: 'Permendikbudristek No. 12 Tahun 2024 tentang Kurikulum', url: 'https://peraturan.go.id/id/permendikbudristek-no-12-tahun-2024', catatan: 'payung hukum asesmen' },
  { teks: 'IMS QTI 3.0', url: 'https://www.imsglobal.org/spec/qti/v3p0/oview', catatan: 'teknis pelengkap, bukan dasar hukum' },
  { teks: 'ISO/IEC 23988:2007', url: 'https://www.iso.org/standard/41840.html', catatan: 'praktik pengamanan asesmen berbasis komputer' },
]
</script>
