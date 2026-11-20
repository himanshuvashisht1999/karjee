<?php
error_reporting(E_ALL &~E_WARNING &~E_NOTICE );
date_default_timezone_set("Asia/Calcutta");
ini_set('display_errors', '1');
require('connection.php');
require('functions/function_master.php');
require('createinvoice/function/functionmaster_inv.php');
$curnow = date("Y-m-d H:i:s");
$soqry="SELECT ifnull(p.finyr,'') as finyr ,ifnull(s.currencyid,'') as currencyid ,s.sno,s.invoicedate,s.termdays,s.purpose,s.purposecode,s.invoiceamount,s.invoicetoid,s.invoiceto, s.stdorderno, s.rate, s.qty, s.companyid,s.subcompanyid, s.projectid, s.remark, s.description, s.taxamt, s.cgstpercent, s.vatpercentage from tbl_standingorder_master s left join tbl_project_master p on p.companyid=s.companyid and p.subcompanyid = s.companyid and p.projectid=s.projectid where (s.isactive is null or s.isactive='' or s.isactive='yes') and date(s.invoicedate)<=date(now())";// and month(invoicedate)=month(now()) and year(invoicedate)=year(now())
$res=$conn->query($soqry);
if($res->num_rows>0){
	while($row=$res->fetch_assoc()){
	  $invqry="SELECT sno,companyid,subcompanyid,projectid,invoice_date,invoice_no,invoicepurpose,invoice_amount,client_id,client_name,description,rate,refno,terms_days from tbl_invoices where invoice_date='".$row["invoicedate"]."' and client_id='".$row["invoicetoid"]."' and invoicepurpose='".$row["purpose"]."' and companyid='".$row["companyid"]."' and subcompanyid='".$row["subcompanyid"]."' and projectid='".$row["projectid"]."'";
	//,taxamt

		$result=$conn->query($invqry);
		if($result->num_rows<=0){
			$finyrid=$row["finyr"]||1;
			$finyr=getFinancialYear_monthbased($conn,$row["invoicedate"], $finyrid);

			$invoice_id=getmaxinvoiceno("tbl_invoices",$row["companyid"],$row["subcompanyid"],$row["projectid"],$finyr,$finyrid,'Standard Instruction',$conn);			 
			$invoice_no="SOI-".$invoice_id; 
			mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    		$conn->begin_transaction();
    		try{ 

				$withouttax=floatval($row["invoiceamount"]||0)-floatval($row["taxamt"]||0);

				   $insinv = "INSERT INTO tbl_invoices(invoice_fromname, companyid, subcompanyid, projectid, invoice_type, invoice_date,invoice_id,invoice_no,invoicepurpose,invoicepurposecode,invoice_amount,client_id,client_name,description,rate,refno,terms_days,currencyid,enterdate,vatpercent, gstpercent,tax_amount,finyr,amount_exclude_tax)values('Standard Instruction','".$row["companyid"]."','".$row["subcompanyid"]."','".$row["projectid"]."','simple','".$row["invoicedate"]."','".$invoice_id."','".$invoice_no."','".$row["purpose"]."','".$row["purposecode"]."','".$row["invoiceamount"]."','".$row["invoicetoid"]."','".$row["invoiceto"]."','".addslashes($row["description"])."','".$row["rate"]."','".$row["stdorderno"]."','".$row["termdays"]."','".$row["currencyid"]."','".$curnow."' ,'".$row["vatpercentage"]."','".$row["cgstpercent"]."','".$row["taxamt"]."','".$finyr."','".$withouttax."' )";
				$conn->query($insinv); 
				    $insinvbrk = "INSERT INTO tbl_invoices_breakdown (companyid,subcompanyid,projectid,finyr,invoice_no, invoice_id,taxable_amount,totalamount_excludingtax, totalamount_includetax)values('".$row["companyid"]."','".$row["subcompanyid"]."','".$row["projectid"]."','".$finyr."' ,'".$invoice_no."','".$invoice_id."','".$row["taxamt"]."','".$row["invoiceamount"]."','".$withouttax."' )";

				$conn->query($insinvbrk);
				$updinv = "UPDATE tbl_standingorder_master set invoicedate = CASE termdays WHEN 'weekly' THEN DATE_ADD( invoicedate , INTERVAL 7 DAY) WHEN 'bi-monthly' THEN DATE_ADD(invoicedate , INTERVAL 15 DAY) WHEN 'monthly' THEN DATE_ADD(invoicedate , INTERVAL 1 MONTH) WHEN 'quarterly' THEN DATE_ADD(invoicedate , INTERVAL 3 MONTH)WHEN 'six-monthly' THEN DATE_ADD(invoicedate , INTERVAL 6 MONTH) WHEN 'yearly' THEN DATE_ADD(invoicedate , INTERVAL 1 YEAR) END, nextinvoicedate = CASE termdays WHEN 'weekly' THEN DATE_ADD(nextinvoicedate , INTERVAL 7 DAY) WHEN 'bi-monthly' THEN DATE_ADD(nextinvoicedate , INTERVAL 15 DAY) WHEN 'monthly' THEN DATE_ADD(nextinvoicedate , INTERVAL 1 MONTH) WHEN 'quarterly' THEN DATE_ADD(nextinvoicedate , INTERVAL 3 MONTH) WHEN 'six-monthly' THEN DATE_ADD(nextinvoicedate , INTERVAL 6 MONTH) WHEN 'yearly' THEN DATE_ADD(nextinvoicedate , INTERVAL 1 YEAR) END where sno='".$row["sno"]."'";
				$conn->query($updinv); 
				$conn->commit();
    		}
    		catch (mysqli_sql_exception $e) {
	        	$conn->rollback();
	        	echo $e;
    		}	
		}

	}
}
?>