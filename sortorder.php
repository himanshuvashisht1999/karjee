<?php  
require('header.php');

if($companyid!=""){
  $modalwin = isset($_GET["modal"])?"1":"";
?>
      <div class="row">
          <div class="col-md-12 mt-1 mb-2 pt-3 pb-3 text-center" style="box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);">
              <span class="uppercase  bold" style="font-size: 1.75rem;text-shadow:2px 2px gray;">Display Sort Order Setting</span>
          </div>
      </div>
      <div class="row">
        <div class="col-md-12 ">
          Note: To re-position the particular Sub-Category, simply drag and drop it to that position.
        </div>
      </div>
      <div class="row">
        <div class="col-md-12">
          <table class="table table-bordered table-light" id="sortablesubcategory">
            <colgroup>
              <col style="width:60px;">
            </colgroup>
              <thead>
                  <tr>
                      <th>#</th>
                      <th>Subcategory</th>
                  </tr>
              </thead>
              <tbody>
              </tbody>
          </table>
        </div>
      </div>
      <div class="row">
        <div class="col-md-12">
          <button class="btn btn-success m-1 pull-right d-none" id="saveOrder">Save Order</button>
        </div>
      </div>
<?php
}
require('footer.php');
?>
<script type="text/javascript">
  var modalwin = "<?php echo $modalwin; ?>";
  if(modalwin){
    $(".navbar").hide();
    $(".companyheading").hide();
  }
  loaddata("hidden_companyid","hidden_subcompanyid","hidden_projectid",function(res){
    if(res.length>0){
      var tbodyhtml = "";
      for(i=0;i<res.length;i++){
        if(tbodyhtml){
          tbodyhtml = tbodyhtml+ '<tr class="drag-row" data-id="'+res[i].sub_categoryid+'"><td>'+(i+1)+'</td><td>'+res[i].sub_category+'</td></tr>';
        }
        else{
          tbodyhtml = '<tr class="drag-row" data-id="'+res[i].sub_categoryid+'"><td>'+(i+1)+'</td><td>'+res[i].sub_category+'</td></tr>';
        }
      }
      $("#sortablesubcategory tbody").html(tbodyhtml);
    }
  });
  $(function() {
      // Enable sortable functionality
      $("#sortablesubcategory tbody").sortable({
          helper: "clone",
          update: function(event, ui) {
              // Update the row numbering dynamically
              $("#sortablesubcategory tbody tr").each(function(index) {
                  $(this).find("td:first").text(index + 1);
              });
              savechanges();
          }
      }).disableSelection();

      // Save the updated order
      $("#saveOrder").on("click", function () {
        savechanges();
      });

  });

  function savechanges(){
    let order = [];
    $("#sortablesubcategory tbody tr").each(function () {
        order.push($(this).data("id"));
    });

    var form_data = new FormData();
    var companyid = $("#hidden_companyid").val();
    form_data.append("companyid", companyid);
    var subcompanyid = $("#hidden_subcompanyid").val();
    form_data.append("subcompanyid", subcompanyid);
    var projectid = $("#hidden_projectid").val();
    form_data.append("projectid", projectid);
    form_data.append("order", JSON.stringify(order)); 

    $.ajax({
        url: "modal/request_modal.php?action=save_subcategoryorder",
        type: "POST",
        data: form_data,
        processData: false, 
        contentType: false, 
        success: function (response) {
            loaddata("hidden_companyid","hidden_subcompanyid","hidden_projectid",function(res){
                if(res.length>0){
                  var tbodyhtml = "";
                  for(i=0;i<res.length;i++){
                    if(tbodyhtml){
                        tbodyhtml = tbodyhtml+ '<tr class="drag-row" data-id="'+res[i].sub_categoryid+'"><td>'+(i+1)+'</td><td>'+res[i].sub_category+'</td></tr>';
                    }
                    else{
                      tbodyhtml = '<tr class="drag-row" data-id="'+res[i].sub_categoryid+'"><td>'+(i+1)+'</td><td>'+res[i].sub_category+'</td></tr>';
                    }
                  }
                  $("#sortablesubcategory tbody").html(tbodyhtml);
                }
            });
        },
        error: function () {
            alert("An error occurred while saving the changes.");
            location.reload();
        },
    });
  }

  function loaddata(companyid_eid,subcompanyid_eid,projectid_eid,callback){
    var form_data = new FormData();
    var companyid = $("#"+companyid_eid+"").val();
    form_data.append("companyid", companyid); 
    var subcompanyid = $("#"+subcompanyid_eid+"").val();
    form_data.append("subcompanyid", subcompanyid); 
    var projectid = $("#"+projectid_eid+"").val();
    form_data.append("projectid", projectid);
    $.ajax
    ({
      type: "POST",
      url: "modal/request_modal.php?action=getsubcategorywithorder",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
        callback(res);
      }
    });
  } 

</script>