<template>
  <Layout>
    <template #nav>
      <a href="/teacher/dashboard" v-if="role === 'teacher'" class="text-sm text-gray-500 hover:text-gray-700">← Dasbor</a>
      <a href="/admin/dashboard" v-else-if="role === 'admin'" class="text-sm text-gray-500 hover:text-gray-700">← Dasbor</a>
    </template>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="text-center mb-8">
        <div class="text-4xl mb-2">📖</div>
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">Panduan Pengguna</h1>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
          Panduan sesuai peran Anda:
          <span class="font-semibold text-indigo-600 dark:text-indigo-400">{{ roleLabel }}</span>
        </p>
      </div>

      <!-- PANDUAN ADMIN -->
      <template v-if="role === 'admin'">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 mb-4">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">👤 Dasbor Admin</h2>
          <p class="text-sm text-gray-600 dark:text-gray-300">
            Admin memantau guru yang mendaftar ke aplikasi Sumatif Guru.
          </p>
        </div>

        <Accordion title="1. Melihat Statistik Pendaftar Guru" :open="true">
          <ol class="list-decimal list-inside space-y-2 text-sm text-gray-600 dark:text-gray-300">
            <li>Masuk ke aplikasi menggunakan akun Admin.</li>
            <li>Buka menu <strong>Dasbor Admin</strong> di sidebar.</li>
            <li>Kartu <strong>"Total Guru Terdaftar"</strong> menampilkan jumlah seluruh guru yang sudah mendaftar.</li>
            <li>Tabel <strong>"Daftar Guru Terdaftar"</strong> berisi nama, email, nama sekolah, dan tanggal pendaftaran.</li>
            <li>Data terbaru muncul di urutan paling atas (diurutkan berdasarkan tanggal daftar terbaru).</li>
          </ol>
        </Accordion>

        <Accordion title="2. Catatan Penting untuk Admin">
          <ul class="list-disc list-inside space-y-2 text-sm text-gray-600 dark:text-gray-300">
            <li>Akun guru dibuat melalui halaman <strong>Register</strong> — setiap pendaftar otomatis mendapat peran "Guru".</li>
            <li>Admin tidak membuat soal atau ujian; itu tugas masing-masing guru di akunnya.</li>
            <li>Data guru bersifat privat per akun — seorang guru hanya melihat data miliknya sendiri.</li>
          </ul>
        </Accordion>
      </template>

      <!-- PANDUAN GURU -->
      <template v-else-if="role === 'teacher'">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 mb-4">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">🎓 Alur Lengkap Mengajar</h2>
          <p class="text-sm text-gray-600 dark:text-gray-300">
            Ikuti 6 langkah berikut dari awal hingga laporan nilai.
          </p>
        </div>

        <Accordion title="1. Daftarkan Kelas & Siswa" :open="true">
          <ol class="list-decimal list-inside space-y-2 text-sm text-gray-600 dark:text-gray-300">
            <li>Buka menu <strong>Kelas &amp; Siswa</strong> di sidebar.</li>
            <li>Ketik nama kelas (mis. <em>VII-A</em>), klik <strong>+ Kelas</strong>.</li>
            <li>Tambah siswa satu per satu lewat kolom nama + tombol <strong>+ Siswa</strong>.</li>
            <li>
              Atau <strong>Import CSV</strong>: siapkan file CSV satu kolom dengan header
              <em>Nama</em> (satu nama per baris), klik "Import CSV". Nama duplikat otomatis dilewati.
            </li>
            <li>Pastikan setiap kelas berisi siswa — tanpa siswa, dropdown nama di ujian akan kosong.</li>
          </ol>
        </Accordion>

        <Accordion title="2. Buat Mata Pelajaran & Tujuan Pembelajaran (TP)">
          <ol class="list-decimal list-inside space-y-2 text-sm text-gray-600 dark:text-gray-300">
            <li>Buka menu <strong>Tujuan Pembelajaran</strong>.</li>
            <li>Pilih mata pelajaran, lalu tambahkan TP (mis. <em>"Operasi Hitung Campuran"</em>).</li>
            <li>Isi <strong>KKTP</strong> (Kriteria Ketercapaian) — rubrik tiap level beserta nilai minimal dan kriteria penilaiannya.</li>
            <li>KKTP ini yang ditampilkan ke siswa di halaman persiapan ujian.</li>
          </ol>
        </Accordion>

        <Accordion title="3. Isi Bank Soal">
          <ol class="list-decimal list-inside space-y-2 text-sm text-gray-600 dark:text-gray-300">
            <li>Buka menu <strong>Bank Soal</strong>.</li>
            <li>Klik <strong>Tambah Soal</strong>: pilih TP, fase, tipe soal (Pilihan Ganda / Benar-Salah / Isian Singkat / Esai).</li>
            <li>Untuk PG isi opsi A–D dan kunci jawaban. Untuk Esai, kunci boleh kosong (dinilai manual).</li>
            <li><strong>Sangat disarankan</strong> isi kolom <strong>Pembahasan</strong> — teks ini tampil ke siswa setelah ujian.</li>
            <li>
              Banyak soal? Unduh <strong>template CSV</strong>, isi di Excel, lalu
              <strong>Import Soal</strong>. Baris yang gagal akan dilaporkan.
            </li>
          </ol>
        </Accordion>

        <Accordion title="4. Rakit Sesi Ujian">
          <ol class="list-decimal list-inside space-y-2 text-sm text-gray-600 dark:text-gray-300">
            <li>Buka menu <strong>Sesi Asesmen</strong>, klik <strong>+ Buat Sesi</strong>.</li>
            <li>Isi nama ujian, durasi (menit), dan token (atau biarkan auto-generate).</li>
            <li>Centang <strong>kelas peserta</strong> — bisa lebih dari satu kelas.</li>
            <li>Klik <strong>Rakit Soal</strong> → centang soal yang mau dipakai (bisa filter per TP/mapel).</li>
            <li>Terakhir klik <strong>Aktifkan Ujian</strong> — status jadi "Aktif" dan token siap dipakai siswa.</li>
          </ol>
        </Accordion>

        <Accordion title="5. Siswa Mengerjakan Ujian">
          <ol class="list-decimal list-inside space-y-2 text-sm text-gray-600 dark:text-gray-300">
            <li>Bagikan <strong>token</strong> ujian ke siswa.</li>
            <li>Siswa buka <strong>/ujian</strong> (tanpa login) → masukkan token → pilih namanya.</li>
            <li>Halaman persiapan menampilkan judul, durasi, dan KKTP — lalu klik <strong>MULAI UJIAN</strong>.</li>
            <li>Timer berjalan dari waktu server; jawaban autosave tiap kali diisi (aman kalau refresh).</li>
            <li>Soal muncul acak; tombol <strong>Kirim Ujian</strong> ada di soal terakhir.</li>
          </ol>
        </Accordion>

        <Accordion title="6. Cek Hasil & Unduh Laporan">
          <ol class="list-decimal list-inside space-y-2 text-sm text-gray-600 dark:text-gray-300">
            <li>Di daftar Sesi Asesmen, klik tombol <strong>Hasil</strong>.</li>
            <li>Halaman analitik menampilkan total siswa, rata-rata kelas, dan grafik tuntas/belum tuntas per TP.</li>
            <li>Tabel rekap: nilai per TP dengan tanda ✓ (tuntas) atau * (belum tuntas), plus nilai akhir.</li>
            <li>Klik <strong>Unduh Laporan (CSV)</strong> untuk ekspor ke Excel.</li>
            <li>Nilai akhir = rata-rata nilai seluruh TP; tuntas bila ≥ 70 (ambang di <em>config/asesmen.php</em>).</li>
          </ol>
        </Accordion>
      </template>

      <!-- GUEST -->
      <template v-else>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 text-sm text-gray-600 dark:text-gray-300">
          Masuk sebagai Admin atau Guru untuk melihat panduan sesuai peran Anda.
        </div>
      </template>

      <p class="text-gray-400 dark:text-gray-500 text-xs mt-8">Versi: 1.0 (Final)</p>
    </div>
  </Layout>
</template>

<script setup>
import { ref } from 'vue'
import Layout from '../Plugins/InertiaLayout.vue'
import Accordion from '../Components/Accordion.vue'

defineProps({ role: String })

const roleLabel = { admin: 'Admin', teacher: 'Guru' }
</script>
