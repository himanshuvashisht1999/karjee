<?php
// Include once from common footer.php after jQuery and dashboard scripts.
$efConfig=require __DIR__.'/config.php';
if(!empty($loginid)&&!empty($projectid)&&!empty($efConfig['enabled'])):
?>
<script>window.krfEnquirySessionTag=<?=json_encode(hash('sha256',session_id()))?>;window.krfEnquiryFollowupConfig=<?=json_encode($efConfig,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)?>;</script>
<script src="<?=htmlspecialchars(rtrim($rooturl,'/').'/krf_enquiry_followup/enquiry_reminders.js?v='.filemtime(__DIR__.'/enquiry_reminders.js'),ENT_QUOTES,'UTF-8')?>"></script>
<?php endif;?>
