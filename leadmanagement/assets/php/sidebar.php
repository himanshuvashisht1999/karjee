<?php
// assets/php/sidebar.php – collapsible navigation with dynamic active states
$currentPage = basename($_SERVER['SCRIPT_NAME']);
$k = isset($_GET['k']) ? '?k=' . urlencode($_GET['k']) : '';
?>
<nav class="sidebar">
    <ul>
        <li class="<?php echo ($currentPage == 'index.php' || $currentPage == '') ? 'active' : ''; ?>">
            <a href="index.php<?php echo $k; ?>">
                <i class="fa-solid fa-chart-pie"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="<?php echo ($currentPage == 'register.php') ? 'active' : ''; ?>">
            <a href="register.php<?php echo $k; ?>">
                <i class="fa-solid fa-user-plus"></i>
                <span>New Lead</span>
            </a>
        </li>
        <li>
            <a href="#">
                <i class="fa-solid fa-sliders"></i>
                <span>Settings</span>
            </a>
        </li>
    </ul>
</nav>
