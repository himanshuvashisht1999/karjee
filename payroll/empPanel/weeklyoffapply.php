<?php 
include('header.php'); 

if (!empty($isResigned)) {
    echo "<div class='container mt-5 text-center'><div class='alert alert-warning py-4'><h4><i class='ti ti-alert-triangle me-2'></i>Access Restricted</h4><p class='mb-3'>Weekly Off Apply is disabled because this employee is marked as resigned.</p><a href='index.php?k=" . urlencode($key) . "&empid=" . urlencode($empid) . "&accessby=" . urlencode($accessby) . "' class='btn btn-primary'>Back to Profile</a></div></div>";
    include('footer.php');
    exit;
}

// Fetch employee's configured week off days
$weekOffs = [];
$woRes = mysqli_query($hrmconn, "SELECT WeekOffdayName, WeekOffDayNo FROM hrm_EmployeeWeekOff WHERE EmployeeID = '$empid' AND ProjectID = '$projectid' AND Status = 'Active'");
while ($woRow = mysqli_fetch_assoc($woRes)) {
    $weekOffs[] = $woRow;
}

// Calculate upcoming week-off dates for the next 14 days
$upcomingDates = [];
if (!empty($weekOffs)) {
    $allowedDayNos = array_column($weekOffs, 'WeekOffDayNo');
    for ($i = 1; $i <= 14; $i++) {
        $time = strtotime("+$i days");
        $dayNo = date('N', $time); // 1 (Mon) to 7 (Sun)
        if (in_array($dayNo, $allowedDayNos)) {
            $upcomingDates[] = [
                'date' => date('Y-m-d', $time),
                'formatted' => date('d-M-Y (l)', $time)
            ];
        }
    }
}

// Fetch active colleagues/backup employees in the same project
$colRes = mysqli_query($hrmconn, "SELECT EmployeeID, FirstName, MiddleName, LastName FROM hrm_EmployeesMaster WHERE ProjectID = '$projectid' AND EmployeeID != '$empid' AND Status = 'Active' ORDER BY FirstName ASC");
$colleagues = [];
while ($colRow = mysqli_fetch_assoc($colRes)) {
    $colleagues[] = $colRow;
}
?>
<div class="content">
	<!-- Page Header -->
	<div class="d-flex align-items-center justify-content-between page-breadcrumb mb-4">
		<div class="my-auto mb-2">
			<h2 class="mb-1 fw-bold">Weekly Off Management</h2>
			<nav>
				<ol class="breadcrumb mb-0">
					<li class="breadcrumb-item">
						<a href="<?php echo $rooturl; ?>empPanel/index.php?k=<?php echo $key; ?>&empid=<?php echo $empid; ?>&accessby=<?php echo $accessby; ?>">
							<i class="ti ti-smart-home"></i>
						</a>
					</li>
					<li class="breadcrumb-item">Employee</li>
					<li class="breadcrumb-item active text-primary" aria-current="page">Weekly Off</li>
				</ol>
			</nav>
		</div>
		<div class="d-flex my-xl-auto right-content align-items-center flex-wrap gap-2">
			<button data-bs-toggle="modal" data-bs-target="#applyWeekOffModal" class="btn btn-apply-weekoff d-flex align-items-center shadow-sm">
				<i class="ti ti-circle-plus me-1"></i> Apply Week-off
			</button>
		</div>
	</div>

	<!-- Scheduled Days Banner -->
	<div class="card border-0 shadow-sm mb-4 bg-soft-primary overflow-hidden">
		<div class="card-body p-3">
			<div class="d-flex align-items-center justify-content-between">
				<div class="d-flex align-items-center gap-3">
					<div class="icon-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 42px; height: 42px; borderRadius: 50%;">
						<i class="ti ti-calendar-event fs-20"></i>
					</div>
					<div>
						<h5 class="mb-0 fw-bold text-dark">Scheduled Weekly Offs</h5>
						<p class="mb-0 text-muted small">
							Your scheduled week-off days in employee master: 
							<span class="text-primary fw-bold">
								<?php 
								if (!empty($weekOffs)) {
									echo implode(', ', array_column($weekOffs, 'WeekOffdayName'));
								} else {
									echo "None configured";
								}
								?>
							</span>
						</p>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Week-off History Section -->
	<div class="mt-4">
		<div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
			<h4 class="mb-0 fw-bold">Week-off Application History</h4>
			<span class="badge bg-light text-secondary border px-3 py-2 rounded-pill">Total Applied: <span id="totalAppliedCount">0</span></span>
		</div>

		<!-- History Strips Container -->
		<div id="weekOffHistoryWrapper" class="info-strip-container">
			<div class="text-center py-5">
				<div class="spinner-border text-primary spinner-border-sm"></div>
				<p class="small text-muted mt-2">Loading applications...</p>
			</div>
		</div>
	</div>
</div>

<!-- Modal Form for Apply Week-off -->
<div class="modal fade" id="applyWeekOffModal" tabindex="-1" aria-labelledby="applyWeekOffModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content border-0 shadow">
			<div class="modal-header bg-primary text-white">
				<h5 class="modal-title fw-bold text-white" id="applyWeekOffModalLabel">
					<i class="ti ti-calendar-plus me-1"></i> Apply for Weekly Off
				</h5>
				<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body p-4">
				<form id="applyWeekOffForm">
					<input type="hidden" name="Action" value="Apply">
					<input type="hidden" name="EmployeeID" value="<?php echo $empid; ?>">
					<input type="hidden" name="ProjectID" value="<?php echo $projectid; ?>">

					<!-- Week-off Date Dropdown -->
					<div class="mb-3">
						<label class="form-label fw-bold text-dark">Select Week-off Date</label>
						<select class="form-select" name="WeekOffDate" required>
							<option value="">-- Choose Upcoming Date --</option>
							<?php foreach ($upcomingDates as $dateItem): ?>
								<option value="<?php echo $dateItem['date']; ?>">
									<?php echo $dateItem['formatted']; ?>
								</option>
							<?php endforeach; ?>
						</select>
						<small class="text-muted">Calculated as per your weekly scheduled off days.</small>
					</div>

					<!-- Colleague/Subordinate Dropdown -->
					<div class="mb-3">
						<label class="form-label fw-bold text-dark">Subordinate / Backup Employee</label>
						<select class="form-select select2" name="SubordinateID" id="SubordinateID" required>
							<option value="">-- Select Work Presence Backup --</option>
							<?php foreach ($colleagues as $colleague): ?>
								<?php 
								$fullName = implode(' ', array_filter([
									trim($colleague['FirstName']),
									trim($colleague['MiddleName']),
									trim($colleague['LastName'])
								]));
								?>
								<option value="<?php echo $colleague['EmployeeID']; ?>">
									<?php echo htmlspecialchars($fullName); ?> (ID: <?php echo $colleague['EmployeeID']; ?>)
								</option>
							<?php endforeach; ?>
						</select>
						<small class="text-muted">Select the employee who will be present for duty in your absence.</small>
					</div>

					<!-- Reason / Remarks -->
					<div class="mb-3">
						<label class="form-label fw-bold text-dark">Reason / Remarks</label>
						<textarea class="form-control" name="Reason" rows="3" placeholder="Enter reason or remarks for reference..."></textarea>
					</div>

					<div class="text-end pt-2">
						<button type="button" class="btn btn-light px-4 me-2" data-bs-dismiss="modal">Cancel</button>
						<button type="submit" class="btn btn-primary px-4">Submit Application</button>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<?php include('footer.php'); ?>

<style>
.bg-soft-primary {
	background-color: #eff6ff !important;
	border: 1px solid #bfdbfe;
}
.btn-apply-weekoff {
	background: linear-gradient(135deg, #fa5a28, #ea580c);
	color: #fff;
	font-weight: 600;
	border: none;
	border-radius: 8px;
	padding: 8px 16px;
	font-size: 0.85rem;
	transition: all 0.2s;
}
.btn-apply-weekoff:hover {
	transform: translateY(-1px);
	box-shadow: 0 4px 12px rgba(234, 88, 12, 0.25);
	color: #fff;
}
.info-strip {
	background: #fff;
	border-radius: 12px;
	padding: 16px;
	border: 1px solid #e2e8f0;
	transition: all 0.2s;
	margin-bottom: 12px;
}
.info-strip:hover {
	box-shadow: 0 4px 12px rgba(0,0,0,0.05);
	border-color: #cbd5e1;
}
.info-strip-icon {
	width: 40px;
	height: 40px;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 16px;
}
.info-strip-label {
	font-size: 0.65rem;
	font-weight: 700;
	text-transform: uppercase;
	color: #94a3b8;
	letter-spacing: 0.5px;
}
.info-strip-value {
	font-size: 0.85rem;
	font-weight: 600;
	color: #1e293b;
	margin-top: 2px;
}
.status-badge {
	display: inline-flex;
	align-items: center;
	gap: 6px;
	font-weight: 600;
	font-size: 11px;
	padding: 4px 10px;
	border-radius: 9999px;
}
.status-pending {
	background-color: #fef3c7;
	color: #d97706;
}
.status-approved {
	background-color: #dcfce7;
	color: #15803d;
}
.status-rejected {
	background-color: #fee2e2;
	color: #b91c1c;
}
.status-cancelled {
	background-color: #f1f5f9;
	color: #64748b;
}

@media (max-width: 575px) {
	.btn-apply-weekoff {
		padding: 5px 8px !important;
		font-size: 0.7rem !important;
	}
	.page-breadcrumb h2 {
		font-size: 1.25rem !important;
	}
	.info-strip {
		padding: 10px;
	}
	.info-strip-value {
		font-size: 0.75rem !important;
	}
	.info-strip-label {
		font-size: 0.55rem !important;
	}
}

/* Select2 custom styling inside applyWeekOffModal */
#applyWeekOffModal .select2-container--default .select2-selection--single {
	border: 1px solid #dee2e6;
	border-radius: 8px;
	height: 42px;
	padding: 6px 12px;
	font-size: 0.9rem;
}
#applyWeekOffModal .select2-container--default .select2-selection--single .select2-selection__arrow {
	height: 40px;
	right: 12px;
}
#applyWeekOffModal .select2-container--default .select2-selection--single .select2-selection__rendered {
	line-height: 28px;
	color: #334155;
	padding: 0;
}
#applyWeekOffModal .select2-container {
	width: 100% !important;
}
</style>

<script>
$(document).ready(function() {
	loadWeekOffHistory();

	// Initialize Select2 with search for Subordinate/Backup Employee dropdown inside Modal
	$('#SubordinateID').select2({
		dropdownParent: $('#applyWeekOffModal'),
		placeholder: "-- Select Work Presence Backup --",
		allowClear: true,
		width: '100%'
	});

	$('#applyWeekOffModal').on('shown.bs.modal', function () {
		$('#SubordinateID').select2({
			dropdownParent: $('#applyWeekOffModal'),
			placeholder: "-- Select Work Presence Backup --",
			allowClear: true,
			width: '100%'
		});
	});

	// Handle apply form submission
	$("#applyWeekOffForm").on("submit", function(e) {
		e.preventDefault();
		
		$.ajax({
			type: "POST",
			url: "weeklyoff_submit.php",
			data: $(this).serialize(),
			dataType: "JSON",
			success: function(res) {
				if (res.status === "success") {
					iziToast.success({
						title: "Success",
						message: res.message
					});
					$("#applyWeekOffModal").modal("hide");
					$("#applyWeekOffForm")[0].reset();
					$("#SubordinateID").val("").trigger("change");
					loadWeekOffHistory();
				} else {
					iziToast.error({
						title: "Error",
						message: res.message
					});
				}
			},
			error: function() {
				iziToast.error({
					title: "Error",
					message: "Something went wrong. Please try again."
				});
			}
		});
	});
});

function loadWeekOffHistory() {
	const wrapper = $("#weekOffHistoryWrapper");
	const empid = "<?php echo $empid; ?>";
	const projectid = "<?php echo $projectid; ?>";

	$.ajax({
		type: "POST",
		url: "get_weeklyoff_data.php",
		data: { EmployeeID: empid, ProjectID: projectid },
		dataType: "JSON",
		success: function(res) {
			if (res.status === "success" && Array.isArray(res.data) && res.data.length > 0) {
				$("#totalAppliedCount").text(res.data.length);
				let stripsHtml = "";
				
				res.data.forEach(row => {
					let sClass = "status-pending", sIcon = "ti-hourglass-low";
					if (row.Status === "Approved") { sClass = "status-approved"; sIcon = "ti-circle-check"; }
					else if (row.Status === "Rejected") { sClass = "status-rejected"; sIcon = "ti-circle-x"; }
					else if (row.Status === "Cancelled") { sClass = "status-cancelled"; sIcon = "ti-ban"; }

					let cancelBtn = "";
					if (row.Status === "Pending") {
						cancelBtn = `
							<button class="btn btn-outline-danger btn-xs py-1 px-2 text-uppercase fw-bold" style="font-size:9px;" onclick="cancelWeekOff(${row.WeeklyOffApplyID})">
								Cancel
							</button>
						`;
					}

					stripsHtml += `
					<div class="info-strip shadow-sm">
						<div class="row align-items-center g-3">
							<div class="col-auto">
								<div class="info-strip-icon bg-soft-primary text-primary font-weight-bold">
									<i class="ti ti-calendar"></i>
								</div>
							</div>
							<div class="col">
								<div class="row g-2">
									<div class="col-md-3 col-6">
										<div class="info-strip-label">Week-off Date</div>
										<div class="info-strip-value text-primary">${row.WeekOffDateFormatted}</div>
									</div>
									<div class="col-md-3 col-6">
										<div class="info-strip-label">Backup Subordinate</div>
										<div class="info-strip-value">${row.SubordinateName}</div>
									</div>
									<div class="col-md-3 col-6">
										<div class="info-strip-label">Date Applied</div>
										<div class="info-strip-value">${row.AppliedDateFormatted}</div>
									</div>
									<div class="col-md-3 col-6">
										<div class="info-strip-label">Reason</div>
										<div class="info-strip-value text-muted" style="font-size:0.75rem;">${row.Reason || 'N/A'}</div>
									</div>
								</div>
							</div>
							<div class="col-auto text-end d-flex align-items-center gap-2">
								<span class="status-badge ${sClass}">
									<i class="ti ${sIcon}"></i> ${row.Status}
								</span>
								${cancelBtn}
							</div>
						</div>
					</div>`;
				});
				wrapper.html(stripsHtml);
			} else {
				$("#totalAppliedCount").text("0");
				wrapper.html(`
					<div class="alert alert-light text-center border-dashed py-5">
						<i class="ti ti-calendar-off fs-30 d-block mb-2 text-muted"></i>
						No week-off applications found.
					</div>
				`);
			}
		},
		error: function() {
			wrapper.html(`<div class="alert alert-danger">Failed to load week-off history.</div>`);
		}
	});
}

function cancelWeekOff(applyId) {
	if (!confirm("Are you sure you want to cancel this week-off application?")) return;

	$.ajax({
		type: "POST",
		url: "weeklyoff_submit.php",
		data: { Action: "Cancel", WeeklyOffApplyID: applyId },
		dataType: "JSON",
		success: function(res) {
			if (res.status === "success") {
				iziToast.success({
					title: "Cancelled",
					message: res.message
				});
				loadWeekOffHistory();
			} else {
				iziToast.error({
					title: "Error",
					message: res.message
				});
			}
		},
		error: function() {
			iziToast.error({
				title: "Error",
				message: "Failed to cancel request."
			});
		}
	});
}
</script>
