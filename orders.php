<?php
session_start();
include 'koneksi.php';

// Cek login dan role (UPDATE: penjual → owner)
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'owner') {
    header("Location: index.php");
    exit;
}

$seller = $_SESSION['username'];

// Handle Approve
if (isset($_POST['approve'])) {
    $order_id = mysqli_real_escape_string($conn, $_POST['order_id']);
    $estimated_days = mysqli_real_escape_string($conn, $_POST['estimated_days']);
    $total_price = mysqli_real_escape_string($conn, $_POST['total_price']);
    
    $completion_date = date('Y-m-d', strtotime("+$estimated_days days"));
    
    $query = "UPDATE custom_orders SET 
              status = 'approved', 
              approved_at = NOW(),
              estimated_completion_date = '$completion_date',
              total_price = '$total_price'
              WHERE id = '$order_id' AND seller = '$seller'";
    
    if (mysqli_query($conn, $query)) {
        echo "<script>alert('✅ Pesanan berhasil di-approve!'); window.location='orders.php';</script>";
        exit;
    } else {
        echo "<script>alert('❌ Gagal approve pesanan: " . mysqli_error($conn) . "');</script>";
    }
}

// Handle Reject
if (isset($_POST['reject'])) {
    $order_id = mysqli_real_escape_string($conn, $_POST['order_id']);
    $reject_reason = mysqli_real_escape_string($conn, $_POST['reject_reason']);
    
    $query = "UPDATE custom_orders SET 
              status = 'cancelled', 
              notes = '$reject_reason'
              WHERE id = '$order_id' AND seller = '$seller'";
    
    if (mysqli_query($conn, $query)) {
        echo "<script>alert('✅ Pesanan berhasil ditolak!'); window.location='orders.php';</script>";
        exit;
    } else {
        echo "<script>alert('❌ Gagal reject pesanan: " . mysqli_error($conn) . "');</script>";
    }
}

// Ambil pesanan pending
$pending_orders = mysqli_query($conn, "
    SELECT * FROM custom_orders 
    WHERE seller = '$seller' AND status = 'pending' 
    ORDER BY created_at DESC
");

// Ambil pesanan approved (siap setup produksi)
$approved_orders = mysqli_query($conn, "
    SELECT * FROM custom_orders 
    WHERE seller = '$seller' AND status = 'approved' 
    ORDER BY approved_at DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pesanan - ERP UMKM</title>
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
        }
        
        h2 {
            color: #333;
            font-size: 28px;
        }
        
        .btn-back {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.2s;
        }
        
        .btn-back:hover {
            transform: translateY(-2px);
        }
        
        .section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .section-title {
            font-size: 20px;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .badge {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
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
        
        .order-body {
            display: grid;
            grid-template-columns: 1fr 1fr;
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
        
        .action-form {
            display: flex;
            gap: 10px;
            align-items: end;
            flex-wrap: wrap;
        }
        
        .form-group {
            flex: 1;
            min-width: 150px;
        }
        
        .form-group label {
            display: block;
            font-size: 12px;
            color: #555;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
        }
        
        .btn-approve {
            background: #27ae60;
            color: white;
        }
        
        .btn-approve:hover {
            background: #229954;
            transform: translateY(-2px);
        }
        
        .btn-reject {
            background: #e74c3c;
            color: white;
        }
        
        .btn-reject:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }
        
        .btn-setup {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            transition: transform 0.2s;
        }
        
        .btn-setup:hover {
            transform: translateY(-2px);
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
        
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-approved {
            background: #d4edda;
            color: #155724;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            max-width: 500px;
            width: 90%;
        }
        
        .modal-title {
            font-size: 20px;
            margin-bottom: 20px;
            color: #333;
        }
        
        textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
            resize: vertical;
            min-height: 100px;
        }
        
        textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .modal-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn-cancel {
            background: #95a5a6;
            color: white;
            flex: 1;
        }
        
        .btn-cancel:hover {
            background: #7f8c8d;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2>📋 Kelola Pesanan Custom</h2>
        <a href="dashboard.php" class="btn-back">🏠 Dashboard</a>
    </div>
    
    <!-- PENDING ORDERS -->
    <div class="section">
        <div class="section-title">
            🔔 Pesanan Baru (Butuh Approval)
            <span class="badge"><?php echo mysqli_num_rows($pending_orders); ?></span>
        </div>
        
        <?php if (mysqli_num_rows($pending_orders) > 0): ?>
            <?php while ($order = mysqli_fetch_assoc($pending_orders)): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <div class="order-number">Order #<?php echo $order['order_number']; ?></div>
                            <div class="order-date">📅 <?php echo date('d M Y H:i', strtotime($order['created_at'])); ?></div>
                        </div>
                    </div>
                    
                    <div class="order-body">
                        <div class="info-item">
                            <span class="info-label">👤 Pembeli</span>
                            <span class="info-value"><?php echo $order['buyer']; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">📦 Produk</span>
                            <span class="info-value"><?php echo $order['product_name']; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">🔢 Jumlah</span>
                            <span class="info-value"><?php echo $order['quantity']; ?> unit</span>
                        </div>
                    </div>
                    
                    <?php if (!empty($order['specifications'])): ?>
                        <div class="specs-box">
                            <div class="specs-title">📝 Spesifikasi:</div>
                            <div class="specs-content"><?php echo nl2br(htmlspecialchars($order['specifications'])); ?></div>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="action-form" onsubmit="return validateApprove(this)">
                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                        
                        <div class="form-group">
                            <label>Estimasi Waktu (hari)</label>
                            <input type="number" name="estimated_days" required min="1" placeholder="Contoh: 7">
                        </div>
                        
                        <div class="form-group">
                            <label>Total Harga (Rp)</label>
                            <input type="number" name="total_price" required min="0" step="1000" placeholder="Contoh: 5000000">
                        </div>
                        
                        <button type="submit" name="approve" class="btn btn-approve">✅ Approve</button>
                        <button type="button" class="btn btn-reject" onclick="showRejectModal(<?php echo $order['id']; ?>)">❌ Reject</button>
                    </form>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">📭</div>
                <h3>Tidak Ada Pesanan Baru</h3>
                <p>Pesanan masuk akan muncul di sini</p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- APPROVED ORDERS -->
    <div class="section">
        <div class="section-title">
            ✅ Pesanan Approved (Siap Setup Produksi)
            <span class="badge" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                <?php echo mysqli_num_rows($approved_orders); ?>
            </span>
        </div>
        
        <?php if (mysqli_num_rows($approved_orders) > 0): ?>
            <?php while ($order = mysqli_fetch_assoc($approved_orders)): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <div class="order-number">Order #<?php echo $order['order_number']; ?></div>
                            <span class="status-badge status-approved">APPROVED</span>
                        </div>
                        <a href="setup_production.php?order_id=<?php echo $order['id']; ?>" class="btn-setup">
                            ⚙️ Setup Produksi
                        </a>
                    </div>
                    
                    <div class="order-body">
                        <div class="info-item">
                            <span class="info-label">👤 Pembeli</span>
                            <span class="info-value"><?php echo $order['buyer']; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">📦 Produk</span>
                            <span class="info-value"><?php echo $order['product_name']; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">💰 Total Harga</span>
                            <span class="info-value">Rp <?php echo number_format($order['total_price'], 0, ',', '.'); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">⏰ Target Selesai</span>
                            <span class="info-value"><?php echo date('d M Y', strtotime($order['estimated_completion_date'])); ?></span>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">📦</div>
                <h3>Belum Ada Pesanan Approved</h3>
                <p>Approve pesanan di atas untuk memulai setup produksi</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Reject -->
<div id="rejectModal" class="modal">
    <div class="modal-content">
        <h3 class="modal-title">❌ Alasan Penolakan</h3>
        <form method="POST" onsubmit="return validateReject(this)">
            <input type="hidden" name="order_id" id="reject_order_id">
            <textarea name="reject_reason" placeholder="Masukkan alasan penolakan pesanan..." required></textarea>
            <div class="modal-actions">
                <button type="button" class="btn btn-cancel" onclick="closeRejectModal()">Batal</button>
                <button type="submit" name="reject" class="btn btn-reject">Tolak Pesanan</button>
            </div>
        </form>
    </div>
</div>

<script>
function validateApprove(form) {
    const days = form.estimated_days.value;
    const price = form.total_price.value;
    
    if (days < 1) {
        alert('Estimasi waktu minimal 1 hari!');
        return false;
    }
    
    if (price < 1000) {
        alert('Total harga minimal Rp 1.000!');
        return false;
    }
    
    return confirm('Yakin ingin approve pesanan ini?');
}

function validateReject(form) {
    const reason = form.reject_reason.value.trim();
    
    if (reason.length < 10) {
        alert('Alasan penolakan minimal 10 karakter!');
        return false;
    }
    
    return confirm('Yakin ingin menolak pesanan ini?');
}

function showRejectModal(orderId) {
    document.getElementById('reject_order_id').value = orderId;
    document.getElementById('rejectModal').style.display = 'flex';
}

function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
}

// Close modal saat klik di luar
window.onclick = function(event) {
    const modal = document.getElementById('rejectModal');
    if (event.target == modal) {
        closeRejectModal();
    }
}
</script>

</body>
</html>