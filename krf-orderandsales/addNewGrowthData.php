<?php
$psodate = "";
$clientname = "";
$mobileno = "";
$emailid = "";
$deliverydate = "";
$psoamount = "";
if (isset($_GET['dz'])) {
    require('../connection.php');
    require('../functions/function_master.php');
    include('functions/orders_functions.php');
    $key = $_GET['k'];
    $key_decoded = base64url_decode($key);
    $key_parts = explode("|", $key_decoded);
    $checkkey = base64url_decode($key_parts[count($key_parts) - 1]);
    $actkey = "";
    for ($i = 0; $i < count($key_parts) - 1; $i++) {
        $actkey = $actkey ? $actkey . "|" . $key_parts[$i] : $key_parts[$i];
    }
    if ($actkey != $checkkey) {
        echo "<div class=\"col-md-12 text-center black\"><h3>OOPS!!Something went wrong. Please Try after sometime.</h3></div>";
        die;
    }
    $loginname = "";
    $loginid = $key_parts[0];
    $loginroleid = $key_parts[1];
    $companyid = $key_parts[2];
    $subcompanyid = $key_parts[3];
    $projectid = $key_parts[4];
    $logintype = $key_parts[5];
    $newkey = "x|x|" . $key_parts[2] . "|" . $key_parts[3] . "|" . $key_parts[4] . "|x";
    $encodednewkey = base64url_encode($newkey);
    $keycompany = base64url_encode($newkey . "|" . $encodednewkey);
    $cmpinfo = getcompanydetails($mconn, "companyid", $companyid);
    $companyname = "";
    if (count($cmpinfo) > 0) {
        foreach ($cmpinfo as $cmphkey) {
            $companyname = $cmphkey['company_name'];
            $countryid = $cmphkey['countryid'];
        }
    }

    $subcmpinfo = getsubcompanydetails($mconn, $companyid, $subcompanyid, "subcompanyid", $subcompanyid);
    $subcompanyname = "";
    if (count($subcmpinfo) > 0) {
        foreach ($subcmpinfo as $subcmphkey) {
            $subcompanyname = $subcmphkey['subcompanyname'];
        }
    }

    //projectinfo
    $prj = getprojectdetails($conn, $companyid, $subcompanyid, "projectid", $projectid);
    // print_r($prj);
    $projectname = "";
    if (count($prj) > 0) {
        foreach ($prj as $pjkey) {
            $projectname = $pjkey['projectname'];
        }
    }
    $protocol = $_SERVER['PROTOCOL'] = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 'https' : 'http';
    $url = basename($_SERVER["PHP_SELF"]);
    $rooturl = $protocol . '://' . $_SERVER['HTTP_HOST'];
    $baseurl = $rooturl . str_replace($url, "", $_SERVER["PHP_SELF"]);
    $countryid = "";
    if ($_SERVER['HTTP_HOST'] == "localhost") {
        $prefix = $rooturl . "/krf_amanaccounting/";
    } else {
        $prefix = $rooturl . "/";
    }
    $rooturl = $prefix;
} else {
    require('../header.php');
    include('functions/orders_functions.php');
    $rooturl;
}

$productid = "";
if (isset($_GET['productid'])) {
    $productid = $_GET['productid'];
}

$productgrowthid = "";
if (isset($_GET['productgrowthid'])) {
    $productgrowthid = $_GET['productgrowthid'];
}

$mdid = "";
if (isset($_GET['mdid'])) {
    $mdid = $_GET['mdid'];
}
$dddr = loginmodulesdetails_frommaster($authconn, $companyid, $subcompanyid, $projectid, "moduleid", $mdid);
$groupid = "";
if (count($dddr) > 0) {
    $groupid = $dddr[0]['groupid'];
}
?>
<!-- Premium UI Styling -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
    :root {
        --primary-color: #0d6efd;
        --secondary-color: #6c757d;
        --success-color: #198754;
        --bg-color: #f8f9fa;
        --card-bg: #ffffff;
        --text-color: #212529;
        --border-radius: 12px;
        --shadow-sm: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        --shadow-lg: 0 1rem 3rem rgba(0, 0, 0, 0.175);
    }

    body {
        font-family: 'Inter', sans-serif;
        background-color: var(--bg-color);
        color: var(--text-color);
    }

    .main-container {
        max-width: 1200px;
        margin: 0 auto;
        padding-bottom: 50px;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        padding: 1rem 0;
    }

    .card-custom {
        background: var(--card-bg);
        border: none;
        border-radius: var(--border-radius);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        margin-bottom: 1.5rem;
        overflow: hidden;
    }

    .card-custom:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
    }

    .card-header-custom {
        background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
        color: white;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .card-header-custom h5 {
        margin: 0;
        font-weight: 600;
        font-size: 1.1rem;
        letter-spacing: 0.5px;
    }

    .card-body-custom {
        padding: 1.5rem;
    }

    .form-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .form-control {
        border-radius: 8px;
        padding: 0.6rem 1rem;
        border: 1px solid #ced4da;
        font-size: 0.95rem;
        transition: all 0.3s;
    }

    .form-control:focus {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }

    .form-control:disabled {
        background-color: #e9ecef;
        opacity: 1;
    }

    .btn-action {
        border-radius: 8px;
        padding: 0.6rem 1.5rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        transition: all 0.3s;
    }

    .btn-save {
        background: linear-gradient(135deg, #198754 0%, #157347 100%);
        color: white;
        border: none;
        width: 100%;
        max-width: 300px;
        font-size: 1.1rem;
    }

    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(25, 135, 84, 0.4);
    }

    .info-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.75rem;
        border-radius: 50px;
        font-size: 0.8rem;
        font-weight: 600;
        background-color: rgba(13, 110, 253, 0.1);
        color: #0d6efd;
        margin-left: auto;
    }

    /* Table Styles for Session */
    .table-custom {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 8px;
    }

    .table-custom th {
        font-weight: 600;
        color: #6c757d;
        text-transform: uppercase;
        font-size: 0.85rem;
        padding: 10px;
        border-bottom: 2px solid #e9ecef;
    }

    .table-custom td {
        background: white;
        padding: 10px;
        border-top: 1px solid #f8f9fa;
        border-bottom: 1px solid #f8f9fa;
    }

    .table-custom tr td:first-child {
        border-left: 1px solid #f8f9fa;
        border-top-left-radius: 8px;
        border-bottom-left-radius: 8px;
        font-weight: 500;
    }

    .table-custom tr td:last-child {
        border-right: 1px solid #f8f9fa;
        border-top-right-radius: 8px;
        border-bottom-right-radius: 8px;
    }

    .growth-icon {
        width: 30px;
        height: 30px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background-color: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        margin-right: 10px;
    }
</style>

<div class="main-container container-fluid p-4">
    <!-- Navigation -->
    <div class="page-header <?php echo isset($_GET['dz']) ? 'd-none' : '' ?>">
        <div>
            <a href="<?php echo $rooturl; ?>/krf-orderandsales/productmaster_review.php?k=<?php echo $key; ?>&grpid=<?php echo $groupid; ?>&mdid=<?php echo $mdid; ?>"
                class="btn btn-outline-secondary btn-action">
                <i class="fas fa-arrow-left me-2"></i> Back to Product Master
            </a>
        </div>
        <div>
            <button class="btn btn-outline-primary btn-action" onclick="location.reload();">
                <i class="fas fa-sync-alt me-2"></i> Refresh
            </button>
        </div>
    </div>

    <form id="growthStageForm" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" id="stage_id" value="<?php echo $productgrowthid; ?>">
        <input type="hidden" name="productid" id="productid" value="<?php echo $productid; ?>">

        <!-- Product Details Card -->
        <div class="card card-custom">
            <div class="card-header-custom">
                <div class="growth-icon"><i class="fas fa-cubes"></i></div>
                <h5>Product Information</h5>
                <span class="info-badge" id="grafting-badge">Checking...</span>
            </div>
            <div class="card-body-custom">
                <div class="row g-4">
                    <div class="col-md-4">
                        <label class="form-label">Product Name</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i
                                    class="fas fa-tag text-muted"></i></span>
                            <input type="text" class="form-control border-start-0 ps-0" id="display_productname"
                                disabled>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Grafting Status</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i
                                    class="fas fa-code-branch text-muted"></i></span>
                            <input type="text" class="form-control border-start-0 ps-0" id="display_isgrafted" disabled>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Seed Configuration</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i
                                    class="fas fa-seedling text-muted"></i></span>
                            <input type="text" class="form-control border-start-0 ps-0" id="display_seedname" disabled>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Left Column: Growth Stages -->
            <div class="col-lg-8">
                <!-- Germination & Cycle -->
                <div class="card card-custom">
                    <div class="card-header-custom"
                        style="background: linear-gradient(135deg, #e2f2fe 0%, #dbeafe 100%); color: #052c65 !important;">
                        <div class="growth-icon"><i class="fas fa-seedling"></i></div>
                        <h5>Growth Cycle & Germination</h5>
                    </div>
                    <div class="card-body-custom">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label text-success">Full Cycle Days</label>
                                <input type="number" class="form-control" name="fullcycledays" id="fullcycledays"
                                    required />
                            </div>
                        </div>

                        <div class="row g-3">
                            <!-- Dynamic Germination Inputs -->
                            <div class="col-md-6" id="divScionGermination">
                                <label class="form-label" id="lblScionGermination">Scion Germination Days</label>
                                <input type="number" class="form-control germination-input"
                                    name="ScianSeedGerminationdays" id="inputScionGermination"
                                    placeholder="Days (e.g. 2)">
                            </div>

                            <div class="col-md-6 d-none" id="divRootGermination">
                                <label class="form-label">Root Germination Days</label>
                                <input type="number" class="form-control germination-input" name="SeedGerminationdays"
                                    id="inputRootGermination" placeholder="Days (e.g. 3)">
                            </div>

                            <div class="col-md-12 d-none">
                                <label class="form-label">Germination Remarks</label>
                                <textarea class="form-control" name="SeedGerminationRemark" id="SeedGerminationRemark"
                                    rows="2" placeholder="Any specific notes..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Grafting Operation (Conditional) -->
                <div class="card card-custom d-none" id="graftingdiv">
                    <div class="card-header-custom"
                        style="background: linear-gradient(135deg, #d63384 0%, #e66465 100%);">
                        <div class="growth-icon"><i class="fas fa-cut"></i></div>
                        <h5>Grafting Operation</h5>
                    </div>
                    <div class="card-body-custom">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Operation Days</label>
                                <input type="text" class="form-control" name="GraftingOperationdays"
                                    id="GraftingOperationdays">
                            </div>
                            <div class="col-md-12 d-none">
                                <label class="form-label">Remark</label>
                                <textarea class="form-control" name="GraftingOperationRemark"
                                    id="GraftingOperationRemark" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Healing & Hardening -->
                <div class="card card-custom d-none" id="HealingHardeningdiv">
                    <div class="card-header-custom"
                        style="background: linear-gradient(135deg, #fd7e14 0%, #fac248 100%);">
                        <div class="growth-icon"><i class="fas fa-clinic-medical"></i></div>
                        <h5>Healing & Hardening</h5>
                    </div>
                    <div class="card-body-custom">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3">
                                    <h6 class="text-secondary mb-3"><i class="fas fa-hospital-alt me-2"></i>Healing
                                        Chamber</h6>
                                    <div class="mb-2">
                                        <label class="form-label">Days</label>
                                        <input type="text" class="form-control" name="HealingChamberdays"
                                            id="HealingChamberdays">
                                    </div>
                                    <div class="d-none">
                                        <label class="form-label">Remark</label>
                                        <input type="text" class="form-control" name="HealingChamberdaysRemark"
                                            id="HealingChamberdaysRemark">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3">
                                    <h6 class="text-secondary mb-3"><i class="fas fa-cloud-sun me-2"></i>Hardening</h6>
                                    <div class="mb-2">
                                        <label class="form-label">Days</label>
                                        <input type="text" class="form-control" name="Hardeningdays" id="Hardeningdays">
                                    </div>
                                    <div class="d-none">
                                        <label class="form-label">Remark</label>
                                        <input type="text" class="form-control" name="HardeningdaysRemark"
                                            id="HardeningdaysRemark">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Clip Removal -->
                <div class="card card-custom  d-none" id="ClipRemovaldaysdiv">
                    <div class="card-header-custom"
                        style="background: linear-gradient(135deg, #6610f2 0%, #8540f5 100%);">
                        <div class="growth-icon"><i class="fas fa-paperclip"></i></div>
                        <h5>Clip Removal</h5>
                    </div>
                    <div class="card-body-custom">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Removal Days</label>
                                <input type="text" class="form-control" name="ClipRemovaldays" id="ClipRemovaldays">
                            </div>
                            <div class="col-md-12 d-none">
                                <label class="form-label">Remark</label>
                                <textarea class="form-control" name="ClipRemovalRemark" id="ClipRemovalRemark"
                                    rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Secondary Hardening -->
                <div class="card card-custom">
                    <div class="card-header-custom"
                        style="background: linear-gradient(135deg, #0dcaf0 0%, #0d6efd 100%);">
                        <div class="growth-icon"><i class="fas fa-shield-alt"></i></div>
                        <h5><span class="d-none" id="secondaryhardeninglbl">Secondary</span> Hardening</h5>
                    </div>
                    <div class="card-body-custom">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Hardening Days</label>
                                <input type="text" class="form-control" name="SecondaryHardeningdays"
                                    id="SecondaryHardeningdays">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Legacy Sections Hidden -->
                <div class="d-none">
                    <input type="text" name="EarlySeedlingdays" id="EarlySeedlingdays" />
                    <textarea name="EarlySeedlingRemark" id="EarlySeedlingRemark"></textarea>
                    <input type="text" name="TrueLeafSeedlingdays" id="TrueLeafSeedlingdays">
                    <textarea name="TrueLeafSeedlingRemark" id="TrueLeafSeedlingRemark"></textarea>
                </div>

            </div>

            <!-- Right Column: Sessional Corrections & Actions -->
            <div class="col-lg-4">
                <div class="card card-custom sticky-top" style="top: 20px;">
                    <div class="card-header-custom bg-dark text-white" style="background: #343a40;">
                        <div class="growth-icon"><i class="fas fa-calendar-alt"></i></div>
                        <h5>Sessional Correction</h5>
                    </div>
                    <div class="card-body-custom p-0">
                        <table class="table table-custom mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Session</th>
                                    <th class="pe-4">Extra Days</th>
                                </tr>
                            </thead>
                            <tbody id="SessionCorrectiontbl">
                                <tr>
                                    <td class="ps-4 align-middle"><i class="fas fa-snowflake text-info me-2"></i> Winter
                                    </td>
                                    <td class="pe-4"><input type="number"
                                            class="form-control session-correction text-center" data-session="Winter"
                                            id="sessionwinder" placeholder="0"></td>
                                </tr>
                                <tr>
                                    <td class="ps-4 align-middle"><i class="fas fa-sun text-warning me-2"></i> Summer
                                    </td>
                                    <td class="pe-4"><input type="number"
                                            class="form-control session-correction text-center" data-session="Summer"
                                            id="sessionsummer" placeholder="0"></td>
                                </tr>
                                <tr>
                                    <td class="ps-4 align-middle"><i
                                            class="fas fa-cloud-showers-heavy text-primary me-2"></i> Rainy</td>
                                    <td class="pe-4"><input type="number"
                                            class="form-control session-correction text-center" data-session="Rainy"
                                            id="sessionrainy" placeholder="0"></td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="p-4 bg-light border-top text-center">
                            <input type="hidden" name="companyid" value="<?php echo $companyid; ?>">
                            <input type="hidden" name="subcompanyid" value="<?php echo $subcompanyid; ?>">
                            <input type="hidden" name="projectid" value="<?php echo $projectid; ?>">
                            <input type="hidden" name="loginid" value="<?php echo $loginid; ?>">

                            <button type="submit" class="btn btn-save btn-lg w-100 shadow-sm" style="max-width: 100%;">
                                <i class="fas fa-save me-2"></i> Save Details
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
<?php
if (isset($_GET['dz'])) {
} else {
    include("../footer.php");
}
?>
<script type="text/javascript" src="<?php echo $prefix; ?>krf-orderandsales/js/pjs_function.js"></script>
<script>
    var dz = "<?php echo $_GET['dz']; ?>";
    var productid = $("#productid").val();

    // Global variable to track if functionality is grafted
    var isProductGrafted = false;

    GetFullProductListFrommaster_callback('ProductID', productid, function (response) {
        if (response.length > 0) {
            console.log(response);
            let row = response[0]; // Only one product expected by ID
            $("#display_productname").val(row.ProductName);
            $("#display_isgrafted").val(row.IsGrafted);
            $("#display_seedname").val(row.ScionSeedName + ' - ' + row.RootSeedName);
            let fullcycledays=0;
            let roodseedgrowdays=0;
            let scianseedgrowdays=0;
            // Logic for Grafting View
            if (row.IsGrafted === "Grafted") {
                isProductGrafted = true;
                $("#graftingdiv").removeClass('d-none');
                $("#graftingdiv").find("input").attr("required", true);
                $("#grafting-badge").text("Grafted").addClass("bg-warning text-dark");

                // Show Root Input (SeedGerminationdays) & Update labels
                $("#divRootGermination").removeClass("d-none");
                $("#HealingHardeningdiv").removeClass("d-none");
                $("#ClipRemovaldaysdiv").removeClass("d-none");
                $("#secondaryhardeninglbl").removeClass("d-none");         
                
                

                $("#lblScionGermination").text("Scion Germination Days");
                // ScianSeedGerminationdays
                // RootSeedgrowingdays
                // ScionSeedgrowingdays
                fullcycledays = row.RootSeedgrowingdays + 20; 
                $("#fullcycledays").val(fullcycledays);
                $("#inputScionGermination").val(row.ScionSeedgrowingdays || "");
                $("#inputRootGermination").val(row.RootSeedgrowingdays || "");
            } else {
                isProductGrafted = false;
                $("#graftingdiv").addClass('d-none');
                $("#graftingdiv").find("input").removeAttr("required");
                $("#grafting-badge").text("Non-Grafted").addClass("bg-success text-white");
                fullcycledays = row.ScionSeedgrowingdays; 
                $("#fullcycledays").val(fullcycledays);
                $("#inputScionGermination").val(row.ScionSeedgrowingdays || "");
                $("#inputRootGermination").val(0);

                // Hide Root Input (SeedGerminationdays) & Standard label
                // For non-grafted, we use the visible input for user interaction, 
                // but we'll map it to SeedGerminationdays on submit.
                $("#divRootGermination").addClass("d-none");
                $("#lblScionGermination").text("Seed Germination Days");
            }

            if (row.productGrowthid) {
                const productGrowthData = row.productGrowthData;
                if (productGrowthData.length > 0) {
                    let pgdata = productGrowthData[0];
                    let correctionDaysdata = pgdata['correctiondays'];

                    $("#sessionwinder").val(correctionDaysdata['Winter']);
                    $("#sessionsummer").val(correctionDaysdata['Summer']);
                    $("#sessionrainy").val(correctionDaysdata['Rainy']);
                    $("#stage_id").val(pgdata.id);
                    $("#fullcycledays").val(pgdata.fullcycledays);

                    // --- Handle Germination Days (Direct Mapping) ---
                    if (isProductGrafted) {
                        $("#inputScionGermination").val(pgdata.ScianSeedGerminationdays || "");
                        $("#inputRootGermination").val(pgdata.SeedGerminationdays || "");
                    } else {
                        // Non-grafted: Scian field is hidden, Root field acts as main "Seed Germination"
                        // Wait, in UI logic above:
                        // Non-Grafted -> #divRootGermination is hidden. #lblScionGermination becomes "Seed Germination Days".
                        // So we should put the single value into #inputScionGermination?
                        // Previously: "Seed Germination" was single value.
                        // Now: "ScianSeedGerminationdays" is added.
                        // If Non-Grafted, we likely just use "SeedGerminationdays".
                        // BUT my UI logic (lines 593-596) hides #divRootGermination (which I mapped to SeedGerminationdays)
                        // AND shows #divScionGermination (which I mapped to ScianSeedGerminationdays).

                        // RE-THINK UI LOGIC:
                        // Case 1: Grafted.
                        //   - Scion (Top) -> ScianSeedGerminationdays. (Input: inputScionGermination)
                        //   - Root (Base) -> SeedGerminationdays.      (Input: inputRootGermination)

                        // Case 2: Non-Grafted.
                        //   - Only one "Seed Germination".
                        //   - Which column stores it? Usually 'SeedGerminationdays' (original column).
                        //   - So we should show 'inputRootGermination' and hide 'inputScionGermination'?
                        //   - OR swap the names dynamically?

                        // Let's adjust the UI logic in the callback instead of here.
                        // For now, let's just map data:
                        $("#inputScionGermination").val(pgdata.ScianSeedGerminationdays);
                        $("#inputRootGermination").val(pgdata.SeedGerminationdays);
                    }

                    // $("#SeedGerminationRemark").val(pgdata.SeedGerminationRemark);
                    $("#EarlySeedlingdays").val(pgdata.EarlySeedlingdays);
                    // $("#EarlySeedlingRemark").val(pgdata.EarlySeedlingRemark);
                    $("#TrueLeafSeedlingdays").val(pgdata.TrueLeafSeedlingdays);
                    // $("#TrueLeafSeedlingRemark").val(pgdata.TrueLeafSeedlingRemark);
                    $("#GraftingOperationdays").val(pgdata.GraftingOperationdays);
                    // $("#GraftingOperationRemark").val(pgdata.GraftingOperationRemark);
                    $("#HealingChamberdays").val(pgdata.HealingChamberdays);
                    // $("#HealingChamberdaysRemark").val(pgdata.HealingChamberdaysRemark);
                    $("#Hardeningdays").val(pgdata.Hardeningdays);
                    // $("#HardeningdaysRemark").val(pgdata.HardeningdaysRemark);
                    $("#ClipRemovaldays").val(pgdata.ClipRemovaldays);
                    // $("#ClipRemovalRemark").val(pgdata.ClipRemovalRemark);
                    $("#SecondaryHardeningdays").val(pgdata.SecondaryHardeningdays);
                }
            }
        }
    });

    $("#growthStageForm").submit(function (e) {
        e.preventDefault();

        // --- Pack Germination Data - REMOVED (Direct FormData) ---
        // Just verify inputs for Non-Grafted case
        if (!isProductGrafted) {
            // If non-grafted, we want to save the value from the visible input into 'SeedGerminationdays'.
            // Visible input is 'inputScionGermination' (name='ScianSeedGerminationdays').
            // But we want it in 'SeedGerminationdays'.
            // So, copy value from Scion input to Root input, and maybe clear Scion input?
            // Actually, if Non-Grafted, 'ScianSeedGerminationdays' should probably be empty/null.
            // And 'SeedGerminationdays' should hold the value.
            // So we should swap values before submit if needed.

            // Current UI State for Non-Grafted:
            // #divRootGermination (SeedGerminationdays) is HIDDEN.
            // #divScionGermination (ScianSeedGerminationdays) is VISIBLE and labelled "Seed Germination Days".

            // So user types into 'ScianSeedGerminationdays'.
            // But we want valid data in 'SeedGerminationdays' for non-grafted?
            // OR does 'ScianSeedGerminationdays' hold the value for non-grafted too?
            // Usually separation implies: Scion=Top, Seed=Root/Base.
            // Non-grafted = Base. So it should go to SeedGerminationdays.

            let val = $("#inputScionGermination").val();
            $("#inputRootGermination").val(val); // Put in SeedGerminationdays
            $("#inputScionGermination").val(""); // Clear Scian
        }

        let formData = new FormData(this);
        // ✅ Append session correction JSON
        let correctionJSON = getSessionCorrectionDaysJSON();
        formData.append("SessionalCorrectionDays", JSON.stringify(correctionJSON));

        $.ajax({
            url: "modal/product_modal.php?action=saveupdategrowthStageForm",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            dataType: "json",
            success: function (response) {
                if (response.status === "success") {
                    // Custom Alert or standard alert
                    if (typeof alertmessage === "function") {
                        alertmessage("success", response.message);
                    } else {
                        alert("Success: " + response.message);
                    }
                    location.reload();
                } else {
                    if (typeof alertmessage === "function") {
                        alertmessage("error", response.message);
                    } else {
                        alert("Error: " + response.message);
                    }
                }
            }
        });
    });

    function getSessionCorrectionDaysJSON() {
        let sessionData = {};
        $("#SessionCorrectiontbl .session-correction").each(function () {
            let session = $(this).data("session");
            let value = $(this).val();
            if (value !== "" && !isNaN(value)) {
                sessionData[session] = parseInt(value);
            }
        });
        return sessionData;
    }

</script>