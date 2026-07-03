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

function get_tahun_ajaran_aktif($koneksi) {
    $q = mysqli_query($koneksi, "SELECT tahun_ajaran FROM pengaturan WHERE id_pengaturan = 1 LIMIT 1");
    if ($q && $row = mysqli_fetch_assoc($q)) {
        $tahun_ajaran = trim((string) ($row['tahun_ajaran'] ?? ''));
        if ($tahun_ajaran !== '') {
            return $tahun_ajaran;
        }
    }

    $year = (int) date('Y');
    $month = (int) date('n');
    return $month >= 7 ? $year . '/' . ($year + 1) : ($year - 1) . '/' . $year;
}

function default_tanggal_mulai_tahun_ajaran($tahun_ajaran) {
    if (preg_match('/^(\d{4})\s*\/\s*(\d{4})$/', trim((string) $tahun_ajaran), $m)) {
        return $m[1] . '-07-01';
    }

    return date('Y') . '-07-01';
}

function ensure_pengaturan_tanggal_mulai_column($koneksi) {
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    $col = mysqli_query($koneksi, "SHOW COLUMNS FROM pengaturan LIKE 'tanggal_mulai_tahun_ajaran'");
    if ($col && mysqli_num_rows($col) > 0) {
        $q = mysqli_query($koneksi, "SELECT tahun_ajaran, tanggal_mulai_tahun_ajaran FROM pengaturan WHERE id_pengaturan = 1 LIMIT 1");
        if ($q && $row = mysqli_fetch_assoc($q)) {
            if (trim((string)($row['tanggal_mulai_tahun_ajaran'] ?? '')) === '') {
                $default = mysqli_real_escape_string($koneksi, default_tanggal_mulai_tahun_ajaran($row['tahun_ajaran'] ?? ''));
                mysqli_query($koneksi, "UPDATE pengaturan SET tanggal_mulai_tahun_ajaran = '$default' WHERE id_pengaturan = 1");
            }
        }
        return;
    }

    mysqli_query($koneksi, "ALTER TABLE pengaturan ADD tanggal_mulai_tahun_ajaran DATE NULL AFTER tahun_ajaran");
    $q = mysqli_query($koneksi, "SELECT tahun_ajaran FROM pengaturan WHERE id_pengaturan = 1 LIMIT 1");
    $row = $q ? mysqli_fetch_assoc($q) : null;
    $default = mysqli_real_escape_string($koneksi, default_tanggal_mulai_tahun_ajaran($row['tahun_ajaran'] ?? ''));
    mysqli_query($koneksi, "UPDATE pengaturan SET tanggal_mulai_tahun_ajaran = '$default' WHERE id_pengaturan = 1");
}

function get_tanggal_mulai_tahun_ajaran_aktif($koneksi) {
    ensure_pengaturan_tanggal_mulai_column($koneksi);
    $q = mysqli_query($koneksi, "SELECT tahun_ajaran, tanggal_mulai_tahun_ajaran FROM pengaturan WHERE id_pengaturan = 1 LIMIT 1");
    if ($q && $row = mysqli_fetch_assoc($q)) {
        $tanggal = trim((string)($row['tanggal_mulai_tahun_ajaran'] ?? ''));
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
            return $tanggal;
        }
        return default_tanggal_mulai_tahun_ajaran($row['tahun_ajaran'] ?? get_tahun_ajaran_aktif($koneksi));
    }

    return default_tanggal_mulai_tahun_ajaran(get_tahun_ajaran_aktif($koneksi));
}

function limit_index_bulan_tahun_ajaran($koneksi, $tahun_ajaran) {
    if ($tahun_ajaran !== get_tahun_ajaran_aktif($koneksi)) {
        return 11;
    }

    $mulai = DateTime::createFromFormat('Y-m-d', get_tanggal_mulai_tahun_ajaran_aktif($koneksi));
    if (!$mulai) {
        return -1;
    }

    $today = new DateTime(date('Y-m-d'));
    if ($today < $mulai) {
        return -1;
    }

    $diff = $mulai->diff($today);
    $index = ($diff->y * 12) + $diff->m;
    return max(0, min(11, $index));
}

function tahun_ajaran_sebelumnya($tahun_ajaran) {
    if (preg_match('/^(\d{4})\s*\/\s*(\d{4})$/', trim((string) $tahun_ajaran), $m)) {
        return ((int) $m[1] - 1) . '/' . ((int) $m[2] - 1);
    }

    return '';
}

function tahun_ajaran_awal($tahun_ajaran) {
    if (preg_match('/^(\d{4})\s*\/\s*(\d{4})$/', trim((string) $tahun_ajaran), $m)) {
        return (int) $m[1];
    }

    return null;
}

function daftar_tahun_ajaran_lama_untuk_tunggakan($koneksi, $nisn, $tahun_ajaran_aktif = null) {
    ensure_pembayaran_tahun_ajaran_column($koneksi);

    $tahun_ajaran_aktif = trim((string) ($tahun_ajaran_aktif ?? get_tahun_ajaran_aktif($koneksi)));
    $tahun_aktif_awal = tahun_ajaran_awal($tahun_ajaran_aktif);
    $tahun_ajaran_opsi = [];

    $tahun_sebelumnya = tahun_ajaran_sebelumnya($tahun_ajaran_aktif);
    if ($tahun_sebelumnya !== '') {
        $tahun_ajaran_opsi[] = $tahun_sebelumnya;
    }

    $nisn_esc = mysqli_real_escape_string($koneksi, (string) $nisn);
    $q_tahun = mysqli_query($koneksi, "SELECT DISTINCT tahun_ajaran FROM pembayaran WHERE nisn = '$nisn_esc' AND tahun_ajaran IS NOT NULL AND tahun_ajaran <> ''");
    if ($q_tahun) {
        while ($row = mysqli_fetch_assoc($q_tahun)) {
            $tahun = trim((string) ($row['tahun_ajaran'] ?? ''));
            if ($tahun !== '') {
                $tahun_ajaran_opsi[] = $tahun;
            }
        }
    }

    $tahun_ajaran_opsi = array_values(array_unique(array_filter($tahun_ajaran_opsi)));
    usort($tahun_ajaran_opsi, static function ($a, $b) {
        return (tahun_ajaran_awal($b) ?? 0) <=> (tahun_ajaran_awal($a) ?? 0);
    });

    $tahun_ajaran_lama = [];
    foreach ($tahun_ajaran_opsi as $tahun) {
        $awal = tahun_ajaran_awal($tahun);
        if ($awal === null || $tahun_aktif_awal === null || $awal < $tahun_aktif_awal) {
            $tahun_ajaran_lama[] = $tahun;
        }
    }

    return array_values(array_unique($tahun_ajaran_lama));
}

function cek_tunggakan_tahun_ajaran_lama($koneksi, $nisn, $tahun_ajaran_aktif = null) {
    $tahun_ajaran_aktif = trim((string) ($tahun_ajaran_aktif ?? get_tahun_ajaran_aktif($koneksi)));
    $hasil = [];

    foreach (daftar_tahun_ajaran_lama_untuk_tunggakan($koneksi, $nisn, $tahun_ajaran_aktif) as $tahun_ajaran_lama) {
        $tagihan = cek_tagihan_tunggakan($koneksi, $nisn, $tahun_ajaran_lama);
        if ($tagihan) {
            $hasil[$tahun_ajaran_lama] = $tagihan;
        }
    }

    return !empty($hasil) ? $hasil : false;
}

function isi_tahun_ajaran_pembayaran_kosong($koneksi) {
    mysqli_query(
        $koneksi,
        "UPDATE pembayaran
         SET tahun_ajaran = CASE
             WHEN MONTH(tgl_bayar) >= 7 THEN CONCAT(YEAR(tgl_bayar), '/', YEAR(tgl_bayar) + 1)
             ELSE CONCAT(YEAR(tgl_bayar) - 1, '/', YEAR(tgl_bayar))
         END
         WHERE tahun_ajaran IS NULL OR tahun_ajaran = ''"
    );
}

function normalisasi_tahun_ajaran_tagihan_tunggakan($koneksi) {
    mysqli_query(
        $koneksi,
        "UPDATE pembayaran
         SET tahun_ajaran = CASE
             WHEN MONTH(tgl_bayar) >= 7 THEN CONCAT(YEAR(tgl_bayar) - 1, '/', YEAR(tgl_bayar))
             ELSE CONCAT(YEAR(tgl_bayar) - 1, '/', YEAR(tgl_bayar))
         END
         WHERE (ket = 'Pembayaran Tagihan Tunggakan' OR no_transaksi LIKE 'TRX-TAG-%')
           AND (tahun_ajaran IS NULL OR tahun_ajaran = '' OR tahun_ajaran = CASE
               WHEN MONTH(tgl_bayar) >= 7 THEN CONCAT(YEAR(tgl_bayar), '/', YEAR(tgl_bayar) + 1)
               ELSE CONCAT(YEAR(tgl_bayar) - 1, '/', YEAR(tgl_bayar))
           END)"
    );
}

function ensure_pembayaran_tahun_ajaran_column($koneksi) {
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    $col = mysqli_query($koneksi, "SHOW COLUMNS FROM pembayaran LIKE 'tahun_ajaran'");
    if ($col && mysqli_num_rows($col) > 0) {
        isi_tahun_ajaran_pembayaran_kosong($koneksi);
        normalisasi_tahun_ajaran_tagihan_tunggakan($koneksi);
        return;
    }

    mysqli_query($koneksi, "ALTER TABLE pembayaran ADD tahun_ajaran VARCHAR(20) DEFAULT '' AFTER tahun_bayar");
    isi_tahun_ajaran_pembayaran_kosong($koneksi);
    normalisasi_tahun_ajaran_tagihan_tunggakan($koneksi);
}

/**
 * Periksa apakah siswa memiliki tagihan tunggakan.
 * Tagihan dianggap tunggakan jika melewati tahun ajaran berjalan atau sampai bulan Juli.
 * Mengembalikan array dengan detail tagihan atau false jika tidak ada.
 */
function cek_tagihan_tunggakan($koneksi, $nisn, $tahun_ajaran = null) {
    ensure_pembayaran_tahun_ajaran_column($koneksi);

    $tahun_ajaran = trim((string) ($tahun_ajaran ?? get_tahun_ajaran_aktif($koneksi)));
    if ($tahun_ajaran === '') {
        $tahun_ajaran = get_tahun_ajaran_aktif($koneksi);
    }
    $tahun_ajaran_esc = mysqli_real_escape_string($koneksi, $tahun_ajaran);

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
    $limit_index = limit_index_bulan_tahun_ajaran($koneksi, $tahun_ajaran);
    
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
                $q_bayar = mysqli_query($koneksi, "SELECT bulan_bayar FROM pembayaran WHERE nisn='$nisn' AND id_jenis_bayar='" . $jb['id_jenis_bayar'] . "' AND tahun_ajaran='$tahun_ajaran_esc'");
                while ($row = mysqli_fetch_assoc($q_bayar)) {
                    if (!empty($row['bulan_bayar'])) {
                        $ms = array_map('trim', explode(',', $row['bulan_bayar']));
                        $paid_months = array_merge($paid_months, $ms);
                    }
                }
                
                // Periksa bulan yang sudah jatuh tempo pada tahun ajaran ini.
                foreach ($months as $index => $m) {
                    if ($limit_index < 0) continue;
                    if ($index > $limit_index) continue;
                    
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
                $q_total = mysqli_query($koneksi, "SELECT SUM(jumlah_bayar) as total FROM pembayaran WHERE nisn='$nisn' AND id_jenis_bayar='" . $jb['id_jenis_bayar'] . "' AND tahun_ajaran='$tahun_ajaran_esc'");
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
                    'unpaid_details' => $unpaid_details,
                    'tahun_ajaran' => $tahun_ajaran
                ];
            }
        }
    }
    
    return !empty($tagihan_tunggakan) ? $tagihan_tunggakan : false;
}
