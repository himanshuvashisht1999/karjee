  <details id="voiceinstrqlist" style="display:none;">
    <summary>Voice Instructions</summary>
    <div class="shade9">
      <div class="row">
        <div class="col-md-12">
          <div class="table-responsive">
            <table class="table table-light table-bordered" id="voiceinstruction_tbl">
              <thead>
                <tr>
                  <th style="text-align: center;">S.No</th>
                  <th style="text-align: center;">Instruction Date</th>
                  <th style='text-align: center;'>Instruction Type</th>
                  <th style="text-align: center; display: none;">Instruction Id</th>
                  <th style="text-align: center;">Amount</th>
                  <th style="text-align: center;">Payto Name</th>
                  <th style="text-align: center;">Purpose</th>
                  <th style="text-align: center; display: none;">Instrution Type</th>
                  <th>Instructed By</th>
                  <th style="text-align: center; display: none;">Listen Voice Instruction</th>
                  <th style='text-align:center; display:none;'>File Path</th>
                  <th style="text-align: center;">Digitize</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </details>
  <div id="voiceviewdiv"></div>
  <script type="text/javascript">
    var companyid = $("#hidden_companyid").val();
    var companyname = $("#hidden_companyname").val();
    var subcompanyid = $("#hidden_subcompanyid").val();
    var projectid = $("#hidden_projectid").val();
    var loginid = $("#hidden_loginid").val();
    var rooturl = $("#hidden_rooturl").val();
    voiceinstructiondertails_withshortby('hidden_rooturl','hidden_companyid','hidden_subcompanyid','hidden_projectid','allunapprove','', function(res){
      // console.log(res);
      if(res.length>0){
        var ltinstructon ="";
        var voicepath ="";
        var filepath2 ="";
        $("#voiceinstrqlist").show();
        for(var i=0; i<res.length; i++){
          ltinstructon = res[i].filepath!=""?"<i class='fas fa-microphone'></i>":"N/A";
          voicepath = res[i].filepath!=""?res[i].filepath:"N/A";
          filepath2 = res[i].filepath2!=""?res[i].filepath2:"";

          $("#voiceinstruction_tbl tbody").append("<tr data-voicepath='"+voicepath+"'><td style='text-align:center;'>"+(i + 1)+"</td><td style='text-align:center;' class='instdate_td'>"+res[i].instructiondatedsp+"</td><td style='text-align:center; class='insttype_td'>"+res[i].requesttype+"</td><td style='text-align:center; display:none;' class='instid_td'>"+res[i].instructionid+"</td><td style='text-align:center;' class='insamount_td'>"+res[i].requestamount+"</td><td style='text-align:center;' class='inspayto_td'>"+res[i].payto+"</td><td style='text-align:center;' class='inspurpose_td'>"+res[i].purpose+"</td><td style='text-align:center; display:none;' class='instructiontype_td'>"+res[i].instructiontype+"</td><td class='instby_td'>"+res[i].instructedbyname+"</td><td class='instpath_td' style='cursor:pointer; text-align:center; display:none;'>"+ltinstructon+"</td><td class='filepath_td' style='cursor:pointer; text-align:center; display:none;'>"+filepath2+"</td><td class='instby_td text-center'><button class='btn btn-sm btn-info mb-0 createbookregisterbtn' data-instructionid='"+res[i].instructionid+"' data-instructionamount='"+res[i].requestamount+"' data-paytonametxt='"+res[i].payto+"' data-purposetxt='"+res[i].purpose+"' data-instructiontype='"+res[i].instructiontype+"' data-subprojectid='"+res[i].subprojectid+"'> <i class='fa fa-tasks'></i></button></td></tr>");
        }
      }
    });

    $(document).on("click", ".instpath_td", function(){
      var rooturl = $("#hidden_rooturl").val();
      audiofileview(rooturl,$(this).closest("tr").data("voicepath"),"voiceviewdiv");
    });

    $(document).on("click", ".createbookregisterbtn", function(){
      var tablid = $(this).closest("table").prop("id");
      $("#"+tablid+" tbody tr").css("background-color", "");
      var key=$("#hidden_key").val();
      $("#requesthtmlview").html('');
      var subaction = $(this).closest("tr").find("td.insttype_td").text();
      $(this).closest("tr").css("background-color", "yellow");
      var instid = $(this).data("instructionid");
      var instamount = $(this).data("instructionamount");
      var subprojectid = $(this).data("subprojectid");
      var voicepath = $(this).closest("tr").data("voicepath");
      var filepath = $(this).closest("tr").find("td.filepath_td").text();
      var paytonametxt = $(this).data("paytonametxt");
      var purposetxt = $(this).data("purposetxt");
      var instructiontype = $(this).data("instructiontype");
      // alert(instamount);
      $("#makebook_asperinst_modal").modal('show');
      $("#pendingfortransferdetails").removeAttr("open");

      if(instid){
        $("#viewvoiceinstruction").empty();
        $("#viewvoiceinstruction").data("mdinstid", '');
        $("#viewvoiceinstruction").data("mdsubprojectid", '');
        $("#viewvoiceinstruction").data("mdinstamount", '');
        $("#viewvoiceinstruction").data("mdsubaction", '');
        $("#viewvoiceinstruction").data("mdvoicepath", '');
        $("#viewvoiceinstruction").data("mdfilpath", '');
        var dwnforsheetbtn="";
        voiceinstructiondertails_withshortby('hidden_rooturl','hidden_companyid','hidden_subcompanyid','hidden_projectid','instructionid',instid, function(res){
          console.log(res);
          if(res.length>0){            
              $("#viewvoiceinstruction").data("mdinstid", instid);
              $("#viewvoiceinstruction").data("mdsubprojectid", subprojectid);
              $("#viewvoiceinstruction").data("mdinstamount", instamount);
              $("#viewvoiceinstruction").data("mdsubaction", subaction);
              $("#viewvoiceinstruction").data("mdvoicepath", voicepath);
              $("#viewvoiceinstruction").data("mdfilpath", filepath);              
              for(var i=0; i<res.length; i++){
                if(res[i].filepath){
                  $("#viewvoiceinstruction").append('<div class="row"><div class="col-md-12"><label>Listen voice instructions</label><audio id="readytorequest_instid" controlsList="nodownload" src="'+rooturl+res[i].filepath+'" class="form-control" class="audioPlayback" controls></audio></div></div>');
                }
              }
              $("#viewvoiceinstruction").append('<div class="row"><div class="col-md-12">');
              for(var i=0; i<res.length; i++){
                // alert(res[i].filepath2);
                if(res[i].filepath2){
                  $("#viewvoiceinstruction").append('<button class="btn-sm m-2 btn-deepblue" data-filepath2="' + rooturl + res[i].filepath2 + '" style="border-width: thin;" onclick="showPreview_document(this.getAttribute(\'data-filepath2\'));">Preview Document</button>');
                }

                if(res[i].filepath3){
                  $("#viewvoiceinstruction").append('<button class="btn-sm m-2 btn-deepblue" data-filepath2="' + rooturl + res[i].filepath3 + '" style="border-width: thin;" onclick="showPreview_document(this.getAttribute(\'data-filepath2\'));">Preview Document 2</button>');
                }
                $("#viewvoiceinstruction").append('</div></div>');
              }


              $("#viewvoiceinstruction").append("<div class='row mt-4'><div class='col-md-6'><label class='p-1'><input type='radio' name='actiontonextprocess' value='reject' /> Reject voice instruction?</label></div><div class='col-md-6'><label class='p-1'><input type='radio' name='actiontonextprocess' value='accepttodigitize' /> Next for Digitization</label></div></div>");
            
          }
        });
        
      }
    });
    $(document).on("click", "input[name='actiontonextprocess']", function(){
      var key=$("#hidden_key").val();
      $("#requesthtmlview").html('');
      if($(this).val()=="reject"){
        $("#requesthtmlview").load(rooturl+"webpages/email_voiceinstruction_reject.php?k="+key+"&subaction="+$("#viewvoiceinstruction").data("mdsubaction")+"&dz=2&insturl="+$("#viewvoiceinstruction").data("mdvoicepath")+"&insid="+$("#viewvoiceinstruction").data("mdinstid")+"&insamount="+$("#viewvoiceinstruction").data("mdinstamount")+"&filpath="+$("#viewvoiceinstruction").data("mdfilpath"));
      }else if($(this).val()=="accepttodigitize"){
        var mdsubaction = $("#viewvoiceinstruction").data("mdsubaction");
        var mdvoicepath = $("#viewvoiceinstruction").data("mdvoicepath");
        var mdinstid = $("#viewvoiceinstruction").data("mdinstid");
        var mdinstamount = $("#viewvoiceinstruction").data("mdinstamount");
        var mdfilpath = $("#viewvoiceinstruction").data("mdfilpath");
        var mdsubprojectid = $("#viewvoiceinstruction").data("mdsubprojectid");
        $("#requesthtmlview").load(rooturl+"expenditure_expencessheet/request_voiceinstruction_desigitization.php?k="+key+"&subaction="+$("#viewvoiceinstruction").data("mdsubaction")+"&dz=2&insturl="+$("#viewvoiceinstruction").data("mdvoicepath")+"&insid="+$("#viewvoiceinstruction").data("mdinstid")+"&insamount="+$("#viewvoiceinstruction").data("mdinstamount")+"&filpath="+$("#viewvoiceinstruction").data("mdfilpath")+"&subprojectid="+$("#viewvoiceinstruction").data("mdsubprojectid"));
      }
    });
// savetodb_postpaymententrypart();
    /*$(document).on("click", "input[name='actiontonextprocess']", function(){
      var key=$("#hidden_key").val();
      // $("#rqdiv").show();
      // $("#videtail").hide();
      $('#videtail').prop('open', false);
      // videtail
      if($(this).val()=="reject"){
        $("#requesthtmlview").load(rooturl+"webpages/email_voiceinstruction_reject.php?k="+key+"&subaction="+$("#viewvoiceinstruction").data("mdsubaction")+"&dz=2&insturl="+$("#viewvoiceinstruction").data("mdvoicepath")+"&insid="+$("#viewvoiceinstruction").data("mdinstid")+"&insamount="+$("#viewvoiceinstruction").data("mdinstamount")+"&filpath="+$("#viewvoiceinstruction").data("mdfilpath"));
      }else if($(this).val()=="accepttodigitize"){
        // console.log($("#viewvoiceinstruction").data("mdsubaction"));
        // console.log($("#viewvoiceinstruction").data("mdvoicepath"));
        // console.log($("#viewvoiceinstruction").data("mdinstid"));
        // console.log($("#viewvoiceinstruction").data("mdinstamount"));
        // console.log($("#viewvoiceinstruction").data("mdfilpath"));
        // console.log($("#viewvoiceinstruction").data("mdsubprojectid"));
        $("#requesthtmlview").load(rooturl+"expenditure_expencessheet/index.php?k="+key+"&subaction="+$("#viewvoiceinstruction").data("mdsubaction")+"&dz=2&insturl="+$("#viewvoiceinstruction").data("mdvoicepath")+"&insid="+$("#viewvoiceinstruction").data("mdinstid")+"&insamount="+$("#viewvoiceinstruction").data("mdinstamount")+"&filpath="+$("#viewvoiceinstruction").data("mdfilpath")+"&subprojectid="+$("#viewvoiceinstruction").data("mdsubprojectid"));
      }
      // else if($(this).val()=="minimumdigitizetot3"){
      //    $("#requesthtmlview").load(rooturl+"expenditure_expencessheet/index.php?k="+key+"&subaction=postpayment&dz=2&insturl="+$("#viewvoiceinstruction").data("mdvoicepath")+"&insid="+$("#viewvoiceinstruction").data("mdinstid")+"&insamount="+$("#viewvoiceinstruction").data("mdinstamount")+"&filpath="+$("#viewvoiceinstruction").data("mdfilpath")+"&subprojectid="+$("#viewvoiceinstruction").data("mdsubprojectid"));

      // }
      else if($(this).val()=="gotot3"){
        $("#requesthtmlview").load(rooturl+"webpages/voiceinstruction_justforwardT3.php?k="+key+"&subaction="+$("#viewvoiceinstruction").data("mdsubaction")+"&dz=2&insturl="+$("#viewvoiceinstruction").data("mdvoicepath")+"&insid="+$("#viewvoiceinstruction").data("mdinstid")+"&filpath="+$("#viewvoiceinstruction").data("mdfilpath")+"&subprojectid="+$("#viewvoiceinstruction").data("mdsubprojectid"));
      }
      // else if($(this).val()=="minimumdigitizetot3"){
      //   $("#requesthtmlview").load(rooturl+"webpages/voiceinst_minmdigitizationT3.php?k="+key+"&subaction="+$("#viewvoiceinstruction").data("mdsubaction")+"&dz=2&insturl="+$("#viewvoiceinstruction").data("mdvoicepath")+"&insid="+$("#viewvoiceinstruction").data("mdinstid")+"&insamount="+$("#viewvoiceinstruction").data("mdinstamount")+"&filpath="+$("#viewvoiceinstruction").data("mdfilpath"),function(){
      //       $.getScript(rooturl+"/js/actioncontrol.js");
      //   });

      // }
    });*/
  </script>