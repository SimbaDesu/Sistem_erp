<?php
/**
 * Migration Script: Katalog Produk → Template Produk
 * Run this ONCE to migrate database schema
 */

include 'koneksi.php';

// Security: Uncomment untuk production (agar tidak bisa diakses sembarangan)
// session_start();
// if (!isset($_SESSION['username']) || $_SESSION['role'] != 'owner') {
//     die('⛔ Access denied! Only owner can run migration.');
// }

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migration - Template Produk</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Courier New', monospace;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 40px;
            line-height: 1.6;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: #252526;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        h1 {
            color: #4ec9b0;
            margin-bottom: 10px;
            font-size: 28px;
        }
        h2 {
            color: #569cd6;
            margin: 25px 0 15px 0;
            font-size: 18px;
            border-bottom: 2px solid #3c3c3c;
            padding-bottom: 8px;
        }
        .subtitle {
            color: #858585;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .log-box {
            background: #1e1e1e;
            border: 1px solid #3c3c3c;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
            max-height: 400px;
            overflow-y: auto;
        }
        .log-item {
            padding: 8px 0;
            border-bottom: 1px solid #2d2d2d;
        }
        .log-item:last-child {
            border-bottom: none;
        }
        .success {
            color: #4ec9b0;
        }
        .error {
            color: #f48771;
        }
        .warning {
            color: #dcdcaa;
        }
        .info {
            color: #569cd6;
        }
        .query-preview {
            background: #1e1e1e;
            border-left: 3px solid #569cd6;
            padding: 10px 15px;
            margin: 10px 0;
            font-size: 13px;
            color: #ce9178;
        }
        .btn {
            background: #0e639c;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background: #1177bb;
        }
        .btn-danger {
            background: #c5392a;
        }
        .btn-danger:hover {
            background: #e03e2d;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 13px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #3c3c3c;
        }
        th {
            background: #1e1e1e;
            color: #4ec9b0;
            font-weight: bold;
        }
        tr:hover {
            background: #2d2d2d;
        }
        .actions {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #3c3c3c;
            display: flex;
            gap: 15px;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>🗄️ Database Migration</h1>
    <p class="subtitle">Migrasi dari Katalog Produk (Stock-based) → Template Produk (Make-to-Order)</p>

    <?php
    // Check if migration already run
    $check_column = mysqli_query($conn, "SHOW COLUMNS FROM products LIKE 'is_template'");
    $already_migrated = mysqli_num_rows($check_column) > 0;

    if ($already_migrated && !isset($_GET['force'])) {
        echo '<div class="log-box">';
        echo '<div class="log-item warning">⚠️ Migration sudah pernah dijalankan sebelumnya!</div>';
        echo '<div class="log-item info">ℹ️ Kolom "is_template" sudah ada di database.</div>';
        echo '</div>';
        
        echo '<div class="actions">';
        echo '<a href="?force=1" class="btn btn-danger">🔄 Run Ulang (Force)</a>';
        echo '<a href="dashboard.php" class="btn">← Kembali ke Dashboard</a>';
        echo '</div>';
    } else {
        if (isset($_GET['force'])) {
            echo '<div class="log-box">';
            echo '<div class="log-item warning">🔄 Running migration in FORCE mode...</div>';
            echo '</div>';
        }

        echo '<h2>📋 Migration Steps</h2>';
        echo '<div class="log-box">';

        // Check existing columns first
        $existing_columns = [];
        $check_cols = mysqli_query($conn, "SHOW COLUMNS FROM products");
        while ($col = mysqli_fetch_assoc($check_cols)) {
            $existing_columns[] = $col['Field'];
        }
        
        $queries = [];
        
        // Backup
        $queries[] = [
            'name' => 'Backup existing data',
            'sql' => "CREATE TABLE IF NOT EXISTS products_backup_" . date('Ymd_His') . " AS SELECT * FROM products",
            'critical' => false
        ];
        
        // Add columns only if not exist
        if (!in_array('is_template', $existing_columns)) {
            $queries[] = [
                'name' => 'Add column: is_template',
                'sql' => "ALTER TABLE products ADD COLUMN is_template BOOLEAN DEFAULT 1 COMMENT 'Template produk (1) atau ready stock (0)'",
                'critical' => true
            ];
        }
        
        if (!in_array('estimated_days', $existing_columns)) {
            $queries[] = [
                'name' => 'Add column: estimated_days',
                'sql' => "ALTER TABLE products ADD COLUMN estimated_days VARCHAR(20) DEFAULT NULL COMMENT 'Estimasi waktu pengerjaan'",
                'critical' => true
            ];
        }
        
        if (!in_array('base_price', $existing_columns)) {
            $queries[] = [
                'name' => 'Add column: base_price',
                'sql' => "ALTER TABLE products ADD COLUMN base_price DECIMAL(15,2) DEFAULT NULL COMMENT 'Harga dasar untuk estimasi'",
                'critical' => true
            ];
        }
        
        // Modify stock column
        $queries[] = [
            'name' => 'Modify column: stock (make nullable)',
            'sql' => "ALTER TABLE products MODIFY COLUMN stock INT DEFAULT NULL COMMENT 'NULL untuk template, angka untuk ready stock'",
            'critical' => true
        ];
        
        // Update existing data
        $queries[] = [
            'name' => 'Update existing data',
            'sql' => "UPDATE products SET is_template = 1, base_price = price, estimated_days = '3-5 hari', stock = NULL WHERE 1=1",
            'critical' => true
        ];
        
        // Add indexes (skip if exists)
        $queries[] = [
            'name' => 'Add index: idx_is_template',
            'sql' => "ALTER TABLE products ADD INDEX idx_is_template (is_template)",
            'critical' => false
        ];
        
        $queries[] = [
            'name' => 'Add index: idx_seller_template',
            'sql' => "ALTER TABLE products ADD INDEX idx_seller_template (seller, is_template)",
            'critical' => false
        ];

        $success_count = 0;
        $error_count = 0;

        foreach ($queries as $index => $query) {
            $step = $index + 1;
            echo '<div class="log-item">';
            echo "<strong class='info'>[Step {$step}/{count($queries)}]</strong> {$query['name']}<br>";
            
            // Execute query
            $result = mysqli_query($conn, $query['sql']);
            
            if ($result) {
                echo "<span class='success'>✓ Success</span>";
                $success_count++;
                
                // Show affected rows for UPDATE queries
                if (stripos($query['sql'], 'UPDATE') !== false) {
                    $affected = mysqli_affected_rows($conn);
                    echo " <span class='info'>({$affected} rows affected)</span>";
                }
            } else {
                $error_msg = mysqli_error($conn);
                
                // Check if error is because column already exists
                if (stripos($error_msg, 'Duplicate column') !== false) {
                    echo "<span class='warning'>⚠️ Already exists (skipped)</span>";
                    $success_count++;
                } else {
                    echo "<span class='error'>✗ Error: {$error_msg}</span>";
                    $error_count++;
                    
                    if ($query['critical']) {
                        echo "<br><span class='error'>⛔ Critical error! Migration stopped.</span>";
                        break;
                    }
                }
            }
            
            echo '</div>';
        }

        echo '</div>';

        // Summary
        echo '<h2>📊 Migration Summary</h2>';
        echo '<div class="log-box">';
        echo "<div class='log-item'>";
        echo "<span class='success'>✓ Success: {$success_count}</span><br>";
        echo "<span class='error'>✗ Errors: {$error_count}</span><br>";
        
        if ($error_count == 0) {
            echo "<br><strong class='success'>🎉 Migration completed successfully!</strong>";
        } else {
            echo "<br><strong class='error'>⚠️ Migration completed with errors. Please check above.</strong>";
        }
        echo "</div>";
        echo '</div>';

        // Verification
        echo '<h2>🔍 Verification</h2>';
        
        // Show table structure
        $structure = mysqli_query($conn, "DESCRIBE products");
        if ($structure) {
            echo '<table>';
            echo '<tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th><th>Comment</th></tr>';
            while ($row = mysqli_fetch_assoc($structure)) {
                $highlight = in_array($row['Field'], ['is_template', 'estimated_days', 'base_price', 'stock']) ? 'success' : '';
                echo "<tr class='{$highlight}'>";
                echo "<td><strong>{$row['Field']}</strong></td>";
                echo "<td>{$row['Type']}</td>";
                echo "<td>{$row['Null']}</td>";
                echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
                echo "<td style='font-size:11px;color:#858585;'>" . ($row['Comment'] ?? '-') . "</td>";
                echo "</tr>";
            }
            echo '</table>';
        }

        // Show sample data
        echo '<h2>📄 Sample Data (After Migration)</h2>';
        $sample = mysqli_query($conn, "SELECT id, product_name, price, base_price, stock, estimated_days, is_template FROM products LIMIT 5");
        
        if ($sample && mysqli_num_rows($sample) > 0) {
            echo '<table>';
            echo '<tr><th>ID</th><th>Product Name</th><th>Price</th><th>Base Price</th><th>Stock</th><th>Est. Days</th><th>Template?</th></tr>';
            while ($row = mysqli_fetch_assoc($sample)) {
                echo "<tr>";
                echo "<td>{$row['id']}</td>";
                echo "<td>{$row['product_name']}</td>";
                echo "<td>Rp " . number_format($row['price'], 0, ',', '.') . "</td>";
                echo "<td>Rp " . number_format($row['base_price'] ?? 0, 0, ',', '.') . "</td>";
                echo "<td>" . ($row['stock'] ?? '<span class="warning">NULL</span>') . "</td>";
                echo "<td>{$row['estimated_days']}</td>";
                echo "<td>" . ($row['is_template'] ? '<span class="success">Yes</span>' : 'No') . "</td>";
                echo "</tr>";
            }
            echo '</table>';
        } else {
            echo '<div class="log-box"><div class="log-item warning">⚠️ No products found in database</div></div>';
        }

        echo '<div class="actions">';
        echo '<a href="dashboard.php" class="btn">✓ Lanjut ke Step 2: Update Dashboard</a>';
        echo '<a href="view_product.php" class="btn">👁️ Lihat Template Produk</a>';
        echo '</div>';
    }
    ?>

</div>

</body>
</html>