<?php
session_start();
include 'koneksi.php';

// Cek login dan role
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'owner') {
    header("Location: index.php");
    exit;
}

$owner = $_SESSION['username'];

// Ambil order_id dari parameter atau list semua approved orders
$selected_order_id = isset($_GET['order_id']) ? mysqli_real_escape_string($conn, $_GET['order_id']) : null;

// Handle submit setup production
if (isset($_POST['setup_production'])) {
    $order_id = mysqli_real_escape_string($conn, $_POST['order_id']);
    $processes = $_POST['processes']; // Array of process_type_ids
    $sequences = $_POST['sequences']; // Array of sequence numbers
    
    // Validasi minimal 1 proses
    if (empty($processes) || empty($sequences)) {
        echo "<script>alert('❌ Pilih minimal 1 proses produksi!');</script>";
    } else {
        // Begin transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Hapus setup lama jika ada (untuk re-setup)
            mysqli_query($conn, "DELETE FROM production_schedules WHERE order_id = '$order_id'");
            
            // Insert production schedules
            $total_estimated_time = 0;
            $success = true;
            
            foreach ($processes as $index => $process_id) {
                $sequence = mysqli_real_escape_string($conn, $sequences[$index]);
                
                // Ambil estimasi waktu dari process_types dan hitung berdasarkan quantity
                $process_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT estimated_time FROM process_types WHERE id = '$process_id'"));
                $base_time = $process_info['estimated_time'];
                
                // Ambil quantity dari order
                $order_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT quantity FROM custom_orders WHERE id = '$order_id'"));
                $quantity = $order_info['quantity'];
                
                // Formula: Base Time + (Additional Units × 5 menit per unit)
                $time_per_additional_unit = 5; // menit per unit tambahan
                $estimated_time = $base_time + (($quantity - 1) * $time_per_additional_unit);
                
                $total_estimated_time += $estimated_time;
                
                $query = "INSERT INTO production_schedules 
                          (order_id, process_type_id, sequence_order, operator, status, progress_percentage) 
                          VALUES 
                          ('$order_id', '$process_id', '$sequence', '$owner', 'pending', 0)";
                
                if (!mysqli_query($conn, $query)) {
                    $success = false;
                    break;
                }
            }
            
            if ($success) {
                // Update order status menjadi in_production
                $update_order = "UPDATE custom_orders SET 
                                status = 'in_production',
                                total_estimated_time = '$total_estimated_time'
                                WHERE id = '$order_id'";
                
                if (mysqli_query($conn, $update_order)) {
                    mysqli_commit($conn);
                    echo "<script>
                            alert('✅ Setup produksi berhasil!\\n\\nTotal Estimasi: " . $total_estimated_time . " menit\\nPesanan sudah masuk produksi.');
                            window.location='setup_production.php';
                          </script>";
                    exit;
                } else {
                    throw new Exception("Gagal update order status");
                }
            } else {
                throw new Exception("Gagal insert production schedules");
            }
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            echo "<script>alert('❌ Error: " . $e->getMessage() . "');</script>";
        }
    }
}

// Ambil daftar pesanan approved
$approved_orders = mysqli_query($conn, "
    SELECT * FROM custom_orders 
    WHERE seller = '$owner' AND status = 'approved' 
    ORDER BY approved_at DESC
");

// Jika ada order yang dipilih, ambil detailnya
$selected_order = null;
if ($selected_order_id) {
    $selected_order = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT * FROM custom_orders 
        WHERE id = '$selected_order_id' AND seller = '$owner' AND status = 'approved'
    "));
}

// Ambil semua process types untuk dropdown
$process_types = mysqli_query($conn, "SELECT * FROM process_types ORDER BY category, process_name");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Produksi - ERP UMKM</title>
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
        
        .content-grid {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 20px;
        }
        
        .sidebar {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            height: fit-content;
        }
        
        .sidebar-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .order-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .order-item {
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            color: #333;
        }
        
        .order-item:hover {
            border-color: #667eea;
            background: #f8f9ff;
        }
        
        .order-item.active {
            border-color: #667eea;
            background: #f8f9ff;
        }
        
        .order-item-number {
            font-weight: 600;
            color: #667eea;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .order-item-product {
            font-size: 13px;
            color: #555;
        }
        
        .main-content {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
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
        
        .order-detail {
            background: #f8f9ff;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
        }
        
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .detail-label {
            font-size: 12px;
            color: #777;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        .detail-value {
            font-size: 15px;
            color: #333;
            font-weight: 500;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .process-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .process-item {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .process-number {
            background: #667eea;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            flex-shrink: 0;
        }
        
        .process-info {
            flex: 1;
        }
        
        .process-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .process-desc {
            font-size: 13px;
            color: #777;
        }
        
        .process-time {
            color: #667eea;
            font-weight: 600;
            font-size: 14px;
        }
        
        .process-remove {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .process-remove:hover {
            background: #c0392b;
        }
        
        .add-process-section {
            background: #f8f9ff;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #555;
            margin-bottom: 8px;
        }
        
        select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
        }
        
        .btn-add {
            background: #667eea;
            color: white;
        }
        
        .btn-add:hover {
            background: #5568d3;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            width: 100%;
            padding: 15px;
            font-size: 16px;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(17, 153, 142, 0.4);
        }
        
        .summary-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .summary-title {
            font-weight: 600;
            color: #856404;
            margin-bottom: 10px;
        }
        
        .summary-item {
            color: #856404;
            font-size: 14px;
            margin: 5px 0;
        }
        
        @media (max-width: 968px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2>⚙️ Setup Produksi</h2>
        <a href="dashboard.php" class="btn-back">🏠 Dashboard</a>
    </div>
    
    <div class="content-grid">
        <!-- Sidebar: List Pesanan Approved -->
        <div class="sidebar">
            <div class="sidebar-title">📋 Pesanan Approved</div>
            <div class="order-list">
                <?php if (mysqli_num_rows($approved_orders) > 0): ?>
                    <?php while ($order = mysqli_fetch_assoc($approved_orders)): ?>
                        <a href="setup_production.php?order_id=<?php echo $order['id']; ?>" 
                           class="order-item <?php echo ($selected_order_id == $order['id']) ? 'active' : ''; ?>">
                            <div class="order-item-number">#<?php echo $order['order_number']; ?></div>
                            <div class="order-item-product"><?php echo $order['product_name']; ?></div>
                        </a>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">📭</div>
                        <p>Tidak ada pesanan approved</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Main Content: Setup Form -->
        <div class="main-content">
            <?php if ($selected_order): ?>
                <!-- Order Detail -->
                <div class="order-detail">
                    <h3>📦 Detail Pesanan</h3>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="detail-label">Order Number</span>
                            <span class="detail-value">#<?php echo $selected_order['order_number']; ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Produk</span>
                            <span class="detail-value"><?php echo $selected_order['product_name']; ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Jumlah</span>
                            <span class="detail-value"><?php echo $selected_order['quantity']; ?> unit</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Total Harga</span>
                            <span class="detail-value">Rp <?php echo number_format($selected_order['total_price'], 0, ',', '.'); ?></span>
                        </div>
                    </div>
                    
                    <div style="margin-top: 15px; padding: 12px; background: #fff3cd; border-radius: 8px; border-left: 4px solid #ffc107;">
                        <strong style="color: #856404;">💡 Info Waktu Estimasi:</strong>
                        <p style="color: #856404; font-size: 13px; margin-top: 5px;">
                            Waktu dihitung otomatis: <strong>Base Time + (Qty-1) × 5 menit</strong><br>
                            Contoh: 1 unit = 30 menit | 5 unit = 50 menit | 10 unit = 75 menit
                        </p>
                    </div>
                </div>
                
                <!-- Setup Production Form -->
                <form method="POST" id="setupForm">
                    <input type="hidden" name="order_id" value="<?php echo $selected_order['id']; ?>">
                    
                    <div class="section-title">🔧 Pilih Proses Produksi</div>
                    
                    <div class="add-process-section">
                        <div class="form-group">
                            <label>Pilih Proses</label>
                            <select id="process_selector">
                                <option value="">-- Pilih Proses --</option>
                                <?php 
                                mysqli_data_seek($process_types, 0);
                                while ($process = mysqli_fetch_assoc($process_types)): 
                                ?>
                                    <option value="<?php echo $process['id']; ?>" 
                                            data-name="<?php echo $process['process_name']; ?>"
                                            data-desc="<?php echo $process['description']; ?>"
                                            data-time="<?php echo $process['estimated_time']; ?>"
                                            data-category="<?php echo $process['category']; ?>">
                                        [<?php echo strtoupper($process['category']); ?>] 
                                        <?php echo $process['process_name']; ?> 
                                        (<?php echo $process['estimated_time']; ?> menit)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <button type="button" class="btn btn-add" onclick="addProcess()">➕ Tambah Proses</button>
                    </div>
                    
                    <div id="processList" class="process-list">
                        <!-- Processes will be added here by JavaScript -->
                    </div>
                    
                    <div id="summaryBox" class="summary-box" style="display: none;">
                        <div class="summary-title">📊 Ringkasan Setup Produksi</div>
                        <div class="summary-item">Total Proses: <strong id="totalProcesses">0</strong></div>
                        <div class="summary-item">Total Waktu Estimasi: <strong id="totalTime">0</strong> menit</div>
                    </div>
                    
                    <button type="submit" name="setup_production" class="btn btn-submit" id="submitBtn" style="display: none;">
                        🚀 Mulai Produksi
                    </button>
                </form>
                
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">⚙️</div>
                    <h3>Pilih Pesanan untuk Setup Produksi</h3>
                    <p>Klik salah satu pesanan di sidebar untuk memulai setup</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
let processCounter = 0;
let processes = [];
const orderQuantity = <?php echo $selected_order ? $selected_order['quantity'] : 1; ?>;

function calculateTime(baseTime) {
    // Formula: Base Time + (Qty - 1) × 5 menit per unit tambahan
    const timePerUnit = 5;
    return baseTime + ((orderQuantity - 1) * timePerUnit);
}

function addProcess() {
    const selector = document.getElementById('process_selector');
    const selectedOption = selector.options[selector.selectedIndex];
    
    if (!selectedOption.value) {
        alert('Pilih proses terlebih dahulu!');
        return;
    }
    
    const processId = selectedOption.value;
    const processName = selectedOption.dataset.name;
    const processDesc = selectedOption.dataset.desc;
    const baseTime = parseInt(selectedOption.dataset.time);
    const actualTime = calculateTime(baseTime);
    const processCategory = selectedOption.dataset.category;
    
    processCounter++;
    processes.push({
        id: processId,
        name: processName,
        desc: processDesc,
        baseTime: baseTime,
        time: actualTime,
        sequence: processCounter
    });
    
    renderProcessList();
    updateSummary();
    
    // Reset selector
    selector.value = '';
}

function removeProcess(index) {
    processes.splice(index, 1);
    // Re-sequence
    processes.forEach((proc, idx) => {
        proc.sequence = idx + 1;
    });
    processCounter = processes.length;
    
    renderProcessList();
    updateSummary();
}

function renderProcessList() {
    const listContainer = document.getElementById('processList');
    
    if (processes.length === 0) {
        listContainer.innerHTML = `
            <div class="empty-state">
                <div class="empty-icon">🔧</div>
                <p>Belum ada proses ditambahkan</p>
            </div>
        `;
        return;
    }
    
    let html = '';
    processes.forEach((proc, index) => {
        html += `
            <div class="process-item">
                <div class="process-number">${proc.sequence}</div>
                <div class="process-info">
                    <div class="process-name">${proc.name}</div>
                    <div class="process-desc">${proc.desc}</div>
                </div>
                <div class="process-time">
                    ⏱️ ${proc.time} menit
                    ${orderQuantity > 1 ? `<br><small style="opacity: 0.7;">(Base: ${proc.baseTime}m + ${orderQuantity-1}×5m)</small>` : ''}
                </div>
                <button type="button" class="process-remove" onclick="removeProcess(${index})">🗑️ Hapus</button>
                <input type="hidden" name="processes[]" value="${proc.id}">
                <input type="hidden" name="sequences[]" value="${proc.sequence}">
            </div>
        `;
    });
    
    listContainer.innerHTML = html;
}

function updateSummary() {
    const summaryBox = document.getElementById('summaryBox');
    const submitBtn = document.getElementById('submitBtn');
    
    if (processes.length === 0) {
        summaryBox.style.display = 'none';
        submitBtn.style.display = 'none';
        return;
    }
    
    const totalTime = processes.reduce((sum, proc) => sum + proc.time, 0);
    const totalHours = (totalTime / 60).toFixed(1);
    
    document.getElementById('totalProcesses').textContent = processes.length;
    document.getElementById('totalTime').textContent = `${totalTime} menit (≈ ${totalHours} jam)`;
    
    summaryBox.style.display = 'block';
    submitBtn.style.display = 'block';
}

// Validasi sebelum submit
document.getElementById('setupForm').addEventListener('submit', function(e) {
    if (processes.length === 0) {
        e.preventDefault();
        alert('Tambahkan minimal 1 proses produksi!');
        return false;
    }
    
    const totalTime = processes.reduce((sum, proc) => sum + proc.time, 0);
    const totalHours = (totalTime / 60).toFixed(1);
    
    return confirm(`Yakin ingin memulai produksi dengan ${processes.length} proses?\n\nTotal Estimasi: ${totalTime} menit (≈ ${totalHours} jam)\nQuantity: ${orderQuantity} unit\n\nSetelah dimulai, pesanan akan masuk ke tahap produksi.`);
});
</script>

</body>
</html>