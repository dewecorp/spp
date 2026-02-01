DROP TABLE IF EXISTS jenis_bayar;

CREATE TABLE `jenis_bayar` (
  `id_jenis_bayar` int NOT NULL AUTO_INCREMENT,
  `nama_pembayaran` varchar(50) NOT NULL,
  `nominal` int NOT NULL,
  `tahun_ajaran` varchar(20) NOT NULL,
  PRIMARY KEY (`id_jenis_bayar`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO jenis_bayar VALUES("1","Iuran Ekstrakurikuler","10000","2025/2026");
INSERT INTO jenis_bayar VALUES("2","LKS","150000","2025/2026");



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



DROP TABLE IF EXISTS pembayaran;

CREATE TABLE `pembayaran` (
  `id_pembayaran` int NOT NULL AUTO_INCREMENT,
  `id_petugas` int NOT NULL,
  `nisn` varchar(20) NOT NULL,
  `tgl_bayar` date NOT NULL,
  `bulan_bayar` varchar(20) DEFAULT NULL,
  `tahun_bayar` varchar(20) DEFAULT NULL,
  `id_jenis_bayar` int NOT NULL,
  `jumlah_bayar` int NOT NULL,
  `ket` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pembayaran`),
  KEY `id_petugas` (`id_petugas`),
  KEY `nisn` (`nisn`),
  KEY `id_jenis_bayar` (`id_jenis_bayar`),
  CONSTRAINT `pembayaran_ibfk_1` FOREIGN KEY (`id_petugas`) REFERENCES `pengguna` (`id_pengguna`),
  CONSTRAINT `pembayaran_ibfk_2` FOREIGN KEY (`nisn`) REFERENCES `siswa` (`nisn`) ON DELETE CASCADE,
  CONSTRAINT `pembayaran_ibfk_3` FOREIGN KEY (`id_jenis_bayar`) REFERENCES `jenis_bayar` (`id_jenis_bayar`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;




DROP TABLE IF EXISTS pengaturan;

CREATE TABLE `pengaturan` (
  `id_pengaturan` int NOT NULL,
  `nama_sekolah` varchar(100) DEFAULT NULL,
  `alamat_sekolah` text,
  `logo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_pengaturan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO pengaturan VALUES("1","MI Sultan Fattah Sukosono","Sukosono, Jepara","");



DROP TABLE IF EXISTS pengguna;

CREATE TABLE `pengguna` (
  `id_pengguna` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `role` enum('admin','petugas') NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pengguna`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO pengguna VALUES("1","admin","$2y$10$8CDkDNvsxuABPt9TxvEM/OxgfztNhqVW0nWgU0J252sBmXDEQ7z7O","Administrator","admin","2026-01-31 17:14:42");



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

INSERT INTO siswa VALUES("987654321","321542","SUDARLIM","1","Sukosono Kedung Jepara","086969696969");



