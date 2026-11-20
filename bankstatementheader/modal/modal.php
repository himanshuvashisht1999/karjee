<?php 
  require('../../connection.php');
  require('../../functions/function_master.php');
  include('../../SimpleXLSX.php');
  include('../../SimpleXLS.php');
  $action=$_GET['action'];
  if ($action=='statementkey') {
     $companyid=$_REQUEST['companyid'];
     $subcompanyid=$_REQUEST['subcompanyid'];
     $projectid=$_REQUEST['projectid'];
     $accountid=$_REQUEST['accountid'];
     $bankid=$_REQUEST['bankid'];
     $bankname=$_REQUEST['bankname'];
     $hrow=$_REQUEST['hrow'];
     $valuedateposition=$_REQUEST['valuedateposition'];
     $valuedatetext=$_REQUEST['valuedatetext'];
     $postdateposition=$_REQUEST['postdateposition'];
     $postdatetext=$_REQUEST['postdatetext'];
     $branchposition=$_REQUEST['branchposition'];
     $branchtext=$_REQUEST['branchtext'];
     $typeposition=$_REQUEST['typeposition'];
     $descriptionposition=$_REQUEST['descriptionposition'];
     $descriptiontext=$_REQUEST['descriptiontext'];
     $chequeposition=$_REQUEST['chequeposition'];
     $chequetext=$_REQUEST['chequetext'];
     $debitposition=$_REQUEST['debitposition'];
     $debittext=$_REQUEST['debittext'];
     $creditposition=$_REQUEST['creditposition'];
     $credittext=$_REQUEST['credittext'];
     $balanceposition=$_REQUEST['balanceposition'];
     $balancetext=$_REQUEST['balancetext'];
     $transactionposition=$_REQUEST['transactionposition'];
     $transactiontext=$_REQUEST['transactiontext'];
     $crdrposition=$_REQUEST['crdrposition'];
     $crdrtext=$_REQUEST['crdrtext'];
     $amountposition=$_REQUEST['amountposition'];
     $amounttext=$_REQUEST['amounttext'];

     $npposition=$_REQUEST['npposition'];
     $payernameposition=$_REQUEST['payernameposition'];
     $paytonameposition=$_REQUEST['paytonameposition'];

     $edit=$_REQUEST['edit'];
     if ($edit) {
      $qryinst="Update tbl_bankstatement_import_keys set valuedate='".$valuedateposition."',postdate='".$postdateposition."',description='".$descriptionposition."',type='".$typeposition."',chequeno='".$chequeposition."',transactiondetails='".$transactionposition."',branch='".$branchposition."',amount='".$amountposition."',creditamount='".$creditposition."',debitamount='".$debitposition."',drcr='".$crdrposition."',balanceamount='".$balanceposition."',hrow='".$hrow."' where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and bankid='".$bankid."' and accountid='".$accountid."' and sno='".$edit."'";
     }else{
       $qryinst="Insert into tbl_bankstatement_import_keys(companyid,subcompanyid,projectid,bankid,accountid,bankname,valuedate,postdate,type,description,chequeno,transactiondetails,branch,amount,creditamount,debitamount,drcr,balanceamount,hrow,payername,paytoname,sign) values('".$companyid."','".$subcompanyid."','".$projectid."','".$bankid."','".$accountid."','".$bankname."','".$valuedateposition."','".$postdateposition."','".$typeposition."','".$descriptionposition."','".$chequeposition."','".$transactionposition."','".$branchposition."','".$amountposition."','".$creditposition."','".$debitposition."','".$crdrposition."','".$balanceposition."','".$hrow."','".$payernameposition."','".$paytonameposition."','".$npposition."')";
    }
      $resultint=$conn->query($qryinst);
      if ($resultint) {
        $message[]=array('status' =>'Done','message'=>'Data successfully saved');
      }else{
        $message[]=array('status' =>'Error','message'=>'Data not successfully saved');
      }
     
     echo json_encode($message);
  }else if ($action=='newstatementkey') {
     $companyid=$_REQUEST['companyid'];
     $subcompanyid=$_REQUEST['subcompanyid'];
     $projectid=$_REQUEST['projectid'];
     $accountid=$_REQUEST['accountid'];
     $bankid=$_REQUEST['bankid'];
     $bankname=$_REQUEST['bankname'];
     $hrow=$_REQUEST['hrow'];
     $sqltablename=$_REQUEST['sqltablename'];
     //$tablestructure=$_REQUEST['tablestructure'];
     $tablestructure = json_decode($_POST['tablestructure'],TRUE);
     $edit=$_REQUEST['edit'];
     $maininsertstring="";
     if ($edit) {
       // code...
     }else{
        $insertstrig="companyid,subcompanyid,projectid,bankid,accountid,bankname";
         $insertval="";
         foreach ($tablestructure as $keyk ) {
          if ($insertstrig) {
            $insertstrig.=",".$keyk['columnname'];
          }else{
            $insertstrig=$keyk['columnname'];
          }      
         }

         $insertstrig="Insert Into ".$sqltablename."(".$insertstrig.")";

         foreach ($tablestructure as $keykv ) {
           $mappingvalue="";
            $mapping=$keykv['mapping'];
            if ($mapping && $keykv['columnname']<>"hrow") {
              $mappingvalue= getAlphabetValue($mapping);
            }else if ($keykv['columnname']=="hrow") {
              $mappingvalue=$mapping;
            }else{
               $mappingvalue="";
            }

            if ($insertval) {
              $insertval.=",'".$mappingvalue."'";
            }else{
              $insertval="'".$companyid."','".$subcompanyid."','".$projectid."','".$bankid."','".$accountid."','".$bankname."','".$mappingvalue."'";
            }
         }
         
          $insertval="values( ".$insertval.")";
          $maininsertstring=$insertstrig.$insertval;
          

     }

   $qryX=$maininsertstring;
    $result = $conn->query($qryX) or die($conn->error);
    if ($result) {
       $message[]=array('status' =>'Done','message'=>'Data successfully saved');
    }else{
       $message[]=array('status' =>'Error','message'=>'Data not successfully saved');
    }


     echo json_encode($message);


  }else if ($action=='getstatementkey') {
     $companyid=$_REQUEST['companyid'];
     $subcompanyid=$_REQUEST['subcompanyid'];
     $projectid=$_REQUEST['projectid'];
     $accountid=$_REQUEST['accountid'];
     $bankid=$_REQUEST['bankid'];
     $listkey=array();
    $qry="Select sno,companyid,subcompanyid,projectid,bankid,accountid,bankname,ifnull(valuedate,'') as valuedate,ifnull(postdate,'') as postdate ,ifnull(type,'') as type,ifnull(description,'') as description,ifnull(chequeno,'') as chequeno,ifnull(transactiondetails,'') as transactiondetails ,ifnull(branch ,'') as branch,ifnull(amount,'') as amount,ifnull(creditamount,'') as creditamount,ifnull(debitamount,'') as debitamount,ifnull(drcr,'') as drcr,ifnull(balanceamount,'') as balanceamount,ifnull(hrow,'') as hrow from tbl_bankstatement_import_keys where companyid='".$companyid."' and subcompanyid='".$subcompanyid."'and projectid='".$projectid."' and accountid='".$accountid."'and bankid='".$bankid."'";
     $resultsel=$conn->query($qry);
     if ($resultsel->num_rows>0) {
      while ($rowp=$resultsel->fetch_assoc()) {
        $listkey[]=array('bankname'=>$rowp['bankname'],'valuedate'=>$rowp['valuedate'],'postdate'=>$rowp['postdate'],'type'=>$rowp['type'],'description'=>$rowp['description'],'chequeno'=>$rowp['chequeno'],'transactiondetails'=>$rowp['transactiondetails'],'branch'=>$rowp['branch'],'amount'=>$rowp['amount'],'creditamount'=>$rowp['creditamount'],'debitamount'=>$rowp['debitamount'],'drcr'=>$rowp['drcr'],'balanceamount'=>$rowp['balanceamount'],'hrow'=>$rowp['hrow'],'sno'=>$rowp['sno']);
      }
        
     }
     echo json_encode($listkey);
  }else if ($action=='getheaderfromexcelsheet') {
    $file=$_FILES['files'];
    $stringvalue=array();
    if(is_array($file)){
    $file_ext = pathinfo($_FILES['files']['name'],PATHINFO_EXTENSION);
    $file_path=$_FILES['files']['tmp_name'];
    }else{
      $file_ext = pathinfo($_FILES['files'],PATHINFO_EXTENSION);
      $file_path = $_FILES['files'];
    }
    if ($objectX = $file_ext=="xlsx"?SimpleXLSX::parse($file_path):SimpleXLS::parse($file_path)) {
      $headerrow=detectheaderrow($objectX->rows());
      $headerrow=$headerrow;
      $i=0;
      foreach ($objectX->rows() as $excelrow) {
        if ($headerrow==$i) {
           $stringvalue[]=array("val"=>$headerrow);
          for ($col=0; $col <count($excelrow) ; $col++){
            
            $stringvalue[]=array("val"=>$excelrow[$col]);
          } 
        }
        $i++;
      }
    }

    echo json_encode($stringvalue);
  }else if ($action=='getcolumeofsqltable') {
    $stringvalue=array();
    $tablename=$_REQUEST['tablename'];
    if ($result = $conn -> query("SELECT DATABASE()")) {
    $row = $result -> fetch_row();
    $dbname= $row[0];
    
    }
    $qryt="Select COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '".$dbname."' and TABLE_NAME='".$tablename."'";
    $resultt=$conn->query($qryt);
    if ($resultt->num_rows>0) {
      while ($rowt=$resultt->fetch_assoc()) {
        if ($rowt['COLUMN_NAME']!="companyid") {
           $stringvalue[]=array("val"=>$rowt['COLUMN_NAME']);
        }
        
      }
    }

    echo json_encode($stringvalue);
  }

  function getAlphabetValue($alphabet) {
    // Define the alphabet array
    $arrayAlphabet = [
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L',
        'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'
    ];

    // Convert input to uppercase for a case-insensitive match
    $alphabet = strtoupper($alphabet);

    // Find the position in the array
    $position = array_search($alphabet, $arrayAlphabet);

    if ($position !== false) {
        return $position; // Return the index
    } else {
        return -1; // Return -1 if not found
    }
}

?>