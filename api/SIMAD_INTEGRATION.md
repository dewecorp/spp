# Panduan Integrasi API Sibayar SPP → Web SIMAD

**Versi dokumen:** 1.1.0  
**Tanggal:** 10 Juli 2026  
**Penyedia data:** Sibayar SPP (MI Sultan Fattah Sukosono)  
**Konsumen data:** Web SIMAD  

---

## 1. Ringkasan

API ini menyediakan data **tagihan**, **tunggakan** (termasuk **tahun ajaran lama**), dan **riwayat pembayaran** siswa dari aplikasi Sibayar SPP agar dapat disinkronkan ke Web SIMAD.

```
┌─────────────────┐         HTTP GET (JSON)         ┌─────────────────┐
│  Sibayar SPP    │  ───────────────────────────►   │   Web SIMAD     │
│  (sumber data)  │   tagihan, tunggakan, bayar     │  (pembaca data) │
└─────────────────┘                                 └─────────────────┘
```

> **Catatan arah data:** Integrasi ini bersifat **read-only** dari sisi SIMAD. SIMAD **membaca** data keuangan dari Sibayar. Data master siswa dari SIMAD ke Sibayar menggunakan endpoint terpisah (bukan dokumen ini).

---

## 2. Informasi Koneksi

| Item | Nilai |
|------|-------|
| **Base URL** | `https://sibayar.misultanfattah.sch.id/api/simad.php` |
| **Metode** | `GET` |
| **Format respons** | `application/json` |
| **API Key** | `SPP_SECRET_KEY_2026` |
| **Lingkungan** | Production |

### Autentikasi

API Key dapat dikirim dengan **salah satu** cara berikut:

**Opsi A — Header (disarankan):**
```http
GET /api/simad.php?action=check HTTP/1.1
Host: sibayar.misultanfattah.sch.id
X-API-KEY: SPP_SECRET_KEY_2026
Accept: application/json
```

**Opsi B — Query parameter:**
```
https://sibayar.misultanfattah.sch.id/api/simad.php?action=check&api_key=SPP_SECRET_KEY_2026
```

### Respons jika API Key salah

```json
{
  "status": "error",
  "message": "Unauthorized: Invalid API Key",
  "env": "production",
  "base_url": "https://sibayar.misultanfattah.sch.id"
}
```

HTTP Status: `401 Unauthorized`

---

## 3. Parameter Umum

| Parameter | Wajib | Keterangan |
|-----------|-------|------------|
| `action` | Ya | Jenis operasi (lihat daftar endpoint) |
| `api_key` | Ya* | API Key (*bisa diganti header `X-API-KEY`) |
| `tahun_ajaran` | Tidak | Format `YYYY/YYYY` (contoh: `2025/2026`). Jika kosong, otomatis memakai **tahun ajaran aktif** |
| `nisn` | Ya** | Wajib untuk action `get_student_data` |

---

## 4. Daftar Endpoint

### 4.1 `check` — Cek Koneksi

**Request:**
```
GET /api/simad.php?action=check&api_key=SPP_SECRET_KEY_2026
```

**Contoh respons sukses:**
```json
{
  "status": "success",
  "message": "Sibayar SPP API is active",
  "env": "production",
  "base_url": "https://sibayar.misultanfattah.sch.id",
  "tahun_ajaran_aktif": "2025/2026",
  "server_time": "2026-07-10 07:00:00",
  "php_version": "8.2.0",
  "available_actions": [
    "check",
    "get_tahun_ajaran",
    "get_all_summary",
    "get_student_data"
  ]
}
```

**Gunakan untuk:** verifikasi koneksi dan mendapatkan tahun ajaran aktif saat ini.

---

### 4.2 `get_tahun_ajaran` — Daftar Tahun Ajaran

**Request:**
```
GET /api/simad.php?action=get_tahun_ajaran&api_key=SPP_SECRET_KEY_2026
```

**Contoh respons sukses:**
```json
{
  "status": "success",
  "data": ["2025/2026", "2024/2025", "2023/2024"],
  "tahun_ajaran_aktif": "2025/2026",
  "env": "production"
}
```

**Gunakan untuk:** dropdown/filter tahun ajaran di SIMAD.

---

### 4.3 `get_all_summary` — Sinkron Massal (Semua Siswa)

**Request:**
```
GET /api/simad.php?action=get_all_summary&api_key=SPP_SECRET_KEY_2026
```

**Dengan filter tahun ajaran (opsional):**
```
GET /api/simad.php?action=get_all_summary&tahun_ajaran=2025/2026&api_key=SPP_SECRET_KEY_2026
```

**Contoh respons sukses:**
```json
{
  "status": "success",
  "data": [
    {
      "nisn": "3137563185",
      "nama": "Ahmad Fauzi",
      "kelas": "5",
      "tahun_ajaran": "2025/2026",
      "total_tunggakan_aktif": 500000,
      "total_tunggakan_tahun_lama": 200000,
      "total_tunggakan": 700000,
      "total_sudah_bayar": 1500000,
      "tahun_ajaran_tunggakan": ["2024/2025"],
      "is_lunas": false
    }
  ],
  "count": 150,
  "tahun_ajaran": "2025/2026",
  "tahun_ajaran_aktif": "2025/2026",
  "env": "production"
}
```

#### Penjelasan field per siswa

| Field | Tipe | Keterangan |
|-------|------|------------|
| `nisn` | string | Nomor Induk Siswa Nasional |
| `nama` | string | Nama siswa |
| `kelas` | string | Nama kelas saat ini |
| `tahun_ajaran` | string | Tahun ajaran acuan perhitungan |
| `total_tunggakan_aktif` | int | Tunggakan tahun ajaran yang diminta/aktif (Rupiah) |
| `total_tunggakan_tahun_lama` | int | Tunggakan dari tahun ajaran sebelumnya (Rupiah) |
| `total_tunggakan` | int | Total gabungan (`aktif` + `tahun_lama`) |
| `total_sudah_bayar` | int | Total pembayaran tercatat pada tahun ajaran acuan |
| `tahun_ajaran_tunggakan` | array | Daftar tahun ajaran yang masih punya tunggakan |
| `is_lunas` | bool | `true` jika `total_tunggakan` = 0 |

**Gunakan untuk:** sinkronisasi dashboard massal, notifikasi tunggakan, laporan agregat.

---

### 4.4 `get_student_data` — Detail Per Siswa

**Request:**
```
GET /api/simad.php?action=get_student_data&nisn=3137563185&api_key=SPP_SECRET_KEY_2026
```

**Dengan filter tahun ajaran (opsional):**
```
GET /api/simad.php?action=get_student_data&nisn=3137563185&tahun_ajaran=2025/2026&api_key=SPP_SECRET_KEY_2026
```

**Contoh respons sukses:**
```json
{
  "status": "success",
  "student": {
    "nisn": "3137563185",
    "nama": "Ahmad Fauzi",
    "kelas": "5"
  },
  "tahun_ajaran": "2025/2026",
  "tahun_ajaran_aktif": "2025/2026",
  "billing": [
    {
      "id_jenis_bayar": 1,
      "nama_pembayaran": "SPP",
      "tipe_bayar": "Bulanan",
      "tahun_ajaran": "2025/2026",
      "total_nominal": 50000,
      "total_bayar": 150000,
      "sisa_tagihan": 100000,
      "item_belum_bayar": ["Maret", "April"],
      "is_lunas": false
    }
  ],
  "tunggakan_tahun_ajaran_lama": {
    "2024/2025": {
      "tahun_ajaran": "2024/2025",
      "total_tunggakan": 200000,
      "items": [
        {
          "id_jenis_bayar": 1,
          "nama_pembayaran": "SPP",
          "tipe_bayar": "Bulanan",
          "tahun_ajaran": "2024/2025",
          "total_nominal": 50000,
          "sisa_tagihan": 200000,
          "item_belum_bayar": ["Januari", "Februari", "Maret", "April"],
          "is_lunas": false
        }
      ]
    }
  },
  "payments": [
    {
      "no_transaksi": "TRX-20260710-001",
      "tgl_bayar": "2026-07-01",
      "jumlah_bayar": 100000,
      "nama_pembayaran": "SPP",
      "ket": "",
      "waktu_input": "2026-07-01 08:30:00",
      "tahun_bayar": "2026",
      "tahun_ajaran": "2025/2026"
    }
  ],
  "summary": {
    "total_tunggakan_aktif": 100000,
    "total_tunggakan_tahun_lama": 200000,
    "total_tunggakan": 300000,
    "total_sudah_bayar": 1500000,
    "tahun_ajaran_tunggakan": ["2024/2025"],
    "is_lunas": false
  },
  "env": "production"
}
```

#### Penjelasan section respons

| Section | Keterangan |
|---------|------------|
| `student` | Identitas siswa |
| `billing` | Daftar tagihan tahun ajaran acuan (lunas & belum lunas) |
| `tunggakan_tahun_ajaran_lama` | Object keyed by tahun ajaran lama, berisi detail tunggakan per tahun |
| `payments` | Riwayat pembayaran. Jika `tahun_ajaran` di-request diisi → difilter. Jika kosong → semua riwayat |
| `summary` | Ringkasan angka tunggakan |

#### Field `billing[]`

| Field | Tipe | Keterangan |
|-------|------|------------|
| `id_jenis_bayar` | int | ID jenis pembayaran |
| `nama_pembayaran` | string | Nama tagihan (SPP, Uang Gedung, dll.) |
| `tipe_bayar` | string | `Bulanan` atau `Cicilan` |
| `tahun_ajaran` | string | Tahun ajaran tagihan |
| `total_nominal` | int | Nominal per bulan (bulanan) atau total (cicilan) |
| `total_bayar` | int | Sudah dibayar pada tahun ajaran ini |
| `sisa_tagihan` | int | Sisa yang belum dibayar |
| `item_belum_bayar` | array | Bulan belum bayar (bulanan) atau `["Belum Lunas"]` (cicilan) |
| `is_lunas` | bool | Status lunas |

**Gunakan untuk:** halaman detail siswa di SIMAD, cetak rekap tagihan, notifikasi ke wali.

---

## 5. Logika Tunggakan Tahun Ajaran Lama

Sistem Sibayar memisahkan dua jenis tunggakan:

1. **Tunggakan aktif** — tagihan tahun ajaran yang sedang berjalan (atau tahun ajaran yang diminta via parameter).
2. **Tunggakan tahun ajaran lama** — sisa tagihan dari tahun ajaran sebelumnya yang belum lunas.

Perhitungan mengikuti logika yang sama dengan dashboard dan menu Transaksi di Sibayar, sehingga angka di SIMAD akan konsisten dengan Sibayar.

**Rekomendasi tampilan di SIMAD:**

```
Total Tunggakan     : Rp 700.000
  ├─ Tahun 2025/2026 : Rp 500.000
  └─ Tahun 2024/2025 : Rp 200.000  ← dari tunggakan_tahun_ajaran_lama
```

---

## 6. Client Library PHP (Contoh)

File contoh tersedia di: **`api/simad_client_example.php`**

### Inisialisasi

```php
require_once 'simad_client_example.php';

$sibayar = new SibayarClient('SPP_SECRET_KEY_2026');
```

### Contoh penggunaan

```php
// 1. Cek koneksi
$health = $sibayar->checkConnection();

// 2. Ambil daftar tahun ajaran
$tahunList = $sibayar->getTahunAjaran();

// 3. Sinkron massal
$allData = $sibayar->getAllStudentSummary();

// 4. Detail per siswa
$detail = $sibayar->getStudentDetail('3137563185');

// 5. Detail dengan tahun ajaran tertentu
$detailTa = $sibayar->getStudentDetail('3137563185', '2024/2025');
```

### Method yang tersedia

| Method | Action API | Keterangan |
|--------|-----------|------------|
| `checkConnection()` | `check` | Tes koneksi |
| `getTahunAjaran()` | `get_tahun_ajaran` | Daftar tahun ajaran |
| `getAllStudentSummary($tahunAjaran)` | `get_all_summary` | Ringkasan semua siswa |
| `getStudentDetail($nisn, $tahunAjaran)` | `get_student_data` | Detail tagihan per siswa |

---

## 7. Contoh Integrasi (Bahasa lain)

### JavaScript (fetch)

```javascript
const API_URL = 'https://sibayar.misultanfattah.sch.id/api/simad.php';
const API_KEY = 'SPP_SECRET_KEY_2026';

async function getStudentBilling(nisn) {
  const params = new URLSearchParams({
    action: 'get_student_data',
    nisn: nisn,
    api_key: API_KEY,
  });

  const response = await fetch(`${API_URL}?${params}`, {
    headers: { 'X-API-KEY': API_KEY, 'Accept': 'application/json' },
  });

  return response.json();
}
```

### cURL (command line)

```bash
curl -H "X-API-KEY: SPP_SECRET_KEY_2026" \
  "https://sibayar.misultanfattah.sch.id/api/simad.php?action=get_all_summary"
```

---

## 8. Alur Sinkronisasi yang Disarankan

### Sinkronisasi berkala (cron/scheduler)

```
1. Panggil action=check          → pastikan API aktif
2. Panggil action=get_all_summary → update ringkasan semua siswa di SIMAD
3. Simpan/update ke database SIMAD berdasarkan NISN
```

### Sinkronisasi on-demand (saat buka detail siswa)

```
1. Panggil action=get_student_data&nisn=... 
2. Tampilkan billing + tunggakan_tahun_ajaran_lama + payments
```

### Frekuensi rekomendasi

| Jenis | Frekuensi |
|-------|-----------|
| Bulk summary | 1× sehari (malam) atau setiap ada perubahan |
| Detail siswa | Real-time saat halaman dibuka |

---

## 9. Penanganan Error

| Kondisi | HTTP | Respons |
|---------|------|---------|
| API Key salah | 401 | `"Unauthorized: Invalid API Key"` |
| NISN kosong | 200 | `"NISN is required for this action"` |
| Siswa tidak ditemukan | 200 | `"Student not found"` |
| Action tidak valid | 200 | `"Invalid action"` |
| Koneksi gagal | — | Tangani di sisi client (timeout, DNS, SSL) |

> Semua respons error menggunakan `"status": "error"`. Selalu cek field `status` sebelum memproses data.

---

## 10. Checklist Uji Coba

Sebelum go-live, lakukan pengujian berikut:

- [ ] `action=check` mengembalikan `"status": "success"`
- [ ] `tahun_ajaran_aktif` terisi dengan benar
- [ ] `action=get_all_summary` mengembalikan data semua siswa
- [ ] `total_tunggakan` = `total_tunggakan_aktif` + `total_tunggakan_tahun_lama`
- [ ] Siswa dengan tunggakan lama: field `tunggakan_tahun_ajaran_lama` terisi
- [ ] Siswa lunas: `is_lunas` = `true` dan `total_tunggakan` = 0
- [ ] `action=get_student_data` dengan NISN valid mengembalikan `billing` dan `payments`
- [ ] Angka tunggakan di SIMAD cocok dengan tampilan di Sibayar SPP

---

## 11. Lampiran File

| File | Keterangan |
|------|------------|
| `api/simad.php` | Endpoint API (server-side, sudah terpasang di Sibayar) |
| `api/simad_client_example.php` | Client library PHP contoh untuk SIMAD |
| `api/SIMAD_INTEGRATION.md` | Dokumen ini |

---

## 12. Kontak & Dukungan

Untuk pertanyaan teknis terkait integrasi, hubungi administrator Sibayar SPP.

**Perubahan versi:**

| Versi | Tanggal | Perubahan |
|-------|---------|-----------|
| 1.0.0 | Feb 2026 | Rilis awal endpoint integrasi |
| 1.1.0 | Jul 2026 | Tambah tunggakan tahun ajaran lama, perbaikan filter `tahun_ajaran`, struktur response diperluas |

---

*Dokumen ini dibuat untuk tim pengembang Web SIMAD — MI Sultan Fattah Sukosono.*
