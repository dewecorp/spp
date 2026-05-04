<?php

/**
 * Cek apakah jenis bayar berlaku untuk siswa.
 * Di form admin, tagihan_kelas berisi nama_kelas (mis. "6"); tetap dukung id_kelas string untuk data lama.
 */
function jenis_bayar_berlaku_untuk_kelas($tagihan_kelas_raw, $id_kelas_siswa, $nama_kelas_siswa) {
    $tagihan_kelas_raw = trim((string) $tagihan_kelas_raw);
    if ($tagihan_kelas_raw === '') {
        return true;
    }
    $allowed = array_map('trim', explode(',', $tagihan_kelas_raw));
    $nama = trim((string) $nama_kelas_siswa);
    $id = trim((string) $id_kelas_siswa);
    return in_array($nama, $allowed) || in_array($id, $allowed);
}

/**
 * Map bulan akademik (nama: Juli..Juni) => pembayaran pertama yang melunasi bulan itu.
 * Satu baris pembayaran bisa berisi banyak bulan di kolom bulan_bayar (dipisah koma), seperti di transaksi.
 *
 * @return array<string, array<string, mixed>>
 */
function bulanan_map_pembayaran_per_bulan($koneksi, $nisn, $id_jenis_bayar) {
    $nisn_esc = mysqli_real_escape_string($koneksi, (string) $nisn);
    $id_esc = mysqli_real_escape_string($koneksi, (string) $id_jenis_bayar);
    $q = mysqli_query(
        $koneksi,
        "SELECT bulan_bayar, tgl_bayar, jumlah_bayar FROM pembayaran
         WHERE nisn = '$nisn_esc' AND id_jenis_bayar = '$id_esc'
         ORDER BY tgl_bayar ASC, id_pembayaran ASC"
    );
    $map = [];
    if (!$q) {
        return $map;
    }
    while ($row = mysqli_fetch_assoc($q)) {
        $raw = trim((string) ($row['bulan_bayar'] ?? ''));
        if ($raw === '') {
            continue;
        }
        $parts = array_map('trim', explode(',', $raw));
        $parts = array_values(array_filter($parts, static function ($x) {
            return $x !== '';
        }));
        $n = count($parts);
        if ($n < 1) {
            continue;
        }
        $amt = (int) ($row['jumlah_bayar'] ?? 0);
        $per = (int) round($amt / $n);
        foreach ($parts as $pm) {
            if (!isset($map[$pm])) {
                $map[$pm] = [
                    'tgl_bayar' => $row['tgl_bayar'],
                    'jumlah' => $per,
                ];
            }
        }
    }
    return $map;
}
