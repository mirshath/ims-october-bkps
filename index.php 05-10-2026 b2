<?php
session_start();

include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
}

require_once 'PermissionChecking.php';

// ── Stat Queries ──────────────────────────────────────────
$allStd_Count  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM allocate_programme"))['c'];
$activeCount   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM allocate_programme WHERE status='active'"))['c'];
$inactiveCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM students WHERE student_status='inactive'"))['c'];
$transferCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM allocate_programme WHERE status='transferred'"))['c'];
$dropCount     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM allocate_programme WHERE status='drop'"))['c'];

$totalKpi = max((int)$allStd_Count, 1);
$activePctKpi = round($activeCount / $totalKpi * 100);
$inactivePctKpi = round($inactiveCount / $totalKpi * 100);
$transferPctKpi = round($transferCount / $totalKpi * 100);
$dropPctKpi = round($dropCount / $totalKpi * 100);

$isSuperAdmin  = isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin';

if ($isSuperAdmin) {
    $programCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM program_table"))['c'];
    $batchCount   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM batch_table"))['c'];
    $userCount    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM admin"))['c'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
</head>

<!-- Page Wrapper -->
<div id="wrapper">
    <?php include("nav.php"); ?>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include("includes/topnav.php"); ?>

            <!-- ════════════════ DASHBOARD CONTENT ════════════════ -->
            <div class="dash-root">

                <!-- ── Welcome Hero Banner ── -->
                <div class="dash-hero">
                    <div class="dash-hero-text">
                        <?php
                        // $h = (int)date('G');
                        // $ampm = $h < 12 ? 'AM' : 'PM';
                        // $hour12 = $h % 12 === 0 ? 12 : $h % 12;
                        // $greeting = $h < 12 ? 'Morning' : ($h < 17 ? 'Afternoon' : 'Evening');
                        // echo $greeting;
                        ?>
                        <p class="dash-greeting">Hello, <strong><?php echo htmlspecialchars(ucfirst($_SESSION['username'])); ?></strong></p>
                        <h1 class="dash-title">Institution Management<br><span>Overview</span></h1>
                        <p class="dash-sub">Here's what's happening across your institution today.</p>
                    </div>
                    <div class="dash-hero-meta">
                        <div class="dash-date-pill">
                            <i class="fas fa-calendar-day"></i>
                            <span><?php echo date('l, d M Y'); ?></span>
                        </div>
                        <div class="dash-time-pill">
                            <i class="fas fa-clock"></i>
                            <span id="timeDisplay">--:--</span>
                        </div>
                    </div>
                </div>

                <!-- ── KPI Stat Cards ── -->
                <div class="kpi-section">
                    <h2 class="section-heading">
                        <span class="heading-bar"></span>
                        Student Statistics
                    </h2>

                    <div class="kpi-grid">

                        <div class="kpi-card kpi-total" style="--delay:.05s">
                            <div class="kpi-card-top">
                                <div class="kpi-icon-wrap"><i class="fas fa-database"></i></div>
                                <div class="kpi-trend neutral"><i class="fas fa-minus"></i></div>
                            </div>
                            <div class="kpi-value"><?php echo number_format($allStd_Count); ?></div>
                            <!-- <div class="kpi-sub">100% of total</div> -->
                            <div class="kpi-label">Total Students In Database</div>
                            <div class="kpi-bar">
                                <div class="kpi-bar-fill" style="width:100%"></div>
                            </div>
                        </div>

                        <div class="kpi-card kpi-active" style="--delay:.10s">
                            <div class="kpi-card-top">
                                <div class="kpi-icon-wrap"><i class="fas fa-user-check"></i></div>
                                <div class="kpi-trend up"><i class="fas fa-arrow-up"></i></div>
                            </div>
                            <div class="kpi-value"><?php echo number_format($activeCount); ?></div>
                            <!-- <div class="kpi-sub"><?php echo $activePctKpi; ?>% of total</div> -->
                            <div class="kpi-label">Active Students</div>
                            <div class="kpi-bar">
                                <div class="kpi-bar-fill" style="width:<?php echo $allStd_Count > 0 ? round($activeCount / $allStd_Count * 100) : 0; ?>%"></div>
                            </div>
                        </div>

                        <div class="kpi-card kpi-inactive" style="--delay:.15s">
                            <div class="kpi-card-top">
                                <div class="kpi-icon-wrap"><i class="fas fa-user-clock"></i></div>
                                <div class="kpi-trend neutral"><i class="fas fa-minus"></i></div>
                            </div>
                            <div class="kpi-value"><?php echo number_format($inactiveCount); ?></div>
                            <!-- <div class="kpi-sub"><?php echo $inactivePctKpi; ?>% of total</div> -->
                            <div class="kpi-label">Inactive Students</div>
                            <div class="kpi-bar">
                                <div class="kpi-bar-fill" style="width:<?php echo $allStd_Count > 0 ? round($inactiveCount / $allStd_Count * 100) : 0; ?>%"></div>
                            </div>
                        </div>

                        <div class="kpi-card kpi-transfer" style="--delay:.20s">
                            <div class="kpi-card-top">
                                <div class="kpi-icon-wrap"><i class="fas fa-exchange-alt"></i></div>
                                <div class="kpi-trend neutral"><i class="fas fa-minus"></i></div>
                            </div>
                            <div class="kpi-value"><?php echo number_format($transferCount); ?></div>
                            <!-- <div class="kpi-sub"><?php echo $transferPctKpi; ?>% of total</div> -->
                            <div class="kpi-label">Transferred</div>
                            <div class="kpi-bar">
                                <div class="kpi-bar-fill" style="width:<?php echo $allStd_Count > 0 ? round($transferCount / $allStd_Count * 100) : 0; ?>%"></div>
                            </div>
                        </div>

                        <div class="kpi-card kpi-drop" style="--delay:.25s">
                            <div class="kpi-card-top">
                                <div class="kpi-icon-wrap"><i class="fas fa-user-slash"></i></div>
                                <div class="kpi-trend down"><i class="fas fa-arrow-down"></i></div>
                            </div>
                            <div class="kpi-value"><?php echo number_format($dropCount); ?></div>
                            <!-- <div class="kpi-sub"><?php echo $dropPctKpi; ?>% of total</div> -->
                            <div class="kpi-label">Dropped</div>
                            <div class="kpi-bar">
                                <div class="kpi-bar-fill" style="width:<?php echo $allStd_Count > 0 ? round($dropCount / $allStd_Count * 100) : 0; ?>%"></div>
                            </div>
                        </div>

                        <?php if ($isSuperAdmin): ?>
                            <div class="kpi-card kpi-program" style="--delay:.30s">
                                <div class="kpi-card-top">
                                    <div class="kpi-icon-wrap"><i class="fas fa-list-alt"></i></div>
                                </div>
                                <div class="kpi-value"><?php echo number_format($programCount); ?></div>
                                <div class="kpi-label">Programs</div>
                                <div class="kpi-bar">
                                    <div class="kpi-bar-fill" style="width:100%"></div>
                                </div>
                            </div>

                            <div class="kpi-card kpi-batch" style="--delay:.35s">
                                <div class="kpi-card-top">
                                    <div class="kpi-icon-wrap"><i class="fas fa-layer-group"></i></div>
                                </div>
                                <div class="kpi-value"><?php echo number_format($batchCount); ?></div>
                                <div class="kpi-label">Batches</div>
                                <div class="kpi-bar">
                                    <div class="kpi-bar-fill" style="width:100%"></div>
                                </div>
                            </div>

                            <div class="kpi-card kpi-users" style="--delay:.40s">
                                <div class="kpi-card-top">
                                    <div class="kpi-icon-wrap"><i class="fas fa-users"></i></div>
                                </div>
                                <div class="kpi-value"><?php echo number_format($userCount); ?></div>
                                <div class="kpi-label">System Users</div>
                                <div class="kpi-bar">
                                    <div class="kpi-bar-fill" style="width:100%"></div>
                                </div>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>

                <!-- ── Summary Donut Strip ── -->
                <div class="summary-strip">
                    <?php
                    $total       = max($allStd_Count, 1);
                    $activePct   = round($activeCount / $total * 100);
                    $inactivePct = round($inactiveCount / $total * 100);
                    $transPct    = round($transferCount / $total * 100);
                    $dropPct     = round($dropCount / $total * 100);
                    $c           = 283; // circumference for r=45
                    $aD = $activePct   * $c / 100;
                    $iD = $inactivePct * $c / 100;
                    $tD = $transPct    * $c / 100;
                    $dD = $dropPct     * $c / 100;
                    ?>
                    <div class="summary-chart-wrap">
                        <svg class="donut-svg" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="50" cy="50" r="45" fill="none" stroke="#f1f5f9" stroke-width="9" />
                            <circle cx="50" cy="50" r="45" fill="none" stroke="#22c55e" stroke-width="9"
                                stroke-dasharray="<?php echo $aD; ?> <?php echo $c; ?>"
                                stroke-dashoffset="0" stroke-linecap="round" transform="rotate(-90 50 50)" />
                            <circle cx="50" cy="50" r="45" fill="none" stroke="#f59e0b" stroke-width="9"
                                stroke-dasharray="<?php echo $iD; ?> <?php echo $c; ?>"
                                stroke-dashoffset="-<?php echo $aD; ?>" stroke-linecap="round" transform="rotate(-90 50 50)" />
                            <circle cx="50" cy="50" r="45" fill="none" stroke="#3b82f6" stroke-width="9"
                                stroke-dasharray="<?php echo $tD; ?> <?php echo $c; ?>"
                                stroke-dashoffset="-<?php echo $aD + $iD; ?>" stroke-linecap="round" transform="rotate(-90 50 50)" />
                            <circle cx="50" cy="50" r="45" fill="none" stroke="#ef4444" stroke-width="9"
                                stroke-dasharray="<?php echo $dD; ?> <?php echo $c; ?>"
                                stroke-dashoffset="-<?php echo $aD + $iD + $tD; ?>" stroke-linecap="round" transform="rotate(-90 50 50)" />
                            <text x="50" y="46" text-anchor="middle" fill="#0f172a" font-size="14" font-weight="700" font-family="Sora,sans-serif"><?php echo number_format($allStd_Count); ?></text>
                            <text x="50" y="58" text-anchor="middle" fill="#94a3b8" font-size="6" font-family="Sora,sans-serif">TOTAL</text>
                        </svg>
                    </div>

                    <div class="summary-legend">
                        <div class="legend-row">
                            <span class="legend-dot" style="background:#22c55e"></span>
                            <span class="legend-name">Active</span>
                            <span class="legend-pct"><?php echo $activePct; ?>%</span>
                            <span class="legend-count"><?php echo number_format($activeCount); ?></span>
                        </div>
                        <div class="legend-row">
                            <span class="legend-dot" style="background:#f59e0b"></span>
                            <span class="legend-name">Inactive</span>
                            <span class="legend-pct"><?php echo $inactivePct; ?>%</span>
                            <span class="legend-count"><?php echo number_format($inactiveCount); ?></span>
                        </div>
                        <div class="legend-row">
                            <span class="legend-dot" style="background:#3b82f6"></span>
                            <span class="legend-name">Transferred</span>
                            <span class="legend-pct"><?php echo $transPct; ?>%</span>
                            <span class="legend-count"><?php echo number_format($transferCount); ?></span>
                        </div>
                        <div class="legend-row">
                            <span class="legend-dot" style="background:#ef4444"></span>
                            <span class="legend-name">Dropped</span>
                            <span class="legend-pct"><?php echo $dropPct; ?>%</span>
                            <span class="legend-count"><?php echo number_format($dropCount); ?></span>
                        </div>
                    </div>

                    <div class="summary-quick-stats">
                        <div class="qs-item">
                            <span class="qs-value"><?php echo $activePct; ?>%</span>
                            <span class="qs-label">Retention Rate</span>
                        </div>
                        <div class="qs-divider"></div>
                        <div class="qs-item">
                            <span class="qs-value"><?php echo $dropPct; ?>%</span>
                            <span class="qs-label">Drop Rate</span>
                        </div>
                        <div class="qs-divider"></div>
                        <div class="qs-item">
                            <span class="qs-value"><?php echo $transPct; ?>%</span>
                            <span class="qs-label">Transfer Rate</span>
                        </div>
                    </div>
                </div>




                <!-- ── Notifications (Super Admin only) ── -->
                <?php if ($isSuperAdmin): ?>
                    <div class="notif-section">
                        <div class="notif-section-head">
                            <h2 class="section-heading" style="margin:0">
                                <span class="heading-bar"></span>
                                Latest Notifications
                            </h2>
                            <a href="all_notifications.php" class="view-all-btn">
                                View All <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                        <div class="notif-grid" id="notifications">
                            <div class="notif-skeleton"></div>
                            <div class="notif-skeleton"></div>
                            <div class="notif-skeleton"></div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- ── Department Navigation Cards ── -->
                <div class="nav-section" id="card_enable" <?php echo !in_array('card_enable - index', $subListValues) ? 'style="pointer-events:none;opacity:.4;"' : ''; ?>>
                    <h2 class="section-heading">
                        <span class="heading-bar"></span>
                        Department Dashboards
                    </h2>

                    <div class="nav-grid">
                        <a href="DashboardSalesandMarketing" class="nav-tile" style="--delay:.05s;--accent:#6366f1;--accent-light:#eef2ff">
                            <div class="nav-tile-icon"><i class="fas fa-chart-line"></i></div>
                            <div class="nav-tile-body">
                                <span class="nav-tile-title">Sales & Marketing</span>
                                <span class="nav-tile-desc">Leads, conversions & campaigns</span>
                            </div>
                            <div class="nav-tile-arrow"><i class="fas fa-arrow-right"></i></div>
                        </a>

                        <a href="DashboardProgrammeManagement" class="nav-tile" style="--delay:.10s;--accent:#059669;--accent-light:#ecfdf5">
                            <div class="nav-tile-icon"><i class="fas fa-tasks"></i></div>
                            <div class="nav-tile-body">
                                <span class="nav-tile-title">Programme Management</span>
                                <span class="nav-tile-desc">Schedules, batches & allocation</span>
                            </div>
                            <div class="nav-tile-arrow"><i class="fas fa-arrow-right"></i></div>
                        </a>

                        <a href="DashboardRegistrar" class="nav-tile" style="--delay:.15s;--accent:#0284c7;--accent-light:#e0f2fe">
                            <div class="nav-tile-icon"><i class="fas fa-user-plus"></i></div>
                            <div class="nav-tile-body">
                                <span class="nav-tile-title">Registrar</span>
                                <span class="nav-tile-desc">Enrollments & registrations</span>
                            </div>
                            <div class="nav-tile-arrow"><i class="fas fa-arrow-right"></i></div>
                        </a>

                        <a href="DashboardAccounts" class="nav-tile" style="--delay:.20s;--accent:#d97706;--accent-light:#fffbeb">
                            <div class="nav-tile-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                            <div class="nav-tile-body">
                                <span class="nav-tile-title">Accounts</span>
                                <span class="nav-tile-desc">Fees, invoices & payments</span>
                            </div>
                            <div class="nav-tile-arrow"><i class="fas fa-arrow-right"></i></div>
                        </a>

                        <a href="DashboardReport" class="nav-tile" style="--delay:.25s;--accent:#7c3aed;--accent-light:#f5f3ff">
                            <div class="nav-tile-icon"><i class="fas fa-file-alt"></i></div>
                            <div class="nav-tile-body">
                                <span class="nav-tile-title">Reports</span>
                                <span class="nav-tile-desc">Analytics, exports & insights</span>
                            </div>
                            <div class="nav-tile-arrow"><i class="fas fa-arrow-right"></i></div>
                        </a>

                        <a href="DashboardCredentials" class="nav-tile" style="--delay:.30s;--accent:#0f766e;--accent-light:#f0fdfa">
                            <div class="nav-tile-icon"><i class="fas fa-key"></i></div>
                            <div class="nav-tile-body">
                                <span class="nav-tile-title">User Credentials</span>
                                <span class="nav-tile-desc">Access control & permissions</span>
                            </div>
                            <div class="nav-tile-arrow"><i class="fas fa-arrow-right"></i></div>
                        </a>

                        <a href="DashboardStudent" class="nav-tile" style="--delay:.35s;--accent:#be185d;--accent-light:#fdf2f8">
                            <div class="nav-tile-icon"><i class="fas fa-user-graduate"></i></div>
                            <div class="nav-tile-body">
                                <span class="nav-tile-title">Student</span>
                                <span class="nav-tile-desc">Records, status & history</span>
                            </div>
                            <div class="nav-tile-arrow"><i class="fas fa-arrow-right"></i></div>
                        </a>
                    </div>
                </div>

            </div>
            <!-- ════════════════ END DASHBOARD ════════════════ -->
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/js/select2.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css" rel="stylesheet" />

<script>
    /* ── Live Clock ── */
    (function tick() {
        const now = new Date();
        document.getElementById('timeDisplay').textContent =
            now.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit'
            });
        setTimeout(tick, 1000);
    })();

    <?php if ($isSuperAdmin): ?>
        /* ── Notifications ── */
        function loadNotifications() {
            fetch("get_notifications.php")
                .then(r => r.json())
                .then(data => {
                    const el = document.getElementById("notifications");
                    if (!data.length) {
                        el.innerHTML = `<div class="notif-empty"><i class="fas fa-bell-slash"></i><p>No notifications yet</p></div>`;
                        return;
                    }
                    const actionColors = {
                        INSERT: '#22c55e',
                        UPDATE: '#3b82f6',
                        DELETE: '#ef4444'
                    };
                    el.innerHTML = data.map(n => {
                        const isUnread = n.status === "unread";
                        const color = actionColors[(n.action || '').toUpperCase()] || '#94a3b8';
                        return `<div class="notif-card ${isUnread ? 'notif-unread' : ''}">
                    <div class="notif-card-accent" style="background:${color}"></div>
                    <div class="notif-card-body">
                        <div class="notif-card-top">
                            <span class="notif-action-badge" style="background:${color}20;color:${color}">${(n.action||'EVENT').toUpperCase()}</span>
                            <span class="notif-table-name">${n.table_name}</span>
                            ${isUnread ? '<span class="notif-unread-dot"></span>' : ''}
                        </div>
                        <p class="notif-message ${isUnread ? 'fw-bold' : ''}">${n.message}</p>
                        <div class="notif-card-footer">
                            <span class="notif-time"><i class="fas fa-clock"></i> ${n.created_at}</span>
                            ${isUnread
                                ? `<button onclick="markAsRead(${n.id})" class="notif-read-btn">Mark as Read</button>`
                                : `<span class="notif-read-badge"><i class="fas fa-check"></i> Read</span>`}
                        </div>
                    </div>
                </div>`;
                    }).join('');
                })
                .catch(e => console.error(e));
        }

        function markAsRead(id) {
            fetch("mark_as_read.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: "id=" + id
            }).then(r => r.text()).then(d => {
                if (d === "success") loadNotifications();
            });
        }

        loadNotifications();
        setInterval(loadNotifications, 5000);
    <?php endif; ?>
</script>

<style>
    /* ═══════════════════════════════════════════════════════
   DASHBOARD — PRO REDESIGN (navy + red, matches sidebar & glass top bar)
   Font: Sora (text) + IBM Plex Mono (numbers)
   ═══════════════════════════════════════════════════════ */

    :root {
        --bg: #eef2f9;
        --surface: #ffffff;
        --border: #dfe5f1;
        --text-1: #0f1b3a;
        --text-2: #4a5878;
        --text-3: #7d8db0;
        --green: #22c55e;
        --amber: #f59e0b;
        --blue: #3b82f6;
        --red: #ef4444;

        --brand-top: #182a51;
        --brand-mid: #0f2e71;
        --brand-bot: #03143d;
        --brand-red: #a70000;
        --brand-soft: #7f9df0;

        --radius: 16px;
        --shadow: 0 1px 2px rgba(15, 27, 58, .05), 0 6px 20px rgba(15, 27, 58, .06);
        --shadow-lg: 0 10px 30px rgba(15, 27, 58, .13);
        --font: 'Sora', sans-serif;
        --mono: 'IBM Plex Mono', monospace;
    }

    /* ── Root (transparent so the page's soft glass backdrop shows through) ── */
    .dash-root {
        padding: 4px 32px 60px;
        background: transparent;
        min-height: 100vh;
        font-family: var(--font);
        color: var(--text-1);
    }

    /* ── Hero ── */
    .dash-hero {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        margin-bottom: 28px;
        padding: 30px 36px;
        background: linear-gradient(135deg, var(--brand-top) 0%, var(--brand-mid) 55%, var(--brand-bot) 100%);
        border: 1px solid rgba(255, 255, 255, .08);
        border-radius: 20px;
        box-shadow: 0 14px 34px rgba(3, 20, 61, .28), inset 0 1px 0 rgba(255, 255, 255, .12);
        position: relative;
        overflow: hidden;
    }

    /* red edge marker */
    .dash-hero::before {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 4px;
        height: 56%;
        border-radius: 0 4px 4px 0;
        background: var(--brand-red);
        box-shadow: 0 0 18px rgba(167, 0, 0, .6);
    }

    .dash-hero::after {
        display: none;
    }

    .dash-greeting {
        font-size: 14px;
        color: #c6d0e3;
        margin: 0 0 8px;
        font-weight: 400;
    }

    .dash-greeting strong {
        color: #fff;
        font-weight: 600;
    }

    .dash-title {
        font-size: 30px;
        font-weight: 700;
        color: #fff;
        margin: 0 0 8px;
        line-height: 1.18;
        letter-spacing: -.02em;
    }

    .dash-title span {
        display: block;
        background: none;
        -webkit-text-fill-color: initial;
        color: #c6d0e3;
        font-weight: 400;
    }

    .dash-sub {
        font-size: 13px;
        color: #7d8db0;
        margin: 0;
    }

    .dash-hero-meta {
        display: flex;
        flex-direction: column;
        gap: 10px;
        align-items: flex-end;
        position: relative;
        z-index: 1;
    }

    .dash-date-pill,
    .dash-time-pill {
        display: flex;
        align-items: center;
        gap: 8px;
        background: rgba(255, 255, 255, .08);
        -webkit-backdrop-filter: blur(10px);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, .14);
        border-radius: 100px;
        padding: 8px 16px;
        font-size: 13px;
        font-weight: 500;
        color: #e6ebf5;
        white-space: nowrap;
    }

    .dash-date-pill i,
    .dash-time-pill i {
        color: var(--brand-soft);
    }

    .dash-time-pill {
        font-family: var(--mono);
    }

    /* ── Section heading ── */
    .section-heading {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 16px;
        font-weight: 700;
        color: var(--text-1);
        margin: 0 0 18px;
        letter-spacing: -.01em;
    }

    .heading-bar {
        display: block;
        width: 4px;
        height: 20px;
        background: var(--brand-red);
        border-radius: 100px;
        flex-shrink: 0;
    }

    /* ── KPI grid ── */
    .kpi-section {
        margin-bottom: 28px;
    }

    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 16px;
    }

    .kpi-card {
        display: flex;
        flex-direction: column;
        background: var(--surface);
        border-radius: var(--radius);
        padding: 20px;
        box-shadow: var(--shadow);
        border: 1px solid var(--border);
        animation: riseIn .45s ease both;
        animation-delay: var(--delay, 0s);
        transition: box-shadow .2s ease, border-color .2s ease;
        position: relative;
        overflow: hidden;
    }

    .kpi-card:hover {
        box-shadow: var(--shadow-lg);
        border-color: #c9d3e8;
    }

    .kpi-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
    }

    .kpi-card::after {
        display: none;
    }

    .kpi-total::before {
        background: var(--brand-top);
    }

    .kpi-active::before {
        background: var(--green);
    }

    .kpi-inactive::before {
        background: var(--amber);
    }

    .kpi-transfer::before {
        background: var(--blue);
    }

    .kpi-drop::before {
        background: var(--red);
    }

    .kpi-program::before {
        background: var(--brand-mid);
    }

    .kpi-batch::before {
        background: #5b7fd6;
    }

    .kpi-users::before {
        background: var(--brand-red);
    }

    .kpi-card-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 16px;
    }

    .kpi-icon-wrap {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 17px;
    }

    .kpi-total .kpi-icon-wrap {
        background: #e8edf8;
        color: var(--brand-top);
    }

    .kpi-active .kpi-icon-wrap {
        background: #dcfce7;
        color: #15803d;
    }

    .kpi-inactive .kpi-icon-wrap {
        background: #fef3c7;
        color: #b45309;
    }

    .kpi-transfer .kpi-icon-wrap {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .kpi-drop .kpi-icon-wrap {
        background: #fee2e2;
        color: #b91c1c;
    }

    .kpi-program .kpi-icon-wrap {
        background: #e6edfb;
        color: var(--brand-mid);
    }

    .kpi-batch .kpi-icon-wrap {
        background: #eaf0fd;
        color: #4a6fcf;
    }

    .kpi-users .kpi-icon-wrap {
        background: #fbe9e9;
        color: var(--brand-red);
    }

    /* the arrow chips carried no real trend data — hidden to avoid implying one */
    .kpi-trend {
        display: none;
    }

    .kpi-value {
        font-size: 28px;
        font-weight: 700;
        color: var(--text-1);
        letter-spacing: -.02em;
        font-family: var(--mono);
        line-height: 1;
        margin-bottom: 6px;
    }

    .kpi-sub {
        font-size: 12px;
        font-weight: 600;
        color: var(--text-3);
        font-family: var(--mono);
        margin-bottom: 10px;
    }

    .kpi-label {
        font-size: 13px;
        font-weight: 500;
        color: var(--text-2);
        margin-bottom: 14px;
        text-transform: none;
        letter-spacing: 0;
        line-height: 1.35;
    }

    .kpi-bar {
        height: 6px;
        background: #e8edf6;
        border-radius: 100px;
        overflow: hidden;
        margin-top: auto;
    }

    .kpi-bar-fill {
        height: 100%;
        border-radius: 100px;
        transition: width 1.2s cubic-bezier(.4, 0, .2, 1);
    }

    .kpi-total .kpi-bar-fill {
        background: var(--brand-top);
    }

    .kpi-active .kpi-bar-fill {
        background: var(--green);
    }

    .kpi-inactive .kpi-bar-fill {
        background: var(--amber);
    }

    .kpi-transfer .kpi-bar-fill {
        background: var(--blue);
    }

    .kpi-drop .kpi-bar-fill {
        background: var(--red);
    }

    .kpi-program .kpi-bar-fill {
        background: var(--brand-mid);
    }

    .kpi-batch .kpi-bar-fill {
        background: #5b7fd6;
    }

    .kpi-users .kpi-bar-fill {
        background: var(--brand-red);
    }

    @keyframes riseIn {
        from {
            opacity: 0;
            transform: translateY(8px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* ── Summary strip ── */
    .summary-strip {
        background: var(--surface);
        border-radius: var(--radius);
        border: 1px solid var(--border);
        box-shadow: var(--shadow);
        padding: 28px 36px;
        display: flex;
        align-items: center;
        gap: 40px;
        margin-bottom: 28px;
        flex-wrap: wrap;
    }

    .donut-svg {
        width: 140px;
        height: 140px;
    }

    .summary-legend {
        display: flex;
        flex-direction: column;
        gap: 12px;
        min-width: 220px;
    }

    .legend-row {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 13px;
    }

    .legend-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .legend-name {
        flex: 1;
        color: var(--text-2);
        font-weight: 500;
    }

    .legend-pct {
        font-weight: 700;
        color: var(--text-1);
        font-family: var(--mono);
        font-size: 12px;
        min-width: 36px;
        text-align: right;
    }

    .legend-count {
        font-family: var(--mono);
        font-size: 12px;
        color: var(--text-3);
        min-width: 44px;
        text-align: right;
    }

    .summary-quick-stats {
        display: flex;
        align-items: center;
        gap: 32px;
        margin-left: auto;
        flex-wrap: wrap;
    }

    .qs-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
    }

    .qs-value {
        font-size: 28px;
        font-weight: 700;
        color: var(--brand-top);
        font-family: var(--mono);
        letter-spacing: -.02em;
    }

    .qs-label {
        font-size: 12px;
        color: var(--text-3);
        font-weight: 500;
        text-transform: none;
        letter-spacing: 0;
        white-space: nowrap;
    }

    .qs-divider {
        width: 1px;
        height: 40px;
        background: var(--border);
    }

    /* ── Department tiles (one calm colour family instead of a rainbow) ── */
    .nav-section {
        margin-bottom: 28px;
    }

    .nav-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 14px;
    }

    .nav-tile {
        --accent: var(--brand-mid) !important;
        --accent-light: #e9effb !important;
        display: flex;
        align-items: center;
        gap: 16px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 18px 20px;
        text-decoration: none;
        box-shadow: var(--shadow);
        animation: riseIn .45s ease both;
        animation-delay: var(--delay, 0s);
        transition: border-color .2s ease, box-shadow .2s ease;
        position: relative;
        overflow: hidden;
    }

    .nav-tile::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 3px;
        background: var(--brand-red);
        transform: scaleY(0);
        transform-origin: center;
        transition: transform .22s ease;
    }

    .nav-tile:hover {
        border-color: #c3d0ec;
        box-shadow: var(--shadow-lg);
        text-decoration: none;
    }

    .nav-tile:hover::before {
        transform: scaleY(1);
    }

    .nav-tile:hover .nav-tile-arrow {
        transform: translateX(4px);
        opacity: 1;
    }

    .nav-tile-icon {
        width: 46px;
        height: 46px;
        border-radius: 13px;
        background: var(--accent-light);
        color: var(--accent);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
        position: relative;
        z-index: 1;
        transition: background .2s, color .2s;
    }

    .nav-tile:hover .nav-tile-icon {
        background: var(--brand-top);
        color: #fff;
    }

    .nav-tile-body {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 3px;
        position: relative;
        z-index: 1;
    }

    .nav-tile-title {
        font-size: 14px;
        font-weight: 700;
        color: var(--text-1);
        letter-spacing: -.01em;
    }

    .nav-tile-desc {
        font-size: 12px;
        color: var(--text-3);
        font-weight: 400;
    }

    .nav-tile-arrow {
        color: var(--brand-red);
        font-size: 13px;
        opacity: 0;
        transition: transform .2s ease, opacity .2s ease;
        position: relative;
        z-index: 1;
        flex-shrink: 0;
    }

    /* ── Notifications ── */
    .notif-section {
        margin-bottom: 28px;
    }

    .notif-section-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
    }

    .view-all-btn {
        display: flex;
        align-items: center;
        gap: 6px;
        background: var(--brand-red);
        color: #fff;
        padding: 8px 18px;
        border-radius: 100px;
        font-size: 12px;
        font-weight: 600;
        text-decoration: none;
        transition: background .2s, box-shadow .2s;
        font-family: var(--font);
        box-shadow: 0 6px 16px rgba(167, 0, 0, .28);
    }

    .view-all-btn:hover {
        background: #8a0000;
        color: #fff;
        text-decoration: none;
    }

    .notif-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 14px;
    }

    .notif-card {
        background: var(--surface);
        border-radius: var(--radius);
        border: 1px solid var(--border);
        box-shadow: var(--shadow);
        display: flex;
        overflow: hidden;
        animation: riseIn .4s ease both;
        transition: box-shadow .2s;
    }

    .notif-card:hover {
        box-shadow: var(--shadow-lg);
    }

    .notif-card.notif-unread {
        border-color: #c3d0ec;
        background: #f5f8ff;
    }

    .notif-card-accent {
        width: 4px;
        flex-shrink: 0;
    }

    .notif-card-body {
        padding: 14px 16px;
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .notif-card-top {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .notif-action-badge {
        font-size: 10px;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 100px;
        letter-spacing: .05em;
        font-family: var(--mono);
    }

    .notif-table-name {
        font-size: 11px;
        color: var(--text-3);
        font-weight: 500;
    }

    .notif-unread-dot {
        width: 7px;
        height: 7px;
        background: var(--brand-red);
        border-radius: 50%;
        margin-left: auto;
        animation: pulseDot 2s infinite;
    }

    @keyframes pulseDot {

        0%,
        100% {
            box-shadow: 0 0 0 0 rgba(167, 0, 0, .4);
        }

        50% {
            box-shadow: 0 0 0 5px rgba(167, 0, 0, 0);
        }
    }

    .notif-message {
        font-size: 13px;
        color: var(--text-2);
        margin: 0;
        line-height: 1.5;
    }

    .notif-card-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 4px;
    }

    .notif-time {
        font-size: 11px;
        color: var(--text-3);
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .notif-read-btn {
        background: var(--brand-top);
        color: #fff;
        border: none;
        border-radius: 100px;
        padding: 4px 12px;
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
        transition: background .2s;
        font-family: var(--font);
    }

    .notif-read-btn:hover {
        background: var(--brand-mid);
    }

    .notif-read-badge {
        font-size: 11px;
        font-weight: 600;
        color: #15803d;
        background: #dcfce7;
        padding: 3px 10px;
        border-radius: 100px;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .notif-skeleton {
        height: 110px;
        border-radius: var(--radius);
        background: linear-gradient(90deg, #eaeff8 25%, #dfe5f1 50%, #eaeff8 75%);
        background-size: 200% 100%;
        animation: shimmer 1.4s infinite;
    }

    @keyframes shimmer {
        from {
            background-position: 200% 0;
        }

        to {
            background-position: -200% 0;
        }
    }

    .notif-empty {
        grid-column: 1/-1;
        text-align: center;
        padding: 40px;
        color: var(--text-3);
        font-size: 14px;
    }

    .notif-empty i {
        font-size: 28px;
        display: block;
        margin-bottom: 8px;
    }

    /* ── Table (kept for when the records table is re-enabled) ── */
    .table-section {
        margin-bottom: 20px;
    }

    .table-card {
        background: var(--surface);
        border-radius: var(--radius);
        border: 1px solid var(--border);
        box-shadow: var(--shadow);
        padding: 20px;
        overflow: hidden;
    }

    .dash-table {
        font-family: var(--font) !important;
    }

    /* ── Accessibility ── */
    .nav-tile:focus-visible,
    .view-all-btn:focus-visible,
    .notif-read-btn:focus-visible {
        outline: 2px solid var(--brand-soft);
        outline-offset: 2px;
    }

    @media (prefers-reduced-motion: reduce) {

        .kpi-card,
        .nav-tile,
        .notif-card,
        .notif-unread-dot,
        .notif-skeleton {
            animation: none !important;
        }

        .kpi-bar-fill {
            transition: none;
        }
    }

    /* ── Responsive ── */
    @media (max-width: 1279px) {
        .kpi-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (max-width: 1024px) {
        .summary-strip {
            gap: 24px;
        }

        .summary-quick-stats {
            margin-left: 0;
        }
    }

    @media (max-width: 768px) {
        .dash-root {
            padding: 4px 16px 40px;
        }

        .dash-hero {
            flex-direction: column;
            align-items: flex-start;
            gap: 20px;
            padding: 24px;
        }

        .dash-title {
            font-size: 24px;
        }

        .dash-hero-meta {
            flex-direction: row;
            flex-wrap: wrap;
            align-items: flex-start;
        }

        .kpi-grid {
            grid-template-columns: 1fr 1fr;
        }

        .summary-strip {
            flex-direction: column;
            align-items: flex-start;
            padding: 20px;
            gap: 20px;
        }

        .nav-grid,
        .notif-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 420px) {
        .kpi-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

</body>

</html>
<?php $conn->close(); ?>