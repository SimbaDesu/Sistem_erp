<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portfolio Produk - ERP UMKM</title>
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
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .header p {
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
        
        .info-banner {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border-left: 4px solid #2196f3;
            padding: 20px 25px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .info-banner h3 {
            color: #1565c0;
            font-size: 16px;
            margin-bottom: 8px;
        }
        
        .info-banner p {
            color: #1976d2;
            font-size: 13px;
            line-height: 1.6;
        }
        
        .portfolio-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }
        
        .portfolio-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        
        .portfolio-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .portfolio-image {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 72px;
            position: relative;
        }
        
        .portfolio-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255,255,255,0.95);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            color: #667eea;
        }
        
        .portfolio-content {
            padding: 25px;
        }
        
        .portfolio-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 12px;
        }
        
        .portfolio-specs {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .spec-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            font-size: 13px;
        }
        
        .spec-label {
            color: #777;
        }
        
        .spec-value {
            font-weight: 600;
            color: #333;
        }
        
        .portfolio-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 2px solid #f0f0f0;
        }
        
        .portfolio-price {
            font-size: 22px;
            font-weight: bold;
            color: #667eea;
        }
        
        .price-label {
            font-size: 11px;
            color: #999;
            display: block;
            margin-top: 3px;
        }
        
        .btn-order {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .btn-order:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        @media (max-width: 768px) {
            .portfolio-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <div>
            <h1>📐 Portfolio Produk</h1>
            <p>Referensi produk yang bisa kami buat untuk Anda</p>
        </div>
        <a href="dashboard.php" class="btn-back">← Dashboard</a>
    </div>
    
    <div class="info-banner">
        <h3>💡 Ini adalah Portfolio/Referensi</h3>
        <p>
            Produk di bawah ini adalah <strong>contoh/referensi</strong> dari produk yang bisa kami buat. 
            Setiap produk dapat di-<strong>customize</strong> sesuai kebutuhan Anda (ukuran, material, finishing, dll). 
            Harga yang tertera adalah estimasi awal dan bisa berubah sesuai spesifikasi final.
        </p>
    </div>
    
    <div class="portfolio-grid">
        <!-- Portfolio Card 1 -->
        <div class="portfolio-card">
            <div class="portfolio-image">
                ⚙️
                <span class="portfolio-badge">Referensi</span>
            </div>
            <div class="portfolio-content">
                <div class="portfolio-title">Poros Custom CNC</div>
                
                <div class="portfolio-specs">
                    <div class="spec-item">
                        <span class="spec-label">Material:</span>
                        <span class="spec-value">Stainless Steel 304</span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label">Diameter:</span>
                        <span class="spec-value">50mm (customizable)</span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label">Panjang:</span>
                        <span class="spec-value">500mm (customizable)</span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label">Finishing:</span>
                        <span class="spec-value">Polished</span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label">Estimasi:</span>
                        <span class="spec-value">3-5 hari kerja</span>
                    </div>
                </div>
                
                <div class="portfolio-footer">
                    <div>
                        <div class="portfolio-price">Rp 2.500.000</div>
                        <span class="price-label">Harga mulai dari</span>
                    </div>
                    <a href="custom_order.php?ref=poros-cnc" class="btn-order">
                        Pesan Custom →
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Portfolio Card 2 -->
        <div class="portfolio-card">
            <div class="portfolio-image">
                🔩
                <span class="portfolio-badge">Referensi</span>
            </div>
            <div class="portfolio-content">
                <div class="portfolio-title">Flange Custom</div>
                
                <div class="portfolio-specs">
                    <div class="spec-item">
                        <span class="spec-label">Material:</span>
                        <span class="spec-value">Mild Steel</span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label">Ukuran:</span>
                        <span class="spec-value">Custom sesuai kebutuhan</span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label">Thread:</span>
                        <span class="spec-value">M10 - M20</span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label">Finishing:</span>
                        <span class="spec-value">Painting/Coating</span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label">Estimasi:</span>
                        <span class="spec-value">2-4 hari kerja</span>
                    </div>
                </div>
                
                <div class="portfolio-footer">
                    <div>
                        <div class="portfolio-price">Rp 5.000.000</div>
                        <span class="price-label">Harga mulai dari</span>
                    </div>
                    <a href="custom_order.php?ref=flange" class="btn-order">
                        Pesan Custom →
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Portfolio Card 3 -->
        <div class="portfolio-card">
            <div class="portfolio-image">
                📏
                <span class="portfolio-badge">Referensi</span>
            </div>
            <div class="portfolio-content">
                <div class="portfolio-title">Komponen Mesin Bubut</div>
                
                <div class="portfolio-specs">
                    <div class="spec-item">
                        <span class="spec-label">Material:</span>
                        <span class="spec-value">Bronze / Brass</span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label">Proses:</span>
                        <span class="spec-value">Turning, Threading, Milling</span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label">Toleransi:</span>
                        <span class="spec-value">±0.1mm</span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label">Kompleksitas:</span>
                        <span class="spec-value">Medium - High</span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label">Estimasi:</span>
                        <span class="spec-value">4-6 hari kerja</span>
                    </div>
                </div>
                
                <div class="portfolio-footer">
                    <div>
                        <div class="portfolio-price">Rp 3.000.000</div>
                        <span class="price-label">Harga mulai dari</span>
                    </div>
                    <a href="custom_order.php?ref=komponen-bubut" class="btn-order">
                        Pesan Custom →
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>