<?php
// index.php – Lead Management Dashboard (PHP version)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Lead Management Dashboard</title>
    <meta name="description" content="Smart, fully‑responsive lead management system dashboard" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="assets/css/style.css?v=1.1.2" />
</head>
<body>
    <?php include 'assets/php/header.php'; ?>
    <?php include 'assets/php/sidebar.php'; ?>
    
    <main class="main-content">
        <h2><i class="fa-solid fa-gauge text-primary"></i> Live Lead Statistics</h2>
        <section class="stats-cards">
            <div class="card" id="card-today">
                <h3>Leads Today</h3>
                <p>0</p>
            </div>
            <div class="card" id="card-total">
                <h3>Total Leads</h3>
                <p>0</p>
            </div>
            <div class="card" id="card-conversions">
                <h3>Conversions</h3>
                <p>0%</p>
            </div>
        </section>
        
        <section class="leads-table">
            <div class="table-header-container">
                <h2><i class="fa-solid fa-list-check text-primary"></i> Active Leads List</h2>
                <div class="table-search-wrapper">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" id="leadSearchInput" placeholder="Search by name, contact, product, state..." class="table-search-input" autocomplete="off" />
                </div>
            </div>
            <div class="leads-table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Enquiry Token</th>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>State</th>
                            <th>District</th>
                            <th>City/Village</th>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Remark</th>
                        </tr>
                    </thead>
                    <tbody id="leads-body">
                        <!-- Rows will be injected by dashboard.js -->
                    </tbody>
                </table>
            </div>
        </section>
    </main>
    <script src="assets/js/dashboard.js"></script>
</body>
</html>
