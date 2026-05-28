<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

require_once '../config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Tangkap parameter jika React mengirimkan query pencarian atau kategori
    // Contoh dari React: api/artikel.php?search=react&kategori=1
    $search      = $_GET['search'] ?? '';
    $id_kategori = $_GET['kategori'] ?? '';

    $query = "SELECT artikel.*, kategori.nama_kategori 
              FROM artikel 
              LEFT JOIN kategori ON artikel.id_kategori = kategori.id 
              WHERE 1=1";

    if (!empty($search)) {
        $query .= " AND (artikel.judul LIKE '%" . $koneksi->real_escape_string($search) . "%' OR artikel.isi LIKE '%" . $koneksi->real_escape_string($search) . "%')";
    }
    if (!empty($id_kategori)) {
        $query .= " AND artikel.id_kategori = " . (int)$id_kategori;
    }

    $query .= " ORDER BY artikel.id DESC";
    $result = $koneksi->query($query);
    
    $list_artikel = [];
    while ($row = $result->fetch_assoc()) {
        $row['gambar_url'] = "http://" . $_SERVER['HTTP_HOST'] . "/apiblog/uploads/" . $row['gambar'];
        $list_artikel[] = $row;
    }
    
    http_response_code(200);
    echo json_encode([
        "status" => true,
        "message" => "Berhasil mengambil data artikel.",
        "data" => $list_artikel
    ]);
} else {
    http_response_code(405);
    echo json_encode(["status" => false, "message" => "Method Tidak Diizinkan."]);
}