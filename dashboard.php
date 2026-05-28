<?php
require_once 'config.php';
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

// Ambil semua data kategori untuk dropdown filter dan form modal
$list_kategori = $koneksi->query("SELECT * FROM kategori ORDER BY nama_kategori ASC");

// Tangkap parameter Filter, Search, dan Halaman Aktif
$search     = $_GET['search'] ?? '';
$filter_kat = $_GET['filter_kategori'] ?? '';
$halaman    = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
if ($halaman < 1) $halaman = 1;

// ==========================================
// CONFIGURATION CONFIG: 3 BARIS X 3 CARD = 9 DATA
// ==========================================
$limit = 9; 
$offset = ($halaman - 1) * $limit;

// Base query string untuk pencarian dan filter
$where_clause = " WHERE 1=1";
if (!empty($search)) {
    $where_clause .= " AND (artikel.judul LIKE '%" . $koneksi->real_escape_string($search) . "%' OR artikel.isi LIKE '%" . $koneksi->real_escape_string($search) . "%')";
}
if (!empty($filter_kat)) {
    $where_clause .= " AND artikel.id_kategori = " . (int)$filter_kat;
}

// 1. Hitung total seluruh data yang sesuai filter
$query_total = "SELECT COUNT(*) AS total FROM artikel" . $where_clause;
$total_result = $koneksi->query($query_total);
$total_data = $total_result->fetch_assoc()['total'];
$total_halaman = ceil($total_data / $limit);

// 2. Ambil data artikel dengan batasan LIMIT dan OFFSET
$query_str = "SELECT artikel.*, kategori.nama_kategori 
              FROM artikel 
              LEFT JOIN kategori ON artikel.id_kategori = kategori.id " 
              . $where_clause . 
              " ORDER BY artikel.id DESC LIMIT $limit OFFSET $offset";
$result = $koneksi->query($query_str);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Card Grid View</title>
    <link rel="icon" type="image/x-icon" href="assets/img/logo-apiblog.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
    body {
        background-color: #f8f9fc;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .navbar {
        background: #4e73df;
    }

    .search-card {
        border: none;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        border-radius: 10px;
    }

    /* Styling Card Artikel Modern */
    .blog-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
        background: #fff;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .blog-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(58, 59, 69, 0.15);
    }

    .blog-card .card-img-top {
        height: 180px;
        object-fit: cover;
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
    }

    .blog-card .card-body {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .blog-title {
        font-size: 1.15rem;
        font-weight: 700;
        color: #2e3b55;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        height: 3.2rem;
    }

    .blog-text {
        font-size: 0.9rem;
        color: #6c757d;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    /* Pagination */
    .pagination .page-item.active .page-link {
        background-color: #4e73df;
        border-color: #4e73df;
    }

    .pagination .page-link {
        color: #4e73df;
        border-radius: 5px;
        margin: 0 2px;
    }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand navbar-dark shadow mb-4">
        <div class="container">
            <a class="navbar-brand font-weight-bold" href="#">Blog Manager</a>
            <ul class="navbar-nav ml-auto align-items-center">
                <li class="nav-item active mr-3 text-white d-none d-sm-block">
                    <i class="fas fa-user-circle mr-1"></i> Hai, <?= htmlspecialchars($_SESSION['nama_lengkap']); ?>
                </li>
                <li class="nav-item">
                    <a class="btn btn-sm btn-danger px-3 shadow-sm rounded-pill" href="logout.php">Keluar</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container mb-5">
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between mb-4">
            <h1 class="h3 mb-2 mb-sm-0 text-gray-800 font-weight-bold">Dashboard Artikel</h1>
            <button class="btn btn-primary btn-sm shadow-sm px-3 rounded-pill" onclick="bukaModalTambah()">
                Tambah Artikel
            </button>
        </div>

        <div class="card search-card mb-4">
            <div class="card-body">
                <form method="GET" class="form-row align-items-center">
                    <div class="col-md-5 my-1">
                        <input type="text" name="search" class="form-control form-control-sm"
                            placeholder="Cari judul atau isi artikel..." value="<?= htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-4 my-1">
                        <select name="filter_kategori" class="form-control form-control-sm">
                            <option value="">-- Semua Kategori --</option>
                            <?php 
                            $list_kategori->data_seek(0);
                            while($kat = $list_kategori->fetch_assoc()): 
                            ?>
                            <option value="<?= $kat['id']; ?>" <?= $filter_kat == $kat['id'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($kat['nama_kategori']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3 my-1">
                        <button type="submit" class="btn btn-dark btn-sm btn-block rounded-pill"><i
                                class="fas fa-search mr-1"></i>
                            Filter Data</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row">
            <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
            <div class="col-xl-4 col-md-6 col-sm-12 mb-4" id="row-<?= $row['id']; ?>">
                <div class="card blog-card">
                    <img src="uploads/<?= htmlspecialchars($row['gambar']); ?>" class="card-img-top" alt="Cover">
                    <div class="card-body">
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span
                                    class="badge badge-secondary px-2 py-1 small"><?= htmlspecialchars($row['nama_kategori'] ?? 'Umum'); ?></span>
                                <small class="text-muted"><i
                                        class="far fa-calendar-alt mr-1"></i><?= date('d M Y', strtotime($row['tanggal'])); ?></small>
                            </div>
                            <h5 class="blog-title" title="<?= htmlspecialchars($row['judul']); ?>">
                                <?= htmlspecialchars($row['judul']); ?></h5>
                            <p class="blog-text mt-2"><?= htmlspecialchars(strip_tags($row['isi'])); ?></p>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                            <span class="small text-muted"><i class="far fa-user mr-1"></i>By:
                                <b><?= htmlspecialchars($row['pemosting']); ?></b></span>
                            <div>
                                <button class="btn btn-info btn-sm shadow-sm"
                                    onclick="bukaModalDetail(<?= $row['id']; ?>)" title="Lihat Detail"><i
                                        class="fas fa-eye"></i></button>
                                <button class="btn btn-warning btn-sm shadow-sm"
                                    onclick="bukaModalEdit(<?= $row['id']; ?>)" title="Edit"><i
                                        class="fas fa-edit"></i></button>
                                <button class="btn btn-danger btn-sm shadow-sm"
                                    onclick="hapusArtikel(<?= $row['id']; ?>)" title="Hapus"><i
                                        class="fas fa-trash"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
            <?php else: ?>
            <div class="col-12 text-center py-5">
                <div class="text-muted"><i class="fas fa-folder-open fa-3x mb-3"></i><br>Artikel tidak ditemukan atau
                    data masih kosong.</div>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($total_halaman > 1): ?>
        <div
            class="d-flex justify-content-between align-items-center flex-column flex-sm-row mt-4 card search-card p-3">
            <div class="text-muted small mb-2 mb-sm-0">
                Menampilkan halaman <b><?= $halaman; ?></b> dari <b><?= $total_halaman; ?></b> halaman (Total:
                <?= $total_data; ?> artikel).
            </div>
            <nav aria-label="Page navigation">
                <ul class="pagination pagination-sm m-0">
                    <li class="page-item <?= ($halaman <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link"
                            href="?halaman=<?= $halaman - 1; ?>&search=<?= urlencode($search); ?>&filter_kategori=<?= $filter_kat; ?>"><i
                                class="fas fa-chevron-left"></i></a>
                    </li>
                    <?php for ($i = 1; $i <= $total_halaman; $i++): ?>
                    <li class="page-item <?= ($halaman == $i) ? 'active' : ''; ?>">
                        <a class="page-link"
                            href="?halaman=<?= $i; ?>&search=<?= urlencode($search); ?>&filter_kategori=<?= $filter_kat; ?>"><?= $i; ?></a>
                    </li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($halaman >= $total_halaman) ? 'disabled' : ''; ?>">
                        <a class="page-link"
                            href="?halaman=<?= $halaman + 1; ?>&search=<?= urlencode($search); ?>&filter_kategori=<?= $filter_kat; ?>"><i
                                class="fas fa-chevron-right"></i></a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>

    <div class="modal fade" id="modalDetailArtikel" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content" style="border-radius: 12px; border: none;">
                <div class="modal-header border-0 bg-light">
                    <h5 class="modal-title font-weight-bold text-dark"><i
                            class="fas fa-book-open mr-2 text-primary"></i>Pratinjau Artikel</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-4">
                    <img id="detailGambar" src="" class="img-fluid rounded mb-3 w-100"
                        style="max-height: 380px; object-fit: cover; box-shadow: 0 4px 10px rgba(0,0,0,0.08);">

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span id="detailKategori" class="badge badge-primary px-3 py-2 text-uppercase font-weight-bold"
                            style="letter-spacing: 0.5px;"></span>
                        <small class="text-muted font-weight-bold"><i class="far fa-calendar-alt mr-1"></i> <span
                                id="detailTanggal"></span></small>
                    </div>

                    <h3 id="detailJudul" class="font-weight-bold text-dark mb-2" style="line-height: 1.3;"></h3>
                    <p class="small text-muted mb-3"><i class="far fa-user mr-1"></i> Ditulis oleh: <b
                            id="detailPemosting" class="text-dark"></b></p>
                    <hr class="my-3">

                    <div id="detailIsi"
                        style="line-height: 1.8; color: #4a5568; font-size: 1.05rem; white-space: pre-line; text-align: justify;">
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-secondary px-4" style="border-radius: 8px;"
                        data-dismiss="modal">Tutup Bacaan</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalArtikel" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content" style="border-radius: 12px; border: none;">
                <div class="modal-header border-0 bg-light">
                    <h5 class="modal-title font-weight-bold" id="modalTitle">Tambah Artikel</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="formArtikel" enctype="multipart/form-data">
                    <div class="modal-body p-4">
                        <input type="hidden" name="id" id="artikel_id">
                        <div class="form-group">
                            <label class="font-weight-bold text-dark">Judul Artikel</label>
                            <input type="text" name="judul" id="judul" class="form-control" style="border-radius: 8px;"
                                required placeholder="Tulis judul...">
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold text-dark">Kategori</label>
                            <select name="id_kategori" id="id_kategori" class="form-control" style="border-radius: 8px;"
                                required>
                                <option value="">-- Pilih Kategori --</option>
                                <?php 
                                $list_kategori->data_seek(0);
                                while($kat = $list_kategori->fetch_assoc()): 
                                ?>
                                <option value="<?= $kat['id']; ?>"><?= htmlspecialchars($kat['nama_kategori']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold text-dark">Isi Artikel</label>
                            <textarea name="isi" id="isi" class="form-control" rows="5" style="border-radius: 8px;"
                                required placeholder="Tulis konten..."></textarea>
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold text-dark">Gambar Cover</label>
                            <div id="preview-container" class="mb-2 d-none">
                                <img id="preview-gambar" src="" width="150" class="img-thumbnail rounded">
                            </div>
                            <input type="file" name="gambar" id="gambar" class="form-control-file" accept="image/*">
                            <small class="text-muted">Format: JPG, PNG, WEBP. Max 3MB.</small>
                        </div>
                    </div>
                    <div class="modal-footer border-0 bg-light">
                        <button type="button" class="btn btn-secondary" style="border-radius: 8px;"
                            data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" style="border-radius: 8px;" id="btnSimpan">Simpan
                            Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    // FUNGSI BARU: Mengambil detail via AJAX & Menampilkan Modal Detail
    function bukaModalDetail(id) {
        $.ajax({
            url: 'proses-aksi.php?aksi=get_detail&id=' + id,
            type: 'GET',
            dataType: 'JSON',
            success: function(data) {
                // Ambil teks nama kategori langsung dari element card DOM agar efisien
                let namaKategori = $('#row-' + id).find('.badge').text();

                // Format penanggalan JavaScript Indonesia
                let dateObj = new Date(data.tanggal);
                let opsiTanggal = {
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric'
                };
                let tanggalFormat = dateObj.toLocaleDateString('id-ID', opsiTanggal);

                // Suntik data ke elemen HTML di dalam Modal Detail
                $('#detailGambar').attr('src', 'uploads/' + data.gambar);
                $('#detailKategori').text(namaKategori ? namaKategori : 'Umum');
                $('#detailTanggal').text(tanggalFormat);
                $('#detailJudul').text(data.judul);
                $('#detailPemosting').text(data.pemosting);
                $('#detailIsi').text(data.isi); // Menggunakan .text() untuk keamanan XSS injection

                // Munculkan Modal Detail
                $('#modalDetailArtikel').modal('show');
            },
            error: function() {
                alert('Gagal memuat data detail artikel dari server backend.');
            }
        });
    }

    function bukaModalTambah() {
        $('#formArtikel')[0].reset();
        $('#artikel_id').val('');
        $('#modalTitle').text('Tambah Artikel Baru');
        $('#preview-container').addClass('d-none');
        $('#gambar').attr('required', true);
        $('#modalArtikel').modal('show');
    }

    function bukaModalEdit(id) {
        $('#formArtikel')[0].reset();
        $('#gambar').removeAttr('required');

        $.ajax({
            url: 'proses-aksi.php?aksi=get_detail&id=' + id,
            type: 'GET',
            dataType: 'JSON',
            success: function(data) {
                $('#artikel_id').val(data.id);
                $('#judul').val(data.judul);
                $('#id_kategori').val(data.id_kategori);
                $('#isi').val(data.isi);

                $('#preview-gambar').attr('src', 'uploads/' + data.gambar);
                $('#preview-container').removeClass('d-none');

                $('#modalTitle').text('Edit Artikel');
                $('#modalArtikel').modal('show');
            }
        });
    }

    $('#formArtikel').on('submit', function(e) {
        e.preventDefault();
        let formData = new FormData(this);
        $.ajax({
            url: 'proses-aksi.php?aksi=simpan',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'JSON',
            success: function(response) {
                if (response.status) {
                    alert(response.message);
                    $('#modalArtikel').modal('hide');
                    location.reload();
                } else {
                    alert(response.message);
                }
            }
        });
    });

    function hapusArtikel(id) {
        if (confirm('Apakah anda yakin ingin menghapus artikel ini?')) {
            $.ajax({
                url: 'proses-aksi.php?aksi=hapus&id=' + id,
                type: 'GET',
                dataType: 'JSON',
                success: function(response) {
                    if (response.status) {
                        alert(response.message);
                        $('#row-' + id).fadeOut(500, function() {
                            $(this).remove();
                        });
                    } else {
                        alert(response.message);
                    }
                }
            });
        }
    }
    </script>
</body>

</html>