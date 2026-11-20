<?php
// Include once from the common footer.php after the other script tags.
$fuReminderConfig = require __DIR__ . '/followup_config.php';
if (!empty($loginid) && !empty($projectid) && !empty($fuReminderConfig['enabled'])):
?>
<script>
window.krfFollowupSessionTag=<?= json_encode(hash('sha256',session_id())) ?>;
window.krfFollowupConfig=<?= json_encode($fuReminderConfig, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
<script src="<?= htmlspecialchars(rtrim($rooturl,'/') . '/krf_order_followup/followup_sweetalert.js?v=' . filemtime(__DIR__ . '/followup_sweetalert.js'),ENT_QUOTES,'UTF-8') ?>"></script>
<script src="<?= htmlspecialchars(rtrim($rooturl,'/') . '/krf_order_followup/followup_reminders.js?v=' . filemtime(__DIR__ . '/followup_reminders.js'),ENT_QUOTES,'UTF-8') ?>"></script>
<?php endif; ?>
