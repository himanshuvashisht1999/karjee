<?php
$hide_heading = true;
$hide_footer = true; // Set hide_footer to true to remove the site footer in dashboard
include("header.php");

if (!isset($loginid) || $loginid == "") {
    echo "<div class='text-center mt-5'><h3>Please Login First</h3></div>";
    exit;
}

// Immediate logout if loginrolename is "employee"
if (isset($loginrolename) && strtolower(trim($loginrolename)) === 'employee') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();

    $redirectUrl = (!empty($prefix) ? $prefix : '') . 'index.php';
    echo "<script>
        window.location.href = '" . $redirectUrl . "';
    </script>";
    exit;
}
?>

<style>
    :root {
        --dash-primary: #667eea;
        --dash-secondary: #764ba2;
        --dash-gradient: linear-gradient(135deg, var(--dash-primary) 0%, var(--dash-secondary) 100%);
        --dash-sidebar-bg: #f8fafc;
        --dash-sidebar-width: 260px;
        --dash-sidebar-collapsed-width: 70px;
        --dash-header-offset: 78px;
    }

    /* Dashboard Header Section Margin and Clearance */
    .navbar-premium {
        margin-bottom: 8px !important;
    }

    body {
        overflow: hidden;
        /* Prevent main page from scrolling */
        background-color: #f1f5f9;
        font-family: 'Inter', 'Segoe UI', sans-serif;
    }

    /* Sidebar Styling */
    .sidebar {
        position: fixed;
        top: var(--dash-header-offset);
        bottom: 0;
        left: 0;
        z-index: 1000;
        padding: 0;
        width: var(--dash-sidebar-width);
        background: var(--dash-sidebar-bg) !important;
        border-right: 1px solid #e2e8f0;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .sidebar-sticky {
        position: relative;
        top: 0;
        height: 100%;
        padding: 1rem 0;
        overflow-x: hidden;
        overflow-y: auto;
    }

    /* Main Content Area */
    main[role="main"] {
        margin-left: var(--dash-sidebar-width);
        width: calc(100% - var(--dash-sidebar-width));
        padding-top: 0;
        position: relative;
        background-color: #f1f5f9;
        height: calc(100vh - var(--dash-header-offset));
        /* Match navbar height and clearance */
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Sidebar Collapsed State (Desktop Only >= 992px) */
    @media (min-width: 992px) {
        .sidebar.collapsed {
            width: var(--dash-sidebar-collapsed-width) !important;
        }

        .sidebar.collapsed+main[role="main"] {
            margin-left: var(--dash-sidebar-collapsed-width);
            width: calc(100% - var(--dash-sidebar-collapsed-width));
        }

        /* Hide text/elements when collapsed on desktop */
        .sidebar.collapsed .sidebar-header-text,
        .sidebar.collapsed .sidebar-btn span,
        .sidebar.collapsed .dashboard-link,
        .sidebar.collapsed .sidebar-btn::after,
        .sidebar.collapsed small {
            display: none !important;
        }

        .sidebar.collapsed .sidebar-btn {
            justify-content: center;
            padding: 12px 0;
        }

        .sidebar.collapsed .sidebar-btn i {
            margin-right: 0;
            width: auto;
            font-size: 1.25rem;
        }

        .sidebar.collapsed .dashboard-link::before {
            display: none;
        }

        .sidebar.collapsed .accordion .card-header {
            padding: 0.25rem 0;
        }

        .sidebar.collapsed .sidebar-header {
            padding: 15px 0;
            justify-content: center;
        }
    }

    /* Mobile Responsive Logic (< 992px) - Left Menu is NEVER collapsed or hidden into a tiny strip */
    @media (max-width: 991.98px) {
        .sidebar,
        .sidebar.collapsed {
            width: 285px !important;
            left: -285px;
            /* Hide off-screen by default until Menu button is tapped */
            top: 0;
            height: 100%;
            z-index: 2000;
            box-shadow: 10px 0 25px rgba(0, 0, 0, 0.2) !important;
            background: #ffffff !important;
            transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }

        .sidebar.show,
        .sidebar.collapsed.show {
            left: 0 !important;
            transform: none !important;
        }

        /* Ensure all menu text, badges, and headers are ALWAYS fully visible on mobile */
        .sidebar .sidebar-header-text,
        .sidebar.collapsed .sidebar-header-text,
        .sidebar .sidebar-btn span,
        .sidebar.collapsed .sidebar-btn span,
        .sidebar .dashboard-link,
        .sidebar.collapsed .dashboard-link,
        .sidebar small,
        .sidebar.collapsed small {
            display: inline-block !important;
        }

        .sidebar .dashboard-link,
        .sidebar.collapsed .dashboard-link {
            display: block !important;
        }

        .sidebar .sidebar-btn,
        .sidebar.collapsed .sidebar-btn {
            justify-content: flex-start !important;
            padding: 10px 15px !important;
        }

        .sidebar .sidebar-btn i,
        .sidebar.collapsed .sidebar-btn i {
            margin-right: 12px !important;
            width: 20px !important;
            font-size: 1.1rem !important;
        }

        .sidebar .sidebar-header,
        .sidebar.collapsed .sidebar-header {
            padding: 15px 20px !important;
            justify-content: space-between !important;
        }

        .mobile-dashboard-bar {
            position: sticky;
            top: 0;
            z-index: 1010;
            background: #ffffff !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.06);
        }

        main[role="main"] {
            margin-left: 0 !important;
            width: 100% !important;
            height: calc(100vh - var(--dash-header-offset)) !important;
        }

        #content_frame {
            height: calc(100vh - var(--dash-header-offset) - 55px) !important;
        }
    }

    #sidebarToggle {
        background: transparent;
        border: none;
        color: #64748b;
        cursor: pointer;
        padding: 5px;
        transition: all 0.2s;
        border-radius: 4px;
    }

    #sidebarToggle:hover {
        background: #e2e8f0;
        color: var(--dash-primary);
    }

    .sidebar-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 15px 20px;
        border-bottom: 1px solid #e2e8f0;
        margin-bottom: 10px;
    }



    /* Accordion / Sidebar Buttons Styling */
    .accordion .card {
        background: transparent;
        border: none;
        margin-bottom: 4px;
    }

    .accordion .card-header {
        padding: 0.25rem 0.75rem;
        background: transparent;
        border: none;
    }

    .sidebar-btn {
        width: 100%;
        text-align: left;
        padding: 10px 15px;
        color: #475569;
        font-weight: 600;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        border-radius: 8px;
        border: none;
        background: transparent;
        transition: all 0.2s;
        text-decoration: none !important;
        outline: none !important;
    }

    .sidebar-btn:hover {
        background-color: #e2e8f0;
        color: var(--dash-primary);
    }

    .sidebar-btn[aria-expanded="true"] {
        color: var(--dash-primary);
        background-color: #eff6ff;
    }

    .sidebar-btn i {
        width: 20px;
        margin-right: 12px;
        font-size: 1.1rem;
    }

    .dashboard-link {
        display: block;
        padding: 8px 15px 8px 45px;
        margin: 2px 10px;
        color: #64748b;
        font-size: 0.85rem;
        font-weight: 500;
        text-decoration: none !important;
        border-radius: 6px;
        transition: all 0.2s;
        position: relative;
    }

    .dashboard-link::before {
        content: '';
        position: absolute;
        left: 25px;
        top: 50%;
        width: 4px;
        height: 4px;
        background: #cbd5e1;
        border-radius: 50%;
        transform: translateY(-50%);
    }

    .dashboard-link:hover {
        background-color: #f1f5f9;
        color: var(--dash-primary);
        padding-left: 48px;
    }

    .dashboard-link.active {
        background: var(--dash-gradient) !important;
        color: white !important;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    #content_frame {
        width: 100%;
        height: calc(100vh - var(--dash-header-offset));
        /* Perfectly fills the remaining viewport */
        border: none;
        background: white;
        transition: opacity 0.3s;
        /* Hide default scrollbar but keep functionality */
        scrollbar-width: none;
        /* Firefox */
        -ms-overflow-style: none;
        /* IE and Edge */
    }

    #content_frame::-webkit-scrollbar {
        display: none;
        /* Chrome, Safari, Opera */
    }

    .spinner-border {
        width: 3rem;
        height: 3rem;
        color: var(--dash-primary);
    }
</style>

<div class="container-fluid px-0">
    <div class="row m-0">
        <!-- Sidebar -->
        <nav id="sidebarMenu" class="sidebar collapse d-lg-block shadow-sm">
            <div class="sidebar-sticky">
                <div class="sidebar-header">
                    <span class="sidebar-header-text fw-bold text-primary mb-0">Control Panel</span>
                    <button id="sidebarToggle" title="Toggle Sidebar" class="btn d-none d-lg-block">
                        <i class="fa fa-bars"></i>
                    </button>
                    <!-- Mobile Close Button -->
                    <button class="btn btn-sm d-lg-none" type="button" data-toggle="collapse"
                        data-target="#sidebarMenu">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                <div class="px-4 py-1 mb-2 d-none d-lg-block">
                    <small class="text-muted"><?php echo $loginrolename; ?></small>
                </div>
                <div class="accordion" id="accordionSidebar">
                    <?php if ($loginroleid == 0): // Super Admin ?>
                        <div class="card">
                            <div class="card-header p-0" id="headingAdmin">
                                <button class="btn sidebar-btn" type="button" data-toggle="collapse"
                                    data-target="#collapseAdmin" aria-expanded="false" aria-controls="collapseAdmin">
                                    <i class="fa fa-shield-alt text-danger"></i> <span>Web Admin</span>
                                </button>
                            </div>
                            <div id="collapseAdmin" class="collapse" aria-labelledby="headingAdmin"
                                data-parent="#accordionSidebar">
                                <div class="card-body p-0 pb-2">
                                    <a href="<?php echo $rooturl; ?>modulesCell/index.php?k=<?php echo htmlspecialchars($key); ?>&iframe=true" class="dashboard-link"
                                        data-url="<?php echo $rooturl; ?>modulesCell/index.php?k=<?php echo htmlspecialchars($key); ?>&iframe=true">
                                        Module Management
                                    </a>
                                    <a href="<?php echo $rooturl; ?>loginCell/loginmanagement.php?k=<?php echo htmlspecialchars($key); ?>&iframe=true" class="dashboard-link"
                                        data-url="<?php echo $rooturl; ?>loginCell/loginmanagement.php?k=<?php echo htmlspecialchars($key); ?>&iframe=true">
                                        Login Management
                                    </a>
                                    <a href="<?php echo $rooturl; ?>adminmodule/index.php?k=<?php echo htmlspecialchars($key); ?>&iframe=true" class="dashboard-link"
                                        data-url="<?php echo $rooturl; ?>adminmodule/index.php?k=<?php echo htmlspecialchars($key); ?>&iframe=true">
                                        System Masters
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php
                    // Fetch all authorized modules first
                    if ($loginroleid == 0) {
                        $allModulesList = getAllClientModules($authconn, $companyid, $subcompanyid, $projectid, "status", "active");
                    } else {
                        $authIds = allauthorizedmoduleid($authconn, $companyid, $subcompanyid, $projectid, $loginid);
                        $allModulesList = OnlyProject_loginmodulesdetails_frommaster($authconn, $companyid, $subcompanyid, $projectid, "allwithauth", $authIds);
                    }

                    // Pre-initialize groups in the correct order
                    $categorizedModules = [];
                    $allGroups = getAllClientGroups($authconn, $companyid, $subcompanyid, $projectid, "status", "active");
                    $groupMap = [];
                    if (is_array($allGroups)) {
                        foreach ($allGroups as $group) {
                            $categorizedModules[$group['groupname']] = [];
                            $groupMap[$group['groupid']] = $group['groupname'];
                        }
                    }
                    if (!isset($categorizedModules['General'])) {
                        $categorizedModules['General'] = [];
                    }

                    // Categorize modules into pre-initialized groups
                    if (is_array($allModulesList) && count($allModulesList) > 0) {
                        foreach ($allModulesList as $mod) {
                            $groupid = $mod['groupid'];
                            $groupname = ($groupid == 0) ? 'General' : ($groupMap[$groupid] ?? 'General');

                            if (!isset($categorizedModules[$groupname])) {
                                $categorizedModules[$groupname] = [];
                            }
                            $categorizedModules[$groupname][] = $mod;
                        }
                    }

                    // Render Database Groups
                    $groupCounter = 1;
                    $groupIcons = [
                        'Production' => 'fa-industry',
                        'Inventory' => 'fa-boxes',
                        'HR' => 'fa-users',
                        'Sales' => 'fa-chart-line',
                        'General' => 'fa-th-large'
                    ];

                    foreach ($categorizedModules as $title => $modules):
                        if (empty($modules))
                            continue;

                        // Try to find a matching icon
                        $icon = 'fa-folder';
                        foreach ($groupIcons as $keyIcon => $valIcon) {
                            if (stripos($title, $keyIcon) !== false) {
                                $icon = $valIcon;
                                break;
                            }
                        }

                        $collapseId = "collapseGroup" . $groupCounter;
                        ?>
                        <div class="card">
                            <div class="card-header p-0" id="heading<?php echo $groupCounter; ?>">
                                <button class="btn sidebar-btn" type="button" data-toggle="collapse"
                                    data-target="#<?php echo $collapseId; ?>" aria-expanded="false"
                                    aria-controls="<?php echo $collapseId; ?>">
                                    <i class="fa <?php echo $icon; ?> text-primary"></i> <span><?php echo $title; ?></span>
                                </button>
                            </div>
                            <div id="<?php echo $collapseId; ?>" class="collapse" data-parent="#accordionSidebar">
                                <div class="card-body p-0 pb-2">
                                    <?php foreach ($modules as $mod):
                                        // Specific modules to open OUTSIDE iframe
                                        $isExternal = ($mod['moduleid'] == "441608907" || $mod['moduleid'] == "3794905" || $mod['moduleid'] == "409586670");

                                        $weburl = $mod['modulefoldername'] . '?k=' . $key . '&mdid=' . $mod['moduleid'];

                                        // Logic divergence
                                        if ($isExternal) {
                                            $weburl .= '&iframe=false'; // No iframe param or explicit false
                                            $target = "_self"; // Redirect main page
                                        } else {
                                            $weburl .= '&iframe=true';
                                            $target = "content_frame";
                                        }

                                        ?>
                                        <a href="<?php echo $rooturl . $weburl; ?>" class="dashboard-link"
                                            target="<?php echo $target; ?>" data-url="<?php echo $rooturl . $weburl; ?>"
                                            data-moduleid="<?php echo $mod['moduleid']; ?>"
                                            data-ispass="<?php echo $mod['ispasswordprotected']; ?>"
                                            data-external="<?php echo $isExternal ? 'true' : 'false'; ?>">
                                            <?php echo htmlspecialchars($mod['modulename']); ?>
                                            <?php if ($mod['ispasswordprotected'] == 'yes'): ?>
                                                <i class="fa fa-lock float-right mt-1 small opacity-50"></i>
                                            <?php endif; ?>
                                            <?php if ($isExternal): ?>
                                                <i class="fa fa-external-link-alt float-right mt-1 mr-2 small opacity-50"></i>
                                            <?php endif; ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <?php
                        $groupCounter++;
                    endforeach; ?>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main role="main" class="px-0">
            <!-- Mobile Toggle Bar -->
            <div class="d-lg-none p-3 bg-white border-bottom d-flex align-items-center justify-content-between mobile-dashboard-bar">
                <h6 class="mb-0 fw-bold text-primary">Control Panel</h6>
                <button class="btn btn-outline-primary btn-sm" type="button" data-toggle="collapse"
                    data-target="#sidebarMenu">
                    <i class="fa fa-bars"></i> Menu
                </button>
            </div>
            <div id="loading-spinner" class="d-none">
                <div class="spinner-border" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
            </div>
            <!-- Iframe to load modules -->
            <iframe name="content_frame" id="content_frame"
                src="<?php echo $rooturl; ?>modulesCell/welcome.php?k=<?php echo htmlspecialchars($key); ?>&iframe=true"></iframe>
        </main>
    </div>
</div>


<?php include("footer.php"); ?>

<script>
    // Override functions from modules.js to support Iframe navigation for the Dashboard
    function checkModulePasswordProtection(moduleInfo, weburl) {
        if (moduleInfo.ispasswordprotected === "yes" && moduleInfo.loginstatus == "logout" && moduleInfo.loginroleid > 1) {
            const modalHtml = `
          <div class="modal fade" id="modulelogin_modal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content">
                <div class="modal-header bg-success">
                  <h5 class="modal-title text-white">Module Login</h5>
                  <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                  </button>
                </div>
                <div class="modal-body">
                  <label>Module Login Password</label>
                  <input type="password" class="form-control" id="moduleloginpassword" />
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                  <button class="btn btn-info"
                    onclick="checkmodulepasswordWithLoginid('moduleloginpassword', '${moduleInfo.mdid}', '${moduleInfo.loginid}', '${weburl}')">Login</button>
                </div>
              </div>
            </div>
          </div>
        `;

            $('#modulelogin_modal').remove();
            $('body').append(modalHtml);
            $('#modulelogin_modal').modal('show');
        } else {
            // Load in Iframe
            $('#content_frame').attr('src', weburl);
        }
    }

    function checkmodulepasswordWithLoginid(mdpasswrod_eid, moduleid, loginid, weburl) {
        var form_data = new FormData();
        form_data.append("companyid", $("body").data("companyid"));
        form_data.append("subcompanyid", $("body").data("subcompanyid"));
        form_data.append("projectid", $("body").data("projectid"));

        var modulepassword = $('#' + mdpasswrod_eid).val();
        if (modulepassword == "" || modulepassword == "undefined" || modulepassword == null) {
            if (typeof alertmessage === 'function') {
                alertmessage('error', 'Module login password is missing or wrong');
            } else {
                alert('Module login password is missing or wrong');
            }
            return false;
        }

        form_data.append("modulepassword", modulepassword);
        form_data.append("moduleid", moduleid);
        form_data.append("loginid", loginid);
        var routurl = $("body").data("routurl");

        $.ajax({
            type: "POST",
            url: routurl + "modulesCell/modal/modules_module.php?action=checkmodulepasswordWithLoginid",
            data: form_data,
            contentType: false,
            processData: false,
            cache: false,
            dataType: 'JSON',
            success: function (res) {
                if (res.status == "Success") {
                    $('#modulelogin_modal').modal('hide');
                    if (typeof alertmessage === 'function') {
                        alertmessage('success', res.message);
                    }
                    $('#content_frame').attr('src', weburl);
                } else {
                    if (typeof alertmessage === 'function') {
                        alertmessage('error', res.message);
                    } else {
                        alert(res.message);
                    }
                }
            }
        });
    }

    function updateUrlSessionKey(targetUrl, currentKey) {
        if (!targetUrl || !currentKey) return targetUrl;
        try {
            let urlObj = new URL(targetUrl, window.location.origin);
            if (urlObj.searchParams.has('k')) {
                urlObj.searchParams.set('k', currentKey);
            } else {
                urlObj.searchParams.append('k', currentKey);
            }
            return urlObj.toString();
        } catch (e) {
            if (targetUrl.indexOf('k=') !== -1) {
                return targetUrl.replace(/k=[^&]+/, 'k=' + encodeURIComponent(currentKey));
            } else {
                let sep = targetUrl.indexOf('?') !== -1 ? '&' : '?';
                return targetUrl + sep + 'k=' + encodeURIComponent(currentKey);
            }
        }
    }

    function isValidModuleUrl(url) {
        if (!url) return false;
        let urlStr = String(url).toLowerCase();
        if (urlStr.includes('logincell') || urlStr.includes('logout.php') || urlStr.includes('indexdisplayprojects.php') || urlStr.includes('admin_dashboard.php') || urlStr.includes('welcome.php')) {
            return false;
        }
        return true;
    }

    $(document).ready(function () {
        // Handle Sidebar Links
        $('.dashboard-link').on('click', function (e) {
            var url = $(this).attr('href');
            var isPass = $(this).data('ispass');
            var moduleId = $(this).data('moduleid');
            var isExternal = $(this).data('external');

            // 1. External Link Handling (Staff Payroll / Labour Payment)
            if (isExternal === true || isExternal === "true") {
                // Do NOT save to localStorage
                // Allow default behavior (page redirect)
                return;
            }

            // 2. Internal Module Handling
            e.preventDefault(); // We handle iframe loading manually for robustness

            // Persist active module (only for internal modules)
            if (url) {
                localStorage.setItem('last_active_module_url', url);
            }

            // Clear previous active states
            $('.dashboard-link').removeClass('active');
            $(this).addClass('active');

            if (url && url !== '#') {
                $('#loading-spinner').removeClass('d-none');
                $('#content_frame').css('opacity', '0.5');

                if (isPass === "yes" && typeof checkmoduleHaspasswordProtected === 'function') {
                    checkmoduleHaspasswordProtected(moduleId, url);
                } else {
                    // Force load into iframe via JS
                    $('#content_frame').attr('src', url);
                }
            }

            // Auto-close sidebar on mobile after selection
            if ($(window).width() < 992) {
                $('#sidebarMenu').collapse('hide');
            }
        });

        // Restore active module from localStorage
        let currentKey = "<?php echo htmlspecialchars($key); ?>";
        let lastActiveUrl = localStorage.getItem('last_active_module_url');

        if (lastActiveUrl && isValidModuleUrl(lastActiveUrl)) {
            let restoredUrl = updateUrlSessionKey(lastActiveUrl, currentKey);
            $('#content_frame').attr('src', restoredUrl);

            let targetModuleId = null;
            try {
                let uParams = new URL(restoredUrl, window.location.origin).searchParams;
                targetModuleId = uParams.get('mdid');
            } catch (e) {}

            let matchedLink = null;
            if (targetModuleId) {
                $('.dashboard-link').each(function () {
                    if (String($(this).data('moduleid')) === String(targetModuleId)) {
                        matchedLink = $(this);
                        return false;
                    }
                });
            }

            if (!matchedLink || !matchedLink.length) {
                let restoredPath = restoredUrl.split('?')[0];
                $('.dashboard-link').each(function () {
                    let linkUrl = $(this).attr('href') || $(this).data('url');
                    if (linkUrl && linkUrl.split('?')[0] === restoredPath) {
                        matchedLink = $(this);
                        return false;
                    }
                });
            }

            if (matchedLink && matchedLink.length) {
                $('.dashboard-link').removeClass('active');
                matchedLink.addClass('active');

                // Expand the parent group
                let $parentCollapse = matchedLink.closest('.collapse');
                $parentCollapse.addClass('show');
                $parentCollapse.closest('.card').find('.sidebar-btn').attr('aria-expanded', 'true');
            }
        }

        // Iframe Load Event
        var iframe = document.getElementById('content_frame');
        if (iframe) {
            iframe.onload = function () {
                $('#loading-spinner').addClass('d-none');
                $('#content_frame').css('opacity', '1');

                try {
                    var iframeLoc = iframe.contentWindow.location.href;
                    if (iframeLoc && iframeLoc !== 'about:blank' && isValidModuleUrl(iframeLoc)) {
                        localStorage.setItem('last_active_module_url', iframeLoc);

                        let uParams = new URL(iframeLoc, window.location.origin).searchParams;
                        let mdid = uParams.get('mdid');
                        if (mdid) {
                            $('.dashboard-link').each(function () {
                                if (String($(this).data('moduleid')) === String(mdid)) {
                                    $('.dashboard-link').removeClass('active');
                                    $(this).addClass('active');
                                    let $parentCollapse = $(this).closest('.collapse');
                                    $parentCollapse.addClass('show');
                                    $parentCollapse.closest('.card').find('.sidebar-btn').attr('aria-expanded', 'true');
                                }
                            });
                        }
                    }
                } catch (e) {}
            };
        }

        // Auto-expand group if it contains an active link (for deep linking)
        $('.dashboard-link.active').closest('.collapse').collapse('show');

        // Sidebar Collapse Toggle
        $('#sidebarToggle').on('click', function () {
            $('#sidebarMenu').toggleClass('collapsed');

            // Handle Bootstrap Collapse behavior when shrinking
            if ($('#sidebarMenu').hasClass('collapsed')) {
                $('.collapse.show').collapse('hide');
                localStorage.setItem('sidebar_collapsed', 'true');
            } else {
                localStorage.setItem('sidebar_collapsed', 'false');
            }
        });

        // Restore sidebar state only on desktop (>= 992px)
        if (window.innerWidth >= 992) {
            if (localStorage.getItem('sidebar_collapsed') === 'true') {
                $('#sidebarMenu').addClass('collapsed');
            }
        } else {
            // Ensure sidebar is never collapsed or hidden on mobile
            $('#sidebarMenu').removeClass('collapsed');
        }

        // Handle backdrop and mobile drawer open/close
        $('#sidebarMenu').on('show.bs.collapse', function () {
            if ($(window).width() < 992) {
                $('#sidebarMenu').removeClass('collapsed');
                if (!$('#sidebarBackdrop').length) {
                    $('body').append('<div id="sidebarBackdrop" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(15,23,42,0.45);backdrop-filter:blur(2px);z-index:1999;display:none;"></div>');
                    $('#sidebarBackdrop').on('click', function () {
                        $('#sidebarMenu').collapse('hide');
                    });
                }
                $('#sidebarBackdrop').fadeIn(200);
            }
        });

        $('#sidebarMenu').on('hide.bs.collapse', function () {
            if ($(window).width() < 992) {
                $('#sidebarBackdrop').fadeOut(200);
            }
        });
    });
</script>