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
) ENGINE=InnoDB AUTO_INCREMENT=74 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO log_aktivitas VALUES("70","1","Login","Login berhasil","2026-02-05 09:48:45");
INSERT INTO log_aktivitas VALUES("71","1","Update","Mengubah data siswa: Budi (123456)","2026-02-05 10:20:16");
INSERT INTO log_aktivitas VALUES("72","1","Update","Mengubah data siswa: Amrina Rosyada (3137563185)","2026-02-05 10:20:28");
INSERT INTO log_aktivitas VALUES("73","1","Update","Mengedit jenis bayar: Iuran Rekreasi (Cicilan)","2026-02-05 10:20:45");



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
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO pembayaran VALUES("9","TRX-20260202104253-993","1","3146588936","2026-02-02","","2026","3","100000","1","Cicilan ke-1","2026-02-02 10:59:08");
INSERT INTO pembayaran VALUES("10","TRX-20260202104253-993","1","3146588936","2026-02-02","November, Desember, Januari, Februari","2026","1","40000","0","Lunas (Bulanan) - November, Desember, Januari, Februari","2026-02-02 10:59:08");
INSERT INTO pembayaran VALUES("11","TRX-20260202104253-993","1","3146588936","2026-02-02","","2026","2","50000","1","Cicilan ke-1","2026-02-02 10:59:32");
INSERT INTO pembayaran VALUES("12","TRX-20260202111328-152","1","3132163433","2026-02-02","","2026","4","100000","1","Cicilan ke-1","2026-02-02 11:13:28");
INSERT INTO pembayaran VALUES("13","TRX-20260202134522-528","2","123456","2026-02-02","Februari","2026","1","10000","0","Lunas (Bulanan) - Februari","2026-02-02 13:45:22");
INSERT INTO pembayaran VALUES("14","TRX-202602-001","1","3135628625","2026-02-02","","2026","3","200000","1","Cicilan ke-1","2026-02-02 18:00:39");
INSERT INTO pembayaran VALUES("15","TRX-202602-001","1","3135628625","2026-02-02","","2026","4","200000","1","Cicilan ke-1","2026-02-02 18:00:39");
INSERT INTO pembayaran VALUES("16","TRX-202602-002","1","3137563185","2026-02-02","Januari, Februari","2026","1","20000","0","Lunas (Bulanan) - Januari, Februari","2026-02-02 18:02:51");
INSERT INTO pembayaran VALUES("17","TRX-202602-003","1","3133372371","2026-02-02","","2026","4","50000","1","Cicilan ke-1","2026-02-02 18:03:47");
INSERT INTO pembayaran VALUES("18","TRX-202602-003","1","3133372371","2026-02-02","","2026","2","100000","1","Cicilan ke-1","2026-02-02 18:03:47");
INSERT INTO pembayaran VALUES("19","TRX-202602-004","1","3149297726","2026-02-03","","2026","4","150000","1","Cicilan ke-1","2026-02-03 08:56:00");
INSERT INTO pembayaran VALUES("20","TRX-202602-005","1","3130180823","2026-02-03","","2026","2","50000","1","Cicilan ke-1","2026-02-03 09:20:12");
INSERT INTO pembayaran VALUES("21","TRX-202602-006","1","3133041280","2026-02-03","","2026","3","100000","1","Cicilan ke-1","2026-02-03 09:24:30");
INSERT INTO pembayaran VALUES("22","TRX-202602-007","1","3137207114","2026-02-03","","2026","3","100000","1","Cicilan ke-1","2026-02-03 09:37:44");
INSERT INTO pembayaran VALUES("23","TRX-202602-008","1","3136264986","2026-02-03","","2026","3","50000","1","Cicilan ke-1","2026-02-03 10:03:02");
INSERT INTO pembayaran VALUES("24","TRX-202602-008","1","3136264986","2026-02-03","","2026","4","100000","1","Cicilan ke-1","2026-02-03 10:03:02");



DROP TABLE IF EXISTS pengaturan;

CREATE TABLE `pengaturan` (
  `id_pengaturan` int NOT NULL,
  `nama_sekolah` varchar(100) DEFAULT NULL,
  `alamat_sekolah` text,
  `logo` varchar(255) DEFAULT NULL,
  `nama_bendahara` varchar(100) DEFAULT NULL,
  `tahun_ajaran` varchar(20) DEFAULT '',
  `bg_login` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_pengaturan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO pengaturan VALUES("1","MI Sultan Fattah Sukosono","Jln. Kauman RT. 10 RW. 03 Sukosono Kedung Jepara 59463","logo.png","Zamaah, S.Pd.I","2025/2026","bg_login.jpg");



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
INSERT INTO pengguna VALUES("2","petugas","$2y$10$VzeZYp1DeKNE4djL6JI9Meu5vjMw30wi.R1LxXTsAtkBUFNCdOAqS","Zamaah, S.Pd.I.","petugas","2026-02-01 17:33:49","");



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



