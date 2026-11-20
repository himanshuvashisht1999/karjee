<?php
return [
    'enabled' => true,
    // First follow-up for an order with no follow-up history (calendar days).
    'default_followup_days' => 1,
    'default_followup_time' => '10:30', // HH:MM, Asia/Calcutta

    // One reminder before and one after the scheduled time, in minutes.
    // Set either value to 0 to disable that reminder.
    'reminder_before_minutes' => 60,
    'reminder_after_minutes' => 60,
    'show_on_login' => true,
    'show_overdue' => true,
    'poll_seconds' => 60,
    'followup_page' => 'krf_enquiry_followup/enquiry_followup.php',
    // Optional: enter the USER follow-up module ID from your module master.
    'followup_module_id' => '',
];
