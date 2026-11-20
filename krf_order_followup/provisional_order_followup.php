<?php
ob_start();
include('../header.php');
$followupHeader = ob_get_clean();
date_default_timezone_set('Asia/Kolkata');
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['draft_followup_csrf'])) $_SESSION['draft_followup_csrf'] = bin2hex(random_bytes(24));
function fuQuery($conn, $sql, $params = []) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new RuntimeException($conn->error);
    try {
        if ($params) $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        if (!$stmt->execute()) throw new RuntimeException($stmt->error);
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    } finally { $stmt->close(); }
}
$fuReminderConfig = require __DIR__ . '/followup_config.php';
function fuDefaultFollowup($createdAt, array $config) {
    $days=$config['default_followup_days']??1;
    $time=(string)($config['default_followup_time']??'10:30');
    if(filter_var($days,FILTER_VALIDATE_INT)===false || (int)$days<0 || !preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/',$time))throw new RuntimeException('Invalid default follow-up days/time configuration.');
    $created=new DateTimeImmutable($createdAt,new DateTimeZone('Asia/Calcutta'));
    return $created->setTime(0,0)->modify('+'.(int)$days.' days')->format('Y-m-d').' '.$time.':00';
}
$fuAdmin = !empty($fuAdmin);
if ($fuAdmin) {
    // Match the permitted module path, not a caller-provided mdid or role title.
    $fuAllowed = false;
    $modules = ((string)$loginroleid === '0')
        ? getAllClientModules($authconn, $companyid, $subcompanyid, $projectid, 'status', 'active')
        : OnlyProject_loginmodulesdetails_frommaster($authconn, $companyid, $subcompanyid, $projectid, 'allwithauth', allauthorizedmoduleid($authconn, $companyid, $subcompanyid, $projectid, $loginid));
    foreach ((array)$modules as $module) {
        $path = trim(str_replace('\\', '/', explode('?', (string)($module['modulefoldername'] ?? ''))[0]), '/');
        if ($path === 'krf_order_followup/provisional_order_followup_admin.php') $fuAllowed = true;
    }
    if (!$fuAllowed) {
        http_response_code(403);
        if (isset($_GET['fu_action'])) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status'=>'error','message'=>'You do not have access to the administrator follow-up page.']);
        } else echo $followupHeader . '<p>Access denied. Ask the administrator to grant this module.</p>';
        exit;
    }
}
$reasons = ['No Answer', 'Call Later', 'Payment Promised', 'Needs Order Change', 'Not Interested', 'Other'];
$fuScope = [(string)$companyid, (string)$subcompanyid, (string)$projectid];
if (!$fuAdmin) $fuScope[] = (string)$loginid;
$fuWhere = "p.companyid=? AND p.subcompanyid=? AND p.projectid=? AND p.status='Draft'" . ($fuAdmin ? "" : " AND p.created_by=?");
if (isset($_GET['fu_action'])) {
    header('Content-Type: application/json; charset=utf-8');
    $fuTransactionStarted = false;
    try {
        if (empty($loginid) || empty($projectid)) throw new RuntimeException('Login is required.');
        $action = $_GET['fu_action'];
        if ($action === 'list' || $action === 'reminders') {
            $rows = fuQuery($krfconn, "SELECT p.psono,p.clientid,p.clientname,p.mobileno,p.created_at,p.created_by,p.followup_review_pending,
                f.id AS followup_id,f.next_followup_date,f.reason,f.created_at AS last_followup_at
                FROM krf_provisional_sales_order p
                LEFT JOIN krf_provisional_order_followup f ON f.id=(SELECT MAX(h.id) FROM krf_provisional_order_followup h WHERE h.projectid=p.projectid AND h.psono=CONVERT(p.psono USING utf8mb4) COLLATE utf8mb4_general_ci)
                WHERE $fuWhere", $fuScope);
            if ($fuAdmin && $rows) {
                $ownerIds = array_values(array_unique(array_column($rows, 'created_by')));
                $users = fuQuery($authconn, 'SELECT loginid,loginname FROM login_user_master WHERE loginid IN (' . implode(',', array_fill(0, count($ownerIds), '?')) . ') ORDER BY sno DESC', $ownerIds);
                $ownerNames = [];
                foreach ($users as $user) {
                    if (!isset($ownerNames[$user['loginid']]) && trim((string)$user['loginname']) !== '') $ownerNames[$user['loginid']] = $user['loginname'];
                }
                foreach ($rows as &$ownerRow) $ownerRow['created_by_name'] = $ownerNames[$ownerRow['created_by']] ?? $ownerRow['created_by'];
                unset($ownerRow);
            }
            $today = date('Y-m-d');
            $now = date('Y-m-d H:i:s');
            foreach ($rows as &$row) {
                if($row['followup_id']===null && !(int)$row['followup_review_pending']){
                    $row['next_followup_date']=fuDefaultFollowup($row['created_at'],$fuReminderConfig);
                    $row['last_followup_at']=$row['created_at'];
                }
                $date = $row['next_followup_date'] ? substr($row['next_followup_date'], 0, 10) : null;
                $row['priority'] = $row['next_followup_date'] && $row['next_followup_date'] < $now ? 0 : ($date === $today ? 1 : (!$date ? 2 : 3));
                $row['no_follow_required'] = !$row['next_followup_date'] && (int)$row['followup_review_pending'] === 1;
            }
            unset($row);
            usort($rows, function ($a, $b) use ($fuAdmin) {
                if ($fuAdmin && (int)$a['followup_review_pending'] !== (int)$b['followup_review_pending']) return (int)$b['followup_review_pending'] <=> (int)$a['followup_review_pending'];
                $result = $a['priority'] <=> $b['priority'];
                if ($result) return $result;
                if (in_array($a['priority'], [0,1,3], true)) {
                    $result = strcmp($a['next_followup_date'], $b['next_followup_date']);
                    if ($result) return $result;
                }
                return strcmp($b['created_at'], $a['created_at']) ?: strnatcasecmp($b['psono'], $a['psono']);
            });
            if ($action === 'reminders') {
                $leadMinutes = max(0, min(10080, (int)($fuReminderConfig['reminder_before_minutes'] ?? 2)));
                $cutoff = date('Y-m-d H:i:s', time() + $leadMinutes * 60);
                $rows = empty($fuReminderConfig['enabled']) ? [] : array_values(array_filter($rows, function($r) use ($today,$now,$cutoff,$fuReminderConfig) {
                    return $r['next_followup_date'] && (!empty($fuReminderConfig['show_overdue']) || $r['next_followup_date'] >= $now)
                        && (substr($r['next_followup_date'],0,10) <= $today || $r['next_followup_date'] <= $cutoff);
                }));
            }
            echo json_encode(['status'=>'success','server_now'=>$now,'orders'=>$rows], JSON_UNESCAPED_UNICODE);
        } elseif (in_array($action, ['details','save','review','cancel'], true)) {
            $psono = trim((string)($_POST['psono'] ?? ''));
            if (in_array($action, ['save','review','cancel'], true)) {
                if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals($_SESSION['draft_followup_csrf'], (string)($_POST['csrf'] ?? ''))) throw new RuntimeException('Refresh this page before saving.');
                if (!$krfconn->begin_transaction()) throw new RuntimeException('Unable to start cancellation/follow-up transaction.');
                $fuTransactionStarted = true;
            }
            $order = fuQuery($krfconn, "SELECT p.* FROM krf_provisional_sales_order p WHERE $fuWhere AND p.psono=?" . ($action !== 'details' ? ' FOR UPDATE' : ''), array_merge($fuScope, [$psono]));
            if (count($order) !== 1) throw new RuntimeException('Draft order is not available for this login.');
            if ($action === 'cancel') {
                if (!$fuAdmin || !(int)$order[0]['followup_review_pending']) throw new RuntimeException('Only an authorised administrator can cancel a case pending review.');
                $cancelRemarks = trim((string)($_POST['conversation'] ?? ''));
                if ($cancelRemarks === '') throw new RuntimeException('Enter the cancellation reason or remarks.');
                $cancelAt = date('Y-m-d H:i:s');
                $cancelNote = 'PSO ' . $psono . ' cancelled by ' . $loginname . ' (' . $loginid . ') on ' . $cancelAt . ': ' . $cancelRemarks;
                // The Draft PSO was locked above and scoped to the current company/project.
                fuQuery($krfconn, "UPDATE krf_provisional_sales_order SET status='Cancelled', followup_review_pending=0, updated_by=?, updated_at=? WHERE id=?", [$loginid,$cancelAt,$order[0]['id']]);
                // Reuse the supplied consumed-allocation cancellation behavior.
                fuQuery($krfconn, "UPDATE krf_productionorder_consumed SET confirmstatus='cancel', status='cancel', updated_at=? WHERE projectid=? AND psono=?", [$cancelAt,$projectid,$psono]);
                // Cancel production stock orders belonging to this PSO within the current scope.
                fuQuery($krfconn, "UPDATE krf_production_stockorder SET status='Cancelled', sowingdate=NULL, sowingstatus=NULL, scionsowingdate=NULL, workingstatus=NULL, updated_at=?,
                    remarks=CONCAT(COALESCE(remarks,''), CASE WHEN COALESCE(remarks,'')='' THEN '' ELSE CHAR(10) END, ?)
                    WHERE companyid=? AND subcompanyid=? AND projectid=? AND psono=?",
                    [$cancelAt,$cancelNote,$companyid,$subcompanyid,$projectid,$psono]);
                fuQuery($krfconn, 'INSERT INTO krf_provisional_order_followup (projectid,psono,clientid,reason,conversation,next_followup_date,created_by,created_by_name,created_at) VALUES (?,?,?,?,?,?,?,?,?)',
                    [$projectid,$psono,$order[0]['clientid'],'Administrator Cancelled',$cancelRemarks,null,$loginid,$loginname,$cancelAt]);
                if (!$krfconn->commit()) throw new RuntimeException('Unable to commit cancellation.');
                $fuTransactionStarted = false;
                echo json_encode(['status'=>'success','message'=>'Provisional order cancelled successfully.']);
            } elseif ($action === 'details') {
                $items = fuQuery($krfconn, 'SELECT product_name,quantity,rate,discount_percent,discount_amount,amount,expecteddeliverydate FROM krf_provisional_sales_order_items WHERE psono=? AND projectid=? ORDER BY id', [$psono,$projectid]);
                $history = fuQuery($krfconn, 'SELECT reason,conversation,next_followup_date,created_by,created_by_name,created_at FROM krf_provisional_order_followup WHERE psono=? AND projectid=? ORDER BY created_at DESC,id DESC', [$psono,$projectid]);
                echo json_encode(['status'=>'success','order'=>$order[0],'items'=>$items,'history'=>$history,'default_followup_date'=>null], JSON_UNESCAPED_UNICODE);
            } else {
                if ($action === 'review' && (!$fuAdmin || !(int)$order[0]['followup_review_pending'])) throw new RuntimeException('This case is not pending administrator review.');
                $actorName = (string)$loginname;
                $reason = trim((string)($_POST['reason'] ?? ''));
                $conversation = trim((string)($_POST['conversation'] ?? ''));
                $next = trim((string)($_POST['next_followup_date'] ?? ''));
                $istZone = new DateTimeZone('Asia/Kolkata');
                $parsed = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $next, $istZone);
                if (!in_array($reason,$reasons,true)) throw new RuntimeException('Choose a reason.');
                if ($reason === 'Not Interested' && (int)$order[0]['followup_review_pending'] === 1) throw new RuntimeException('This case has already been forwarded to the administration team for review. Choose another follow-up reason.');
                if ($conversation === '') throw new RuntimeException('Enter the conversation or remarks.');
                if ($reason !== 'Not Interested' && (!$parsed || $parsed->format('Y-m-d\TH:i') !== $next || $parsed < (new DateTimeImmutable('now',new DateTimeZone('Asia/Kolkata')))->modify('+15 minutes') || $parsed->format('Y-m-d') > (new DateTimeImmutable('tomorrow', $istZone))->format('Y-m-d'))) throw new RuntimeException('Choose a follow-up at least 15 minutes from now, for today or tomorrow.');
                $next = $reason === 'Not Interested' ? null : $parsed->format('Y-m-d H:i:s');
                fuQuery($krfconn, 'INSERT INTO krf_provisional_order_followup (projectid,psono,clientid,reason,conversation,next_followup_date,created_by,created_by_name,created_at) VALUES (?,?,?,?,?,?,?,?,?)', [$projectid,$psono,$order[0]['clientid'],$reason,$conversation,$next,$loginid,$actorName,date('Y-m-d H:i:s')]);
                if ($reason === 'Not Interested') {
                    // Separate audit event: records when and by whom the case was sent.
                    fuQuery($krfconn, 'INSERT INTO krf_provisional_order_followup (projectid,psono,clientid,reason,conversation,next_followup_date,created_by,created_by_name,created_at) VALUES (?,?,?,?,?,?,?,?,?)',
                        [$projectid,$psono,$order[0]['clientid'],'Sent for Administrator Review',
                         'Case forwarded to the administration team for review because the customer is not interested.',
                         null,$loginid,$actorName,date('Y-m-d H:i:s')]);
                }
                if ($reason === 'Not Interested' || $action === 'review') fuQuery($krfconn, 'UPDATE krf_provisional_sales_order SET followup_review_pending=? WHERE id=?', [$action === 'review' ? '0' : '1', $order[0]['id']]);
                if (!$krfconn->commit()) throw new RuntimeException('Unable to commit follow-up.');
                $fuTransactionStarted = false;
                echo json_encode(['status'=>'success']);
            }
        } else throw new RuntimeException('Invalid request.');
    } catch (Throwable $e) {
        if ($fuTransactionStarted) $krfconn->rollback();
        http_response_code(400);
        echo json_encode(['status'=>'error','message'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}
echo $followupHeader;
?>
<style>
.fu {
    padding: 20px;
    color: #243348;
    font: 14px 'Outfit', Arial, sans-serif;
}

.fu * {
    box-sizing: border-box;
}

.fu-card,
.fu-dialog {
    background: #fff;
    padding: 18px;
    border-radius: 10px;
}

.fu-card {
    border: 1px solid #dde3eb;
}

.fu-top,
.fu-legend,
.fu-save-row {
    display: flex;
    gap: 12px;
}

.fu-top {
    justify-content: space-between;
    align-items: center;
}

.fu h3 {
    margin: 0 0 8px;
}

.fu-legend {
    flex-wrap: wrap;
    gap: 10px;
    margin: 14px 0;
}

.fu-tag {
    padding: 5px 10px;
    border-radius: 6px;
    font-size: 12px;
}

.fu-overdue { background: #fee2e2 !important; }
.fu-review { background:#fff0c2 !important; }
.fu .fu-due-red{color:#b91c1c;font-weight:700}
.fu input[type=radio],.fu input[type=checkbox]{width:auto;transform:none}
.fu-review-controls{padding:12px;background:#fff0c2;margin:10px 0}
.fu-filter-panel{position:fixed;z-index:99999;background:white;border:1px solid #aaa;padding:12px;max-height:70vh;overflow:auto;width:280px;box-shadow:0 8px 24px #0003}
.fu-filter-panel label{display:flex;gap:10px;align-items:center}
.fu-filter-panel input[type=search]{width:100%}
.fu-filter-btn{margin-left:6px;padding:2px 6px!important}
.fu-today   { background: #fee2e2 !important; }
.fu-future  { background: #e8f6ed !important; }
.fu-none    { background: #f2f4f7 !important; }
/* Colour every cell so shared table/striped/hover CSS cannot mask the row. */
.fu #fu-grid tbody tr.fu-overdue > td { background-color:#fee2e2 !important; }
.fu #fu-grid tbody tr.fu-review > td { background-color:#fff0c2 !important; }
.fu #fu-grid tbody tr.fu-today > td { background-color:#fee2e2 !important; }
.fu #fu-grid tbody tr.fu-future > td { background-color:#e8f6ed !important; }
.fu #fu-grid tbody tr.fu-none > td { background-color:#f2f4f7 !important; }
.fu #fu-grid tbody tr > td { box-shadow:none !important; }
.fu #fu-grid button { background:#fff !important; color:#20313b !important; }


.fu-scroll {
    overflow: auto;
}

.fu table {
    width: 100%;
    border-collapse: collapse;
}

.fu th,
.fu td {
    padding: 10px;
    border-bottom: 1px solid #dfe5ed;
    text-align: left;
}

.fu th {
    background: #eef2f7;
    white-space: nowrap;
}

.fu button,
.fu input,
.fu select,
.fu textarea {
    border: 1px solid #cbd5e1;
    border-radius: 6px;
}

.fu button {
    padding: 8px 12px;
    background: #fff;
    cursor: pointer;
}

.fu button:disabled {
    opacity: .6;
    cursor: wait;
}

.fu-contact {
    color: #175a9e;
    font-weight: 600;
    text-decoration: underline;
}

.fu-empty,
.fu-meta {
    color: #667085;
}

.fu-empty {
    padding: 20px;
}

.fu-meta {
    font-size: 12px;
}

.fu-overlay {
    position: fixed;
    inset: 0;
    z-index: 2000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 18px;
    background: #17213488;
}

.fu-overlay[hidden] {
    display: none;
}

.fu-dialog {
    width: min(1200px, 100%);
    max-height: 94vh;
    overflow: auto;
    padding: 20px;
    border-radius: 12px;
}

.fu-columns {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 22px;
    margin-top: 20px;
}

.fu label {
    display: block;
    margin: 12px 0 6px;
    font-weight: 600;
}

.fu input,
.fu select,
.fu textarea {
    width: 100%;
    padding: 9px;
    font: inherit;
}

.fu textarea {
    min-height: 145px;
    resize: vertical;
}

.fu-history {
    max-height: 390px;
    overflow: auto;
}

.fu-log {
    margin-bottom: 12px;
    padding: 12px;
    border: 1px solid #dde3eb;
    border-left: 4px solid #5895c9;
    border-radius: 7px;
}

.fu-log p {
    margin: 9px 0;
    white-space: pre-wrap;
    overflow-wrap: anywhere;
}

.fu-log .fu-meta {
    color: #6d28d9;
    font-weight: 700;
}

.fu-save {
    background: #21639a !important;
    color: #fff;
}

.fu-save-row {
    align-items: flex-end;
    margin-top: 12px;
}

.fu-date-field {
    flex: 1;
    min-width: 0;
}

.fu-save-row .fu-save {
    min-height: 40px;
    white-space: nowrap;
}

.fu-error,
#fu-save-message {
    margin-top: 10px;
}

.fu-error {
    color: #963f21;
}

@media (max-width: 760px) {
    .fu {
        padding: 10px;
    }

    .fu-overlay {
        padding: 7px;
    }

    .fu-dialog {
        padding: 13px;
    }

    .fu-columns {
        grid-template-columns: 1fr;
    }

    .fu-save-row {
        flex-direction: column;
        align-items: stretch;
    }

    .fu-save-row .fu-save {
        width: 100%;
    }
}
.fu-modal-actions{display:flex;gap:8px;flex-wrap:wrap}
.fu-history-heading{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px}.fu-history-heading h4{margin:0}.fu-history-heading button{white-space:nowrap}
</style>
<div class="fu">
<div class="fu-card">
<div class="fu-top"><div><h3><?= $fuAdmin ? "Administrator Follow-ups" : "Order Follow-ups" ?></h3></div><button id="fu-refresh" type="button">Refresh</button></div>
<div class="fu-legend"><span class="fu-tag fu-overdue">Today / Overdue</span><span class="fu-tag fu-review">Awaiting review</span><span class="fu-tag fu-none">No follow-up</span><span class="fu-tag fu-future">Upcoming</span></div>
<div class="fu-grid-tools" style="display:flex;gap:12px;flex-wrap:wrap;margin:12px 0">
<?php if ($fuAdmin): ?><select id="fu-user-filter" aria-label="Filter by user"><option value="">All users</option></select><?php endif; ?>
<input type="search" id="fu-search" aria-label="Search follow-ups" placeholder="Search client, contact, PSO or reason…" style="flex:1;min-width:220px">
</div>
<div id="fu-message" role="status"></div>
<div class="fu-scroll"><table id="fu-grid"><thead><tr><?php if ($fuAdmin): ?><th>User Name</th><?php endif; ?><th>Client Name</th><th>Contact No.</th><th>PSO No.</th><th>Created Date</th><th>Next Follow-up</th><th>Last Reason</th><th>Action</th></tr></thead><tbody id="fu-orders"></tbody></table></div>
</div>
<div class="fu-overlay" id="fu-modal" hidden><div class="fu-dialog" role="dialog" aria-modal="true" aria-labelledby="fu-title" tabindex="-1">
<div class="fu-top"><h3 id="fu-title">Order Follow-up</h3><div class="fu-modal-actions"><button type="button" id="fu-close" aria-label="Close follow-up">Close</button></div></div>
<div id="fu-client"></div>
<div id="fu-details-message" role="status"></div>
<div class="fu-scroll"><table><thead><tr><th>Product Name</th><th>Qty</th><th>Rate</th><th>Discount %</th><th>Discount Amount</th><th>Amount</th><th>Requested Delivery Date</th></tr></thead><tbody id="fu-items"></tbody></table></div>
<div class="fu-columns"><form id="fu-form"><h4>Record conversation</h4><label for="fu-reason">Reason</label><select id="fu-reason" required><option value="">Choose reason</option><?php foreach ($reasons as $reason): ?><option><?= htmlspecialchars($reason,ENT_QUOTES,'UTF-8') ?></option><?php endforeach; ?><?php if ($fuAdmin): ?><option value="Cancel the order">Cancel the order</option><?php endif; ?></select><label for="fu-conversation">Conversation / remarks</label><textarea id="fu-conversation" required></textarea><div class="fu-save-row"><div class="fu-date-field"><label for="fu-next">Next Follow-up</label><input type="datetime-local" id="fu-next" step="60"  max="<?=(new DateTimeImmutable('tomorrow',new DateTimeZone('Asia/Kolkata')))->format('Y-m-d')?>T23:59" required></div><button class="fu-save" id="fu-save" type="submit">Save Follow-up</button></div><div id="fu-save-message" role="status"></div></form><div><div class="fu-history-heading"><h4>Follow-up history</h4><button type="button" id="fu-print" disabled>Print / PDF</button></div><div id="fu-history" class="fu-history"></div></div></div>
</div></div></div>
<script src="followup_sweetalert.js?v=20261006-1"></script>
<script>
(function(){
const admin=<?= $fuAdmin ? 'true' : 'false' ?>;
const csrf=<?=json_encode($_SESSION['draft_followup_csrf'])?>;
const esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const date=v=>{if(!v)return '—';const d=String(v).slice(0,10).split('-');return d.length===3?d.reverse().join('-'):String(v)};
const stamp=v=>v?date(v)+' · '+String(v).slice(11,16):'—';
const followupStamp=v=>{if(!v)return '—';const text=String(v);if(text.length<16)return date(text);const hour=Number(text.slice(11,13));return date(text)+' '+String(hour%12||12).padStart(2,'0')+':'+text.slice(14,16)+' '+(hour>=12?'PM':'AM')};
const money=v=>Number(v||0).toLocaleString('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2});
let gridRows=[],pendingReview=false,autoOpenDone=false;
let selected='',previousFocus=null,listXhr=null,detailsXhr=null,saving=false;
function api(action,data){const url=new URL(window.location.href);url.searchParams.set('fu_action',action);return $.ajax({url:url.toString(),type:'POST',dataType:'json',data:data||{}})}
function error(xhr){return xhr.responseJSON?.message||'Unable to complete the request. Please try again.'}
function renderGrid(){
 const styles=['fu-overdue','fu-today','fu-none','fu-future'];
 const query=String($('#fu-search').val()||'').trim().toLowerCase();
 const owner=String($('#fu-user-filter').val()||'');
 const rows=gridRows.filter(o=>(!owner||String(o.created_by)===owner)&&(!query||[o.created_by_name,o.clientname,o.mobileno,o.psono,date(o.created_at),followupStamp(o.next_followup_date),o.reason].some(v=>String(v||'').toLowerCase().includes(query)))&&Object.entries(filters).every(([key,values])=>values.has(String(o[key]??''))));
 $('#fu-orders').html(rows.map(o=>{const phone=String(o.mobileno||'').replace(/[^\d+]/g,'');return `<tr class="${[0,1].includes(Number(o.priority))?styles[Number(o.priority)]:(Number(o.followup_review_pending)?'fu-review':(styles[Number(o.priority)]||'fu-none'))}">${admin?`<td>${esc(o.created_by_name)}</td>`:''}<td>${esc(o.clientname)}</td><td>${phone?`<a class="fu-contact" data-order="${esc(o.psono)}" href="tel:${esc(phone)}">${esc(o.mobileno)}</a>`:'—'}</td><td>${esc(o.psono)}</td><td>${date(o.created_at)}</td><td class="${o.priority===0?'fu-due-red':''}">${o.no_follow_required?'No follow required':followupStamp(o.next_followup_date)}</td><td>${esc(o.reason||'—')}${Number(o.followup_review_pending)?'<br><b>Pending administrator review</b>':''}</td><td><button type="button" class="fu-open" data-order="${esc(o.psono)}">Follow up</button></td></tr>`}).join(''));
 // Inline !important wins over shared striped/hover cell backgrounds.
 const rowColours={'fu-overdue':'#fee2e2','fu-review':'#fff0c2','fu-today':'#fee2e2','fu-future':'#e8f6ed','fu-none':'#f2f4f7'};
 document.querySelectorAll('#fu-orders > tr').forEach(tr=>{
  const colour=rowColours[Object.keys(rowColours).find(name=>tr.classList.contains(name))];
  if(!colour)return;
  [tr,...tr.cells].forEach(node=>{
   node.style.setProperty('background',colour,'important');
   node.style.setProperty('background-color',colour,'important');
   node.style.setProperty('background-image','none','important');
   node.style.setProperty('box-shadow','none','important');
   if(tr.classList.contains('fu-overdue')||tr.classList.contains('fu-today'))node.style.setProperty('color','#991b1b','important');
  });
  if(tr.classList.contains('fu-overdue')||tr.classList.contains('fu-today'))tr.querySelectorAll('td a').forEach(node=>node.style.setProperty('color','#991b1b','important'));
 });
 $('#fu-message').text(rows.length+' of '+gridRows.length+' Draft order(s)');
}
const filters={};
$('#fu-search').on('input',renderGrid);$('#fu-user-filter').on('change',renderGrid);
function loadList(){if(listXhr)listXhr.abort();$('#fu-message').text('Loading Draft orders…');listXhr=api('list').done(res=>{gridRows=res.orders;
 if(admin){const current=$('#fu-user-filter').val()||'';const owners=new Map(gridRows.map(o=>[String(o.created_by),o.created_by_name||o.created_by]));$('#fu-user-filter').empty().append($('<option>').val('').text('All users'));[...owners].sort((a,b)=>String(a[1]).localeCompare(String(b[1]))).forEach(([id,name])=>$('#fu-user-filter').append($('<option>').val(id).text(name)));$('#fu-user-filter').val(owners.has(current)?current:'');}
 renderGrid();if(!autoOpenDone){autoOpenDone=true;const requested=new URL(window.location.href).searchParams.get('open_psono');if(requested&&gridRows.some(o=>String(o.psono)===requested))open(requested)}}).fail((xhr,status)=>{if(status!=='abort')$('#fu-message').text(error(xhr))})}
// Per-column Excel-style value selection, with its popup above sticky headers.
{
 const keys=[...(admin?['created_by_name']:[]),'clientname','mobileno','psono','created_at','next_followup_date','reason'];
 $('#fu-grid thead th').each(function(i){if(keys[i])$(this).append($('<button type="button" class="fu-filter-btn">▾</button>').attr('data-key',keys[i]).attr('aria-label','Filter '+$(this).text()));});
 $('#fu-grid').on('click','.fu-filter-btn',function(){
  $('.fu-filter-panel').remove();const key=this.dataset.key,values=[...new Set(gridRows.map(o=>String(o[key]??'')))].sort(),r=this.getBoundingClientRect();
  const panel=$('<div class="fu-filter-panel" role="dialog" aria-label="Column filter"></div>').css({top:Math.min(r.bottom,window.innerHeight-300),left:Math.max(0,Math.min(r.left,window.innerWidth-290))});
  panel.append('<input type="search" placeholder="Search values"><button type="button" class="all">Select all</button><button type="button" class="none">Clear selection</button>');
  const options=$('<div></div>');values.forEach(v=>options.append($('<label></label>').append($('<input type="checkbox">').val(v).prop('checked',!filters[key]||filters[key].has(v))).append(document.createTextNode(v||'(Blank)'))));panel.append(options).append('<button type="button" class="apply">Apply</button><button type="button" class="reset">Reset filter</button><button type="button" class="close-filter">Close</button>');$('.fu').append(panel);
  panel.on('input','input[type=search]',function(){const q=this.value.toLowerCase();options.children().each(function(){$(this).toggle($(this).text().toLowerCase().includes(q))})});
  panel.on('click','.all,.none',function(){options.find('input').prop('checked',this.classList.contains('all'))});
  panel.on('click','.apply',function(){filters[key]=new Set(options.find('input:checked').map(function(){return this.value}).get());panel.remove();renderGrid()});
  panel.on('click','.reset',function(){delete filters[key];panel.remove();renderGrid()});panel.on('click','.close-filter',()=>panel.remove());
 });
}
function details(){$('#fu-print').prop('disabled',true);if(detailsXhr)detailsXhr.abort();const psono=selected;$('#fu-details-message').text('Loading order details…');$('#fu-save').prop('disabled',true);detailsXhr=api('details',{psono}).done(res=>{if(selected!==psono)return;pendingReview=Number(res.order.followup_review_pending)===1;$('#fu-next').val('');$('#fu-review-controls').prop('hidden',!pendingReview);$('#fu-details-message').text('');$('#fu-client').text(res.order.clientname+' · '+res.order.mobileno+' · PSO '+psono);$('#fu-items').html(res.items.map(i=>`<tr><td>${esc(i.product_name)}</td><td>${esc(i.quantity)}</td><td>${money(i.rate)}</td><td>${esc(i.discount_percent)}</td><td>${money(i.discount_amount)}</td><td>${money(i.amount)}</td><td>${date(i.expecteddeliverydate)}</td></tr>`).join(''));$('#fu-history').html(res.history.length?res.history.map(h=>`<article class="fu-log"><strong>${stamp(h.created_at)}</strong><div class="fu-meta">By ${esc(h.created_by_name || 'Name not recorded')}</div><div style="margin-top:8px"><b>Reason:</b> ${esc(h.reason)}</div><p>${esc(h.conversation)}</p><div><b>Next follow-up:</b> ${!h.next_followup_date&&['Not Interested','Sent for Administrator Review'].includes(h.reason)?'No follow required':followupStamp(h.next_followup_date)}</div></article>`).join(''):'<div class="fu-empty">No follow-up recorded yet.</div>');syncNext();$('#fu-save').prop('disabled',false);$('#fu-print').prop('disabled',false)}).fail((xhr,status)=>{if(status!=='abort')$('#fu-details-message').text(error(xhr))})}
function open(psono){if(saving)return;previousFocus=document.activeElement;selected=String(psono);$('#fu-form')[0].reset();$('#fu-next').val('');$('input[name=fu-decision]').prop('checked',false);pendingReview=false;$('#fu-review-controls').prop('hidden',true);syncNext();$('#fu-save-message,#fu-client,#fu-items,#fu-history').empty();$('#fu-title').text('Follow-up · '+selected);$('#fu-modal').prop('hidden',false);$('.fu-dialog')[0].focus();details()}
function close(){if(saving)return;$('#fu-modal').prop('hidden',true);selected='';if(detailsXhr)detailsXhr.abort();if(previousFocus)previousFocus.focus()}
$(document).on('click','.fu-contact,.fu-open',function(){open($(this).attr('data-order'))});
$('#fu-close').on('click',close);$('#fu-refresh').on('click',loadList);
$('#fu-print').on('click',function(){
    if (!selected || this.disabled) return;
    const oldFrame = document.getElementById('fu-print-frame');
    if (oldFrame) oldFrame.remove();
    const frame = document.createElement('iframe');
    frame.id = 'fu-print-frame';
    frame.title = 'Printable follow-up log';
    frame.setAttribute('aria-hidden','true');
    frame.style.cssText = 'position:fixed;left:-10000px;top:0;width:900px;height:700px;border:0;';
    document.body.appendChild(frame);
    const popup = frame.contentWindow;
    const doc = frame.contentDocument;
    doc.open();
    doc.write('<!doctype html><html><head><meta charset="utf-8"><title>Order Follow-up Log</title></head><body></body></html>');
    doc.close();
    doc.title = 'Follow-up Log - ' + selected;
    const style = doc.createElement('style');
    style.textContent = '@page{size:A4;margin:15mm}body{font:14px Arial,sans-serif;color:#243348;margin:0;padding:20px}h2{margin:0 0 12px}.print-generated{font-size:12px;color:#667085;margin:10px 0 16px}.fu-log{border:1px solid #cbd5e1;border-left:4px solid #5895c9;border-radius:6px;padding:12px;margin:0 0 12px;break-inside:avoid;page-break-inside:avoid}.fu-log .fu-meta{color:#6d28d9;font-weight:700;margin-top:4px}.fu-log p{white-space:pre-wrap;overflow-wrap:anywhere;margin:9px 0}@media print{body{padding:0}}';
    doc.head.appendChild(style);
    const heading = doc.createElement('h2');
    heading.textContent = 'Order Follow-up Log';
    doc.body.appendChild(heading);
    const client = doc.createElement('div');
    client.textContent = $('#fu-client').text();
    doc.body.appendChild(client);
    const generated = doc.createElement('div');
    generated.className = 'print-generated';
    generated.textContent = 'Printed: ' + new Intl.DateTimeFormat('en-IN',{timeZone:'Asia/Kolkata',day:'2-digit',month:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit'}).format(new Date());
    doc.body.appendChild(generated);
    const history = doc.createElement('div');
    document.querySelectorAll('#fu-history > *').forEach(entry => history.appendChild(doc.importNode(entry,true)));
    doc.body.appendChild(history);
    popup.requestAnimationFrame(function(){popup.requestAnimationFrame(function(){popup.focus();popup.print();});});
});

function fuMinFollowup(){const t=Math.ceil((Date.now()+15*60000)/60000)*60000;const parts=new Intl.DateTimeFormat('en-GB',{timeZone:'Asia/Kolkata',year:'numeric',month:'2-digit',day:'2-digit',hour:'2-digit',minute:'2-digit',hourCycle:'h23'}).formatToParts(new Date(t));const p=Object.fromEntries(parts.map(x=>[x.type,x.value]));return `${p.year}-${p.month}-${p.day}T${p.hour}:${p.minute}`;}
function fuRefreshMin(){const min=fuMinFollowup();$('#fu-next').attr('min',min);return min;}
function syncNext(){fuRefreshMin();
 const notInterested=$('#fu-reason option').filter(function(){return this.value==='Not Interested';});
 notInterested.prop('disabled',pendingReview).prop('hidden',pendingReview);
 const cancelOption=$('#fu-reason option[value="Cancel the order"]');
 cancelOption.prop('disabled',!pendingReview).prop('hidden',!pendingReview);
 if((pendingReview&&$('#fu-reason').val()==='Not Interested')||(!pendingReview&&$('#fu-reason').val()==='Cancel the order'))$('#fu-reason').val('');
 const cancel=admin&&pendingReview&&$('#fu-reason').val()==='Cancel the order';
 const none=cancel||$('#fu-reason').val()==='Not Interested';
 $('#fu-reason').prop('required',true).prop('disabled',false).show();$('label[for=fu-reason]').show();
 $('#fu-next').prop('required',!none).prop('disabled',none);$('.fu-date-field').toggle(!none);
 $('#fu-save').text(cancel?'Cancel the order':'Save Follow-up');
}
$('#fu-reason').on('change',function(){
    syncNext();
    if (this.value !== 'Needs Order Change' || !selected || saving) return;
    const root = String($('body').data('routurl') || '').replace(/\/?$/, '/');
    const url = new URL(root + 'krf_provisional_sale_order_edit/edit_provisional_sale_order.php', window.location.origin);
    const current = new URL(window.location.href);
    url.searchParams.set('k', current.searchParams.get('k') || String($('body').data('key') || ''));
    url.searchParams.set('mdid', current.searchParams.get('mdid') || '');
    url.searchParams.set('orderno', selected);
    url.searchParams.set('return_to', 'draft_followup');
    url.searchParams.set('followup_admin', admin ? '1' : '0');
    url.searchParams.set('iframe', 'true');
    window.location.href = url.toString();
});

$(document).on('keydown',function(e){if($('#fu-modal').prop('hidden')||(window.Swal&&window.Swal.isVisible()))return;if(e.key==='Escape')close();if(e.key==='Tab'){const nodes=$('.fu-dialog').find('a,button,input,select,textarea,[tabindex]').filter(':visible:not(:disabled)').toArray();const first=nodes[0],last=nodes[nodes.length-1];if(e.shiftKey&&(document.activeElement===first||document.activeElement===$('.fu-dialog')[0])){e.preventDefault();last.focus()}else if(!e.shiftKey&&document.activeElement===last){e.preventDefault();first.focus()}}});
$('#fu-form').on('submit',async function(e){
 e.preventDefault();
 const decision=admin&&pendingReview?($('#fu-reason').val()==='Cancel the order'?'cancel':'review'):'';
 if(saving||!selected)return;syncNext();if(!this.reportValidity())return;if($('#fu-next').prop('required')&&(!Number.isFinite(Date.parse($('#fu-next').val()+':00+05:30'))||Date.parse($('#fu-next').val()+':00+05:30')<Date.now()+15*60000)){$('#fu-save-message').text('Next follow-up must be at least 15 minutes from now.');return;}
 saving=true;
 $('#fu-save').prop('disabled',true);$('#fu-close').prop('disabled',true);
 try {
  if(decision==='cancel'){
   const result=await window.krfFollowupSweetAlert({title:'Cancel the order?',text:'Cancel PSO '+selected+' and its matching production stock order?',icon:'warning',showCancelButton:true,confirmButtonText:'Yes, cancel the order',cancelButtonText:'Keep the order',confirmButtonColor:'#dc3545',focusCancel:true});
   if(!result.isConfirmed)return;
  }
  $('#fu-save').text('Saving…');
  const psono=selected;
  await api(decision==='cancel'?'cancel':decision==='review'?'review':'save',{psono,csrf,loginname:$('#hidden_loginname').val() || '',reason:$('#fu-reason').val(),conversation:$('#fu-conversation').val(),next_followup_date:$('#fu-next').val()});
  saving=false;close();$('#fu-form')[0].reset();loadList();
  if(typeof window.alertmessage==='function')window.alertmessage('success',decision==='cancel'?'Order cancelled successfully.':'Follow-up saved successfully.');
 } catch(xhr) {
  const message=xhr instanceof Error?xhr.message:error(xhr);
  $('#fu-save-message').text(message);
  if(typeof window.alertmessage==='function')await window.alertmessage('warning',message);
 } finally {
  saving=false;$('#fu-save').prop('disabled',false);syncNext();$('#fu-close').prop('disabled',false);
 }
});
loadList();
const returnParams = new URL(window.location.href);
const returnPsono = returnParams.searchParams.get('open_psono');
if (returnPsono) {
    returnParams.searchParams.delete('open_psono');
    window.history.replaceState(null, '', returnParams.toString());
    open(returnPsono);
}
})();
</script>
<?php include('../footer.php'); ?>
