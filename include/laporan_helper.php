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

/**
 * Periksa apakah siswa memiliki tagihan tunggakan.
 * Tagihan dianggap tunggakan jika melewati tahun ajaran berjalan atau sampai bulan Juli.
 * Mengembalikan array dengan detail tagihan atau false jika tidak ada.
 */
function cek_tagihan_tunggakan($koneksi, $nisn) {
    // Cari data siswa
    $q_siswa = mysqli_query($koneksi, "SELECT s.*, k.nama_kelas FROM siswa s JOIN kelas k ON s.id_kelas = k.id_kelas WHERE s.nisn = '$nisn'");
    $d_siswa = mysqli_fetch_assoc($q_siswa);
    if (!$d_siswa) {
        return false;
    }
    
    $id_kelas_siswa = $d_siswa['id_kelas'];
    $nama_kelas_siswa = $d_siswa['nama_kelas'];
    
    // Daftar bulan akademik
    $months = ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni'];
    $current_month_num = date('n'); // 1-12
    $limit_index = ($current_month_num >= 7) ? $current_month_num - 7 : $current_month_num + 5;
    
    $tagihan_tunggakan = [];
    
    // Dapatkan semua jenis pembayaran aktif
    $q_jenis = mysqli_query($koneksi, "SELECT * FROM jenis_bayar WHERE status = 'Aktif' ORDER BY tipe_bayar ASC");
    
    while ($jb = mysqli_fetch_assoc($q_jenis)) {
        if (jenis_bayar_berlaku_untuk_kelas($jb['tagihan_kelas'] ?? '', $id_kelas_siswa, $nama_kelas_siswa)) {
            $is_fully_paid = false;
            $unpaid_details = [];
            $sisa = 0;
            
            if ($jb['tipe_bayar'] == 'Bulanan') {
                // Dapatkan bulan yang sudah dibayar
                $paid_months = [];
                $q_bayar = mysqli_query($koneksi, "SELECT bulan_bayar FROM pembayaran WHERE nisn='$nisn' AND id_jenis_bayar='" . $jb['id_jenis_bayar'] . "'");
                while ($row = mysqli_fetch_assoc($q_bayar)) {
                    if (!empty($row['bulan_bayar'])) {
                        $ms = array_map('trim', explode(',', $row['bulan_bayar']));
                        $paid_months = array_merge($paid_months, $ms);
                    }
                }
                
                // Periksa bulan yang belum dibayar
                $is_extracurricular = stripos($jb['nama_pembayaran'], 'ekstrakurikuler') !== false;
                
                foreach ($months as $index => $m) {
                    // Jika bukan ekstrakurikuler, hanya periksa bulan yang sudah lewat
                    if (!$is_extracurricular && $index > $limit_index) continue;
                    
                    if (!in_array($m, $paid_months)) {
                        $unpaid_details[] = $m;
                        $sisa += (int)$jb['nominal'];
                    }
                }
                
                if (empty($unpaid_details)) {
                    $is_fully_paid = true;
                }
            } else {
                // Cicilan / Bebas
                $q_total = mysqli_query($koneksi, "SELECT SUM(jumlah_bayar) as total FROM pembayaran WHERE nisn='$nisn' AND id_jenis_bayar='" . $jb['id_jenis_bayar'] . "'");
                $d_total = mysqli_fetch_assoc($q_total);
                $total_bayar = $d_total['total'] ?? 0;
                $sisa = $jb['nominal'] - $total_bayar;
                
                if ($sisa <= 0) {
                    $is_fully_paid = true;
                } else {
                    $unpaid_details[] = 'Sisa Tagihan';
                }
            }
            
            if (!$is_fully_paid && $sisa > 0) {
                $tagihan_tunggakan[] = [
                    'id_jenis_bayar' => $jb['id_jenis_bayar'],
                    'nama_pembayaran' => $jb['nama_pembayaran'],
                    'tipe_bayar' => $jb['tipe_bayar'],
                    'nominal' => $jb['nominal'],
                    'sisa' => $sisa,
                    'unpaid_details' => $unpaid_details
                ];
            }
        }
    }
    
    return !empty($tagihan_tunggakan) ? $tagihan_tunggakan : false;
}
