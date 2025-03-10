<?php
/** @var array $_ */
if(isset($_['disabled']) && $_['disabled']===true){
    //occ config:app:set appointments limitToGroups --value '["group"]'
    echo "<div style='width:100%; font-weight: bold; font-size:150%; opacity: .5; text-align: center;margin-top: 5em;'>This page is disabled by your sysadmin.</div>";
} else {
    script('appointments', 'appointments-main');
    style('appointments', 'style');
?>

<div id="appointments-app" 
     data-user-id="<?php p($_['userId']); ?>"
     data-is-therapist="<?php p($_['isTherapist'] ? 'true' : 'false'); ?>"
     data-square-environment="<?php p($_['squareEnvironment']); ?>"
     data-square-application-id="<?php p($_['squareApplicationId']); ?>">
</div>

<?php
}
?>
