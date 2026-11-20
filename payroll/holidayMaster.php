<?php
if (isset($_GET['vw']) == 1) {
    $key = $_GET['k'];
    $rooturl = "";
} else {
    require('header.php');
}
?>

<style>
    :root {
        --holiday-accent: #6366f1;
        --holiday-bg: rgba(255, 255, 255, 0.7);
        --holiday-blur: 10px;
    }

    #employeesalarycontainer {
        max-width: 1300px;
        margin-bottom: 30px !important;
    }

    .holiday-header-card {
        background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
        border: none;
        border-radius: 15px;
        box-shadow: 0 10px 25px rgba(99, 102, 241, 0.2);
        margin-bottom: 20px !important;
        position: relative;
        overflow: hidden;
    }

    .holiday-header-card::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -10%;
        width: 300px;
        height: 300px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        pointer-events: none;
    }

    .holiday-header-card h4 {
        font-weight: 700;
        letter-spacing: 0.5px;
        margin: 0;
        padding: 15px;
        color: #fff;
    }

    .glass-card {
        background: var(--holiday-bg);
        backdrop-filter: blur(var(--holiday-blur));
        -webkit-backdrop-filter: blur(var(--holiday-blur));
        border: 1px solid rgba(255, 255, 255, 0.35);
        border-radius: 15px;
        box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07);
    }

    .master-controls {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        margin-bottom: 20px;
    }

    .year-select-group {
        width: 200px;
    }

    .hrm-select-custom {
        border-radius: 10px;
        border: 1.5px solid #e2e8f0;
        padding: 8px 15px;
        font-weight: 500;
        color: #475569;
        background-color: #fff;
    }

    .holiday-table-container {
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid #f1f5f9;
        background: #fff;
    }

    .table-custom {
        margin-bottom: 0;
    }

    .table-custom thead th {
        background: #f8fafc;
        color: #64748b;
        font-weight: 700;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        padding: 15px;
        border: none;
    }

    .table-custom tbody td {
        padding: 12px 15px;
        vertical-align: middle;
        color: #334155;
        border-top: 1px solid #f1f5f9;
    }

    .action-icons i {
        font-size: 1.25rem;
        margin: 0 8px;
        cursor: pointer;
        transition: transform 0.2s;
    }

    .action-icons i:hover {
        transform: scale(1.2);
    }

    .form-control-custom {
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        padding: 6px 12px;
        font-size: 0.9rem;
    }

    .form-control-custom:disabled {
        background-color: transparent;
        border: none;
        color: #1e293b;
        font-weight: 600;
    }

    .empty-state {
        text-align: center;
        padding: 40px 20px;
    }

    @media (max-width: 768px) {
        .master-controls {
            flex-direction: column;
            align-items: flex-start;
        }

        .year-select-group {
            width: 100%;
        }
    }
</style>

<div class="container mt-2" id="employeesalarycontainer">
    <div class="row mb-3">
        <div class="col-12 text-end">
            <a class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-sm"
                href="../admin_dashboard.php?k=<?php echo $key; ?>&grpid=<?php echo $groupid; ?>&mdid=<?php echo $mdid; ?>">
                <i class="ti ti-arrow-left me-1"></i> BACK TO HOME
            </a>
        </div>
    </div>

    <div class="card holiday-header-card shadow-lg">
        <div class="text-center">
            <h4><i class="ti ti-settings me-2"></i>Holiday Master Configuration</h4>
        </div>
    </div>

    <div class="card glass-card">
        <div class="card-body p-4">
            <div class="master-controls">
                <div>
                    <h5 class="mb-0 fw-bold text-dark"><i class="ti ti-list-check me-2 text-primary"></i>Holiday
                        Directory for <?php echo $projectname; ?></h5>
                </div>
                <div class="year-select-group">
                    <label class="small fw-bold text-muted mb-1 d-block">Manage Holidays for Year</label>
                    <select class="form-control hrm-select-custom w-100" id="holidayyear"></select>
                </div>
            </div>

            <div class="holiday-table-container">
                <div class="table-responsive">
                    <table class="table table-custom" id="holidaymaste_tbl">
                        <thead>
                            <tr>
                                <th style="width:60px;">S.No</th>
                                <th style="width:250px;">Holiday Name</th>
                                <th style="width:180px;">Holiday Date</th>
                                <th style="width:200px;">Holiday Type</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="5" class="empty-state">
                                    <i class="ti ti-loader animate-spin d-inline-block fs-3 mb-2"></i>
                                    <p class="text-muted">Loading master data...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
if (isset($_GET['vw']) == 1) {

} else {
    require('footer.php');
}
?>

<script type="text/javascript">
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");

    $(document).ready(function () {
        fetchHolidayYearsFromMaster_callback((res) => {
            const $dropdown = $("#holidayyear");
            const currentYear = new Date().getFullYear();
            $dropdown.empty();
            if (Array.isArray(res) && res.length > 0) {
                res.forEach(year => {
                    $dropdown.append(`<option value="${year}" ${year == currentYear ? 'selected' : ''}>${year}</option>`);
                });
                $dropdown.trigger("change");
            } else {
                $dropdown.append(`<option value="${currentYear}" selected>${currentYear}</option>`);
                $dropdown.trigger("change");
            }
        });
    });

    $(document).on("change", "#holidayyear", function () {
        const selectedYear = $(this).val();
        loadHolidayMaster(selectedYear);
    });

    function loadHolidayMaster(year) {
        $("#holidaymaste_tbl tbody").html('<tr><td colspan="5" class="empty-state"><i class="ti ti-loader animate-spin d-inline-block mb-2 fs-3"></i><p>Refreshing data...</p></td></tr>');
        fetchHolidayFromMaster_callback(year, function (res) {
            holidayType_callback(function (holidayTypes) {
                const $tbody = $("#holidaymaste_tbl tbody").empty();
                if (res && res.length > 0) {
                    res.forEach((item, i) => {
                        let typeOpts = holidayTypes.map(t => `<option value="${t.HolidayType}" ${t.HolidayType === item.HolidayType ? 'selected' : ''}>${t.HolidayType}</option>`).join('');
                        $tbody.append(`
                            <tr data-holidayid="${item.id}">
                                <td class="fw-bold text-muted">${i + 1}</td>
                                <td><input type="text" class="form-control form-control-custom eholidayname" value="${item.HolidayName}" disabled /></td>
                                <td><input type="date" class="form-control form-control-custom eholidaydate" value="${item.HolidayDate}" disabled /></td>
                                <td><select class="form-control form-control-custom eholidaytype" disabled>${typeOpts}</select></td>
                                <td class="text-center action-icons">
                                    <i class="ti ti-edit text-primary editmoholiday" title="Edit"></i>
                                    <i class="ti ti-trash text-danger removeholiday" title="Delete"></i>
                                    <i class="ti ti-circle-plus text-success addblanckkrowholiday" title="Add New"></i>
                                    <i class="ti ti-device-floppy text-success editsaveholiday" style="display:none;" title="Save"></i>
                                    <i class="ti ti-rotate-back text-warning undoholiday" style="display:none;" title="Cancel"></i>
                                </td>
                            </tr>
                        `);
                    });
                } else {
                    addNewRowToTable();
                }
            });
        });
    }

    function addNewRowToTable() {
        holidayType_callback(function (res) {
            let typeOpts = res.map(t => `<option value="${t.HolidayType}">${t.HolidayType}</option>`).join('');
            $("#holidaymaste_tbl tbody").append(`
                <tr class="table-primary-subtle">
                    <td class="fw-bold">New</td>
                    <td><input type="text" class="form-control form-control-custom eholidayname" placeholder="Enter name..." /></td>
                    <td><input type="date" class="form-control form-control-custom eholidaydate" /></td>
                    <td><select class="form-control form-control-custom eholidaytype">${typeOpts}</select></td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-success rounded-pill px-3 savenewholiday"><i class="ti ti-device-floppy me-1"></i> SAVE</button>
                    </td>
                </tr>
            `);
        });
    }

    $(document).on("click", ".addblanckkrowholiday", function () {
        addNewRowToTable();
        /*holidayType_callback(function (res) {
            let typeOpts = res.map(t => `<option value="${t.HolidayType}">${t.HolidayType}</option>`).join('');
            $(this).closest("tr").after(`
                <tr class="table-success-subtle">
                    <td class="fw-bold"><i class="ti ti-corner-down-right"></i></td>
                    <td><input type="text" class="form-control form-control-custom eholidayname" placeholder="Enter name..." /></td>
                    <td><input type="date" class="form-control form-control-custom eholidaydate" /></td>
                    <td><select class="form-control form-control-custom eholidaytype">${typeOpts}</select></td>
                    <td class="text-center">
                        <div class="btn-group">
                            <button class="btn btn-sm btn-success savenewholiday"><i class="ti ti-device-floppy"></i></button>
                            <button class="btn btn-sm btn-danger remove-unsaved-row"><i class="ti ti-trash"></i></button>
                        </div>
                    </td>
                </tr>
            `);
        });*/
    });

    $(document).on("click", ".remove-unsaved-row", function () { $(this).closest("tr").remove(); });

    $(document).on("click", ".editmoholiday", function () {
        const $row = $(this).closest("tr");
        $row.addClass('table-info-subtle');
        $row.find(".editmoholiday, .addblanckkrowholiday, .removeholiday").hide();
        $row.find(".editsaveholiday, .undoholiday").show();
        $row.find("input, select").prop("disabled", false);
    });

    $(document).on("click", ".undoholiday", function () {
        const $row = $(this).closest("tr");
        $row.removeClass('table-info-subtle');
        $row.find(".editmoholiday, .addblanckkrowholiday, .removeholiday").show();
        $row.find(".editsaveholiday, .undoholiday").hide();
        $row.find("input, select").prop("disabled", true);
    });

    // CRUD Event Listeners (maintained original logic but with SweetAlerts)
    $(document).on("click", ".savenewholiday", function () {
        const $row = $(this).closest("tr");
        let name = $row.find("input.eholidayname").val();
        let date = $row.find("input.eholidaydate").val();
        let type = $row.find("select.eholidaytype").val();
        if (!name || !date) return Swal.fire('Error', 'Please fill all fields', 'error');
        addupdate_holidaymaster("", name, date, type, 'add');
    });

    $(document).on("click", ".editsaveholiday", function () {
        const $row = $(this).closest("tr");
        let id = $row.data("holidayid");
        let name = $row.find("input.eholidayname").val();
        let date = $row.find("input.eholidaydate").val();
        let type = $row.find("select.eholidaytype").val();
        addupdate_holidaymaster(id, name, date, type, 'edit');
    });

    $(document).on("click", ".removeholiday", function () {
        const $row = $(this).closest("tr");
        let id = $row.data("holidayid");
        Swal.fire({
            title: 'Delete Holiday?',
            text: "Are you sure you want to remove this holiday?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Yes, Delete'
        }).then((result) => {
            if (result.isConfirmed) {
                addupdate_holidaymaster(id, $row.find(".eholidayname").val(), $row.find(".eholidaydate").val(), $row.find(".eholidaytype").val(), 'remove');
            }
        });
    });
</script>