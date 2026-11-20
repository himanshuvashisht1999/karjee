<?php
function loginuserinfo($conn,$loginroleid,$shortby,$shortbyvalue){
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

function login_rolefrommaster($conn,$shortby,$shortbyvalue){
	if($shortby=="allactive"){
		$lmd="SELECT roleid, rolename, rolename_display, rolelevel FROM login_role_master WHERE status='active' order by roleid";
	}else if($shortby=="roleid"){
		$lmd="SELECT roleid, rolename, rolename_display, rolelevel FROM login_role_master WHERE roleid='".$shortbyvalue."' order by roleid";
	}else if($shortby=="afterloginroleid"){
		$lmd="SELECT roleid, rolename, rolename_display, rolelevel FROM login_role_master WHERE  status='active' and roleid>'".$shortbyvalue."' order by roleid";
	}
	$lmdr=$conn->query($lmd);
	$mdlist=array();
	if($lmdr->num_rows>0){
		while($mrow=$lmdr->fetch_assoc()){
			$mdlist[]=array('roleid'=>$mrow['roleid'], 'rolename'=>$mrow['rolename'], 'rolelevel'=>$mrow['rolelevel'], 'rolename_display'=>$mrow['rolename_display']);
		}
	}
	return $mdlist;
}
?>