# Debug Session: lks-hosting-fail

Status: OPEN
Started: 2026-06-17

## Gejala
- Transaksi bayar untuk jenis `LKS 1-3` gagal di hosting.
- Di local transaksi yang sama sukses.
- Jenis bayar lain normal.
- Pesan gagal: `Tidak ada item pembayaran yang valid untuk disimpan.`

## Hipotesis Awal
1. Mapping item pembayaran `LKS 1-3` berbeda antara environment local dan hosting, sehingga query item di hosting menghasilkan kosong.
2. Perbandingan string untuk jenis bayar sensitif terhadap perbedaan encoding, spasi, atau kolasi database di hosting.
3. Ada data master yang tidak lengkap atau berbeda di hosting khusus untuk kategori `LKS 1-3`.
4. Filter tanggal, tahun ajaran, atau relasi siswa-kelas yang dipakai untuk `LKS 1-3` berjalan berbeda di hosting karena konfigurasi PHP/MySQL.
5. Payload dari frontend untuk `LKS 1-3` berubah format saat di hosting, sehingga backend menolak semua item sebagai tidak valid.

## Rencana
1. Telusuri alur frontend dan backend untuk transaksi bayar `LKS 1-3`.
2. Tambahkan instrumentation log minimal pada titik pembentukan payload dan validasi item pembayaran.
3. Bandingkan bukti runtime local vs hosting.
4. Terapkan perbaikan minimal berdasarkan bukti.

## Temuan
- Error `Tidak ada item pembayaran yang valid untuk disimpan.` hanya muncul bila seluruh `id_jenis_bayar` yang diterima backend tidak pernah lolos ke proses insert.
- Data lokal menunjukkan `LKS 1-3` (`id_jenis_bayar=2`) dan `LKS 4-6` (`id_jenis_bayar=5`) sama-sama bertipe `Cicilan`, sehingga akar masalah bukan pada cabang `tipe_bayar`.
- Perbedaan environment paling mungkin terjadi pada dua titik rapuh: array `id_jenis_bayar[]` dari multi-select yang tidak konsisten terkirim di hosting, dan pencocokan `kelas` yang terlalu mengandalkan string mentah.

## Instrumentation
- Menambahkan log debug di `transaksi/transaksi.php` pada titik masuk request, hasil baca kelas siswa, pemuatan `jenis_bayar`, validasi `tagihan_kelas`, ringkasan loop, dan catch error.

## Fix
- Normalisasi `id_jenis_bayar` agar hanya menerima ID numerik valid.
- Menambahkan fallback pembacaan ID dari key `payment[...]` dan `payment[id][id_jenis_bayar]` saat `id_jenis_bayar[]` kosong atau tidak valid.
- Menambahkan normalisasi nama kelas agar `1`, `Kelas 1`, dan `I` tetap dianggap setara saat dicocokkan dengan `tagihan_kelas`.

## Verifikasi Lokal
- Uji CLI menunjukkan fallback ID berhasil mengubah payload dengan `raw_ids=['']` tetapi `payment[2]` tersedia menjadi `normalized_ids=[2]`.
- Uji CLI menunjukkan pencocokan kelas tetap berhasil untuk variasi `Kelas 1`, `I`, dan `6`.

## Iterasi Lanjutan
- Setelah deploy pertama, gejala berubah dari `Tidak ada item pembayaran yang valid untuk disimpan.` menjadi `Input transaksi belum lengkap.`
- Perubahan gejala ini menunjukkan backend sudah melewati masalah lama, tetapi frontend di hosting masih berpotensi tidak mengirim `id_jenis_bayar[]` / `payment[...]` untuk `LKS 1-3`.
- Akar masalah yang paling mungkin: filter kelas di JavaScript masih menilai `LKS 1-3` tidak valid untuk siswa kelas 1-3 karena format kelas di hosting berbeda dari local.
- Iterasi fix kedua menambahkan normalisasi kelas di JavaScript pada modal tambah dan edit agar nilai seperti `1`, `Kelas 1`, `1A`, dan `I` tetap cocok dengan `tagihan_kelas=1,2,3`.

## Bukti Hosting (Ref)
- Ref `trx-20260617143344-df1fd876` menunjukkan payload untuk kasus LKS 1-3 di hosting mengirim `id_jenis_bayar` bernilai `0` dan `payment` juga terkunci di key `0`.
- Contoh ringkas dari log:
  - `raw_id_jenis_bayar=["0","0"]`
  - `normalized_id_jenis_bayar=[]`
  - `payment_keys=[0]`
  - `payment_item_ids=[]`
- Ini menjelaskan mengapa sebelumnya loop transaksi tidak pernah memproses item LKS 1-3: nilai `0` tersaring oleh validasi `> 0`.

## Fix Tambahan
- Backend kini menerima `id_jenis_bayar=0` (jika memang ada record `jenis_bayar` ID 0 di hosting), sehingga item tidak lagi otomatis ter-skip.
