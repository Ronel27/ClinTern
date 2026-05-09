<?php
// ══════════════════════════════════════════════════════════════════════════
//  WMSU OESCD — Unified Login Portal (With Back & Toggle)
// ══════════════════════════════════════════════════════════════════════════
require_once __DIR__ . '/../config/database.php';
session_start();

$role_requested = isset($_GET['role']) ? $_GET['role'] : 'student';

// 1. AUTO-REPAIR (Ensures admin@gmail.com is ready)
$target_pass = 'admin123';
$new_hash = password_hash($target_pass, PASSWORD_DEFAULT);

$repairSQL = "INSERT INTO users (name, email, password_hash, role, is_active) 
              VALUES ('Admin User', 'admin@gmail.com', :hash1, 'admin', 1)
              ON DUPLICATE KEY UPDATE password_hash = :hash2, role = 'admin', is_active = 1";
$repairStmt = $pdo->prepare($repairSQL);
$repairStmt->execute(['hash1' => $new_hash, 'hash2' => $new_hash]);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$email, $role_requested]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role']    = $user['role'];
            $_SESSION['name']    = $user['name'];

            $dest = ($user['role'] === 'admin') ? '../admin/dashboard.php' : '../student/dashboard.php';
            header("Location: $dest");
            exit;
        } else {
            $error = "Invalid email or password for the " . ucfirst($role_requested) . " portal.";
        }
    } catch (PDOException $e) {
        $error = "Database Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ucfirst($role_requested); ?> Login | WMSU</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Syne:wght@800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --bg: #0a0c10; --gold: #f1b933; --border: #1e2229; --gray: #94a3b8; }
        body { background: var(--bg); color: white; font-family: 'Inter', sans-serif; height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; overflow: hidden; }
        .grid-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-image: linear-gradient(to right, rgba(255,255,255,0.02) 1px, transparent 1px), linear-gradient(to bottom, rgba(255,255,255,0.02) 1px, transparent 1px); background-size: 50px 50px; z-index: -1; }
        
        .login-card { background: rgba(30, 41, 59, 0.4); backdrop-filter: blur(12px); border: 1px solid var(--border); padding: 40px; border-radius: 16px; width: 100%; max-width: 400px; box-shadow: 0 25px 50px rgba(0,0,0,0.5); position: relative; }
        
        /* Back Button Style */
        .btn-back { position: absolute; top: 20px; left: 20px; color: var(--gray); text-decoration: none; font-size: 0.75rem; font-weight: 600; display: flex; align-items: center; gap: 5px; transition: 0.2s; }
        .btn-back:hover { color: white; }

        .login-header h2 { font-family: 'Syne', sans-serif; text-transform: uppercase; font-size: 1.4rem; text-align: center; margin-bottom: 5px; margin-top: 10px; letter-spacing: -0.5px; }
        .login-header p { text-align: center; color: var(--gray); font-size: 0.8rem; margin-bottom: 30px; }
        
        .form-control { background: rgba(10, 12, 16, 0.6); border: 1px solid var(--border); color: white; padding: 12px; border-radius: 8px; }
        .form-control:focus { background: rgba(10, 12, 16, 0.8); border-color: var(--gold); color: white; box-shadow: none; }
        
        .btn-gold { background: var(--gold); color: black; font-weight: 800; width: 100%; padding: 12px; border: none; text-transform: uppercase; border-radius: 8px; margin-top: 10px; transition: 0.3s; }
        .btn-gold:hover { background: #ffcc4d; transform: translateY(-2px); }
        
        .error-msg { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: #f87171; padding: 10px; border-radius: 8px; font-size: 0.8rem; margin-bottom: 20px; text-align: center; }
        
        /* Toggle Link Style */
        .portal-toggle { display: block; text-align: center; margin-top: 25px; font-size: 0.85rem; color: var(--gray); text-decoration: none; border-top: 1px solid var(--border); padding-top: 20px; }
        .portal-toggle b { color: var(--gold); }
        .portal-toggle:hover b { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="grid-overlay"></div>
    
    <div class="login-card">
        <a href="../index.php" class="btn-back">← BACK</a>

        <div class="login-header">
            <h2><?php echo ($role_requested === 'admin') ? '🔐 Admin' : '🎓 Student'; ?> Login</h2>
            <p>Please enter your credentials to continue</p>
        </div>

        <?php if($error): ?> <div class="error-msg"><?php echo $error; ?></div> <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="small text-secondary fw-bold mb-1">EMAIL ADDRESS</label>
                <input type="email" name="email" class="form-control" placeholder="user@example.com" required>
            </div>
            <div class="mb-4">
                <label class="small text-secondary fw-bold mb-1">PASSWORD</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-gold">Sign In →</button>
        </form>

        <?php if($role_requested === 'admin'): ?>
            <a href="login.php?role=student" class="portal-toggle">Are you a student? <b>Student Login</b></a>
        <?php else: ?>
            <a href="login.php?role=admin" class="portal-toggle">Are you staff? <b>Admin Portal</b></a>
        <?php endif; ?>
    </div>
</body>
</html>