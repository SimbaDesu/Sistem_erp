<?php
session_start();
include 'koneksi.php';

// Cek login dan role
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'pembeli') {
    header("Location: index.php");
    exit;
}

$buyer = $_SESSION['username'];

// Ambil daftar penjual dari tabel products (unique sellers)
$sellers_query = mysqli_query($conn, "SELECT DISTINCT seller FROM products ORDER BY seller");

// Generate order number
function generateOrderNumber() {
    return 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

// Handle submit order
if (isset($_POST['submit_order'])) {
    $order_number = generateOrderNumber();
    $seller = mysqli_real_escape_string($conn, $_POST['seller']);
    $product_name = mysqli_real_escape_string($conn, $_POST['product_name']);
    $specifications = mysqli_real_escape_string($conn, $_POST['specifications']);
    $quantity = mysqli_real_escape_string($conn, $_POST['quantity']);
    
    // Handle file upload (gambar teknik)
    $drawing_data = null;
    $drawing_filename = null;
    
    if (isset($_FILES['technical_drawing']) && $_FILES['technical_drawing']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
        $file_type = $_FILES['technical_drawing']['type'];
        $file_size = $_FILES['technical_drawing']['size'];
        
        if (in_array($file_type, $allowed_types) && $file_size <= 5000000) { // Max 5MB
            $drawing_filename = $_FILES['technical_drawing']['name'];
            $drawing_data = addslashes(file_get_contents($_FILES['technical_drawing']['tmp_name']));
        }
    }
    
    $query = "INSERT INTO custom_orders 
              (order_number, buyer, seller, product_name, specifications, quantity, technical_drawing, drawing_filename, status) 
              VALUES 
              ('$order_number', '$buyer', '$seller', '$product_name', '$specifications', '$quantity', '$drawing_data', '$drawing_filename', 'pending')";
    
    if (mysqli_query($conn, $query)) {
        echo "<script>
                alert('✅ Pesanan berhasil dibuat!\\n\\nNomor Order: $order_number\\n\\nMenunggu approval dari penjual.');
                window.location='my_orders.php';
              </script>";
    } else {
        echo "<script>alert('❌ Gagal membuat pesanan: " . mysqli_error($conn) . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Custom Order - ERP UMKM</title>
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
            max-width: 800px;
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
            display: flex;
            align-items: center;
            gap: 10px;
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
        
        .form-container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        
        .info-box strong {
            display: block;
            margin-bottom: 5px;
            color: #1565c0;
        }
        
        .info-box p {
            color: #555;
            font-size: 14px;
            line-height: 1.6;
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
        
        .required {
            color: #e74c3c;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: inherit;
        }
        
        textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }
        
        .file-input-wrapper input[type=file] {
            position: absolute;
            left: -9999px;
        }
        
        .file-input-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 12px 15px;
            background: #f8f9ff;
            border: 2px dashed #667eea;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            color: #667eea;
            font-weight: 600;
        }
        
        .file-input-label:hover {
            background: #667eea;
            color: white;
        }
        
        .file-name {
            margin-top: 8px;
            font-size: 13px;
            color: #777;
            font-style: italic;
        }
        
        .file-info {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }
        
        .example-box {
            background: #fff8e1;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
        }
        
        .example-box strong {
            display: block;
            margin-bottom: 8px;
            color: #f57c00;
        }
        
        .example-box ul {
            margin-left: 20px;
            color: #666;
            font-size: 13px;
            line-height: 1.8;
        }
        
        button {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .grid-2 {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2>🎨 Custom Order</h2>
        <a href="dashboard.php" class="btn-back">🏠 Dashboard</a>
    </div>
    
    <div class="form-container">
        <div class="info-box">
            <strong>💡 Informasi Custom Order</strong>
            <p>Isi form di bawah dengan detail spesifikasi produk yang Anda inginkan. Penjual akan meninjau pesanan Anda dan memberikan estimasi harga & waktu pengerjaan.</p>
        </div>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>🏭 Pilih Penjual/Manufaktur <span class="required">*</span></label>
                <select name="seller" required>
                    <option value="">-- Pilih Penjual --</option>
                    <?php while ($seller_row = mysqli_fetch_assoc($sellers_query)): ?>
                        <option value="<?php echo $seller_row['seller']; ?>">
                            <?php echo $seller_row['seller']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="grid-2">
                <div class="form-group">
                    <label>📦 Nama Produk <span class="required">*</span></label>
                    <input type="text" name="product_name" required placeholder="Contoh: Poros Custom 50mm">
                </div>
                
                <div class="form-group">
                    <label>🔢 Jumlah (Unit) <span class="required">*</span></label>
                    <input type="number" name="quantity" required min="1" placeholder="Contoh: 10">
                </div>
            </div>
            
            <div class="form-group">
                <label>📝 Spesifikasi Detail <span class="required">*</span></label>
                <textarea name="specifications" required placeholder="Jelaskan detail spesifikasi produk yang Anda inginkan..."></textarea>
                
                <div class="example-box">
                    <strong>💡 Contoh Spesifikasi yang Baik:</strong>
                    <ul>
                        <li><strong>Material:</strong> Stainless Steel 304 / Mild Steel / Aluminium</li>
                        <li><strong>Dimensi:</strong> Panjang 500mm, Diameter 50mm, Tebal 10mm</li>
                        <li><strong>Finishing:</strong> Polished / Galvanis / Cat</li>
                        <li><strong>Toleransi:</strong> ±0.1mm (jika diperlukan)</li>
                        <li><strong>Proses:</strong> Bubut, Milling, Welding, dll</li>
                        <li><strong>Detail Khusus:</strong> Ulir M10 di ujung, lubang baut 8xM12, dll</li>
                    </ul>
                </div>
            </div>
            
            <div class="form-group">
                <label>📎 Upload Gambar Teknik (Opsional)</label>
                <div class="file-input-wrapper">
                    <input type="file" name="technical_drawing" id="technical_drawing" accept="image/*,.pdf" onchange="showFileName()">
                    <label for="technical_drawing" class="file-input-label">
                        <span>📁</span>
                        <span>Klik untuk upload gambar/PDF</span>
                    </label>
                </div>
                <div id="file-name" class="file-name"></div>
                <div class="file-info">Format: JPG, PNG, PDF | Max: 5MB</div>
            </div>
            
            <button type="submit" name="submit_order">🚀 Kirim Pesanan</button>
        </form>
    </div>
</div>

<script>
function showFileName() {
    const input = document.getElementById('technical_drawing');
    const fileNameDiv = document.getElementById('file-name');
    
    if (input.files.length > 0) {
        const fileName = input.files[0].name;
        const fileSize = (input.files[0].size / 1024 / 1024).toFixed(2);
        fileNameDiv.innerHTML = `✅ File: <strong>${fileName}</strong> (${fileSize} MB)`;
        fileNameDiv.style.color = '#27ae60';
    } else {
        fileNameDiv.innerHTML = '';
    }
}
</script>

</body>
</html>