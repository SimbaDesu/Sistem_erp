<?php
session_start();

// Cek login
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit;
}

include 'koneksi.php';

// Ambil statistik untuk dashboard
$username = $_SESSION['username'];
$role = $_SESSION['role'];

// Statistik berdasarkan role
if ($role == 'owner') {
    // Statistik Owner - Gabungan Penjual + Operator
    // HAPUS query total_produk karena tidak perlu lagi
    // $total_produk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM products WHERE seller = '$username'"));
    // $total_produk = $total_produk['total'];
    
    $pesanan_pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM custom_orders WHERE seller = '$username' AND status = 'pending'"));
    $pesanan_pending = $pesanan_pending['total'] ?? 0;
    
    $in_production = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM custom_orders WHERE seller = '$username' AND status = 'in_production'"));
    $in_production = $in_production['total'] ?? 0;
    
    $completed = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM custom_orders WHERE seller = '$username' AND status = 'completed'"));
    $completed = $completed['total'] ?? 0;
    
    // Tambahan: Active Orders (pesanan yang masih aktif/belum delivered)
    $active_orders = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT COUNT(*) as total 
        FROM custom_orders 
        WHERE seller = '$username' 
        AND status NOT IN ('delivered', 'cancelled')
    "));
    $active_orders = $active_orders['total'] ?? 0;
    
} else {
    // Statistik Pembeli
    
    // Pending approval
    $pending_approval = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT COUNT(*) as total 
        FROM custom_orders 
        WHERE buyer = '$username' 
        AND status = 'pending'
    "));
    $pending_approval = $pending_approval['total'] ?? 0;
    
    // In production
    $in_production = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT COUNT(*) as total 
        FROM custom_orders 
        WHERE buyer = '$username' 
        AND status = 'in_production'
    "));
    $in_production = $in_production['total'] ?? 0;
    
    // Completed (ready to ship tapi belum delivered)
    $completed = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT COUNT(*) as total 
        FROM custom_orders 
        WHERE buyer = '$username' 
        AND status = 'completed'
    "));
    $completed = $completed['total'] ?? 0;
    
    // Delivered (sudah terkirim)
    $delivered = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT COUNT(*) as total 
        FROM custom_orders 
        WHERE buyer = '$username' 
        AND status = 'delivered'
    "));
    $delivered = $delivered['total'] ?? 0;
    
    // Active orders (belum delivered/cancelled)
    $my_orders = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT COUNT(*) as total 
        FROM custom_orders 
        WHERE buyer = '$username' 
        AND status NOT IN ('delivered', 'cancelled')
    "));
    $my_orders = $my_orders['total'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ERP UMKM</title>
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
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 25px 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .header-left h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .header-left p {
            color: #777;
            font-size: 14px;
        }
        
        .user-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 25px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .role-badge {
            background: rgba(255,255,255,0.2);
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            text-transform: uppercase;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .stat-icon.blue { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-icon.green { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
        .stat-icon.orange { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-icon.red { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
        
        .stat-value {
            font-size: 36px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #777;
            font-size: 14px;
        }
        
        .menu-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .menu-section h2 {
            color: #333;
            font-size: 22px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .menu-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px 20px;
            border-radius: 12px;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }
        
        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        .menu-card-icon {
            font-size: 36px;
        }
        
        .menu-card-title {
            font-size: 16px;
            font-weight: 600;
        }
        
        .menu-card-desc {
            font-size: 12px;
            opacity: 0.9;
        }
        
        /* Info Banner untuk Owner */
        .info-banner {
            background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
            border-left: 4px solid #ff9800;
            padding: 20px 25px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .info-banner h3 {
            color: #e65100;
            font-size: 16px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .info-banner p {
            color: #666;
            font-size: 13px;
            line-height: 1.7;
        }
        
        .logout-section {
            text-align: center;
            margin-top: 20px;
        }
        
        .btn-logout {
            background: #e74c3c;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-logout:hover {
            background: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.4);
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <div class="header-left">
            <h1>🏢 Dashboard ERP UMKM</h1>
            <p>Selamat datang, <?php echo $username; ?>! 
            <?php 
                if ($role == 'owner') echo 'Kelola pesanan dan produksi dengan efisien';
                else echo 'Buat custom order sesuai kebutuhan Anda';
            ?>
            </p>
        </div>
        <div class="user-badge">
            👤 <?php echo $username; ?> 
            <span class="role-badge">
                <?php 
                    if ($role == 'owner') echo '👨‍💼 OWNER';
                    else echo '🛒 PEMBELI';
                ?>
            </span>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="stats-grid">
        <?php if ($role == 'owner'): ?>
            <!-- Statistik Owner -->
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-value"><?php echo $pesanan_pending; ?></div>
                        <div class="stat-label">Pesanan Baru</div>
                    </div>
                    <div class="stat-icon orange">📋</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-value"><?php echo $in_production; ?></div>
                        <div class="stat-label">Sedang Produksi</div>
                    </div>
                    <div class="stat-icon blue">🔄</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-value"><?php echo $completed; ?></div>
                        <div class="stat-label">Selesai</div>
                    </div>
                    <div class="stat-icon green">✅</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-value"><?php echo $active_orders; ?></div>
                        <div class="stat-label">Pesanan Aktif</div>
                    </div>
                    <div class="stat-icon red">📦</div>
                </div>
            </div>
            
        <?php else: ?>
            <!-- Statistik Pembeli -->
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-value"><?php echo $pending_approval; ?></div>
                        <div class="stat-label">Menunggu Approval</div>
                    </div>
                    <div class="stat-icon orange">⏳</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-value"><?php echo $in_production; ?></div>
                        <div class="stat-label">Sedang Diproduksi</div>
                    </div>
                    <div class="stat-icon blue">🔄</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-value"><?php echo $delivered; ?></div>
                        <div class="stat-label">Selesai (Terkirim)</div>
                    </div>
                    <div class="stat-icon green">✅</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-value"><?php echo $my_orders; ?></div>
                        <div class="stat-label">Pesanan Aktif</div>
                    </div>
                    <div class="stat-icon red">📦</div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    

    
    <!-- Menu Utama -->
    <div class="menu-section">
        <h2>📋 Menu Utama</h2>
        <div class="menu-grid">
            <?php if ($role == 'owner'): ?>
                <!-- Menu Owner - TANPA KELOLA PRODUK -->
                <a href="orders.php" class="menu-card">
                    <div class="menu-card-icon">📋</div>
                    <div class="menu-card-title">Pesanan Masuk</div>
                    <div class="menu-card-desc">Terima & approve pesanan</div>
                </a>
                
                <a href="setup_production.php" class="menu-card">
                    <div class="menu-card-icon">⚙️</div>
                    <div class="menu-card-title">Setup Produksi</div>
                    <div class="menu-card-desc">Pilih proses produksi</div>
                </a>
                
                <a href="update_progress.php" class="menu-card">
                    <div class="menu-card-icon">🔄</div>
                    <div class="menu-card-title">Update Progress</div>
                    <div class="menu-card-desc">Kerjakan & update status</div>
                </a>
                
                <a href="completed_production.php" class="menu-card">
                    <div class="menu-card-icon">✅</div>
                    <div class="menu-card-title">Produksi Selesai</div>
                    <div class="menu-card-desc">Ready to ship</div>
                </a>
                
                <!-- HAPUS MENU KELOLA PRODUK -->
                
            <?php else: ?>
                <!-- Menu Pembeli - TANPA KATALOG PRODUK READY STOCK -->
                <a href="custom_order.php" class="menu-card">
                    <div class="menu-card-icon">🎨</div>
                    <div class="menu-card-title">Custom Order</div>
                    <div class="menu-card-desc">Pesan produk custom</div>
                </a>
                
                <a href="my_orders.php" class="menu-card">
                    <div class="menu-card-icon">📦</div>
                    <div class="menu-card-title">Pesanan Saya</div>
                    <div class="menu-card-desc">Riwayat pesanan</div>
                </a>
                
                <a href="tracking.php" class="menu-card">
                    <div class="menu-card-icon">🔍</div>
                    <div class="menu-card-title">Tracking</div>
                    <div class="menu-card-desc">Lacak progress real-time</div>
                </a>
                
                <!-- HAPUS KATALOG PRODUK READY STOCK -->
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Logout -->
    <div class="logout-section">
        <a href="dashboard.php?logout=1" class="btn-logout" onclick="return confirm('Yakin ingin logout?')">🚪 Logout</a>
    </div>
</div>

</body>
</html>