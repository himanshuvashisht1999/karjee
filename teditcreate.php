<?php
/**
 * Script: teditcreate.php
 * Path: http://localhost/krf_amanaccounting/teditcreate.php
 * Purpose: Iterates through every table in the specified database and adds
 *          the specified columns if they do not already exist.
 */

// 1. CONFIGURATION
$databasename = 'karjeefarm';
$addcolumns   = ['tedit', 'teditremark'];

// Column SQL definitions
$columnDefinitions = [
    'tedit'       => 'VARCHAR(255) NULL DEFAULT NULL',
    'teditremark' => 'VARCHAR(500) NULL DEFAULT NULL'
];
$defaultDefinition = 'VARCHAR(255) NULL DEFAULT NULL';

// Allow database override via GET parameter if needed, defaulting to $databasename
if (isset($_GET['databasename']) && !empty(trim($_GET['databasename']))) {
    $databasename = trim($_GET['databasename']);
}

// 2. DATABASE CONNECTION
require_once __DIR__ . '/connection.php';

$conn = null;
if (isset($config) && is_array($config)) {
    $dbUser = !empty($config['username']) ? $config['username'] : (!empty($config['dbname_krf']) ? $config['dbname_krf'] : 'root');
    $dbPass = isset($config['password']) ? $config['password'] : '';
    $dbHost = !empty($config['servername']) ? $config['servername'] : '127.0.0.1';
    
    if (function_exists('createConnection')) {
        $conn = createConnection($dbHost, $dbUser, $dbPass, $databasename);
    } else {
        $conn = @mysqli_connect($dbHost, $dbUser, $dbPass, $databasename);
    }
}

if (!$conn || !($conn instanceof mysqli)) {
    // Fallback standard connection
    $conn = @mysqli_connect('127.0.0.1', 'root', '', $databasename);
    if (!$conn) {
        $conn = @mysqli_connect('localhost', 'root', '', $databasename);
    }
}

if (!$conn || mysqli_connect_errno()) {
    $errorMsg = "Database Connection to '{$databasename}' failed: " . mysqli_connect_error();
    if (php_sapi_name() === 'cli') {
        fwrite(STDERR, $errorMsg . PHP_EOL);
        exit(1);
    } else {
        die("<!DOCTYPE html><html><body style='font-family:sans-serif;padding:30px;background:#fff5f5;color:#c53030;'><h2>Database Connection Error</h2><p>{$errorMsg}</p></body></html>");
    }
}

// 3. RETRIEVE ALL BASE TABLES FOR THE DATABASE
$tablesQuery = "SELECT TABLE_NAME 
                FROM INFORMATION_SCHEMA.TABLES 
                WHERE TABLE_SCHEMA = '" . mysqli_real_escape_string($conn, $databasename) . "' 
                  AND TABLE_TYPE = 'BASE TABLE' 
                ORDER BY TABLE_NAME ASC";

$tablesResult = mysqli_query($conn, $tablesQuery);
if (!$tablesResult) {
    die("Failed to fetch tables: " . mysqli_error($conn));
}

$allTables = [];
while ($row = mysqli_fetch_assoc($tablesResult)) {
    $allTables[] = $row['TABLE_NAME'];
}

// Fetch all existing columns in one batch for maximum speed and efficiency
$existingColumnsMap = [];
$colsQuery = "SELECT TABLE_NAME, COLUMN_NAME 
              FROM INFORMATION_SCHEMA.COLUMNS 
              WHERE TABLE_SCHEMA = '" . mysqli_real_escape_string($conn, $databasename) . "'";
$colsResult = mysqli_query($conn, $colsQuery);
if ($colsResult) {
    while ($colRow = mysqli_fetch_assoc($colsResult)) {
        $tbl = strtolower($colRow['TABLE_NAME']);
        $col = strtolower($colRow['COLUMN_NAME']);
        if (!isset($existingColumnsMap[$tbl])) {
            $existingColumnsMap[$tbl] = [];
        }
        $existingColumnsMap[$tbl][$col] = true;
    }
}

// 4. PROCESS EACH TABLE AND EACH TARGET COLUMN
$results = [];
$totalTablesScanned = count($allTables);
$totalColumnsAdded = 0;
$totalColumnsSkipped = 0;
$totalErrors = 0;

foreach ($allTables as $table) {
    $tblLower = strtolower($table);
    $tableHasChanges = false;

    foreach ($addcolumns as $column) {
        $colLower = strtolower($column);
        $colDefinition = isset($columnDefinitions[$colLower]) ? $columnDefinitions[$colLower] : $defaultDefinition;

        // Check if column already exists
        $alreadyExists = isset($existingColumnsMap[$tblLower][$colLower]);

        if ($alreadyExists) {
            $totalColumnsSkipped++;
            $results[] = [
                'table'   => $table,
                'column'  => $column,
                'status'  => 'SKIPPED',
                'message' => 'Column already exists. Skipped.',
                'query'   => null
            ];
        } else {
            // Add column to table
            $alterSql = "ALTER TABLE `{$table}` ADD `{$column}` {$colDefinition}";
            if (mysqli_query($conn, $alterSql)) {
                $totalColumnsAdded++;
                $tableHasChanges = true;
                $results[] = [
                    'table'   => $table,
                    'column'  => $column,
                    'status'  => 'ADDED',
                    'message' => 'Column added successfully.',
                    'query'   => $alterSql
                ];
                // Record in cache so future checks in same run are aware
                $existingColumnsMap[$tblLower][$colLower] = true;
            } else {
                $totalErrors++;
                $err = mysqli_error($conn);
                $results[] = [
                    'table'   => $table,
                    'column'  => $column,
                    'status'  => 'ERROR',
                    'message' => 'Error adding column: ' . $err,
                    'query'   => $alterSql
                ];
            }
        }
    }
}

// CLI OUTPUT
if (php_sapi_name() === 'cli') {
    echo "========================================================\n";
    echo " DATABASE COLUMN SYNC REPORT\n";
    echo " Database: {$databasename}\n";
    echo " Columns:  " . implode(', ', $addcolumns) . "\n";
    echo "========================================================\n";
    echo " Total Tables Scanned : {$totalTablesScanned}\n";
    echo " Columns Added        : {$totalColumnsAdded}\n";
    echo " Columns Skipped      : {$totalColumnsSkipped}\n";
    echo " Errors               : {$totalErrors}\n";
    echo "========================================================\n\n";

    if ($totalColumnsAdded > 0) {
        echo "[+] ADDED COLUMNS:\n";
        foreach ($results as $res) {
            if ($res['status'] === 'ADDED') {
                echo " - Table `{$res['table']}` -> Added column `{$res['column']}`\n";
            }
        }
        echo "\n";
    }

    if ($totalErrors > 0) {
        echo "[!] ERRORS:\n";
        foreach ($results as $res) {
            if ($res['status'] === 'ERROR') {
                echo " - Table `{$res['table']}` -> Error on `{$res['column']}`: {$res['message']}\n";
            }
        }
        echo "\n";
    }

    echo "Finished successfully.\n";
    exit(0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Column Manager - <?php echo htmlspecialchars($databasename); ?></title>
    <!-- Fonts & Bootstrap 4 -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #3b82f6;
            --primary-dark: #1d4ed8;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            --card-shadow-hover: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.04);
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: #f0f2f5;
            color: var(--gray-800);
            line-height: 1.5;
            padding: 24px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-radius: 16px;
            padding: 28px 32px;
            color: #ffffff;
            margin-bottom: 24px;
            box-shadow: var(--card-shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }
        .header-title h1 {
            font-size: 24px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .header-title p {
            color: #94a3b8;
            font-size: 14px;
            margin-top: 4px;
        }
        .header-badges {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .badge-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 9999px;
            font-size: 13px;
            font-weight: 600;
            background: rgba(255, 255, 255, 0.12);
            color: #e2e8f0;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .btn-reload {
            background: #3b82f6;
            color: #fff;
            border: none;
            padding: 10px 18px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        .btn-reload:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--card-shadow);
            display: flex;
            align-items: center;
            gap: 16px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--card-shadow-hover);
        }
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        .stat-icon.tables { background: #eff6ff; color: #3b82f6; }
        .stat-icon.added { background: #ecfdf5; color: #10b981; }
        .stat-icon.skipped { background: #f3f4f6; color: #6b7280; }
        .stat-icon.errors { background: #fef2f2; color: #ef4444; }
        .stat-info .num {
            font-size: 24px;
            font-weight: 700;
            color: var(--gray-900);
        }
        .stat-info .label {
            font-size: 13px;
            color: var(--gray-600);
            font-weight: 500;
        }
        .content-card {
            background: #ffffff;
            border-radius: 14px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }
        .card-toolbar {
            padding: 16px 24px;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            background: #fafafa;
        }
        .filter-tabs {
            display: flex;
            gap: 8px;
        }
        .tab-btn {
            background: transparent;
            border: 1px solid var(--gray-300);
            border-radius: 6px;
            padding: 6px 14px;
            font-size: 13px;
            font-weight: 600;
            color: var(--gray-700);
            cursor: pointer;
            transition: all 0.2s;
        }
        .tab-btn.active, .tab-btn:hover {
            background: #1e293b;
            color: #ffffff;
            border-color: #1e293b;
        }
        .search-box {
            position: relative;
            min-width: 260px;
        }
        .search-box input {
            width: 100%;
            padding: 8px 12px 8px 36px;
            border-radius: 6px;
            border: 1px solid var(--gray-300);
            font-size: 13px;
            outline: none;
            transition: border-color 0.2s;
        }
        .search-box input:focus {
            border-color: var(--primary);
        }
        .search-box i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 14px;
        }
        .table-responsive {
            overflow-x: auto;
            max-height: 650px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            text-align: left;
        }
        thead {
            position: sticky;
            top: 0;
            background: #f8fafc;
            z-index: 10;
        }
        thead th {
            padding: 12px 18px;
            font-weight: 600;
            color: var(--gray-700);
            border-bottom: 1px solid var(--gray-200);
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.05em;
        }
        tbody tr {
            border-bottom: 1px solid var(--gray-100);
            transition: background 0.15s;
        }
        tbody tr:hover {
            background: #f8fafc;
        }
        tbody td {
            padding: 12px 18px;
            color: var(--gray-800);
            vertical-align: middle;
        }
        .tag-status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }
        .tag-status.added {
            background: #dcfce7;
            color: #15803d;
        }
        .tag-status.skipped {
            background: #f1f5f9;
            color: #475569;
        }
        .tag-status.error {
            background: #fee2e2;
            color: #b91c1c;
        }
        .table-name {
            font-weight: 600;
            color: #0f172a;
            font-family: monospace;
            font-size: 13px;
        }
        .col-name {
            background: #e2e8f0;
            padding: 2px 8px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
            font-weight: 600;
            color: #334155;
        }
        .query-code {
            font-family: monospace;
            font-size: 11px;
            color: #64748b;
            max-width: 380px;
            word-break: break-all;
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Header -->
    <div class="header">
        <div class="header-title">
            <h1><i class="fa-solid fa-database"></i> Database Column Manager</h1>
            <p>Target Database: <strong><?php echo htmlspecialchars($databasename); ?></strong> | Columns: 
               <?php foreach ($addcolumns as $c): ?>
                   <span class="col-name"><?php echo htmlspecialchars($c); ?></span>
               <?php endforeach; ?>
            </p>
        </div>
        <div class="header-badges">
            <span class="badge-pill"><i class="fa-solid fa-server"></i> <?php echo htmlspecialchars(isset($dbHost) ? $dbHost : '127.0.0.1'); ?></span>
            <a href="teditcreate.php?databasename=<?php echo urlencode($databasename); ?>" class="btn-reload">
                <i class="fa-solid fa-rotate-right"></i> Run / Re-Check
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon tables"><i class="fa-solid fa-table-list"></i></div>
            <div class="stat-info">
                <div class="num"><?php echo $totalTablesScanned; ?></div>
                <div class="label">Total Tables Checked</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon added"><i class="fa-solid fa-circle-check"></i></div>
            <div class="stat-info">
                <div class="num"><?php echo $totalColumnsAdded; ?></div>
                <div class="label">Columns Added</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon skipped"><i class="fa-solid fa-forward-step"></i></div>
            <div class="stat-info">
                <div class="num"><?php echo $totalColumnsSkipped; ?></div>
                <div class="label">Already Existed (Skipped)</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon errors"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div class="stat-info">
                <div class="num"><?php echo $totalErrors; ?></div>
                <div class="label">Errors Encountered</div>
            </div>
        </div>
    </div>

    <!-- Results Table Card -->
    <div class="content-card">
        <div class="card-toolbar">
            <div class="filter-tabs">
                <button class="tab-btn active" onclick="filterStatus('ALL')">All (<span id="count-all"><?php echo count($results); ?></span>)</button>
                <button class="tab-btn" onclick="filterStatus('ADDED')">Added (<span id="count-added"><?php echo $totalColumnsAdded; ?></span>)</button>
                <button class="tab-btn" onclick="filterStatus('SKIPPED')">Skipped (<span id="count-skipped"><?php echo $totalColumnsSkipped; ?></span>)</button>
                <?php if ($totalErrors > 0): ?>
                <button class="tab-btn" onclick="filterStatus('ERROR')">Errors (<span id="count-error"><?php echo $totalErrors; ?></span>)</button>
                <?php endif; ?>
            </div>
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="Search by table or column name...">
            </div>
        </div>

        <div class="table-responsive">
            <table id="resultsTable">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Table Name</th>
                        <th>Column</th>
                        <th>Status</th>
                        <th>Details / Result</th>
                        <th>SQL Executed</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($results)): ?>
                        <tr><td colspan="6" style="text-align: center; padding: 24px; color: #94a3b8;">No tables found in database.</td></tr>
                    <?php else: ?>
                        <?php foreach ($results as $index => $row): ?>
                            <tr class="row-item" data-status="<?php echo $row['status']; ?>" data-search="<?php echo htmlspecialchars(strtolower($row['table'] . ' ' . $row['column'] . ' ' . $row['status'])); ?>">
                                <td><?php echo $index + 1; ?></td>
                                <td class="table-name"><?php echo htmlspecialchars($row['table']); ?></td>
                                <td><span class="col-name"><?php echo htmlspecialchars($row['column']); ?></span></td>
                                <td>
                                    <?php if ($row['status'] === 'ADDED'): ?>
                                        <span class="tag-status added"><i class="fa-solid fa-plus-circle"></i> Added</span>
                                    <?php elseif ($row['status'] === 'SKIPPED'): ?>
                                        <span class="tag-status skipped"><i class="fa-solid fa-check-double"></i> Exists (Skipped)</span>
                                    <?php else: ?>
                                        <span class="tag-status error"><i class="fa-solid fa-circle-exclamation"></i> Error</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['message']); ?></td>
                                <td class="query-code"><?php echo $row['query'] ? htmlspecialchars($row['query']) : '<span style="color:#cbd5e1;">-</span>'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    let currentFilter = 'ALL';

    function filterStatus(status) {
        currentFilter = status;
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        if (event && event.target) {
            event.target.classList.add('active');
        }
        filterTable();
    }

    function filterTable() {
        const query = document.getElementById('searchInput').value.toLowerCase().trim();
        const rows = document.querySelectorAll('.row-item');

        rows.forEach(row => {
            const status = row.getAttribute('data-status');
            const searchData = row.getAttribute('data-search');

            const matchesStatus = (currentFilter === 'ALL' || status === currentFilter);
            const matchesQuery = !query || searchData.includes(query);

            if (matchesStatus && matchesQuery) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
</script>

</body>
</html>
