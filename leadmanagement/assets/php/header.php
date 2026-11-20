<?php
// assets/php/header.php – top bar used across pages with main dashboard return
$k = isset($_GET['k']) ? '?k=' . urlencode($_GET['k']) : '';
?>
<header class="header">
    <div class="logo">
        <i class="fa-solid fa-people-roof"></i>
        <span>Lead Management</span>
    </div>
    <div class="right-section">
        <a href="../admin_dashboard.php<?php echo $k; ?>" class="back-link" title="Return to main admin console">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
            <span>Exit Console</span>
        </a>
        <div class="user-avatar" title="Active Admin Session">
            <i class="fa-regular fa-user"></i>
        </div>
    </div>
</header>
