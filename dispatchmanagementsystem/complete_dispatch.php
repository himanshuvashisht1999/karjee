<?php
include('../header.php');
date_default_timezone_set("Asia/Calcutta");

$trip_id = $_GET['trip'] ?? '';
$back_url = "index.php"; // default
$trip_data = null;

if ($trip_id) {
    // Fetch Trip details
    $trip_res = $krfconn->query("SELECT * FROM krf_vehicle_travel_history WHERE trip_id = '$trip_id'");
    $trip_data = $trip_res ? $trip_res->fetch_assoc() : null;
    if ($trip_data && !empty($trip_data['stockorder_no'])) {
        $back_url = "internaldispatch.php";
    }
}

if (!$trip_id || !$trip_data) {
    echo "<div class='container p-5 text-center'><h3>Invalid Trip ID</h3><a href='{$back_url}?k=$key" . ((isset($_GET['iframe']) && $_GET['iframe'] == 'true') ? "&iframe=true" : "") . "' class='btn btn-primary'>Back to Dashboard</a></div>";
    include('../footer.php');
    exit;
}

if ($trip_data['status'] == 'Delivered') {
    echo "<div class='container p-5 text-center'>
            <div class='glass-card p-5 d-inline-block'>
                <i class='fa fa-check-circle text-success fa-4x mb-4'></i>
                <h3 class='fw-bold'>This Trip was already Delivered</h3>
                <p class='text-muted'>Record is now locked for editing.</p>
                <a href='{$back_url}?k=$key" . ((isset($_GET['iframe']) && $_GET['iframe'] == 'true') ? "&iframe=true" : "") . "' class='btn btn-primary rounded-pill px-4'>Back to Dashboard</a>
            </div>
          </div>";
    include('../footer.php');
    exit;
}
?>

<style>
    :root {
        --dispatch-success: #10b981;
    }

    .container-complete {
        padding: 24px;
        background: #f8fafc;
        min-height: calc(100vh - 120px);
    }

    .glass-card {
        background: white;
        border-radius: 20px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
    }

    .upload-area {
        border: 2px dashed #cbd5e1;
        border-radius: 20px;
        padding: 40px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s;
    }

    .upload-area:hover {
        border-color: var(--dispatch-success);
        background: #f0fdf4;
    }

    #proof_preview {
        max-width: 100%;
        max-height: 300px;
        border-radius: 12px;
        margin-top: 20px;
        display: none;
    }
</style>

<div class="container-complete">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="glass-card p-5">
                <div class="text-center mb-5">
                    <h2 class="fw-bold text-slate-900">Delivery Confirmation</h2>
                    <p class="text-slate-500">Capture or upload proof of delivery for Trip <span class="fw-bold text-primary"><?php echo $trip_id; ?></span></p>
                </div>

                <form id="frm_complete">
                    <input type="hidden" name="trip_id" value="<?php echo $trip_id; ?>">
                    <input type="hidden" name="action" value="complete_delivery">

                    <div class="mb-5">
                        <label class="form-label fw-bold small text-slate-400 uppercase">Proof of Delivery (Photo)</label>
                        <div class="upload-area" onclick="$('#proof_file').click()">
                            <i class="fa fa-camera fa-2x text-slate-400 mb-3"></i>
                            <div class="fw-bold text-slate-600">Click to capture or upload</div>
                            <div class="small text-slate-400">JPG, PNG allowed | Max 5MB</div>
                            <img id="proof_preview" src="#" alt="Preview">
                        </div>
                        <input type="file" id="proof_file" name="proof_file" class="d-none" accept="image/*" capture="environment">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-slate-400 uppercase">Received Quantity Details</label>
                        <div class="p-3 bg-light rounded-12 mb-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="small">Sent Quantity:</span>
                                <span class="fw-bold"><?php echo $trip_data['send_totalplantcount']; ?></span>
                            </div>
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <label class="small fw-bold">Received Quantity</label>
                                    <input type="number" class="form-control" name="received_qty" value="<?php echo $trip_data['send_totalplantcount']; ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="small fw-bold">Damages / Shortage</label>
                                    <input type="number" class="form-control" name="damage_qty" value="0">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-slate-400 uppercase">Receiver Signature / Name</label>
                        <input type="text" class="form-control" name="receiver_name" placeholder="Name of the person who received it" required>
                    </div>

                    <button type="submit" class="btn btn-success w-100 btn-lg rounded-pill fw-bold shadow-lg">
                        Finalize Delivery & Lock Trip
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include('../footer.php'); ?>

<script>
$(document).ready(function() {
    $('#proof_file').on('change', function() {
        const file = this.files[0];
        if (file) {
            let reader = new FileReader();
            reader.onload = function(e) {
                $('#proof_preview').attr('src', e.target.result).show();
            }
            reader.readAsDataURL(file);
        }
    });

    $('#frm_complete').on('submit', function(e) {
        e.preventDefault();
        
        // Multi-part for file upload
        let formData = new FormData(this);

        Swal.fire({
            title: 'Finalize Delivery?',
            text: "This will officially mark the trip as 'Delivered' and it will no longer be editable.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            confirmButtonText: 'Yes, Delivered'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'ajax/handle_completion.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(res) {
                        if (res.status == 'success') {
                            Swal.fire('Trip Completed!', 'Delivery proof saved successfully.', 'success').then(() => {
                                window.location.href = '<?php echo $back_url; ?>?k=<?php echo $key; ?><?php echo (isset($_GET['iframe']) && $_GET['iframe'] == 'true') ? '&iframe=true' : ''; ?>';
                            });
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    }
                });
            }
        });
    });
});

// Global defensive fix for "Cannot stop, scanner is not running or paused" error
(function() {
    const originalError = console.error;
    console.error = function() {
        if (arguments[0] && typeof arguments[0] === 'string' && arguments[0].includes('Cannot stop, scanner is not running or paused')) {
            return; // Suppress this specific error
        }
        originalError.apply(console, arguments);
    };

    // Monkey-patch Html5Qrcode if it exists
    const checkAndPatch = () => {
        if (typeof Html5Qrcode !== 'undefined' && !Html5Qrcode._is_patched) {
            const originalStop = Html5Qrcode.prototype.stop;
            Html5Qrcode.prototype.stop = function() {
                try {
                    // Check if scanner is actually running before stopping
                    if (this.getState() === 2) { // 2 = Html5QrcodeScannerState.SCANNING
                        return originalStop.apply(this, arguments);
                    } else {
                        return Promise.resolve();
                    }
                } catch (e) {
                    return Promise.resolve();
                }
            };
            Html5Qrcode._is_patched = true;
            console.log("Dispatch Scanner Defense: Html5Qrcode.stop() patched.");
        }
    };

    checkAndPatch();
    let patchInterval = setInterval(() => {
        checkAndPatch();
        if (typeof Html5Qrcode !== 'undefined' && Html5Qrcode._is_patched) {
            clearInterval(patchInterval);
        }
    }, 1000);
})();
</script>
