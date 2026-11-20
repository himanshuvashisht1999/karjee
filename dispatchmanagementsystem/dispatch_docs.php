<?php
include('../header.php');
date_default_timezone_set("Asia/Calcutta");

$trip_id = $_GET['trip'] ?? '';
$back_url = "index.php"; // default
$is_internal = false;

if ($trip_id) {
    // Fetch Trip details
    $trip_res = $krfconn->query("SELECT * FROM krf_vehicle_travel_history WHERE trip_id = '$trip_id'");
    $trip_items = [];
    if ($trip_res) {
        while ($row = $trip_res->fetch_assoc()) {
            $trip_items[] = $row;
        }
    }
    if (!empty($trip_items)) {
        $first_item = $trip_items[0];
        if (!empty($first_item['stockorder_no'])) {
            $back_url = "internaldispatch.php";
            $is_internal = true;
        }
    }
}

if (!$trip_id) {
    echo "<div class='container p-5 text-center'><h3>Invalid Trip ID</h3><a href='{$back_url}?k=$key" . ((isset($_GET['iframe']) && $_GET['iframe'] == 'true') ? "&iframe=true" : "") . "' class='btn btn-primary'>Back to Dashboard</a></div>";
    include('../footer.php');
    exit;
}

if (empty($trip_items)) {
    echo "<div class='container p-5 text-center'><h3>Trip Not Found</h3><a href='{$back_url}?k=$key" . ((isset($_GET['iframe']) && $_GET['iframe'] == 'true') ? "&iframe=true" : "") . "' class='btn btn-primary'>Back to Dashboard</a></div>";
    include('../footer.php');
    exit;
}

// Check if documents are already generated
$gp_path = "attachementfile/dispatch/gate_pass_$trip_id.pdf";
$dn_path = "attachementfile/dispatch/delivery_note_$trip_id.pdf";
$gp_exists = file_exists("../$gp_path");
$dn_exists = file_exists("../$dn_path");

// For invoices, look into their specific directories using glob
$psono_clean = str_replace("/", "_", $first_item['psono']);
$fi_glob = glob("../attachementfile/freight_invoice/*/*" . $psono_clean . "*.pdf");
$fi_path = !empty($fi_glob) ? str_replace('../', '', $fi_glob[0]) : '';

$si_glob = glob("../attachementfile/invoice/*/*INV-$trip_id.pdf");
$si_path = !empty($si_glob) ? str_replace('../', '', $si_glob[0]) : '';
?>

<style>
    :root {
        --dispatch-primary: #4f46e5;
        --doc-bg: #f8fafc;
    }

    .container-docs {
        padding: 24px;
        background: var(--doc-bg);
        min-height: calc(100vh - 120px);
    }

    .glass-card {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        border: 1px solid rgba(255, 255, 255, 0.5);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }

    .doc-preview-card {
        transition: transform 0.3s;
        cursor: pointer;
        border: 2px solid transparent;
    }

    .doc-preview-card:hover {
        transform: translateY(-5px);
        border-color: var(--dispatch-primary);
    }

    .doc-icon {
        font-size: 3rem;
        margin-bottom: 20px;
    }

    .trip-badge {
        background: #ede9fe;
        color: #6d28d9;
        padding: 5px 15px;
        border-radius: 50px;
        font-weight: 700;
    }

    .btn-back {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: white;
        color: #475569;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 8px 16px;
        font-weight: 600;
        font-size: 0.875rem;
        transition: all 0.2s;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        text-decoration: none;
    }

    .btn-back:hover {
        background: #f8fafc;
        color: var(--dispatch-primary);
        border-color: #cbd5e1;
        text-decoration: none;
        transform: translateX(-2px);
    }
</style>

<div class="container-docs">
    <div class="row mb-5">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo $back_url; ?>?k=<?php echo $key; ?><?php echo (isset($_GET['iframe']) && $_GET['iframe'] == 'true') ? '&iframe=true' : ''; ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active">Documentation</li>
                </ol>
            </nav>
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                <h2 class="fw-bold text-slate-900 mb-0">Dispatch Documentation</h2>
                <a href="<?php echo $back_url; ?>?k=<?php echo $key; ?><?php echo (isset($_GET['iframe']) && $_GET['iframe'] == 'true') ? '&iframe=true' : ''; ?>" class="btn-back">
                    <i class="fa fa-arrow-left"></i>
                    <span>Back to Dashboard</span>
                </a>
            </div>
            <p class="text-slate-500">Step 3: Generate and download documents for Trip <span class="trip-badge"><?php echo $trip_id; ?></span></p>
        </div>
    </div>

    <!-- Trip Summary Card -->
    <div class="glass-card p-4 mb-5">
        <div class="row align-items-center">
            <div class="col-md-3 border-end">
                <div class="small text-slate-400">VEHICLE</div>
                <div class="fw-bold text-slate-800"><?php echo htmlspecialchars($first_item['vehicle_no']); ?></div>
            </div>
            <div class="col-md-3 border-end">
                <div class="small text-slate-400">DRIVER</div>
                <div class="fw-bold text-slate-800"><?php echo htmlspecialchars($first_item['driver_name']); ?></div>
            </div>
            <div class="col-md-3 border-end">
                <div class="small text-slate-400"><?php echo $is_internal ? 'PRODUCTION ORDER' : 'DESTINATION (PSO)'; ?></div>
                <div class="fw-bold text-slate-800"><?php echo htmlspecialchars($is_internal ? $first_item['stockorder_no'] : $first_item['psono']); ?></div>
            </div>
            <div class="col-md-3">
                <div class="small text-slate-400">DISPATCH STATUS</div>
                <div class="badge bg-warning text-dark"><?php echo htmlspecialchars($first_item['status']); ?></div>
            </div>
        </div>
    </div>

    <!-- Document Actions -->
    <div class="row g-4">
        <?php $card_col = $is_internal ? 'col-md-4' : 'col-md-3'; ?>
        
        <!-- Gate Pass -->
        <div class="<?php echo $card_col; ?>">
            <div class="glass-card p-4 text-center doc-preview-card h-100">
                <div class="doc-icon text-primary"><i class="fa fa-id-card"></i></div>
                <h5 class="fw-bold text-slate-800">Gate Pass</h5>
                <p class="text-slate-500 x-small mb-4">Exit pass for vehicle and load.</p>
                <div id="gate_pass_link" class="mt-auto">
                    <?php if ($gp_exists): ?>
                        <a href="../<?php echo $gp_path; ?>" target="_blank" class="btn btn-outline-success btn-sm rounded-pill w-100 mb-2">View Gate Pass</a>
                        <button class="btn btn-primary btn-sm rounded-pill w-100" onclick="generateDoc('gate_pass')">Regenerate</button>
                    <?php else: ?>
                        <button class="btn btn-primary btn-sm rounded-pill w-100" onclick="generateDoc('gate_pass')">Generate</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Delivery Note -->
        <div class="<?php echo $card_col; ?>">
            <div class="glass-card p-4 text-center doc-preview-card h-100">
                <div class="doc-icon text-indigo-500"><i class="fa fa-file-invoice"></i></div>
                <h5 class="fw-bold text-slate-800">Delivery Note</h5>
                <p class="text-slate-500 x-small mb-4">Breakdown for the customer.</p>
                <div id="delivery_note_link" class="mt-auto">
                    <?php if ($dn_exists): ?>
                        <a href="../<?php echo $dn_path; ?>" target="_blank" class="btn btn-outline-success btn-sm rounded-pill w-100 mb-2">View Delivery Note</a>
                        <button class="btn btn-indigo btn-sm rounded-pill w-100 text-white" style="background-color: #6366f1;" onclick="generateDoc('delivery_note')">Regenerate</button>
                    <?php else: ?>
                        <button class="btn btn-indigo btn-sm rounded-pill w-100 text-white" style="background-color: #6366f1;" onclick="generateDoc('delivery_note')">Generate</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Freight Invoice -->
        <div class="<?php echo $card_col; ?>">
            <div class="glass-card p-4 text-center doc-preview-card h-100">
                <div class="doc-icon text-orange-500"><i class="fa fa-truck-loading"></i></div>
                <h5 class="fw-bold text-slate-800">Freight Invoice</h5>
                <p class="text-slate-500 x-small mb-4">Invoice for vehicle charges.</p>
                <div id="freight_invoice_link" class="mt-auto">
                    <?php if ($fi_path): ?>
                        <a href="../<?php echo $fi_path; ?>" target="_blank" class="btn btn-outline-success btn-sm rounded-pill w-100 mb-2">View Invoice</a>
                        <button class="btn btn-warning btn-sm rounded-pill w-100 text-white" onclick="generateDoc('freight_invoice')">Regenerate</button>
                    <?php else: ?>
                        <button class="btn btn-warning btn-sm rounded-pill w-100 text-white" onclick="generateDoc('freight_invoice')">Generate</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if (!$is_internal): ?>
        <!-- Final Sale Invoice -->
        <div class="col-md-3">
            <div class="glass-card p-4 text-center doc-preview-card h-100">
                <div class="doc-icon text-success"><i class="fa fa-file-signature"></i></div>
                <h5 class="fw-bold text-slate-800">Final Sale Invoice</h5>
                <p class="text-slate-500 x-small mb-4">Official billing for products.</p>
                <div id="final_invoice_link" class="mt-auto">
                    <?php if ($si_path): ?>
                        <a href="../<?php echo $si_path; ?>" target="_blank" class="btn btn-outline-success btn-sm rounded-pill w-100 mb-2">View Sale Invoice</a>
                        <button class="btn btn-success btn-sm rounded-pill w-100" onclick="generateDoc('final_invoice')">Regenerate</button>
                    <?php else: ?>
                        <button class="btn btn-success btn-sm rounded-pill w-100" onclick="generateDoc('final_invoice')">Generate</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Next Step -->
    <div class="text-center mt-5 pt-5">
        <a href="order_tracking.php?k=<?php echo $key; ?>&trip=<?php echo $trip_id; ?><?php echo (isset($_GET['iframe']) && $_GET['iframe'] == 'true') ? '&iframe=true' : ''; ?>" class="btn btn-outline-slate-800 btn-lg rounded-pill px-5">
            Continue to Tracking <i class="fa fa-arrow-right ms-2"></i>
        </a>
    </div>
</div>

<?php include('../footer.php'); ?>

<script>
function generateDoc(type) {
    let loader = '<div class="spinner-border spinner-border-sm text-primary" role="status"></div> Generating...';
    $(`#${type}_link`).html(loader);

    let action = 'generate_pdf';
    if(type === 'freight_invoice') action = 'generate_freight_pdf';
    if(type === 'final_invoice') action = 'generate_final_invoice_pdf';

    $.ajax({
        url: 'ajax/handle_docs.php',
        type: 'POST',
        data: { 
            action: action, 
            type: type, 
            trip_id: '<?php echo $trip_id; ?>',
            projectid: '<?php echo $projectid; ?>',
            loginname: '<?php echo $loginname; ?>'
        },
        dataType: 'json',
        success: function(res) {
            if (res.status == 'success') {
                $(`#${type}_link`).html(`
                    <div class="alert alert-success py-2 shadow-sm border-0 d-inline-block w-100">
                        <i class="fa fa-check-circle me-1"></i> 
                        <a href="../${res.filepath}" target="_blank" class="fw-bold text-decoration-underline x-small">View PDF</a>
                    </div>
                `);
                
                let title = type.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ') + ' Generated!';
                
                Swal.fire({
                    title: title,
                    text: 'The document is ready for viewing/printing.',
                    icon: 'success',
                    footer: '<a href="../' + res.filepath + '" target="_blank">Open PDF</a>'
                });
            } else {
                $(`#${type}_link`).html(`<div class="text-danger x-small">${res.message}</div>`);
                Swal.fire('Error', res.message, 'error');
            }
        },
        error: function() {
            $(`#${type}_link`).html('<div class="text-danger x-small">Network error occurred.</div>');
        }
    });
}
</script>
