<?php
/**
 * Open Sale Stock Functions
 * Independent helper functions for Open Sale Stock Review and Reporting.
 * Completely self-contained to ensure zero impact on existing modules.
 */

if (!function_exists('getOpenSaleKPIs')) {
    function getOpenSaleKPIs($krfconn, $projectid = '', $filters = []) {
        $where = ["1=1"];
        if (!empty($filters['location'])) {
            $lEsc = $krfconn->real_escape_string($filters['location']);
            $where[] = "(s.open_sale_location = '{$lEsc}' OR s.opensale_locationid = '{$lEsc}' OR s.open_sale_location LIKE '%{$lEsc}%' OR '{$lEsc}' LIKE CONCAT('%', s.open_sale_location, '%'))";
        }
        if (!empty($filters['productid'])) {
            $pEsc = $krfconn->real_escape_string($filters['productid']);
            $where[] = "s.productid = '{$pEsc}'";
        }
        if (!empty($filters['date_from'])) {
            $dfEsc = $krfconn->real_escape_string($filters['date_from']);
            $where[] = "DATE(s.created_at) >= '{$dfEsc}'";
        }
        if (!empty($filters['date_to'])) {
            $dtEsc = $krfconn->real_escape_string($filters['date_to']);
            $where[] = "DATE(s.created_at) <= '{$dtEsc}'";
        }

        $baseWhere = implode(" AND ", $where);

        // Active OpenSale Available Stats
        $kpiSql = "
            SELECT 
                COUNT(CASE WHEN s.status = 'OpenSale' THEN 1 END) AS active_trays,
                COALESCE(SUM(CASE WHEN s.status = 'OpenSale' THEN s.actual_plant_count ELSE 0 END), 0) AS active_plants,
                COUNT(DISTINCT CASE WHEN s.status = 'OpenSale' THEN s.productid END) AS active_products,
                COUNT(CASE WHEN s.status = 'Sold' THEN 1 END) AS sold_trays,
                COALESCE(SUM(s.sold_qty), 0) AS sold_plants,
                
                -- Location-wise Available Plants & Trays
                COUNT(CASE WHEN s.status = 'OpenSale' AND (s.open_sale_location = 'Karjee' OR s.opensale_locationid = 'LOC6905e0fd5ab05') THEN 1 END) AS karjee_trays,
                COALESCE(SUM(CASE WHEN s.status = 'OpenSale' AND (s.open_sale_location = 'Karjee' OR s.opensale_locationid = 'LOC6905e0fd5ab05') THEN s.actual_plant_count ELSE 0 END), 0) AS karjee_plants,
                
                COUNT(CASE WHEN s.status = 'OpenSale' AND (s.open_sale_location = 'Sargawa' OR s.opensale_locationid = 'LOC696b280faf104') THEN 1 END) AS sargawa_trays,
                COALESCE(SUM(CASE WHEN s.status = 'OpenSale' AND (s.open_sale_location = 'Sargawa' OR s.opensale_locationid = 'LOC696b280faf104') THEN s.actual_plant_count ELSE 0 END), 0) AS sargawa_plants,
                
                COUNT(CASE WHEN s.status = 'OpenSale' AND (s.open_sale_location = 'Mansoon' OR s.opensale_locationid = 'LOC6a4f396f08173') THEN 1 END) AS mansoon_trays,
                COALESCE(SUM(CASE WHEN s.status = 'OpenSale' AND (s.open_sale_location = 'Mansoon' OR s.opensale_locationid = 'LOC6a4f396f08173') THEN s.actual_plant_count ELSE 0 END), 0) AS mansoon_plants
            FROM krf_opensale_stocks s
            WHERE {$baseWhere}
        ";

        $res = $krfconn->query($kpiSql);
        $kpi = [
            'active_trays'    => 0,
            'active_plants'   => 0,
            'active_products' => 0,
            'sold_trays'      => 0,
            'sold_plants'     => 0,
            'karjee_trays'    => 0,
            'karjee_plants'   => 0,
            'sargawa_trays'   => 0,
            'sargawa_plants'  => 0,
            'mansoon_trays'   => 0,
            'mansoon_plants'  => 0
        ];

        if ($res && ($r = $res->fetch_assoc())) {
            $kpi = [
                'active_trays'    => (int)$r['active_trays'],
                'active_plants'   => (int)$r['active_plants'],
                'active_products' => (int)$r['active_products'],
                'sold_trays'      => (int)$r['sold_trays'],
                'sold_plants'     => (int)$r['sold_plants'],
                'karjee_trays'    => (int)$r['karjee_trays'],
                'karjee_plants'   => (int)$r['karjee_plants'],
                'sargawa_trays'   => (int)$r['sargawa_trays'],
                'sargawa_plants'  => (int)$r['sargawa_plants'],
                'mansoon_trays'   => (int)$r['mansoon_trays'],
                'mansoon_plants'  => (int)$r['mansoon_plants']
            ];
        }
        return $kpi;
    }
}

if (!function_exists('getOpenSaleGroupedSummary')) {
    function getOpenSaleGroupedSummary($krfconn, $projectid = '', $filters = []) {
        $where = ["1=1"];
        
        // Status filter (default OpenSale)
        $st = !empty($filters['status']) ? $filters['status'] : 'OpenSale';
        if ($st !== 'All') {
            $stEsc = $krfconn->real_escape_string($st);
            $where[] = "s.status = '{$stEsc}'";
        }

        if (!empty($filters['location'])) {
            $lEsc = $krfconn->real_escape_string($filters['location']);
            $where[] = "(s.open_sale_location = '{$lEsc}' OR s.opensale_locationid = '{$lEsc}' OR s.open_sale_location LIKE '%{$lEsc}%' OR '{$lEsc}' LIKE CONCAT('%', s.open_sale_location, '%'))";
        }
        if (!empty($filters['productid'])) {
            $pEsc = $krfconn->real_escape_string($filters['productid']);
            $where[] = "s.productid = '{$pEsc}'";
        }
        if (!empty($filters['search'])) {
            $sEsc = $krfconn->real_escape_string(trim($filters['search']));
            $where[] = "(s.productname LIKE '%{$sEsc}%' OR s.variety LIKE '%{$sEsc}%' OR s.barcode LIKE '%{$sEsc}%' OR s.stockorder_no LIKE '%{$sEsc}%')";
        }
        if (!empty($filters['date_from'])) {
            $dfEsc = $krfconn->real_escape_string($filters['date_from']);
            $where[] = "DATE(s.created_at) >= '{$dfEsc}'";
        }
        if (!empty($filters['date_to'])) {
            $dtEsc = $krfconn->real_escape_string($filters['date_to']);
            $where[] = "DATE(s.created_at) <= '{$dtEsc}'";
        }

        $whereSql = implode(" AND ", $where);

        $sql = "
            SELECT 
                s.productid,
                s.productname,
                s.variety,
                s.tray_code,
                s.qtypertray,
                IFNULL(pm.PriceOfProduct, 0) AS standard_rate,
                COUNT(s.id) AS total_trays,
                SUM(s.actual_plant_count) AS total_plants,
                SUM(s.sold_qty) AS total_sold_qty,
                GROUP_CONCAT(DISTINCT s.open_sale_location SEPARATOR ', ') AS locations_list,
                SUM(CASE WHEN s.open_sale_location = 'Karjee' OR s.opensale_locationid = 'LOC6905e0fd5ab05' THEN s.actual_plant_count ELSE 0 END) AS plants_karjee,
                SUM(CASE WHEN s.open_sale_location = 'Sargawa' OR s.opensale_locationid = 'LOC696b280faf104' THEN s.actual_plant_count ELSE 0 END) AS plants_sargawa,
                SUM(CASE WHEN s.open_sale_location = 'Mansoon' OR s.opensale_locationid = 'LOC6a4f396f08173' THEN s.actual_plant_count ELSE 0 END) AS plants_mansoon
            FROM krf_opensale_stocks s
            LEFT JOIN product_master pm ON pm.ProductID = s.productid
            WHERE {$whereSql}
            GROUP BY s.productid, s.productname, s.variety
            ORDER BY total_plants DESC, s.productname ASC
        ";

        $res = $krfconn->query($sql);
        $rows = [];
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $r['standard_rate'] = (float)$r['standard_rate'];
                $r['total_trays']   = (int)$r['total_trays'];
                $r['total_plants']  = (int)$r['total_plants'];
                $r['total_sold_qty']= (int)$r['total_sold_qty'];
                $r['plants_karjee'] = (int)$r['plants_karjee'];
                $r['plants_sargawa']= (int)$r['plants_sargawa'];
                $r['plants_mansoon']= (int)$r['plants_mansoon'];
                $rows[] = $r;
            }
        }
        return $rows;
    }
}

if (!function_exists('getOpenSaleTraysList')) {
    function getOpenSaleTraysList($krfconn, $projectid = '', $filters = [], $limit = 1000, $offset = 0) {
        $where = ["1=1"];

        $st = !empty($filters['status']) ? $filters['status'] : 'OpenSale';
        if ($st !== 'All') {
            $stEsc = $krfconn->real_escape_string($st);
            $where[] = "s.status = '{$stEsc}'";
        }

        if (!empty($filters['location'])) {
            $lEsc = $krfconn->real_escape_string($filters['location']);
            $where[] = "(s.open_sale_location = '{$lEsc}' OR s.opensale_locationid = '{$lEsc}' OR s.open_sale_location LIKE '%{$lEsc}%' OR '{$lEsc}' LIKE CONCAT('%', s.open_sale_location, '%'))";
        }
        if (!empty($filters['productid'])) {
            $pEsc = $krfconn->real_escape_string($filters['productid']);
            $where[] = "s.productid = '{$pEsc}'";
        }
        if (!empty($filters['search'])) {
            $sEsc = $krfconn->real_escape_string(trim($filters['search']));
            $where[] = "(
                s.barcode LIKE '%{$sEsc}%' 
                OR s.productname LIKE '%{$sEsc}%' 
                OR s.variety LIKE '%{$sEsc}%' 
                OR s.stockorder_no LIKE '%{$sEsc}%'
                OR s.psono LIKE '%{$sEsc}%'
                OR s.from_bed_no LIKE '%{$sEsc}%'
                OR s.from_polyhousename LIKE '%{$sEsc}%'
                OR s.invoiceno LIKE '%{$sEsc}%'
            )";
        }
        if (!empty($filters['date_from'])) {
            $dfEsc = $krfconn->real_escape_string($filters['date_from']);
            $where[] = "DATE(s.created_at) >= '{$dfEsc}'";
        }
        if (!empty($filters['date_to'])) {
            $dtEsc = $krfconn->real_escape_string($filters['date_to']);
            $where[] = "DATE(s.created_at) <= '{$dtEsc}'";
        }

        $whereSql = implode(" AND ", $where);
        $limit = max(1, min(5000, (int)$limit));
        $offset = max(0, (int)$offset);

        // Count total matching
        $countSql = "SELECT COUNT(*) as total FROM krf_opensale_stocks s WHERE {$whereSql}";
        $cRes = $krfconn->query($countSql);
        $totalRecords = $cRes ? (int)$cRes->fetch_assoc()['total'] : 0;

        $sql = "
            SELECT 
                s.id,
                s.polyhouseplantid,
                s.barcode,
                s.stockorder_no,
                s.psono,
                s.productid,
                s.productname,
                s.variety,
                s.trayid,
                s.tray_code,
                s.qtypertray,
                s.actual_plant_count,
                s.sold_qty,
                s.from_locationid,
                s.from_locationname,
                s.from_polyhouseid,
                s.from_polyhousename,
                s.from_bed_no,
                s.open_sale_location,
                s.opensale_locationid,
                s.movement_reason,
                s.status,
                s.shiftingstatus,
                s.invoiceno,
                s.remarks,
                s.created_by,
                s.created_at,
                s.updated_at,
                s.sold_at,
                IFNULL(pm.PriceOfProduct, 0) AS standard_rate
            FROM krf_opensale_stocks s
            LEFT JOIN product_master pm ON pm.ProductID = s.productid
            WHERE {$whereSql}
            ORDER BY s.id DESC
            LIMIT {$offset}, {$limit}
        ";

        $res = $krfconn->query($sql);
        $items = [];
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $r['actual_plant_count'] = (int)$r['actual_plant_count'];
                $r['qtypertray']         = (int)$r['qtypertray'];
                $r['sold_qty']           = (int)$r['sold_qty'];
                $r['standard_rate']      = (float)$r['standard_rate'];
                $r['created_at_fmt']     = !empty($r['created_at']) ? date('d-M-Y h:i A', strtotime($r['created_at'])) : '-';
                $r['sold_at_fmt']        = !empty($r['sold_at']) ? date('d-M-Y h:i A', strtotime($r['sold_at'])) : '-';
                $items[] = $r;
            }
        }

        return [
            'total' => $totalRecords,
            'items' => $items
        ];
    }
}

if (!function_exists('getOpenSaleFilterOptions')) {
    function getOpenSaleFilterOptions($krfconn, $projectid = '') {
        // 1. Locations from krf_polyhouse_master
        $locations = [];
        $locSql = "
            SELECT polyhouseid, polyhousename, locationid 
            FROM krf_polyhouse_master 
            WHERE (type = 'OpenSale' OR structure_type = 'OpenSale' OR polyhousename LIKE '%Open%Sale%') 
              AND (status = 'Active' OR status IS NULL) 
            ORDER BY polyhouseid ASC
        ";
        $lRes = $krfconn->query($locSql);
        if ($lRes) {
            while ($lr = $lRes->fetch_assoc()) {
                $rawLoc = !empty($lr['polyhousename']) ? $lr['polyhousename'] : ('Open Sale ' . $lr['polyhouseid']);
                $cleanEnum = 'Karjee';
                if (stripos($rawLoc, 'Mansoon') !== false) {
                    $cleanEnum = 'Mansoon';
                } elseif (stripos($rawLoc, 'Sargw') !== false || stripos($rawLoc, 'Sargaw') !== false) {
                    $cleanEnum = 'Sargawa';
                }
                $locations[] = [
                    'polyhouseid'   => $lr['polyhouseid'],
                    'polyhousename' => $rawLoc,
                    'locationid'    => $lr['locationid'],
                    'enum_location' => $cleanEnum
                ];
            }
        }

        // 2. Distinct products present in krf_opensale_stocks
        $products = [];
        $pSql = "
            SELECT DISTINCT s.productid, s.productname 
            FROM krf_opensale_stocks s 
            WHERE s.productname IS NOT NULL AND s.productname != '' 
            ORDER BY s.productname ASC
        ";
        $pRes = $krfconn->query($pSql);
        if ($pRes) {
            while ($pr = $pRes->fetch_assoc()) {
                $products[] = [
                    'productid'   => $pr['productid'],
                    'productname' => $pr['productname']
                ];
            }
        }

        return [
            'locations' => $locations,
            'products'  => $products
        ];
    }
}
