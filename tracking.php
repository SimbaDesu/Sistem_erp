<?php
session_start();
include 'koneksi.php';

// Cek login dan role pembeli
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'pembeli') {
    header("Location: index.php");
    exit;
}

$buyer = $_SESSION['username'];
$selected_order = null;

// Case: Tracking specific order
if (isset($_GET['order_id'])) {
    $order_id = mysqli_real_escape_string($conn, $_GET['order_id']);
    
    // VALIDASI: Cek ownership + hitung progress
    $order_query = mysqli_query($conn, "
        SELECT co.*, 
        (SELECT COUNT(*) FROM production_schedules WHERE order_id = co.id) as total_proses,
        (SELECT COUNT(*) FROM production_schedules WHERE order_id = co.id AND status = 'completed') as proses_selesai
        FROM custom_orders co
        WHERE co.id = '$order_id' 
        AND co.buyer = '$buyer'
    ");
    
    if (mysqli_num_rows($order_query) > 0) {
        $selected_order = mysqli_fetch_assoc($order_query);
    } else {
        $_SESSION['error'] = "⚠️ Order tidak ditemukan atau Anda tidak memiliki akses!";
        header("Location: tracking.php");
        exit;
    }
}

// Handle search by order number
if (isset($_POST['search_order'])) {
    $order_number = mysqli_real_escape_string($conn, $_POST['order_number']);
    
    $search_query = mysqli_query($conn, "
        SELECT * FROM custom_orders 
        WHERE order_number = '$order_number' 
        AND buyer = '$buyer'
    ");
    
    if (mysqli_num_rows($search_query) > 0) {
        $order = mysqli_fetch_assoc($search_query);
        header("Location: tracking.php?order_id=" . $order['id']);
        exit;
    } else {
        $_SESSION['error'] = "❌ Order dengan nomor '$order_number' tidak ditemukan!";
    }
}

// Ambil semua pesanan pembeli untuk list
$orders = mysqli_query($conn, "
    SELECT co.*, 
    (SELECT COUNT(*) FROM production_schedules WHERE order_id = co.id) as total_proses,
    (SELECT COUNT(*) FROM production_schedules WHERE order_id = co.id AND status = 'completed') as proses_selesai
    FROM custom_orders co
    WHERE co.buyer = '$buyer' 
    ORDER BY co.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tracking Pesanan - ERP UMKM</title>
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
            margin-bottom: 20px;
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
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .header-left p {
            color: #777;
            font-size: 14px;
        }
        
        .btn-back {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            background: white;
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
            border-left: 4px solid #dc3545;
        }
        
        .search-box {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .search-title {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
        }
        
        .search-form {
            display: flex;
            gap: 10px;
        }
        
        .search-form input {
            flex: 1;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .search-form input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn-search {
            padding: 12px 25px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-search:hover {
            background: #5568d3;
        }
        
        .order-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .order-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: all 0.3s;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .order-card-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .order-number {
            font-size: 14px;
            font-weight: bold;
            color: #667eea;
        }
        
        .order-date {
            font-size: 11px;
            color: #999;
            margin-top: 3px;
        }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d1ecf1; color: #0c5460; }
        .status-in_production { background: #cce5ff; color: #004085; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-delivered { background: #e2e3e5; color: #383d41; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        
        .order-product {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }
        
        .order-info {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            color: #666;
            margin-bottom: 15px;
        }
        
        .progress-container {
            margin-top: 15px;
        }
        
        .progress-label {
            font-size: 12px;
            color: #777;
            margin-bottom: 5px;
            display: flex;
            justify-content: space-between;
        }
        
        .progress-bar-wrapper {
            background: #e9ecef;
            height: 8px;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            transition: width 0.3s ease;
        }
        
        /* Detail Tracking Styles */
        .tracking-detail {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .detail-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .detail-title {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }
        
        .detail-subtitle {
            font-size: 14px;
            color: #777;
            margin-top: 5px;
        }
        
        .status-large {
            padding: 10px 20px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .info-card {
            background: #f8f9ff;
            padding: 20px;
            border-radius: 10px;
        }
        
        .info-title {
            font-size: 14px;
            font-weight: 600;
            color: #667eea;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
            font-size: 13px;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            color: #777;
        }
        
        .info-value {
            font-weight: 600;
            color: #333;
        }
        
        .timeline-section {
            background: #f8f9ff;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 20px;
        }
        
        .timeline-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .overall-progress {
            background: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .progress-text {
            font-size: 14px;
            color: #777;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
        }
        
        .progress-percentage {
            font-weight: bold;
            color: #667eea;
        }
        
        .progress-bar-large {
            background: #e9ecef;
            height: 20px;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 11px;
            font-weight: 600;
            transition: width 0.5s ease;
        }
        
        .process-timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline-item {
            position: relative;
            padding-bottom: 25px;
        }
        
        .timeline-item:last-child {
            padding-bottom: 0;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -22px;
            top: 8px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            border: 3px solid white;
            box-shadow: 0 0 0 2px #667eea;
        }
        
        .timeline-item.completed::before {
            background: #28a745;
            box-shadow: 0 0 0 2px #28a745;
        }
        
        .timeline-item.in-progress::before {
            background: #667eea;
            box-shadow: 0 0 0 2px #667eea;
            animation: pulse 2s infinite;
        }
        
        .timeline-item.pending::before {
            background: #e9ecef;
            box-shadow: 0 0 0 2px #dee2e6;
        }
        
        .timeline-item::after {
            content: '';
            position: absolute;
            left: -17px;
            top: 22px;
            width: 4px;
            height: calc(100% - 10px);
            background: #e0e0e0;
        }
        
        .timeline-item:last-child::after {
            display: none;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        .timeline-content {
            background: white;
            padding: 15px;
            border-radius: 10px;
        }
        
        .timeline-content.completed {
            border-left: 4px solid #28a745;
        }
        
        .timeline-content.in-progress {
            border-left: 4px solid #667eea;
        }
        
        .timeline-content.pending {
            border-left: 4px solid #dee2e6;
        }
        
        .process-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .process-name {
            font-weight: 600;
            color: #333;
            font-size: 15px;
        }
        
        .process-status {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .process-status.completed {
            background: #d4edda;
            color: #155724;
        }
        
        .process-status.in-progress {
            background: #cce5ff;
            color: #004085;
        }
        
        .process-status.pending {
            background: #f8f9fa;
            color: #6c757d;
        }
        
        .process-desc {
            color: #777;
            font-size: 13px;
            margin-bottom: 10px;
        }
        
        .process-meta {
            display: flex;
            gap: 15px;
            font-size: 12px;
            color: #999;
        }
        
        .process-progress {
            margin-top: 10px;
        }
        
        .empty-state {
            background: white;
            padding: 60px 40px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .empty-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        .empty-text {
            color: #333;
            font-size: 18px;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .empty-subtext {
            color: #999;
            font-size: 14px;
            margin-bottom: 20px;
        }
        
        .btn-primary {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .specs-box {
            background: #fff8e1;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .specs-title {
            font-size: 14px;
            font-weight: 600;
            color: #f57c00;
            margin-bottom: 8px;
        }
        
        .specs-content {
            color: #666;
            font-size: 13px;
            line-height: 1.6;
        }
        
        @media (max-width: 768px) {
            .order-grid {
                grid-template-columns: 1fr;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <div class="header-left">
            <h1>🔍 Tracking Pesanan</h1>
            <p><?php echo $selected_order ? 'Detail tracking pesanan Anda' : 'Pilih pesanan untuk melihat detail tracking'; ?></p>
        </div>
        <a href="<?php echo $selected_order ? 'tracking.php' : 'dashboard.php'; ?>" class="btn-back">
            <?php echo $selected_order ? '← Kembali ke List' : '🏠 Dashboard'; ?>
        </a>
    </div>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert">
            <span style="font-size: 20px;">❌</span>
            <span><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
        </div>
    <?php endif; ?>
    
    <?php if (!$selected_order): ?>
        <!-- Search Box -->
        <div class="search-box">
            <div class="search-title">🔎 Cari dengan Order Number</div>
            <form method="POST" class="search-form">
                <input type="text" name="order_number" placeholder="Contoh: ORD-20250115-TEST01" required>
                <button type="submit" name="search_order" class="btn-search">Track</button>
            </form>
        </div>
        
        <!-- Order List -->
        <?php if (mysqli_num_rows($orders) > 0): ?>
            <div class="order-grid">
                <?php while ($order = mysqli_fetch_assoc($orders)): ?>
                    <?php
                    // Hitung progress dengan handling untuk completed
                    $progress = 0;
                    if ($order['status'] == 'completed' || $order['status'] == 'delivered') {
                        $progress = 100; // Force 100% untuk completed/delivered
                    } elseif (isset($order['total_proses']) && $order['total_proses'] > 0) {
                        $progress = ($order['proses_selesai'] / $order['total_proses']) * 100;
                    }
                    
                    // Status text
                    $status_text = [
                        'pending' => '⏳ Menunggu Approval',
                        'approved' => '✅ Disetujui',
                        'in_production' => '🔄 Sedang Produksi',
                        'completed' => '✅ Selesai',
                        'delivered' => '📦 Terkirim',
                        'cancelled' => '❌ Dibatalkan'
                    ];
                    ?>
                    <a href="tracking.php?order_id=<?php echo $order['id']; ?>" class="order-card">
                        <div class="order-card-header">
                            <div>
                                <div class="order-number">#<?php echo $order['order_number']; ?></div>
                                <div class="order-date"><?php echo date('d M Y H:i', strtotime($order['created_at'])); ?></div>
                            </div>
                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                <?php echo $status_text[$order['status']]; ?>
                            </span>
                        </div>
                        
                        <div class="order-product"><?php echo $order['product_name']; ?></div>
                        
                        <div class="order-info">
                            <span>📦 <?php echo $order['quantity']; ?> unit</span>
                            <span>💰 Rp <?php echo number_format($order['total_price'] ?? 0, 0, ',', '.'); ?></span>
                        </div>
                        
                        <?php if ($order['status'] == 'in_production' || $order['status'] == 'completed' || $order['status'] == 'delivered'): ?>
                            <div class="progress-container">
                                <div class="progress-label">
                                    <span>Progress Produksi</span>
                                    <span style="font-weight: bold; color: #667eea;"><?php echo round($progress); ?>%</span>
                                </div>
                                <div class="progress-bar-wrapper">
                                    <div class="progress-bar" style="width: <?php echo $progress; ?>%"></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </a>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">🔭</div>
                <div class="empty-text">Belum Ada Pesanan</div>
                <div class="empty-subtext">Buat pesanan custom terlebih dahulu untuk mulai tracking</div>
                <a href="custom_order.php" class="btn-primary">🎨 Buat Pesanan Custom</a>
            </div>
        <?php endif; ?>
        
    <?php else: ?>
        <!-- Detail Tracking -->
        <?php
        // Hitung overall progress dengan null coalescing dan handling completed
        $total_proses = $selected_order['total_proses'] ?? 0;
        $proses_selesai = $selected_order['proses_selesai'] ?? 0;
        
        // Handling khusus untuk status completed/delivered tanpa production schedules
        if ($selected_order['status'] == 'completed' || $selected_order['status'] == 'delivered') {
            $overall_progress = 100; // Force 100% jika status completed/delivered
        } elseif ($total_proses > 0) {
            $overall_progress = ($proses_selesai / $total_proses) * 100;
        } else {
            $overall_progress = 0;
        }
        
        // Ambil production schedules
        $schedules = mysqli_query($conn, "
            SELECT ps.*, pt.process_name, pt.description, pt.category
            FROM production_schedules ps
            JOIN process_types pt ON ps.process_type_id = pt.id
            WHERE ps.order_id = '{$selected_order['id']}'
            ORDER BY ps.sequence_order
        ");
        
        $status_text = [
            'pending' => '⏳ Menunggu Approval',
            'approved' => '✅ Disetujui - Belum Produksi',
            'in_production' => '🔄 Sedang Produksi',
            'completed' => '✅ Produksi Selesai',
            'delivered' => '📦 Sudah Dikirim',
            'cancelled' => '❌ Dibatalkan'
        ];
        ?>
        
        <div class="tracking-detail">
            <div class="detail-header">
                <div>
                    <div class="detail-title">Order #<?php echo $selected_order['order_number']; ?></div>
                    <div class="detail-subtitle"><?php echo $selected_order['product_name']; ?> • <?php echo $selected_order['quantity']; ?> unit</div>
                </div>
                <span class="status-large status-badge status-<?php echo $selected_order['status']; ?>">
                    <?php echo $status_text[$selected_order['status']]; ?>
                </span>
            </div>
            
            <!-- Order Info Grid -->
            <div class="info-grid">
                <div class="info-card">
                    <div class="info-title">📋 Informasi Pesanan</div>
                    <div class="info-row">
                        <span class="info-label">Penjual</span>
                        <span class="info-value"><?php echo $selected_order['seller']; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Quantity</span>
                        <span class="info-value"><?php echo $selected_order['quantity']; ?> unit</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Total Harga</span>
                        <span class="info-value">Rp <?php echo number_format($selected_order['total_price'] ?? 0, 0, ',', '.'); ?></span>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-title">⏱️ Timeline</div>
                    <div class="info-row">
                        <span class="info-label">Order Dibuat</span>
                        <span class="info-value"><?php echo date('d M Y H:i', strtotime($selected_order['created_at'])); ?></span>
                    </div>
                    <?php if ($selected_order['approved_at']): ?>
                        <div class="info-row">
                            <span class="info-label">Disetujui</span>
                            <span class="info-value"><?php echo date('d M Y H:i', strtotime($selected_order['approved_at'])); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($selected_order['estimated_completion_date']): ?>
                        <div class="info-row">
                            <span class="info-label">Target Selesai</span>
                            <span class="info-value"><?php echo date('d M Y', strtotime($selected_order['estimated_completion_date'])); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Specifications -->
            <?php if (!empty($selected_order['specifications'])): ?>
                <div class="specs-box">
                    <div class="specs-title">📝 Spesifikasi Produk</div>
                    <div class="specs-content"><?php echo nl2br(htmlspecialchars($selected_order['specifications'])); ?></div>
                </div>
            <?php endif; ?>
            
            <!-- Production Timeline -->
            <?php if ($selected_order['status'] == 'in_production' || $selected_order['status'] == 'completed' || $selected_order['status'] == 'delivered'): ?>
                <div class="timeline-section">
                    <div class="timeline-title">🔧 Progress Produksi</div>
                    
                    <!-- Overall Progress -->
                    <div class="overall-progress">
                        <div class="progress-text">
                            <span>Overall Progress</span>
                            <span class="progress-percentage"><?php echo round($overall_progress); ?>%</span>
                        </div>
                        <div class="progress-bar-large">
                            <div class="progress-fill" style="width: <?php echo $overall_progress; ?>%">
                                <?php if ($overall_progress > 10): ?>
                                    <?php echo round($overall_progress); ?>%
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Process Timeline -->
                    <?php if (mysqli_num_rows($schedules) > 0): ?>
                        <div class="process-timeline">
                            <?php while ($process = mysqli_fetch_assoc($schedules)): ?>
                                <?php
                                $status_class = str_replace('_', '-', $process['status']);
                                
                                // Detail description
                                $detail_desc = '';
                                if ($process['category'] == 'persiapan') {
                                    $detail_desc = 'Setup material, cutting awal, dan persiapan mesin';
                                } elseif ($process['category'] == 'produksi') {
                                    $detail_desc = 'Rough turning, fine turning, face milling, threading, grinding';
                                } elseif ($process['category'] == 'finishing') {
                                    $detail_desc = 'Polishing, quality check, final packaging';
                                }
                                
                                // Calculate time
                                $elapsed = 0;
                                if ($process['actual_start'] && $process['actual_end']) {
                                    $elapsed = round((strtotime($process['actual_end']) - strtotime($process['actual_start'])) / 60);
                                }
                                ?>
                                <div class="timeline-item <?php echo $status_class; ?>">
                                    <div class="timeline-content <?php echo $status_class; ?>">
                                        <div class="process-header">
                                            <span class="process-name">
                                                <?php echo $process['sequence_order']; ?>. <?php echo $process['process_name']; ?>
                                            </span>
                                            <span class="process-status <?php echo $status_class; ?>">
                                                <?php 
                                                    if ($process['status'] == 'completed') echo '✓ Selesai';
                                                    elseif ($process['status'] == 'in_progress') echo '🔄 Sedang Dikerjakan';
                                                    else echo '⏳ Menunggu';
                                                ?>
                                            </span>
                                        </div>
                                        
                                        <div class="process-desc">
                                            <?php echo $detail_desc; ?>
                                        </div>
                                        
                                        <?php if ($process['status'] == 'in_progress'): ?>
                                            <div class="process-progress">
                                                <div class="progress-label">
                                                    <span style="font-size: 11px;">Progress</span>
                                                    <span style="font-weight: bold; color: #667eea; font-size: 11px;">
                                                        <?php echo $process['progress_percentage']; ?>%
                                                    </span>
                                                </div>
                                                <div class="progress-bar-wrapper">
                                                    <div class="progress-bar" style="width: <?php echo $process['progress_percentage']; ?>%"></div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="process-meta">
                                            <?php if ($process['actual_start']): ?>
                                                <span>🕐 Mulai: <?php echo date('d M H:i', strtotime($process['actual_start'])); ?></span>
                                            <?php endif; ?>
                                            
                                            <?php if ($elapsed > 0): ?>
                                                <span>⏱️ Durasi: <?php echo $elapsed; ?> menit</span>
                                            <?php elseif ($process['estimated_time']): ?>
                                                <span>⏱️ Est: <?php echo $process['estimated_time']; ?> menit</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 20px; color: #999;">
                            <p>Produksi belum di-setup oleh penjual</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php elseif ($selected_order['status'] == 'approved'): ?>
                <div class="timeline-section">
                    <div class="timeline-title">⏳ Menunggu Setup Produksi</div>
                    <div style="text-align: center; padding: 30px 20px; color: #999;">
                        <div style="font-size: 48px; margin-bottom: 15px;">⚙️</div>
                        <p style="font-size: 14px; margin-bottom: 5px;">Pesanan Anda sudah disetujui!</p>
                        <p style="font-size: 13px;">Penjual sedang menyiapkan jadwal produksi.</p>
                    </div>
                </div>
            <?php elseif ($selected_order['status'] == 'pending'): ?>
                <div class="timeline-section">
                    <div class="timeline-title">⏳ Menunggu Persetujuan</div>
                    <div style="text-align: center; padding: 30px 20px; color: #999;">
                        <div style="font-size: 48px; margin-bottom: 15px;">📋</div>
                        <p style="font-size: 14px; margin-bottom: 5px;">Pesanan Anda sedang direview</p>
                        <p style="font-size: 13px;">Penjual akan segera memberikan konfirmasi harga dan estimasi waktu.</p>
                    </div>
                </div>
            <?php elseif ($selected_order['status'] == 'cancelled'): ?>
                <div class="timeline-section">
                    <div class="timeline-title">❌ Pesanan Dibatalkan</div>
                    <div style="text-align: center; padding: 30px 20px; color: #999;">
                        <div style="font-size: 48px; margin-bottom: 15px;">🚫</div>
                        <p style="font-size: 14px; margin-bottom: 10px;">Pesanan ini telah dibatalkan</p>
                        <?php if (!empty($selected_order['notes'])): ?>
                            <div style="background: #f8d7da; padding: 12px; border-radius: 8px; border-left: 4px solid #dc3545; margin-top: 15px; text-align: left;">
                                <strong style="color: #721c24; font-size: 13px;">Alasan:</strong>
                                <p style="color: #721c24; font-size: 13px; margin-top: 5px;">
                                    <?php echo nl2br(htmlspecialchars($selected_order['notes'])); ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
// Auto-refresh setiap 30 detik (opsional, uncomment untuk enable)
/*
setTimeout(function() {
    location.reload();
}, 30000);
*/

// Smooth scroll to top when loading detail
<?php if ($selected_order): ?>
window.scrollTo({ top: 0, behavior: 'smooth' });
<?php endif; ?>
</script>

</body>
</html>