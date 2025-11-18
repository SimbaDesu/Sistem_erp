<?php
session_start();
include 'koneksi.php';

// Cek login dan role
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'owner') {
    header("Location: index.php");
    exit;
}

$owner = $_SESSION['username'];

// Handle Start Process
if (isset($_POST['start_process'])) {
    $schedule_id = mysqli_real_escape_string($conn, $_POST['schedule_id']);
    
    $query = "UPDATE production_schedules SET 
              status = 'in_progress',
              actual_start = NOW()
              WHERE id = '$schedule_id' AND operator = '$owner'";
    
    if (mysqli_query($conn, $query)) {
        // Log ke production_logs
        mysqli_query($conn, "INSERT INTO production_logs (schedule_id, operator, progress_percentage, status, notes) 
                             VALUES ('$schedule_id', '$owner', 0, 'started', 'Proses dimulai')");
        
        echo "<script>alert('▶️ Proses dimulai!'); window.location='update_progress.php';</script>";
        exit;
    }
}

// Handle Update Progress
if (isset($_POST['update_progress'])) {
    $schedule_id = mysqli_real_escape_string($conn, $_POST['schedule_id']);
    $progress = mysqli_real_escape_string($conn, $_POST['progress_percentage']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);
    
    $query = "UPDATE production_schedules SET 
              progress_percentage = '$progress'
              WHERE id = '$schedule_id' AND operator = '$owner'";
    
    if (mysqli_query($conn, $query)) {
        // Log update
        mysqli_query($conn, "INSERT INTO production_logs (schedule_id, operator, progress_percentage, status, notes) 
                             VALUES ('$schedule_id', '$owner', '$progress', 'in_progress', '$notes')");
        
        echo "<script>alert('✅ Progress diupdate ke $progress%'); window.location='update_progress.php';</script>";
        exit;
    }
}

// Handle Complete Process
if (isset($_POST['complete_process'])) {
    $schedule_id = mysqli_real_escape_string($conn, $_POST['schedule_id']);
    $notes = mysqli_real_escape_string($conn, $_POST['complete_notes']);
    
    mysqli_begin_transaction($conn);
    
    try {
        // Update schedule
        $query = "UPDATE production_schedules SET 
                  status = 'completed',
                  progress_percentage = 100,
                  actual_end = NOW()
                  WHERE id = '$schedule_id' AND operator = '$owner'";
        
        if (!mysqli_query($conn, $query)) {
            throw new Exception("Gagal update schedule");
        }
        
        // Log completion
        mysqli_query($conn, "INSERT INTO production_logs (schedule_id, operator, progress_percentage, status, notes) 
                             VALUES ('$schedule_id', '$owner', 100, 'completed', '$notes')");
        
        // Cek apakah semua proses di order ini sudah completed
        $schedule_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT order_id FROM production_schedules WHERE id = '$schedule_id'"));
        $order_id = $schedule_info['order_id'];
        
        $pending_count = mysqli_fetch_assoc(mysqli_query($conn, "
            SELECT COUNT(*) as total FROM production_schedules 
            WHERE order_id = '$order_id' AND status != 'completed'
        "));
        
        // Jika semua proses completed, update order status
        if ($pending_count['total'] == 0) {
            mysqli_query($conn, "UPDATE custom_orders SET status = 'completed', completed_at = NOW() WHERE id = '$order_id'");
        }
        
        mysqli_commit($conn);
        echo "<script>alert('✅ Proses selesai!'); window.location='update_progress.php';</script>";
        exit;
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "<script>alert('❌ Error: " . $e->getMessage() . "');</script>";
    }
}

// Handle Pause Process
if (isset($_POST['pause_process'])) {
    $schedule_id = mysqli_real_escape_string($conn, $_POST['schedule_id']);
    $notes = mysqli_real_escape_string($conn, $_POST['pause_notes']);
    
    $query = "UPDATE production_schedules SET 
              status = 'paused'
              WHERE id = '$schedule_id' AND operator = '$owner'";
    
    if (mysqli_query($conn, $query)) {
        mysqli_query($conn, "INSERT INTO production_logs (schedule_id, operator, progress_percentage, status, notes) 
                             VALUES ('$schedule_id', '$owner', (SELECT progress_percentage FROM production_schedules WHERE id = '$schedule_id'), 'paused', '$notes')");
        
        echo "<script>alert('⏸️ Proses dijeda'); window.location='update_progress.php';</script>";
        exit;
    }
}

// Ambil semua order yang sedang in_production
$in_production_orders = mysqli_query($conn, "
    SELECT * FROM custom_orders 
    WHERE seller = '$owner' AND status = 'in_production' 
    ORDER BY approved_at DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Progress - ERP UMKM</title>
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
        
        .order-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px dashed #e0e0e0;
        }
        
        .order-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .order-number {
            font-size: 14px;
            color: #667eea;
            font-weight: 600;
        }
        
        .overall-progress {
            text-align: right;
        }
        
        .overall-label {
            font-size: 12px;
            color: #777;
            margin-bottom: 5px;
        }
        
        .overall-percentage {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }
        
        .process-card {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        
        .process-card.completed {
            border-color: #27ae60;
            background: #f0fff4;
        }
        
        .process-card.in-progress {
            border-color: #667eea;
            background: #f8f9ff;
        }
        
        .process-card.paused {
            border-color: #f39c12;
            background: #fff8e1;
        }
        
        .process-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        
        .process-left {
            flex: 1;
        }
        
        .process-number {
            background: #667eea;
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 10px;
        }
        
        .process-name {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            display: inline;
        }
        
        .process-desc {
            color: #777;
            font-size: 14px;
            margin-top: 5px;
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
        
        .status-in-progress {
            background: #cce5ff;
            color: #004085;
        }
        
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        
        .status-paused {
            background: #fff3cd;
            color: #856404;
        }
        
        .time-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 15px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .time-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .time-label {
            font-size: 12px;
            color: #777;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        .time-value {
            font-size: 15px;
            color: #333;
            font-weight: 500;
        }
        
        .progress-bar-container {
            background: #e0e0e0;
            height: 30px;
            border-radius: 15px;
            overflow: hidden;
            margin: 15px 0;
            position: relative;
        }
        
        .progress-bar {
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            height: 100%;
            transition: width 0.5s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
        }
        
        .action-section {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px dashed #e0e0e0;
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
        
        input, textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
        }
        
        textarea {
            resize: vertical;
            min-height: 60px;
        }
        
        input:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
        }
        
        .btn-start {
            background: #27ae60;
            color: white;
            flex: 1;
        }
        
        .btn-start:hover {
            background: #229954;
            transform: translateY(-2px);
        }
        
        .btn-update {
            background: #667eea;
            color: white;
        }
        
        .btn-update:hover {
            background: #5568d3;
        }
        
        .btn-pause {
            background: #f39c12;
            color: white;
        }
        
        .btn-pause:hover {
            background: #e67e22;
        }
        
        .btn-complete {
            background: #27ae60;
            color: white;
            flex: 1;
        }
        
        .btn-complete:hover {
            background: #229954;
            transform: translateY(-2px);
        }
        
        .quick-progress {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .quick-btn {
            flex: 1;
            padding: 10px;
            background: #f8f9ff;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            color: #667eea;
        }
        
        .quick-btn:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
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
        
        .logs-section {
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .logs-title {
            font-size: 14px;
            font-weight: 600;
            color: #555;
            margin-bottom: 10px;
        }
        
        .log-item {
            padding: 8px 12px;
            background: white;
            border-radius: 6px;
            margin-bottom: 8px;
            font-size: 13px;
            color: #666;
        }
        
        .log-time {
            color: #999;
            font-size: 11px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2>🔄 Update Progress Produksi</h2>
        <a href="dashboard.php" class="btn-back">🏠 Dashboard</a>
    </div>
    
    <?php if (mysqli_num_rows($in_production_orders) > 0): ?>
        <?php while ($order = mysqli_fetch_assoc($in_production_orders)): ?>
            <?php
            // Ambil semua schedules untuk order ini
            $schedules = mysqli_query($conn, "
                SELECT ps.*, pt.process_name, pt.description, pt.category 
                FROM production_schedules ps
                JOIN process_types pt ON ps.process_type_id = pt.id
                WHERE ps.order_id = '{$order['id']}'
                ORDER BY ps.sequence_order
            ");
            
            // Hitung overall progress
            $total_schedules = mysqli_num_rows($schedules);
            mysqli_data_seek($schedules, 0);
            $total_progress = 0;
            while ($s = mysqli_fetch_assoc($schedules)) {
                $total_progress += $s['progress_percentage'];
            }
            $overall_progress = $total_schedules > 0 ? round($total_progress / $total_schedules) : 0;
            mysqli_data_seek($schedules, 0);
            ?>
            
            <div class="order-section">
                <div class="order-header">
                    <div>
                        <div class="order-title">📦 <?php echo $order['product_name']; ?></div>
                        <div class="order-number">#<?php echo $order['order_number']; ?> • <?php echo $order['quantity']; ?> unit</div>
                    </div>
                    <div class="overall-progress">
                        <div class="overall-label">Overall Progress</div>
                        <div class="overall-percentage"><?php echo $overall_progress; ?>%</div>
                    </div>
                </div>
                
                <?php while ($schedule = mysqli_fetch_assoc($schedules)): ?>
                    <?php
                    // Ambil logs untuk schedule ini
                    $logs = mysqli_query($conn, "
                        SELECT * FROM production_logs 
                        WHERE schedule_id = '{$schedule['id']}' 
                        ORDER BY logged_at DESC 
                        LIMIT 5
                    ");
                    
                    // Hitung elapsed time jika sedang in progress
                    $elapsed_time = 0;
                    if ($schedule['status'] == 'in_progress' && $schedule['actual_start']) {
                        $start = strtotime($schedule['actual_start']);
                        $now = time();
                        $elapsed_time = round(($now - $start) / 60); // dalam menit
                    }
                    
                    // Hitung actual time jika completed
                    $actual_time = 0;
                    if ($schedule['status'] == 'completed' && $schedule['actual_start'] && $schedule['actual_end']) {
                        $start = strtotime($schedule['actual_start']);
                        $end = strtotime($schedule['actual_end']);
                        $actual_time = round(($end - $start) / 60); // dalam menit
                    }
                    ?>
                    
                    <div class="process-card <?php echo $schedule['status']; ?>">
                        <div class="process-header">
                            <div class="process-left">
                                <span class="process-number"><?php echo $schedule['sequence_order']; ?></span>
                                <span class="process-name"><?php echo $schedule['process_name']; ?></span>
                                <div class="process-desc"><?php echo $schedule['description']; ?></div>
                            </div>
                            <span class="status-badge status-<?php echo str_replace('_', '-', $schedule['status']); ?>">
                                <?php 
                                    $status_text = [
                                        'pending' => '⏳ PENDING',
                                        'in_progress' => '🔄 IN PROGRESS',
                                        'completed' => '✅ COMPLETED',
                                        'paused' => '⏸️ PAUSED'
                                    ];
                                    echo $status_text[$schedule['status']];
                                ?>
                            </span>
                        </div>
                        
                        <?php if ($schedule['status'] == 'completed'): ?>
                            <div class="time-info">
                                <div class="time-item">
                                    <span class="time-label">Estimasi</span>
                                    <span class="time-value"><?php echo $schedule['estimated_time'] ?? 'N/A'; ?> menit</span>
                                </div>
                                <div class="time-item">
                                    <span class="time-label">Actual Time</span>
                                    <span class="time-value" style="color: <?php echo $actual_time <= ($schedule['estimated_time'] ?? 0) ? '#27ae60' : '#e74c3c'; ?>">
                                        <?php echo $actual_time; ?> menit
                                        <?php if ($schedule['estimated_time']): ?>
                                            <?php $diff = ($schedule['estimated_time'] ?? 0) - $actual_time; ?>
                                            (<?php echo $diff > 0 ? "lebih cepat $diff menit" : "lebih lama " . abs($diff) . " menit"; ?>)
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="time-item">
                                    <span class="time-label">Start</span>
                                    <span class="time-value"><?php echo date('d M H:i', strtotime($schedule['actual_start'])); ?></span>
                                </div>
                                <div class="time-item">
                                    <span class="time-label">Complete</span>
                                    <span class="time-value"><?php echo date('d M H:i', strtotime($schedule['actual_end'])); ?></span>
                                </div>
                            </div>
                            
                            <div class="progress-bar-container">
                                <div class="progress-bar" style="width: 100%;">100% COMPLETED</div>
                            </div>
                            
                        <?php elseif ($schedule['status'] == 'in_progress' || $schedule['status'] == 'paused'): ?>
                            <div class="time-info">
                                <div class="time-item">
                                    <span class="time-label">Estimasi</span>
                                    <span class="time-value"><?php echo $schedule['estimated_time'] ?? 'N/A'; ?> menit</span>
                                </div>
                                <div class="time-item">
                                    <span class="time-label">Elapsed Time</span>
                                    <span class="time-value"><?php echo $elapsed_time; ?> menit</span>
                                </div>
                                <div class="time-item">
                                    <span class="time-label">Start</span>
                                    <span class="time-value"><?php echo date('d M H:i', strtotime($schedule['actual_start'])); ?></span>
                                </div>
                            </div>
                            
                            <div class="progress-bar-container">
                                <div class="progress-bar" style="width: <?php echo $schedule['progress_percentage']; ?>%;">
                                    <?php echo $schedule['progress_percentage']; ?>%
                                </div>
                            </div>
                            
                            <?php if ($schedule['status'] == 'in_progress'): ?>
                                <div class="action-section">
                                    <div class="quick-progress">
                                        <button class="quick-btn" onclick="setProgress(<?php echo $schedule['id']; ?>, 25)">25%</button>
                                        <button class="quick-btn" onclick="setProgress(<?php echo $schedule['id']; ?>, 50)">50%</button>
                                        <button class="quick-btn" onclick="setProgress(<?php echo $schedule['id']; ?>, 75)">75%</button>
                                        <button class="quick-btn" onclick="setProgress(<?php echo $schedule['id']; ?>, 100)">100%</button>
                                    </div>
                                    
                                    <form method="POST" id="updateForm<?php echo $schedule['id']; ?>">
                                        <input type="hidden" name="schedule_id" value="<?php echo $schedule['id']; ?>">
                                        <input type="hidden" name="progress_percentage" id="progress<?php echo $schedule['id']; ?>" value="<?php echo $schedule['progress_percentage']; ?>">
                                        
                                        <div class="form-group">
                                            <label>Notes / Catatan</label>
                                            <textarea name="notes" placeholder="Catatan progress (opsional)..."></textarea>
                                        </div>
                                        
                                        <div class="btn-group">
                                            <button type="submit" name="update_progress" class="btn btn-update">💾 Update Progress</button>
                                            <button type="submit" name="pause_process" class="btn btn-pause" onclick="return confirm('Yakin ingin pause proses?')">⏸️ Pause</button>
                                        </div>
                                    </form>
                                    
                                    <form method="POST" style="margin-top: 10px;">
                                        <input type="hidden" name="schedule_id" value="<?php echo $schedule['id']; ?>">
                                        <input type="hidden" name="complete_notes" value="Proses selesai">
                                        <button type="submit" name="complete_process" class="btn btn-complete" onclick="return confirm('Yakin proses sudah 100% selesai?')">✅ Complete Proses</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <div class="action-section">
                                    <form method="POST">
                                        <input type="hidden" name="schedule_id" value="<?php echo $schedule['id']; ?>">
                                        <button type="submit" name="start_process" class="btn btn-start">▶️ Resume Proses</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                            
                        <?php else: ?>
                            <div class="time-info">
                                <div class="time-item">
                                    <span class="time-label">Estimasi Waktu</span>
                                    <span class="time-value"><?php echo $schedule['estimated_time'] ?? 'N/A'; ?> menit</span>
                                </div>
                            </div>
                            
                            <div class="action-section">
                                <form method="POST">
                                    <input type="hidden" name="schedule_id" value="<?php echo $schedule['id']; ?>">
                                    <button type="submit" name="start_process" class="btn btn-start">▶️ Start Proses</button>
                                </form>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (mysqli_num_rows($logs) > 0): ?>
                            <div class="logs-section">
                                <div class="logs-title">📝 History Log</div>
                                <?php while ($log = mysqli_fetch_assoc($logs)): ?>
                                    <div class="log-item">
                                        <strong><?php echo $log['progress_percentage']; ?>%</strong> - 
                                        <?php echo $log['notes']; ?>
                                        <div class="log-time"><?php echo date('d M Y H:i:s', strtotime($log['logged_at'])); ?></div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endwhile; ?>
        
    <?php else: ?>
        <div class="order-section">
            <div class="empty-state">
                <div class="empty-icon">📭</div>
                <h3>Tidak Ada Produksi Aktif</h3>
                <p>Setup produksi terlebih dahulu di menu "Setup Produksi"</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function setProgress(scheduleId, percentage) {
    document.getElementById('progress' + scheduleId).value = percentage;
}
</script>

</body>
</html>