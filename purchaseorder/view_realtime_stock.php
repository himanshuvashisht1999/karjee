<?php
require_once dirname(__DIR__) . "/connection.php";
require_once __DIR__ . "/po_functions_v2.php";

if (!isset($krfconn) || !($krfconn instanceof mysqli)) {
    $krfconn = (isset($conn) && ($conn instanceof mysqli)) ? $conn : null;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Real-Time Stock Viewer | KRF Inventory</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">

    <style>
        :root {
            --primary-color: #16a34a;
            --primary-dark: #15803d;
            --secondary-color: #f8fafc;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: var(--secondary-color);
            color: #1e293b;
            font-size: 0.88rem;
            letter-spacing: -0.01em;
        }

        .navbar {
            background: linear-gradient(135deg, #14532d 0%, #16a34a 100%);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .card-header {
            background-color: #ffffff;
            border-bottom: 1px solid #edf2f7;
            padding: 1rem 1.25rem;
            border-radius: 12px 12px 0 0 !important;
        }

        /* Compact & Crisp Table Typography */
        #stockTable {
            font-size: 0.84rem;
        }

        #stockTable thead th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 700;
            font-size: 0.72rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            padding: 11px 14px;
            border-bottom: 2px solid #e2e8f0;
            white-space: nowrap;
        }

        #stockTable tbody td {
            padding: 9px 14px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
        }

        #stockTable tbody tr:hover {
            background-color: #f8fafc;
        }

        /* Mono typography for SKU and Numbers */
        .stock-sku {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.78rem;
            font-weight: 700;
            color: #334155;
            background: #f1f5f9;
            padding: 2px 7px;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
            white-space: nowrap;
            display: inline-block;
        }

        .stock-item-name {
            font-weight: 600;
            color: #0f172a;
            font-size: 0.85rem;
            line-height: 1.35;
        }

        .stock-num {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.84rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .stock-num-primary {
            color: #2563eb;
            font-weight: 700;
        }

        .stock-num-success {
            color: #059669;
            font-weight: 700;
        }

        /* Location Badges */
        .badge-all-locs {
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
            font-size: 0.74rem;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 6px;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
        }

        .badge-single-loc {
            background: #f0fdf4;
            color: #15803d;
            border: 1px solid #bbf7d0;
            font-size: 0.74rem;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 6px;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
        }

        /* Action View Details Button */
        .btn-view-details {
            background: #ffffff;
            color: #4f46e5;
            border: 1px solid #c7d2fe;
            font-weight: 600;
            font-size: 0.78rem;
            padding: 3.5px 10px;
            border-radius: 6px;
            transition: all 0.15s ease-in-out;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
        }

        .btn-view-details:hover {
            background: #4f46e5;
            color: #ffffff;
            border-color: #4338ca;
            box-shadow: 0 2px 6px rgba(79, 70, 229, 0.25);
        }

        .btn-refresh {
            background: #fff;
            border: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 0.84rem;
            font-weight: 600;
        }

        .btn-refresh:hover {
            background: #f8fafc;
            color: #0f172a;
        }

        /* Loading Spinner */
        .loader-wrapper {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.75);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            border-radius: 12px;
            display: none;
        }
    </style>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <div class="bg-white p-1 rounded-circle me-3"
                    style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-warehouse text-success"></i>
                </div>
                <span class="fw-bold tracking-tight">KRF Real-Time Stock</span>
            </a>

            <div class="ms-auto d-flex align-items-center">
                <div class="text-white small me-3 d-none d-md-block">
                    <i class="fas fa-circle text-warning me-1 small"></i> Live Updates Enabled
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4 px-4">

        <!-- Header & Top Controls -->
        <div class="row align-items-center mb-4">
            <div class="col-md-6">
                <h3 class="fw-bold mb-0">Inventory Dashboard</h3>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Home</a></li>
                        <li class="breadcrumb-item active">Real-time Stock</li>
                    </ol>
                </nav>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <button class="btn btn-refresh shadow-sm me-2" onclick="refreshData()">
                    <i class="fas fa-sync-alt me-2"></i> Refresh Data
                </button>
                <div class="btn-group shadow-sm">
                    <button class="btn btn-primary" onclick="exportData()">
                        <i class="fas fa-file-export me-2"></i> Export
                    </button>
                </div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="card mb-4">
            <div class="card-body py-3">
                <div class="row g-3 align-items-center">
                    <div class="col-md-3">
                        <label class="form-label mb-1 fw-bold small text-muted"><i class="fas fa-filter me-1"></i>
                            Location:</label>
                        <select id="locationSelector" class="form-select border-0 bg-light fw-semibold"
                            onchange="loadStockTable()">
                            <option value="">All Locations</option>
                            <?php
                            $locs = getLocations($hrmconn);
                            foreach ($locs as $l) {
                                echo "<option value='{$l['location_id']}'>{$l['location_name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label mb-1 fw-bold small text-muted"><i class="fas fa-box me-1"></i> Item
                            Name:</label>
                        <select id="itemSelector" class="form-select border-0 bg-light fw-semibold"
                            onchange="loadStockTable()">
                            <option value="">All Items</option>
                            <?php
                            if ($krfconn) {
                                $itemsRes = $krfconn->query("SELECT item_id, item_name, sku FROM inventory_purchase_item_master WHERE status='Active' ORDER BY item_name");
                                if ($itemsRes) {
                                    while ($i = $itemsRes->fetch_assoc()) {
                                        echo "<option value='{$i['item_id']}'>{$i['item_name']} ({$i['sku']})</option>";
                                    }
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label mb-1 fw-bold small text-muted"><i class="fas fa-calendar-alt me-1"></i>
                            Date From:</label>
                        <input type="date" id="dateFrom" class="form-control border-0 bg-light fw-semibold"
                            value="<?= date('Y-01-01') ?>" onchange="loadStockTable()">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label mb-1 fw-bold small text-muted"><i class="fas fa-calendar-alt me-1"></i>
                            Date To:</label>
                        <input type="date" id="dateTo" class="form-control border-0 bg-light fw-semibold"
                            value="<?= date('Y-m-d') ?>" onchange="loadStockTable()">
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-8 ms-auto"></div>
                    <!-- <div class="col-md-4 ms-auto">
                        <label class="form-label mb-1 fw-bold small text-muted d-block">&nbsp;</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-transparent border-0"><i
                                    class="fas fa-search text-muted"></i></span>
                            <input type="text" id="customSearch" class="form-control border-0 bg-transparent"
                                placeholder="Quick search table...">
                        </div>
                    </div> -->
                </div>
            </div>
        </div>

        <!-- Stock Table -->
        <div class="card position-relative">
            <div class="loader-wrapper" id="tableLoader">
                <div class="spinner-border text-success" role="status"></div>
            </div>

            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold"><i class="fas fa-table me-2 text-primary"></i> Current Stock Levels <br/>&nbsp;&nbsp;&nbsp;&nbsp;<span id="rowCount" class="badge bg-light text-dark border">0 Items Found</span></h5>
                <div class="ms-auto border">
                    <label class="form-label mb-1 fw-bold small text-muted d-block">&nbsp;</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-transparent border-0"><i
                                class="fas fa-search text-muted"></i></span>
                        <input type="text" id="customSearch" class="form-control border-0 bg-transparent"
                            placeholder="Quick search table...">
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="stockTable" class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Item Name</th>
                                <th>Location</th>
                                <th class="text-end">Total Purchase</th>
                                <th class="text-end">Total Used</th>
                                <th class="text-end">Stock Quantity</th>
                                <th class="text-end">Balance Quantity</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="stockTableBody">
                            <!-- Data populated via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- Summary Footer -->
    <div class="mt-4 row g-3">
        <div class="col-md-3">
            <div class="card p-3 bg-white text-center shadow-sm border">
                <div class="text-muted small fw-bold mb-1"><i class="fas fa-barcode me-1 text-primary"></i> TOTAL SKU COUNT</div>
                <h4 class="mb-0 fw-bold text-dark" id="totalSkuCount">0</h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 bg-white text-center shadow-sm border">
                <div class="text-muted small fw-bold mb-1"><i class="fas fa-shopping-cart me-1 text-info"></i> TOTAL PURCHASES</div>
                <h4 class="mb-0 fw-bold text-info" id="totalPurchaseCount">0.00</h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 bg-white text-center shadow-sm border">
                <div class="text-muted small fw-bold mb-1"><i class="fas fa-boxes me-1 text-primary"></i> STOCK IN-HAND</div>
                <h4 class="mb-0 fw-bold text-primary" id="totalStockOnHand">0.00</h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 bg-white text-center shadow-sm border">
                <div class="text-muted small fw-bold mb-1"><i class="fas fa-balance-scale me-1 text-success"></i> TOTAL BALANCE QTY</div>
                <h4 class="mb-0 fw-bold text-success" id="totalBalanceCount">0.00</h4>
            </div>
        </div>
    </div>

    </div>

    <!-- Location-Wise Stock Details & Ledger Modal -->
    <div class="modal fade" id="itemStockDetailModal" tabindex="-1" aria-labelledby="itemStockDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content shadow-lg border-0 rounded-4 overflow-hidden">
                <div class="modal-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 42px; height: 42px; background: #e8f5e9; color: #2e7d32; flex-shrink: 0;">
                            <i class="fas fa-cubes" style="font-size: 1.25rem;"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold mb-0 text-dark" id="itemStockDetailModalLabel">
                                Location-Wise Real-Time Stock Details
                            </h5>
                            <div class="small text-muted" id="itemStockSubHeader">Loading stock data...</div>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4 bg-light">
                    <!-- Compact 1-Liner KPI Summary Cards -->
                    <div class="row g-2 mb-3" id="modalKpiRow">
                        <div class="col-6 col-md-3">
                            <div class="card p-2 bg-white border border-start border-3 border-primary shadow-sm rounded-3">
                                <div class="text-muted text-uppercase fw-bold" style="font-size: 0.68rem; letter-spacing: 0.04em;">Total Purchase</div>
                                <div class="fw-bold text-dark font-monospace fs-6 mt-0.5" id="mTotalPurchase">0.00</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="card p-2 bg-white border border-start border-3 border-danger shadow-sm rounded-3">
                                <div class="text-muted text-uppercase fw-bold" style="font-size: 0.68rem; letter-spacing: 0.04em;">Total Used</div>
                                <div class="fw-bold text-danger font-monospace fs-6 mt-0.5" id="mTotalUsed">0.00</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="card p-2 bg-white border border-start border-3 border-primary shadow-sm rounded-3">
                                <div class="text-muted text-uppercase fw-bold" style="font-size: 0.68rem; letter-spacing: 0.04em;">Current Stock</div>
                                <div class="fw-bold text-primary font-monospace fs-6 mt-0.5" id="mCurrentStock">0.00</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="card p-2 bg-white border border-start border-3 border-success shadow-sm rounded-3">
                                <div class="text-muted text-uppercase fw-bold" style="font-size: 0.68rem; letter-spacing: 0.04em;">Balance Qty</div>
                                <div class="fw-bold text-success font-monospace fs-6 mt-0.5" id="mBalanceQty">0.00</div>
                            </div>
                        </div>
                    </div>

                    <!-- Location-wise Breakdown Table -->
                    <div class="card bg-white border shadow-2xs rounded-3 mb-4 overflow-hidden">
                        <div class="card-header bg-white border-bottom py-2.5 px-3 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-map-marked-alt text-primary me-2"></i> Location-Wise Real-Time Stock Breakdown</h6>
                            <span class="badge bg-light text-dark border" id="mLocationCountBadge">0 Locations</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered mb-0 align-middle">
                                <thead class="table-light text-muted small text-uppercase" style="font-size: 0.72rem;">
                                    <tr>
                                        <th class="ps-3 py-2.5">Location</th>
                                        <th class="text-end py-2.5">Total Purchase</th>
                                        <th class="text-end py-2.5">Total Used</th>
                                        <th class="text-end py-2.5">Real-Time Stock</th>
                                        <th class="text-end py-2.5">Balance Quantity</th>
                                        <th class="py-2.5 text-center">Last Updated</th>
                                    </tr>
                                </thead>
                                <tbody id="modalLocationTableBody">
                                    <!-- Populated via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Recent Stock Transaction Logs -->
                    <div class="card bg-white border shadow-2xs rounded-3 overflow-hidden">
                        <div class="card-header bg-white border-bottom py-2.5 px-3 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-history text-secondary me-2"></i> Recent Stock Transactions / Movement Log</h6>
                            <small class="text-muted">Last 50 movements</small>
                        </div>
                        <div class="table-responsive" style="max-height: 280px;">
                            <table class="table table-sm table-hover mb-0 align-middle">
                                <thead class="table-light text-muted small text-uppercase sticky-top" style="font-size: 0.72rem;">
                                    <tr>
                                        <th class="ps-3 py-2">Date & Time</th>
                                        <th class="py-2">Type</th>
                                        <th class="py-2">Location</th>
                                        <th class="py-2 text-end">Qty Change</th>
                                        <th class="py-2">Reference</th>
                                        <th class="pe-3 py-2">Notes</th>
                                    </tr>
                                </thead>
                                <tbody id="modalTransactionTableBody">
                                    <!-- Populated via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-white border-top py-2.5 px-4 d-flex justify-content-end">
                    <button type="button" class="btn btn-secondary px-4 fw-semibold" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

    <script>
        let stockTable;

        $(document).ready(function () {
            loadStockTable();

            // Custom search binding
            $('#customSearch').keyup(function () {
                if (stockTable) {
                    stockTable.search($(this).val()).draw();
                }
            });
        });

        function loadStockTable() {
            const locId = $('#locationSelector').val();
            const itemId = $('#itemSelector').val();
            const dateFrom = $('#dateFrom').val();
            const dateTo = $('#dateTo').val();
            $('#tableLoader').fadeIn(100);
            $.ajax({
                url: 'ajax_po.php',
                type: 'POST',
                data: {
                    action: 'list_inventory',
                    location_id: locId,
                    item_id: itemId,
                    date_from: dateFrom,
                    date_to: dateTo,
                    is_realtime: 1
                },
                dataType: 'json',
                success: function (response) {
                    $('#tableLoader').fadeOut(200);

                    if (!response || typeof response.rows === 'undefined') {
                        $('#stockTableBody').html("<tr><td colspan='8' class='text-center py-4 text-danger'>Data structure error: No rows found in response.</td></tr>");
                        console.error('AJAX Error: Invalid response structure', response);
                        return;
                    }

                    if ($.fn.DataTable.isDataTable('#stockTable')) {
                        stockTable.destroy();
                    }

                    $('#stockTableBody').html(response.rows);

                    // Initialize DataTable
                    stockTable = $('#stockTable').DataTable({
                        pageLength: 25,
                        dom: 'rtip',
                        language: {
                            emptyTable: "<div class='py-4'><i class='fas fa-box-open fa-3x text-muted mb-3'></i><p>No stock records found for this selection.</p></div>"
                        },
                        columnDefs: [
                            { className: "fw-bold", targets: [0, 5, 6] },
                            { className: "text-end", targets: [3, 4, 5, 6] },
                            { className: "text-center", targets: [7] }
                        ],
                        drawCallback: function () {
                            const info = this.api().page.info();
                            $('#rowCount').text(`${info.recordsTotal} Items Found`);
                            updateSummaries();
                        }
                    });
                },
                error: function () {
                    $('#tableLoader').fadeOut(100);
                    alert('Failed to load inventory data.');
                }
            });
        }

        function updateSummaries() {
            if (!stockTable) return;
            const rows = stockTable.rows({ search: 'applied' }).nodes();
            $('#totalSkuCount').text(rows.length);

            let totalPurch = 0;
            let totalStock = 0;
            let totalBal = 0;

            $(rows).each(function () {
                const purch = parseFloat($(this).find('td:eq(3)').text().replace(/,/g, '')) || 0;
                const stock = parseFloat($(this).find('td:eq(5)').text().replace(/,/g, '')) || 0;
                const bal = parseFloat($(this).find('td:eq(6)').text().replace(/,/g, '')) || 0;
                totalPurch += purch;
                totalStock += stock;
                totalBal += bal;
            });

            $('#totalPurchaseCount').text(totalPurch.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            $('#totalStockOnHand').text(totalStock.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            $('#totalBalanceCount').text(totalBal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        }

        function refreshData() {
            loadStockTable();
        }

        function exportData() {
            const table = document.getElementById("stockTable");
            if (!table) return;
            let csv = [];
            for (let i = 0; i < table.rows.length; i++) {
                let row = [], cols = table.rows[i].querySelectorAll("td, th");
                for (let j = 0; j < cols.length - 1; j++) { // Exclude action column
                    let text = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, "").replace(/(\s\s+)/gm, " ");
                    text = text.replace(/"/g, '""');
                    row.push('"' + text + '"');
                }
                csv.push(row.join(","));
            }
            let csvFile = new Blob([csv.join("\n")], { type: "text/csv" });
            let downloadLink = document.createElement("a");
            downloadLink.download = "Realtime_Stock_Report_" + new Date().toISOString().slice(0,10) + ".csv";
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = "none";
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }

        // View Location-wise breakdown & Real-Time Stock Balance for Item
        function viewItemLocationBreakdown(seedId, itemName, sku, targetLocId) {
            $('#itemStockDetailModalLabel').html(`
                <span class="text-primary fw-bold">${itemName}</span>
                <span class="badge bg-light text-dark border ms-2 font-monospace" style="font-size:0.82rem;">${sku || 'SKU'}</span>
            `);
            $('#itemStockSubHeader').text('Loading location-wise stock data and history...');

            $('#mTotalPurchase, #mTotalUsed, #mCurrentStock, #mBalanceQty').text('0.00');
            $('#modalLocationTableBody').html('<tr><td colspan="6" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Loading location stock...</td></tr>');
            $('#modalTransactionTableBody').html('<tr><td colspan="6" class="text-center py-4"><div class="spinner-border spinner-border-sm text-secondary me-2"></div> Loading transaction history...</td></tr>');

            $('#itemStockDetailModal').modal('show');

            const dateFrom = $('#dateFrom').val();
            const dateTo = $('#dateTo').val();

            $.ajax({
                url: 'ajax_po.php',
                type: 'POST',
                data: {
                    action: 'get_item_location_breakdown',
                    seed_id: seedId,
                    date_from: dateFrom,
                    date_to: dateTo
                },
                dataType: 'json',
                success: function (res) {
                    if (!res || res.status !== 'success') {
                        $('#modalLocationTableBody').html('<tr><td colspan="6" class="text-center text-danger py-4">Failed to load location breakdown.</td></tr>');
                        return;
                    }

                    const summary = res.summary || {};
                    const locations = res.locations || [];
                    const transactions = res.transactions || [];

                    // KPI Cards
                    $('#mTotalPurchase').text((summary.total_purchase || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                    $('#mTotalUsed').text((summary.total_used || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                    $('#mCurrentStock').text((summary.stock_quantity || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                    $('#mBalanceQty').text((summary.balance_quantity || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

                    $('#itemStockSubHeader').html(`
                        <strong>Total Locations with Stock:</strong> ${locations.length} | 
                        <strong>Total Balance Stock:</strong> <span class="text-success fw-bold">${(summary.balance_quantity || 0).toFixed(2)}</span>
                    `);
                    $('#mLocationCountBadge').text(`${locations.length} Location(s)`);

                    // Location Table
                    if (!locations.length) {
                        $('#modalLocationTableBody').html('<tr><td colspan="6" class="text-center text-muted py-4">No location stock records found.</td></tr>');
                    } else {
                        let locHtml = '';
                        locations.forEach(loc => {
                            const isTarget = targetLocId && loc.location_id == targetLocId;
                            const purch = (parseFloat(loc.total_purchase) || 0).toFixed(2);
                            const used = (parseFloat(loc.total_used) || 0).toFixed(2);
                            const stock = (parseFloat(loc.stock_quantity) || 0).toFixed(2);
                            const bal = (parseFloat(loc.balance_quantity) || 0).toFixed(2);
                            const updated = loc.last_updated ? loc.last_updated.substring(0, 16) : '-';

                            locHtml += `
                                <tr class="${isTarget ? 'table-warning' : ''}">
                                    <td class="ps-3 fw-bold">
                                        <span class="badge bg-info text-dark px-2.5 py-1.5" style="font-size:0.78rem;">
                                            <i class="fas fa-map-marker-alt me-1"></i> ${loc.location_name}
                                        </span>
                                        ${isTarget ? '<span class="badge bg-warning text-dark ms-1">Selected</span>' : ''}
                                    </td>
                                    <td class="text-end font-monospace">${purch}</td>
                                    <td class="text-end font-monospace text-danger">${used}</td>
                                    <td class="text-end font-monospace text-primary fw-bold">${stock}</td>
                                    <td class="text-end font-monospace text-success fw-bold">${bal}</td>
                                    <td class="text-center small text-muted">${updated}</td>
                                </tr>
                            `;
                        });
                        $('#modalLocationTableBody').html(locHtml);
                    }

                    // Transactions Log
                    if (!transactions.length) {
                        $('#modalTransactionTableBody').html('<tr><td colspan="6" class="text-center text-muted py-4">No recent stock movement logs found.</td></tr>');
                    } else {
                        let txHtml = '';
                        transactions.forEach(t => {
                            const qty = parseFloat(t.qty_change) || 0;
                            const isPositive = qty >= 0;
                            const typeBadge = isPositive 
                                ? `<span class="badge bg-success-subtle text-success border border-success px-2 py-0.5" style="font-size:0.72rem;"><i class="fas fa-arrow-down me-1"></i>${t.trans_type || 'IN'}</span>`
                                : `<span class="badge bg-danger-subtle text-danger border border-danger px-2 py-0.5" style="font-size:0.72rem;"><i class="fas fa-arrow-up me-1"></i>${t.trans_type || 'OUT'}</span>`;

                            txHtml += `
                                <tr>
                                    <td class="ps-3 small font-monospace">${t.created_at || '-'}</td>
                                    <td>${typeBadge}</td>
                                    <td><span class="badge bg-light text-dark border">${t.location_name}</span></td>
                                    <td class="text-end font-monospace fw-bold ${isPositive ? 'text-success' : 'text-danger'}">
                                        ${isPositive ? '+' : ''}${qty.toFixed(2)}
                                    </td>
                                    <td class="small text-muted">${t.reference_type || ''} #${t.reference_id || ''}</td>
                                    <td class="pe-3 small text-muted">${t.notes || '-'}</td>
                                </tr>
                            `;
                        });
                        $('#modalTransactionTableBody').html(txHtml);
                    }
                },
                error: function () {
                    $('#modalLocationTableBody').html('<tr><td colspan="6" class="text-center text-danger py-4">Failed to load item breakdown.</td></tr>');
                }
            });
        }
    </script>

</body>

</html>