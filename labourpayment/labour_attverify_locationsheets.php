<?php
require('header.php');
$records = [];
$res = mysqli_query($hrmconn,
    "SELECT * FROM hrm_attverify_locationsheets ORDER BY sno DESC"
);
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $records[] = $row;
    }
}

$total    = count($records);
$active   = count(array_filter($records, fn($r) => $r['status'] == 1));
$inactive = $total - $active;
$projects = count(array_unique(array_filter(array_column($records, 'projectid'))));
?>

<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">

<style>
/* ── Page-level overrides (inside labourpayment theme) ─────────────────────── */
.page-header-box {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 12px; margin-bottom: 24px;
}
.page-header-box .page-title h4 {
    font-size: 1.25rem; font-weight: 800; color: #1e293b; margin: 0;
}
.page-header-box .page-title p {
    margin: 2px 0 0; color: #64748b; font-size: 0.82rem;
}

/* ── Stat Cards ────────────────────────────────────────────────────────────── */
.ls-stats-row {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 14px; margin-bottom: 22px;
}
.ls-stat {
    background: #fff; border-radius: 12px;
    padding: 16px 18px; box-shadow: 0 2px 8px rgba(0,0,0,0.07);
    display: flex; align-items: center; gap: 12px;
    border-left: 4px solid transparent; transition: transform .2s;
}
.ls-stat:hover { transform: translateY(-2px); }
.ls-stat.blue   { border-color: #3b82f6; }
.ls-stat.green  { border-color: #22c55e; }
.ls-stat.red    { border-color: #ef4444; }
.ls-stat.purple { border-color: #8b5cf6; }
.ls-stat-icon {
    width: 40px; height: 40px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center; font-size: 18px;
}
.ls-stat.blue   .ls-stat-icon { background:#eff6ff; color:#3b82f6; }
.ls-stat.green  .ls-stat-icon { background:#f0fdf4; color:#22c55e; }
.ls-stat.red    .ls-stat-icon { background:#fef2f2; color:#ef4444; }
.ls-stat.purple .ls-stat-icon { background:#f5f3ff; color:#8b5cf6; }
.ls-stat-num   { font-size: 1.5rem; font-weight: 800; color: #1e293b; line-height: 1; }
.ls-stat-label { font-size: 0.73rem; color: #64748b; margin-top: 2px; }

/* ── Card wrapper ──────────────────────────────────────────────────────────── */
.ls-card {
    background: #fff; border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.07); overflow: hidden;
}
.ls-card-head {
    padding: 16px 20px;
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;
    background: linear-gradient(135deg,#f8fafc,#f1f5f9);
    border-bottom: 1px solid #e2e8f0;
}
.ls-card-head h5 {
    margin: 0; font-size: 0.95rem; font-weight: 700; color: #1e293b;
    display: flex; align-items: center; gap: 7px;
}
.ls-card-head h5 i { color: #3b82f6; }

/* ── Filter bar ────────────────────────────────────────────────────────────── */
.ls-filter { display: flex; gap: 8px; flex-wrap: wrap; }
.ls-filter input, .ls-filter select {
    border: 1.5px solid #e2e8f0; border-radius: 7px;
    padding: 7px 11px; font-size: 0.82rem; outline: none;
    transition: border-color .2s; background: #fff;
}
.ls-filter input:focus, .ls-filter select:focus {
    border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
}

/* ── Table ─────────────────────────────────────────────────────────────────── */
.ls-table-wrap { overflow-x: auto; }
.ls-table { width: 100%; border-collapse: collapse; font-size: 0.83rem; }
.ls-table thead th {
    background: linear-gradient(135deg,#1e293b,#334155);
    color: #cbd5e1; font-weight: 600;
    padding: 11px 13px; text-align: left;
    font-size: 0.75rem; letter-spacing: .5px; text-transform: uppercase;
    position: sticky; top: 0; z-index: 2; border: none !important;
}
.ls-table tbody tr { border-bottom: 1px solid #f1f5f9; transition: background .15s; }
.ls-table tbody tr:hover { background: #f8faff !important; }
.ls-table tbody td { padding: 10px 13px; color: #374151; vertical-align: middle; border: none !important; }
.ls-table tbody tr:nth-child(even) { background: #fafbff; }

.sno-pill {
    background: linear-gradient(135deg,#e0e7ff,#c7d2fe);
    color: #4338ca; font-weight: 700; font-size: 0.73rem;
    padding: 3px 8px; border-radius: 6px; display: inline-block;
}
.prj-pill {
    background: #f0fdf4; color: #15803d; font-weight: 600; font-size: 0.75rem;
    padding: 3px 9px; border-radius: 20px; display: inline-block;
}
.gs-link {
    color: #3b82f6; text-decoration: none; font-size: 0.78rem; font-weight: 500;
    background: #eff6ff; padding: 3px 9px; border-radius: 20px;
    display: inline-flex; align-items: center; gap: 5px;
    max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    transition: all .2s;
}
.gs-link:hover { background: #dbeafe; color: #1d4ed8; text-decoration: none; }
.sheet-pill {
    background: #f8fafc; border: 1px solid #e2e8f0;
    padding: 3px 8px; border-radius: 6px; font-size: 0.78rem; font-family: monospace;
}
.status-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 9px; border-radius: 20px; font-size: 0.73rem; font-weight: 600;
}
.status-on  { background: #dcfce7; color: #15803d; }
.status-off { background: #fee2e2; color: #b91c1c; }
.action-btns { display: flex; gap: 5px; }
.abn {
    width: 30px; height: 30px; border-radius: 7px; border: none;
    display: inline-flex; align-items: center; justify-content: center;
    cursor: pointer; font-size: 12px; transition: all .2s;
}
.abn-edit   { background:#eff6ff; color:#3b82f6; }
.abn-edit:hover   { background:#3b82f6; color:#fff; }
.abn-del    { background:#fef2f2; color:#ef4444; }
.abn-del:hover    { background:#ef4444; color:#fff; }
.abn-on     { background:#f0fdf4; color:#22c55e; }
.abn-on:hover     { background:#22c55e; color:#fff; }
.abn-off    { background:#fef9c3; color:#ca8a04; }
.abn-off:hover    { background:#ca8a04; color:#fff; }

/* ── Add button ────────────────────────────────────────────────────────────── */
.btn-ls-add {
    background: linear-gradient(135deg,#3b82f6,#2563eb);
    color:#fff; border:none; border-radius:8px;
    padding:9px 16px; font-size:0.84rem; font-weight:600;
    cursor:pointer; display:inline-flex; align-items:center; gap:7px;
    box-shadow:0 2px 8px rgba(59,130,246,0.35); transition:all .2s;
}
.btn-ls-add:hover { transform:translateY(-1px); box-shadow:0 4px 14px rgba(59,130,246,0.45); }

/* ── Modal overlay ─────────────────────────────────────────────────────────── */
.ls-modal-bg {
    display: none; position: fixed; inset: 0;
    background: rgba(15,23,42,0.6); backdrop-filter: blur(4px);
    z-index: 9999; align-items: center; justify-content: center;
}
.ls-modal-bg.open { display: flex; }
.ls-modal {
    background: #fff; border-radius: 16px;
    width: 96%; max-width: 540px;
    box-shadow: 0 24px 60px rgba(0,0,0,0.22);
    animation: lsIn .22s ease;
    max-height: 92vh; overflow-y: auto;
}
@keyframes lsIn {
    from { transform: scale(0.9) translateY(-20px); opacity: 0; }
    to   { transform: scale(1) translateY(0);       opacity: 1; }
}
.ls-modal-hdr {
    padding: 18px 22px 14px;
    border-bottom: 1px solid #f1f5f9;
    display: flex; align-items: center; justify-content: space-between;
    position: sticky; top: 0; background: #fff; z-index: 2;
}
.ls-modal-hdr h5 {
    margin: 0; font-size: 1rem; font-weight: 700; color: #1e293b;
    display: flex; align-items: center; gap: 8px;
}
.ls-modal-hdr h5 i { color: #3b82f6; }
.ls-modal-close {
    width: 30px; height: 30px; border-radius: 7px;
    background: #f1f5f9; border: none; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: 15px; color: #64748b; transition: all .2s;
}
.ls-modal-close:hover { background: #ef4444; color: #fff; }
.ls-modal-body { padding: 18px 22px; }
.ls-modal-ftr  {
    padding: 12px 22px 18px;
    border-top: 1px solid #f1f5f9;
    display: flex; gap: 10px; justify-content: flex-end;
    position: sticky; bottom: 0; background: #fff;
}

/* ── Form ──────────────────────────────────────────────────────────────────── */
.ls-fg { margin-bottom: 14px; }
.ls-fg label { display: block; margin-bottom: 5px; font-size: 0.8rem; font-weight: 600; color: #374151; }
.ls-fg label .req { color: #ef4444; margin-left: 2px; }
.ls-fc {
    width: 100%; border: 1.5px solid #e2e8f0; border-radius: 8px;
    padding: 8px 12px; font-size: 0.86rem; outline: none;
    transition: border-color .2s; color: #1e293b; background: #fff;
}
.ls-fc:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
.ls-row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
@media (max-width: 480px) { .ls-row2 { grid-template-columns: 1fr; } }

.btn-ls-save {
    background: linear-gradient(135deg,#3b82f6,#2563eb);
    color:#fff; border:none; border-radius:8px;
    padding:9px 18px; font-size:0.84rem; font-weight:600;
    cursor:pointer; transition:all .2s; box-shadow:0 2px 8px rgba(59,130,246,0.3);
}
.btn-ls-save:hover { transform:translateY(-1px); }
.btn-ls-cancel {
    background:#f1f5f9; color:#475569; border:none; border-radius:8px;
    padding:9px 16px; font-size:0.84rem; font-weight:600; cursor:pointer; transition:all .2s;
}
.btn-ls-cancel:hover { background:#e2e8f0; }
.btn-ls-del {
    background:linear-gradient(135deg,#ef4444,#dc2626);
    color:#fff; border:none; border-radius:8px;
    padding:9px 18px; font-size:0.84rem; font-weight:600;
    cursor:pointer; box-shadow:0 2px 8px rgba(239,68,68,0.3); transition:all .2s;
}
.btn-ls-del:hover { transform:translateY(-1px); }

/* ── Delete confirm ────────────────────────────────────────────────────────── */
.del-confirm-icon {
    width: 60px; height: 60px; border-radius: 50%;
    background: #fef2f2; color: #ef4444; font-size: 26px;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 14px;
}

/* ── Empty state ───────────────────────────────────────────────────────────── */
.ls-empty { text-align:center; padding:56px 20px; color:#94a3b8; }
.ls-empty i { font-size:3rem; display:block; margin-bottom:12px; opacity:.4; }
.ls-empty h6 { color:#64748b; font-size:0.95rem; margin-bottom:4px; }
.ls-empty p  { font-size:0.8rem; }

/* ── Toast ─────────────────────────────────────────────────────────────────── */
#ls-toast-wrap {
    position: fixed; bottom: 22px; right: 22px;
    z-index: 19999; display: flex; flex-direction: column; gap: 9px;
}
.ls-toast {
    min-width: 260px; max-width: 340px;
    background: #1e293b; color: #fff;
    padding: 13px 16px; border-radius: 10px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.2);
    display: flex; align-items: center; gap: 10px;
    animation: lsToastIn .28s ease; font-size: 0.855rem;
}
@keyframes lsToastIn {
    from { transform:translateX(120%); opacity:0; }
    to   { transform:translateX(0);    opacity:1; }
}
.ls-toast.ok  .ti { color:#4ade80; font-size:16px; }
.ls-toast.err .ti { color:#f87171; font-size:16px; }
.ls-toast.inf .ti { color:#60a5fa; font-size:16px; }

.ls-bread { font-size:0.78rem; color:#94a3b8; margin-bottom:8px; }
.ls-bread a { color:#3b82f6; text-decoration:none; }
.ls-bread a:hover { text-decoration:underline; }
</style>

<div class="content container-fluid">
    <div class="ls-bread">
        <a href="index.php?k=<?php echo $key; ?>&mdid=<?php echo $mdid; ?>">
            <i class="ti ti-home"></i> Home
        </a>
        <span class="mx-1">/</span>
        <span>Attendance Verification</span>
        <span class="mx-1">/</span>
        <strong class="text-dark">Location Google Sheets</strong>
    </div>

    <!-- Page header -->
    <div class="page-header-box">
        <div class="page-title">
            <h4><i class="ti ti-table me-2" style="color:#3b82f6;"></i>Location Google Sheets</h4>
            <p>Manage Google Sheets linked to locations for physical attendance verification</p>
        </div>
        <button class="btn-ls-add" id="btnAddNew" onclick="openAddModal()">
            <i class="ti ti-plus"></i> Add New Sheet
        </button>
    </div>

    <!-- Stat cards -->
    <div class="ls-stats-row">
        <div class="ls-stat blue">
            <div class="ls-stat-icon"><i class="ti ti-table"></i></div>
            <div>
                <div class="ls-stat-num" id="statTotal"><?php echo $total; ?></div>
                <div class="ls-stat-label">Total Sheets</div>
            </div>
        </div>
        <div class="ls-stat green">
            <div class="ls-stat-icon"><i class="ti ti-circle-check"></i></div>
            <div>
                <div class="ls-stat-num" id="statActive"><?php echo $active; ?></div>
                <div class="ls-stat-label">Active</div>
            </div>
        </div>
        <div class="ls-stat red">
            <div class="ls-stat-icon"><i class="ti ti-circle-x"></i></div>
            <div>
                <div class="ls-stat-num" id="statInactive"><?php echo $inactive; ?></div>
                <div class="ls-stat-label">Inactive</div>
            </div>
        </div>
        <div class="ls-stat purple">
            <div class="ls-stat-icon"><i class="ti ti-map-pin"></i></div>
            <div>
                <div class="ls-stat-num" id="statProjects"><?php echo $projects; ?></div>
                <div class="ls-stat-label">Projects Mapped</div>
            </div>
        </div>
    </div>

    <!-- Main records card -->
    <div class="ls-card">
        <div class="ls-card-head">
            <h5><i class="ti ti-list-details"></i> Sheet Records</h5>
            <div class="ls-filter">
                <input type="text" id="searchInput" placeholder="🔍 Search..." style="min-width:200px;">
                <select id="filterStatus">
                    <option value="">All Status</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
        </div>

        <div class="ls-table-wrap">
            <table class="ls-table" id="lsTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Location Name</th>
                        <th>Google Sheet</th>
                        <th>Sheet Name</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="lsTableBody">
                    <?php if (empty($records)): ?>
                    <tr>
                        <td colspan="6">
                            <div class="ls-empty">
                                <i class="ti ti-inbox"></i>
                                <h6>No Records Found</h6>
                                <p>Add your first Google Sheet location mapping using <strong>Add New Sheet</strong> above.</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: foreach ($records as $idx => $row): ?>
                    <tr data-status="<?php echo $row['status']; ?>"
                        data-sno="<?php echo $row['sno']; ?>"
                        data-projectid="<?php echo htmlspecialchars($row['projectid']); ?>"
                        data-locationid="<?php echo htmlspecialchars($row['locationid']); ?>"
                        data-search="<?php echo strtolower($row['projectid'] . ' ' . $row['locationname'] . ' ' . $row['googlesheetid'] . ' ' . $row['sheetname']); ?>">
                        <td><?php echo $idx + 1; ?></td>
                        <td><?php echo htmlspecialchars($row['locationname'] ?: '—'); ?></td>
                        <td>
                            <a href="https://docs.google.com/spreadsheets/d/<?php echo htmlspecialchars($row['googlesheetid']); ?>"
                               target="_blank" class="gs-link"
                               title="<?php echo htmlspecialchars($row['googlesheetid']); ?>">
                                <i class="ti ti-external-link"></i>
                                <?php echo htmlspecialchars(substr($row['googlesheetid'], 0, 20)) . (strlen($row['googlesheetid']) > 20 ? '…' : ''); ?>
                            </a>
                        </td>
                        <td><span class="sheet-pill"><?php echo htmlspecialchars($row['sheetname']); ?></span></td>
                        <td>
                            <span class="status-badge <?php echo $row['status'] ? 'status-on' : 'status-off'; ?>"
                                  id="sBadge_<?php echo $row['sno']; ?>">
                                <i class="ti <?php echo $row['status'] ? 'ti-check' : 'ti-x'; ?>"></i>
                                <?php echo $row['status'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-btns">
                                <!-- Edit -->
                                <button class="abn abn-edit"
                                        onclick='openEditModal(<?php echo json_encode($row); ?>)'
                                        title="Edit">
                                    <i class="ti ti-pencil"></i>
                                </button>
                                <!-- Toggle status -->
                                <button class="abn <?php echo $row['status'] ? 'abn-on' : 'abn-off'; ?>"
                                        id="tBtn_<?php echo $row['sno']; ?>"
                                        onclick="doToggle(<?php echo $row['sno']; ?>, <?php echo $row['status'] ? 0 : 1; ?>, this)"
                                        title="<?php echo $row['status'] ? 'Deactivate' : 'Activate'; ?>">
                                    <i class="ti <?php echo $row['status'] ? 'ti-toggle-right' : 'ti-toggle-left'; ?>"></i>
                                </button>
                                <!-- Delete -->
                                <button class="abn abn-del"
                                        onclick="confirmDel(<?php echo $row['sno']; ?>, '<?php echo addslashes(htmlspecialchars($row['locationname'])); ?>')"
                                        title="Delete">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="ls-modal-bg" id="formModal">
    <div class="ls-modal">
        <div class="ls-modal-hdr">
            <h5 id="modalTitle"><i class="ti ti-plus-circle"></i> Add New Sheet</h5>
            <button class="ls-modal-close" onclick="closeFormModal()"><i class="ti ti-x"></i></button>
        </div>
        <div class="ls-modal-body">
            <form id="sheetForm" autocomplete="off">
                <input type="hidden" id="fSno"       name="sno"        value="">
                <input type="hidden" id="fAction"    name="action"     value="add">
                <input type="hidden" id="fProjectId" name="projectid"  value="">
                <input type="hidden" id="fLocId"     name="locationid" value="">

                <div class="ls-fg">
                    <label for="fLocDropdown">Location <span class="req">*</span></label>
                    <select id="fLocDropdown" class="ls-fc" required>
                        <option value="">— Loading locations… —</option>
                    </select>
                    <small style="color:#94a3b8;font-size:0.73rem;margin-top:4px;display:block;">
                        <i class="ti ti-info-circle"></i> Locations are loaded from the current project.
                    </small>
                </div>

                <input type="hidden" id="fLocName" name="locationname" value="">


                <div class="ls-fg">
                    <label for="fGsId">Google Sheet ID <span class="req">*</span></label>
                    <input type="text" id="fGsId" name="googlesheetid"
                           class="ls-fc" placeholder="Paste the Sheet ID from the URL…" required>
                    <small style="color:#94a3b8;font-size:0.73rem;margin-top:4px;display:block;">
                        <i class="ti ti-info-circle"></i>
                        From URL: <code>docs.google.com/spreadsheets/d/<strong>[SHEET_ID]</strong>/edit</code>
                    </small>
                </div>

                <!-- Live preview link -->
                <div id="gsPreview" style="display:none; margin-bottom:10px;">
                    <a id="gsPreviewLink" href="#" target="_blank"
                       style="font-size:0.78rem;color:#3b82f6;display:inline-flex;align-items:center;gap:5px;">
                        <i class="ti ti-external-link"></i> Preview Sheet
                    </a>
                </div>

                <div class="ls-row2">
                    <div class="ls-fg">
                        <label for="fSheetName">Sheet Tab Name</label>
                        <input type="text" id="fSheetName" name="sheetname"
                               class="ls-fc" value="Sheet1" placeholder="Sheet1">
                    </div>
                    <div class="ls-fg">
                        <label for="fStatus">Status</label>
                        <select id="fStatus" name="status" class="ls-fc">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>
        <div class="ls-modal-ftr">
            <button class="btn-ls-cancel" onclick="closeFormModal()">
                <i class="ti ti-x me-1"></i> Cancel
            </button>
            <button class="btn-ls-save" id="btnSave" onclick="doSave()">
                <i class="ti ti-device-floppy me-1"></i> Save Record
            </button>
        </div>
    </div>
</div>

<div class="ls-modal-bg" id="delModal">
    <div class="ls-modal" style="max-width:400px;">
        <div class="ls-modal-body" style="text-align:center;padding:30px 22px 16px;">
            <div class="del-confirm-icon"><i class="ti ti-trash"></i></div>
            <h5 style="font-weight:700;color:#1e293b;margin-bottom:6px;">Delete Record?</h5>
            <p style="color:#64748b;font-size:0.86rem;margin:0;">
                Delete <strong id="delLocName"></strong>?<br>
                <span style="color:#ef4444;font-size:0.78rem;">This cannot be undone.</span>
            </p>
        </div>
        <div class="ls-modal-ftr" style="justify-content:center;">
            <button class="btn-ls-cancel" onclick="closeDelModal()">
                <i class="ti ti-x me-1"></i> Cancel
            </button>
            <button class="btn-ls-del" id="btnConfirmDel" onclick="execDelete()">
                <i class="ti ti-trash me-1"></i> Yes, Delete
            </button>
        </div>
    </div>
</div>
<div id="ls-toast-wrap"></div>
<?php include('footer.php'); ?>
<script>
/* ── State ──────────────────────────────────────────────────────────────────── */
const SELF_URL = 'modal/attverify_locationsheets_modal.php';
let   delSno   = null;

/* ── Search / Filter ─────────────────────────────────────────────────────────── */
document.getElementById('searchInput').addEventListener('input',  filterRows);
document.getElementById('filterStatus').addEventListener('change', filterRows);

function filterRows() {
    const q   = document.getElementById('searchInput').value.toLowerCase().trim();
    const st  = document.getElementById('filterStatus').value;
    document.querySelectorAll('#lsTableBody tr[data-status]').forEach(tr => {
        const okSearch = !q  || tr.dataset.search.includes(q);
        const okStatus = !st || tr.dataset.status === st;
        tr.style.display = (okSearch && okStatus) ? '' : 'none';
    });
}

/* ── Google Sheet ID live preview ────────────────────────────────────────────── */
document.getElementById('fGsId').addEventListener('input', function () {
    const v = this.value.trim();
    if (v.length > 10) {
        document.getElementById('gsPreview').style.display = 'block';
        document.getElementById('gsPreviewLink').href =
            'https://docs.google.com/spreadsheets/d/' + encodeURIComponent(v);
    } else {
        document.getElementById('gsPreview').style.display = 'none';
    }
});

/* ── Location map (id → name) ────────────────────────────────────────────────── */
var lsLocationMap = {};

/* ── Load locations into the dropdown ───────────────────────────────────────── */
function loadLocationsDropdown(callback) {
    LabourAttendanceLocationList('all', 'all', function (res) {
        lsLocationMap = {};
        var sel = document.getElementById('fLocDropdown');
        sel.innerHTML = '<option value="">— Select Location —</option>';
        if (res && res.length > 0) {
            res.forEach(function (loc) {
                lsLocationMap[String(loc.LocationId)] = loc.LocationName;
                var opt = document.createElement('option');
                opt.value       = loc.LocationId;
                opt.textContent = loc.LocationName;
                sel.appendChild(opt);
            });
        } else {
            sel.innerHTML = '<option value="">— No locations found —</option>';
        }
        if (typeof callback === 'function') callback();
    });
}

/* ── Auto-fill hidden fields when dropdown changes ───────────────────────────── */
document.addEventListener('DOMContentLoaded', function () {
    /* Load locations once on page ready */
    loadLocationsDropdown();

    document.getElementById('fLocDropdown').addEventListener('change', function () {
        var id   = this.value;
        var name = lsLocationMap[String(id)] || '';
        document.getElementById('fLocId').value   = id;
        document.getElementById('fLocName').value = name;
    });
});

/* ── Open Add Modal ──────────────────────────────────────────────────────────── */
function openAddModal() {
    document.getElementById('modalTitle').innerHTML = '<i class="ti ti-plus-circle"></i> Add New Sheet';
    document.getElementById('fAction').value    = 'add';
    document.getElementById('fSno').value       = '';
    /* Auto-fill projectid from body data attribute */
    document.getElementById('fProjectId').value = document.body.dataset.projectid || '';
    document.getElementById('fLocDropdown').value = '';
    document.getElementById('fLocId').value     = '';
    document.getElementById('fLocName').value   = '';
    document.getElementById('fGsId').value      = '';
    document.getElementById('fSheetName').value = 'Sheet1';
    document.getElementById('fStatus').value    = '1';
    document.getElementById('gsPreview').style.display = 'none';
    document.getElementById('btnSave').innerHTML =
        '<i class="ti ti-device-floppy me-1"></i> Save Record';
    openBg('formModal');
}

/* ── Open Edit Modal ─────────────────────────────────────────────────────────── */
function openEditModal(r) {
    document.getElementById('modalTitle').innerHTML = '<i class="ti ti-pencil"></i> Edit Sheet Record';
    document.getElementById('fAction').value    = 'edit';
    document.getElementById('fSno').value       = r.sno;
    document.getElementById('fProjectId').value = document.body.dataset.projectid || r.projectid || '';
    /* Set dropdown to the saved locationid */
    document.getElementById('fLocDropdown').value = r.locationid || '';
    document.getElementById('fLocId').value       = r.locationid || '';
    document.getElementById('fLocName').value     = r.locationname || (lsLocationMap[String(r.locationid)] || '');
    document.getElementById('fGsId').value        = r.googlesheetid || '';
    document.getElementById('fSheetName').value   = r.sheetname || 'Sheet1';
    document.getElementById('fStatus').value      = r.status;
    document.getElementById('btnSave').innerHTML  =
        '<i class="ti ti-device-floppy me-1"></i> Update Record';

    const gs = r.googlesheetid || '';
    if (gs.length > 10) {
        document.getElementById('gsPreview').style.display = 'block';
        document.getElementById('gsPreviewLink').href =
            'https://docs.google.com/spreadsheets/d/' + encodeURIComponent(gs);
    } else {
        document.getElementById('gsPreview').style.display = 'none';
    }
    openBg('formModal');
}

/* ── Save (Add / Edit) ───────────────────────────────────────────────────────── */
function doSave() {
    /* Ensure hidden fields are populated from dropdown before validating */
    var locDrop = document.getElementById('fLocDropdown');
    document.getElementById('fLocId').value   = locDrop.value;
    document.getElementById('fLocName').value = lsLocationMap[String(locDrop.value)] || '';
    document.getElementById('fProjectId').value = document.body.dataset.projectid || '';

    if (!locDrop.value) {
        locDrop.focus();
        toast('Please select a location.', 'err');
        return;
    }

    const form = document.getElementById('sheetForm');
    if (!form.checkValidity()) { form.reportValidity(); return; }

    const btn = document.getElementById('btnSave');
    btn.disabled = true;
    btn.innerHTML = '<i class="ti ti-loader-2 spin me-1"></i> Saving…';

    fetch(SELF_URL, { method: 'POST', body: new FormData(form) })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = '<i class="ti ti-device-floppy me-1"></i> Save Record';
            if (res.success) {
                toast(res.msg, 'ok');
                closeFormModal();
                setTimeout(() => location.reload(), 700);
            } else {
                toast(res.msg, 'err');
            }
        })
        .catch(() => {
            btn.disabled = false;
            toast('Network error. Please try again.', 'err');
        });
}

/* ── Toggle Status ───────────────────────────────────────────────────────────── */
function doToggle(sno, newStatus, btn) {
    const fd = new FormData();
    fd.append('action', 'toggle_status');
    fd.append('sno',    sno);
    fd.append('status', newStatus);

    fetch(SELF_URL, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (!res.success) { toast(res.msg, 'err'); return; }

            const badge = document.getElementById('sBadge_' + sno);
            const row   = btn.closest('tr');

            if (newStatus == 1) {
                badge.className = 'status-badge status-on';
                badge.innerHTML = '<i class="ti ti-check"></i> Active';
                btn.className   = 'abn abn-on';
                btn.title       = 'Deactivate';
                btn.querySelector('i').className = 'ti ti-toggle-right';
            } else {
                badge.className = 'status-badge status-off';
                badge.innerHTML = '<i class="ti ti-x"></i> Inactive';
                btn.className   = 'abn abn-off';
                btn.title       = 'Activate';
                btn.querySelector('i').className = 'ti ti-toggle-left';
            }
            // flip for next click
            btn.setAttribute('onclick', 'doToggle(' + sno + ',' + (newStatus == 1 ? 0 : 1) + ',this)');
            row.dataset.status = newStatus;
            updateStats();
            toast(res.msg, 'ok');
        })
        .catch(() => toast('Network error.', 'err'));
}

/* ── Delete ──────────────────────────────────────────────────────────────────── */
function confirmDel(sno, name) {
    delSno = sno;
    document.getElementById('delLocName').textContent = name || ('SNO #' + sno);
    openBg('delModal');
}

function execDelete() {
    if (!delSno) return;
    const btn = document.getElementById('btnConfirmDel');
    btn.disabled = true;
    btn.innerHTML = '<i class="ti ti-loader-2 spin me-1"></i> Deleting…';

    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('sno',    delSno);

    fetch(SELF_URL, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = '<i class="ti ti-trash me-1"></i> Yes, Delete';
            if (res.success) {
                toast(res.msg, 'ok');
                closeDelModal();
                setTimeout(() => location.reload(), 700);
            } else {
                toast(res.msg, 'err');
            }
        })
        .catch(() => {
            btn.disabled = false;
            toast('Network error.', 'err');
        });
}

/* ── Modal helpers ───────────────────────────────────────────────────────────── */
function openBg(id)      { document.getElementById(id).classList.add('open'); }
function closeFormModal(){ document.getElementById('formModal').classList.remove('open'); }
function closeDelModal() { document.getElementById('delModal').classList.remove('open'); delSno = null; }

// Close on backdrop click
document.querySelectorAll('.ls-modal-bg').forEach(bg => {
    bg.addEventListener('click', e => { if (e.target === bg) bg.classList.remove('open'); });
});

/* ── Update stat counters ────────────────────────────────────────────────────── */
function updateStats() {
    let total = 0, active = 0, inactive = 0;
    document.querySelectorAll('#lsTableBody tr[data-status]').forEach(tr => {
        total++;
        tr.dataset.status == '1' ? active++ : inactive++;
    });
    document.getElementById('statTotal').textContent    = total;
    document.getElementById('statActive').textContent   = active;
    document.getElementById('statInactive').textContent = inactive;
}

/* ── Toast ───────────────────────────────────────────────────────────────────── */
function toast(msg, type) {
    const icons = { ok:'ti-circle-check', err:'ti-alert-circle', inf:'ti-info-circle' };
    const el = document.createElement('div');
    el.className = 'ls-toast ' + type;
    el.innerHTML = '<i class="ti ' + (icons[type] || icons.inf) + ' ti"></i><span>' + msg + '</span>';
    document.getElementById('ls-toast-wrap').appendChild(el);
    setTimeout(() => el.remove(), 3600);
}

/* ── Spinner animation ────────────────────────────────────────────────────────── */
const style = document.createElement('style');
style.textContent = '.spin { animation: spinAnim 1s linear infinite; } @keyframes spinAnim { to { transform: rotate(360deg); } }';
document.head.appendChild(style);
</script>
