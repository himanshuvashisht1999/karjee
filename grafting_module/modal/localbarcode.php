<?php

require('../../connection.php'); 

$projectid     = '17';//(int)($_GET['projectid'] ?? 0);
$locationid    = 'LOC696b280faf104';//trim($_GET['locationid'] ?? '');
$stockorder_no = 'KFP4109';//trim($_GET['stockorder'] ?? '');
$productid     = '196';//trim($_GET['productid'] ?? '');

$error = '';

if ($projectid <= 0) {
    $error = 'Invalid project.';
}
elseif ($locationid === '') {
    $error = 'Location is required.';
}
elseif ($stockorder_no === '') {
    $error = 'Stock Order No. is required.';
}
elseif ($productid === '') {
    $error = 'Product is required.';
}


$labels = [];


if ($error === '') {

    $sql = "
        SELECT
            barcode,
            MAX(productname) AS productname,
            MAX(CAST(qtypertray AS UNSIGNED)) AS qtypertray,
            MAX(graftingdate) AS graftingdate,
            MAX(created_at) AS created_at

        FROM krf_polyhouse_plant_details

        WHERE projectid = ?
          AND locationid = ?
          AND stockorder_no = ?
          AND productid = ?
          AND status = 'Grafted'
          AND barcode IS NOT NULL
          AND TRIM(barcode) <> ''

        GROUP BY barcode

        ORDER BY barcode
    ";


    $stmt = $krfconn->prepare($sql);


    if (!$stmt) {

        $error =
            'Query prepare failed: ' .
            $krfconn->error;

    } else {

        $stmt->bind_param(
            "isss",
            $projectid,
            $locationid,
            $stockorder_no,
            $productid
        );


        $stmt->execute();

        $result = $stmt->get_result();


        while ($row = $result->fetch_assoc()) {

            $printDate = '';

            if (!empty($row['graftingdate'])) {

                $printDate = date(
                    'd-m-Y',
                    strtotime($row['graftingdate'])
                );

            } elseif (!empty($row['created_at'])) {

                $printDate = date(
                    'd-m-Y',
                    strtotime($row['created_at'])
                );
            }


            $labels[] = [

                'barcode' =>
                    $row['barcode'],

                'stockorder_no' =>
                    $stockorder_no,

                'productname' =>
                    $row['productname'] ?? '',

                'qty' =>
                    (int)($row['qtypertray'] ?? 0),

                'date' =>
                    $printDate
            ];
        }


        $stmt->close();


        if (count($labels) === 0) {

            $error =
                'No Grafted tray barcodes found.';
        }
    }
}


function h($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

?>

<!DOCTYPE html>

<html>

<head>

<meta charset="UTF-8">

<title>Grafted Tray Barcode</title>


<style>

@page {

    size: A4;

    margin: 10mm;
}


* {

    box-sizing: border-box;
}


body {

    margin: 0;

    padding: 0;

    font-family: Arial, sans-serif;

    color: #000;

    background: #fff;
}


/* ---------------------------------------
   Top toolbar
--------------------------------------- */

.toolbar {

    margin-bottom: 10px;

    padding: 10px;

    background: #f5f5f5;

    border: 1px solid #ddd;

    text-align: center;
}


.toolbar button {

    padding: 7px 18px;

    margin: 0 4px;

    cursor: pointer;
}


/* ---------------------------------------
   Labels
--------------------------------------- */

.print-area {

    display: grid;

    grid-template-columns:
        repeat(2, 78.6mm);

    column-gap: 5mm;

    row-gap: 3mm;

    align-items: start;
}


.label {

    width: 78.6mm;

    height: 24mm;

    display: flex;

    padding: 1.2mm;

    border: 0.25mm solid #bbb;

    overflow: hidden;

    page-break-inside: avoid;

    break-inside: avoid;
}


.qr-area {

    width: 21mm;

    height: 21mm;

    flex-shrink: 0;

    display: flex;

    align-items: center;

    justify-content: center;
}


.qr {

    width: 19mm;

    height: 19mm;
}


.label-info {

    flex: 1;

    padding-left: 2mm;

    overflow: hidden;
}


.order-no {

    font-size: 9pt;

    font-weight: bold;

    line-height: 10pt;

    white-space: nowrap;
}


.plant-type {

    font-size: 8pt;

    font-weight: bold;

    line-height: 9pt;
}


.product-name {

    font-size: 7pt;

    line-height: 8pt;

    height: 8mm;

    overflow: hidden;
}


.bottom-info {

    display: flex;

    justify-content: space-between;

    font-size: 7pt;

    font-weight: bold;

    line-height: 8pt;
}


.barcode-text {

    font-size: 6pt;

    line-height: 7pt;

    white-space: nowrap;
}


.error-box {

    margin: 30px auto;

    max-width: 700px;

    padding: 20px;

    border: 1px solid #dc3545;

    color: #dc3545;

    text-align: center;

    font-weight: bold;
}


@media print {

    .no-print {

        display: none !important;
    }


    .label {

        border-color: #ddd;
    }
}

</style>


<script
    src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js">
</script>

</head>


<body>


<?php if ($error !== ''): ?>


    <div class="error-box">

        <?= h($error) ?>

    </div>


<?php else: ?>


    <div class="toolbar no-print">

        <strong>
            <?= count($labels) ?>
            Grafted Tray Barcode(s)
        </strong>

        &nbsp;&nbsp;

        <button
            type="button"
            onclick="window.print();">

            Print

        </button>


        <button
            type="button"
            onclick="window.close();">

            Close

        </button>

    </div>


    <div
        class="print-area"
        id="printArea">


        <?php foreach ($labels as $index => $label): ?>


            <div class="label">


                <div class="qr-area">

                    <div
                        class="qr"
                        id="qr_<?= $index ?>"
                        data-barcode="<?= h($label['barcode']) ?>">
                    </div>

                </div>


                <div class="label-info">


                    <div class="order-no">

                        <?= h($label['stockorder_no']) ?>

                    </div>


                    <div class="plant-type">

                        Grafted

                    </div>


                    <div class="product-name">

                        <?= h($label['productname']) ?>

                    </div>


                    <div class="bottom-info">

                        <span>

                            Qty:
                            <?= number_format(
                                (int)$label['qty']
                            ) ?>

                        </span>


                        <span>

                            <?= h($label['date']) ?>

                        </span>

                    </div>


                    <div class="barcode-text">

                        <?= h($label['barcode']) ?>

                    </div>


                </div>


            </div>


        <?php endforeach; ?>


    </div>


    <script>

    document.addEventListener(
        "DOMContentLoaded",
        function () {

            document
                .querySelectorAll(".qr")
                .forEach(function (el) {

                    const barcode =
                        el.dataset.barcode || "";

                    if (!barcode) {
                        return;
                    }


                    new QRCode(
                        el,
                        {
                            text: barcode,

                            width: 72,

                            height: 72,

                            correctLevel:
                                QRCode.CorrectLevel.M
                        }
                    );

                });

        }
    );

    </script>


<?php endif; ?>


</body>

</html>