# PRD — Pembagian Kuota Ekspor

| | |
|---|---|
| Status | Diimplementasikan (10 November 2026) — lihat §17 |
| Tanggal | 10 November 2026 |
| Modul induk | Assessment (`assesments`, `assesment_isi`) |
| Sumber metode | Excel "Formula 2026 kulit reptil" (Biawak Air, Kulit, Reptil, Jatim 2026, Q = 17.000) |

---

## 1. Latar Belakang

Kuota ekspor TSL ditetapkan per provinsi, per jenis, per tahun. Selama ini pembagian ke perusahaan dihitung di Excel ("Formula 2026 kulit reptil") dengan menggabungkan dua faktor: rekam jejak ekspor tahun sebelumnya dan nilai assessment perusahaan. Proses ini manual, sulit diaudit, dan rentan salah salin.

Modul Assessment sudah menghasilkan nilai berbobot per entitas per komoditas (`assesment_isi.skor_total`), dan realisasi ekspor sudah tercatat di `sats_ln_items.realisasi_on_port`. Fitur ini menyatukan keduanya untuk menghasilkan **rekomendasi** pembagian. **Penetapan kuota tetap diinput manual** oleh petugas dan disahkan pejabat penetap.

## 2. Tujuan & Non-Tujuan

### Tujuan
1. Menghitung rekomendasi pembagian kuota ekspor secara otomatis dan dapat direproduksi (hasilnya sama dengan Excel untuk input yang sama).
2. Menyimpan setiap perhitungan sebagai snapshot yang tidak bisa diubah (input, pengaturan, hasil).
3. Menyediakan alur penetapan manual dengan validasi (Σ ditetapkan ≤ Q, wajib alasan jika menyimpang dari rekomendasi).
4. Memisahkan peran pengusul dan penyetuju (four-eyes principle).
5. Mencatat realisasi ekspor dan SK Kuota Ekspor per perusahaan, serta audit log semua perubahan.

### Non-Tujuan (v1)
- Rekomendasi **tidak** menjadi kuota secara otomatis.
- Tidak membuat/mengubah baris `kuota_perusahaan` (kuota ekspor) secara otomatis dari hasil penetapan.
- Tidak memvalidasi Σ kuota ekspor semua provinsi terhadap `kuota_nasional`.
- Permohonan perusahaan **tidak** membatasi rekomendasi (hanya ditampilkan sebagai informasi, tanpa peringatan).
- Tidak ada pengaturan bobot/cadangan per komoditas (hanya global).
- Tidak ada seeding role, hanya permission.

## 3. Metode & Rumus

### Notasi

| Simbol | Arti | Sumber |
|---|---|---|
| Q | Kuota ekspor provinsi per komoditas/jenis TSL/tahun | Input manual di periode |
| c | Cadangan (default 10%) | `kuota_settings`, di-snapshot |
| Q_b | Kuota yang dibagi = `floor(Q × (1 − c))` | Dihitung |
| E_i | Realisasi ekspor perusahaan *i*, jenis TSL sama, **tahun − 1** | `sats_ln_items.realisasi_on_port`, di-snapshot |
| N_i | "Total Nilai" assessment (sudah berbobot), dipakai apa adanya | `assesment_isi.skor_total`, di-snapshot |
| w_E, w_N | Bobot ekspor (0,30) dan evaluasi (0,70) | `kuota_settings`, di-snapshot |

### Rumus

```
Q_b            = floor(Q × (1 − c))
KomponenEkspor_i   = w_E × (E_i / ΣE) × Q_b
KomponenEvaluasi_i = w_N × (N_i / ΣN) × Q_b
Rekomendasi_i      = KomponenEkspor_i + KomponenEvaluasi_i
```

- Rumus memakai **porsi** (E_i/ΣE, N_i/ΣN), jadi skala N_i (0–1 atau 0–100) tidak berpengaruh.
- Rekomendasi disimpan sebagai `decimal(15,4)`, ditampilkan sebagai bilangan bulat hasil **largest remainder** sehingga Σ rekomendasi bulat = Q_b.

### Aturan kasus khusus

| Kondisi | Perlakuan |
|---|---|
| Perusahaan tanpa realisasi ekspor tahun − 1 | E_i = 0 (bukan error) |
| Perusahaan tanpa assessment terverifikasi untuk komoditas tsb. | N_i = 0 + peringatan per baris |
| ΣE = 0 | Seluruh KomponenEkspor = 0 + peringatan periode. Porsi 30% **tidak** dialihkan; menjadi sisa (bertambah ke cadangan aktual) |
| ΣN = 0 | Seluruh KomponenEvaluasi = 0 + peringatan periode. Porsi 70% tidak dialihkan |
| ΣE = 0 dan ΣN = 0 | Semua rekomendasi 0 + peringatan |
| Tidak ada peserta | Hitung ditolak (validasi) |
| Permohonan > rekomendasi atau < rekomendasi | Tidak ada efek, hanya informasi |

### Presisi & pembulatan
- Hitung dengan **BCMath** (scale 10), lalu bulatkan half-up ke 4 desimal saat disimpan. Jangan memakai `float`: contoh CV. Banyuwangi Leather = tepat 2.695,0000; float bisa menghasilkan 2.694,99999… lalu `floor` jadi 2.694.
- **Largest remainder**: target T = pembulatan Σ rekomendasi desimal ke bilangan bulat (= Q_b pada kondisi normal; lebih kecil jika ΣE = 0 atau ΣN = 0).
  1. `bulat_i = floor(rekomendasi_i)`
  2. `sisa = T − Σ bulat_i`
  3. Urutkan baris berdasarkan pecahan `rekomendasi_i − bulat_i` menurun; *tie-break*: rekomendasi desimal lebih besar, lalu `entitas_id` menaik (deterministik).
  4. Tambahkan +1 ke `sisa` baris teratas.

## 4. Alur & Status

```
 draft ──hitung──▶ dihitung ──ajukan──▶ diajukan ──setujui──▶ ditetapkan ──tutup──▶ ditutup
   ▲                 │  ▲                   │                (terkunci)
   └──(edit peserta)─┘  └────kembalikan─────┘
                     └─hitung ulang (snapshot baru)
```

| Status | Yang boleh dilakukan | Permission |
|---|---|---|
| `draft` | Edit header periode (Q, wilayah, jenis), kelola peserta & permohonan, Hitung | `kuota.update`, `kuota.calculate` |
| `dihitung` | Hitung ulang (snapshot baru), isi kolom Ditetapkan + alasan, Salin rekomendasi, Ajukan | `kuota.calculate`, `kuota.allocate`, `kuota.submit` |
| `diajukan` | Read-only bagi pengusul. Penyetuju: Setujui atau Kembalikan (wajib catatan) | `kuota.approve` |
| `ditetapkan` | Terkunci untuk alokasi. Hanya input realisasi ekspor + SK Kuota Ekspor, Export | `kuota.realisasi`, `kuota.export` |
| `ditutup` | Read-only total. Export | `kuota.export` |

Aturan transisi:
- Setiap **Hitung / Hitung ulang** membuat baris baru di `kuota_calculation_snapshots` (versi +1), lalu memperbarui kolom rekomendasi di `kuota_allocations`. Nilai **Ditetapkan yang sudah diisi tidak dihapus**, tapi alasan divalidasi ulang saat Ajukan.
- **Ajukan** mensyaratkan: snapshot ada, semua baris punya nilai Ditetapkan (boleh 0), alasan terisi untuk baris yang menyimpang, Σ ditetapkan ≤ Q.
- **Setujui** ditolak jika `submitted_by` = user yang menyetujui.
- Semua transisi dijalankan dalam `DB::transaction` dengan `lockForUpdate()` pada baris periode dan cek status asal (mencegah double-submit/double-approve).
- Hapus periode hanya boleh di status `draft`.

## 5. Kebutuhan Fungsional

| ID | Kebutuhan |
|---|---|
| F-01 | CRUD periode kuota: provinsi, komoditas, jenis (species), kode perdagangan, tahun, Q, catatan. Unik per (provinsi, species, trade_code, tahun). |
| F-02 | Kelola peserta: tambah otomatis kandidat (entitas `perusahaan`, tidak dibatasi provinsi domisili, yang punya `assesment_isi` terverifikasi untuk komoditas ATAU realisasi ekspor tahun − 1 untuk jenis tsb.), tambah/hapus manual di status `draft`/`dihitung` (perubahan mengembalikan status ke `draft`). |
| F-03 | Input "Permohonan" (jumlah yang diminta perusahaan) per peserta — informasi saja. |
| F-04 | Tombol **Hitung**: ambil E_i otomatis (`RealisasiEksporResolver`), ambil N_i dari assessment, ambil pengaturan global, simpan snapshot, tampilkan rekomendasi desimal & bulat + peringatan. |
| F-05 | Kolom **Ditetapkan** kosong secara default. Tombol opsional **Salin rekomendasi** mengisi Ditetapkan = rekomendasi bulat untuk baris yang masih kosong. |
| F-06 | Wajib isi alasan jika Ditetapkan ≠ rekomendasi bulat. |
| F-07 | Tolak simpan jika Σ Ditetapkan > Q. Tampilkan **cadangan aktual = Q − Σ Ditetapkan**. Peringatan non-blocking jika cadangan aktual < c × Q. |
| F-08 | Ajukan → Setujui / Kembalikan, sesuai §4. |
| F-09 | Setelah `ditetapkan`: input realisasi ekspor (angka) dan SK Kuota Ekspor (nomor, tanggal, file PDF) per perusahaan. |
| F-10 | Audit log untuk setiap perubahan: header periode, peserta, Ditetapkan, alasan, realisasi, SK, transisi status, perhitungan. Menyimpan nilai lama & baru. |
| F-11 | Riwayat snapshot: daftar versi, lihat detail tiap versi (read-only). |
| F-12 | Pengaturan global (`kuota.settings`): bobot ekspor, bobot evaluasi (jumlah harus 1,00), cadangan (0–0,50). Perubahan tidak memengaruhi snapshot yang sudah ada. |
| F-13 | Export (CSV/PDF) tabel alokasi periode, termasuk snapshot yang dipakai. |
| F-14 | Perusahaan (`kuota.view-own`) melihat alokasi miliknya sendiri, hanya untuk periode `ditetapkan`/`ditutup`. |
| F-15 | Semua teks UI via `__()` (default Bahasa Indonesia, terjemahan di `lang/en.json`). |

## 6. Role & Permission

### 6.1 Permission (di-seed)

| Permission | Fungsi |
|---|---|
| `kuota.view` | Lihat daftar & detail periode |
| `kuota.create` | Buat periode |
| `kuota.update` | Ubah header periode, peserta, permohonan (status `draft`) |
| `kuota.delete` | Hapus periode (status `draft`) |
| `kuota.calculate` | Hitung / hitung ulang rekomendasi |
| `kuota.allocate` | Isi Ditetapkan + alasan, Salin rekomendasi |
| `kuota.submit` | Ajukan periode |
| `kuota.approve` | Setujui / kembalikan periode |
| `kuota.realisasi` | Input realisasi ekspor + SK Kuota Ekspor |
| `kuota.export` | Export CSV/PDF |
| `kuota.view-own` | Perusahaan melihat alokasi miliknya |
| `kuota.settings` | Ubah pengaturan global |

### 6.2 Seeder

Seeder **hanya mendaftarkan permission**, tidak membuat role dan tidak meng-assign ke role mana pun (termasuk `super-admin`). Role dibuat dan diberi permission secara manual oleh admin lewat menu Roles/Permissions yang sudah ada.

```php
// database/seeders/KuotaTangkapPermissionSeeder.php
declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class KuotaTangkapPermissionSeeder extends Seeder
{
    private const PERMISSIONS = [
        'kuota.view', 'kuota.create', 'kuota.update', 'kuota.delete',
        'kuota.calculate', 'kuota.allocate', 'kuota.submit', 'kuota.approve',
        'kuota.realisasi', 'kuota.export', 'kuota.view-own', 'kuota.settings',
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
```

Didaftarkan di `DatabaseSeeder`, idempoten (aman dijalankan ulang di production).

### 6.3 Panduan pengelompokan (tidak di-seed)

| Role (saran nama) | Permission yang disarankan |
|---|---|
| Admin Balai | `kuota.view`, `create`, `update`, `delete`, `calculate`, `export`, `settings` |
| Bidang Wilayah | `kuota.view`, `calculate`, `allocate`, `submit`, `export` |
| Seksi Wilayah | `kuota.view`, `allocate`, `realisasi`, `export` |
| Pejabat Penetap | `kuota.view`, `approve`, `export` |
| Perusahaan | `kuota.view-own` |

Catatan: satu user yang memegang `submit` dan `approve` sekaligus tetap tidak bisa menyetujui periode yang ia ajukan sendiri (dicek Policy, bukan role).

### 6.4 Policy — `KuotaPeriodPolicy`

| Ability | Aturan |
|---|---|
| `view` | `kuota.view` dan periode dalam cakupan wilayah user (lihat §15 Q-3) |
| `update` / `delete` | permission terkait **dan** status `draft` |
| `calculate` | `kuota.calculate` dan status ∈ {`draft`, `dihitung`} |
| `allocate` | `kuota.allocate` dan status = `dihitung` |
| `submit` | `kuota.submit` dan status = `dihitung` |
| `approve` / `return` | `kuota.approve`, status = `diajukan`, **dan `$period->submitted_by !== $user->id`** |
| `realisasi` | `kuota.realisasi` dan status = `ditetapkan` |
| `close` | `kuota.approve` dan status = `ditetapkan` |
| `viewOwn` | `kuota.view-own`, status ∈ {`ditetapkan`, `ditutup`}, dan user punya alokasi di periode (via `users.entity_id`) |

Middleware `permission:` di route = gerbang kasar; Policy = aturan status & kepemilikan. Keduanya wajib.

## 7. Desain Data

Konvensi mengikuti migration yang ada: `bigIncrements` (bukan UUID — lihat §16), FK eksplisit + index, `decimal(15,4)` untuk kuantitas kuota (sama seperti `kuota_nasional.quota`), `smallInteger('year')`, `down()` selalu ada.

### 7.1 `kuota_settings` (satu baris, global)

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigIncrements | |
| bobot_ekspor | decimal(5,4) | default 0.3000 |
| bobot_evaluasi | decimal(5,4) | default 0.7000 |
| cadangan | decimal(5,4) | default 0.1000 |
| updated_by | FK users, nullable, nullOnDelete | |
| timestamps | | |

Validasi: `bobot_ekspor + bobot_evaluasi = 1`, `0 ≤ cadangan ≤ 0.5`. Migration meng-insert baris default. Pola akses mirip `WhatsAppSetting` (static accessor).

### 7.2 `kuota_periods`

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigIncrements | |
| provinsi_id | FK provinsi, restrict | index |
| komoditas_id | FK komoditas, restrict | dipakai untuk mencocokkan `assesment_isi.komoditas_id` |
| species_id | FK species, restrict | dipakai untuk mencocokkan `sats_ln_items.species_id` |
| trade_code_id | FK trade_codes, restrict | mis. "Kulit"; filter E_i via `kuota_nasional.trade_code_id` |
| year | smallInteger | index |
| kuota_tangkap | decimal(15,4) | Q |
| status | string(20) default `draft` | Enum `KuotaPeriodStatus`, index |
| latest_snapshot_id | FK kuota_calculation_snapshots, nullable, nullOnDelete | FK ditambahkan setelah tabel snapshot dibuat |
| catatan | text nullable | |
| catatan_pengembalian | text nullable | diisi saat Kembalikan |
| created_by, submitted_by, approved_by, closed_by | FK users, nullable, nullOnDelete | |
| submitted_at, approved_at, closed_at | timestamp nullable | |
| timestamps, softDeletes | | |

Unique: (`provinsi_id`, `species_id`, `trade_code_id`, `year`) — soft-delete tidak boleh menghalangi pembuatan ulang, jadi gunakan partial unique index `WHERE deleted_at IS NULL` (PostgreSQL) atau tanpa softDeletes (lihat §15 Q-5).

### 7.3 `kuota_allocations`

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigIncrements | |
| kuota_period_id | FK kuota_periods, cascadeOnDelete | index |
| entity_id | FK entities, restrict | unique (`kuota_period_id`, `entity_id`) |
| permohonan | decimal(15,4) nullable | informasi saja |
| realisasi_ekspor | decimal(15,4) nullable | E_i dari snapshot terakhir |
| nilai_evaluasi | decimal(8,2) nullable | N_i dari snapshot terakhir (presisi = `skor_total`) |
| assesment_isi_id | FK assesment_isi, nullable, nullOnDelete | sumber N_i |
| komponen_ekspor | decimal(15,4) nullable | |
| komponen_evaluasi | decimal(15,4) nullable | |
| rekomendasi | decimal(15,4) nullable | |
| rekomendasi_bulat | integer nullable | hasil largest remainder |
| ditetapkan | decimal(15,4) nullable | **input manual**, kosong default |
| alasan_penyesuaian | text nullable | wajib jika `ditetapkan ≠ rekomendasi_bulat` |
| realisasi_tangkap | decimal(15,4) nullable | diisi setelah `ditetapkan` |
| sk_tangkap_nomor | string nullable | |
| sk_tangkap_tanggal | date nullable | |
| sk_tangkap_path | string nullable | file di S3 (disk yang sama dengan dokumen lain) |
| timestamps | | |

### 7.4 `kuota_calculation_snapshots` (immutable, insert-only)

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigIncrements | |
| kuota_period_id | FK kuota_periods, cascadeOnDelete | |
| version | unsignedInteger | unique (`kuota_period_id`, `version`) |
| kuota_tangkap | decimal(15,4) | Q saat dihitung |
| cadangan | decimal(5,4) | c |
| bobot_ekspor, bobot_evaluasi | decimal(5,4) | |
| kuota_dibagi | integer | Q_b |
| total_realisasi_ekspor | decimal(15,4) | ΣE |
| total_nilai_evaluasi | decimal(10,2) | ΣN |
| tahun_realisasi | smallInteger | tahun − 1 |
| rows | jsonb | per entitas: entity_id, nama, permohonan, E_i, N_i, assesment_isi_id, komponen, rekomendasi, rekomendasi_bulat |
| warnings | jsonb | daftar kode + pesan peringatan |
| calculated_by | FK users, nullable, nullOnDelete | |
| created_at | timestamp | tanpa `updated_at` |

### 7.5 `kuota_activities` (audit log)

Tidak tercantum di daftar awal, tapi dibutuhkan untuk F-10. Polanya sama dengan `activity_satsln`.

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | id() | |
| kuota_period_id | FK, cascadeOnDelete | |
| kuota_allocation_id | FK, nullable, nullOnDelete | |
| user_id | FK users, nullable, nullOnDelete | |
| action | string(40) | `created`, `updated`, `participant_added`, `calculated`, `allocated`, `submitted`, `approved`, `returned`, `closed`, `realisasi_updated`, `settings_updated`, ... |
| old_values, new_values | jsonb nullable | |
| keterangan | text nullable | |
| created_at | timestamp | index (`kuota_period_id`, `created_at`) |

Perubahan `kuota_settings` dicatat dengan `kuota_period_id` null → kolom tersebut **nullable** (atau tabel log terpisah; dipilih nullable demi satu tempat audit).

## 8. Desain Teknis

### 8.1 Struktur file (mengikuti konvensi folder per modul)

```
app/Enums/KuotaPeriodStatus.php                       # label(), badgeClass() seperti AssesmentIsiStatus
app/Models/KuotaTangkap/KuotaPeriod.php
app/Models/KuotaTangkap/KuotaAllocation.php
app/Models/KuotaTangkap/KuotaCalculationSnapshot.php
app/Models/KuotaTangkap/KuotaSetting.php
app/Models/KuotaTangkap/KuotaActivity.php
app/Services/KuotaTangkap/KuotaRecommendationService.php
app/Services/KuotaTangkap/LargestRemainderRounder.php
app/Services/KuotaTangkap/RealisasiEksporResolver.php
app/Services/KuotaTangkap/KuotaPeriodWorkflowService.php   # transisi status + audit
app/Policies/KuotaPeriodPolicy.php                    # folder Policies baru
app/Http/Controllers/KuotaTangkap/KuotaPeriodController.php
app/Http/Controllers/KuotaTangkap/KuotaAllocationController.php
app/Http/Controllers/KuotaTangkap/KuotaWorkflowController.php
app/Http/Controllers/KuotaTangkap/KuotaRealisasiController.php
app/Http/Controllers/KuotaTangkap/KuotaSettingController.php
app/Http/Controllers/KuotaTangkap/KuotaSayaTangkapController.php
app/Http/Requests/KuotaTangkap/KuotaPeriodRequest.php
app/Http/Requests/KuotaTangkap/KuotaAllocationRequest.php
app/Http/Requests/KuotaTangkap/KuotaRealisasiRequest.php
app/Http/Requests/KuotaTangkap/KuotaReturnRequest.php
app/Http/Requests/KuotaTangkap/KuotaSettingRequest.php
database/migrations/xxxx_create_kuota_tangkap_tables.php
database/seeders/KuotaTangkapPermissionSeeder.php
routes/kuota-tangkap.php                              # di-require dari routes/web.php
resources/views/kuota-tangkap/{index,show,settings,saya}.blade.php
tests/Unit/LargestRemainderRounderTest.php
tests/Feature/KuotaRecommendationServiceTest.php
tests/Feature/KuotaTangkapTest.php
```

### 8.2 `RealisasiEksporResolver`

```php
/** @return array<int, string> entity_id => total realisasi (string desimal) */
public function forPeriod(KuotaPeriod $period, Collection $entityIds): array
```

Query (satu query, grouped):

```sql
SELECT s.entity_id, COALESCE(SUM(i.realisasi_on_port), 0) AS total
FROM sats_ln_items i
JOIN sats_ln s ON s.id = i.sats_ln_id AND s.deleted_at IS NULL
LEFT JOIN kuota_nasional kn ON kn.id = i.kuota_nasional_id
WHERE s.type_satsln = 'Export'
  AND s.year = :tahun_minus_1
  AND s.status_satsln = 'disetujui'
  AND i.species_id = :species_id
  AND kn.trade_code_id = :trade_code_id
  AND s.entity_id IN (:entity_ids)
GROUP BY s.entity_id
```

Entitas yang tidak muncul → E_i = 0. Hasilnya disimpan ke snapshot, jadi perubahan data SATS-LN setelahnya tidak mengubah perhitungan lama.

### 8.3 `LargestRemainderRounder`

```php
/**
 * @param  array<int|string, string>  $values  key => desimal (string, 4 digit)
 * @return array<int|string, int>             key => bulat, Σ = $target
 */
public function round(array $values, int $target, array $tieBreakKeys = []): array
```

Murni (tanpa DB), BCMath, deterministik. Melempar `InvalidArgumentException` jika `target < Σ floor` atau `target − Σ floor > count(values)`.

### 8.4 `KuotaRecommendationService`

```php
public function calculate(KuotaPeriod $period, User $user): KuotaCalculationSnapshot
```

Dalam `DB::transaction`:
1. `lockForUpdate` periode, pastikan status ∈ {`draft`, `dihitung`}, peserta ≥ 1.
2. Ambil `KuotaSetting` aktif.
3. E_i dari `RealisasiEksporResolver`; N_i = `skor_total` dari `assesment_isi` terakhir berstatus `verified` (urut `verified_at` desc) untuk (`entitas_id`, `komoditas_id` periode).
4. Hitung Q_b, komponen, rekomendasi (BCMath), lalu `LargestRemainderRounder`.
5. Insert snapshot (version = max + 1), update kolom rekomendasi di `kuota_allocations`, set `latest_snapshot_id`, status → `dihitung`.
6. Catat `kuota_activities` (`calculated`, versi, ringkasan).

Fungsi hitung murni dipisah (`compute(array $rows, Settings, string $q): Result`) agar bisa diuji tanpa DB, termasuk uji kompatibilitas Excel.

### 8.5 Routes + middleware (`routes/kuota-tangkap.php`)

```php
Route::middleware('auth')->get('kuota-tangkap-saya', [KuotaSayaTangkapController::class, 'index'])
    ->middleware('permission:kuota.view-own')->name('kuota-tangkap-saya.index');

Route::middleware('auth')->prefix('kuota-tangkap')->name('kuota-tangkap.')->group(function (): void {
    Route::get('pengaturan', [KuotaSettingController::class, 'edit'])->middleware('permission:kuota.settings')->name('settings.edit');
    Route::put('pengaturan', [KuotaSettingController::class, 'update'])->middleware('permission:kuota.settings')->name('settings.update');

    Route::get('', [KuotaPeriodController::class, 'index'])->middleware('permission:kuota.view')->name('index');
    Route::post('', [KuotaPeriodController::class, 'store'])->middleware('permission:kuota.create')->name('store');
    Route::get('{period}', [KuotaPeriodController::class, 'show'])->middleware('permission:kuota.view')->name('show');
    Route::put('{period}', [KuotaPeriodController::class, 'update'])->middleware('permission:kuota.update')->name('update');
    Route::delete('{period}', [KuotaPeriodController::class, 'destroy'])->middleware('permission:kuota.delete')->name('destroy');

    Route::post('{period}/peserta', [KuotaAllocationController::class, 'store'])->middleware('permission:kuota.update')->name('peserta.store');
    Route::delete('{period}/peserta/{allocation}', [KuotaAllocationController::class, 'destroy'])->middleware('permission:kuota.update')->name('peserta.destroy');

    Route::post('{period}/hitung', [KuotaWorkflowController::class, 'calculate'])->middleware('permission:kuota.calculate')->name('calculate');
    Route::put('{period}/alokasi', [KuotaAllocationController::class, 'update'])->middleware('permission:kuota.allocate')->name('alokasi.update');
    Route::post('{period}/ajukan', [KuotaWorkflowController::class, 'submit'])->middleware('permission:kuota.submit')->name('submit');
    Route::post('{period}/setujui', [KuotaWorkflowController::class, 'approve'])->middleware('permission:kuota.approve')->name('approve');
    Route::post('{period}/kembalikan', [KuotaWorkflowController::class, 'return'])->middleware('permission:kuota.approve')->name('return');
    Route::post('{period}/tutup', [KuotaWorkflowController::class, 'close'])->middleware('permission:kuota.approve')->name('close');

    Route::put('{period}/realisasi/{allocation}', [KuotaRealisasiController::class, 'update'])->middleware('permission:kuota.realisasi')->name('realisasi.update');
    Route::get('{period}/snapshot/{snapshot}', [KuotaPeriodController::class, 'snapshot'])->middleware('permission:kuota.view')->name('snapshot');
    Route::get('{period}/export', [KuotaPeriodController::class, 'export'])->middleware('permission:kuota.export')->name('export');
});
```

Controller memanggil `$this->authorize(...)` (Policy) sebelum memanggil service. Route model binding `{allocation}` di-*scope* ke `{period}` (`->scopeBindings()`).

### 8.6 FormRequest

| Request | Aturan utama |
|---|---|
| `KuotaPeriodRequest` | `provinsi_id`, `komoditas_id`, `species_id`, `trade_code_id` `exists`; `year` integer 2000–2100; `kuota_tangkap` numeric `gt:0`; unique kombinasi (abaikan diri sendiri saat update); species harus milik komoditas |
| `KuotaAllocationRequest` | `allocations.*.id` milik periode; `ditetapkan` nullable numeric `min:0`; `alasan_penyesuaian` `required` jika ditetapkan ≠ `rekomendasi_bulat` (di `after()`); `after()`: Σ ditetapkan ≤ Q → error bila melebihi |
| `KuotaRealisasiRequest` | `realisasi_tangkap` numeric `min:0`; `sk_tangkap_nomor` string max 100; `sk_tangkap_tanggal` date; `sk_tangkap_file` `mimes:pdf` max 5 MB |
| `KuotaReturnRequest` | `catatan_pengembalian` required, max 2000 |
| `KuotaSettingRequest` | bobot 0–1, `bobot_ekspor + bobot_evaluasi = 1` (toleransi 0,0001), `cadangan` 0–0,5 |

Validasi Σ ≤ Q dan alasan **diulang** di `KuotaPeriodWorkflowService::submit()` (data bisa berubah setelah simpan, mis. karena hitung ulang).

## 9. Sketsa UI

Mengikuti stack yang benar-benar dipakai (Tailwind/Preline, tabel biasa, dialog konfirmasi kustom), menu baru **"Kuota Ekspor"** di grup sidebar "Database Kuota".

**Daftar periode** (`/kuota-tangkap`, mengikuti "Tahun Aktif" topbar)

```
┌ Kuota Ekspor ─ Tahun 2026 ───────────────────────────── [+ Periode] ┐
│ Provinsi     Jenis TSL             Kode   Q        Status      Aksi  │
│ Jawa Timur   Varanus salvator      Kulit  17.000   [Dihitung]  Lihat │
│ Jawa Barat   Python reticulatus    Kulit   8.000   [Draft]     Lihat │
└──────────────────────────────────────────────────────────────────────┘
```

**Detail periode** (`/kuota-tangkap/{id}`)

```
Jawa Timur · Biawak Air (Varanus salvator) · Kulit · 2026        [Dihitung]
Q 17.000 │ Cadangan 10% │ Q_b 15.300 │ ΣE (2025) 61.213 │ ΣN 3,06 │ Snapshot v3
⚠ PT X belum punya assessment terverifikasi → N_i = 0

[Hitung ulang] [Salin rekomendasi] [Simpan] [Ajukan]       Riwayat snapshot ▾

Perusahaan               Permohonan   E_i     N_i   Rekom.  Ditetapkan  Alasan
PT Ahmad Jaya Sentosa         5.000     120  0,63   2.214   [______]    [______]
PT. Berkat Alam Abadi        16.150  31.373  0,54   4.242   [______]    [______]
...
Total                                               15.300   12.000
Cadangan aktual = Q − Σ ditetapkan = 5.000 (29,4%)
```

- Rekomendasi desimal & komponen tampil di tooltip/baris detail.
- Field Alasan menjadi wajib (border merah + pesan) saat nilai ≠ rekomendasi.
- Σ > Q: tombol Simpan tetap bisa diklik, server menolak dengan pesan; klien menampilkan indikator merah secara langsung.
- Cadangan aktual < 10%: banner kuning, tidak memblokir.
- Status `diajukan`/`ditetapkan`: semua input read-only; penyetuju melihat [Setujui] [Kembalikan].
- Tab **Realisasi** (status `ditetapkan`): Ditetapkan, Realisasi ekspor, Nomor/Tanggal SK, unggah PDF.
- Tab **Riwayat**: timeline `kuota_activities`.

## 10. Kriteria Penerimaan

1. Dengan fixture §11, sistem menghasilkan rekomendasi bulat persis 2.214 / 4.242 / 849 / 5.300 / 2.695, Σ = 15.300, cadangan 1.700.
2. Dengan c = 0, rekomendasi desimal = 2.459,9979 / 4.713,8614 / 943,3312 / 5.888,3650 / 2.994,4444, Σ = 17.000.
3. E_i diambil otomatis dari realisasi ekspor tahun − 1 dan tersimpan di snapshot; mengubah data SATS-LN setelah hitung tidak mengubah snapshot lama.
4. Setiap Hitung membuat snapshot baru dengan versi naik; snapshot lama tetap bisa dibuka.
5. Kolom Ditetapkan kosong setelah hitung pertama; Salin rekomendasi hanya mengisi yang kosong.
6. Simpan dengan Ditetapkan ≠ rekomendasi tanpa alasan → ditolak.
7. Simpan dengan Σ Ditetapkan > Q → ditolak, data tidak berubah.
8. Cadangan aktual < 10% → peringatan, tetap bisa disimpan.
9. User yang mengajukan tidak bisa menyetujui periode yang sama (403), walaupun memegang `kuota.approve`.
10. Periode `ditetapkan` menolak perubahan alokasi/hitung ulang; hanya realisasi + SK yang bisa diubah.
11. Perubahan pengaturan global tidak mengubah snapshot yang sudah ada.
12. Setiap aksi tercatat di `kuota_activities` dengan nilai lama/baru dan pelaku.
13. Perusahaan hanya melihat alokasinya sendiri, dan hanya setelah ditetapkan.
14. Seeder hanya membuat 12 permission `kuota.*`; tidak ada role yang dibuat/diubah.

## 11. Rencana Pengujian

### Test fixture (wajib)

Q = 17.000, c = 10%, Q_b = 15.300, ΣE = 61.213, ΣN = 3,06

| Perusahaan | Permohonan | E_i | N_i | Komp. Ekspor | Komp. Evaluasi | Rekomendasi desimal | Rekomendasi bulat |
|---|---:|---:|---:|---:|---:|---:|---:|
| PT Ahmad Jaya Sentosa | 5.000 | 120 | 0,63 | 8,9981 | 2.205,0000 | 2.213,9981 | 2.214 |
| PT. Berkat Alam Abadi | 16.150 | 31.373 | 0,54 | 2.352,4753 | 1.890,0000 | 4.242,4753 | 4.242 |
| PT Dua Saudara Sakti Berkah | 3.000 | 120 | 0,24 | 8,9981 | 840,0000 | 848,9981 | 849 |
| PT. Fendibali Leather Indonesia | 37.000 | 29.600 | 0,88 | 2.219,5285 | 3.080,0000 | 5.299,5285 | 5.300 |
| CV. Banyuwangi Leather | 2.000 | 0 | 0,77 | 0,0000 | 2.695,0000 | 2.695,0000 | 2.695 |
| **Total** | | **61.213** | **3,06** | | | **15.300,0000** | **15.300** |

Cadangan = 1.700. Jejak largest remainder: Σ floor = 15.297, sisa 3 → +1 ke pecahan 0,9981 (Ahmad Jaya), 0,9981 (Dua Saudara), 0,5285 (Fendibali). Tie 0,9981 diurutkan berdasarkan rekomendasi desimal (Ahmad Jaya lebih besar) — keduanya tetap mendapat +1.

Uji kompatibilitas Excel (c = 0, Q_b = 17.000): rekomendasi desimal = 2.459,9979 · 4.713,8614 · 943,3312 · 5.888,3650 · 2.994,4444 (Σ 17.000). Nilai fixture ini sudah diverifikasi ulang dengan perhitungan independen saat menyusun dokumen.

### Daftar test

| Jenis | File | Kasus |
|---|---|---|
| Unit | `LargestRemainderRounderTest` | Σ = target; tie-break deterministik; nilai bulat tepat (2.695,0000 tidak turun ke 2.694); target tidak valid → exception |
| Feature (DB) | `KuotaRecommendationServiceTest` | Fixture c = 10% (bulat + desimal + Q_b + ΣE + ΣN); fixture c = 0 (kompatibilitas Excel); ΣE = 0 → komponen ekspor 0 + warning, target < Q_b; ΣN = 0; entitas tanpa realisasi → E_i = 0; tanpa assessment → N_i = 0 + warning; resolver hanya menghitung Export, tahun − 1, species & trade code sama, SATS-LN `disetujui` dan tidak terhapus; hitung ulang → versi +1, snapshot lama utuh; perubahan setting tidak mengubah snapshot lama |
| Feature (HTTP) | `KuotaTangkapTest` | Permission middleware per route (403 tanpa permission); Ditetapkan ≠ rekomendasi tanpa alasan → 422; Σ > Q → 422, data tak berubah; cadangan < 10% → sukses + flash warning; submit → approve oleh user lain sukses; **approve oleh pengaju → 403**; periode `ditetapkan` menolak alokasi & hitung (403); realisasi + upload SK (Storage::fake); audit log tercatat; perusahaan `view-own` hanya melihat entitasnya, hanya setelah ditetapkan |
| Seeder | `KuotaTangkapTest` | Seeder membuat 12 permission, idempoten, jumlah role tidak berubah |

Jalankan: `php artisan test --compact --filter=Kuota`.

## 12. Keputusan

| # | Keputusan | Alasan |
|---|---|---|
| D-1 | Hasil hitung hanya rekomendasi; Ditetapkan input manual | Kewenangan penetapan ada pada pejabat |
| D-2 | Cadangan disisihkan di awal: Q_b = floor(Q × (1 − c)) | Sesuai metode; Q_b bulat sehingga largest remainder punya target bulat |
| D-3 | N_i = `skor_total` apa adanya | Rumus memakai porsi; skala tidak relevan |
| D-4 | E_i = realisasi ekspor tahun − 1 dari `sats_ln_items.realisasi_on_port`, snapshot | Reproducible & bisa diaudit |
| D-5 | ΣE = 0 / ΣN = 0 → komponen 0, porsi tidak dialihkan, peringatan | Sesuai spesifikasi; sisa otomatis menjadi cadangan aktual |
| D-6 | Permohonan tidak membatasi rekomendasi, tanpa peringatan | Sesuai spesifikasi |
| D-7 | Bobot & cadangan global di `kuota_settings`, di-snapshot | Satu kebijakan nasional; perubahan tidak merusak riwayat |
| D-8 | BCMath, simpan 4 desimal | Hindari galat float pada floor |
| D-9 | Batas penetapan Σ ≤ Q (bukan Q_b) | Cadangan boleh dipakai sebagian oleh pejabat, dengan peringatan |
| D-10 | Hitung ulang tidak menghapus Ditetapkan | Mencegah kehilangan input manual; alasan divalidasi ulang saat ajukan |
| D-11 | Ada transisi "Kembalikan" (diajukan → dihitung) | Tanpa ini periode yang salah tidak bisa diperbaiki |
| D-12 | Seeder hanya permission | Role dikelola admin |
| D-13 | Pemisahan pengaju/penyetuju dicek di Policy via `submitted_by` | Berlaku apa pun role-nya |

## 13. Tahapan Implementasi

| Tahap | Isi | Selesai bila |
|---|---|---|
| 1. Fondasi | Migration 5 tabel, model + relasi, enum status, seeder permission, `KuotaSetting` default | `php artisan migrate` & rollback bersih; seeder idempoten |
| 2. Mesin hitung | `LargestRemainderRounder`, `RealisasiEksporResolver`, `KuotaRecommendationService` + snapshot | Test fixture c = 10% & c = 0 lulus |
| 3. Periode & peserta | CRUD periode, kandidat peserta otomatis + manual, permohonan, Policy | HTTP test CRUD + permission lulus |
| 4. Penetapan & workflow | Ditetapkan, alasan, Salin rekomendasi, Σ ≤ Q, ajukan/setujui/kembalikan/tutup, audit log | Kriteria 5–10, 12 lulus |
| 5. Realisasi & SK | Input realisasi ekspor, unggah SK ke S3 | Test realisasi + upload lulus |
| 6. Akses perusahaan, export, pengaturan | `kuota-tangkap-saya`, Export, halaman pengaturan, menu sidebar, terjemahan `lang/en.json` | Kriteria 11, 13 lulus; `npm run build` |

## 14. Risiko

| Risiko | Mitigasi |
|---|---|
| `sats_ln_items.realisasi_on_port` saat ini **belum diinput dari UI mana pun** (default 0) → semua E_i = 0, ΣE = 0 | Peringatan ΣE = 0 tampil jelas; putuskan Q-1 sebelum tahap 2 |
| Assessment tidak punya kolom tahun → N_i bisa berasal dari assessment lama | Ambil `verified` terbaru; tampilkan tanggal verifikasi di tabel; lihat Q-2 |
| Data produksi dengan banyak periode/entitas | Resolver satu query grouped; tidak ada query di Blade |

## 15. Pertanyaan Terbuka

| # | Pertanyaan | Default sementara di dokumen ini |
|---|---|---|
| Q-1 | Sumber E_i: `realisasi_on_port` (realisasi di pelabuhan) atau `qty` SATS-LN disetujui bila realisasi belum diinput? | `realisasi_on_port`, SATS-LN `status_satsln = disetujui` |
| Q-2 | Perlukah assessment dibatasi periode/tahun tertentu? | Assessment `verified` terbaru per entitas + komoditas |
| Q-3 | Cakupan wilayah user balai: per `entities.provinsi_id` milik entitas UPT user, atau lintas provinsi? | User dengan entitas `upt_ksda` hanya melihat periode di provinsi entitasnya; user internal (tanpa entitas) melihat semua |
| Q-4 | Apakah penetapan nantinya otomatis membuat `kuota_perusahaan`? | Tidak (non-tujuan v1) |
| Q-5 | `kuota_periods` perlu soft delete? | Ya, dengan partial unique index `WHERE deleted_at IS NULL` |
| Q-6 | Apakah Σ kuota ekspor semua perusahaan lintas periode perlu dicek terhadap `kuota_nasional`? | Tidak di v1 |

## 16. Asumsi yang Diambil dari Kode Project

1. **Tabel realisasi** = `sats_ln_items`, kolom `realisasi_on_port` (`decimal(15,2)`), berelasi ke `sats_ln` (`entity_id`, `type_satsln` enum `Export|Import|Re-Export`, `year`, `status_satsln`, soft delete) dan `species_id`. Tidak ada tabel bernama "realisasi" tersendiri. Kolom ini belum diisi dari UI mana pun saat dokumen ditulis (hanya cast di `App\Models\SatsLnItem`). `PermohonanService::realisasiKuotaNasional()` menyebut "realisasi" tetapi menjumlahkan `qty` (pemakaian kuota), bukan realisasi ekspor.
2. **Tahun realisasi** = `sats_ln.year` (tahun kuota nasional yang dipakai SATS-LN), bukan `publish_date`.
3. **Model assessment**: `App\Models\Assesment\AssesmentIsi` (tabel `assesment_isi`, ejaan "assesment" satu "s" konsisten di seluruh kode), per `entitas_id` + `komoditas_id`, status enum `App\Enums\AssesmentIsiStatus` (`draft|submitted|verified`). "Total Nilai" = `skor_total` (`decimal(8,2)`, rata-rata berbobot dari `AssesmentSkorService`, berdasarkan jawaban final setelah koreksi verifikator), bukan `skor_entitas`.
4. **Assessment per komoditas, kuota per jenis**: N_i dicocokkan lewat `komoditas_id` periode, E_i lewat `species_id` (+ `trade_code_id` via `kuota_nasional`).
5. **Package permission**: `spatie/laravel-permission` 8.3.0, guard `web`, middleware route `permission:x.y` per route (pola `routes/assesment.php`, `routes/kuota-perusahaan.php`). Belum ada `Gate::before` untuk super-admin, dan belum ada folder `app/Policies` — `KuotaPeriodPolicy` adalah policy pertama.
6. **Konvensi permission**: `entitas.aksi` dengan titik (`kuota-nasional.view`, `assesment-isi.verify`). Prefix `kuota.*` belum dipakai dan tidak bentrok dengan `kuota-nasional.*`, `kuota-perusahaan.*`, `kuota-saya.view`.
7. **Primary key**: semua tabel memakai `bigIncrements`, bukan UUID (CLAUDE.md menyebut UUID, tetapi kode nyata tidak) — tabel baru mengikuti kode nyata.
8. **Perusahaan** = `entities` dengan `entity_type = 'perusahaan'`; user terhubung lewat `users.entity_id` (null untuk petugas internal). Provinsi entitas di `entities.provinsi_id`. Balai = entitas `upt_ksda`.
9. **Wilayah**: tabel `provinsi` sudah ada; `kuota_nasional` tidak punya kolom provinsi, sehingga Q provinsi diinput di `kuota_periods`.
10. **Frontend**: Tailwind/Preline, tabel biasa, dialog kustom, `__()` dengan default Bahasa Indonesia (bukan Bootstrap/DataTables/SweetAlert2 seperti di CLAUDE.md), sesuai halaman kuota & assessment yang ada. Menu masuk grup "Database Kuota".
11. **Audit log**: belum ada package activity log; dibuat tabel `kuota_activities` dengan pola `activity_satsln`.
12. **Pengaturan global**: belum ada tabel settings generik; `kuota_settings` satu baris, pola akses seperti `App\Models\WhatsAppSetting`.
13. **Penyimpanan file**: SK Kuota Ekspor diunggah ke disk S3 yang sama dengan dokumen/bukti assessment (`league/flysystem-aws-s3-v3`).
14. **BCMath** tersedia di PHP 8.4 environment ini.
15. **Test**: PHPUnit 12 (bukan Pest), Feature test di `tests/Feature/*Test.php`.

## 17. Catatan Implementasi

Penyimpangan dan tambahan terhadap rancangan di atas, diputuskan saat implementasi:

| Topik | Implementasi |
|---|---|
| Kandidat peserta | Tidak difilter provinsi domisili perusahaan (contoh Excel memuat perusahaan Bali untuk kuota Jawa Timur). |
| Edit setelah dihitung | Header periode & peserta boleh diubah di status `dihitung`; perubahan Q/peserta mengembalikan status ke `draft` sehingga wajib hitung ulang. |
| Export | CSV (tanpa dependency baru) dan PDF (dompdf yang sudah terpasang), bukan .xlsx. |
| Struktur kode | Selain `KuotaRecommendationService`, logika dipisah ke `KuotaPeriodService` (header & peserta) dan `KuotaPeriodWorkflowService` (penetapan, transisi status, realisasi). |
| Partial unique index | `kuota_periods_unique_active` dibuat setelah FK `latest_snapshot_id` karena SQLite membangun ulang tabel saat menambah FK dan menghilangkan klausa `WHERE`. |
| Pertanyaan terbuka §15 | Semua memakai default sementara di tabel §15 (Q-1 `realisasi_on_port`, Q-3 cakupan per `entities.provinsi_id` entitas UPT). |
| Panduan pengguna | `docs/Panduan-Pembagian-Kuota-Ekspor.pptx`. |
