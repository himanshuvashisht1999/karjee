<?php
function confirmedAuthorize($addressOnly=false) {
    global $authconn,$companyid,$subcompanyid,$projectid,$loginid,$loginroleid;
    if(session_status()!==PHP_SESSION_ACTIVE || empty($_SESSION['sessionid']) || $_SESSION['sessionid']!==session_id() || empty($loginid) || empty($projectid))throw new RuntimeException('Login is required.');
    $modules=((string)$loginroleid==='0')?getAllClientModules($authconn,$companyid,$subcompanyid,$projectid,'status','active'):OnlyProject_loginmodulesdetails_frommaster($authconn,$companyid,$subcompanyid,$projectid,'allwithauth',allauthorizedmoduleid($authconn,$companyid,$subcompanyid,$projectid,$loginid));
    foreach((array)$modules as $module){
        $path=trim(str_replace('\\','/',explode('?',(string)($module['modulefoldername']??''))[0]),'/');
        if(in_array($path,($addressOnly?['krf_provisional_sale_order_edit/edit_confirmed_order.php','krf-orderandsales/confirmed_order_modification_admin.php','krf-orderandsales/finalsaleorder_review_new.php']:['krf_provisional_sale_order_edit/edit_confirmed_order.php','krf-orderandsales/confirmed_order_modification_admin.php']),true))return;
    }
    throw new RuntimeException('Access to confirmed-order editing is required.');
}
function confirmedAjaxContext($checkEditPermission=true){
    global $companyid,$subcompanyid,$projectid,$loginid,$loginname;
    if(session_status()===PHP_SESSION_NONE)session_start();
    if(empty($_SESSION['sessionid'])||$_SESSION['sessionid']!==session_id())throw new RuntimeException('Session expired. Please log in again.');
    if(!isset($_GET['k'])&&isset($_POST['k']))$_GET['k']=$_POST['k'];
    $cwd=getcwd();chdir(dirname(__DIR__,2));ob_start();
    try{require dirname(__DIR__,2).'/header.php';}finally{ob_end_clean();chdir($cwd);}
    // header variables are local to this function, export authenticated context.
    foreach(['authconn','krfconn','companyid','subcompanyid','projectid','loginid','loginname','loginroleid'] as $name)if(isset($$name))$GLOBALS[$name]=$$name;
    if($checkEditPermission)confirmedAuthorize();
    return [(string)$companyid,(string)$subcompanyid,(string)$projectid,(string)$loginid];
}
