<?php
session_start();
include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
}

$userID = $_SESSION['user_id'];

$role = $_SESSION['role'] ?? '';
if ($role === 'super_admin') {
    $role = 'Super Admin';
} elseif ($role === 'manager') {
    $role = 'Manager';
} elseif ($role === 'data_enter') {
    $role = 'Data Enter';
} else {
    $role = 'not set role';
}

// Fetch Program Allocation
$allocated_programs = [];
$query_program = "
    SELECT pt.program_name 
    FROM program_allocation_user pau 
    JOIN program_table pt ON pau.program_code = pt.program_code 
    WHERE pau.user_id = ?";

if ($stmt_prog = $conn->prepare($query_program)) {
    $stmt_prog->bind_param("i", $userID);
    $stmt_prog->execute();
    $stmt_prog->bind_result($fetched_program_name);
    while ($stmt_prog->fetch()) {
        $allocated_programs[] = $fetched_program_name;
    }
    $stmt_prog->close();
}

$message = "";
$messageType = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $message = "New passwords do not match.";
        $messageType = "danger";
    } else {
        $stmt = $conn->prepare("SELECT password FROM admin WHERE id = ?");
        $stmt->bind_param("i", $userID);
        $stmt->execute();
        $stmt->bind_result($stored_hash);
        $stmt->fetch();
        $stmt->close();

        if (password_verify($current_password, $stored_hash)) {
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare("UPDATE admin SET password = ? WHERE id = ?");
            $update_stmt->bind_param("si", $new_hash, $userID);
            if ($update_stmt->execute()) {
                $message = "Password updated successfully.";
                $messageType = "success";
            } else {
                $message = "Error updating password.";
                $messageType = "danger";
            }
            $update_stmt->close();
        } else {
            $message = "Incorrect current password.";
            $messageType = "danger";
        }
    }
}
?>

<!-- Page Wrapper -->
<div id="wrapper">
    <?php include("nav.php"); ?>
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include("includes/topnav.php"); ?>

            <!-- ===================== PROFILE PAGE CONTENT ===================== -->
            <div class="profile-page-wrapper">

                <!-- Page Header -->
                <div class="profile-page-header">
                    <div class="header-left">
                        <div class="breadcrumb-trail">
                            <span class="breadcrumb-home"><i class="fas fa-home"></i></span>
                            <span class="breadcrumb-sep">/</span>
                            <span class="breadcrumb-current">My Profile</span>
                        </div>
                        <h1 class="page-title">Account Settings</h1>
                        <p class="page-subtitle">Manage your profile and security preferences</p>
                    </div>
                    <div class="header-right">
                        <div class="last-login-badge">
                            <i class="fas fa-clock"></i>
                            <span>Last login: Today</span>
                        </div>
                    </div>
                </div>

                <!-- Main Grid -->
                <div class="profile-grid">

                    <!-- ── LEFT COLUMN ── -->
                    <div class="profile-left-col">

                        <!-- Identity Card -->
                        <div class="identity-card">
                            <div class="identity-card-bg"></div>
                            <div class="identity-card-inner">
                                <div class="avatar-ring">
                                    <img
                                        src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['username']); ?>&background=6366f1&color=fff&size=200&bold=true&font-size=0.4"
                                        alt="Avatar"
                                        class="avatar-img">
                                    <div class="avatar-online-dot"></div>
                                </div>
                                <div class="identity-info">
                                    <h2 class="identity-name"><?php echo htmlspecialchars(ucfirst($_SESSION['username'])); ?></h2>
                                    <div class="identity-role-badge">
                                        <?php
                                        $roleIcon = 'fa-user';
                                        if ($_SESSION['role'] === 'super_admin') $roleIcon = 'fa-crown';
                                        elseif ($_SESSION['role'] === 'manager') $roleIcon = 'fa-briefcase';
                                        elseif ($_SESSION['role'] === 'data_enter') $roleIcon = 'fa-database';
                                        ?>
                                        <i class="fas <?php echo $roleIcon; ?>"></i>
                                        <span><?php echo $role; ?></span>
                                    </div>
                                    <div class="identity-status">
                                        <span class="status-dot"></span>
                                        <span class="status-text">Active</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Stats Row -->
                            <div class="identity-stats">
                                <div class="stat-item">
                                    <span class="stat-value"><?php echo count($allocated_programs); ?></span>
                                    <span class="stat-label">Programs</span>
                                </div>
                                <div class="stat-divider"></div>
                                <div class="stat-item">
                                    <span class="stat-value">1</span>
                                    <span class="stat-label">Session</span>
                                </div>
                                <div class="stat-divider"></div>
                                <div class="stat-item">
                                    <span class="stat-value">
                                        <?php echo ($_SESSION['role'] === 'super_admin') ? '∞' : count($allocated_programs); ?>
                                    </span>
                                    <span class="stat-label">Access</span>
                                </div>
                            </div>
                        </div>

                        <!-- Program Allocation Card -->
                        <div class="program-card">
                            <div class="section-label">
                                <div class="section-label-icon">
                                    <i class="fas fa-graduation-cap"></i>
                                </div>
                                <span>Program Allocation</span>
                            </div>

                            <div class="program-list">
                                <?php if (!empty($allocated_programs)): ?>
                                    <?php foreach ($allocated_programs as $index => $prog): ?>
                                        <div class="program-item" style="animation-delay: <?php echo $index * 0.08; ?>s">
                                            <div class="program-icon">
                                                <i class="fas fa-book-open"></i>
                                            </div>
                                            <div class="program-details">
                                                <span class="program-name"><?php echo htmlspecialchars($prog); ?></span>
                                                <span class="program-meta">Enrolled</span>
                                            </div>
                                            <div class="program-badge active">Active</div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php elseif (($_SESSION['role'] ?? '') === 'super_admin'): ?>
                                    <div class="program-super-admin">
                                        <div class="super-admin-icon">
                                            <i class="fas fa-crown"></i>
                                        </div>
                                        <h4>Super Administrator</h4>
                                        <p>You have universal access to all programs across the system.</p>
                                        <div class="access-indicator">
                                            <span class="access-dot"></span>
                                            Full Access Granted
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="program-empty">
                                        <div class="empty-icon">
                                            <i class="fas fa-inbox"></i>
                                        </div>
                                        <h4>No Programs Assigned</h4>
                                        <p>Contact your administrator to get programs allocated to your account.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>

                    <!-- ── RIGHT COLUMN ── -->
                    <div class="profile-right-col">

                        <!-- Change Password Card -->
                        <div class="password-card">
                            <div class="password-card-header">
                                <div class="section-label">
                                    <div class="section-label-icon">
                                        <i class="fas fa-shield-alt"></i>
                                    </div>
                                    <div>
                                        <span class="section-title">Security Settings</span>
                                        <p class="section-desc">Update your password to keep your account secure</p>
                                    </div>
                                </div>
                                <div class="security-score">
                                    <svg class="score-ring" viewBox="0 0 36 36">
                                        <path class="score-ring-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                                        <path class="score-ring-fill" stroke-dasharray="75, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                                    </svg>
                                    <span class="score-label">Strong</span>
                                </div>
                            </div>

                            <!-- Alert messages -->
                            <?php if (!empty($message)): ?>
                                <div class="modern-alert modern-alert-<?php echo $messageType; ?>">
                                    <div class="modern-alert-icon">
                                        <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                                    </div>
                                    <span><?php echo $message; ?></span>
                                    <button class="modern-alert-close" onclick="this.parentElement.remove()">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            <?php endif; ?>

                            <form id="passwordForm" method="POST" class="password-form">
                                <input type="hidden" name="change_password" value="1">

                                <!-- Current Password -->
                                <div class="field-group">
                                    <label class="field-label">
                                        <i class="fas fa-lock field-label-icon"></i>
                                        Current Password
                                    </label>
                                    <div class="field-input-wrap">
                                        <input type="password" id="currentPassword" name="current_password"
                                            class="field-input" placeholder="Enter your current password">
                                        <button type="button" class="toggle-eye" onclick="togglePasswordVisibility('currentPassword')">
                                            <i class="fas fa-eye" id="eye-icon-currentPassword"></i>
                                        </button>
                                    </div>
                                    <div class="field-underline"></div>
                                </div>

                                <div class="fields-divider">
                                    <span>New Credentials</span>
                                </div>

                                <!-- New Password -->
                                <div class="field-group">
                                    <label class="field-label">
                                        <i class="fas fa-key field-label-icon"></i>
                                        New Password
                                    </label>
                                    <div class="field-input-wrap">
                                        <input type="password" id="newPassword" name="new_password"
                                            class="field-input" placeholder="Create a strong new password">
                                        <button type="button" class="toggle-eye" onclick="togglePasswordVisibility('newPassword')">
                                            <i class="fas fa-eye" id="eye-icon-newPassword"></i>
                                        </button>
                                    </div>
                                    <div class="field-underline"></div>
                                    <!-- Password Strength Bar -->
                                    <div class="strength-bar-wrap" id="strengthBar" style="display:none;">
                                        <div class="strength-segments">
                                            <div class="strength-seg" id="seg1"></div>
                                            <div class="strength-seg" id="seg2"></div>
                                            <div class="strength-seg" id="seg3"></div>
                                            <div class="strength-seg" id="seg4"></div>
                                        </div>
                                        <span class="strength-label" id="strengthLabel">Weak</span>
                                    </div>
                                </div>

                                <!-- Confirm Password -->
                                <div class="field-group">
                                    <label class="field-label">
                                        <i class="fas fa-check-double field-label-icon"></i>
                                        Confirm New Password
                                    </label>
                                    <div class="field-input-wrap">
                                        <input type="password" id="confirmPassword" name="confirm_password"
                                            class="field-input" placeholder="Re-enter your new password">
                                        <button type="button" class="toggle-eye" onclick="togglePasswordVisibility('confirmPassword')">
                                            <i class="fas fa-eye" id="eye-icon-confirmPassword"></i>
                                        </button>
                                        <div class="match-indicator" id="matchIndicator"></div>
                                    </div>
                                    <div class="field-underline"></div>
                                    <div id="statusMessage" class="field-hint" style="display:none;"></div>
                                </div>

                                <!-- Actions -->
                                <div class="form-actions">
                                    <button type="button" id="cancelBtn" class="btn-ghost">
                                        <i class="fas fa-times"></i>
                                        <span>Cancel</span>
                                    </button>
                                    <button type="submit" id="updateBtn" class="btn-primary-modern" disabled>
                                        <span class="btn-inner">
                                            <i class="fas fa-shield-alt"></i>
                                            <span>Update Password</span>
                                        </span>
                                        <div class="btn-shimmer"></div>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Account Info Card -->
                        <div class="info-card">
                            <div class="section-label">
                                <div class="section-label-icon">
                                    <i class="fas fa-info-circle"></i>
                                </div>
                                <span>Account Information</span>
                            </div>
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-key">Username</span>
                                    <span class="info-val"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-key">Role</span>
                                    <span class="info-val info-val-role"><?php echo $role; ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-key">Account Status</span>
                                    <span class="info-val">
                                        <span class="inline-badge active">Active</span>
                                    </span>
                                </div>
                                <div class="info-item">
                                    <span class="info-key">User ID</span>
                                    <span class="info-val info-val-id">#<?php echo str_pad($userID, 5, '0', STR_PAD_LEFT); ?></span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <!-- ===================== END PROFILE PAGE CONTENT ===================== -->
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/js/select2.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css" rel="stylesheet" />

<script>
    const currentPassword = document.getElementById('currentPassword');
    const newPassword     = document.getElementById('newPassword');
    const confirmPassword = document.getElementById('confirmPassword');
    const statusMessage   = document.getElementById('statusMessage');
    const updateBtn       = document.getElementById('updateBtn');
    const cancelBtn       = document.getElementById('cancelBtn');

    /* ── Eye Toggle ── */
    function togglePasswordVisibility(inputId) {
        const input   = document.getElementById(inputId);
        const eyeIcon = document.getElementById(`eye-icon-${inputId}`);
        input.type = input.type === 'password' ? 'text' : 'password';
        eyeIcon.classList.toggle('fa-eye');
        eyeIcon.classList.toggle('fa-eye-slash');
    }

    /* ── Password Strength ── */
    newPassword.addEventListener('input', function () {
        const val = this.value;
        const bar = document.getElementById('strengthBar');
        bar.style.display = val.length ? 'flex' : 'none';

        let score = 0;
        if (val.length >= 8)              score++;
        if (/[A-Z]/.test(val))            score++;
        if (/[0-9]/.test(val))            score++;
        if (/[^A-Za-z0-9]/.test(val))    score++;

        const segs  = ['seg1','seg2','seg3','seg4'];
        const colors = ['#ef4444','#f97316','#eab308','#22c55e'];
        const labels = ['Weak','Fair','Good','Strong'];

        segs.forEach((id, i) => {
            const el = document.getElementById(id);
            el.style.background = i < score ? colors[score - 1] : '#e2e8f0';
        });
        document.getElementById('strengthLabel').textContent = score ? labels[score - 1] : '';
        document.getElementById('strengthLabel').style.color = score ? colors[score - 1] : '#94a3b8';

        validatePasswords();
        updateButtonState();
    });

    /* ── Match Validation ── */
    function validatePasswords() {
        const newPass  = newPassword.value;
        const confPass = confirmPassword.value;
        const indicator = document.getElementById('matchIndicator');

        if (newPass && confPass) {
            if (newPass === confPass) {
                confirmPassword.classList.remove('error');
                confirmPassword.classList.add('success');
                indicator.innerHTML = '<i class="fas fa-check-circle" style="color:#22c55e"></i>';
                statusMessage.textContent = 'Passwords match';
                statusMessage.className = 'field-hint success';
                statusMessage.style.display = 'flex';
                return true;
            } else {
                confirmPassword.classList.add('error');
                confirmPassword.classList.remove('success');
                indicator.innerHTML = '<i class="fas fa-times-circle" style="color:#ef4444"></i>';
                statusMessage.textContent = 'Passwords do not match';
                statusMessage.className = 'field-hint error';
                statusMessage.style.display = 'flex';
                return false;
            }
        } else {
            confirmPassword.classList.remove('error','success');
            indicator.innerHTML = '';
            statusMessage.style.display = 'none';
            return false;
        }
    }

    confirmPassword.addEventListener('input', () => { validatePasswords(); updateButtonState(); });

    function updateButtonState() {
        const valid = currentPassword.value && newPassword.value && confirmPassword.value && validatePasswords();
        updateBtn.disabled = !valid;
    }

    currentPassword.addEventListener('input', updateButtonState);

    cancelBtn.addEventListener('click', function () {
        [currentPassword, newPassword, confirmPassword].forEach(el => {
            el.value = '';
            el.classList.remove('error','success');
        });
        statusMessage.style.display = 'none';
        document.getElementById('strengthBar').style.display = 'none';
        document.getElementById('matchIndicator').innerHTML = '';
        updateButtonState();
    });
</script>

<style>
/* ===================================================
   PROFILE PAGE — MODERN REDESIGN
   =================================================== */
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap');

:root {
    --indigo-50:  #eef2ff;
    --indigo-100: #e0e7ff;
    --indigo-500: #6366f1;
    --indigo-600: #4f46e5;
    --indigo-700: #4338ca;
    --slate-50:   #f8fafc;
    --slate-100:  #f1f5f9;
    --slate-200:  #e2e8f0;
    --slate-300:  #cbd5e1;
    --slate-400:  #94a3b8;
    --slate-500:  #64748b;
    --slate-600:  #475569;
    --slate-700:  #334155;
    --slate-800:  #1e293b;
    --slate-900:  #0f172a;
    --green-500:  #22c55e;
    --green-100:  #dcfce7;
    --red-500:    #ef4444;
    --red-100:    #fee2e2;
    --amber-500:  #f59e0b;
    --white:      #ffffff;
    --shadow-sm:  0 1px 2px rgba(0,0,0,.05);
    --shadow-md:  0 4px 16px rgba(15,23,42,.08);
    --shadow-lg:  0 12px 40px rgba(15,23,42,.12);
    --radius-md:  12px;
    --radius-lg:  20px;
    --radius-xl:  28px;
    --font-main:  'Plus Jakarta Sans', sans-serif;
    --font-mono:  'JetBrains Mono', monospace;
}

/* ── Layout ── */
.profile-page-wrapper {
    padding: 28px 32px 48px;
    min-height: 100vh;
    background: var(--slate-50);
    font-family: var(--font-main);
}

/* ── Page Header ── */
.profile-page-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    margin-bottom: 32px;
}

.breadcrumb-trail {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: var(--slate-400);
    margin-bottom: 8px;
    font-weight: 500;
    letter-spacing: .03em;
}

.breadcrumb-home { color: var(--indigo-500); }
.breadcrumb-sep  { color: var(--slate-300); }
.breadcrumb-current { color: var(--slate-500); }

.page-title {
    font-size: 26px;
    font-weight: 800;
    color: var(--slate-900);
    margin: 0 0 4px;
    letter-spacing: -.02em;
}

.page-subtitle {
    font-size: 14px;
    color: var(--slate-400);
    margin: 0;
}

.last-login-badge {
    display: flex;
    align-items: center;
    gap: 6px;
    background: var(--white);
    border: 1px solid var(--slate-200);
    border-radius: 100px;
    padding: 8px 16px;
    font-size: 12px;
    font-weight: 500;
    color: var(--slate-500);
    box-shadow: var(--shadow-sm);
}

.last-login-badge i { color: var(--indigo-500); }

/* ── Grid ── */
.profile-grid {
    display: grid;
    grid-template-columns: 340px 1fr;
    gap: 24px;
    align-items: start;
}

/* ===================================================
   LEFT COLUMN
   =================================================== */

/* ── Identity Card ── */
.identity-card {
    background: var(--white);
    border-radius: var(--radius-xl);
    overflow: hidden;
    box-shadow: var(--shadow-md);
    border: 1px solid var(--slate-200);
    margin-bottom: 20px;
    position: relative;
}

.identity-card-bg {
    height: 100px;
    background: linear-gradient(135deg, var(--indigo-600) 0%, #818cf8 50%, #a5b4fc 100%);
    position: relative;
}

.identity-card-bg::after {
    content: '';
    position: absolute;
    inset: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.07'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}

.identity-card-inner {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0 24px 24px;
    margin-top: -50px;
}

.avatar-ring {
    position: relative;
    width: 96px;
    height: 96px;
    border-radius: 50%;
    border: 4px solid var(--white);
    box-shadow: 0 4px 20px rgba(99,102,241,.3);
    background: var(--white);
    margin-bottom: 16px;
    flex-shrink: 0;
}

.avatar-img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
    display: block;
}

.avatar-online-dot {
    position: absolute;
    bottom: 4px;
    right: 4px;
    width: 14px;
    height: 14px;
    background: var(--green-500);
    border-radius: 50%;
    border: 2px solid var(--white);
    animation: pulse-green 2s infinite;
}

@keyframes pulse-green {
    0%, 100% { box-shadow: 0 0 0 0 rgba(34,197,94,.5); }
    50% { box-shadow: 0 0 0 5px rgba(34,197,94,0); }
}

.identity-name {
    font-size: 20px;
    font-weight: 800;
    color: var(--slate-900);
    margin: 0 0 10px;
    letter-spacing: -.02em;
    text-align: center;
}

.identity-role-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: var(--indigo-50);
    color: var(--indigo-600);
    border: 1px solid var(--indigo-100);
    border-radius: 100px;
    padding: 5px 14px;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 12px;
    letter-spacing: .02em;
}

.identity-status {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: var(--slate-500);
}

.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--green-500);
}

.status-text { font-weight: 500; }

/* Stats Row */
.identity-stats {
    display: flex;
    align-items: center;
    justify-content: space-around;
    border-top: 1px solid var(--slate-100);
    padding: 16px 24px;
    margin-top: 4px;
}

.stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
}

.stat-value {
    font-size: 20px;
    font-weight: 800;
    color: var(--slate-900);
    letter-spacing: -.02em;
}

.stat-label {
    font-size: 11px;
    color: var(--slate-400);
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: .06em;
}

.stat-divider {
    width: 1px;
    height: 32px;
    background: var(--slate-200);
}

/* ── Program Card ── */
.program-card {
    background: var(--white);
    border-radius: var(--radius-lg);
    padding: 24px;
    box-shadow: var(--shadow-md);
    border: 1px solid var(--slate-200);
}

/* ── Section Label ── */
.section-label {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
    font-weight: 700;
    font-size: 15px;
    color: var(--slate-800);
}

.section-label-icon {
    width: 36px;
    height: 36px;
    background: var(--indigo-50);
    color: var(--indigo-600);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    flex-shrink: 0;
}

.section-title {
    font-weight: 700;
    font-size: 15px;
    color: var(--slate-800);
    display: block;
}

.section-desc {
    font-size: 12px;
    color: var(--slate-400);
    margin: 2px 0 0;
    font-weight: 400;
}

/* Program Items */
.program-list { display: flex; flex-direction: column; gap: 10px; }

.program-item {
    display: flex;
    align-items: center;
    gap: 12px;
    background: var(--slate-50);
    border: 1px solid var(--slate-200);
    border-radius: var(--radius-md);
    padding: 12px 14px;
    animation: fadeSlide .4s ease both;
}

@keyframes fadeSlide {
    from { opacity: 0; transform: translateY(6px); }
    to   { opacity: 1; transform: translateY(0); }
}

.program-icon {
    width: 36px;
    height: 36px;
    background: var(--indigo-50);
    color: var(--indigo-600);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    flex-shrink: 0;
}

.program-details {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.program-name {
    font-size: 13px;
    font-weight: 600;
    color: var(--slate-800);
}

.program-meta {
    font-size: 11px;
    color: var(--slate-400);
    margin-top: 1px;
}

.program-badge.active {
    background: var(--green-100);
    color: #15803d;
    font-size: 10px;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 100px;
    letter-spacing: .04em;
    text-transform: uppercase;
}

/* Super Admin / Empty States */
.program-super-admin, .program-empty {
    text-align: center;
    padding: 20px 16px;
}

.super-admin-icon, .empty-icon {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    margin: 0 auto 12px;
}

.super-admin-icon { background: linear-gradient(135deg, #fbbf24, #f59e0b); color: var(--white); }
.empty-icon { background: var(--slate-100); color: var(--slate-400); }

.program-super-admin h4, .program-empty h4 {
    font-size: 14px;
    font-weight: 700;
    color: var(--slate-800);
    margin: 0 0 6px;
}

.program-super-admin p, .program-empty p {
    font-size: 12px;
    color: var(--slate-400);
    margin: 0 0 12px;
    line-height: 1.5;
}

.access-indicator {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 600;
    color: var(--green-500);
    background: var(--green-100);
    padding: 5px 12px;
    border-radius: 100px;
}

.access-dot {
    width: 7px;
    height: 7px;
    background: var(--green-500);
    border-radius: 50%;
    animation: pulse-green 2s infinite;
}

/* ===================================================
   RIGHT COLUMN
   =================================================== */

/* ── Password Card ── */
.password-card {
    background: var(--white);
    border-radius: var(--radius-xl);
    padding: 28px;
    box-shadow: var(--shadow-md);
    border: 1px solid var(--slate-200);
    margin-bottom: 20px;
}

.password-card-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 24px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--slate-100);
}

/* Security Score Ring */
.security-score {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    flex-shrink: 0;
}

.score-ring { width: 48px; height: 48px; transform: rotate(-90deg); }
.score-ring-bg   { fill: none; stroke: var(--slate-100); stroke-width: 3; }
.score-ring-fill { fill: none; stroke: var(--green-500); stroke-width: 3; stroke-linecap: round; transition: stroke-dasharray .6s ease; }
.score-label { font-size: 11px; font-weight: 700; color: var(--green-500); letter-spacing: .04em; text-transform: uppercase; }

/* ── Modern Alert ── */
.modern-alert {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    border-radius: var(--radius-md);
    margin-bottom: 20px;
    font-size: 13px;
    font-weight: 500;
    animation: fadeSlide .3s ease;
}

.modern-alert-success {
    background: var(--green-100);
    color: #15803d;
    border: 1px solid #bbf7d0;
}

.modern-alert-danger {
    background: var(--red-100);
    color: #b91c1c;
    border: 1px solid #fecaca;
}

.modern-alert-icon { font-size: 16px; flex-shrink: 0; }

.modern-alert-close {
    margin-left: auto;
    background: none;
    border: none;
    cursor: pointer;
    color: inherit;
    opacity: .6;
    padding: 0;
    font-size: 12px;
}

.modern-alert-close:hover { opacity: 1; }

/* ── Fields ── */
.password-form { display: flex; flex-direction: column; gap: 0; }

.field-group { margin-bottom: 20px; }

.field-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    font-weight: 600;
    color: var(--slate-500);
    text-transform: uppercase;
    letter-spacing: .07em;
    margin-bottom: 8px;
}

.field-label-icon { color: var(--indigo-500); font-size: 11px; }

.field-input-wrap {
    position: relative;
    display: flex;
    align-items: center;
}

.field-input {
    width: 100%;
    background: var(--slate-50);
    border: 1.5px solid var(--slate-200);
    border-radius: var(--radius-md);
    padding: 12px 44px 12px 16px;
    font-size: 14px;
    font-family: var(--font-main);
    color: var(--slate-800);
    transition: all .2s ease;
    outline: none;
}

.field-input::placeholder { color: var(--slate-400); }

.field-input:focus {
    border-color: var(--indigo-500);
    background: var(--white);
    box-shadow: 0 0 0 3px rgba(99,102,241,.12);
}

.field-input.error   { border-color: var(--red-500); }
.field-input.success { border-color: var(--green-500); }

.toggle-eye {
    position: absolute;
    right: 12px;
    background: none;
    border: none;
    cursor: pointer;
    color: var(--slate-400);
    font-size: 14px;
    padding: 4px;
    transition: color .15s;
    display: flex;
    align-items: center;
}

.toggle-eye:hover { color: var(--indigo-500); }

.match-indicator {
    position: absolute;
    right: 38px;
    font-size: 14px;
    transition: all .2s;
}

.field-underline { height: 0; }

.fields-divider {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 4px 0 20px;
}

.fields-divider::before, .fields-divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--slate-100);
}

.fields-divider span {
    font-size: 11px;
    font-weight: 600;
    color: var(--slate-400);
    text-transform: uppercase;
    letter-spacing: .07em;
    white-space: nowrap;
}

/* Strength Bar */
.strength-bar-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 8px;
}

.strength-segments {
    display: flex;
    gap: 4px;
    flex: 1;
}

.strength-seg {
    height: 4px;
    flex: 1;
    border-radius: 100px;
    background: var(--slate-200);
    transition: background .3s ease;
}

.strength-label {
    font-size: 11px;
    font-weight: 700;
    min-width: 44px;
    text-align: right;
    letter-spacing: .04em;
    text-transform: uppercase;
}

/* Field Hint */
.field-hint {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
    font-weight: 500;
    margin-top: 6px;
    animation: fadeSlide .2s ease;
}

.field-hint.success { color: #15803d; }
.field-hint.error   { color: var(--red-500); }

/* ── Form Actions ── */
.form-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 28px;
    padding-top: 20px;
    border-top: 1px solid var(--slate-100);
}

.btn-ghost {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 11px 20px;
    background: none;
    border: 1.5px solid var(--slate-200);
    border-radius: var(--radius-md);
    font-size: 13px;
    font-weight: 600;
    font-family: var(--font-main);
    color: var(--slate-600);
    cursor: pointer;
    transition: all .2s;
}

.btn-ghost:hover {
    background: var(--slate-100);
    border-color: var(--slate-300);
    color: var(--slate-800);
}

.btn-primary-modern {
    position: relative;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 11px 24px;
    background: linear-gradient(135deg, var(--indigo-600), var(--indigo-500));
    border: none;
    border-radius: var(--radius-md);
    font-size: 13px;
    font-weight: 700;
    font-family: var(--font-main);
    color: var(--white);
    cursor: pointer;
    transition: all .25s;
    overflow: hidden;
    box-shadow: 0 4px 14px rgba(99,102,241,.35);
}

.btn-primary-modern:not(:disabled):hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 22px rgba(99,102,241,.45);
}

.btn-primary-modern:not(:disabled):active {
    transform: translateY(0);
    box-shadow: 0 2px 10px rgba(99,102,241,.3);
}

.btn-primary-modern:disabled {
    opacity: .45;
    cursor: not-allowed;
    box-shadow: none;
    transform: none;
}

.btn-inner {
    display: flex;
    align-items: center;
    gap: 8px;
    position: relative;
    z-index: 1;
}

.btn-shimmer {
    position: absolute;
    inset: 0;
    background: linear-gradient(105deg, transparent 40%, rgba(255,255,255,.25) 50%, transparent 60%);
    transform: translateX(-100%);
}

.btn-primary-modern:not(:disabled):hover .btn-shimmer {
    animation: shimmer .6s ease;
}

@keyframes shimmer {
    to { transform: translateX(200%); }
}

/* ── Info Card ── */
.info-card {
    background: var(--white);
    border-radius: var(--radius-lg);
    padding: 24px 28px;
    box-shadow: var(--shadow-md);
    border: 1px solid var(--slate-200);
}

.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1px;
    background: var(--slate-100);
    border-radius: var(--radius-md);
    overflow: hidden;
    border: 1px solid var(--slate-100);
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
    padding: 16px 18px;
    background: var(--white);
}

.info-key {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: var(--slate-400);
}

.info-val {
    font-size: 14px;
    font-weight: 700;
    color: var(--slate-800);
}

.info-val-role { color: var(--indigo-600); }
.info-val-id   { font-family: var(--font-mono); font-size: 13px; color: var(--slate-500); }

.inline-badge.active {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: var(--green-100);
    color: #15803d;
    font-size: 11px;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 100px;
    letter-spacing: .04em;
    text-transform: uppercase;
}

.inline-badge.active::before {
    content: '';
    width: 6px;
    height: 6px;
    background: var(--green-500);
    border-radius: 50%;
}

/* ── Responsive ── */
@media (max-width: 1024px) {
    .profile-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .profile-page-wrapper {
        padding: 16px;
    }

    .profile-page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }

    .info-grid {
        grid-template-columns: 1fr;
    }

    .form-actions {
        flex-direction: column-reverse;
    }

    .btn-ghost, .btn-primary-modern {
        width: 100%;
        justify-content: center;
    }

    .password-card-header {
        flex-direction: column;
        gap: 16px;
    }
}
</style>