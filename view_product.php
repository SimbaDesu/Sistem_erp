<?php
session_start();
include 'koneksi.php';

// Cek login
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

// Handle Delete
if (isset($_GET['delete']) && $_SESSION['role'] == 'penjual') {
    $id = mysqli_real_escape_string($conn, $_GET['delete']);
    $delete_query = "DELETE FROM products WHERE id = '$id' AND seller = '{$_SESSION['username']}'";
    
    if (mysqli_query($conn, $delete_query)) {
        echo "<script>alert('✅ Produk berhasil dihapus!'); window.location='view_product.php';</script>";
    } else {
        echo "<script>alert('❌ Gagal menghapus produk!');</script>";
    }
}

// Ambil data produk
$query = mysqli_query($conn, "SELECT * FROM products ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Produk - ERP UMKM</title>
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
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .user-info {
            background: rgba(255,255,255,0.95);
            color: #333;
            padding: 15px 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .user-info-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .btn-dashboard {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-dashboard:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .header {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        h2 {
            color: #333;
            font-size: 28px;
        }
        
        .btn-add {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.2s ease;
            display: inline-block;
        }
        
        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .table-container {
            background: white;
            border-radius: 15px;
            overflow-x: auto;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        table { 
            width: 100%; 
            border-collapse: collapse; 
        }
        
        th, td { 
            padding: 15px; 
            text-align: left; 
        }
        
        th { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; 
            font-weight: 600;
            text-transform: uppercase;
            font-size: 13px;
            letter-spacing: 0.5px;
        }
        
        td {
            border-bottom: 1px solid #f0f0f0;
            color: #555;
        }
        
        tr:hover td {
            background: #f8f9ff;
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        .id-col {
            width: 60px;
            text-align: center;
            font-weight: 600;
            color: #667eea;
        }
        
        .name-col {
            font-weight: 600;
            color: #333;
            min-width: 150px;
        }
        
        .desc-col {
            color: #666;
            font-size: 13px;
            max-width: 300px;
            line-height: 1.5;
        }
        
        .price-col {
            color: #27ae60;
            font-weight: 600;
            min-width: 120px;
        }
        
        .stock-col {
            text-align: center;
            font-weight: 600;
            min-width: 80px;
        }
        
        .seller-col {
            color: #777;
            font-size: 14px;
            min-width: 100px;
        }
        
        .action-col {
            min-width: 180px;
            text-align: center;
        }
        
        .btn-action {
            padding: 8px 15px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-block;
            margin: 2px;
        }
        
        .btn-edit {
            background: #3498db;
            color: white;
        }
        
        .btn-edit:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        
        .btn-delete {
            background: #e74c3c;
            color: white;
        }
        
        .btn-delete:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        .empty-desc {
            font-style: italic;
            color: #ccc;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="user-info">
        <div class="user-info-left">
            <span>👤 Login sebagai: <strong><?php echo $_SESSION['username']; ?></strong> (<?php echo ucfirst($_SESSION['role']); ?>)</span>
        </div>
        <a href="dashboard.php" class="btn-dashboard">🏠 Kembali ke Dashboard</a>
    </div>
    
    <div class="header">
        <h2><?php echo $_SESSION['role'] == 'penjual' ? '📦 Kelola Produk' : '🛍️ Katalog Produk'; ?></h2>
        <?php if ($_SESSION['role'] == 'penjual'): ?>
            <a href="add_product.php" class="btn-add">+ Tambah Produk Baru</a>
        <?php endif; ?>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th class="id-col">ID</th>
                    <th>Nama Produk</th>
                    <th>Deskripsi</th>
                    <th>Harga (Rp)</th>
                    <th>Stok</th>
                    <th>Penjual</th>
                    <?php if ($_SESSION['role'] == 'penjual'): ?>
                        <th class="action-col">Aksi</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                if (mysqli_num_rows($query) > 0) {
                    while ($row = mysqli_fetch_assoc($query)) {
                        $deskripsi = !empty($row['deskripsi']) ? htmlspecialchars($row['deskripsi']) : '<span class="empty-desc">Tidak ada deskripsi</span>';
                        
                        echo "<tr>
                                <td class='id-col'>{$row['id']}</td>
                                <td class='name-col'>{$row['name']}</td>
                                <td class='desc-col'>{$deskripsi}</td>
                                <td class='price-col'>Rp " . number_format($row['harga'], 0, ',', '.') . "</td>
                                <td class='stock-col'>{$row['stok']} pcs</td>
                                <td class='seller-col'>{$row['seller']}</td>";
                        
                        // Tombol aksi hanya untuk penjual dan hanya untuk produk miliknya
                        if ($_SESSION['role'] == 'penjual' && $row['seller'] == $_SESSION['username']) {
                            echo "<td class='action-col'>
                                    <a href='edit_product.php?id={$row['id']}' class='btn-action btn-edit'>✏️ Edit</a>
                                    <a href='view_product.php?delete={$row['id']}' class='btn-action btn-delete' onclick='return confirm(\"Yakin ingin menghapus produk ini?\")'>🗑️ Hapus</a>
                                  </td>";
                        } elseif ($_SESSION['role'] == 'penjual') {
                            echo "<td class='action-col'>-</td>";
                        }
                        
                        echo "</tr>";
                    }
                } else {
                    $colspan = $_SESSION['role'] == 'penjual' ? 7 : 6;
                    echo "<tr>
                            <td colspan='$colspan' class='empty-state'>
                                <div class='empty-state-icon'>📦</div>
                                <h3>Belum ada produk</h3>
                                <p>Klik tombol 'Tambah Produk Baru' untuk menambahkan produk pertama Anda</p>
                            </td>
                          </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>