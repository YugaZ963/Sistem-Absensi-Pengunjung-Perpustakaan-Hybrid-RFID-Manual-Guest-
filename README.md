# Sistem Absensi Pengunjung Perpustakaan Hybrid RFID, Manual, dan Guest

Aplikasi web sederhana untuk mencatat kunjungan perpustakaan dengan beberapa metode input: pencarian NIM, tap kartu RFID, dan form guest/tamu. Project ini dibuat menggunakan PHP native dan MySQL, sehingga cocok dijalankan di lingkungan lokal seperti Laragon, XAMPP, atau server PHP sejenis.

## Ringkasan

Project ini membantu petugas perpustakaan mencatat absensi pengunjung dari dua kategori utama:

- Mahasiswa, melalui input NIM manual atau UID RFID.
- Guest/tamu umum, melalui form identitas singkat.

Data kunjungan tersimpan di database dan dapat dikelola melalui panel admin. Admin dapat mengelola data mahasiswa, melakukan import Excel, pairing RFID, melihat dashboard jumlah kunjungan, memfilter rekap absensi, mengekspor laporan Excel, dan mencetak laporan.

## Fitur Utama

- Absensi mahasiswa melalui NIM.
- Absensi mahasiswa melalui RFID dengan reader mode keyboard/HID.
- Absensi guest/tamu dengan nama, alamat, dan asal kampus opsional.
- Dashboard admin dengan ringkasan kunjungan hari ini.
- Manajemen data mahasiswa: tambah, ubah, hapus, dan cari.
- Import data mahasiswa dari file Excel `.xlsx`.
- Pairing UID RFID ke data mahasiswa.
- Rekap absensi berdasarkan periode harian, mingguan, bulanan, custom, semester, atau tahun akademik.
- Filter laporan berdasarkan status pengunjung, prodi, jenjang, dan alamat.
- Export laporan ke Excel.
- Cetak laporan melalui halaman print browser.

## Teknologi

- PHP native
- MySQL atau MariaDB
- HTML, CSS, dan JavaScript
- MySQLi
- ZipArchive dan SimpleXML untuk membaca file `.xlsx`

## Struktur Folder

```text
.
|-- admin/              # Halaman dan proses panel admin
|-- app/                # Service aplikasi: auth, absensi, admin, laporan, export xlsx
|-- assets/             # CSS dan JavaScript
|-- config/             # Konfigurasi database
|-- database/           # Schema database
|-- exports/            # Folder pendukung export
|-- views/              # Folder view tambahan
`-- index.php           # Halaman absensi front desk
```

## Kebutuhan Sistem

- PHP 7.4 atau lebih baru, PHP 8.x direkomendasikan.
- MySQL atau MariaDB.
- Web server lokal seperti Laragon, XAMPP, Apache, atau Nginx.
- Ekstensi PHP:
  - `mysqli`
  - `zip`
  - `simplexml`
- Reader RFID yang dapat bekerja sebagai keyboard input atau keyboard wedge jika ingin memakai mode RFID.

## Instalasi Lokal

1. Clone repository ke folder web server.

   ```bash
   git clone https://github.com/YugaZ963/Sistem-Absensi-Pengunjung-Perpustakaan-Hybrid-RFID-Manual-Guest-.git
   ```

2. Masuk ke folder project.

   ```bash
   cd Sistem-Absensi-Pengunjung-Perpustakaan-Hybrid-RFID-Manual-Guest-
   ```

3. Buat database dengan menjalankan file schema:

   ```sql
   database/schema.sql
   ```

   Secara default schema akan membuat database:

   ```text
   db_absensi_perpus
   ```

4. Sesuaikan konfigurasi database di `config/db.php`.

   ```php
   $DB_HOST = '127.0.0.1';
   $DB_PORT = 3306;
   $DB_NAME = 'db_absensi_perpus';
   $DB_USER = 'root';
   $DB_PASS = '';
   ```

5. Jalankan web server, lalu buka halaman utama:

   ```text
   http://localhost/absensi-perpus-1/
   ```

   Jika nama folder berbeda, sesuaikan URL dengan nama folder project di web server.

## Akun Admin Default

Saat panel admin pertama kali dibuka dan tabel admin masih kosong, aplikasi akan membuat akun default:

```text
Username: admin
Password: admin123
```

Untuk penggunaan nyata, segera ganti password default tersebut. Saat ini project belum menyediakan halaman ubah password, jadi perubahan dapat dilakukan langsung melalui database atau dengan menambahkan fitur manajemen akun admin.

## Cara Menggunakan

### Front Desk Absensi

Buka halaman utama aplikasi. Tersedia dua mode:

- Pencarian mahasiswa: input NIM secara manual atau tap kartu RFID.
- Guest/tamu: isi nama, alamat, dan asal kampus jika ada.

Untuk RFID, pastikan reader mengirim UID sebagai input keyboard. Aplikasi akan menjaga fokus pada field RFID dan mencoba menyimpan otomatis setelah UID terbaca.

### Panel Admin

Buka:

```text
http://localhost/absensi-perpus-1/admin/login.php
```

Setelah login, admin dapat mengakses:

- Dashboard
- Data mahasiswa
- Import Excel
- Pairing RFID
- Rekap absensi

## Format Import Excel

Import mahasiswa menggunakan file `.xlsx`. File `.xls` dapat dipilih dari form, tetapi parser bawaan project ini belum mendukung `.xls`, sehingga file sebaiknya disimpan ulang sebagai `.xlsx`.

Kolom wajib untuk template mahasiswa:

```text
NIM | Nama | Prodi | Jenjang | Alamat | Semester | Tahun Akademik
```

Catatan:

- Jika NIM sudah ada, data mahasiswa akan diperbarui.
- Jika NIM belum ada, data mahasiswa akan ditambahkan.
- Header tidak harus berada di baris pertama selama masih berada dalam 30 baris awal.
- RFID otomatis dibuat saat import dengan format `RFID-4HURUFNAMA-NNN`.
- Untuk UID kartu RFID fisik yang sebenarnya, gunakan menu Pairing RFID.

## Laporan dan Rekap

Menu rekap absensi mendukung filter:

- Harian
- Mingguan
- Bulanan
- Rentang tanggal custom
- Semester
- Tahun akademik
- Status pengunjung: semua, mahasiswa, atau guest
- Prodi
- Jenjang
- Alamat

Hasil rekap dapat diekspor ke Excel atau dibuka pada halaman print untuk dicetak maupun disimpan sebagai PDF melalui fitur browser.

## Catatan Pengembangan

Beberapa hal yang perlu diperhatikan jika project ini akan dikembangkan lebih lanjut:

- Password admin default sebaiknya diganti dan fitur ubah password perlu ditambahkan.
- Konfigurasi database masih ditulis langsung di `config/db.php`; untuk deployment publik sebaiknya dipindahkan ke environment variable.
- Import Excel saat ini fokus pada `.xlsx`.
- Tidak ada dependency manager seperti Composer, sehingga project relatif mudah dijalankan tetapi struktur autoloading masih manual.
- Validasi dan audit keamanan perlu diperkuat jika aplikasi digunakan di jaringan publik.

## Lisensi

Belum ada lisensi yang ditentukan. Tambahkan file `LICENSE` jika project akan dipublikasikan atau digunakan ulang oleh orang lain.
