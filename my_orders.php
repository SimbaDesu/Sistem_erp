<?php
session_start();
include 'koneksi.php';

// Cek login dan role
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'pembeli') {
    header("Location: index.php");
    exit;
}

$buyer = $_SESSION['username'];

// Ambil semua pesanan pembeli
$orders_query = mysqli_query($conn, "
    SELECT * FROM custom_orders 
    WHERE buyer = '$buyer' 
    ORDER BY 
        CASE status
            WHEN 'pending' THEN 1
            WHEN 'approved' THEN 2
            WHEN 'in_production' THEN 3
            WHEN 'completed' THEN 4
            WHEN 'delivered' THEN 5
            WHEN 'cancelled' THEN 6
        END,
        created_at DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Saya - ERP UMKM</title>
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
        
        .header-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.2s;
            display: inline-block;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
        }
        
        .stat-label {
            color: #777;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .orders-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .order-card {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        
        .order-card:hover {
            border-color: #667eea;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px dashed #e0e0e0;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .order-number {
            font-size: 18px;
            font-weight: bold;
            color: #667eea;
        }
        
        .order-date {
            color: #777;
            font-size: 13px;
        }
        
        .status-badge {
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-approved {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-in_production {
            background: #cce5ff;
            color: #004085;
        }
        
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        
        .status-delivered {
            background: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .order-body {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .info-label {
            font-size: 12px;
            color: #777;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        .info-value {
            font-size: 15px;
            color: #333;
            font-weight: 500;
        }
        
        .specs-box {
            background: #f8f9ff;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        
        .specs-title {
            font-weight: 600;
            color: #555;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .specs-content {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .order-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        .btn-action {
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-block;
        }
        
        .btn-track {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-track:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-icon {
            font-size: 64px;
            margin-bottom: 15px;
        }
        
        .price-highlight {
            color: #27ae60;
            font-weight: bold;
            font-size: 18px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2>📦 Pesanan Saya</h2>
        <div class="header-actions">
            <a href="custom_order.php" class="btn btn-primary">➕ Buat Pesanan Baru</a>
            <a href="dashboard.php" class="btn btn-secondary">🏠 Dashboard</a>
        </div>
    </div>
    
    <?php
    // Hitung statistik
    $total_orders = mysqli_num_rows($orders_query);
    mysqli_data_seek($orders_query, 0); // Reset pointer
    
    $pending = 0;
    $in_production = 0;
    $completed = 0;
    
    while ($row = mysqli_fetch_assoc($orders_query)) {
        if ($row['status'] == 'pending') $pending++;
        if ($row['status'] == 'in_production') $in_production++;
        if ($row['status'] == 'completed' || $row['status'] == 'delivered') $completed++;
    }
    mysqli_data_seek($orders_query, 0); // Reset lagi
    ?>
    
    <div class="stats">
        <div class="stat-card">
            <div class="stat-value"><?php echo $total_orders; ?></div>
            <div class="stat-label">Total Pesanan</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $pending; ?></div>
            <div class="stat-label">Menunggu Approval</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $in_production; ?></div>
            <div class="stat-label">Sedang Produksi</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $completed; ?></div>
            <div class="stat-label">Selesai</div>
        </div>
    </div>
    
    <div class="orders-container">
        <?php if (mysqli_num_rows($orders_query) > 0): ?>
            <?php while ($order = mysqli_fetch_assoc($orders_query)): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <div class="order-number">Order #<?php echo $order['order_number']; ?></div>
                            <div class="order-date">📅 <?php echo date('d M Y H:i', strtotime($order['created_at'])); ?></div>
                        </div>
                        <span class="status-badge status-<?php echo $order['status']; ?>">
                            <?php 
                                $status_text = [
                                    'pending' => '⏳ Menunggu Approval',
                                    'approved' => '✅ Approved',
                                    'in_production' => '🔄 Sedang Produksi',
                                    'completed' => '✅ Selesai',
                                    'delivered' => '📦 Terkirim',
                                    'cancelled' => '❌ Dibatalkan'
                                ];
                                echo $status_text[$order['status']];
                            ?>
                        </span>
                    </div>
                    
                    <div class="order-body">
                        <div class="info-item">
                            <span class="info-label">🏭 Penjual</span>
                            <span class="info-value"><?php echo $order['seller']; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">📦 Produk</span>
                            <span class="info-value"><?php echo $order['product_name']; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">🔢 Jumlah</span>
                            <span class="info-value"><?php echo $order['quantity']; ?> unit</span>
                        </div>
                        
                        <?php if ($order['total_price']): ?>
                            <div class="info-item">
                                <span class="info-label">💰 Total Harga</span>
                                <span class="info-value price-highlight">Rp <?php echo number_format($order['total_price'], 0, ',', '.'); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($order['estimated_completion_date']): ?>
                            <div class="info-item">
                                <span class="info-label">⏰ Target Selesai</span>
                                <span class="info-value"><?php echo date('d M Y', strtotime($order['estimated_completion_date'])); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($order['specifications'])): ?>
                        <div class="specs-box">
                            <div class="specs-title">📝 Spesifikasi:</div>
                            <div class="specs-content"><?php echo nl2br(htmlspecialchars($order['specifications'])); ?></div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($order['status'] == 'cancelled' && !empty($order['notes'])): ?>
                        <div class="specs-box" style="background: #f8d7da; border-left: 4px solid #dc3545;">
                            <div class="specs-title" style="color: #721c24;">❌ Alasan Penolakan:</div>
                            <div class="specs-content" style="color: #721c24;"><?php echo nl2br(htmlspecialchars($order['notes'])); ?></div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="order-actions">
                        <?php if ($order['status'] == 'in_production' || $order['status'] == 'approved'): ?>
                            <a href="tracking.php?order_id=<?php echo $order['id']; ?>" class="btn-action btn-track">
                                📍 Tracking Produksi
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($order['drawing_filename']): ?>
                            <a href="download_drawing.php?order_id=<?php echo $order['id']; ?>" class="btn-action" style="background: #3498db; color: white;">
                                📎 Lihat Gambar Teknik
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">📭</div>
                <h3>Belum Ada Pesanan</h3>
                <p>Klik tombol "Buat Pesanan Baru" untuk membuat pesanan custom pertama Anda</p>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>