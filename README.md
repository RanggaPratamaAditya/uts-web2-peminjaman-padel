\# Sistem Informasi Peminjaman Lapangan Padel



\## 📌 Deskripsi



Sistem Informasi Peminjaman Lapangan Padel adalah aplikasi web yang digunakan untuk mengelola data lapangan dan peminjaman lapangan padel.



Project ini dibuat sebagai tugas UTS Web 2 dengan menggunakan \*\*Vue.js sebagai frontend, Laravel sebagai backend/API, dan MySQL/MariaDB sebagai database\*\*.



Aplikasi menyediakan fitur login, logout, pembatasan akses, pengelolaan data lapangan, pengelolaan data peminjaman, validasi data, dan penyimpanan data ke database.



\---



\## 🛠️ Teknologi yang Digunakan



\* \*\*Frontend:\*\* Vue.js + JavaScript

\* \*\*Build Tool:\*\* Vite

\* \*\*Backend:\*\* Laravel

\* \*\*Bahasa Backend:\*\* PHP

\* \*\*Database:\*\* MySQL / MariaDB

\* \*\*API:\*\* REST API

\* \*\*ORM:\*\* Eloquent



\---



\## ✨ Fitur Sistem



\* Login pengguna

\* Logout pengguna

\* Pembatasan akses

\* Dashboard

\* Melihat data lapangan

\* Menambah data peminjaman

\* Melihat data peminjaman

\* Mengedit data peminjaman

\* Validasi data pada server

\* Pesan kesalahan

\* Penyimpanan data ke database

\* Data tetap tersedia setelah halaman di-refresh



\---



\## 📂 Struktur Project



```text

uts-web2/

│

├── backend/

│   └── Laravel REST API

│

├── frontend/

│   └── Vue.js + Vite

│

├── README.md

└── .gitignore

```



\---



\## 🗃️ Modul Sistem



\### 1. Peminjaman



Peminjaman merupakan \*\*modul utama\*\* dalam sistem.



Modul ini digunakan untuk mengelola data peminjaman lapangan, seperti:



\* Pengguna

\* Lapangan

\* Tanggal peminjaman

\* Waktu mulai

\* Waktu selesai

\* Status peminjaman



\### 2. Lapangan



Lapangan merupakan \*\*modul pendukung\*\* yang digunakan untuk menyimpan informasi lapangan yang tersedia.



\### 3. Users



Users digunakan untuk kebutuhan:



\* Login

\* Logout

\* Autentikasi

\* Pembatasan akses pengguna



\---



\## 🔗 Relasi Database



Relasi utama sistem adalah antara tabel `lapangans` dan `peminjamans`.



```text

lapangans

&#x20;   │

&#x20;   │ 1

&#x20;   │

&#x20;   │ N

&#x20;   ▼

peminjamans

```



Artinya:



\*\*Satu lapangan dapat memiliki banyak data peminjaman.\*\*



Foreign key:



```text

peminjamans.lapangan\_id

&#x20;       ↓

lapangans.id

```



Jika digunakan pada implementasi:



```text

peminjamans.user\_id

&#x20;       ↓

users.id

```



\---



\## 🏗️ Arsitektur Sistem



```text

┌─────────────────────┐

│       Vue.js        │

│      Frontend       │

└──────────┬──────────┘

&#x20;          │

&#x20;          │ HTTP Request

&#x20;          ▼

┌─────────────────────┐

│       Laravel       │

│     REST API        │

│                     │

│ Controller          │

│ Validation          │

│ Model / Eloquent    │

└──────────┬──────────┘

&#x20;          │

&#x20;          │ Query

&#x20;          ▼

┌─────────────────────┐

│    MySQL / MariaDB  │

│      Database       │

└─────────────────────┘

```



\---



\## 🚀 Cara Menjalankan Backend



Masuk ke folder backend:



```bash

cd backend

```



Install dependency Laravel:



```bash

composer install

```



Pastikan file `.env` sudah dikonfigurasi dengan database yang digunakan.



Contoh:



```text

DB\_DATABASE=uts\_web2

DB\_USERNAME=root

DB\_PASSWORD=

```



Jalankan migration:



```bash

php artisan migrate

```



Jalankan server Laravel:



```bash

php artisan serve

```



Backend dapat diakses melalui:



```text

http://127.0.0.1:8000

```



\---



\## 🚀 Cara Menjalankan Frontend



Buka terminal baru.



Masuk ke folder frontend:



```bash

cd frontend

```



Install dependency:



```bash

npm install

```



Jalankan Vite:



```bash

npm run dev

```



Frontend dapat diakses melalui:



```text

http://localhost:5173

```



\---



\## 🔄 Alur Utama Sistem



```text

Login

&#x20; ↓

Dashboard

&#x20; ↓

Daftar Peminjaman

&#x20; ↓

Tambah Peminjaman

&#x20; ↓

Validasi Server

&#x20; ↓

Simpan ke Database

&#x20; ↓

Data Ditampilkan

&#x20; ↓

Refresh Halaman

&#x20; ↓

Data Tetap Tersedia

&#x20; ↓

Edit Peminjaman

&#x20; ↓

Logout

```



\---



\## 🧪 Pengujian



| No | Skenario                | Hasil yang Diharapkan                      |

| -- | ----------------------- | ------------------------------------------ |

| 1  | Login dengan data benar | Pengguna berhasil masuk                    |

| 2  | Login dengan data salah | Sistem menampilkan pesan error             |

| 3  | Logout                  | Pengguna keluar dari sistem                |

| 4  | Akses tanpa login       | Sistem membatasi akses                     |

| 5  | Melihat data peminjaman | Data tampil dari database                  |

| 6  | Menambah peminjaman     | Data berhasil disimpan                     |

| 7  | Data tidak valid        | Server menolak data                        |

| 8  | Mengedit peminjaman     | Data berhasil diperbarui                   |

| 9  | Refresh halaman         | Data tetap tersedia                        |

| 10 | Jadwal bentrok          | Sistem menolak jika validasi konflik aktif |



\---



\## ⚠️ Keterbatasan



Project ini masih merupakan mini project untuk kebutuhan UTS sehingga beberapa fitur belum tersedia, antara lain:



\* Pembayaran online

\* Notifikasi otomatis

\* Integrasi kalender eksternal

\* Laporan dan analitik lanjutan

\* Deployment production



\---



\## 👨‍💻 Identitas Project



\*\*Nama:\*\* Rangga Pratama Aditya



\*\*Mata Kuliah:\*\* Web 2



\*\*Jenis Project:\*\* UTS Web 2



\*\*Judul:\*\* Sistem Informasi Peminjaman Lapangan Padel



\*\*Tahun:\*\* 2026



