<?php
  include('../header.php');
  date_default_timezone_set("Asia/Calcutta");
  $mdid ="";
  if(isset($_GET['mdid'])){
    $mdid = $_GET['mdid'];
  }
  $dddr = loginmodulesdetails_frommaster($authconn,$companyid,$subcompanyid,$projectid,"moduleid",$mdid);
  $groupid ="";
  if(count($dddr)>0){
    $groupid = $dddr[0]['groupid'];
  }
?>
<style>
    .po-header {
        background: #e9f7ef;
        border-left: 5px solid #198754;
    }
    .total-box {
        background: #f8f9fa;
        border-radius: 6px;
    }
</style>
<div class="container1 my-4">
    <div class="card">
        <div class="card-header">
            <div class="row">
                <div class="col-md-6">
                    <h3>Purchase Order</h3>
                </div>
                <div class="col-md-6">
                    <button class="btn btn-sm btn-deepblue mr-2 float-right" onclick="window.location.reload();"> <i class="fa fa-sync"></i> Refresh</button>
                </div>
            </div> 
        </div>
        <div class="card-body">
            <h4 class="mb-0 text-success">🌱 Karjee Farm – Purchase Order</h4>
            <small class="text-muted">Generate New Purchase Order</small>
        </div>
        <form id="purchaseOrderForm">
            <!-- PO Header -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Supplier</label>
                            <select class="form-select" required>
                                <option value="">Select Supplier</option>
                                <option>Green Grower Seeds</option>
                                <option>Agro Nursery India</option>
                                <option>PlantCare Supplier</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PO Items -->
            <div class="card mb-4">
                <div class="card-body">
                    <h6 class="mb-3">Purchase Order Items</h6>

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead class="table-success">
                                <tr>
                                    <th style="width:30%">Item / Material</th>
                                    <th style="width:10%">Qty</th>
                                    <th style="width:15%">Rate</th>
                                    <th style="width:10%">Tax %</th>
                                    <th style="width:15%">Amount</th>
                                    <th style="width:5%"></th>
                                </tr>
                            </thead>
                            <tbody id="poItemBody">
                                <tr>
                                    <td>
                                        <input type="text" class="form-control" placeholder="Seed / Plant / Material">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control" step="0.01">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control" step="0.01">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control" step="0.01">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control" readonly>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-danger">×</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <button type="button" class="btn btn-sm btn-outline-success">
                        + Add Item
                    </button>
                </div>
            </div>

            <!-- Totals -->
            <div class="row justify-content-end">
                <div class="col-md-4">
                    <div class="card total-box">
                        <div class="card-body">

                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal</span>
                                <strong>₹ 0.00</strong>
                            </div>

                            <div class="d-flex justify-content-between mb-2">
                                <span>Total Tax</span>
                                <strong>₹ 0.00</strong>
                            </div>

                            <hr>

                            <div class="d-flex justify-content-between">
                                <span>Grand Total</span>
                                <strong class="text-success">₹ 0.00</strong>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label">Remarks</label>
                    <input type="text" class="form-control" placeholder="Optional notes">
                </div>
            </div>

            <!-- Actions -->
            <div class="text-end mt-4">
                <button type="reset" class="btn btn-secondary">Reset</button>
                <button type="submit" class="btn btn-success">Save Purchase Order</button>
                <button type="button" class="btn btn-outline-primary">Save & Approve</button>
            </div>

        </form>
    </div>
</div>
<?php include('../footer.php'); ?>