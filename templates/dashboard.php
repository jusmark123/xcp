<?php 
global $user;

$tabs = array(
	'Overview',
	'Budget',
	'Expenses',
	'Income',
	'Reports',
	'Settings'
); ?>

<div id="dashboard" style="padding:0.5em;">
  <div id="tabs">
    <ul>
      <?php
		foreach( $tabs as $key => $tab ) {?>
      <li><a href="#tabs-<?php echo $key + 1;?>"><?php echo $tab;?></a></li>
      <?php } ?>
    </ul>
  <?php 
    foreach( $tabs as $key => $tab ) {?>
  <div id="tabs-<?php echo $key + 1;?>">
    <?php include( 'tabs/' . strtolower($tab) . '.php' ); ?>
  </div>
  <?php 
	} ?>
</div>
</div>
<script>
	(function($) {
		$(document).ready(function(e) {
            $('#tabs').tabs();
        });	
	})(jQuery);
</script>