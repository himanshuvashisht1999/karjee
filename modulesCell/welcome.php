<?php
$hide_header = true;
include("../header.php");

if (!isset($loginid) || $loginid == "") {
    echo "<div class='text-center mt-5'><h3>Please Login First</h3></div>";
    exit;
}
?>
<div class="row d-flex justify-content-center align-items-center py-5" style="min-height: 80vh;">
    <div class="col-11 col-md-10 col-lg-8 text-center">
        <div class="mb-4">
            <i class="fas fa-columns fa-4x text-info"></i>
        </div>
        <h2 class="text-dark">Welcome back, <span class="text-primary">
                <?php echo htmlspecialchars($loginname); ?>
            </span></h2>
        <p class="lead text-muted mt-3">Select a module from the left menu to begin working.</p>

        <div class="row mt-5 g-3">
            <div class="col-12 col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title text-info"><i class="fas fa-clock mr-2"></i> Session</h5>
                        <p class="card-text small">Your session is active.</p>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4 mt-3 mt-md-0">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title text-success"><i class="fas fa-check-circle mr-2"></i> System</h5>
                        <p class="card-text small">System is running smooth.</p>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4 mt-3 mt-md-0">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title text-warning"><i class="fas fa-calendar-alt mr-2"></i> Date</h5>
                        <p class="card-text small">
                            <?php echo date('d M Y'); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include("../footer.php"); ?>