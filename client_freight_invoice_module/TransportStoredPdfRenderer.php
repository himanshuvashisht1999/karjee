<?php
/**
 * TCPDF-native renderer for stored Step 5 transport PDFs.
 *
 * IMPORTANT:
 * - Uses simple TCPDF-compatible HTML/tables only (no flex/grid CSS).
 * - Common header/font/layout is centralized here.
 * - Document body remains document-specific.
 * - No transport business SQL lives in this class.
 */
final class TransportStoredPdfRenderer
{
    private TCPDF $pdf;
    // FreeSerif is bundled with standard TCPDF distributions and provides
    // broader Devanagari/Unicode coverage for Hindi addresses/location names.
    private string $fontFamily = 'dejavusans';
    private array $brand;

    public function __construct(TCPDF $pdf, array $brand)
    {
        $this->pdf = $pdf;
        if (function_exists('transport_document_pdf_font')) {
            $this->fontFamily = transport_document_pdf_font();
        }
$this->brand = function_exists('transport_pdf_display_data') ? transport_pdf_display_data($brand) : $brand;
    }

    private static function e($v): string
    {
        $v = function_exists('transport_pdf_display_text') ? transport_pdf_display_text($v) : (string)$v;
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }

    private static function n($v): string
    {
        return number_format((float)$v, 0);
    }

    private static function dt($v): string
    {
        if (!$v) return '-';
        $ts = strtotime((string)$v);
        return $ts ? date('d-M-Y h:i A', $ts) : (string)$v;
    }

    /**
     * Convert the stored pickup mode into the user-facing transport wording.
     *
     * Older/newer transport queries have used slightly different aliases, so
     * the renderer accepts all known keys. The confirmed snapshot should still
     * store one canonical value: delivered, pickbyclient, or boxpacking.
     */
    private static function pickupTypeLabel(array $header, array $details = []): string
    {
        $keys = [
            'pickup_type',
            'pickupfrom',
            'pickup_from',
            'pickup_mode',
            'pickuptype',
            'movement_type',
        ];

        $raw = '';
        foreach ($keys as $key) {
            if (isset($header[$key]) && trim((string)$header[$key]) !== '') {
                $raw = trim((string)$header[$key]);
                break;
            }
        }

        // Detail fallback supports older snapshot queries where pickupfrom was
        // selected with the dispatch lines instead of the snapshot header.
        if ($raw === '' && !empty($details[0]) && is_array($details[0])) {
            foreach ($keys as $key) {
                if (isset($details[0][$key]) && trim((string)$details[0][$key]) !== '') {
                    $raw = trim((string)$details[0][$key]);
                    break;
                }
            }
        }

        if ($raw === '') {
            return '-';
        }

        $normalized = strtolower((string)preg_replace('/[^a-z0-9]+/', '', $raw));
        $labels = [
            'delivered' => 'Company Transport',
            'companytransport' => 'Company Transport',
            'pickbyclient' => 'Pick By Client',
            'pickupbyclient' => 'Pick By Client',
            'clientpickup' => 'Pick By Client',
            'selfpickup' => 'Pick By Client',
            'boxpacking' => 'Box Packing',
            'internal' => 'Internal Transfer',
            'internaltransfer' => 'Internal Transfer',
        ];

        return $labels[$normalized] ?? $raw;
    }

    public function writeCommonHeader(string $title, string $docNo): void
    {
        $pdf = $this->pdf;
        $b = $this->brand;

        // Match the established KF provisional-document visual language:
        // Helvetica, black text, logo left, company/address right, centered title.
        $pdf->SetFont($this->fontFamily, 'B', 11);
        $pdf->Cell(0, 5, 'GSTIN/UIN: '.(string)($b['gstin'] ?? ''), 0, 1, 'L');

        $y = $pdf->GetY();
        $logo = (string)($b['logo_path'] ?? '');
        if ($logo !== '' && is_file($logo)) {
            $pdf->Image($logo, 13, $y + 1, 30, 0, '', '', '', false, 300);
        }

        $pdf->SetXY(92, $y + 1);
        $pdf->SetFont($this->fontFamily, 'B', 18);
        $pdf->Cell(105, 8, (string)($b['company'] ?? ''), 0, 1, 'R');
        $pdf->SetX(92);
        $pdf->SetFont($this->fontFamily, '', 11);
        $pdf->MultiCell(105, 5.5, (string)($b['address'] ?? ''), 0, 'R', false, 1);
        $pdf->SetX(92);
        $pdf->Cell(105, 5.5, 'Mobile No: '.(string)($b['mobile'] ?? ''), 0, 1, 'R');

        // Keep enough vertical room for logo before heading.
        $pdf->SetY(max($pdf->GetY() + 3, $y + 31));
        $pdf->SetFont($this->fontFamily, 'B', 16);
        $pdf->Cell(0, 9, $title, 0, 1, 'C');
        $pdf->SetFont($this->fontFamily, 'B', 11);
        $pdf->Cell(0, 5.5, $docNo, 0, 1, 'C');
        $pdf->Ln(4);
    }

    private function overviewGrid(array $cells, int $columns = 3): void
    {
        $columns = max(1, min(3, $columns));
        $width = $columns === 3 ? '33.33%' : ($columns === 2 ? '50%' : '100%');
        $html = '<table border="1" cellpadding="0" cellspacing="0" width="100%">';
        $chunks = array_chunk($cells, $columns);
        foreach ($chunks as $chunk) {
            $html .= '<tr>';
            foreach ($chunk as $cell) {
                $label = self::e($cell[0] ?? '');
                $value = self::e(($cell[1] ?? '') !== '' ? $cell[1] : '-');
                $valueFontSize = strcasecmp((string)($cell[0] ?? ''), 'Vehicle') === 0
                    ? '13pt'
                    : '11.5pt';
                $html .= '<td width="'.$width.'" style="padding:0;">'
                    . '<table border="0" cellpadding="5" cellspacing="0" width="100%">'
                    . '<tr><td style="background-color:#eeeeee;font-size:10.5pt;font-weight:bold;line-height:1.25;color:#222;">'.$label.'</td></tr>'
                    . '<tr><td style="font-size:'.$valueFontSize.';font-weight:bold;line-height:1.35;color:#000;">'.$value.'</td></tr>'
                    . '</table></td>';
            }
            for ($i = count($chunk); $i < $columns; $i++) {
                $html .= '<td width="'.$width.'"></td>';
            }
            $html .= '</tr>';
        }
        $html .= '</table><br>';
        $this->pdf->SetFont($this->fontFamily, '', 11.5);
        $this->pdf->writeHTML($html, true, false, true, false, '');
    }

    private function sectionTitle(string $title): void
    {
        $this->pdf->SetFont($this->fontFamily, 'B', 10.5);
        $this->pdf->Cell(0, 6, $title, 0, 1, 'L');
    }


    /**
     * Compact helpers used only by Client Freight Invoice.
     * They intentionally do not change Gate Pass / Challan / other frozen PDFs.
     */
    private function invoiceSectionTitle(string $title): void
    {
        $this->pdf->SetFont($this->fontFamily, 'B', 9.2);
        $this->pdf->Cell(0, 4.5, $title, 0, 1, 'L');
    }

    private function invoiceOverviewGrid(array $cells, int $columns = 2): void
    {
        $columns = max(1, min(3, $columns));
        $width = $columns === 3 ? '33.33%' : ($columns === 2 ? '50%' : '100%');
        $html = '<table border="1" cellpadding="0" cellspacing="0" width="100%">';
        $chunks = array_chunk($cells, $columns);

        foreach ($chunks as $chunk) {
            $html .= '<tr>';
            foreach ($chunk as $cell) {
                $label = self::e($cell[0] ?? '');
                $value = self::e(($cell[1] ?? '') !== '' ? $cell[1] : '-');
                $html .= '<td width="'.$width.'" style="padding:0;">'
                    .'<table border="0" cellpadding="3" cellspacing="0" width="100%">'
                    .'<tr><td style="background-color:#eeeeee;font-size:8.2pt;font-weight:bold;line-height:1.05;">'.$label.'</td></tr>'
                    .'<tr><td style="font-size:9.3pt;font-weight:bold;line-height:1.10;">'.$value.'</td></tr>'
                    .'</table></td>';
            }
            for ($i = count($chunk); $i < $columns; $i++) {
                $html .= '<td width="'.$width.'"></td>';
            }
            $html .= '</tr>';
        }

        $html .= '</table>';
        $this->pdf->SetFont($this->fontFamily, '', 9.3);
        $this->pdf->writeHTML($html, true, false, true, false, '');
        $this->pdf->Ln(1.2);
    }

    private function writeInvoiceCompactFooter(array $labels, string $note): void
    {
        $pdf = $this->pdf;
        $pdf->Ln(4);
        $count = max(1, count($labels));
        $width = 184 / $count;

        // Enough space for a handwritten signature, but much less than the
        // generic transport-document footer so a normal freight invoice stays
        // on one A4 page even when Bill To wraps to 2-3 lines.
        $pdf->SetFont($this->fontFamily, '', 8.2);
        foreach ($labels as $label) {
            $pdf->Cell($width, 10, '', 0, 0, 'C');
        }
        $pdf->Ln(10);
        foreach ($labels as $label) {
            $pdf->Cell($width, 4.2, (string)$label, 'T', 0, 'C');
        }
        $pdf->Ln(5);

        $pdf->SetFont($this->fontFamily, '', 6.8);
        $pdf->SetTextColor(90, 90, 90);
        $pdf->MultiCell(0, 3.2, $note, 0, 'L', false, 1);
        $pdf->SetTextColor(0, 0, 0);
    }

    public function writeFooterSignatures(array $labels, string $note): void
    {
        $pdf = $this->pdf;
        $pdf->Ln(10);
        $count = max(1, count($labels));
        $width = 184 / $count;
        $pdf->SetFont($this->fontFamily, '', 9);
        foreach ($labels as $label) {
            $pdf->Cell($width, 18, '', 0, 0, 'C');
        }
        $pdf->Ln(18);
        foreach ($labels as $label) {
            $pdf->Cell($width, 5, (string)$label, 'T', 0, 'C');
        }
        $pdf->Ln(10);
        $pdf->SetFont($this->fontFamily, '', 7.5);
        $pdf->SetTextColor(90, 90, 90);
        $pdf->Cell(0, 4, $note, 0, 1, 'L');
        $pdf->SetTextColor(0, 0, 0);
    }

    public function renderGatePass(array $h, array $details): void
    {
        if (function_exists('transport_pdf_display_data')) { $h=transport_pdf_display_data($h); $details=transport_pdf_display_data($details); }
        $this->writeCommonHeader('GATE PASS', (string)$h['gate_pass_no']);

        $pickupType = self::pickupTypeLabel($h, $details);
        $transportLine = trim((string)($h['transport_type'] ?? ''));

        $this->overviewGrid([
            ['Trip No.', $h['trip_id'] ?? '-'],
            ['Transport Type', $transportLine !== '' ? $transportLine : '-'],
            ['Pickup / Movement Type', $pickupType],
            ['Vehicle', trim((string)($h['vehicle_name'] ?? '').' '.(string)($h['vehicle_no'] ?? ''))],
            ['Driver', trim((string)($h['driver_name'] ?? '').((!empty($h['driver_mobile'])) ? ' / '.$h['driver_mobile'] : ''))],
            ['Actual Gate Out', self::dt($h['actual_dispatch_at'] ?? '')],
            ['Pickup Source', $h['source_locationname'] ?? '-'],
            ['Total Load', self::n($h['total_loaded_qty'] ?? 0).' Plants / '.self::n($h['total_loaded_trays'] ?? 0).' Trays'],
            ['Start Odometer', isset($h['start_odometer_km']) && $h['start_odometer_km'] !== null ? $h['start_odometer_km'].' KM' : '-'],
        ], 3);

        $isInternal = strcasecmp((string)($h['transport_type'] ?? ''), 'Internal') === 0;
        $partyLabel = $isInternal ? 'Destinations' : 'Clients';

        $this->sectionTitle('Dispatch Summary');
        $summary = '<table border="1" cellpadding="4" cellspacing="0" width="100%"><tr>'
            .'<td align="center"><b>Stops</b><br>'.(int)($h['stop_count'] ?? 0).'</td>'
            .'<td align="center"><b>'.self::e($partyLabel).'</b><br>'.(int)($h['total_clients'] ?? 0).'</td>'
            .'<td align="center"><b>Orders</b><br>'.(int)($h['total_orders'] ?? 0).'</td>'
            .'<td align="center"><b>Products</b><br>'.(int)($h['total_products'] ?? 0).'</td>'
            .'<td align="center"><b>Loaded Plants</b><br>'.self::n($h['total_loaded_qty'] ?? 0).'</td>'
            .'<td align="center"><b>Loaded Trays</b><br>'.self::n($h['total_loaded_trays'] ?? 0).'</td>'
            .'</tr></table><br>';
        $this->pdf->SetFont($this->fontFamily, '', 9);
        $this->pdf->writeHTML($summary, true, false, true, false, '');

        $stops = [];
        foreach ($details as $d) {
            $k = (int)($d['trip_stop_id'] ?? 0);
            if (!isset($stops[$k])) {
                $stops[$k] = [
                    'sequence' => (int)($d['stop_sequence'] ?? 0),
                    'destination' => (string)(($d['destination_address'] ?? '') ?: ($d['destination_locationname'] ?? '-')),
                    'plants' => 0,
                    'trays' => 0,
                ];
            }
            $stops[$k]['plants'] += (int)($d['loaded_qty'] ?? 0);
            $stops[$k]['trays'] += (int)($d['loaded_trays'] ?? 0);
        }
        uasort($stops, fn($a,$b)=>$a['sequence']<=>$b['sequence']);

        $this->sectionTitle('Route / Destination Summary');
        $html = '<table border="1" cellpadding="4" cellspacing="0" width="100%">'
              . '<thead><tr style="font-weight:bold;background-color:#f2f2f2;"><th width="10%">Stop</th><th width="60%">Destination</th><th width="15%" align="right">Plants</th><th width="15%" align="right">Trays</th></tr></thead><tbody>';
        foreach ($stops as $s) {
            $html .= '<tr><td width="10%" align="center">'.(int)$s['sequence'].'</td><td width="60%">'.self::e($s['destination']).'</td><td width="15%" align="right">'.self::n($s['plants']).'</td><td width="15%" align="right">'.self::n($s['trays']).'</td></tr>';
        }
        $html .= '</tbody></table><br>';
        $this->pdf->writeHTML($html, true, false, true, false, '');

        $clientSummary=[];
        foreach($details as $d){
            $key=(string)($d['clientid']??'').'|'.(string)($d['clientname']??'').'|'.(string)($d['sono']??'');
            if(!isset($clientSummary[$key])) $clientSummary[$key]=['client'=>$d['clientname']??'','clientid'=>$d['clientid']??'','order'=>$d['sono']??'','plants'=>0,'trays'=>0];
            $clientSummary[$key]['plants']+=(int)($d['loaded_qty']??0); $clientSummary[$key]['trays']+=(int)($d['loaded_trays']??0);
        }
        $this->sectionTitle($isInternal ? 'Request-wise Plant Summary' : 'Client-wise Summary');
        $html='<table border="1" cellpadding="4" cellspacing="0" width="100%"><thead><tr style="font-weight:bold;background-color:#f2f2f2;"><th width="40%">'.($isInternal ? 'Destination' : 'Client').'</th><th width="30%">'.($isInternal ? 'Request' : 'Order').'</th><th width="15%" align="right">Plants</th><th width="15%" align="right">Trays</th></tr></thead><tbody>';
        foreach($clientSummary as $c){ $html.='<tr><td width="40%"><b>'.self::e($c['client']).'</b><br><span style="font-size:8px;">'.self::e($c['clientid']).'</span></td><td width="30%">'.self::e($c['order']).'</td><td width="15%" align="right">'.self::n($c['plants']).'</td><td width="15%" align="right">'.self::n($c['trays']).'</td></tr>'; }
        $html.='<tr style="font-weight:bold;"><td width="70%" colspan="2" align="right">Total</td><td width="15%" align="right">'.self::n($h['total_loaded_qty']??0).'</td><td width="15%" align="right">'.self::n($h['total_loaded_trays']??0).'</td></tr></tbody></table>';
        $this->pdf->writeHTML($html, true, false, true, false, '');

        if(trim((string)($h['dispatch_remarks']??''))!==''){
            $this->pdf->Ln(2); $this->pdf->SetFont($this->fontFamily,'',9); $this->pdf->MultiCell(0,5,'Remarks: '.(string)$h['dispatch_remarks'],1,'L');
        }

        $this->writeFooterSignatures([
            'Dispatch / Prepared By'.(!empty($h['confirmed_by']) ? "\n".$h['confirmed_by'] : ''),
            'Security Gate',
            'Driver'
        ], '');
    }

    public function renderVehicleChallan(array $h, array $details): void
    {
        if (function_exists('transport_pdf_display_data')) { $h=transport_pdf_display_data($h); $details=transport_pdf_display_data($details); }
        $this->writeCommonHeader('VEHICLE CHALLAN', (string)$h['vehicle_challan_no']);

        $pickupType = self::pickupTypeLabel($h, $details);
        $this->overviewGrid([
            ['Trip No.', $h['trip_id'] ?? '-'],
            ['Transport Type', ($h['transport_type'] ?? '') ?: '-'],
            ['Pickup / Movement Type', $pickupType],
            ['Vehicle', trim((string)($h['vehicle_name'] ?? '').' '.(string)($h['vehicle_no'] ?? ''))],
            ['Driver', trim((string)($h['driver_name'] ?? '').((!empty($h['driver_mobile'])) ? ' / '.$h['driver_mobile'] : ''))],
            ['Dispatch Date / Time', self::dt($h['actual_dispatch_at'] ?? '')],
            ['Pickup Source', $h['source_locationname'] ?? '-'],
            ['Total Load', self::n($h['total_loaded_qty'] ?? 0).' Plants / '.self::n($h['total_loaded_trays'] ?? 0).' Trays'],
            ['Stops', (string)($h['stop_count'] ?? 0)],
        ], 3);

        $routeStops=[];
        foreach($details as $d){ $sid=(int)($d['trip_stop_id']??0); if(!isset($routeStops[$sid])) $routeStops[$sid]=['sequence'=>(int)($d['stop_sequence']??0),'destination'=>(string)(($d['destination_address']??'')?:($d['destination_locationname']??'Destination not available'))]; }
        uasort($routeStops,fn($a,$b)=>$a['sequence']<=>$b['sequence']);
        $this->sectionTitle('Route / Destination Sequence');
        $html='<table border="1" cellpadding="4" cellspacing="0" width="100%"><thead><tr style="font-weight:bold;background-color:#f2f2f2;"><th width="12%">Stop</th><th width="88%">Destination / Stoppage</th></tr></thead><tbody>';
        foreach($routeStops as $r){$html.='<tr><td width="12%" align="center">'.(int)$r['sequence'].'</td><td width="88%">'.self::e($r['destination']).'</td></tr>';}
        $html.='</tbody></table><br>';
        $this->pdf->writeHTML($html,true,false,true,false,'');

        $grouped=[];
        foreach($details as $d){ $sid=(int)($d['trip_stop_id']??0); if(!isset($grouped[$sid])) $grouped[$sid]=['sequence'=>(int)($d['stop_sequence']??0),'destination'=>(string)(($d['destination_address']??'')?:($d['destination_locationname']??'Destination not available')),'rows'=>[],'plants'=>0,'trays'=>0]; $grouped[$sid]['rows'][]=$d; $grouped[$sid]['plants']+=(int)($d['loaded_qty']??0); $grouped[$sid]['trays']+=(int)($d['loaded_trays']??0); }
        uasort($grouped,fn($a,$b)=>$a['sequence']<=>$b['sequence']);

        $isInternal = strcasecmp((string)($h['transport_type'] ?? ''), 'Internal') === 0;
        foreach($grouped as $g){
            $this->sectionTitle('Stop '.(int)$g['sequence'].' - Destination: '.$g['destination']);
            $html='<table border="1" cellpadding="4" cellspacing="0" width="100%"><thead><tr style="font-weight:bold;background-color:#f2f2f2;"><th width="26%">'.($isInternal ? 'Destination' : 'Client').'</th><th width="19%">'.($isInternal ? 'Request' : 'Order').'</th><th width="30%">Product</th><th width="15%" align="right">Loaded Plants</th><th width="10%" align="right">Trays</th></tr></thead><tbody>';
            foreach($g['rows'] as $d){$html.='<tr><td width="26%"><b>'.self::e($d['clientname']??'').'</b><br><span style="font-size:8px;">'.self::e($d['clientid']??'').'</span></td><td width="19%">'.self::e($d['sono']??'').'</td><td width="30%">'.self::e($d['productname']??'').'</td><td width="15%" align="right">'.self::n($d['loaded_qty']??0).'</td><td width="10%" align="right">'.self::n($d['loaded_trays']??0).'</td></tr>';}
            $html.='<tr style="font-weight:bold;"><td width="75%" colspan="3" align="right">Stop Total</td><td width="15%" align="right">'.self::n($g['plants']).'</td><td width="10%" align="right">'.self::n($g['trays']).'</td></tr></tbody></table><br>';
            $this->pdf->writeHTML($html,true,false,true,false,'');
        }

        $this->writeFooterSignatures([
            'Dispatch By'.(!empty($h['confirmed_by']) ? "\n".$h['confirmed_by'] : ''),
            'Driver Signature',
            'Authorized Signature'
        ], '');
    }

    public function renderClientPlantSummary(array $h, array $details): void
    {
        if (function_exists('transport_pdf_display_data')) { $h=transport_pdf_display_data($h); $details=transport_pdf_display_data($details); }
        $isInternal = strcasecmp((string)($h['transport_type'] ?? ''), 'Internal') === 0;
        $this->writeCommonHeader(
            $isInternal ? 'PLANT SUMMARY' : 'CLIENT PLANT SUMMARY',
            (string)($h['trip_id'] ?? '')
        );

        $pickupType = self::pickupTypeLabel($h, $details);
        $this->overviewGrid([
            ['Trip No.', $h['trip_id'] ?? '-'],
            ['Transport Type', ($h['transport_type'] ?? '') ?: '-'],
            ['Pickup / Movement Type', $pickupType],
            ['Vehicle', trim((string)($h['vehicle_name'] ?? '').' '.(string)($h['vehicle_no'] ?? ''))],
            ['Dispatch Date / Time', self::dt($h['actual_dispatch_at'] ?? '')],
            ['Total Load', self::n($h['total_loaded_qty'] ?? 0).' Plants / '.self::n($h['total_loaded_trays'] ?? 0).' Trays'],
        ], 3);

        $clients=[];
        foreach($details as $d){
            $ck=(string)($d['clientid']??'').'|'.(string)($d['clientname']??'');
            if(!isset($clients[$ck])) $clients[$ck]=['name'=>$d['clientname']??'','id'=>$d['clientid']??'','orders'=>[],'plants'=>0,'trays'=>0];
            $ok=(string)($d['sono']??'');
            if(!isset($clients[$ck]['orders'][$ok])) $clients[$ck]['orders'][$ok]=[];
            $clients[$ck]['orders'][$ok][]=$d;
            $clients[$ck]['plants']+=(int)($d['loaded_qty']??0);
            $clients[$ck]['trays']+=(int)($d['loaded_trays']??0);
        }

        foreach($clients as $client){
            $this->sectionTitle(trim((string)$client['name']).(($client['id']??'')!==''?' ('.$client['id'].')':''));
            foreach($client['orders'] as $order=>$rows){
                $oq=0;$ot=0;
                $html='<table border="1" cellpadding="4" cellspacing="0" width="100%">'
                    .'<tr style="font-weight:bold;background-color:#f2f2f2;"><td colspan="4">'.($isInternal ? 'Request' : 'Order').': '.self::e($order!==''?$order:'-').'</td></tr>'
                    .'<tr style="font-weight:bold;background-color:#f2f2f2;"><th width="35%">Stop / Destination</th><th width="35%">Product</th><th width="18%" align="right">Loaded Plants</th><th width="12%" align="right">Trays</th></tr>';
                foreach($rows as $d){
                    $q=(int)($d['loaded_qty']??0);$t=(int)($d['loaded_trays']??0);$oq+=$q;$ot+=$t;
                    $dest=(string)(($d['destination_address']??'')?:($d['destination_locationname']??''));
                    $html.='<tr><td width="35%">Stop '.(int)($d['stop_sequence']??0).'<br><span style="font-size:8px;">'.self::e($dest).'</span></td><td width="35%">'.self::e($d['productname']??'').'</td><td width="18%" align="right">'.self::n($q).'</td><td width="12%" align="right">'.self::n($t).'</td></tr>';
                }
                $html.='<tr style="font-weight:bold;"><td width="70%" colspan="2" align="right">Order Total</td><td width="18%" align="right">'.self::n($oq).'</td><td width="12%" align="right">'.self::n($ot).'</td></tr></table><br>';
                $this->pdf->writeHTML($html,true,false,true,false,'');
            }
        }

        $this->writeFooterSignatures([
            'Dispatch / Prepared By'.(!empty($h['confirmed_by']) ? "\n".$h['confirmed_by'] : ''),
            'Checked By',
            'Authorized Signature'
        ], $isInternal
            ? 'Operational Internal Transfer plant quantity summary generated from system.'
            : 'Operational client plant quantity summary generated from system.');
    }

    public function renderDetailedLoadingSheet(array $h, array $details, array $scans): void
    {
        if (function_exists('transport_pdf_display_data')) { $h=transport_pdf_display_data($h); $details=transport_pdf_display_data($details); $scans=transport_pdf_display_data($scans); }
        $isInternal = strcasecmp((string)($h['transport_type'] ?? ''), 'Internal') === 0;
        $this->writeCommonHeader('DETAILED LOADING / DISPATCH SHEET', (string)($h['trip_id'] ?? ''));

        $pickupType = self::pickupTypeLabel($h, $details);
        $this->overviewGrid([
            ['Trip No.', $h['trip_id'] ?? '-'],
            ['Transport Type', ($h['transport_type'] ?? '') ?: '-'],
            ['Pickup / Movement Type', $pickupType],
            ['Vehicle', trim((string)($h['vehicle_name'] ?? '').' '.(string)($h['vehicle_no'] ?? ''))],
            ['Driver', trim((string)($h['driver_name'] ?? '').((!empty($h['driver_mobile'])) ? ' / '.$h['driver_mobile'] : ''))],
            ['Dispatch Date / Time', self::dt($h['actual_dispatch_at'] ?? '')],
        ], 3);

        $this->sectionTitle('Loaded Scan / Allocation Detail');
        $html='<table border="1" cellpadding="3" cellspacing="0" width="100%"><thead><tr style="font-weight:bold;background-color:#f2f2f2;">'
            .'<th width="7%">Scan</th><th width="7%">Stop</th><th width="14%">Barcode</th><th width="13%">'.($isInternal ? 'Destination' : 'Client').'</th><th width="12%">'.($isInternal ? 'Request' : 'Order').'</th><th width="17%">Product</th><th width="13%">Stock Order</th><th width="8%" align="right">Physical</th><th width="9%" align="right">Allocated</th>'
            .'</tr></thead><tbody>';
        foreach($scans as $row){
            $html.='<tr><td width="7%">'.(int)($row['scan_id']??0).'</td><td width="7%">'.(int)($row['trip_stop_id']??0).'</td><td width="14%">'.self::e($row['barcode']??'').'</td><td width="13%">'.self::e($row['clientid']??'').'</td><td width="12%">'.self::e($row['sono']??'').'</td><td width="17%">'.self::e($row['productname']??'').'</td><td width="13%">'.self::e($row['stockorder_no']??'').'</td><td width="8%" align="right">'.self::n($row['physical_qty']??0).'</td><td width="9%" align="right">'.self::n($row['allocated_qty']??0).'</td></tr>';
        }
        if(!$scans){$html.='<tr><td colspan="9" align="center">No scan snapshot rows found.</td></tr>';}
        $html.='</tbody></table>';
        $this->pdf->SetFont($this->fontFamily,'',8.2);
        $this->pdf->writeHTML($html,true,false,true,false,'');

        $this->writeFooterSignatures([
            'Loaded / Prepared By'.(!empty($h['confirmed_by']) ? "\n".$h['confirmed_by'] : ''),
            'Driver Signature',
            'Authorized Signature'
        ], '');
    }


    public function renderClientFreightInvoice(array $d): void
    {
        if (function_exists('transport_pdf_display_data')) { $d=transport_pdf_display_data($d); }
        $this->writeCommonHeader('CLIENT FREIGHT INVOICE', (string)($d['invoice_no'] ?? ''));

        $invoiceDate=(string)($d['invoice_date']??'');
        if($invoiceDate!==''){
            $ts=strtotime($invoiceDate);
            if($ts)$invoiceDate=date('d-M-Y',$ts);
        }

        // Compact invoice identity / billing party. Invoice-only layout: other
        // transport documents keep using the standard overviewGrid().
        $this->invoiceOverviewGrid([
            ['Client',$d['client_name']??'-'],
            ['Client Code',$d['client_id']??'-'],
            ['Invoice Date',$invoiceDate?:'-'],
            ['Financial Year',$d['financial_year']??'-']
        ],2);

        if(trim((string)($d['billing_address']??''))!==''){
            $this->invoiceSectionTitle('Bill To');
            $billTo = trim((string)($d['client_name']??''));
            $address = trim((string)$d['billing_address']);
            if($address!=='') $billTo .= ($billTo!=='' ? "\n" : '').$address;

            // 4 mm line height comfortably supports a 2-3 line address while
            // preserving enough page height for totals and signatures.
            $this->pdf->SetFont($this->fontFamily,'',8.6);
            $this->pdf->MultiCell(0,4,$billTo,1,'L',false,1);
            $this->pdf->Ln(1.2);
        }

        $this->invoiceSectionTitle('Transport Reference');
        $this->invoiceOverviewGrid([
            ['Trip No.',$d['trip_id']??'-'],
            ['Trip Date',$d['trip_date']??'-'],
            ['Vehicle',$d['vehicle_no']??'-'],
            ['Distance',number_format((float)($d['trip_distance_km']??0),2).' KM']
        ],2);

        // Client-specific actual trip stoppage. Do not use the billing address
        // and do not print the generic trip label "Final Stop" when an actual
        // stoppage address is available.
        $source=trim((string)($d['source_locationname']??''));
        $stoppage=trim((string)($d['stoppage_address']??''));
        if($stoppage!==''){
            $this->pdf->SetFont($this->fontFamily,'B',7.8);
            $this->pdf->Cell(0,3.6,'Route',0,1,'L');
            $this->pdf->SetFont($this->fontFamily,'',8.2);
            $route=($source!==''?$source:'Karjee').'  →  '.$stoppage;
            $this->pdf->MultiCell(0,3.8,$route,1,'L',false,1);
            $this->pdf->Ln(1);
        }

        $this->invoiceSectionTitle('Freight Charges');
        $original=(float)($d['original_freight']??0);
        $discount=(float)($d['discount_amount']??0);
        $adjustment=(float)($d['adjustment']??0);
        $taxable=(float)($d['taxable']??0);

        $html=
            '<table border="1" cellpadding="4" cellspacing="0" width="100%">'
           .'<thead><tr style="font-weight:bold;background-color:#f2f2f2;">'
           .'<th width="55%">Particulars</th>'
           .'<th width="15%" align="right">Rate / %</th>'
           .'<th width="30%" align="right">Amount</th>'
           .'</tr></thead><tbody>'
           .'<tr><td width="55%">Transport Client Freight</td><td width="15%" align="right">-</td><td width="30%" align="right">'.number_format($original,2).'</td></tr>'
           .'<tr><td>Less: Discount</td><td align="right">'.number_format((float)($d['discount_percent']??0),2).'%</td><td align="right">('.number_format($discount,2).')</td></tr>'
           .'<tr><td>Billing Adjustment (+/-)</td><td align="right">-</td><td align="right">'.number_format($adjustment,2).'</td></tr>'
           .'<tr style="font-weight:bold;background-color:#f8fafc;"><td colspan="2" align="right">Taxable Freight</td><td align="right">'.number_format($taxable,2).'</td></tr>'
           .'</tbody></table>';
        $this->pdf->SetFont($this->fontFamily,'',8.6);
        $this->pdf->writeHTML($html,true,false,true,false,'');
        $this->pdf->Ln(1);

        // Slightly smaller heading/body, as requested, to keep the invoice
        // compact without making the financial values hard to read.
        $this->invoiceSectionTitle('Tax & Invoice Summary');
        $html=
            '<table border="1" cellpadding="4" cellspacing="0" width="48%" align="right">'
           .'<tr><td width="60%">Taxable Freight</td><td width="40%" align="right">'.number_format($taxable,2).'</td></tr>'
           .'<tr><td>CGST '.number_format((float)($d['cgst_percent']??0),2).'%</td><td align="right">'.number_format((float)($d['cgst_amount']??0),2).'</td></tr>'
           .'<tr><td>SGST '.number_format((float)($d['sgst_percent']??0),2).'%</td><td align="right">'.number_format((float)($d['sgst_amount']??0),2).'</td></tr>'
           .'<tr><td>IGST '.number_format((float)($d['igst_percent']??0),2).'%</td><td align="right">'.number_format((float)($d['igst_amount']??0),2).'</td></tr>'
           .'<tr style="font-weight:bold;background-color:#f2f2f2;"><td>Invoice Amount</td><td align="right">'.number_format((float)($d['invoice_amount']??0),2).'</td></tr>'
           .'</table>';
        $this->pdf->SetFont($this->fontFamily,'',8.3);
        $this->pdf->writeHTML($html,true,false,true,false,'');

        $reason=trim((string)($d['reason']??''));
        $remarks=trim((string)($d['remarks']??''));
        if($reason!==''||$remarks!==''){
            $this->pdf->Ln(2);
            $this->invoiceSectionTitle('Billing Notes');
            $note='';
            if($reason!=='')$note.='Discount / Adjustment Reason: '.$reason;
            if($remarks!=='')$note.=($note!==''?"\n":'').'Remarks: '.$remarks;
            $this->pdf->SetFont($this->fontFamily,'',8);
            $this->pdf->MultiCell(0,3.8,$note,1,'L',false,1);
        }

        $this->writeInvoiceCompactFooter(
            [
                'Prepared By'.(!empty($d['prepared_by'])?"\n".$d['prepared_by']:''),
                'Accounts',
                'Authorized Signatory'
            ],
            'System-generated Client Freight Invoice. Original Transport Client Freight remains unchanged.'
        );
    }

    public function renderTransportFreightProposal(array $d): void
    {
        if (function_exists('transport_pdf_display_data')) { $d=transport_pdf_display_data($d); }
        $this->writeCommonHeader(
            'TRANSPORT FREIGHT CHARGES PROPOSAL',
            (string)($d['proposal_no']??'')
        );

        $transportType=trim((string)($d['transport_type']??''));
        $this->overviewGrid([
            ['Trip No.',$d['trip_id']??'-'],
            ['Transport Type',$transportType?:'-'],
            ['Transporter',trim((string)($d['transporter_name']??''))?:'-'],
            ['Vehicle',trim((string)($d['vehicle_name']??'').' '.(string)($d['vehicle_no']??''))?:'-'],
            ['Driver',$d['driver_name']??'-'],
            ['Total Distance',number_format((float)($d['total_distance_km']??0),2).' KM']
        ],3);

        $source=trim((string)($d['source_locationname']??''));
        $destination=trim((string)($d['destination_locationname']??''));
        if($source!=='' || $destination!==''){
            $this->sectionTitle('Route');
            $this->pdf->SetFont($this->fontFamily,'',9.5);
            $this->pdf->MultiCell(
                0,6,
                ($source?:'-').'  →  '.($destination?:'-'),
                1,'L'
            );
            $this->pdf->Ln(3);
        }

        $this->sectionTitle('Transporter Freight Calculation');

        $bothWay=!empty($d['both_way_applicable']) &&
                 strtolower((string)$d['both_way_applicable'])!=='no';
        $html='<table border="1" cellpadding="6" cellspacing="0" width="100%">'
            .'<tr><td width="65%">Both Way Charges Applicable</td><td width="35%" align="right">'.($bothWay?'Yes':'No').'</td></tr>'
            .'<tr><td>One Way Transport Charge</td><td align="right">'.number_format((float)($d['one_way_freight']??0),2).'</td></tr>'
            .'<tr><td>Return Way Transport Charge</td><td align="right">'.number_format((float)($d['return_freight']??0),2).'</td></tr>'
            .'<tr style="font-weight:bold;"><td>Gross Transporter Freight</td><td align="right">'.number_format((float)($d['gross_freight']??0),2).'</td></tr>'
            .'<tr><td>Transporter Discount'
            .((float)($d['discount_percent']??0)>0?' ('.number_format((float)$d['discount_percent'],2).'%)':'')
            .'</td><td align="right">'.number_format((float)($d['discount_amount']??0),2).'</td></tr>'
            .'<tr style="font-weight:bold;background-color:#f2f2f2;"><td>Pay to Transporter</td><td align="right">'.number_format((float)($d['pay_to_transporter']??0),2).'</td></tr>'
            .'</table>';

        $this->pdf->SetFont($this->fontFamily,'',10);
        $this->pdf->writeHTML($html,true,false,true,false,'');

        $this->writeFooterSignatures(
            [
                'Prepared By'.(!empty($d['prepared_by'])?"\n".$d['prepared_by']:''),
                'Transport / Accounts',
                'Approved By'
            ],
            ''
        );
    }

}
