<?php
include("../connection.php");
include("functions/module_functions.php");
include("functions/login_functions.php");
$key = $_GET['k'];
$key_decoded = base64url_decode_mdl($key);
$key_parts = explode("|", $key_decoded);
$checkkey = base64url_decode_mdl($key_parts[count($key_parts) - 1]);
$actkey = "";
for ($i = 0; $i < count($key_parts) - 1; $i++) {
  $actkey = $actkey ? $actkey . "|" . $key_parts[$i] : $key_parts[$i];
}
if ($actkey != $checkkey) {
  echo "<div class=\"col-md-12 text-center black\"><h3>OOPS!!Something went wrong. Please Try after sometime.</h3></div>";
  die;
}
$loginid = $key_parts[0];
$loginroleid = $key_parts[1];
$companyid = $key_parts[2];
$subcompanyid = $key_parts[3];
$projectid = $key_parts[4];
$logintype = $key_parts[5];
$newkey = "x|x|" . $key_parts[2] . "|" . $key_parts[3] . "|" . $key_parts[4] . "|x";
$encodednewkey = base64url_encode_mdl($newkey);
$keycompany = base64url_encode_mdl($newkey . "|" . $encodednewkey);

$logininfo = loginuserinfo($authconn, $loginid, "loginid", $loginid);
$loginname = "";
$loginrolename = "";
if (count($logininfo) > 0) {
  foreach ($logininfo as $lgkey) {
    $loginroleid = $lgkey['loginroleid'];
    $loginname = $lgkey['loginname'];
    $loginrolename = $lgkey['loginrolename'];
    $logintype = $lgkey['loginrolename'];
  }

}

$projectname = "";
$prjinfo = getprojectdetailsmodules($conn, $companyid, $subcompanyid, "projectid", $projectid);
if (count($prjinfo) > 0) {
  $projectname = $prjinfo[0]['projectname'];
}
$protocol = $_SERVER['PROTOCOL'] = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 'https' : 'http';
$url = basename($_SERVER["PHP_SELF"]);
$rooturl = $protocol . '://' . $_SERVER['HTTP_HOST'];
$baseurl = $rooturl . str_replace($url, "", $_SERVER["PHP_SELF"]);
if ($_SERVER['HTTP_HOST'] == "localhost" || $_SERVER['HTTP_HOST'] == "192.168.1.65") {
  $prefix = $rooturl . "/krf_amanaccounting/";
} else {
  $prefix = $rooturl . "/";
}
$rooturl = $prefix;
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Modules</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" type="text/css" href="css/custom.css">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      min-height: 100vh;
      overflow-x: hidden;
    }

    /* Sidebar Styling */
    .left-sidebar {
      width: 260px;
      position: fixed;
      top: 0;
      left: -260px;
      /* Hidden by default on mobile */
      height: 100vh;
      background-color: #001B48;
      /* Deep Blue from theme */
      color: #fff;
      padding-top: 20px;
      transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
      z-index: 1040;
      box-shadow: 4px 0 10px rgba(0, 0, 0, 0.1);
      display: flex;
      flex-direction: column;
    }

    .left-sidebar.active {
      left: 0;
      /* Show when active */
    }

    .left-sidebar .brand-title {
      font-size: 1.5rem;
      font-weight: 700;
      padding: 0 20px 20px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      margin-bottom: 10px;
      letter-spacing: 1px;
    }

    .left-sidebar a {
      color: rgba(255, 255, 255, 0.8);
      padding: 12px 25px;
      display: flex;
      align-items: center;
      text-decoration: none;
      font-size: 0.95rem;
      transition: all 0.2s ease;
      border-left: 4px solid transparent;
    }

    .left-sidebar a i {
      width: 25px;
      text-align: center;
      margin-right: 10px;
    }

    .left-sidebar a:hover,
    .left-sidebar a.active-link {
      background-color: rgba(255, 255, 255, 0.1);
      color: #fff;
      border-left: 4px solid #17A2B8;
      /* Accent Color */
    }

    /* Main Content Area */
    .main-content {
      margin-left: 0;
      padding: 20px;
      transition: margin-left 0.3s ease;
      width: 100%;
    }

    /* Desktop View */
    @media (min-width: 992px) {
      .left-sidebar {
        left: 0;
        /* Always visible */
      }

      .main-content {
        margin-left: 260px;
        width: calc(100% - 260px);
      }

      .sidebar-toggle {
        display: none;
      }
    }

    /* Mobile Toggle Button */
    .sidebar-toggle {
      position: fixed;
      top: 15px;
      left: 15px;
      z-index: 1050;
      background-color: #001B48;
      color: white;
      border: none;
      padding: 8px 12px;
      border-radius: 4px;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
      cursor: pointer;
    }

    .sidebar-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      z-index: 1030;
    }

    .sidebar-overlay.active {
      display: block;
    }

    /* Iframe & Fullscreen Adaptations - Adjusted to keep sidebar visible */
    .is-iframe .main-content {
      padding: 15px !important;
    }
  </style>
  <script>
    // Optional: Keep iframe class for specific internal adjustments if needed, 
    // but sidebar hiding is removed.
    if (window.self !== window.top) {
      document.documentElement.classList.add('is-iframe');
    }
  </script>
</head>

<body data-key="<?php echo $key; ?>" data-routurl="<?php echo $rooturl; ?>"
  data-projectname="<?php echo $projectname; ?>" data-loginid='<?php echo $loginid; ?>'
  data-loginroleid='<?php echo $loginroleid; ?>' data-companyid='<?php echo $companyid; ?>'
  data-subcompanyid='<?php echo $subcompanyid; ?>' data-projectid='<?php echo $projectid; ?>'>
  <!-- Sidebar Overlay -->
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <!-- Sidebar Toggle Button -->
  <button class="sidebar-toggle d-lg-none" id="toggleSidebar">
    <i class="fa fa-bars"></i>
  </button>

  <!-- Left Sidebar -->
  <div class="left-sidebar" id="leftSidebar">
    <div class="brand-title">
      <i class="fa fa-cubes me-2"></i> Modules
    </div>
    <a href="index.php?k=<?php echo $key; ?>"><i class="fa fa-list-alt"></i> Modules Master</a>
    <a href="groupmaster.php?k=<?php echo $key; ?>"><i class="fa fa-object-group"></i> Group Master</a>
    <a href="allocation.php?k=<?php echo $key; ?>"><i class="fa fa-exchange-alt"></i> Allocation</a>
    <a href="setpassord_tomodule.php?k=<?php echo $key; ?>"><i class="fa fa-key"></i> Set Password</a>
    <a href="indexdisplay.php?k=<?php echo $key; ?>" class="mt-auto bg-danger text-white"><i
        class="fa fa-arrow-left"></i> Back to Dashboard</a>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const toggleBtn = document.getElementById('toggleSidebar');
      const sidebar = document.getElementById('leftSidebar');
      const overlay = document.getElementById('sidebarOverlay');

      function closeSidebar() {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
      }

      if (toggleBtn) {
        toggleBtn.addEventListener('click', function (e) {
          e.stopPropagation();
          sidebar.classList.toggle('active');
          overlay.classList.toggle('active');
        });
      }

      if (overlay) {
        overlay.addEventListener('click', closeSidebar);
      }

      // Auto-set active link
      const currentUrl = window.location.href;
      document.querySelectorAll('.left-sidebar a').forEach(link => {
        if (link.href === currentUrl) {
          link.classList.add('active-link');
        }
      });
    });
  </script>
  <!-- Main Content -->
  <div class="main-content">
    <div class="container-fixed">