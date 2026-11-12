# Submission File Service (Kelompok 4)

Proyek Mini LMS Terdistribusi, mata kuliah Sistem Terdistribusi, PTIK.

## Identitas anggota dan pembagian kontribusi

| Nama | NIM | 
| --- | --- |
| *(Nur Asma)* | *(240209500049)* |
| *(Magfirah Ahmad)* | *(240209500038)* |
| *(Aisyah Nurhidayati Az-zahrah)* | *(240209504003)* |
| *(Nur Salmi)* | *(240209500036)* |
| *(Nazwa Ghalia Abdillah)* | *(240209500046)* |


 
 



## Informasi service

| Item | Nilai |
| --- | --- |
| Nama service | Submission File Service |
| Port | 8105 |
| Alamat | http://127.0.0.1:8105 |
| Bahasa dan runtime | PHP (server bawaan `php -S`), cURL untuk memanggil service lain |
| Penyimpanan data | Berkas JSON di `data/submissions.json` (dibuat otomatis jika belum ada) |
| Penyimpanan berkas | Folder `uploads/` (dibuat otomatis) |

Service ini mencatat pengumpulan tugas mahasiswa: menerima unggahan berkas tugas, menyimpan metadata submission, menampilkan daftar submission per tugas, dan menghapus submission. Sebelum menyimpan, service memeriksa token ke Authentication Service dan memeriksa `assignment_id` ke Assignment Service. Setelah submission tersimpan, service mengirim notifikasi ke Notification Service.

## Persyaratan

- PHP 8.x dengan ekstensi cURL aktif (paket XAMPP sudah menyertakannya). Cek dengan `php -v` dan `php -m`.
- Postman untuk pengujian.
- Tidak perlu `npm`, Composer, maupun basis data.

Jika `php` tidak dikenali di terminal, tambahkan `C:\xampp\php` ke PATH atau panggil `C:\xampp\php\php.exe`.

## Cara menjalankan

1. Buka terminal di folder proyek ini.
2. Jalankan:

```
php -S 127.0.0.1:8105 index.php
```

3. Uji dengan `GET http://127.0.0.1:8105/health`. Hasil yang benar adalah status 200 dan `"status": "healthy"`.
4. Hentikan service dengan `Ctrl + C`.

Agar dapat diakses dari laptop lain, ganti alamat menjadi `0.0.0.0:8105` dan izinkan port 8105 pada firewall.

## Konfigurasi

Alamat service lain dibaca dari `services.json` (format sama dengan contoh pada modul). Berkas ini tidak berisi kata sandi atau token.

| Kunci | Nilai bawaan | Dipakai untuk |
| --- | --- | --- |
| `auth` | http://127.0.0.1:8102 | Memeriksa token (`POST /api/auth/validate`) |
| `assignment` | http://127.0.0.1:8104 | Memeriksa `assignment_id` (`GET /api/assignments/{id}`) |
| `notification` | http://127.0.0.1:8106 | Mengirim notifikasi (`POST /api/notifications`) |
| `course`, `submission`, `monitoring` | 8103, 8105, 8107 | Daftar bersama, tidak dipanggil langsung oleh service ini |

Jika menguji di beberapa laptop, ganti `127.0.0.1` dengan alamat IP laptop kelompok lain.

## Endpoint

Rincian request dan response ada di [API_CONTRACT.md](API_CONTRACT.md).

| Metode | Path | Token |
| --- | --- | --- |
| GET | `/health` | tidak |
| GET | `/api/submissions?assignment_id=...` | wajib |
| POST | `/api/submissions` (form-data: `assignment_id`, `file`) | wajib |
| DELETE | `/api/submissions/{id}` | wajib |

Melalui API Gateway (port 8101), path-nya menjadi `/api/submission/health` dan `/api/submission/submissions`.

## Contoh penggunaan

```
curl.exe -X POST http://127.0.0.1:8105/api/submissions -H "Authorization: Bearer <token>" -F "assignment_id=asg-001" -F "file=@laporan.pdf"

curl.exe "http://127.0.0.1:8105/api/submissions?assignment_id=asg-001" -H "Authorization: Bearer <token>"

curl.exe -X DELETE http://127.0.0.1:8105/api/submissions/sub-001 -H "Authorization: Bearer <token>"
```

Token diperoleh dengan login ke Authentication Service (`POST http://127.0.0.1:8102/api/auth/login`).

## Hubungan dengan service lain

| Service | Kelompok | Port | Cara |
| --- | --- | --- | --- |
| Authentication | 1 | 8102 | `POST /api/auth/validate` untuk setiap request terproteksi |
| Assignment | 6 | 8104 | `GET /api/assignments/{id}` (endpoint tambahan dari Kelompok 6) |
| Notification | 2 | 8106 | `POST /api/notifications` setelah submission tersimpan |
| API Gateway | 5 | 8101 | Meneruskan `/api/submission/...` ke service ini |
| Monitoring | 7 | 8107 | Memanggil `GET /health` |

Diagram ada di [docs/DIAGRAM.md](docs/DIAGRAM.md).

## Struktur folder

```
kelompok-4-submission-file/
├── README.md, API_CONTRACT.md, services.json, .gitignore
├── index.php                      Seluruh logika service
├── data/submissions.json          Penyimpanan metadata
├── uploads/                       Berkas unggahan (tidak masuk Git)
├── postman/Mini-LMS-Submission.postman_collection.json
└── docs/
    ├── TESTING.md                 Laporan pengujian
    ├── DIAGRAM.md                 Diagram hubungan service dan alur request
    └── bukti/                     Screenshot pengujian
```

## Asumsi (bukan dari modul)

1. Bentuk body notifikasi (`user_id`, `message`, `type`, `assignment_id`, `submission_id`) disusun sendiri karena kontrak Notification Service belum dicocokkan. Sesuaikan jika Kelompok 2 menetapkan format lain.
2. Respons Assignment Service diasumsikan berformat kontrak bersama (`success: true`, `data`).
3. Berkas yang diterima dibatasi pada ekstensi pdf, doc, docx, ppt, pptx, xls, xlsx, dan zip, dengan ukuran maksimal 10 MB. Batas ini keputusan kelompok karena modul tidak menetapkan angkanya.
4. `GET /api/submissions` mewajibkan parameter `assignment_id`.
5. Data submission memakai `file_path` (lokasi berkas pada server), bukan `file_url`.

## Keterbatasan implementasi yang diketahui

- Pemilik submission belum diperiksa saat `DELETE`. Pengguna mana pun yang punya token sah dapat menghapus submission berdasarkan ID.
- Kegagalan Notification Service diabaikan secara diam-diam. Respons tidak menandai apakah notifikasi terkirim.
- Kasus timeout service lain dikembalikan sebagai 503, bukan 504.
- Batas ukuran berkas efektif juga dipengaruhi `upload_max_filesize` dan `post_max_size` pada `php.ini`. Berkas yang melebihi batas PHP ditolak sebagai `upload_error` (400) atau `validation_error`, bukan `file_too_large` (413).
- Berkas yang diunggah belum dapat diunduh lewat API.
- Data disimpan di berkas JSON, belum aman untuk akses bersamaan oleh banyak request. Penulisan memakai `LOCK_EX`, tetapi pembacaan dan penulisan belum dibungkus satu kunci.
- Pengujian dilakukan lewat Postman pada satu laptop. Integrasi antar laptop dan skenario kegagalan service lain belum diuji (lihat `docs/TESTING.md`).
