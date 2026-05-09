<?php
// ══════════════════════════════════════
//  WMSU OESCD — Community Profiles (CRUD)
// ══════════════════════════════════════
require_once __DIR__ . '/../config/database.php';
session_start();

// Auth Check
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: ../auth/login.php?role=admin');
    exit;
}

$message = '';

/**
 * 1. HANDLE CRUD ACTIONS
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // CREATE PROFILE
    if (isset($_POST['add_profile'])) {
        $sid  = $_POST['student_id'];
        $empl = $_POST['employment_status'];
        $edu  = $_POST['education_level'];
        $inc  = $_POST['monthly_income'];

        $stmt = $pdo->prepare("INSERT INTO community_profiles (student_id, employment_status, education_level, monthly_income) VALUES (?, ?, ?, ?)");
        $stmt->execute([$sid, $empl, $edu, $inc]);
        $message = "Community profile created!";
    }

    // UPDATE PROFILE
    if (isset($_POST['update_profile'])) {
        $id   = $_POST['profile_id'];
        $empl = $_POST['employment_status'];
        $edu  = $_POST['education_level'];
        $inc  = $_POST['monthly_income'];

        $stmt = $pdo->prepare("UPDATE community_profiles SET employment_status = ?, education_level = ?, monthly_income = ? WHERE profile_id = ?");
        $stmt->execute([$empl, $edu, $inc, $id]);
        $message = "Profile updated successfully!";
    }

    // DELETE PROFILE
    if (isset($_POST['delete_id'])) {
        $stmt = $pdo->prepare("DELETE FROM community_profiles WHERE profile_id = ?");
        $stmt->execute([$_POST['delete_id']]);
        $message = "Profile deleted.";
    }
}

/**
 * 2. FETCH DATA
 */
// Join with students to get names
$profiles = $pdo->query("SELECT cp.*, s.first_name, s.surname 
                         FROM community_profiles cp
                         JOIN students s ON cp.student_id = s.student_id
                         ORDER BY s.surname ASC")->fetchAll();

// Get students who don't have a profile yet for the 'Add' dropdown
$eligible_students = $pdo->query("SELECT student_id, first_name, surname 
                                  FROM students 
                                  WHERE student_id NOT IN (SELECT student_id FROM community_profiles)")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Community Profiles | WMSU OESCD</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --bg: #0a0c10; --sidebar-bg: #111419; --card-bg: #14171c; --gold: #f1b933; --border: #1e2229; --text-muted: #64748b; }
        body { background: var(--bg); color: white; font-family: 'Inter', sans-serif; display: flex; margin: 0; }
        
        /* Sidebar - Consistent with your Dashboard */
        .sidebar { width: 240px; height: 100vh; background: var(--sidebar-bg); border-right: 1px solid var(--border); padding: 20px; position: fixed; }
        .sidebar-brand { font-family: 'Syne', sans-serif; font-weight: 800; color: var(--gold); font-size: 1.1rem; text-decoration: none; display: block; margin-bottom: 40px; }
        .nav-link { color: var(--text-muted); padding: 10px 15px; border-radius: 6px; margin-bottom: 4px; display: flex; align-items: center; gap: 10px; text-decoration: none; font-size: 0.85rem; }
        .nav-link.active { background: rgba(241, 185, 51, 0.1); color: var(--gold); font-weight: 600; }
        .nav-link:hover:not(.active) { color: white; background: rgba(255, 255, 255, 0.05); }

        .main-wrapper { margin-left: 240px; width: calc(100% - 240px); min-height: 100vh; }
        .content-area { padding: 40px; max-width: 1200px; }

        h1.page-title { font-family: 'Syne', sans-serif; font-weight: 800; font-size: 2.2rem; margin-bottom: 30px; }
        .card-main { background: var(--card-bg); border: 1px solid var(--border); padding: 25px; border-radius: 12px; }
        .btn-gold { background: var(--gold); color: black; font-weight: 700; border: none; }

        /* Table Styling */
        .custom-table { width: 100%; color: white; border-collapse: collapse; }
        .custom-table th { color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; padding: 15px; border-bottom: 1px solid var(--border); }
        .custom-table td { padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 0.9rem; }

        /* Modal Visibility Fixes */
        .modal-content { background: #1a1d23; border: 1px solid var(--border); color: white; }
        .form-label { color: #ffffff !important; font-weight: 600; font-size: 0.8rem; text-transform: uppercase; }
        .form-control, .form-select { background: #0a0c10; border: 1px solid var(--border); color: #ffffff !important; }
        .form-control:focus, .form-select:focus { background: #000; border-color: var(--gold); box-shadow: none; color: white; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <a href="dashboard.php" class="sidebar-brand">WMSU <span style="font-weight:400; font-size:0.8rem;">ADMIN</span></a>
        <nav>
            <a href="dashboard.php" class="nav-link">📊 Dashboard</a>
            <a href="enrollments.php" class="nav-link">📋 Enrollments</a>
            <a href="community.php" class="nav-link active">👥 Community Profiles</a>
            <a href="courses.php" class="nav-link">📚 Courses</a>
            <a href="schedules.php" class="nav-link">📅 Schedules</a>
            <a href="students.php" class="nav-link">📄 Student Records</a>
            <a href="reports.php" class="nav-link">📉 Reports</a>
            <a href="users.php" class="nav-link">👥 User Management</a>
            <a href="audit.php" class="nav-link">📜 Audit Logs</a>
            <a href="../auth/logout.php" class="nav-link text-danger">🚪 Logout</a>
        </nav>
    </aside>

    <div class="main-wrapper">
        <main class="content-area">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="page-title m-0">Community Profiles</h1>
                <button class="btn btn-gold px-4" data-bs-toggle="modal" data-bs-target="#addModal">+ Add Profile</button>
            </div>

            <?php if($message): ?>
                <div class="alert alert-success bg-success text-white border-0 py-2 small mb-4"><?= $message ?></div>
            <?php endif; ?>

            <div class="card-main">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Employment</th>
                            <th>Education</th>
                            <th>Monthly Income</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($profiles as $p): ?>
                        <tr>
                            <td class="fw-bold"><?= htmlspecialchars($p['surname'] . ', ' . $p['first_name']) ?></td>
                            <td><?= htmlspecialchars($p['employment_status']) ?></td>
                            <td><?= htmlspecialchars($p['education_level']) ?></td>
                            <td class="text-gold fw-bold">₱<?= number_format($p['monthly_income'], 2) ?></td>
                            <td>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-outline-light" onclick='editProfile(<?= json_encode($p) ?>)'>Edit</button>
                                    <form method="POST" onsubmit="return confirm('Remove this community profile?')">
                                        <input type="hidden" name="delete_id" value="<?= $p['profile_id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title font-syne">New Community Entry</h5>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Student</label>
                        <select name="student_id" class="form-select" required>
                            <option value="">-- Choose Student --</option>
                            <?php foreach($eligible_students as $es): ?>
                                <option value="<?= $es['student_id'] ?>"><?= $es['surname'] ?>, <?= $es['first_name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Employment Status</label>
                        <select name="employment_status" class="form-select">
                            <option value="Employed">Employed</option>
                            <option value="Unemployed">Unemployed</option>
                            <option value="Self-Employed">Self-Employed</option>
                            <option value="Student">Student</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Education Level</label>
                        <input type="text" name="education_level" class="form-control" placeholder="e.g. College Graduate">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Monthly Income (PHP)</label>
                        <input type="number" step="0.01" name="monthly_income" class="form-control" value="0.00">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_profile" class="btn btn-gold px-4">Save Profile</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                <input type="hidden" name="profile_id" id="edit_pid">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title font-syne">Edit Community Profile</h5>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Student</label>
                        <input type="text" id="edit_sname" class="form-control" readonly style="opacity: 0.6;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Employment Status</label>
                        <select name="employment_status" id="edit_empl" class="form-select">
                            <option value="Employed">Employed</option>
                            <option value="Unemployed">Unemployed</option>
                            <option value="Self-Employed">Self-Employed</option>
                            <option value="Student">Student</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Education Level</label>
                        <input type="text" name="education_level" id="edit_edu" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Monthly Income (PHP)</label>
                        <input type="number" step="0.01" name="monthly_income" id="edit_inc" class="form-control">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_profile" class="btn btn-gold px-4">Update Profile</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const editModal = new bootstrap.Modal(document.getElementById('editModal'));
        function editProfile(p) {
            document.getElementById('edit_pid').value = p.profile_id;
            document.getElementById('edit_sname').value = p.first_name + " " + p.surname;
            document.getElementById('edit_empl').value = p.employment_status;
            document.getElementById('edit_edu').value = p.education_level;
            document.getElementById('edit_inc').value = p.monthly_income;
            editModal.show();
        }
    </script>
</body>
</html>