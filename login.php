<?php
// 1. Mulai session di baris paling atas
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

// 2. Cek apakah user sudah login, jika ya lempar ke dashboard
if (isset($_SESSION['admin_logged_in'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

// 3. Logika pemrosesan form login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        $stmt = $koneksi->prepare("SELECT id, password, nama_lengkap FROM admin WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            
            if (password_verify($password, $row['password'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $row['id'];
                $_SESSION['nama_lengkap'] = $row['nama_lengkap'];
                
                header("Location: dashboard.php");
                exit;
            }
        }
        $error = "Username atau password salah!";
    } else {
        $error = "Semua field wajib diisi!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Blog Dashboard</title>
    <link rel="icon" type="image/x-icon" href="assets/img/logo-apiblog.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <style>
    body {
        font-family: 'Poppins', sans-serif;
        background: #f4f6f9;
        height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0;
    }

    .card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        width: 100%;
        max-width: 400px;
        background-color: #ffffff;
    }

    .card-header {
        background-color: transparent;
        border-bottom: none;
        text-align: center;
        padding-top: 30px;
        padding-bottom: 20px;
    }

    .card-header img {
        width: 150px;
        margin: 0 auto;
        display: block;
    }

    .btn-primary {
        background: #4e73df;
        border: none;
        font-weight: 600;
    }

    .btn-primary:hover {
        background: #3c61c3;
    }

    .form-group label {
        font-weight: 500;
        color: #5a5a5a;
    }
    </style>
</head>

<body>
    <div class="card p-4">
        <div class="card-header">
            <img src="assets/img/apiblog-logos.png" alt="APIBlog Logo" class="align-self-center">
        </div>

        <?php if($error): ?>
        <div class="alert alert-danger font-weight-normal text-center" style="font-size: 0.9rem; border-radius: 8px;">
            <?= htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" class="form-control rounded-pill px-4"
                    placeholder="Masukkan username" required autocomplete="off">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" class="form-control rounded-pill px-4"
                    placeholder="Masukkan password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block mt-4 rounded-pill py-2">Log In</button>
        </form>
    </div>
</body>

</html>