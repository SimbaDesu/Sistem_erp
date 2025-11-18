<?php
session_start();

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['username'])) {
    header("Location: dashboard.php");
    exit;
}

$users = [
    "owner" => ["password" => "123", "role" => "owner"],
    "pembeli" => ["password" => "123", "role" => "pembeli"]
];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];

    if (isset($users[$username]) && $users[$username]["password"] == $password) {
        $_SESSION["username"] = $username;
        $_SESSION["role"] = $users[$username]["role"];
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Username atau password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ERP UMKM</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 420px;
        }
        
        .logo {
            text-align: center;
            font-size: 64px;
            margin-bottom: 10px;
        }
        
        h2 {
            color: #333;
            text-align: center;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .subtitle {
            text-align: center;
            color: #777;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 600;
            font-size: 14px;
        }
        
        input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: inherit;
        }
        
        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        .error {
            background: #fee;
            color: #c33;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
            border: 1px solid #fcc;
        }
        
        .demo-info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 13px;
            color: #1565c0;
            border: 1px solid #90caf9;
        }
        
        .demo-info strong {
            display: block;
            margin-bottom: 8px;
        }
        
        .demo-account {
            background: white;
            padding: 8px 12px;
            border-radius: 5px;
            margin: 5px 0;
            font-family: 'Courier New', monospace;
        }
        
        .info-note {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 12px;
            margin-top: 15px;
            border-radius: 5px;
            font-size: 12px;
            color: #856404;
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="logo">🏢</div>
    <h2>Login ERP UMKM</h2>
    <p class="subtitle">Sistem Manajemen Bisnis untuk UMKM</p>
    
    <?php if (isset($error)): ?>
        <div class="error">❌ <?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label>👤 Username</label>
            <input type="text" name="username" required placeholder="Masukkan username" autocomplete="off">
        </div>
        
        <div class="form-group">
            <label>🔑 Password</label>
            <input type="password" name="password" required placeholder="Masukkan password">
        </div>
        
        <button type="submit">🔐 Login</button>
    </form>
    
    <div class="demo-info">
        <strong>🔑 Demo Akun:</strong>
        <div class="demo-account">
            👨‍💼 Owner: <strong>owner</strong> / <strong>123</strong>
        </div>
        <div class="demo-account">
            🛒 Pembeli: <strong>pembeli</strong> / <strong>123</strong>
        </div>
        
        <div class="info-note">
            💡 <strong>Update:</strong> Role Penjual & Operator digabung jadi <strong>Owner</strong>
        </div>
    </div>
</div>

</body>
</html>