DROP TABLE IF EXISTS jenis_bayar;

CREATE TABLE `jenis_bayar` (
  `id_jenis_bayar` int NOT NULL AUTO_INCREMENT,
  `nama_pembayaran` varchar(50) NOT NULL,
  `nominal` int NOT NULL,
  `tipe_bayar` enum('Bulanan','Cicilan') NOT NULL DEFAULT 'Bulanan',
  `kali_cicilan` int DEFAULT '0',
  `tagihan_kelas` text,
  PRIMARY KEY (`id_jenis_bayar`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO jenis_bayar VALUES("1","Iuran Ekstrakurikuler","10000","Bulanan","0","1,2,3,4,5,6");
INSERT INTO jenis_bayar VALUES("2","LKS","150000","Cicilan","3","1,2,3,4,5,6");
INSERT INTO jenis_bayar VALUES("3","Biaya Ujian 2026","900000","Cicilan","5","6");
INSERT INTO jenis_bayar VALUES("4","Iuran Rekreasi","250000","Cicilan","10","6");



DROP TABLE IF EXISTS kelas;

CREATE TABLE `kelas` (
  `id_kelas` int NOT NULL AUTO_INCREMENT,
  `nama_kelas` varchar(20) NOT NULL,
  PRIMARY KEY (`id_kelas`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO kelas VALUES("1","1");
INSERT INTO kelas VALUES("2","2");
INSERT INTO kelas VALUES("3","3");
INSERT INTO kelas VALUES("4","4");
INSERT INTO kelas VALUES("5","5");
INSERT INTO kelas VALUES("6","6");



DROP TABLE IF EXISTS log_aktivitas;

CREATE TABLE `log_aktivitas` (
  `id_log` int NOT NULL AUTO_INCREMENT,
  `id_pengguna` int DEFAULT NULL,
  `jenis_aktivitas` varchar(50) NOT NULL,
  `deskripsi` text NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_log`),
  KEY `id_pengguna` (`id_pengguna`),
  CONSTRAINT `log_aktivitas_ibfk_1` FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id_pengguna`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO log_aktivitas VALUES("1","1","Update","Mengubah data siswa: Budi (123456)","2026-02-01 18:02:05");
INSERT INTO log_aktivitas VALUES("2","1","Update","Mengedit jenis bayar: Iuran Ekstrakurikuler (2025/2026)","2026-02-01 18:12:38");
INSERT INTO log_aktivitas VALUES("3","1","Update","Mengedit kelas: 1","2026-02-01 18:12:43");
INSERT INTO log_aktivitas VALUES("4","1","Create","Membuat backup database: backup_2026-02-01_18-31-18.sql","2026-02-01 18:31:18");
INSERT INTO log_aktivitas VALUES("5","1","Create","Membuat backup database: backup_2026-02-01_18-31-23.sql","2026-02-01 18:31:23");
INSERT INTO log_aktivitas VALUES("6","1","Delete","Menghapus file backup: backup_2026-02-01_10-08-37.sql","2026-02-01 18:31:30");
INSERT INTO log_aktivitas VALUES("7","1","Update","Mengedit jenis bayar: LKS (Cicilan)","2026-02-01 19:20:45");
INSERT INTO log_aktivitas VALUES("8","1","Update","Mengedit jenis bayar: LKS (Cicilan)","2026-02-01 19:25:08");
INSERT INTO log_aktivitas VALUES("9","1","Update","Mengedit jenis bayar: Iuran Ekstrakurikuler (Bulanan)","2026-02-01 19:25:14");
INSERT INTO log_aktivitas VALUES("10","1","Create","Menambah jenis bayar: Biaya Ujian 2026 (Bulanan)","2026-02-01 19:27:37");
INSERT INTO log_aktivitas VALUES("11","1","Update","Mengedit jenis bayar: Biaya Ujian 2026 (Cicilan)","2026-02-01 19:36:14");
INSERT INTO log_aktivitas VALUES("12","1","Create","Menambah transaksi pembayaran NISN: 123456","2026-02-01 19:39:31");
INSERT INTO log_aktivitas VALUES("13","1","Create","Menambah transaksi pembayaran NISN: 987654321","2026-02-01 19:40:45");
INSERT INTO log_aktivitas VALUES("14","1","Create","Menambah 2 transaksi pembayaran NISN: 123456","2026-02-01 20:44:34");
INSERT INTO log_aktivitas VALUES("15","1","Update","Mengedit transaksi No: TRX-OLD-4-9450","2026-02-01 21:44:17");
INSERT INTO log_aktivitas VALUES("16","1","Delete","Menghapus transaksi No: TRX-OLD-3-1635","2026-02-01 21:45:33");
INSERT INTO log_aktivitas VALUES("17","1","Create","Import 19 data siswa via Excel","2026-02-02 09:37:05");
INSERT INTO log_aktivitas VALUES("18","1","Update","Multi update 2 data siswa","2026-02-02 10:38:03");
INSERT INTO log_aktivitas VALUES("19","1","Create","Menambah 1 transaksi pembayaran NISN: 3146588936","2026-02-02 10:42:53");
INSERT INTO log_aktivitas VALUES("20","1","Update","Mengedit transaksi No: TRX-20260202104253-993","2026-02-02 10:43:50");
INSERT INTO log_aktivitas VALUES("21","1","Update","Mengedit transaksi No: TRX-20260202104253-993","2026-02-02 10:45:16");
INSERT INTO log_aktivitas VALUES("22","1","Update","Mengedit transaksi No: TRX-20260202104253-993","2026-02-02 10:45:51");
INSERT INTO log_aktivitas VALUES("23","1","Update","Mengedit transaksi No: TRX-20260202104253-993","2026-02-02 10:46:10");
INSERT INTO log_aktivitas VALUES("24","1","Update","Mengedit transaksi No: TRX-20260202104253-993","2026-02-02 10:52:29");
INSERT INTO log_aktivitas VALUES("25","1","Update","Mengedit transaksi No: TRX-20260202104253-993","2026-02-02 10:59:08");
INSERT INTO log_aktivitas VALUES("26","1","Update","Mengedit transaksi No: TRX-20260202104253-993","2026-02-02 10:59:32");
INSERT INTO log_aktivitas VALUES("27","1","Create","Menambah jenis bayar: Iuran Rekreasi (Cicilan)","2026-02-02 11:12:05");
INSERT INTO log_aktivitas VALUES("28","1","Create","Menambah 1 transaksi pembayaran NISN: 3132163433","2026-02-02 11:13:28");
INSERT INTO log_aktivitas VALUES("29","1","Logout","Logout berhasil","2026-02-02 13:44:26");
INSERT INTO log_aktivitas VALUES("30","2","Login","Login berhasil","2026-02-02 13:44:30");
INSERT INTO log_aktivitas VALUES("31","2","Create","Menambah 1 transaksi pembayaran NISN: 123456","2026-02-02 13:45:22");
INSERT INTO log_aktivitas VALUES("32","2","Logout","Logout berhasil","2026-02-02 13:46:59");
INSERT INTO log_aktivitas VALUES("33","1","Login","Login berhasil","2026-02-02 13:47:18");



DROP TABLE IF EXISTS pembayaran;

CREATE TABLE `pembayaran` (
  `id_pembayaran` int NOT NULL AUTO_INCREMENT,
  `no_transaksi` varchar(50) DEFAULT NULL,
  `id_petugas` int NOT NULL,
  `nisn` varchar(20) NOT NULL,
  `tgl_bayar` date NOT NULL,
  `bulan_bayar` text,
  `tahun_bayar` varchar(20) DEFAULT NULL,
  `id_jenis_bayar` int NOT NULL,
  `jumlah_bayar` int NOT NULL,
  `cicilan_ke` int DEFAULT '0',
  `ket` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pembayaran`),
  KEY `id_petugas` (`id_petugas`),
  KEY `nisn` (`nisn`),
  KEY `id_jenis_bayar` (`id_jenis_bayar`),
  CONSTRAINT `pembayaran_ibfk_1` FOREIGN KEY (`id_petugas`) REFERENCES `pengguna` (`id_pengguna`),
  CONSTRAINT `pembayaran_ibfk_2` FOREIGN KEY (`nisn`) REFERENCES `siswa` (`nisn`) ON DELETE CASCADE,
  CONSTRAINT `pembayaran_ibfk_3` FOREIGN KEY (`id_jenis_bayar`) REFERENCES `jenis_bayar` (`id_jenis_bayar`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO pembayaran VALUES("1","TRX-OLD-1-3397","1","123456","2026-02-01","","","2","50000","1","Cicilan ke-1","2026-02-01 19:39:31");
INSERT INTO pembayaran VALUES("2","TRX-OLD-2-8813","1","987654321","2026-02-01","","","1","10000","0","Lunas (Bulanan)","2026-02-01 19:40:45");
INSERT INTO pembayaran VALUES("5","TRX-OLD-4-9450","1","123456","2026-02-01","Januari, Februari","2026","1","20000","0","Lunas (Bulanan) - Januari, Februari","2026-02-01 21:44:17");
INSERT INTO pembayaran VALUES("6","TRX-OLD-4-9450","1","123456","2026-02-01","","2026","2","50000","1","Cicilan ke-1","2026-02-01 21:44:17");
INSERT INTO pembayaran VALUES("9","TRX-20260202104253-993","1","3146588936","2026-02-02","","2026","3","100000","1","Cicilan ke-1","2026-02-02 10:59:08");
INSERT INTO pembayaran VALUES("10","TRX-20260202104253-993","1","3146588936","2026-02-02","November, Desember, Januari, Februari","2026","1","40000","0","Lunas (Bulanan) - November, Desember, Januari, Februari","2026-02-02 10:59:08");
INSERT INTO pembayaran VALUES("11","TRX-20260202104253-993","1","3146588936","2026-02-02","","2026","2","50000","1","Cicilan ke-1","2026-02-02 10:59:32");
INSERT INTO pembayaran VALUES("12","TRX-20260202111328-152","1","3132163433","2026-02-02","","2026","4","100000","1","Cicilan ke-1","2026-02-02 11:13:28");
INSERT INTO pembayaran VALUES("13","TRX-20260202134522-528","2","123456","2026-02-02","Februari","2026","1","10000","0","Lunas (Bulanan) - Februari","2026-02-02 13:45:22");



DROP TABLE IF EXISTS pengaturan;

CREATE TABLE `pengaturan` (
  `id_pengaturan` int NOT NULL,
  `nama_sekolah` varchar(100) DEFAULT NULL,
  `alamat_sekolah` text,
  `logo` varchar(255) DEFAULT NULL,
  `nama_bendahara` varchar(100) DEFAULT NULL,
  `tahun_ajaran` varchar(20) DEFAULT '',
  PRIMARY KEY (`id_pengaturan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO pengaturan VALUES("1","MI Sultan Fattah Sukosono","Sukosono Kedung Jepara 59463","logo.png","Zamaah, S.Pd.I","2025/2026");



DROP TABLE IF EXISTS pengguna;

CREATE TABLE `pengguna` (
  `id_pengguna` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `role` enum('admin','petugas') NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `foto` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_pengguna`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO pengguna VALUES("1","admin","$2y$10$8CDkDNvsxuABPt9TxvEM/OxgfztNhqVW0nWgU0J252sBmXDEQ7z7O","Administrator","admin","2026-01-31 17:14:42","");
INSERT INTO pengguna VALUES("2","petugas","$2y$10$pGsvwbguI4d77Ry9Wqb5QugZI0xXwRVQIS9GBsbSusUOLy6sTcoti","Zamaah, S.Pd.I.","petugas","2026-02-01 17:33:49","");



DROP TABLE IF EXISTS siswa;

CREATE TABLE `siswa` (
  `nisn` varchar(20) NOT NULL,
  `nis` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `id_kelas` int NOT NULL,
  `alamat` text,
  `no_telp` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`nisn`),
  KEY `id_kelas` (`id_kelas`),
  CONSTRAINT `siswa_ibfk_1` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO siswa VALUES("0137840437","-","Diyah Ayu Prawesti","6","","");
INSERT INTO siswa VALUES("123456","-","Budi","1","","");
INSERT INTO siswa VALUES("1234567","-","Santoso","1","","");
INSERT INTO siswa VALUES("3130180823","-","Muhammad Daris Alfurqon Aqim","6","","");
INSERT INTO siswa VALUES("3130250384","-","Muhammad Elga Saputra","6","","");
INSERT INTO siswa VALUES("3131634863","-","Siti Afifah Nauvalyn Fikriyah","6","","");
INSERT INTO siswa VALUES("3132163433","-","Bilqis Fahiya Rifda","6","","");
INSERT INTO siswa VALUES("3133041280","-","Lidia Aura Citra","6","","");
INSERT INTO siswa VALUES("3133372371","-","Fabregas Alviano","6","","");
INSERT INTO siswa VALUES("3135628625","-","Aqilah Khoirurrosyadah","6","","");
INSERT INTO siswa VALUES("3136264986","-","Putra Sadewa Saifunnawas","6","","");
INSERT INTO siswa VALUES("3137207114","-","Rizquna Halalan Thoyyiba","6","","");
INSERT INTO siswa VALUES("3137563185","-","Amrina Rosyada","6","","");
INSERT INTO siswa VALUES("3137847985","-","Dzakira Talita Azzahra","6","","");
INSERT INTO siswa VALUES("3138275600","-","Dewi Khuzaimah Annisa","6","","");
INSERT INTO siswa VALUES("3139561428","-","Siti Mei Listiana","6","","");
INSERT INTO siswa VALUES("3140702123","-","Muhammad Egi Ferdiansyah","6","","");
INSERT INTO siswa VALUES("3141710676","-","Najwah Fadia Amalia Fitri","6","","");
INSERT INTO siswa VALUES("3146510193","-","Indana Zulfa","6","","");
INSERT INTO siswa VALUES("3146588936","-","ADIBA NUHA AZZAHRA","6","","");
INSERT INTO siswa VALUES("3149297726","-","Muhammad Agung Susilo Sugiono","6","","");
INSERT INTO siswa VALUES("987654321","321542","SUDARLIM","1","Sukosono Kedung Jepara","086969696969");



