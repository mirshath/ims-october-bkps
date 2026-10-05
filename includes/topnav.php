<?php
$sessionName = $_SESSION['username'];
?>
<link rel="stylesheet" href="css/new_style_css/topnav-glass.css">

<nav class="navbar navbar-expand topbar mb-4 static-top gl-topbar">

    <!-- Sidebar toggle (mobile) -->
    <button id="sidebarToggleTop" class="gl-icon-btn d-md-none mr-2" type="button" aria-label="Toggle sidebar">
        <i class="fa fa-bars"></i>
    </button>

    <!-- Brand / welcome -->
    <div class="gl-brand">
        <span class="gl-brand-mark">IMS</span>
        <span class="gl-brand-text d-none d-sm-inline">Welcome to Insititute Management System</span>
    </div>

    <ul class="navbar-nav ml-auto align-items-center">

        <!-- User -->
        <li class="nav-item dropdown no-arrow">
            <a class="gl-user dropdown-toggle" href="#" id="userDropdown" role="button"
                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <img class="gl-avatar"
                    src="<?= !empty($sessionProfileImage) ? $sessionProfileImage : 'admin/uploads/company_profiles/download.png'; ?>"
                    alt="User Profile">
                <span class="gl-user-name d-none d-lg-inline"><?= htmlspecialchars($sessionName) ?></span>
            </a>
            <div class="dropdown-menu dropdown-menu-right gl-menu" aria-labelledby="userDropdown">
                <a class="gl-menu-item" href="myProfile.php">
                    <i class="fas fa-user fa-fw"></i> Profile
                </a>
                <div class="gl-menu-sep"></div>
                <a class="gl-menu-item gl-menu-danger" href="logout.php">
                    <i class="fas fa-sign-out-alt fa-fw"></i> Logout
                </a>
            </div>
        </li>
    </ul>
</nav>