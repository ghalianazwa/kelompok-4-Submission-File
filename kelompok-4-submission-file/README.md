Mini-LMS - Course Service



LAPORAN PENGUJIAN SUBMISSION FILE SERVICE
1. Pendahuluan
Submission File Service merupakan salah satu layanan dalam sistem Mini LMS Terdistribusi yang bertanggung jawab untuk menangani pengumpulan tugas oleh mahasiswa. Layanan ini menyediakan fitur untuk mengunggah file tugas, melihat daftar submission, serta menghapus submission.

Pengujian dilakukan untuk memastikan bahwa Submission File Service dapat berjalan dengan baik dan dapat diakses melalui API Gateway.

2. Tujuan Pengujian
Tujuan pengujian ini adalah:
1. Memastikan Submission File Service dapat berjalan dengan normal.
2. Memastikan endpoint API dapat diakses melalui API Gateway.
3. Memastikan proses pengunggahan file tugas berjalan dengan baik.
4. Memastikan data submission dapat ditampilkan.
5. Memastikan data submission dapat dihapus.
6. Memastikan integrasi dengan layanan lain yang dibutuhkan berjalan dengan baik.

3. Konfigurasi Pengujian
Komponen	Keterangan
Nama layanan	Submission File Service
Port layanan	8105
API Gateway	Port 8101
Metode pengujian	Postman
Format upload	Multipart/form-data
Autentikasi	Bearer Token
Bahasa/backend	PHP

Alamat API Gateway:

http://127.0.0.1:8101

Alamat Submission Service:

http://127.0.0.1:8105

4. Hasil Pengujian
4.1 Pengujian Health Check
- Metode: GET
- Endpoint: /api/submission/health
- Tujuan: Memastikan layanan Submission File aktif dan dapat diakses.
Hasil respons:
{
  "success": true,
  "data": {
    "service": "submission",
    "status": "healthy"
  }
}


Hasil: Berhasil. Layanan memberikan status healthy.
4.2 Pengujian Melihat Daftar Submission
- Metode: GET
- Endpoint: /api/submission/submissions?assignment_id=asg-001
- Tujuan: Mengambil daftar submission berdasarkan ID tugas.
Hasil pengujian menunjukkan bahwa API berhasil mengembalikan daftar submission beserta jumlah data yang tersedia.

Hasil: Berhasil. Endpoint dapat menampilkan data submission.
4.3 Pengujian Upload File Submission
- Metode: POST
- Endpoint: /api/submission/submissions
- Body: form-data
- Parameter:
  - assignment_id: asg-001
  - file: Kelompok 5 PAPER.pdf
- Autentikasi: Bearer Token
Hasil respons menunjukkan bahwa file berhasil diunggah dan sistem membuat data submission baru dengan ID sub-002.

Informasi hasil pengunggahan:
Atribut	Hasil
Submission ID	sub-002
Assignment ID	asg-001
User ID	usr-mhs01
Nama file	Kelompok 5 PAPER.pdf
Status	Berhasil diunggah

Hasil: Berhasil. File dapat diunggah dan data submission berhasil dibuat.
4.4 Pengujian Verifikasi Hasil Upload
- Metode: GET
- Endpoint: /api/submission/submissions?assignment_id=asg-001
- Tujuan: Memastikan data yang baru diunggah tersimpan dan dapat ditampilkan.
Hasil pengujian menunjukkan bahwa submission dengan ID sub-002 muncul dalam daftar dan jumlah submission menjadi 1.

Hasil: Berhasil. Data submission yang diunggah dapat ditemukan melalui endpoint daftar submission.
4.5 Pengujian Menghapus Submission
- Metode: DELETE
- Endpoint: /api/submission/submissions/sub-002
- Tujuan: Menghapus data submission berdasarkan ID.
Hasil respons:
{
  "success": true,
  "data": {
    "message": "Submission berhasil dihapus",
    "id": "sub-002"
  }
}


Hasil: Berhasil. Sistem memberikan konfirmasi bahwa submission berhasil dihapus.
4.6 Pengujian Verifikasi Setelah Penghapusan
- Metode: GET
- Endpoint: /api/submission/submissions?assignment_id=asg-001
- Tujuan: Memastikan data submission yang dihapus sudah tidak muncul dalam daftar.
Hasil pengujian menunjukkan:
{
  "submissions": [],
  "total": 0
}


Hasil: Berhasil. Data submission dengan ID sub-002 sudah tidak ditemukan dalam daftar.

5. Rekapitulasi Hasil Pengujian
Berdasarkan seluruh pengujian yang telah dilakukan, diperoleh hasil sebagai berikut:
No.	Skenario Pengujian	Hasil	Status
1	Health Check	Layanan berstatus healthy	Berhasil
2	Melihat daftar submission	Data berhasil ditampilkan	Berhasil
3	Upload file tugas	File berhasil diunggah	Berhasil
4	Verifikasi hasil upload	Data submission ditemukan	Berhasil
5	Menghapus submission	Data berhasil dihapus	Berhasil
6	Verifikasi setelah penghapusan	Data tidak lagi ditemukan	Berhasil

6 dari 6 skenario pengujian berhasil.

6. Kesimpulan
Berdasarkan hasil pengujian yang telah dilakukan, Submission File Service berhasil dijalankan dan dapat diakses melalui API Gateway pada port 8101. Layanan berjalan pada port 8105 dan seluruh enam skenario pengujian menggunakan Postman berhasil dilakukan.

Fitur utama Submission File Service, yaitu pemeriksaan kesehatan layanan, pengambilan daftar submission, pengunggahan file tugas, serta penghapusan submission telah berjalan sesuai hasil yang diharapkan.

Dengan demikian, Submission File Service dinyatakan berhasil diuji dan siap digunakan sebagai bagian dari sistem Mini LMS Terdistribusi.