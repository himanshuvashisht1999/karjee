<?php
if (isset($_GET['ajax_action'])) {
    require('../connection.php');
    header('Content-Type: application/json');

    $action = $_GET['ajax_action'];
    $projectid = $_POST['projectid'] ?? $_GET['projectid'] ?? '';

    if ($action === 'getBoxDetails') {
        $qrcode = trim($_POST['qrcode'] ?? '');
        if (empty($qrcode)) {
            echo json_encode(['status' => 'error', 'message' => 'Box QR Code is required']);
            exit;
        }

        // Fetch box details
        $stmt = $krfconn->prepare("
            SELECT boxid, qrcode, productname, sowinglotno_internal, boxpackingdate, traycountinbox 
            FROM krf_polyhouse_germinated_boxes 
            WHERE qrcode = ? AND projectid = ? 
            LIMIT 1
        ");
        $stmt->bind_param("si", $qrcode, $projectid);
        $stmt->execute();
        $box = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$box) {
            echo json_encode(['status' => 'error', 'message' => 'Germination Box QR Code not found']);
            exit;
        }

        // Fetch trays linked to this box
        $trays = [];
        $stmt = $krfconn->prepare("
            SELECT sowingplantid, barcode, workingstatus, status 
            FROM krf_polyhouse_sowingplant_details 
            WHERE germination_qrcode = ? AND projectid = ? 
            ORDER BY sowingplantid ASC
        ");
        $stmt->bind_param("si", $qrcode, $projectid);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $trays[] = $row;
        }
        $stmt->close();

        echo json_encode([
            'status' => 'success',
            'box' => $box,
            'trays' => $trays
        ]);
        exit;
    }

    if ($action === 'updateBoxTrays') {
        $qrcode = trim($_POST['qrcode'] ?? '');
        $traycount = intval($_POST['traycountinbox'] ?? 0);
        $trays = $_POST['trays'] ?? [];

        if (empty($qrcode) || $traycount <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid input data']);
            exit;
        }

        $krfconn->begin_transaction();
        try {
            // Update box count
            $stmt = $krfconn->prepare("
                UPDATE krf_polyhouse_germinated_boxes 
                SET traycountinbox = ? 
                WHERE qrcode = ? AND projectid = ?
            ");
            $stmt->bind_param("isi", $traycount, $qrcode, $projectid);
            $stmt->execute();
            $stmt->close();

            // Update individual tray barcodes
            if (is_array($trays) && !empty($trays)) {
                $stmt = $krfconn->prepare("
                    UPDATE krf_polyhouse_sowingplant_details 
                    SET barcode = ? 
                    WHERE sowingplantid = ? AND projectid = ?
                ");
                foreach ($trays as $t) {
                    $sowingplantid = intval($t['sowingplantid']);
                    $barcode = trim($t['barcode']);
                    $stmt->bind_param("sii", $barcode, $sowingplantid, $projectid);
                    $stmt->execute();
                }
                $stmt->close();
            }

            $krfconn->commit();
            echo json_encode(['status' => 'success', 'message' => 'Successfully updated germination box and individual tray details.']);
        } catch (Exception $e) {
            $krfconn->rollback();
            echo json_encode(['status' => 'error', 'message' => 'Update failed: ' . $e->getMessage()]);
        }
        exit;
    }
}

include('../header.php');
include('functions/product_functions.php');
include('functions/vendor_functions.php');
include('functions/seed_functions.php');

date_default_timezone_set("Asia/Calcutta");
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
<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
    #filterBox input {
        width: 200px;
    }
</style>

<style>
@media print {

    body * {
        display: none !important;
    }

    #printSection {
        display: block !important;
        position: fixed;
        left: 0;
        top: 0;
        width: 100%;
    }

    #printSection * {
        display: block !important;
    }

    /* Hide modal completely */
    .modal, .modal-backdrop {
        display: none !important;
    }
}
</style>
<style>

    .swal2-radio {
        display: flex !important;
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 12px !important;
        margin-top: 10px !important;
    }

    .lot-bg-1 {
        background: #f8f9fa !important;
        /* Light Gray */
    }

    .lot-bg-2 {
        background: #e8f4ff !important;
        /* Light Blue */
    }

    .lot-bg-3 {
        background: #e8ffe8 !important;
        /* Light Green */
    }

    .seedLotCard {
        border-left: 5px solid #0d6efd;
    }

    /* Header gradient */
    .smart-modal-header {
        background: linear-gradient(135deg, #4e73df, #1cc88a);
        color: #fff;
    }

    /* Body gradient */
    .smart-modal-body {
        background: linear-gradient(180deg, #f8f9fc, #eef1f7);
    }

    /* Labels */
    .smart-modal-body label {
        font-weight: 600;
        font-size: 14px;
    }

    /* Button */
    .smart-confirm-btn {
        background: linear-gradient(135deg, #1cc88a, #17a673);
        border: none;
        padding: 10px 28px;
        font-weight: 600;
        color: #fff;
    }

    .smart-confirm-btn:hover {
        opacity: 0.9;
    }

    /* Mobile compact */
    @media (max-width: 768px) {
        .smart-confirm-btn {
            width: 100%;
        }
    }
</style>

<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<!-- <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script> -->

<!-- ✅ QR Code Scanner Library -->
<script src="https://unpkg.com/html5-qrcode@2.3.7/html5-qrcode.min.js"></script>
<style>
    #AllocationReaderId {
        width: 100%;
        max-width: 300px;
        margin: 20px auto;
        border: 2px solid #0d6efd;
        border-radius: 10px;
        padding: 10px;
    }

    #AllocationBoxDetailsInfoToPH {
        display: none;
    }
</style>
<div class="container-fluid my-4">
    <div id="printparameterdiv"></div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center py-3">
            <h3 class="mb-0">Production Sowing Schedule</h3>
            <div class="d-flex gap-2">
                
                <button class="btn btn-sm btn-info" onclick="window.location.reload();"> <i class="fa fa-sync"></i> Refresh</button>
                <button class="btn btn-sm btn-primary" id="updateTraysInBoxBtn" style="background: linear-gradient(135deg, #7c3aed, #4f46e5); border: none;"><i class="fa fa-edit"></i> Update Trays In Box</button>

            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <label>Select Month</label>
                    <select id="sowingmonthFilter" class="form-control">
                        <option value="all">All Months</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label>Select Week</label>
                    <select id="sowingweekSelector" class="form-control"></select>
                </div>
                <div class="col-md-6 d-flex align-items-end justify-content-end">
                    <button class="btn btn-warning m-1" id="sowingprevWeekBtn">← Previous Week</button>
                    <button class="btn btn-success m-1" id="sowingnextWeekBtn">Next Week →</button>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-12">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="sowingplandetaisls">
                            <thead>
                                <tr>
                                    <th>S.No.</th>
                                    <th>Sowing Location</th>
                                    <th>Order No</th>
                                    <th>Sowing Date</th>
                                    <th>Variety of Seed</th>
                                    <th>Product Name</th>
                                    <th>Order Qty</th>
                                    <th>Total Sowing Qty</th>
                                    <th>Pending Qty<br /> For Sowing</th>
                                    <th>Print Sowing<br /> QR Code</th>
                                    <th>Pack For Germination</th>
                                    <th>Direct Polyhouse</th>
                                </tr>
                            </thead>
                            <tbody id="scheduleTable"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="sowingQRCodeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="max-width: 95%;">
        <div class="modal-content border-0 shadow-lg">
            <!-- Header -->
            <div class="modal-header bg-primary text-white">
                <div>
                    <h5 class="modal-title d-flex align-items-center mb-1">
                        <i class="fa fa-qrcode me-2"></i> QR Codes for Sowing Trays
                    </h5>
                    <div class="small opacity-75">
                        <span class="me-3"><i class="fa fa-leaf me-1"></i> <span
                                class="productNamemd fw-bold"></span></span>
                        <span class="me-3"><i class="fa fa-hashtag me-1"></i> Order: <span
                                class="productOrderNo fw-bold"></span></span>
                        <span><i class="fa fa-layer-group me-1"></i> Qty: <span
                                class="productOrderQty fw-bold"></span></span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 ms-auto">
                    <button type="button" class="btn btn-sm btn-light shadow-sm px-3 py-2 text-primary d-flex align-items-center gap-1" id="refreshSowingModalBtn" style="border-radius: 8px; font-weight: 600; border: none; font-size: 0.85rem;">
                        <i class="fa fa-sync"></i> Refresh
                    </button>
                    <button type="button" class="btn-close btn-close-white CloseForTodaybtn ms-2" style="margin: 0;"></button>
                </div>
            </div>

            <!-- Body -->
            <div class="modal-body bg-light">
                <div id="otherorderdetails"></div>

                <!-- Section 1: Sowing Configuration -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-bottom-0">
                        <h6 class="card-title mb-0 text-primary fw-bold"><i class="fa fa-cogs me-2"></i>Sowing
                            Configuration</h6>
                    </div>
                    <div class="card-body pt-0">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="sowingtraytype"
                                    class="form-label fw-semibold text-secondary small text-uppercase">Tray Type</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0"><i
                                            class="fa fa-box text-muted"></i></span>
                                    <select class="form-select border-start-0 ps-0" id="sowingtraytype"></select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label for="GerminationFailure"
                                    class="form-label fw-semibold text-secondary small text-uppercase">Buffer
                                    (%)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0"><i
                                            class="fa fa-percentage text-muted"></i></span>
                                    <input type="number" class="form-control border-start-0 ps-0"
                                        id="GerminationFailure" value="30" placeholder="Enter %">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label for="TraysRequired"
                                    class="form-label fw-semibold text-secondary small text-uppercase">Req.
                                    Trays</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0"><i
                                            class="fa fa-calculator text-muted"></i></span>
                                    <input type="number" class="form-control border-start-0 ps-0 bg-light"
                                        id="TraysRequired" readonly placeholder="Auto-calc">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label for="SowingQuantity"
                                    class="form-label fw-semibold text-secondary small text-uppercase">Sowing
                                    Qty</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0"><i
                                            class="fa fa-seedling text-muted"></i></span>
                                    <input type="number" class="form-control border-start-0 ps-0 bg-light"
                                        id="SowingQuantity" readonly placeholder="Total seeds">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Seed & Lot Details -->
                <div class="card border-0 shadow-sm">
                    <div
                        class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                        <h6 class="card-title mb-0 text-primary fw-bold"><i class="fa fa-list-alt me-2"></i>Seed Lot
                            Details</h6>
                        <div class="d-flex align-items-center gap-3">
                            <div class="badge bg-light text-dark border px-3 py-2">
                                <small class="text-muted d-block text-start" style="font-size: 0.65rem;">PENDING QTY
                                    WITH BUFFER</small>
                                <span id="pendingSowingqtylbl" class="text-primary fw-bold fs-6">0</span>
                            </div>
                            <div class="badge bg-light text-dark border px-3 py-2">
                                <small class="text-muted d-block text-start" style="font-size: 0.65rem;">PENDING TRAYS
                                    FOR QR</small>
                                <span id="resttoprintQrCode" class="text-danger fw-bold fs-6">0</span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Seed Selection -->
                        <div class="row justify-content-center mb-4">
                            <div class="col-md-6">
                                <label for="SeedNameForSowing" class="form-label fw-bold text-center w-100 d-block mb-1"
                                    id="seednamelable">Seed Name</label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-white"><i
                                            class="fa fa-search text-muted"></i></span>
                                    <select class="form-select fw-bold text-dark" id="SeedNameForSowing" readonly></select>
                                </div>
                            </div>
                        </div>

                        <!-- Dynamic Lot Rows -->
                        <div id="seedlotdetailsdiv" class="mb-4">
                            <!-- Helper Text for Empty State -->
                            <div class="text-center text-muted py-4 d-none" id="noLotsMsg">
                                <i class="fa fa-folder-open fa-2x mb-2 opacity-50"></i>
                                <p>No lot details added yet. Click "Add Lot" below.</p>
                            </div>

                            <!-- Template Row (Hidden/Initial) handled by JS, but keeping structure consistent -->
                            <div class="seedLotCard card shadow-sm mb-3 border-start border-4 border-primary">
                                <div class="card-body py-3">
                                    <div class="row align-items-end g-3">
                                        <div class="col-lg-3">
                                            <label class="form-label small text-muted fw-bold">Lot Number</label>
                                            <input type="text" class="form-control form-control-sm LotNumberofSeed"
                                                placeholder="From packet">
                                        </div>
                                        <div class="col-lg-2">
                                            <label class="form-label small text-muted fw-bold">Available Seeds</label>
                                            <input type="text" class="form-control form-control-sm SeedQtyofLotno"
                                                placeholder="Qty">
                                        </div>
                                        <div class="col-lg-2">
                                            <label class="form-label small text-muted fw-bold">Trays for QR</label>
                                            <input type="text"
                                                class="form-control form-control-sm QRCodeQtyofLotno fw-bold text-primary"
                                                placeholder="Count">
                                        </div>
                                        <div class="col-lg-5 text-end">
                                            <button class="btn btn-primary btn-sm px-3 SaveQRCodeDetailsBtn"><i
                                                    class="fa fa-save me-1"></i> Save Details</button>
                                            <button class="btn btn-outline-danger btn-sm px-3 RemoveLotNoRow"><i
                                                    class="fa fa-trash"></i></button>
                                        </div>
                                    </div>
                                    <!-- Messages container -->
                                    <div class="mt-2 text-warning small fw-bold row-messages"></div>
                                </div>
                            </div>

                        </div>

                        <!-- Actions -->
                        <div class="d-flex justify-content-center gap-3 border-top pt-4">
                            <button class="btn btn-success px-4" id="AddLotNoRow">
                                <i class="fa fa-plus-circle me-2"></i> Add Lot Number
                            </button>
                            <button class="btn btn-outline-secondary px-4 CloseForTodaybtn" id="CloseForTodaybtn">
                                <i class="fa fa-times me-2"></i> Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="germpackingQRCodeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" style="max-width:98%;">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(120deg, #e3d5fd, #fee3ff); border: none;">
                <h6 class="modal-title">
                    QR Codes for Germination Packing — <b class="productNamemd_pck"></b><br />
                    <small><b>Order No:</b> <span class="productOrderNo_pck"></span></small>
                    <small><b>Order Quantity:</b> <span class="productOrderQty_pck"></span></small>
                    <small><b>Sowing Quantity:</b> <span class="sowingOrderQty_pck"></span></small>
                </h6>
                <div class="d-flex align-items-center gap-2 ms-auto">
                    <button type="button" class="btn btn-sm btn-primary shadow-sm px-3 py-2 text-white d-flex align-items-center gap-1" id="refreshGermPackingModalBtn" style="border-radius: 8px; font-weight: 600; background: linear-gradient(135deg, #4e73df 0%, #224abe 100%); border: none; font-size: 0.85rem;">
                        <i class="fa fa-sync"></i> Refresh
                    </button>
                    <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" style="margin: 0;"></button>
                </div>
                <div id="otherorderdetails_pck"></div>
            </div>

            <div class="modal-body">
                <div class="card mt-3">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div id="alreadyprintedboxqrcode"></div>
                            </div>
                        </div>
                        <div id="boxqrcodemsg"></div>
                        <div id="newboxqrcodediv">
                            <div class="row">
                                <div class="col-md-4"></div>
                                <div class="col-md-4">
                                    <div class="card text-center p-2 shadow-sm"
                                        style="background: linear-gradient(135deg, #e3f2fd, #ffe3ff); border: none;">
                                        <h5 class="fw-semibold text-secondary">
                                            No. of Trays for Germination Box
                                        </h5>
                                        <h3 class="fw-bold text-primary">
                                            <span class="sowingTrayQty_pck" id="pendingsowingTrayQtypck">0</span>
                                        </h3>
                                    </div>
                                </div>
                                <div class="col-md-4"></div>
                            </div>
                            <div class="row mt-4">
                                <div class="col-md-6 mb-2">
                                    <label>No of Trays in a Box</label>
                                    <input type="number" class="form-control" id="traycountinbox" />
                                </div>

                                <div class="col-md-6 mb-2">
                                    <label>Total Number of Boxes</label>
                                    <input type="number" class="form-control" id="totalboxesforgerm" readonly />
                                </div>
                            </div>

                            <!-- Suggested Packing Breakdown Section -->
                            <div class="row mt-2" id="boxBreakdownContainer" style="display: none;">
                                <div class="col-md-12">
                                    <div class="alert alert-info py-2 px-3 mb-2 d-flex align-items-center justify-content-between border-0 shadow-sm" style="background: linear-gradient(135deg, #e0f2fe, #f0f9ff); color: #0369a1; border-radius: 8px;">
                                        <div>
                                            <i class="fa fa-cubes me-2"></i>
                                            <strong>Suggested Packing Breakdown:</strong>
                                            <span id="boxBreakdownText" class="ms-2"></span>
                                        </div>
                                        <div id="boxBreakdownBadges" class="d-flex gap-2"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-2">
                                <div class="col-md-4 mb-2">
                                    <label>Date of Packing</label>
                                    <input type="date" class="form-control" id="packingdate"
                                        value="<?php echo date('Y-m-d'); ?>" />
                                </div>

                                <div class="col-md-4 mb-2">
                                    <label>Box Open after Days</label>
                                    <input type="number" class="form-control" id="boxopenafterdays" />
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label>Date of Opening</label>
                                    <input type="date" class="form-control" id="dateofopening" readonly />
                                </div>
                            </div>
                             <div class="row mt-2">
                                <div class="col-md-3 mb-2"></div>
                                <div class="col-md-6 mb-2">
                                    <label>Confirm To Print QR Code For Box</label>
                                    <input type="number" class="form-control" id="counttiprintqrcode" />
                                </div>
                                <div class="col-md-3 mb-2"></div>
                             </div>
                            <div class="row mt-4">
                                <div class="col-md-12 text-center">
                                    <button class="btn btn-success me-2" id="saveGerminationDetailsBtnpck">Save Germination
                                        Packing Details</button>
                                    <button class="btn btn-primary me-2 d-none" id="GerminationPrintQrBtnpck">Print QR
                                        Code</button>
                                    <button class="btn btn-outline-secondary px-4 CloseForTodaybtn" id="CloseForTodaybtn">
                                    <i class="fa fa-times me-2"></i> Close
                                </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- ajay -->


<div class="modal fade" id="direct_germpackingQRCodeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" style="max-width:98%;">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(120deg, #e3d5fd, #fee3ff); border: none;">
                <h6 class="modal-title">
                    QR Codes for Germination Packing — <b class="productNamemd_pck"></b><br />
                    <small><b>Order No:</b> <span class="productOrderNo_pck"></span></small>
                    <small><b>Order Quantity:</b> <span class="productOrderQty_pck"></span></small>
                    <small><b>Sowing Quantity:</b> <span class="sowingOrderQty_pck"></span></small>
                </h6>
                <div class="d-flex align-items-center gap-2 ms-auto">
                    <button type="button" class="btn btn-sm btn-primary shadow-sm px-3 py-2 text-white d-flex align-items-center gap-1" id="direct_refreshGermPackingModalBtn" style="border-radius: 8px; font-weight: 600; background: linear-gradient(135deg, #4e73df 0%, #224abe 100%); border: none; font-size: 0.85rem;">
                        <i class="fa fa-sync"></i> Refresh
                    </button>
                    <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" style="margin: 0;"></button>
                </div>
                <div id="direct_otherorderdetails_pck"></div>
            </div>

            <div class="modal-body">
                <div class="card mt-3">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div id="direct_alreadyprintedboxqrcode"></div>
                            </div>
                        </div>
                        <div id="direct_boxqrcodemsg"></div>
                        <div id="direct_newboxqrcodediv">
                            <div class="row">
                                <div class="col-md-4"></div>
                                <div class="col-md-4">
                                    <div class="card text-center p-2 shadow-sm"
                                        style="background: linear-gradient(135deg, #e3f2fd, #ffe3ff); border: none;">
                                        <h5 class="fw-semibold text-secondary">
                                            No. of Trays for Direct
                                        </h5>
                                        <h3 class="fw-bold text-primary">
                                            <span class="direct_sowingTrayQty_pck" id="direct_pendingsowingTrayQtypck">0</span>
                                        </h3>
                                    </div>
                                </div>
                                <div class="col-md-4"></div>
                            </div> 
                            <div class="row mt-4">
                                <div class="col-md-6 mb-2">
                                    <label>No of Trays</label>
                                    <input type="number" class="form-control" id="direct_traycountinbox" />
                                </div>

                                <!-- <div class="col-md-6 mb-2">
                                    <label>Total Number of Boxes</label>
                                    <input type="number" class="form-control" id="direct_totalboxesforgerm" readonly />
                                </div> -->
                                <div class="mb-3">
                                    <label for="direct_sowinglocation" class="form-label">Select Location</label>
                                    <select class="form-select" id="direct_sowinglocation" required>
                                        <option value="">Choose...</option>

                                    </select>
                                </div>
                            </div>
                            <div id="direct_polyhouseCapacityDetails"></div> 
                            <div id="AllocationBoxDetailsInfoToPH" style="display:none;">
                                <!-- Content injected via JS -->
                            </div>   
                            <!-- Suggested Packing Breakdown Section -->
                       <!--      <div class="row mt-2" id="direct_boxBreakdownContainer" style="display: none;">
                                <div class="col-md-12">
                                    <div class="alert alert-info py-2 px-3 mb-2 d-flex align-items-center justify-content-between border-0 shadow-sm" style="background: linear-gradient(135deg, #e0f2fe, #f0f9ff); color: #0369a1; border-radius: 8px;">
                                        <div>
                                            <i class="fa fa-cubes me-2"></i>
                                            <strong>Suggested Packing Breakdown:</strong>
                                            <span id="direct_boxBreakdownText" class="ms-2"></span>
                                        </div>
                                        <div id="direct_boxBreakdownBadges" class="d-flex gap-2"></div>
                                    </div>
                                </div>
                            </div> -->

                       <!--      <div class="row mt-2">
                                <div class="col-md-4 mb-2">
                                    <label>Date of Packing</label>
                                    <input type="date" class="form-control" id="direct_packingdate"
                                        value="<?php echo date('Y-m-d'); ?>" />
                                </div>

                                <div class="col-md-4 mb-2">
                                    <label>Box Open after Days</label>
                                    <input type="number" class="form-control" id="direct_boxopenafterdays" />
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label>Date of Opening</label>
                                    <input type="date" class="form-control" id="direct_dateofopening" readonly />
                                </div>
                            </div> -->
                             <!-- <div class="row mt-2">
                                <div class="col-md-3 mb-2"></div>
                                <div class="col-md-6 mb-2">
                                    <label>Confirm To Print QR Code For Box</label>
                                    <input type="number" class="form-control" id="direct_counttiprintqrcode" />
                                </div>
                                <div class="col-md-3 mb-2"></div>
                             </div> -->
                            <div class="row mt-4">
                                <div class="col-md-12 text-center">
                                    <button class="btn btn-success me-2" id="direct_saveGerminationDetailsBtnpck">Save Direct
                                        Poly House</button>
                                    <button class="btn btn-primary me-2 d-none" id="direct_GerminationPrintQrBtnpck">Print QR
                                        Code</button>
                                    <button class="btn btn-outline-secondary px-4 direct_CloseForTodaybtn" id="direct_CloseForTodaybtn">
                                    <i class="fa fa-times me-2"></i> Close
                                </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- ajay -->
<div class="modal fade" id="viewQRcodelistforadjtmodal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" style="max-width: 98%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">QRCode For Print</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div id="ViewQRcodeGerminationDiv" class="print-area" style="width:100%; text-align:center;">
                        </div>
                    </div>
                    <div class="col-md-12 mt-4 text-center">
                        <!-- <button class="btn btn-primary" id="generatePdfBtnXX" onclick="generateQRCodePDF({ sourceDivId: 'ViewQRcodeGerminationDiv', fileName: 'QRCode-Stickers.pdf'})"> Generate PDF </button> -->
                        <button class="btn btn-primary" id="generatePdfBtnXX"
                            onclick="downloadQRStickerPDF('ViewQRcodeGerminationDiv')"> Generate PDF </button>

                        <button class="btn btn-success" onclick="window.print()">Print QRcode Sheet-0th--</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="askquestion_modal" tabindex="-1" role="dialog" aria-labelledby="askquestionModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <!-- Header -->
            <div class="modal-header">
                <h5 class="modal-title" id="askquestionModalLabel">QRcode Printing Settings</h5>
                <button type="button" class="close" onclick="$('#askquestion_modal').modal('hide')">&times;</button>
            </div>
            <!-- Body -->
            <div class="modal-body">

                <div class="row g-3 px-4 py-3 d-none">
                    <div class="col-md-6">
                        <label for="barcodelengthinsheet" class="form-label fw-semibold">
                            Total QRcode labels per sheet
                        </label>
                        <input type="number" id="barcodelengthinsheet" class="form-control" placeholder="e.g., 30"
                            value="24" min="1">
                    </div>

                    <div class="col-md-6">
                        <label for="barcodelengthinrow" class="form-label fw-semibold">
                            Number of QRcode labels per row
                        </label>
                        <input type="number" id="barcodelengthinrow" class="form-control" placeholder="e.g., 5"
                            value="4" min="1">
                    </div>
                </div>

                <div class="row g-3 px-4 d-none">
                    <div class="col-md-6">
                        <label for="barcodeCount" class="form-label fw-semibold">
                            Number of QRcodes to print
                        </label>
                        <input type="number" id="barcodeCount" class="form-control" placeholder="Enter total number"
                            min="1">
                    </div>

                    <div class="col-md-6">
                        <label for="startBox" class="form-label fw-semibold">
                            Starting box number for printing
                        </label>
                        <input type="number" id="startBox" class="form-control" placeholder="e.g., 1" value="1" min="1">
                    </div>
                </div>
                <div class="text-center mt-4 mb-2">
                    <button class="btn btn-primary px-4" id="printbarcodefrom">
                        <i class="bi bi-printer"></i> Confirm to Print
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="viewbarcodelistforadjt_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">QRCode For Print</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12 mt-2">
                        <div id="ViewBarcodeDiv" data-pdffilename="" style="width:100%;"></div>
                    </div>
                    <div class="col-md-12 mt-4 text-center">
                        <!-- <button class="btn btn-warning" id="generatePdfBtnXX">Download QRcode PDF</button> -->
                        <button class="btn btn-primary" onclick="downloadQRStickerPDF('ViewBarcodeDiv')">
                            Download Sticker PDF
                        </button>
                        <button type="button" class="btn btn-primary" onclick="printBarcodeDiv()">
                            Print QR
                        </button>

                        <!-- <button class="btn btn-success" id="PrintQrCodeBTN"
                            onclick="printThermalSheet('ViewBarcodeDiv')">Print QR Code
                            Sheet----</button> -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="viewqrcode_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" style="max-width: 98%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">QRCode Preview</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12 mt-2">
                        <p>Product Name: <b id="prdnamelbl"></b></p>
                        <p>Lot Number: <b id="ltnolbl"></b></p>
                    </div>
                    <div class="col-md-12 mt-2">
                        <label>Search to Filter</label>
                        <input type="search" class="form-control" border=1 placeholder="Search...." id="searchprdltin"
                            oninput="filterTable('#searchprdltin', '#lotqrcodeprvtbl');">
                    </div>
                    <div class="col-md-12 mt-2">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="lotqrcodeprvtbl">
                                <thead>
                                    <tr>
                                        <th style="width:50px; text-align:center;">S.No</th>
                                        <th>QRCode No</th>
                                        <th style="width:180px; text-align:center;">Action</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="ConfirmSowingQtymodal">
    <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width: 75%;">
        <div class="modal-content shadow rounded-3">

            <!-- Header -->
            <div class="modal-header smart-modal-header">
                <h6 class="modal-title">🌱Update Destroyed/Damaged Sowing Trays</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body smart-modal-body">
                <div class="mb-2">
                    <small><b>Product:</b> <span id="confirm_prdnamelbl"></span></small><br>
                    <small><b>Lot No:</b> <span id="confirm_ltnolbl"></span></small>
                </div>
                <div class="row g-2">
                    <div class="col-12 col-md-4">
                        <label>Total Trays</label>
                        <input type="number" class="form-control form-control-sm" id="total_tray_count" min="0"
                            readonly>
                    </div>
                    <div class="col-12 col-md-4">
                        <label>Confirmed Trays</label>
                        <input type="number" class="form-control form-control-sm" id="confirmed_tray_count" min="0">
                    </div>
                    <div class="col-12 col-md-4">
                        <label>Damaged / Lost</label>
                        <input type="number" class="form-control form-control-sm" id="damaged_tray_count" min="0">
                    </div>
                </div>
                <div class="text-center mt-3">
                    <button class="btn smart-confirm-btn btn-sm" id="ConfirmUpdateSowingTrayoslot">
                        ✔ Confirm & Update
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Update Sowing Location Modal -->
<div class="modal fade" id="updateSowingLocationModal" tabindex="-1" aria-labelledby="updateSowingLocationModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateSowingLocationModalLabel">Update Sowing Location</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="updateSowingLocationForm">
                    <input type="hidden" id="upl_stockorderno">
                    <input type="hidden" id="upl_projectid">
                    <input type="hidden" id="upl_roottype">
                    <input type="hidden" id="upl_sowingschedulid">
                    <div class="mb-3">
                        <label for="upl_productname" class="form-label">Product Name</label>
                        <input type="text" class="form-control" id="upl_productname" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="upl_stockorderno_disp" class="form-label">Stock Order No</label>
                        <input type="text" class="form-control" id="upl_stockorderno_disp" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="upl_sowinglocation" class="form-label">Select Location</label>
                        <select class="form-select" id="upl_sowinglocation" required>
                            <option value="">Choose...</option>

                        </select>
                    </div>
                    <!-- Capacity Details Section -->
                    <div id="polyhouseCapacityDetails"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveSowingLocationBtn">Save changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Update Trays in Box -->
<div class="modal fade" id="updateTraysInBoxModal" tabindex="-1" aria-labelledby="updateTraysInBoxModalLabel" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="updateTraysInBoxModalLabel"><i class="fa fa-box me-2"></i>Update Trays in Germination Box</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(1);"></button>
            </div>
            <div class="modal-body bg-light">
                <!-- Step 1: Scan Box QR -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3"><i class="fa fa-qrcode me-2 text-primary"></i>1. Scan or Enter Germination Box QR</h6>
                        <div class="row g-3 align-items-center">
                            <div class="col-md-8">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fa fa-barcode text-primary"></i></span>
                                    <input type="text" id="boxQrInput" class="form-control form-control-lg" placeholder="Scan or enter Germination Box QR code (e.g. BOX...)" autocomplete="off">
                                    <button class="btn btn-primary btn-lg" id="btnSearchBoxQr"><i class="fa fa-search"></i> Fetch Details</button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <button class="btn btn-outline-primary btn-lg w-100" id="btnToggleBoxCamera"><i class="fa fa-camera me-1"></i> Toggle Camera Scanner</button>
                            </div>
                        </div>
                        
                        <!-- Camera Scanner Container -->
                        <div id="boxScannerContainer" class="mt-3 text-center" style="display: none; background: #e2e8f0; border-radius: 8px; padding: 10px;">
                            <div id="box-qr-reader" style="width: 100%; max-width: 350px; margin: 0 auto;"></div>
                        </div>
                        <div id="boxScanError" class="text-danger small mt-2" style="display: none; font-weight: bold;"></div>
                    </div>
                </div>

                <!-- Step 2: Show Box Details & Edit Option -->
                <div id="boxDetailsSection" style="display: none;">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white py-3">
                            <h6 class="card-title mb-0 text-primary fw-bold"><i class="fa fa-info-circle me-2"></i>Germination Box Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3 mb-4">
                                <div class="col-md-3">
                                    <div class="small text-muted">Box QR Code</div>
                                    <div class="fw-bold fs-5 text-dark" id="lblBoxQr">-</div>
                                </div>
                                <div class="col-md-3">
                                    <div class="small text-muted">Product / Seed Variety</div>
                                    <div class="fw-bold fs-5 text-dark" id="lblBoxProduct">-</div>
                                </div>
                                <div class="col-md-3">
                                    <div class="small text-muted">Sowing Lot / Batch</div>
                                    <div class="fw-bold fs-5 text-dark" id="lblBoxBatch">-</div>
                                </div>
                                <div class="col-md-3">
                                    <div class="small text-muted">Date of Packing</div>
                                    <div class="fw-bold fs-5 text-dark" id="lblBoxPackingDate">-</div>
                                </div>
                            </div>
                            
                            <!-- Update Tray Count Input -->
                            <div class="row g-3 align-items-end p-3 bg-white border rounded-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-secondary">Number of Trays in Box <span class="text-danger">*</span></label>
                                    <input type="number" id="txtTrayCountInBox" class="form-control form-control-lg" min="1" placeholder="Enter number of trays">
                                </div>
                                <div class="col-md-6">
                                    <button class="btn btn-success btn-lg w-100" id="btnSaveBoxTrayCount"><i class="fa fa-save me-1"></i> Update Box Details</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Particular Tray List -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3">
                            <h6 class="card-title mb-0 text-primary fw-bold"><i class="fa fa-list me-2"></i>Trays Currently in this Box</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table align-middle table-hover mb-0" id="boxTraysTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 80px;">S.No.</th>
                                            <th style="width: 120px;">Sowing Plant ID</th>
                                            <th>Tray Barcode / QR Code</th>
                                            <th>Working Status</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Populated dynamically -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
  let boxQrScanner = null;

  // Open Modal
  $('#updateTraysInBoxBtn').click(function() {
    // Reset inputs
    $('#boxQrInput').val('');
    $('#boxScannerContainer').hide();
    $('#boxScanError').hide();
    $('#boxDetailsSection').hide();
    $('#txtTrayCountInBox').val('');
    $('#boxTraysTable tbody').empty();
    
    if (boxQrScanner && boxQrScanner.isScanning) {
      boxQrScanner.stop().catch(err => console.log(err));
    }

    const modal = new bootstrap.Modal(document.getElementById('updateTraysInBoxModal'));
    modal.show();
  });

  // Toggle Camera
  $('#btnToggleBoxCamera').click(function() {
    if ($('#boxScannerContainer').is(':visible')) {
      stopBoxScanner();
    } else {
      startBoxScanner();
    }
  });

  function startBoxScanner() {
    $('#boxScannerContainer').show();
    $('#boxScanError').hide();

    if (!boxQrScanner) {
      boxQrScanner = new Html5Qrcode("box-qr-reader");
    }

    const config = { fps: 10, qrbox: { width: 250, height: 250 } };

    boxQrScanner.start(
      { facingMode: "environment" },
      config,
      function(decodedText) {
        $('#boxQrInput').val(decodedText);
        fetchBoxDetails(decodedText);
        stopBoxScanner();
      }
    ).catch(err => {
      $('#boxScanError').text("Camera error: " + err).show();
      stopBoxScanner();
    });
  }

  function stopBoxScanner() {
    $('#boxScannerContainer').hide();
    if (boxQrScanner && boxQrScanner.isScanning) {
      boxQrScanner.stop().catch(err => console.error(err));
    }
  }

  // Fetch Details on Click or Enter
  $('#btnSearchBoxQr').click(function() {
    const qr = $('#boxQrInput').val().trim();
    if (qr) fetchBoxDetails(qr);
  });

  $('#boxQrInput').keypress(function(e) {
    if (e.which === 13) {
      const qr = $(this).val().trim();
      if (qr) fetchBoxDetails(qr);
    }
  });

  function fetchBoxDetails(qrcode) {
    const projectid = $('body').data('projectid') || 1;
    $('#boxScanError').hide();

    $.ajax({
      url: 'showingplan_starting.php?ajax_action=getBoxDetails',
      type: 'POST',
      data: { qrcode: qrcode, projectid: projectid },
      dataType: 'json',
      success: function(res) {
        if (res.status === 'success') {
          $('#lblBoxQr').text(res.box.qrcode);
          $('#lblBoxProduct').text(res.box.productname);
          $('#lblBoxBatch').text(res.box.sowinglotno_internal || 'N/A');
          $('#lblBoxPackingDate').text(res.box.boxpackingdate || 'N/A');
          $('#txtTrayCountInBox').val(res.box.traycountinbox);

          // Render Trays
          let trHtml = '';
          if (res.trays && res.trays.length > 0) {
            res.trays.forEach(function(tray, index) {
              trHtml += `
                <tr class="tray-row" data-sowingplantid="${tray.sowingplantid}">
                  <td>${index + 1}</td>
                  <td class="fw-bold">${tray.sowingplantid}</td>
                  <td>
                    <input type="text" class="form-control tray-barcode-input" value="${tray.barcode || ''}" placeholder="Scan/Type tray barcode">
                  </td>
                  <td><span class="badge bg-soft-info text-info" style="color: #0dcaf0; background: #0dcaf01a;">${tray.workingstatus}</span></td>
                  <td><span class="badge bg-soft-secondary text-secondary" style="color: #6c757d; background: #6c757d1a;">${tray.status}</span></td>
                </tr>
              `;
            });
          } else {
            trHtml = '<tr><td colspan="5" class="text-center text-muted">No individual trays linked to this box in database.</td></tr>';
          }
          $('#boxTraysTable tbody').html(trHtml);
          $('#boxDetailsSection').slideDown();
        } else {
          $('#boxScanError').text(res.message).show();
          $('#boxDetailsSection').hide();
        }
      },
      error: function() {
        $('#boxScanError').text("Network error fetching box details.").show();
        $('#boxDetailsSection').hide();
      }
    });
  }

  // Update Box and Trays
  $('#btnSaveBoxTrayCount').click(function() {
    const qrcode = $('#lblBoxQr').text();
    const traycount = parseInt($('#txtTrayCountInBox').val()) || 0;
    const projectid = $('body').data('projectid') || 1;

    if (traycount <= 0) {
      alert("Please enter a valid tray count");
      return;
    }

    // Collect individual tray updates
    let trays = [];
    $('.tray-row').each(function() {
      const sowingplantid = $(this).data('sowingplantid');
      const barcode = $(this).find('.tray-barcode-input').val().trim();
      trays.push({ sowingplantid: sowingplantid, barcode: barcode });
    });

    $.ajax({
      url: 'showingplan_starting.php?ajax_action=updateBoxTrays',
      type: 'POST',
      data: {
        qrcode: qrcode,
        traycountinbox: traycount,
        trays: trays,
        projectid: projectid
      },
      dataType: 'json',
      success: function(res) {
        if (res.status === 'success') {
          alert(res.message);
          // Refresh details
          fetchBoxDetails(qrcode);
        } else {
          alert('Update failed: ' + res.message);
        }
      },
      error: function() {
        alert('Network error during update.');
      }
    });
  });

  // Stop camera on modal hidden
  $('#updateTraysInBoxModal').on('hidden.bs.modal', function () {
    stopBoxScanner();
  });
});
</script>

<?php include('../footer.php'); ?>
<script type="text/javascript" src="<?php echo $prefix; ?>krf-orderandsales/js/pjs_function.js"></script>
<!-- Bootstrap JS + dependencies -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<!-- QR Scanner (html5-qrcode) -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<!-- QZ Tray for Thermal Printing (Local) -->
<script src="js/sha256.min.js"></script>
<script>
    // QZ Tray expects an object named 'Sha256' with a '.hash()' method.
    // The js-sha256 library exports a global 'sha256' function.
    if (typeof Sha256 === 'undefined' && typeof sha256 !== 'undefined') {
        window.Sha256 = {
            hash: function(data) {
                return sha256(data);
            }
        };
    }
</script>
<script src="js/qz-tray.js"></script>
<script>
    // console.log("Checking QZ Tray loading...");
    if (typeof qz !== 'undefined') {
        // console.log("QZ Tray library loaded successfully. Version: " + qz.version);
    } else {
        // console.error("QZ Tray library failed to load. Check if 'js/qz-tray.js' exists in the 'krf-orderandsales' folder.");
    }
</script>

<script type="text/javascript" src="<?php echo $prefix; ?>krf-orderandsales/js/sowing_plan_operations.js?v=<?php echo time(); ?>"></script>

<script>
function printBarcodeDiv() {
    let div = document.getElementById("ViewBarcodeDiv");

    if (!div) {
        alert("Barcode Div not found!");
        return;
    }

    // 🔹 Clone content (important for modal)
    let printContent = div.cloneNode(true);

    // 🔹 Create temp container
    let printContainer = document.createElement("div");
    printContainer.id = "printSection";
    printContainer.appendChild(printContent);
    document.body.appendChild(printContainer);
    // 🔹 If barcode/QR is canvas → convert to image
    let canvas = printContainer.querySelector("canvas");
    if (canvas) {
        let img = document.createElement("img");
        img.src = canvas.toDataURL();
        canvas.parentNode.replaceChild(img, canvas);
    }
    window.print();
    // 🔹 Cleanup
    document.body.removeChild(printContainer);
}
</script>