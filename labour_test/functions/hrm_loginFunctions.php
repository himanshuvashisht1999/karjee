<?php
@ini_set('display_errors', 0);
set_time_limit(500);
error_reporting(E_ALL & ~E_WARNING & ~E_DEPRECATED & ~E_NOTICE);

function hrm_loginuserinfo($conn,$loginroleid,$shortby,$shortbyvalue){
    if($shortby=="all"){
        $lguq=$conn->query("SELECT sno, loginname, loginid, loginpassword, loginroleid, loginrolename, mobileno, emailid, isActive, isLogined, loginDatetime,comments FROM login_user_master WHERE (tedit is null or tedit='') and isActive='yes'  and loginroleid>'".$loginroleid."'");
    }else if($shortby=="loginid"){
        $lguq=$conn->query("SELECT sno, loginname, loginid, loginpassword, loginroleid, loginrolename, mobileno, emailid, isActive, isLogined, loginDatetime,comments  FROM login_user_master WHERE (tedit is null or tedit='') and loginid='".$shortbyvalue."'");
    }else if($shortby=="mobileno"){
        $lguq=$conn->query("SELECT sno, loginname, loginid, loginpassword, loginroleid, loginrolename, mobileno, emailid, isActive, isLogined, loginDatetime,comments  FROM login_user_master WHERE (tedit is null or tedit='') and mobileno='".$shortbyvalue."'");
    }else if($shortby=="sno"){
        $lguq=$conn->query("SELECT sno, loginname, loginid, loginpassword, loginroleid, loginrolename, mobileno, emailid, isActive, isLogined, loginDatetime,comments FROM login_user_master WHERE (tedit is null or tedit='') and sno='".$shortbyvalue."'");
    }
    $ullist=array();
    if($lguq->num_rows>0){
        while($lurow=$lguq->fetch_assoc()){
            $ullist[]=array('loginname'=>$lurow['loginname'], 'loginid'=>$lurow['loginid'], 'loginpassword'=>$lurow['loginpassword'], 'loginroleid'=>$lurow['loginroleid'], 'loginrolename'=>$lurow['loginrolename'], 'mobileno'=>($lurow['mobileno']?$lurow['mobileno']:"N/A"), 'emailid'=>($lurow['emailid']?$lurow['emailid']:"N/A"), 'isactive'=>($lurow['isActive']?$lurow['isActive']:"Active"), 'islogined'=>($lurow['isLogined']?ucwords($lurow['isLogined']):"Logout"), 'logindatetime'=>$lurow['loginDatetime'],'comments'=>$lurow['comments']);
        }
    }
    return $ullist;
}

function loginmodulesdetails_frommaster($conn,$companyid,$subcompanyid,$projectid,$shortby,$shortbyvalue){
    if($shortby=="allactive"){
        $lmd="SELECT m.sno, m.vieworderby, m.groupid, m.moduleid, m.modulename, m.modulefoldername, m.subaction, m.status, g.groupname, m.frmid, m.ispasswordprotected, m.integrationtype FROM login_modules_master_client m join login_group_master_client g on m.groupid=g.groupid WHERE m.status='active' and m.companyid='".$companyid."' and m.subcompanyid='".$subcompanyid."' and m.projectid='".$projectid."' order by m.vieworderby asc, m.modulename asc";
    }else if($shortby=="moduleid"){
        $lmd="SELECT m.sno, m.vieworderby, m.groupid, m.moduleid, m.modulename, m.modulefoldername, m.subaction, m.status, g.groupname, m.frmid, m.ispasswordprotected, m.integrationtype FROM login_modules_master_client m join login_group_master_client g on m.groupid=g.groupid WHERE m.status='active' and m.companyid='".$companyid."' and m.subcompanyid='".$subcompanyid."' and m.projectid='".$projectid."' and m.moduleid in ('".str_replace(",", "','",$shortbyvalue)."') order by m.vieworderby, m.modulename";
    }else if($shortby=="groupidwithmoduleid"){
        $shortbyvalexp=explode("|", $shortbyvalue);
        $groupid = $shortbyvalexp[0];
        $moduleids = $shortbyvalexp[1];
        $lmd="SELECT m.sno, m.vieworderby, m.groupid, m.moduleid, m.modulename, m.modulefoldername, m.subaction, m.status, g.groupname, m.frmid, m.ispasswordprotected, m.integrationtype FROM login_modules_master_client m join login_group_master_client g on m.groupid=g.groupid WHERE m.status='active' and m.companyid='".$companyid."' and m.subcompanyid='".$subcompanyid."' and m.projectid='".$projectid."' and m.groupid='".$groupid."' and m.moduleid in ('".str_replace(",", "','",$moduleids)."') order by m.vieworderby, m.modulename";
    }else if($shortby=="groupid"){
         $lmd="SELECT m.sno, m.vieworderby, m.groupid, m.moduleid, m.modulename, m.modulefoldername, m.subaction, m.status, g.groupname, m.frmid, m.ispasswordprotected, m.integrationtype FROM login_modules_master_client m join login_group_master_client g on m.groupid=g.groupid WHERE m.status='active' and m.companyid='".$companyid."' and m.subcompanyid='".$subcompanyid."' and m.projectid='".$projectid."' and m.groupid in ('".str_replace(",", "','",$shortbyvalue)."') order by m.vieworderby, m.modulename";
    }else if($shortby=="allactivegroup"){
        $lmd="SELECT m.sno, m.vieworderby, m.groupid, m.moduleid, m.modulename, m.modulefoldername, m.subaction, m.status, g.groupname, m.frmid, m.ispasswordprotected, m.integrationtype FROM login_modules_master_client m join login_group_master_client g on m.groupid=g.groupid WHERE m.status='active' group by m.groupid";
    }else if($shortby=="sno"){
        $lmd="SELECT sno, vieworderby, groupid, moduleid, modulename, modulefoldername, subaction, status, integrationtype FROM login_modules_master_client WHERE sno='".$shortbyvalue."'";
    }else if($shortby=="allactive_mainaccount"){
        $lmd="SELECT g.groupname  as groupid, l.sno, ifnull(l.vieworderby,'') as vieworderby,  l.moduleid, l.modulename, l.modulefoldername, l.subaction, l.status, l.frmid, l.ispasswordprotected, l.integrationtype FROM login_modules_master_client l join login_group_master_client g  on g.groupid =l.groupid   WHERE ifnull(l.status,'')='active' and ifnull(l.groupid,'') <>'' and ifnull(l.modulename,'') <>'' and ifnull(l.modulefoldername,'') <>'' and ifnull(l.groupid,'') <>'' and l.companyid='".$companyid."' and l.subcompanyid='".$subcompanyid."' and l.projectid='".$projectid."' order by g.groupname, l.vieworderby, l.modulename";
    }
    
    $lmdr=$conn->query($lmd);
    $mdlist=array();
    if($lmdr->num_rows>0){
        while($mrow=$lmdr->fetch_assoc()){
            $mdlist[]=array('sno'=>$mrow['sno'], 'vieworderby'=>$mrow['vieworderby'], 'mainmodule'=>$mrow['groupid'], 'groupid'=>$mrow['groupid'], 'groupname'=>$mrow['groupname'], 'moduleid'=>$mrow['moduleid'], 'modulename'=>$mrow['modulename'], 'modulefoldername'=>$mrow['modulefoldername'], 'subaction'=>$mrow['subaction'], 'status'=>$mrow['status'], 'frmid'=>$mrow['frmid'], 'ispasswordprotected'=>$mrow['ispasswordprotected'], 'integrationtype'=>$mrow['integrationtype']);
        }
    }
    return $mdlist;
}
?>