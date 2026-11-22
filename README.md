# Sistem Penyewaan Garasi Kendaraan

Aplikasi backend berbasis web (**Laravel + MySQL**) untuk manajemen penyewaan garasi kendaraan (Sepeda, Mobil Bensin, dan Mobil Listrik) yang dibuat untuk memenuhi **Uji Kompetensi Pemrogram** (Skema Sertifikasi: `IMT.01.15/SSK/LSP/IX/2021`).

- **Nama Asesi:** Javin Erasmus Clementino
- **NIM:** 0806022310025
- **Program Studi:** Informatika — Universitas Ciputra

---

## Fitur Utama & Aturan Bisnis

### Tipe Garasi & Penomoran Otomatis
Setiap tipe garasi memiliki kode ID otomatis (*auto-increment*) maksimal **999 unit** per tipe:

| Tipe Garasi | Kode Internal | Rentang ID | Kendaraan yang Diizinkan |
| :--- | :--- | :--- | :--- |
| Garasi Sepeda | `bike` | `S001` - `S999` | Sepeda, Sepeda Motor |
| Garasi Mobil | `car` | `C001` - `C999` | Mobil Bensin, Mobil Listrik |
| Garasi Mobil Listrik | `ev_car` | `E001` - `E999` | Mobil Listrik (wajib isi daya penuh) |

### Skema Sewa & Harga
- **Sewa Harian:** Maksimal 30 hari (`harga_harian * jumlah_hari`).
- **Sewa Bulanan:** Otomatis `25 * harga_harian`.
- **Mobil Listrik di Garasi EV:** Ditambah biaya pengisian daya hingga penuh (`kapasitas_baterai_kwh * harga_listrik_per_kwh`).
- **Multi-Sewa:** Satu penyewa dapat menyewa lebih dari satu garasi secara bersamaan.
- **Status Otomatis:** Setelah transaksi disimpan, status garasi otomatis berubah dari `available` menjadi `used`.

### Fitur Berdasarkan Role
- **Penyewa (`tenant`):** Registrasi akun (NIK 16 digit), Login/Logout, cek daftar garasi tersedia sesuai tipe kendaraan, dan sewa garasi baru dengan upload bukti pelunasan.
- **Admin (`admin`):** Login/Logout, buat garasi berkelompok, verifikasi pendaftar baru (KTP: `approved`/`rejected`), perpanjang status garasi aktif hari ini dengan upload bukti bayar, dan laporan sewa garasi dengan **4 filter** (per bulan dalam tahun tertentu, per hari dalam bulan tertentu, per tipe garasi, dan per penyewa).

---

## Teknologi & Library yang Digunakan
- **PHP 8.2+ & Laravel Framework** — Arsitektur MVC, REST API, validasi request, dan penerapan OOP.
- **MySQL & Eloquent ORM** — Basis data relasional (5 tabel: `users`, `vehicles`, `garages`, `rentals`, `rental_extensions`) dengan dukungan `DB::transaction`.
- **Laravel Sanctum** — Autentikasi API berbasis *Bearer Token* dan pemisahan role Admin/Penyewa.
- **Carbon** — Manipulasi tanggal mulai otomatis, tanggal selesai, dan selisih hari perpanjangan.
- **PHPUnit** — Pengujian unit otomatis (*automated unit testing*).

---

## Struktur Proyek
```text
app/
├── Http/
│   ├── Controllers/
│   │   ├── AuthController.php        # Registrasi, Login, Logout
│   │   ├── RentalController.php      # Cek garasi tersedia & transaksi sewa baru
│   │   └── AdminController.php       # Batch garasi, verifikasi, perpanjangan, laporan
│   └── Middleware/
│       └── EnsureUserIsAdmin.php     # Proteksi endpoint khusus Admin
├── Models/
│   ├── User.php
│   ├── Vehicle.php
│   ├── Garage.php
│   ├── Rental.php
│   └── RentalExtension.php
└── Services/
    ├── GarageService.php             # Logika pembuatan garasi berkelompok & ID otomatis
    └── RentalService.php             # Kalkulasi biaya, transaksi sewa, & perpanjangan
database/
├── migrations/                       # Migrasi 5 tabel relasional
├── seeders/DatabaseSeeder.php        # Seeder akun Admin default
└── schema_mysql.sql                  # Skema SQL alternatif
docs/
└── postman_collection.json           # Koleksi Postman untuk uji & demo API
routes/
└── api.php                           # Definisi seluruh endpoint REST API
tests/
└── Unit/GarageRentalTest.php         # 7 skenario pengujian unit (PHPUnit)
```

---

## Cara Instalasi & Menjalankan Lokal

### Prasyarat
- PHP >= 8.2 (ekstensi `pdo_mysql`, `pdo_sqlite`, dan `sqlite3` aktif)
- Composer
- MySQL (Laragon / XAMPP / MySQL Server)

### Langkah-Langkah
1. **Clone repositori ini dan masuk ke folder proyek:**
   ```bash
   git clone https://github.com/USERNAME_ANDA/NAMA_REPO.git
   cd NAMA_REPO
   ```

2. **Install dependensi PHP:**
   ```bash
   composer install
   ```

3. **Salin konfigurasi environment dan generate key:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Konfigurasi database pada file `.env`:**
   Buat database kosong bernama `sewa_garasi` di MySQL, lalu sesuaikan `.env`:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=sewa_garasi
   DB_USERNAME=root
   DB_PASSWORD=

   SESSION_DRIVER=file
   ```

5. **Buat symbolic link storage, jalankan migrasi, dan seeder:**
   ```bash
   php artisan storage:link
   php artisan migrate --seed
   ```
   Seeder akan membuat akun Admin default:
   - **Email:** `admin@garasi.local`
   - **Password:** `admin12345`

6. **Jalankan server lokal:**
   ```bash
   php artisan serve
   ```
   API aktif pada `http://127.0.0.1:8000/api`.

---

## Daftar Endpoint API

| Fitur | Role | Method | Endpoint |
| :--- | :--- | :---: | :--- |
| Registrasi Penyewa | Publik | `POST` | `/api/register` |
| Login | Publik | `POST` | `/api/login` |
| Logout | Auth | `POST` | `/api/logout` |
| Cek Garasi Tersedia | Penyewa | `GET` | `/api/garages/available?vehicle_type=ev_car` |
| Sewa Garasi Baru | Penyewa | `POST` | `/api/rentals` (`form-data`) |
| Buat Garasi Berkelompok | Admin | `POST` | `/api/admin/garages/batch` |
| Verifikasi Pendaftar Baru | Admin | `PATCH` | `/api/admin/tenants/{user}/verify` |
| Sewa Aktif Hari Ini | Admin | `GET` | `/api/admin/rentals/active-today` |
| Perpanjang Status Garasi | Admin | `POST` | `/api/admin/rentals/{rental}/extend` (`form-data`) |
| Laporan Sewa Garasi | Admin | `GET` | `/api/admin/reports` (`?year=`, `&month=`, `?garage_type=`, `?user_id=`) |

> Koleksi Postman siap pakai tersedia di `docs/postman_collection.json`.

---

## Menjalankan Unit Testing
Jalankan pengujian unit menggunakan PHPUnit:
```bash
php artisan test --filter=GarageRentalTest
```

Mencakup **7 skenario pengujian** (11 assertions):
1. Penomoran ID garasi otomatis (`S001`, `S002`, `C001`, `E001`).
2. Rumus harga sewa bulanan (`25 * harga harian`).
3. Validasi batas maksimal sewa harian (maks. 30 hari).
4. Validasi aturan penempatan kendaraan terhadap tipe garasi.
5. Perhitungan biaya listrik penuh untuk mobil listrik di garasi EV.
6. Mobil listrik di garasi mobil biasa tanpa biaya listrik tambahan.
7. Perubahan status garasi otomatis menjadi `used` setelah transaksi disimpan.