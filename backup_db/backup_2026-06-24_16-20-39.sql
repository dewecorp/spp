DROP TABLE IF EXISTS jenis_bayar;

CREATE TABLE `jenis_bayar` (
  `id_jenis_bayar` int NOT NULL AUTO_INCREMENT,
  `nama_pembayaran` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nominal` int NOT NULL,
  `tipe_bayar` enum('Bulanan','Cicilan') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Bulanan',
  `kali_cicilan` int DEFAULT '0',
  `tagihan_kelas` text COLLATE utf8mb4_unicode_ci,
  `status` enum('Aktif','Tidak Aktif') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Aktif',
  PRIMARY KEY (`id_jenis_bayar`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO jenis_bayar VALUES("1","Iuran Ekstrakurikuler","10000","Bulanan","0","1,2,3,4,5,6","Aktif");
INSERT INTO jenis_bayar VALUES("2","LKS 1-3","260000","Cicilan","2","1,2,3","Aktif");
INSERT INTO jenis_bayar VALUES("3","Biaya Ujian 2026","900000","Cicilan","5","6","Aktif");
INSERT INTO jenis_bayar VALUES("4","Iuran Rekreasi","250000","Cicilan","10","6","Aktif");
INSERT INTO jenis_bayar VALUES("5","LKS 4-6","332000","Cicilan","2","4,5,6","Aktif");



DROP TABLE IF EXISTS kelas;

CREATE TABLE `kelas` (
  `id_kelas` int NOT NULL AUTO_INCREMENT,
  `nama_kelas` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id_kelas`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `jenis_aktivitas` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_log`),
  KEY `id_pengguna` (`id_pengguna`),
  CONSTRAINT `log_aktivitas_ibfk_1` FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id_pengguna`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=129 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO log_aktivitas VALUES("127","1","Login","Login berhasil","2026-06-23 20:01:26");
INSERT INTO log_aktivitas VALUES("128","1","Delete","Menghapus file backup: backup_2026-02-03_10-11-14.sql","2026-06-24 13:25:31");



DROP TABLE IF EXISTS pembayaran;

CREATE TABLE `pembayaran` (
  `id_pembayaran` int NOT NULL AUTO_INCREMENT,
  `no_transaksi` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_petugas` int NOT NULL,
  `nisn` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tgl_bayar` date NOT NULL,
  `bulan_bayar` text COLLATE utf8mb4_unicode_ci,
  `tahun_bayar` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_jenis_bayar` int NOT NULL,
  `jumlah_bayar` int NOT NULL,
  `cicilan_ke` int DEFAULT '0',
  `ket` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pembayaran`),
  KEY `id_petugas` (`id_petugas`),
  KEY `nisn` (`nisn`),
  KEY `id_jenis_bayar` (`id_jenis_bayar`),
  CONSTRAINT `pembayaran_ibfk_1` FOREIGN KEY (`id_petugas`) REFERENCES `pengguna` (`id_pengguna`),
  CONSTRAINT `pembayaran_ibfk_2` FOREIGN KEY (`nisn`) REFERENCES `siswa` (`nisn`) ON DELETE CASCADE,
  CONSTRAINT `pembayaran_ibfk_3` FOREIGN KEY (`id_jenis_bayar`) REFERENCES `jenis_bayar` (`id_jenis_bayar`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO pembayaran VALUES("9","TRX-20260202104253-993","1","3146588936","2026-02-02","","2026","3","100000","1","Cicilan ke-1","2026-02-02 10:59:08");
INSERT INTO pembayaran VALUES("10","TRX-20260202104253-993","1","3146588936","2026-02-02","November, Desember, Januari, Februari","2026","1","40000","0","Lunas (Bulanan) - November, Desember, Januari, Februari","2026-02-02 10:59:08");
INSERT INTO pembayaran VALUES("11","TRX-20260202104253-993","1","3146588936","2026-02-02","","2026","2","50000","1","Cicilan ke-1","2026-02-02 10:59:32");
INSERT INTO pembayaran VALUES("12","TRX-20260202111328-152","1","3132163433","2026-02-02","","2026","4","100000","1","Cicilan ke-1","2026-02-02 11:13:28");
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
INSERT INTO pembayaran VALUES("25","TRX-202604-001","1","3139561428","2026-04-29","","2026","2","100000","1","Cicilan ke-1","2026-04-29 11:07:36");
INSERT INTO pembayaran VALUES("26","TRX-202604-002","1","3184602457","2026-04-29","","2026","2","100000","1","Cicilan ke-1","2026-04-29 11:10:43");
INSERT INTO pembayaran VALUES("27","TRX-202605-001","1","3135628625","2026-05-04","Juli, Agustus, September, Oktober, November, Desember, Januari","2026","1","70000","0","Lunas (Bulanan) - Juli, Agustus, September, Oktober, November, Desember, Januari","2026-05-04 10:59:02");
INSERT INTO pembayaran VALUES("28","TRX-202606-001","1","3184602457","2026-06-17","","2026","2","100000","1","Cicilan ke-1","2026-06-17 13:40:56");
INSERT INTO pembayaran VALUES("29","TRX-202606-002","1","3184275775","2026-06-17","","2026","2","100000","1","Cicilan ke-1","2026-06-17 13:42:18");
INSERT INTO pembayaran VALUES("30","TRX-202606-003","1","3180229036","2026-06-17","","2026","2","200000","1","Cicilan ke-1","2026-06-17 13:54:48");
INSERT INTO pembayaran VALUES("31","TRX-202606-004","1","3184602457","2026-06-17","","2026","2","50000","1","Cicilan ke-1","2026-06-17 14:56:55");
INSERT INTO pembayaran VALUES("32","TRX-202606-005","1","3184602457","2026-06-17","","2026","2","50000","1","Cicilan ke-1","2026-06-17 15:06:04");



DROP TABLE IF EXISTS pengaturan;

CREATE TABLE `pengaturan` (
  `id_pengaturan` int NOT NULL,
  `nama_sekolah` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alamat_sekolah` text COLLATE utf8mb4_unicode_ci,
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nama_bendahara` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tahun_ajaran` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `bg_login` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_pengaturan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO pengaturan VALUES("1","MI Sultan Fattah Sukosono","Jln. Kauman RT. 10 RW. 03 Sukosono Kedung Jepara 59463","logo.png","Zamaah, S.Pd.I","2025/2026","bg_login.jpg");



DROP TABLE IF EXISTS pengguna;

CREATE TABLE `pengguna` (
  `id_pengguna` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_lengkap` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','petugas') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `foto` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_pengguna`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO pengguna VALUES("1","admin","$2y$10$8CDkDNvsxuABPt9TxvEM/OxgfztNhqVW0nWgU0J252sBmXDEQ7z7O","Administrator","admin","2026-01-31 17:14:42","69ffc6404e46a.png");
INSERT INTO pengguna VALUES("2","petugas","$2y$10$VzeZYp1DeKNE4djL6JI9Meu5vjMw30wi.R1LxXTsAtkBUFNCdOAqS","Zamaah, S.Pd.I.","petugas","2026-02-01 17:33:49","");



DROP TABLE IF EXISTS siswa;

CREATE TABLE `siswa` (
  `nisn` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_kelas` int NOT NULL,
  `alamat` text COLLATE utf8mb4_unicode_ci,
  `no_telp` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jenis_kelamin` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '-',
  `tempat_lahir` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '-',
  `tgl_lahir` date DEFAULT '1900-01-01',
  `nama_wali` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '-',
  PRIMARY KEY (`nisn`),
  KEY `id_kelas` (`id_kelas`),
  CONSTRAINT `siswa_ibfk_1` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO siswa VALUES("0134799480","MUHAMMAD MIRZA RAMADHAN","5","-","","L","-","1900-01-01","-");
INSERT INTO siswa VALUES("0136511810","BATARI ARSYAVIDYA AHMAD","4","-","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("0137840437","Diyah Ayu Prawesti","6","","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("0141467581","SAILIN ANJA SAFARA","5","-","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("0141948658","AHMAD ARSYIL ROHAN","5","-","","L","-","1900-01-01","-");
INSERT INTO siswa VALUES("0143035500","MUHAMMAD ROMY PRAYADINATA","5","-","","L","-","1900-01-01","-");
INSERT INTO siswa VALUES("0144317238","FARID LINTANG ARDIANSYAH","5","-","","L","-","1900-01-01","-");
INSERT INTO siswa VALUES("0144565120","FURJAH SABDA KENCANA","5","-","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("0145967375","MUHAMMAD NAILUL FAIZ","5","-","","L","-","1900-01-01","-");
INSERT INTO siswa VALUES("0146016714","MUHAMMAD BAYU ALHABSYI HIKMAWAN","5","-","","L","-","1900-01-01","-");
INSERT INTO siswa VALUES("0146901301","AHMAD ARJUNNAJATA ILAMAULA","5","-","","L","-","1900-01-01","-");
INSERT INTO siswa VALUES("0153488311","ANGGITA CITRA ANINDITA","5","-","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("0154732385","MAFATIHUL KHOIR","4","-","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("0155498460","NOR ALVIAN AZ ZAHRAN","4","-","","L","-","1900-01-01","-");
INSERT INTO siswa VALUES("0156746004","RIZQIYATUL MUNAYA","5","-","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("0158273255","AZKA ARDA YOGA","4","-","","L","-","1900-01-01","-");
INSERT INTO siswa VALUES("0159328720","HAYU FALISHA LAIL","4","-","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("0159344416","ANDHARA NAURA LATIFAH","4","-","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("0163291739","ARINI NIHAYATUS SHOLIHAH","3","-","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("0166159155","ANNISA MAZROATUL AHSANI","3","-","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("0166304991","SRI AISYAH AILANI ARKA","3","-","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("0167091943","WULANDARI","4","-","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("0177572457","Nikeisya Sherin Aqila","2","-","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("0178946336","AHMAD HASAN","3","-","","L","-","1900-01-01","-");
INSERT INTO siswa VALUES("3130180823","Muhammad Daris Alfurqon Aqim","6","","","L","-","1900-01-01","-");
INSERT INTO siswa VALUES("3130235731","Rizal Arahman","5","-","","L","-","1900-01-01","-");
INSERT INTO siswa VALUES("3130250384","Muhammad Elga Saputra","6","","","L","-","1900-01-01","-");
INSERT INTO siswa VALUES("3131634863","Siti Afifah Nauvalyn Fikriyah","6","","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("3132163433","Bilqis Fahiya Rifda","6","","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("3132469215","SEKAR NURI MAULIDA","5","-","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("3133041280","Lidia Aura Citra","6","","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("3133372371","Fabregas Alviano","6","","","L","-","1900-01-01","-");
INSERT INTO siswa VALUES("3135628625","Aqilah Khoirurrosyadah","6","","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("3136264986","Putra Sadewa Saifunnawas","6","","","L","-","1900-01-01","-");
INSERT INTO siswa VALUES("3137207114","Rizquna Halalan Thoyyiba","6","","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("3137563185","Amrina Rosyada","6","","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("3137847985","Dzakira Talita Azzahra","6","","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("3138275600","Dewi Khuzaimah Annisa","6","","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("3138636681","ASTI DAYINTA ELOK WIGUNA","4","-","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("3139561428","Siti Mei Listiana","6","","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("3140219560","MUHAMMAD SAMSUL AD\'N","5","-","","L","-","1900-01-01","-");
INSERT INTO siswa VALUES("3140702123","Muhammad Egi Ferdiansyah","6","","","L","-","1900-01-01","-");
INSERT INTO siswa VALUES("3140721776","EVA FANI VEBRIANI","5","-","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("3140985027","NAF\'AN KAIS AHMAD","5","-","","L","-","1900-01-01","-");
INSERT INTO siswa VALUES("3141710676","Najwah Fadia Amalia Fitri","6","","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("3146510193","Indana Zulfa","6","","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("3146588936","ADIBA NUHA AZZAHRA","6","","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("3146697836","ZANETA AQILA ANDIENAMIRA","5","-","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("3148398924","MUHAMMAD FAHRI ARDIANSYAH","5","-","","L","-","1900-01-01","-");
INSERT INTO siswa VALUES("3149297726","Muhammad Agung Susilo Sugiono","6","","","L","-","1900-01-01","-");
INSERT INTO siswa VALUES("3151044603","VEBY STEVANIE","3","-","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("3151306061","MUHAMMAD ALGA NOVA","4","-","","L","-","1900-01-01","-");
INSERT INTO siswa VALUES("3153353973","BILQIS ANINDITA HUDA","5","-","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("3154268018","NILAM CAHYA RANI","4","-","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("3155089697","RATU VIOLA ZAZKIA ANNISA","4","-","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("3156182504","FADLIL A\'LA","4","-","","L","-","1900-01-01","-");
INSERT INTO siswa VALUES("3157310312","AHMAD DAFA SAPUTRA","4","-","","L","-","1900-01-01","-");
INSERT INTO siswa VALUES("3160331953","MUHAMMAD ULIN NAWAF ABDULLAH","3","-","","L","-","1900-01-01","-");
INSERT INTO siswa VALUES("3162535127","AKBAR AL HANAN","3","-","","L","-","1900-01-01","-");
INSERT INTO siswa VALUES("3162918924","SYIFANA AWWALIYA","3","-","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("3163104174","JOVAN PRATAMA","3","-","","L","-","1900-01-01","-");
INSERT INTO siswa VALUES("3163117591","REVA RAMADHANI","3","-","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("3163851089","KAYLA ANANDA SELFIA","3","-","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("3164665566","ALZAM MAUZA ALINDRA","3","-","","L","-","1900-01-01","-");
INSERT INTO siswa VALUES("3164754146","QIYANU JABAR MASA\'ID","4","-","","L","-","1900-01-01","-");
INSERT INTO siswa VALUES("3165088631","RAFA RASYIQUL UMAR","3","-","","L","-","1900-01-01","-");
INSERT INTO siswa VALUES("3165720620","CITRA DWI LESTARI","3","-","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("3166299389","TITANIA WICENZA SETYA","3","-","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("3168502077","MUHAMMAD ARDIAN ERFA SAPUTRA","3","-","","L","-","1900-01-01","-");
INSERT INTO siswa VALUES("3168585316","LAILATUZZAHRA","3","-","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("3168692801","RIZQY AWWALUN PUTRA AHMAD","3","-","","L","-","1900-01-01","-");
INSERT INTO siswa VALUES("3169048580","MUHAMMAD NUR YUSUF","3","-","","L","-","1900-01-01","-");
INSERT INTO siswa VALUES("3169440189","ALMA NAFI\'A","3","-","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("3169539885","AURORA DIYATUL FILARDI","3","-","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("3170498904","HANIA NATASHA ADINDA AZZAHRA","2","-","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("3171065604","HAFIZ AL RASHAAD","2","-","","L","-","1900-01-01","-");
INSERT INTO siswa VALUES("3171432750","NUR ERLINA ARDIRA","2","-","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("3171957808","MAHIR RIZQI ABDILLAH","2","-","","L","-","1900-01-01","-");
INSERT INTO siswa VALUES("3172404776","MAUWAFIQ KHOIRUL FAJAR","1","-","","L","-","1900-01-01","-");
INSERT INTO siswa VALUES("3174187068","AFWAN SETYO MANGGALA PUTRA","2","-","","L","-","1900-01-01","-");
INSERT INTO siswa VALUES("3174413024","SRI HANDAYANI","2","-","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("3174857338","AKHMAD SONY BINTANG ADITYA","2","-","","L","-","1900-01-01","-");
INSERT INTO siswa VALUES("3175059536","ZALFA KHAIRUNNISA","2","-","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("3175785706","ALMAQVIRA AULIA SHALIHA","2","-","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("3175823960","Muhammad Nadaril Saputra","2","-","","L","-","1900-01-01","-");
INSERT INTO siswa VALUES("3176934840","RAHMA SHEKHA ADINDA PUTRI","2","-","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("3177478000","NUR ALIFATUL ZAHIRA","3","-","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("3177681680","KAYLA PUTRI AMALIA","1","-","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("3178039730","AIRA DWI MAULIDA","2","-","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("3179401623","ABIZARD ALTAN MUTTAQI","2","-","","L","-","1900-01-01","-");
INSERT INTO siswa VALUES("3180027333","MUHAMMAD SABRIEL RAYYAN","2","-","","L","-","1900-01-01","-");
INSERT INTO siswa VALUES("3180229036","ADHITAMA ELVAN SYAHREZA","1","-","","L","-","1900-01-01","-");
INSERT INTO siswa VALUES("3181022161","ALIF SAPUTRA","2","-","","L","-","1900-01-01","-");
INSERT INTO siswa VALUES("3181233911","AURA LATISHA AQUINA","2","-","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("3182266699","MUHAMMAD RIZQI MAULANA","2","-","","L","-","1900-01-01","-");
INSERT INTO siswa VALUES("3182355082","ARFAN MIYAZ ALINDRA","1","-","","L","-","1900-01-01","-");
INSERT INTO siswa VALUES("3182663303","AHMAD MANUTHO MUHAMMAD","1","-","","L","-","1900-01-01","-");
INSERT INTO siswa VALUES("3183882033","HIBAT ALMALIK","1","-","","L","-","1900-01-01","-");
INSERT INTO siswa VALUES("3184245017","DHIRA QALESYA","1","-","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("3184275775","ABIZAR HABIBILLAH","1","-","","L","-","1900-01-01","-");
INSERT INTO siswa VALUES("3184514039","TUSAMMA SALSABILA","1","-","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("3184602457","ABDULLAH HASAN","1","-","","L","Jepara","1900-01-01","");
INSERT INTO siswa VALUES("3186829907","RHEVA PUTRI RAMADHANI","1","-","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("3187039124","MUHAMMAD AZKA DHIYA’UL HAQ","2","-","","L","-","1900-01-01","-");
INSERT INTO siswa VALUES("3187106516","MUHAMMAD MAHDI","2","-","","L","-","1900-01-01","-");
INSERT INTO siswa VALUES("3187786956","MAULANA SYAFIQ RAMADHAN","2","-","","L","-","1900-01-01","-");
INSERT INTO siswa VALUES("3188013385","SALMA SHAFIRA RAYYANA","1","-","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("3189975601","MUHAMMAD HAFIZ MAULANA","2","-","","L","-","1900-01-01","-");
INSERT INTO siswa VALUES("3190992049","LAILATUL JANNATU AZZA","1","-","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("3194274202","JIHAN FADHILLAH","1","-","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("3194980092","AIRA ZAHWA SAFIRA","1","-","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("3195153075","DELISA ALYA SAFIQNA","1","-","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("3195813730","DIAN AIRA","1","-","","P","-","1900-01-01","-");
INSERT INTO siswa VALUES("3198116081","NORREIN NABIHA","1","-","","P","-","1900-01-01","-");



