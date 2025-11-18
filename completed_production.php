<?php
session_start();

// Cek login dan role owner
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'owner') {
    header("Location: index.php");
    exit;
}

include 'koneksi.php';

$username = $_SESSION['username'];

// Handle action untuk mark as shipped
if (isset($_POST['ship_order'])) {
    $order_id = mysqli_real_escape_string($conn, $_POST['order_id']);
    
    // Update status order menjadi delivered
    $update = mysqli_query($conn, "UPDATE custom_orders SET status = 'delivered' WHERE id = '$order_id' AND seller = '$username'");
    
    if ($update) {
        $_SESSION['success'] = "Pesanan berhasil ditandai sebagai DELIVERED!";
    } else {
        $_SESSION['error'] = "Gagal update status pesanan!";
    }
    
    header("Location: completed_production.php");
    exit;
}

// Ambil semua pesanan yang sudah completed
$query = "SELECT co.*, 
          (SELECT COUNT(*) FROM production_schedules WHERE order_id = co.id) as total_proses,
          (SELECT COUNT(*) FROM production_schedules WHERE order_id = co.id AND status = 'completed') as proses_selesai
          FROM custom_orders co 
          WHERE co.seller = '$username' 
          AND co.status = 'completed'
          ORDER BY co.completed_at DESC";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produksi Selesai - ERP UMKM</title>
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
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .header-left p {
            color: #777;
            font-size: 14px;
        }
        
        .btn-back {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
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
        }
        
        .alert-success {
            border-left: 4px solid #28a745;
        }
        
        .alert-error {
            border-left: 4px solid #dc3545;
        }
        
        .stats-box {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .stat-item {
            text-align: center;
            padding: 15px;
            background: #f8f9ff;
            border-radius: 10px;
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #777;
            font-size: 14px;
        }
        
        .order-grid {
            display: grid;
            gap: 20px;
        }
        
        .order-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .order-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .order-id {
            font-size: 18px;
            font-weight: bold;
            color: #667eea;
        }
        
        .order-date {
            font-size: 12px;
            color: #777;
            margin-top: 5px;
        }
        
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-completed {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
        }
        
        .order-info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .info-section {
            background: #f8f9ff;
            padding: 20px;
            border-radius: 10px;
        }
        
        .section-title {
            font-size: 14px;
            font-weight: 600;
            color: #667eea;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .info-table {
            width: 100%;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            color: #777;
            font-size: 13px;
        }
        
        .info-value {
            font-weight: 600;
            color: #333;
            font-size: 13px;
        }
        
        .production-timeline {
            background: #f8f9ff;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .timeline-title {
            font-size: 14px;
            font-weight: 600;
            color: #667eea;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .process-timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline-item {
            position: relative;
            padding-bottom: 20px;
        }
        
        .timeline-item:last-child {
            padding-bottom: 0;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -22px;
            top: 8px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #28a745;
            border: 3px solid white;
            box-shadow: 0 0 0 2px #28a745;
        }
        
        .timeline-item::after {
            content: '';
            position: absolute;
            left: -17px;
            top: 20px;
            width: 2px;
            height: calc(100% - 10px);
            background: #e0e0e0;
        }
        
        .timeline-item:last-child::after {
            display: none;
        }
        
        .timeline-content {
            background: white;
            padding: 12px 15px;
            border-radius: 8px;
            border-left: 3px solid #28a745;
        }
        
        .timeline-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }
        
        .process-name {
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        
        .process-status {
            background: #d4edda;
            color: #155724;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .process-desc {
            color: #777;
            font-size: 12px;
            margin-top: 5px;
        }
        
        .process-time {
            display: flex;
            gap: 15px;
            margin-top: 8px;
            font-size: 11px;
            color: #999;
        }
        
        .time-item {
            display: flex;
            align-items: center;
            gap: 5px;
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
        
        .order-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(17, 153, 142, 0.4);
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
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        
        .modal-header {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
        }
        
        .modal-body {
            color: #666;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        .btn-cancel {
            background: #6c757d;
            color: white;
        }
        
        .btn-cancel:hover {
            background: #5a6268;
        }
        
        @media (max-width: 768px) {
            .order-info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <div class="header-left">
            <h1>✅ Produksi Selesai</h1>
            <p>Daftar pesanan yang sudah selesai diproduksi dan siap untuk dikirim</p>
        </div>
        <a href="dashboard.php" class="btn-back">🏠 Dashboard</a>
    </div>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <span style="font-size: 20px;">✅</span>
            <span><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <span style="font-size: 20px;">❌</span>
            <span><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
        </div>
    <?php endif; ?>
    
    <!-- Statistics -->
    <div class="stats-box">
        <div class="stat-item">
            <div class="stat-value"><?php echo mysqli_num_rows($result); ?></div>
            <div class="stat-label">Ready to Ship</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">
                <?php 
                $delivered = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM custom_orders WHERE seller = '$username' AND status = 'delivered'"));
                echo $delivered['total'] ?? 0;
                ?>
            </div>
            <div class="stat-label">Sudah Dikirim</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">
                <?php 
                $total_completed_delivered = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM custom_orders WHERE seller = '$username' AND status IN ('completed', 'delivered')"));
                echo $total_completed_delivered['total'] ?? 0;
                ?>
            </div>
            <div class="stat-label">Total Selesai</div>
        </div>
    </div>
    
    <!-- Orders List -->
    <div class="order-grid">
        <?php if (mysqli_num_rows($result) > 0): ?>
            <?php while ($order = mysqli_fetch_assoc($result)): ?>
                <?php
                // Hitung durasi dengan aman
                $durasi_hari = 0;
                if ($order['approved_at'] && $order['completed_at']) {
                    $start = strtotime($order['approved_at']);
                    $end = strtotime($order['completed_at']);
                    if ($end > $start) {
                        $durasi_hari = ceil(($end - $start) / 86400);
                    }
                }
                
                // Hitung progress (dengan fallback untuk avoid division by zero)
                $progress_percentage = 100;
                if ($order['total_proses'] > 0) {
                    $progress_percentage = ($order['proses_selesai'] / $order['total_proses']) * 100;
                }
                ?>
                
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <div class="order-id">Order #<?php echo $order['order_number']; ?></div>
                            <div class="order-date">
                                ✅ Selesai: <?php echo date('d M Y H:i', strtotime($order['completed_at'])); ?>
                            </div>
                        </div>
                        <span class="status-badge status-completed">✅ COMPLETED</span>
                    </div>
                    
                    <!-- Order Info Grid -->
                    <div class="order-info-grid">
                        <!-- Left: Customer & Product Info -->
                        <div class="info-section">
                            <div class="section-title">📋 Informasi Pesanan</div>
                            <div class="info-table">
                                <div class="info-row">
                                    <span class="info-label">Customer</span>
                                    <span class="info-value"><?php echo $order['buyer']; ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Produk</span>
                                    <span class="info-value"><?php echo $order['product_name']; ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Quantity</span>
                                    <span class="info-value"><?php echo $order['quantity']; ?> unit</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Total Harga</span>
                                    <span class="info-value">Rp <?php echo number_format($order['total_price'], 0, ',', '.'); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Right: Timeline Info -->
                        <div class="info-section">
                            <div class="section-title">⏱️ Timeline Produksi</div>
                            <div class="info-table">
                                <div class="info-row">
                                    <span class="info-label">Mulai Produksi</span>
                                    <span class="info-value">
                                        <?php echo $order['approved_at'] ? date('d M Y', strtotime($order['approved_at'])) : 'N/A'; ?>
                                    </span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Selesai Produksi</span>
                                    <span class="info-value"><?php echo date('d M Y', strtotime($order['completed_at'])); ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Durasi</span>
                                    <span class="info-value">
                                        <?php echo $durasi_hari > 0 ? $durasi_hari . ' hari' : 'N/A'; ?>
                                    </span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Progress</span>
                                    <span class="info-value" style="color: #28a745;"><?php echo round($progress_percentage); ?>%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Specifications -->
                    <?php if (!empty($order['specifications'])): ?>
                        <div class="specs-box">
                            <div class="specs-title">📝 Spesifikasi Produk</div>
                            <div class="specs-content"><?php echo nl2br(htmlspecialchars($order['specifications'])); ?></div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Production Timeline -->
                    <div class="production-timeline">
                        <div class="timeline-title">🔧 Detail Proses Produksi (Milestone)</div>
                        <div class="process-timeline">
                            <?php
                            $processes = mysqli_query($conn, "
                                SELECT ps.*, pt.process_name, pt.description, pt.category
                                FROM production_schedules ps
                                JOIN process_types pt ON ps.process_type_id = pt.id
                                WHERE ps.order_id = '{$order['id']}' 
                                ORDER BY ps.sequence_order
                            ");
                            
                            while ($proc = mysqli_fetch_assoc($processes)):
                                // Hitung waktu proses
                                $elapsed = 0;
                                if ($proc['actual_start'] && $proc['actual_end']) {
                                    $start_time = strtotime($proc['actual_start']);
                                    $end_time = strtotime($proc['actual_end']);
                                    if ($end_time > $start_time) {
                                        $elapsed = round(($end_time - $start_time) / 60); // dalam menit
                                    }
                                }
                                
                                // Deskripsi detail berdasarkan kategori
                                $detail_desc = '';
                                if ($proc['category'] == 'persiapan') {
                                    $detail_desc = 'Meliputi: Setup material, cutting awal, dan persiapan mesin';
                                } elseif ($proc['category'] == 'produksi') {
                                    $detail_desc = 'Meliputi: Rough turning, fine turning, face milling, threading, dan grinding';
                                } elseif ($proc['category'] == 'finishing') {
                                    $detail_desc = 'Meliputi: Polishing, quality check, dan final packaging';
                                }
                            ?>
                                <div class="timeline-item">
                                    <div class="timeline-content">
                                        <div class="timeline-header">
                                            <span class="process-name">
                                                <?php echo $proc['sequence_order']; ?>. <?php echo $proc['process_name']; ?>
                                            </span>
                                            <span class="process-status">✓ Completed</span>
                                        </div>
                                        <div class="process-desc">
                                            <?php echo $detail_desc; ?>
                                        </div>
                                        <div class="process-time">
                                            <?php if ($elapsed > 0): ?>
                                                <span class="time-item">⏱️ Waktu: <?php echo $elapsed; ?> menit</span>
                                            <?php endif; ?>
                                            <?php if ($proc['actual_start']): ?>
                                                <span class="time-item">🕐 <?php echo date('d M H:i', strtotime($proc['actual_start'])); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <div class="order-actions">
                        <button class="btn btn-success" onclick="confirmShip(<?php echo $order['id']; ?>, '<?php echo $order['order_number']; ?>')">
                            🚚 Mark as Delivered
                        </button>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">📭</div>
                <div class="empty-text">Belum Ada Produksi yang Selesai</div>
                <div class="empty-subtext">Pesanan yang sudah completed akan muncul di sini</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Konfirmasi Ship -->
<div id="shipModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">🚚 Konfirmasi Pengiriman</div>
        <div class="modal-body">
            Apakah Anda yakin produk untuk <strong id="order_number_display"></strong> sudah dikirim ke customer?<br><br>
            Status pesanan akan berubah menjadi <strong>DELIVERED</strong>.
        </div>
        <div class="modal-actions">
            <button class="btn btn-cancel" onclick="closeShipModal()">Batal</button>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="order_id" id="ship_order_id">
                <button type="submit" name="ship_order" class="btn btn-success">✅ Ya, Sudah Dikirim</button>
            </form>
        </div>
    </div>
</div>

<script>
function confirmShip(orderId, orderNumber) {
    document.getElementById('ship_order_id').value = orderId;
    document.getElementById('order_number_display').textContent = orderNumber;
    document.getElementById('shipModal').classList.add('active');
}

function closeShipModal() {
    document.getElementById('shipModal').classList.remove('active');
}

// Close modal when clicking outside
document.getElementById('shipModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeShipModal();
    }
});
</script>

</body>
</html>