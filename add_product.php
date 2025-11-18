<?php
session_start();
include 'koneksi.php';

// Cek login
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'penjual') {
    header("Location: index.php");
    exit;
}

// Simpan produk
if (isset($_POST['submit'])) {
    // Ambil data dari form dengan validasi
    $nama_produk = mysqli_real_escape_string($conn, $_POST['name']);
    $harga = mysqli_real_escape_string($conn, $_POST['harga']);
    $stok = mysqli_real_escape_string($conn, $_POST['stok']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $seller = mysqli_real_escape_string($conn, $_SESSION['username']);

    // Query sesuai dengan struktur database: id, name, harga, stok, deskripsi, seller
    $query = "INSERT INTO products (name, harga, stok, deskripsi, seller) VALUES ('$nama_produk', '$harga', '$stok', '$deskripsi', '$seller')";
    
    if (mysqli_query($conn, $query)) {
        echo "<script>alert('✅ Produk berhasil ditambahkan!'); window.location='view_product.php';</script>";
    } else {
        echo "<script>alert('❌ Error: " . mysqli_error($conn) . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Produk - ERP UMKM</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .form-container { 
            width: 100%;
            max-width: 500px;
        }
        
        .form-box { 
            background: white; 
            padding: 40px; 
            border-radius: 15px; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.2); 
        }
        
        h2 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
            text-align: center;
        }
        
        .subtitle {
            color: #777;
            margin-bottom: 30px;
            font-size: 14px;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
            font-size: 14px;
        }
        
        input { 
            width: 100%; 
            padding: 12px 15px; 
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: inherit;
        }
        
        textarea {
            width: 100%; 
            padding: 12px 15px; 
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: inherit;
            resize: vertical;
            min-height: 80px;
        }
        
        input:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 13px;
            pointer-events: none;
        }
        
        button { 
            width: 100%;
            margin-top: 10px;
            padding: 14px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        button:hover { 
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: white;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: opacity 0.3s ease;
            text-align: center;
            width: 100%;
        }
        
        .back-link:hover {
            opacity: 0.8;
        }
        
        .user-badge {
            background: rgba(255,255,255,0.9);
            padding: 10px 20px;
            border-radius: 25px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
            color: #555;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .user-badge strong {
            color: #667eea;
        }
    </style>
</head>
<body>

<div class="form-container">
    <div class="user-badge">
        👤 Login sebagai: <strong><?php echo $_SESSION['username']; ?></strong>
    </div>
    
    <div class="form-box">
        <h2>📦 Tambah Produk</h2>
        <p class="subtitle">Lengkapi form di bawah untuk menambah produk baru</p>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Nama Produk</label>
                <input type="text" name="name" required placeholder="Contoh: Kemeja Batik Premium" autocomplete="off">
            </div>
            
            <div class="form-group">
                <label>Harga</label>
                <div class="input-wrapper">
                    <input type="number" name="harga" required min="0" step="0.01" placeholder="Contoh: 150000" autocomplete="off">
                    <span class="input-icon">Rp</span>
                </div>
            </div>
            
            <div class="form-group">
                <label>Stok</label>
                <div class="input-wrapper">
                    <input type="number" name="stok" required min="0" placeholder="Contoh: 50" autocomplete="off">
                    <span class="input-icon">pcs</span>
                </div>
            </div>
            
            <div class="form-group">
                <label>Deskripsi / Variasi Produk</label>
                <textarea name="deskripsi" placeholder="Contoh: Tersedia warna Merah, Biru, Hijau. Ukuran S, M, L, XL. Bahan katun premium." rows="4"></textarea>
                <small style="color: #999; font-size: 12px; display: block; margin-top: 5px;">💡 Opsional - Jelaskan variasi warna, ukuran, bahan, atau detail produk lainnya</small>
            </div>
            
            <button type="submit" name="submit">💾 Simpan Produk</button>
        </form>
    </div>
    
    <a href="view_product.php" class="back-link">← Kembali ke Daftar Produk</a>
</div>

</body>
</html>