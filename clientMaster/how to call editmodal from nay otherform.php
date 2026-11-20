<?php
require_once __DIR__ . '/../clientMaster/modal/client_edit_form.php';
?>
<button
    type="button"
    class="btn btn-sm btn-primary dispatch-client-edit-btn"
    data-client-id="<?= htmlspecialchars((string)$row['client_id'], ENT_QUOTES, 'UTF-8') ?>"
    data-project-id="<?= htmlspecialchars((string)$projectid, ENT_QUOTES, 'UTF-8') ?>"
>
    <i class="fa fa-edit"></i> Edit Client
</button>

<script>
$(document).on('click', '.dispatch-client-edit-btn', function () {
    openClientEditModal(
        $(this).data('client-id'),
        $(this).data('project-id')
    );
});
</script>