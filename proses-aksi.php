<?php
require_once 'config.php';

// Atur header agar merespons dalam format JSON
header('Content-Type: application/json; charset=UTF-8');

// Proteksi Keamanan: Pastikan sesi admin sudah login aktif
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(419);
    echo json_encode([
        'status' => false,
        'message' => 'Sesi Anda telah habis atau Anda tidak memiliki akses!'
    ]);
    exit;
}

// Tangkap parameter aksi dari URL query string (misal: proses-aksi.php?aksi=simpan)
$aksi = $_GET['aksi'] ?? '';

// =========================================================================
// 1. AMBIL DETAIL DATA (Untuk dilempar ke field form Modal Edit)
// =========================================================================
if ($aksi === 'get_detail') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($id <= 0) {
        echo json_encode(['status' => false, 'message' => 'ID data tidak valid.']);
        exit;
    }

    $stmt = $koneksi->prepare("SELECT * FROM artikel WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    if ($data) {
        echo json_encode($data);
    } else {
        http_response_code(404);
        echo json_encode(['status' => false, 'message' => 'Data artikel tidak ditemukan.']);
    }
    exit;
}

// =========================================================================
// 2. SIMPAN DATA (Bisa bertindak sebagai INSERT baru atau UPDATE data lama)
// =========================================================================
if ($aksi === 'simpan') {
    // Tangkap data dari form modal submit via AJAX FormData
    $id          = $_POST['id'] ?? ''; // Jika kosong = Insert, Jika ada angka = Update
    $judul       = trim($_POST['judul'] ?? '');
    $id_kategori = $_POST['id_kategori'] ?? null;
    $isi         = trim($_POST['isi'] ?? '');
    $pemosting   = $_SESSION['nama_lengkap']; // Diambil dari sesi admin yang login

    // Validasi input form minimal
    if (empty($judul) || empty($isi) || empty($id_kategori)) {
        echo json_encode([
            'status' => false, 
            'message' => 'Gagal menyimpan! Judul, Kategori, dan Isi Artikel wajib diisi.'
        ]);
        exit;
    }

    // Identifikasi gambar lama apabila sedang melakukan proses edit (Update)
    $gambar_lama = '';
    if (!empty($id)) {
        $stmt_cek = $koneksi->prepare("SELECT gambar FROM artikel WHERE id = ?");
        $stmt_cek->bind_param("i", $id);
        $stmt_cek->execute();
        $res_cek = $stmt_cek->get_result()->fetch_assoc();
        $gambar_lama = $res_cek['gambar'] ?? '';
    }

    // --- Logika Validasi & Upload Berkas Gambar ---
    $nama_file_baru = $gambar_lama; // Jadikan default file jika user tidak ganti gambar saat edit

    if (isset($_FILES['gambar']['name']) && $_FILES['gambar']['error'] === 0) {
        $nama_file   = $_FILES['gambar']['name'];
        $tmp_file    = $_FILES['gambar']['tmp_name'];
        $ukuran_file = $_FILES['gambar']['size'];
        
        $ekstensi_valid = ['jpg', 'jpeg', 'png', 'webp'];
        $ekstensi_file  = strtolower(pathinfo($nama_file, PATHINFO_EXTENSION));

        // Validasi Ekstensi Gambar
        if (!in_array($ekstensi_file, $ekstensi_valid)) {
            echo json_encode([
                'status' => false, 
                'message' => 'Format gambar salah! Hanya diizinkan: JPG, JPEG, PNG, atau WEBP.'
            ]);
            exit;
        }

        // Validasi Ukuran Berkas Maksimal 3 Megabyte (3 * 1024 * 1024)
        if ($ukuran_file > 3145728) {
            echo json_encode([
                'status' => false, 
                'message' => 'Ukuran berkas terlalu besar! Maksimal batas berkas adalah 3MB.'
            ]);
            exit;
        }

        // Generate nama berkas acak unik baru agar tidak terjadi bentrok nama file di folder uploads
        $nama_file_baru = uniqid('img_', true) . '.' . $ekstensi_file;
        
        // Eksekusi pemindahan file dari temporer komputer ke direktori proyek Laragon
        if (move_uploaded_file($tmp_file, 'uploads/' . $nama_file_baru)) {
            // Hapus gambar lama dari penyimpanan server jika ini adalah aksi Update data
            if (!empty($gambar_lama) && file_exists('uploads/' . $gambar_lama)) {
                unlink('uploads/' . $gambar_lama);
            }
        } else {
            echo json_encode([
                'status' => false, 
                'message' => 'Gagal memproses pengunggahan berkas gambar ke server penyimpanan.'
            ]);
            exit;
        }
    }

    // --- Eksekusi Query Database ---
    if (empty($id)) {
        // PROSES: TAMBAH DATA BARU (INSERT)
        if (empty($nama_file_baru)) {
            echo json_encode([
                'status' => false, 
                'message' => 'Gambar cover artikel wajib diunggah untuk postingan baru!'
            ]);
            exit;
        }

        $stmt = $koneksi->prepare("INSERT INTO artikel (id_kategori, judul, isi, gambar, pemosting) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $id_kategori, $judul, $isi, $nama_file_baru, $pemosting);
        $pesan_sukses = 'Artikel baru Anda berhasil dipublikasikan!';
    } else {
        // PROSES: PERBARUI DATA LAMA (UPDATE)
        $stmt = $koneksi->prepare("UPDATE artikel SET id_kategori = ?, judul = ?, isi = ?, gambar = ? WHERE id = ?");
        $stmt->bind_param("isssi", $id_kategori, $judul, $isi, $nama_file_baru, $id);
        $pesan_sukses = 'Perubahan data artikel berhasil diperbarui!';
    }

    if ($stmt->execute()) {
        echo json_encode([
            'status' => true, 
            'message' => $pesan_sukses
        ]);
    } else {
        echo json_encode([
            'status' => false, 
            'message' => 'Sistem gagal menyimpan perubahan data ke database.'
        ]);
    }
    exit;
}

// =========================================================================
// 3. HAPUS DATA ARTIKEL
// =========================================================================
if ($aksi === 'hapus') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($id <= 0) {
        echo json_encode(['status' => false, 'message' => 'ID target tidak valid.']);
        exit;
    }

    // Cari tahu nama file gambarnya terlebih dahulu agar bisa dihapus dari storage
    $stmt_cari = $koneksi->prepare("SELECT gambar FROM artikel WHERE id = ?");
    $stmt_cari->bind_param("i", $id);
    $stmt_cari->execute();
    $row_artikel = $stmt_cari->get_result()->fetch_assoc();

    if ($row_artikel) {
        $nama_gambar = $row_artikel['gambar'];
        
        // Hapus file fisik gambar dari dalam folder uploads lokal Laragon jika filenya ada
        if (!empty($nama_gambar) && file_exists('uploads/' . $nama_gambar)) {
            unlink('uploads/' . $nama_gambar);
        }

        // Hapus record row data dari tabel database
        $stmt_hapus = $koneksi->prepare("DELETE FROM artikel WHERE id = ?");
        $stmt_hapus->bind_param("i", $id);
        
        if ($stmt_hapus->execute()) {
            echo json_encode([
                'status' => true, 
                'message' => 'Artikel beserta gambar cover berhasil dihapus secara permanen.'
            ]);
        } else {
            echo json_encode([
                'status' => false, 
                'message' => 'Gagal menghapus baris data dari database.'
            ]);
        }
    } else {
        echo json_encode([
            'status' => false, 
            'message' => 'Data tidak ditemukan atau sudah dihapus sebelumnya.'
        ]);
    }
    exit;
}

// Jika ada request masuk tanpa parameter aksi yang jelas/cocok
http_response_code(400);
echo json_encode([
    'status' => false, 
    'message' => 'Permintaan penanganan parameter aksi ilegal/tidak dikenal.'
]);
exit;