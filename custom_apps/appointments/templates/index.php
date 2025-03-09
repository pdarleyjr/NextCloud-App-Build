<?php
/**
 * @copyright Copyright (c) 2023 NextCloud App Build
 *
 * @author NextCloud App Build
 *
 * @license AGPL-3.0-or-later
 */
script('appointments', 'appointments-main');
style('appointments', 'style');
?>

<div id="appointments-app" data-user-id="<?php p($_['user_id']); ?>" data-is-therapist="<?php p($_['is_therapist']); ?>" data-square-environment="<?php p($_['square_environment']); ?>" data-square-application-id="<?php p($_['square_application_id']); ?>">
    <div id="app-loading">
        <div class="icon-loading"></div>
        <h2><?php p($l->t('Loading Appointments...')); ?></h2>
    </div>
</div>