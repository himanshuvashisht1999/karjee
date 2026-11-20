<?php
include('../header.php');
date_default_timezone_set("Asia/Calcutta");

$logoPath = __DIR__ . '/../img/logo/logo.png';
$krfLogoBase64 = '';
if (file_exists($logoPath)) {
    $logoData = file_get_contents($logoPath);
    $krfLogoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
}
?>
<!-- Font Awesome 6 Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<!-- Module Custom CSS -->
<link href="css/postdispatch_polyhousestock.css?v=<?php echo time(); ?>" rel="stylesheet" />

<!-- jQuery & Third-Party Libraries -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<div class="container-fluid my-4" data-projectid="<?php echo htmlspecialchars($projectid ?? '0'); ?>" data-loginid="<?php echo htmlspecialchars($loginid ?? ''); ?>">
    <div class="card shadow-sm border-0">
        <!-- Page Header -->
        <div class="card-header bg-white py-3 border-bottom">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 text-white d-flex align-items-center justify-content-center shadow-sm" style="width: 44px; height: 44px; background-color: #0d9488;">
                        <i class="fa fa-boxes-packing fs-5"></i>
                    </div>
                    <div>
                        <h3 class="mb-0 text-dark font-weight-bold page-title" style="font-size: 21px; white-space: nowrap;">
                            Post-Dispatch Residual Polyhouse Stock Tracker
                        </h3>
                        <div class="text-muted small" style="font-size: 12px;">
                            Monitor available surplus plants remaining in polyhouse after client final dispatch
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <a href="../timetable_finaldispatch.php?k=<?php echo urlencode($_GET['k'] ?? ''); ?>" class="btn btn-outline-success btn-sm fw-bold" style="height: 38px; display: inline-flex; align-items: center;">
                        <i class="fa fa-calendar-alt me-1"></i> Final Dispatch Timetable
                    </a>
                    <a href="../vehicle_allocation.php?k=<?php echo urlencode($_GET['k'] ?? ''); ?>" class="btn btn-outline-primary btn-sm fw-bold" style="height: 38px; display: inline-flex; align-items: center;">
                        <i class="fa fa-truck-moving me-1"></i> Vehicle Allocation Hub
                    </a>
                    <button class="btn btn-sm btn-outline-secondary" onclick="window.location.reload();" style="height: 38px;">
                        <i class="fa fa-sync"></i> Refresh
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body p-3">
            <!-- 1. Filter Bar: Year, Month, Week, Navigation & Export -->
            <div class="d-flex flex-wrap align-items-end gap-3 mb-3 p-3 bg-light rounded-3 shadow-sm border">
                <div style="min-width: 140px; flex: 0.8;">
                    <label class="form-label fw-bold text-secondary small text-uppercase mb-1" style="font-size: 11px; letter-spacing: 0.5px;">Select Year</label>
                    <select id="surplusYearFilter" class="form-select" style="height: 42px;"></select>
                </div>
                <div style="min-width: 180px; flex: 1;">
                    <label class="form-label fw-bold text-secondary small text-uppercase mb-1" style="font-size: 11px; letter-spacing: 0.5px;">Select Month</label>
                    <select id="surplusMonthFilter" class="form-select" style="height: 42px;"></select>
                </div>
                <div style="min-width: 250px; flex: 1.5;">
                    <label class="form-label fw-bold text-secondary small text-uppercase mb-1" style="font-size: 11px; letter-spacing: 0.5px;">Select Week</label>
                    <select id="surplusWeekSelector" class="form-select" style="height: 42px;"></select>
                </div>
                <div class="d-flex gap-2 flex-wrap ms-md-auto align-items-center mt-2 mt-md-0">
                    <button class="btn" id="surplusPrevWeekBtn" style="height: 42px;">← Previous Week</button>
                    <button class="btn" id="surplusNextWeekBtn" style="height: 42px;">Next Week →</button>
                    <button class="btn btn-success fw-bold" id="btnExportExcel" style="height: 42px; background-color: #107c41; border-color: #107c41; display: inline-flex; align-items: center;" title="Export surplus schedule to Excel">
                        <i class="fa fa-file-excel text-white me-1"></i> Excel
                    </button>
                    <button class="btn btn-danger fw-bold" id="btnExportPdf" style="height: 42px; background-color: #dc2626; border-color: #dc2626; display: inline-flex; align-items: center;" title="Open printable PDF preview">
                        <i class="fa fa-file-pdf text-white me-1"></i> Print / PDF
                    </button>
                </div>
            </div>

            <!-- 2. KPI Summary Cards -->
            <div class="row g-3 mb-3">
                <!-- Total Orders with Surplus -->
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="surplus-kpi-card card-batches">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="card-kpi-title">Batches with Surplus</span>
                                <h3 class="card-kpi-value mb-0" id="kpiSurplusOrdersCount">0</h3>
                                <span class="card-kpi-sub">Dispatched Batches</span>
                            </div>
                            <div class="card-kpi-icon">
                                <i class="fa fa-clipboard-list"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Sowing Plants -->
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="surplus-kpi-card card-sowing">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="card-kpi-title">Total Sowing Plants</span>
                                <h3 class="card-kpi-value mb-0" id="kpiTotalSowingPlants">0</h3>
                                <span class="card-kpi-sub">Original Sown Quantity</span>
                            </div>
                            <div class="card-kpi-icon">
                                <i class="fa fa-seedling"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Dispatched to Clients -->
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="surplus-kpi-card card-dispatched">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="card-kpi-title">Dispatched to Clients</span>
                                <h3 class="card-kpi-value mb-0" id="kpiTotalDispatchedPlants">0</h3>
                                <span class="card-kpi-sub">Delivered Orders</span>
                            </div>
                            <div class="card-kpi-icon">
                                <i class="fa fa-truck"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Surplus Available in Polyhouse -->
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="surplus-kpi-card card-surplus">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="card-kpi-title">Available in Polyhouse</span>
                                <h3 class="card-kpi-value mb-0 text-success" id="kpiTotalSurplusPlants">0</h3>
                                <span class="card-kpi-sub text-success fw-bold">Residual Ready Plants</span>
                            </div>
                            <div class="card-kpi-icon">
                                <i class="fa fa-warehouse"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. In-Table Secondary Dynamic Filters -->
            <div class="card mb-3 border bg-white shadow-sm" style="border-radius: 10px;">
                <div class="card-body p-3">
                    <div class="row g-2 align-items-end">
                        <!-- 1. Delivery / Dispatch Date -->
                        <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                            <label class="form-label fw-bold text-secondary small text-uppercase mb-1" style="font-size: 11px;">
                                <i class="fa fa-calendar-day text-success me-1"></i> Dispatch Date
                            </label>
                            <select id="filterDeliveryDate" class="form-select select2-filter" style="width: 100%;">
                                <option value="all">All Dates</option>
                            </select>
                        </div>

                        <!-- 2. Client Name / Sales Order -->
                        <div class="col-12 col-sm-6 col-md-3 col-lg-3">
                            <label class="form-label fw-bold text-secondary small text-uppercase mb-1" style="font-size: 11px;">
                                <i class="fa fa-user text-primary me-1"></i> Client / Sales Order
                            </label>
                            <select id="filterClient" class="form-select select2-filter" style="width: 100%;">
                                <option value="all">All Clients & Orders</option>
                            </select>
                        </div>

                        <!-- 3. Type (Grafted / Non-Grafted) -->
                        <div class="col-12 col-sm-6 col-md-2 col-lg-2">
                            <label class="form-label fw-bold text-secondary small text-uppercase mb-1" style="font-size: 11px;">
                                <i class="fa fa-dna text-danger me-1"></i> Type
                            </label>
                            <select id="filterType" class="form-select select2-filter" style="width: 100%;">
                                <option value="all">All Types</option>
                                <option value="grafted">Grafted</option>
                                <option value="nongrafted">Non-Grafted</option>
                            </select>
                        </div>

                        <!-- 4. Product Name -->
                        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                            <label class="form-label fw-bold text-secondary small text-uppercase mb-1" style="font-size: 11px;">
                                <i class="fa fa-seedling text-teal me-1" style="color: #0d9488;"></i> Product Name
                            </label>
                            <select id="filterProduct" class="form-select select2-filter" style="width: 100%;">
                                <option value="all">All Products</option>
                            </select>
                        </div>

                        <!-- 5. Polyhouse -->
                        <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                            <label class="form-label fw-bold text-secondary small text-uppercase mb-1" style="font-size: 11px;">
                                <i class="fa fa-warehouse text-info me-1"></i> Polyhouse
                            </label>
                            <select id="filterPolyhouse" class="form-select select2-filter" style="width: 100%;">
                                <option value="all">All Polyhouses</option>
                            </select>
                        </div>
                    </div>

                    <!-- Bottom row: Row counter, quick search input, and Reset button -->
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mt-3 pt-2 border-top">
                        <div class="text-muted small">
                            <i class="fa fa-list-check text-success me-1"></i> Showing <span class="badge bg-light text-dark border fw-bold px-2 py-1" id="filteredRowCountDisplay">0</span> of <span class="fw-bold" id="totalRowCountDisplay">0</span> batches with surplus
                        </div>
                        <div class="flex-grow-1" style="min-width: 300px; max-width: 600px;">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0 px-3"><i class="fa fa-search text-muted"></i></span>
                                <input type="text" id="surplusSearchInput" class="form-control border-start-0" style="height: 38px; font-size: 13px;" placeholder="Search by Client, SO/PSONO, Batch No, Product, Polyhouse, Bed...">
                            </div>
                        </div>
                        <div class="d-flex gap-2 align-items-center">
                            <button type="button" class="btn btn-outline-danger" id="btnResetFilters" style="height: 38px; padding: 0 16px;">
                                <i class="fa fa-rotate-left me-1"></i> Reset Filters
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Data Table Grid -->
            <div class="row mt-2">
                <div class="col-md-12">
                    <div class="table-responsive surplus-table-responsive">
                        <table class="table table-bordered table-hover mb-0" id="surplusStockTable">
                            <thead id="surplusTableHead">
                                <tr>
                                    <th class="text-center" style="width: 45px; vertical-align: middle;">S.No</th>
                                    <th style="min-width: 140px;">Dispatch Date & SO</th>
                                    <th style="min-width: 220px;">Client & Contact</th>
                                    <th style="min-width: 200px;">Product & Variety</th>
                                    <th class="text-center" style="min-width: 155px;">Sowing / Order</th>
                                    <th class="text-center" style="min-width: 110px;">Polyhouse Received</th>
                                    <th class="text-center" style="min-width: 100px;">Allocated</th>
                                    <th class="text-center" style="min-width: 100px;">Dispatched</th>
                                    <th class="text-center" style="min-width: 120px;">Est. Open Stock</th>
                                    <th style="min-width: 160px;">Polyhouse & Beds</th>
                                    <th class="text-center" style="min-width: 120px;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="surplusStockTableBody">
                                <tr>
                                    <td colspan="11" class="text-center py-4 text-muted">
                                        <i class="fa fa-spinner fa-spin me-2"></i> Loading residual polyhouse stock...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Column Detail Popup -->
<div class="modal fade" id="orderGrowthStatusModal" tabindex="-1" aria-labelledby="orderGrowthStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom" style="background-color: #f0f9ff; border-bottom: 2px solid #bae6fd !important;">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary rounded-3 p-2 d-flex align-items-center justify-content-center text-white shadow-sm" id="growthModalIconBox" style="width: 44px; height: 44px;">
                        <i class="fa fa-seedling fs-5" id="growthModalIcon"></i>
                    </div>
                    <div>
                        <h4 class="modal-title fw-bold mb-0 text-dark" id="orderGrowthStatusModalLabel">
                            Batch & Polyhouse Details
                        </h4>
                        <span class="text-secondary small fw-medium" id="growthModalSubtitle">Complete Production Stock Order Details</span>
                    </div>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <button type="button" class="btn btn-danger btn-sm px-3 shadow-sm close-modal-btn" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fa fa-times me-1"></i> Close
                    </button>
                </div>
            </div>
            <div class="modal-body bg-light p-4" id="orderGrowthModalBody">
                <div class="text-center py-5 text-muted">
                    <i class="fa fa-spinner fa-spin fa-2x me-2 text-primary"></i>
                    <span class="fs-5">Loading details...</span>
                </div>
            </div>
            <div class="modal-footer bg-white py-2 px-4 border-top d-flex justify-content-between align-items-center">
                <span class="text-muted small"><i class="fa fa-info-circle text-primary me-1"></i> Click any top tab to switch between Sowing, Ready, Allocated, and Dispatched breakdowns.</span>
                <button type="button" class="btn btn-secondary px-4 close-modal-btn" data-dismiss="modal" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: PDF & Print Preview for Surplus Stock -->
<div class="modal fade" id="surplusPdfPreviewModal" tabindex="-1" role="dialog" aria-labelledby="surplusPdfPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document" style="max-width: 95vw;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header py-2.5 px-4 d-flex justify-content-between align-items-center bg-dark text-white">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa fa-file-pdf text-danger fs-5"></i>
                    <div>
                        <h6 class="modal-title fw-bold mb-0 text-white" id="surplusPdfPreviewModalLabel">Post-Dispatch Residual Polyhouse Stock Report</h6>
                        <span class="text-white-50 small" id="surplusPdfPreviewSubtitle" style="font-size: 11.5px;">A4 Landscape Print / PDF Document</span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-primary px-3 fw-bold" id="btnPdfModalPrint">
                        <i class="fa fa-print me-1"></i> Print
                    </button>
                    <button type="button" class="btn btn-sm btn-danger px-3 fw-bold" id="btnPdfModalDownload">
                        <i class="fa fa-download me-1"></i> Download PDF
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-light px-2.5" data-bs-dismiss="modal" data-dismiss="modal">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="modal-body p-2 p-md-3" style="background-color: #525659; overflow-y: auto; max-height: 80vh;">
                <!-- Printable Paper Container (Full-width A4 Landscape) -->
                <div id="surplusPdfPrintableDocument" class="bg-white shadow-lg mx-auto p-3 p-md-4" style="width: 100%; max-width: 1080px; min-height: 300px; color: #1e293b; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; border-radius: 4px; box-sizing: border-box;">
                    <!-- Dynamically populated via JS -->
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-4 border-top d-flex justify-content-between align-items-center">
                <span class="text-muted small" id="surplusPdfFooterCount">Total surplus orders in report: 0</span>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary btn-sm px-3 fw-bold" id="btnPdfModalPrintBottom">
                        <i class="fa fa-print me-1"></i> Print Report
                    </button>
                    <button type="button" class="btn btn-danger btn-sm px-3 fw-bold" id="btnPdfModalDownloadBottom">
                        <i class="fa fa-download me-1"></i> Download PDF
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('../footer.php'); ?>
<script>
    window.KRF_COMPANY_LOGO = <?php echo json_encode($krfLogoBase64 ?? ''); ?>;
</script>
<script type="text/javascript" src="<?php echo $prefix; ?>krf-orderandsales/js/pjs_function.js"></script>
<!-- Module Dedicated JavaScript (Inlined to completely eliminate any browser caching) -->
<script type="text/javascript">
<?php include __DIR__ . '/js/postdispatch_polyhousestock.js'; ?>
</script>
