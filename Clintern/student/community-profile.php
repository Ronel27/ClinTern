<?php
// ══════════════════════════════════════
//  WMSU OESCD — Student Community Profile
// ══════════════════════════════════════
require_once __DIR__ . '/../config/database.php';
session_start();

// Auth Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../auth/login.php?role=student');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = 'info';

// 1. Fetch Student ID and existing profile
$stmt = $pdo->prepare("SELECT student_id, first_name FROM students WHERE user_id = ?");
$stmt->execute([$user_id]);
$student = $stmt->fetch();
$student_id = $student['student_id'];

// Check if profile exists
$stmt_profile = $pdo->prepare("SELECT * FROM community_profiles WHERE student_id = ?");
$stmt_profile->execute([$student_id]);
$existing_profile = $stmt_profile->fetch();

/**
 * 2. HANDLE FORM SUBMISSION
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    $employment = $_POST['employment_status'];
    $education = $_POST['education_level'];
    $income = $_POST['monthly_income'];

    if ($existing_profile) {
        // UPDATE
        $sql = "UPDATE community_profiles SET employment_status = ?, education_level = ?, monthly_income = ? WHERE student_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$employment, $education, $income, $student_id]);
        $message = "Profile updated successfully!";
    } else {
        // INSERT
        $sql = "INSERT INTO community_profiles (student_id, employment_status, education_level, monthly_income) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$student_id, $employment, $education, $income]);
        $message = "Community profile submitted successfully!";
    }
    
    // Refresh local data
    $stmt_profile->execute([$student_id]);
    $existing_profile = $stmt_profile->fetch();
    $message_type = 'success';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Community Profile | WMSU OESCD</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --bg-body: #0a0c10;
            --sidebar-bg: #111419;
            --card-bg: #14171c;
            --gold: #f1b933;
            --border: #1e2229;
            --text-muted: #64748b;
        }

        body {
            background-color: var(--bg-body);
            color: #ffffff;
            font-family: 'Inter', sans-serif;
            margin: 0;
            display: flex;
        }

        /* Sidebar - Exact Match */
        .sidebar {
            width: 240px;
            height: 100vh;
            background: var(--sidebar-bg);
            border-right: 1px solid var(--border);
            padding: 20px;
            position: fixed;
            z-index: 1000;
        }

        .sidebar-brand {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            color: var(--gold);
            margin-bottom: 40px;
            font-size: 1.2rem;
            display: block;
            text-decoration: none;
        }

        .nav-link {
            color: var(--text-muted);
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: 0.2s;
        }

        .nav-link.active {
            background: rgba(241, 185, 51, 0.1);
            color: var(--gold);
            font-weight: 600;
        }

        .nav-link:hover:not(.active) {
            color: white;
            background: rgba(255, 255, 255, 0.05);
        }

        .main-wrapper { margin-left: 240px; width: calc(100% - 240px); }

        .top-nav {
            height: 70px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding: 0 40px;
        }

        .content-area { padding: 40px; max-width: 800px; }

        .portal-badge {
            display: inline-block;
            background: rgba(241, 185, 51, 0.1);
            color: var(--gold);
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1px;
            margin-bottom: 15px;
            border-left: 3px solid var(--gold);
        }

        h1.page-title {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: 2.8rem;
            letter-spacing: -1px;
            margin-bottom: 10px;
        }

        /* Form Styling */
        .profile-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 40px;
        }

        .form-label {
            color: var(--text-muted);
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }

        .form-control, .form-select {
            background: #0a0c10;
            border: 1px solid var(--border);
            color: white;
            padding: 12px;
            border-radius: 8px;
        }

        .form-control:focus, .form-select:focus {
            background: #000;
            border-color: var(--gold);
            color: white;
            box-shadow: none;
        }

        .btn-gold {
            background: var(--gold);
            color: black;
            font-weight: 700;
            border: none;
            padding: 14px;
            border-radius: 8px;
            text-transform: uppercase;
            font-size: 0.9rem;
            width: 100%;
            margin-top: 20px;
        }

        .status-pill {
            font-size: 0.7rem;
            padding: 2px 8px;
            border-radius: 4px;
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            margin-left: 10px;
        }
    </style>
</head>
<body>

    <aside class="sidebar">
        <a href="dashboard.php" class="sidebar-brand">WMSU OESCD</a>
        <nav>
            <a href="dashboard.php" class="nav-link">🏠 Dashboard</a>
            <a href="enroll.php" class="nav-link">📝 Enroll Now</a>
            <a href="my-enrollments.php" class="nav-link">📄 My Enrollments</a>
            <a href="community-profile.php" class="nav-link active">📊 Community Profile</a>
            <a href="settings.php" class="nav-link">⚙️ Profile Settings</a>
        </nav>
    </aside>

    <div class="main-wrapper">
        <header class="top-nav">
            <span style="font-size: 0.9rem; color: var(--text-white); margin-right: 20px;">
                <?= htmlspecialchars($student['first_name'] ?? 'Student') ?>
            </span>
            <a href="../auth/logout.php" class="btn btn-sm btn-outline-danger">Logout</a>
        </header>

        <main class="content-area">
            <span class="portal-badge">✦ DEMOGRAPHICS</span>
            <h1 class="page-title">Community Profile 
                <?php if($existing_profile): ?><span class="status-pill">✓ COMPLETED</span><?php endif; ?>
            </h1>
            <p class="text-white mb-5">This information helps us better understand our community impact and tailor our extension services.</p>

            <?php if($message): ?>
                <div class="alert alert-<?= $message_type ?> bg-dark text-white border-secondary mb-4">
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <div class="profile-card">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-12 mb-4">
                            <label class="form-label">Employment Status</label>
                            <select name="employment_status" class="form-select" required>
                                <option value="" disabled <?= !$existing_profile ? 'selected' : '' ?>>Select status...</option>
                                <option value="Employed" <?= ($existing_profile && $existing_profile['employment_status'] == 'Employed') ? 'selected' : '' ?>>Employed</option>
                                <option value="Unemployed" <?= ($existing_profile && $existing_profile['employment_status'] == 'Unemployed') ? 'selected' : '' ?>>Unemployed</option>
                                <option value="Self-Employed" <?= ($existing_profile && $existing_profile['employment_status'] == 'Self-Employed') ? 'selected' : '' ?>>Self-Employed</option>
                                <option value="Student" <?= ($existing_profile && $existing_profile['employment_status'] == 'Student') ? 'selected' : '' ?>>Student</option>
                            </select>
                        </div>

                        <div class="col-md-12 mb-4">
                            <label class="form-label">Highest Educational Attainment</label>
                            <input type="text" name="education_level" class="form-control" 
                                   placeholder="e.g. College Graduate, Senior High School" 
                                   value="<?= $existing_profile ? htmlspecialchars($existing_profile['education_level']) : '' ?>" required>
                        </div>

                        <div class="col-md-12 mb-4">
                            <label class="form-label">Estimated Monthly Income (PHP)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-white">₱</span>
                                <input type="number" step="0.01" name="monthly_income" class="form-control" 
                                       value="<?= $existing_profile ? htmlspecialchars($existing_profile['monthly_income']) : '0.00' ?>" required>
                            </div>
                        </div>
                    </div>

                    <button type="submit" name="save_profile" class="btn btn-gold">
                        <?= $existing_profile ? 'Update Profile Information' : 'Submit Profile' ?>
                    </button>
                </form>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>