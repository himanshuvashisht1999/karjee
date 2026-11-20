<?php
include("connection.php");
include("functions/module_functions.php");
include("../loginCell/functions/login_functions.php");
$grpid = "";
if (isset($_GET['grpid'])) {
  $grpid = $_GET['grpid'];
}
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
$prefix = "";
$referrer = "";
$countryid = "";
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
  <meta charset="UTF-8">
  <title>Module Definition</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" type="text/css" href="css/custom.css">
</head>

<body data-key='<?php echo $key; ?>' data-loginid='<?php echo $loginid; ?>'
  data-loginroleid='<?php echo $loginroleid; ?>' data-companyid='<?php echo $companyid; ?>'
  data-subcompanyid='<?php echo $subcompanyid; ?>' data-projectid='<?php echo $projectid; ?>'
  data-baseurl='<?php echo $baseurl; ?>' data-routurl='<?php echo $rooturl; ?>'>
  <!-- Bootstrap Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-deepblue">
    <div class="container-fluid">
      <!-- Left: Project Name -->
      <a class="navbar-brand" href="#"><?php echo htmlspecialchars($projectname); ?></a>
      <!-- Toggler for mobile view -->
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown">
        <span class="navbar-toggler-icon"></span>
      </button>

      <!-- Right: Login Name and Logout Button -->
      <div class="collapse navbar-collapse justify-content-end" id="navbarNavDropdown">
        <ul class="navbar-nav align-items-center">
          <li class="nav-item me-3">
            <span class="nav-link text-white">
              <?php echo htmlspecialchars($loginname); ?>
            </span>
          </li>
          <li class="nav-item">
            <button class="btn btn-outline-light btn-sm" onclick="logoutUser()">Logout</button>
          </li>
        </ul>
      </div>
    </div>
  </nav>
  <div class="container mt-4">
    <div class="row">
      <!-- Sidebar -->
      <div class="col-md-12">
        <div class="sidebar">
          <h5 class="mb-3">Modules</h5>
          <div class="accordion" id="sidebarAccordion">
            <?php
            // echo "role: ".$loginroleid;
            if ($loginroleid == 0) {
              ?>
              <div class="accordion-item my-2">
                <h2 class="accordion-header" id="00Heading">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                    data-bs-target="#00module" aria-expanded="false">
                    WEB ADMIN
                  </button>
                </h2>
                <div id="00module" class="accordion-collapse collapse" aria-labelledby="00Heading"
                  data-bs-parent="#sidebarAccordion">
                  <div class="accordion-body">
                    <?php
                    echo '<a class="btn btn-outline-primary w-100 my-2 d-block" href="' . $rooturl . 'modulesCell/index.php?k=' . htmlspecialchars($key) . '">Module Management</a>';
                    echo '<a class="btn btn-outline-primary w-100 my-2 d-block" href="' . $rooturl . 'loginCell/loginmanagement.php?k=' . htmlspecialchars($key) . '">Login Management</a>';
                    echo '<a class="btn btn-outline-primary w-100 my-2 d-block" href="' . $rooturl . 'adminmodule/index.php?k=' . htmlspecialchars($key) . '">Masters</a>';
                    ?>
                  </div>
                </div>
              </div>
              <?php
            }
            $groupbyin = getAllClientModules($authconn, $companyid, $subcompanyid, $projectid, "groupbygroupid", "0");
            $ungroupbtn = "";
            $isexistanymodule = 0;
            $groupname = "";
            $renderedGroups = [];
            if (count($groupbyin) > 0) {
              $accIndex = 0;
              foreach ($groupbyin as $grpkey) {
                $groupname = "";
                if ($loginroleid == 0) {
                  $modulein = getAllClientModules($authconn, $companyid, $subcompanyid, $projectid, "groupid", $grpkey['groupid']);
                } else {
                  $allmodules = allauthorizedmoduleid($authconn, $companyid, $subcompanyid, $projectid, $loginid);
                  $ggpidiwthmoduleid = $grpkey['groupid'] . "|" . $allmodules;
                  $modulein = OnlyProject_loginmodulesdetails_frommaster($authconn, $companyid, $subcompanyid, $projectid, "groupidwithmoduleid", $ggpidiwthmoduleid);
                }
                if (count($modulein) > 0) {
                  $isexistanymodule++;
                  $btnalign = "";
                  $btnsize = "";
                  if ($grpkey['groupid'] == 0) {
                    foreach ($modulein as $mdllkey) {

                      if ($mdllkey['groupid'] == 0 && $mdllkey['status'] == "active") {
                        // echo "==".$mdllkey['modulealign'];
            
                        $plock = '';
                        if ($mdllkey['ispasswordprotected'] == "yes") {
                          $plock = '<i class="fa fa-key float-right" aria-hidden="true"></i>';
                        }

                        if ($mdllkey['modulesize'] == "full") {
                          $btnsize = "w-100";
                        } else if ($mdllkey['modulesize'] == "half") {
                          $btnsize = "w-50";
                        } else if ($mdllkey['modulesize'] == "quarter") {
                          $btnsize = "w-25";
                        } else {
                          $btnsize = "w-100";
                        }

                        if ($mdllkey['modulealign'] == "right") {
                          $btnalign = "justify-content-end";
                        } else if ($mdllkey['modulealign'] == "middle") {
                          $btnalign = "justify-content-center";
                        } else {
                          $btnalign = "justify-content-start";
                        }

                        $ungroupbtn .= '<div class="d-flex mt-4 ' . $btnalign . '"><button class="btn btn-outline-primary ' . $btnsize . ' my-2 d-block btnaction" data-modulefoldername=' . $rooturl . htmlspecialchars($mdllkey['modulefoldername']) . ' data-ispasswordprotected=' . $mdllkey['ispasswordprotected'] . ' data-key=' . htmlspecialchars($key) . ' data-moduleid=' . htmlspecialchars($mdllkey['moduleid']) . ' data-modulename=' . htmlspecialchars($mdllkey['modulename']) . '>' . htmlspecialchars($mdllkey['modulename']) . ' ' . $plock . '</button></div>';
                      }
                    }
                  } else {
                    if (in_array($grpkey['groupid'], $renderedGroups))
                      continue;
                    $renderedGroups[] = $grpkey['groupid'];

                    $grpinf = getAllClientGroups($authconn, $companyid, $subcompanyid, $projectid, "groupid", $grpkey['groupid']);

                    $groupname = $grpinf[0]['groupname'] ?? "Group " . $grpkey['groupid'];
                    $vieworderby = $grpinf[0]['vieworderby'] ?? $grpkey['vieworderby'];
                    $accordionId = "itemOne" . $accIndex++;
                  }
                  if ($groupname) {
                    ?>
                    <div class="accordion-item my-2 groupdiv" data-groupviewno="<?php echo $vieworderby; ?>">
                      <h2 class="accordion-header" id="heading_<?php echo $grpkey['groupid']; ?>">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                          data-bs-target="#collapse_<?php echo $grpkey['groupid']; ?>" aria-expanded="false"
                          aria-controls="collapse_<?php echo $grpkey['groupid']; ?>">
                          <?php echo htmlspecialchars($groupname); ?>
                        </button>
                      </h2>
                      <div id="collapse_<?php echo $grpkey['groupid']; ?>" class="accordion-collapse collapse"
                        aria-labelledby="heading_<?php echo $grpkey['groupid']; ?>" data-bs-parent="#sidebarAccordion">
                        <div class="accordion-body">
                          <?php
                          foreach ($modulein as $mdllkey) {
                            $plock = '';
                            if ($mdllkey['ispasswordprotected'] == "yes") {
                              $plock = '<i class="fa fa-key float-right" aria-hidden="true"></i>';
                            }

                            echo '<button class="btn btn-outline-primary w-100 my-2 d-block btnaction" data-modulefoldername=' . $rooturl . htmlspecialchars($mdllkey['modulefoldername']) . ' data-ispasswordprotected=' . $mdllkey['ispasswordprotected'] . ' data-key=' . htmlspecialchars($key) . ' data-moduleid=' . htmlspecialchars($mdllkey['moduleid']) . ' data-modulename=' . htmlspecialchars($mdllkey['modulename']) . '>' . htmlspecialchars($mdllkey['modulename']) . ' ' . $plock . '</button>';
                          }
                          ?>
                        </div>
                      </div>
                    </div>
                    <?php
                  }
                }
              }
              // Show ungrouped modules
              if (!empty($ungroupbtn)) {
                // echo '<div class="mt-4 "><h6>Ungrouped Modules</h6>' . $ungroupbtn . '</div>';
                echo $ungroupbtn;
              }

              if ($isexistanymodule == 0) {
                echo '<div class="mt-4 text-center"><h6>You currently do not have access to any authorized modules</h6></div>';
              }
            } else {
              echo '<div class="alert alert-info d-flex align-items-center" role="alert">
                      <i class="fas fa-info-circle me-2"></i> Your company has been registered, but no modules have been defined for your account yet.  Your account will be activated within 24 hours. </div>';
            }
            ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>

</html>
<!-- Bootstrap 5 JS Bundle -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="text/javascript" src="js/modules.js"></script>
<script type="text/javascript" src="js/iziToast.js"></script>
<script type="text/javascript" src="js/actioncontrol.js"></script>
<script src="../loginCell/js/login.js"></script>
<script>
  $(document).on("click", ".btnaction", function (e) {
    e.preventDefault(); // ✅ prevent accidental immediate navigation
    var routurl = $("body").data("routurl");
    var key = $(this).data("key");
    var modulefoldername = $(this).data("modulefoldername");
    var moduleid = $(this).data("moduleid");
    var modulename = $(this).data("modulename");
    var ispasswordprotected = $(this).data("ispasswordprotected");
    // Construct module URL
    var weburl = modulefoldername + '?k=' + key + '&mdid=' + moduleid;
    if (ispasswordprotected === "yes") {
      // Pass both moduleid and weburl so modal login can redirect later
      checkmoduleHaspasswordProtected(moduleid, weburl);
    } else {
      // Directly go to module if no password required
      window.location.href = weburl;
    }
  });



  $(document).ready(function () {
    var grpid = "<?php echo $grpid; ?>";
    if (grpid) {
      let collapseId = "#collapse_" + grpid; // Example: collapse_1
      let $collapseElem = $(collapseId);

      if ($collapseElem.length > 0) {
        // Open the accordion by adding 'show'
        $collapseElem.addClass("show");

        // Also set aria-expanded="true" for its button
        let $accordionButton = $('[data-bs-target="' + collapseId + '"]');
        $accordionButton.attr("aria-expanded", "true");

        // Smooth scroll to it
        $("html, body").animate({
          scrollTop: $collapseElem.offset().top - 80 // adjust offset for header
        }, 600);
      }
    }
  });
  sortGroupDivs("#sidebarAccordion");

  function sortGroupDivs(containerSelector, order = "asc") {
    var $container = $(containerSelector);
    var groups = $container.find(".groupdiv").get();

    groups.sort(function (a, b) {
      var av = $(a).data("groupviewno");
      var bv = $(b).data("groupviewno");

      // If both missing, keep relative order
      if (av == null && bv == null) return 0;

      // If a missing → put after b
      if (av == null) return 1;

      // If b missing → put after a
      if (bv == null) return -1;

      // Both present → sort ascending or descending
      return order === "asc" ? av - bv : bv - av;
    });

    $.each(groups, function (i, el) {
      $container.append(el); // moves element
    });
  }
</script>