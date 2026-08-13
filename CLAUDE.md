# CLAUDE.md - Project Guidelines & Context

## Project Overview

- **Framework:** Laravel 13.x
- **PHP Version:** 8.4
- **Database:** PostgreSQL
- **Frontend Stack:** TailwindCSS v4.1.x

---

## Technical Stack & Architecture Guidelines

### 1. Code Style & Standards

- Ikuti standar **PSR-12**.
- Gunakan **Strict Types** pada setiap file PHP: `declare(strict_types=1);`.
- **Form Request:** Selalu pisahkan logika validasi dari Controller ke file `FormRequest`.
- **Services & Repositories:** Tempatkan logika bisnis yang kompleks di layer `Services`, jangan menumpuk kode bisnis di Controller atau Model.
- **Type Hinting & Return Types:** Setiap method/fungsi **wajib** memiliki type hint pada argumen dan return type secara eksplisit.

### 2. Laravel Specific Conventions

- Gunakan **Eloquent API Resources** untuk format response JSON.
- Gunakan **Enums** (PHP 8.4+) untuk status atau tipe data yang bersifat fixed.
- Gunakan **Database Transactions** (`DB::transaction`) untuk operasi mutasi data berseri/multi-tabel.
- Jangan pernah memanggil query database langsung di dalam Blade views.

### 3. Database & Migrations

- Setiap migration wajib memiliki foreign key constraint dan indexes pada kolom yang sering di-query.
- Selalu sediakan `down()` method pada migration untuk kemudahan rollback.
- Gunakan `Model::preventLazyLoading(!app()->isProduction());` pada AppServiceProvider untuk mencegah issue N+1 Query.

### 3. Database & Migrations

- Menggunakan PostgreSql
- gunakan UUID

---

## Development Workflow & Commands

### Useful Commands

```bash
# Development Server
php artisan serve
php artisan migrate

# Run Queues & Reverb
php artisan queue:work
php artisan reverb:start

# Run Tests
php artisan test --parallel

# Code Formatting & Static Analysis
./vendor/bin/pint
./vendor/bin/phpstan analyse
```

## claude config set -g allowedTools "Bash(php artisan*),Bash(sed*),Bash(git\*)"

## Terminal & Command Permissions

- **Auto-Approved Commands:**
    - `php artisan *`
    - `herd php artisan *`
    - `sed *`
    - `git status` / `git add` / `git commit`
- Execute terminal commands directly without asking for confirmation if they match the auto-approved pattern above.

- **DataTables:** Gunakan `yajra/laravel-datatables` untuk server-side processing pada tabel data padat.
- **Form & UI Components:**
    - Gunakan **SweetAlert2** untuk pop-up konfirmasi (hapus data, sukses/gagal).
    - Gunakan **Select2** untuk dropdown dengan fitur pencarian.
- gunakan Bootstrap v5.2.0 untuk class html dan css nya di setiap blade maupun component
- gunakan mapbox https://www.mapbox.com/ untuk fitur yang menampilkan peta interaktif, hitmap dan lainnya tergantung permintaan saya.
- Contoh template html ada di folder views/template-html. gunakan sebagai referensi layout atau class html
- Konsep menu sidebar menggunakan Tabular Menu.

## Component Guidelines

- Semua halaman index yang menampilkan list data utama **wajib** menggunakan Yajra DataTables (Server-side AJAX).
- Hindari membuat perulangan `@foreach` Blade manual untuk data berukuran besar; gunakan DataTables controller response.

---

## AI / Claude Interaction Rules (Token Savers)

1. **Be Concise & Direct:** Berikan jawaban langsung ke kode solusi tanpa penjelasan teori/intro yang terlalu panjang.
2. **No Unnecessary Explanations:** Jangan jelaskan cara kerja fungsi standar Laravel kecuali diminta secara eksplisit.
3. **Diff/Incremental Code Only:** Untuk perbaikan/refactoring, cukup tunjukkan bagian kode yang diubah atau ditambah, bukan seluruh isi file jika file tersebut panjang.
4. **Follow Project Structure:** Pertahankan konvensi penamaan dan struktur folder yang sudah ada di proyek ini.
5. **Selalu update tasks:** update setiap file task.md (centang) yang sudah dikerjakan
