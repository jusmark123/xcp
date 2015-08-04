<?php 
	error_reporting(E_ERROR | E_WARNING | E_PARSE);
	
	define( 'MAIN_DIR', dirname(__FILE__) );
	define( 'TEMPLATE_DIR', dirname(__FILE__) . '/templates/');
	
	include_once( 'php/functions.php' );
 	include_once( 'php/class-user.php' );

	sec_session_start();

	global $user;
	
	$title = 'Welcome';
	
	include_once( 'php/xcp-head.php' );
?><body>
<div class="wrap" style="display:none;">
  <div class="main">
    <h1 id="xpc-title">Expense Calc Pro</h1>
    <?php 
			if( ! checkLogin() )  { 
            	include( 'templates/forms/form-login.php' );
			} else { 
				$user = new User( $_SESSION['user_id'], $_SESSION['login_string'] );?>
    <h2 id="xpc-welcome">Welcome Back <?php echo $user->getFirstName();?></h2>
    <?php include( 'templates/dashboard.php' ); ?>
    <?php 	} ?>
  </div>
  <div class="dialog"></div>
</div>
</body>

<style type="text/css">
.main {
	padding: 0.3em;
}
.ui-widget-content {
	/* Permalink - use to edit and share this gradient: http://colorzilla.com/gradient-editor/#f2f5f6+0,e3eaed+37,c8d7dc+100;Grey+3D+%234 */
	background: #f2f5f6; /* Old browsers */
	background: -moz-linear-gradient(top, #f2f5f6 0%, #e3eaed 37%, #c8d7dc 100%); /* FF3.6+ */
	background: -webkit-gradient(linear, left top, left bottom, color-stop(0%, #f2f5f6), color-stop(37%, #e3eaed), color-stop(100%, #c8d7dc)); /* Chrome,Safari4+ */
	background: -webkit-linear-gradient(top, #f2f5f6 0%, #e3eaed 37%, #c8d7dc 100%); /* Chrome10+,Safari5.1+ */
	background: -o-linear-gradient(top, #f2f5f6 0%, #e3eaed 37%, #c8d7dc 100%); /* Opera 11.10+ */
	background: -ms-linear-gradient(top, #f2f5f6 0%, #e3eaed 37%, #c8d7dc 100%); /* IE10+ */
	background: linear-gradient(to bottom, #f2f5f6 0%, #e3eaed 37%, #c8d7dc 100%); /* W3C */
filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#f2f5f6', endColorstr='#c8d7dc', GradientType=0 ); /* IE6-9 */
}
#xpc-title {
	display: inline-block;
	width: 50%;
}
#xpc-welcome {
	font-size: 1.5em;
	display: inline-block;
	width: 49%;
	text-align: right;
}
</style>
<script>
	(function($){ 
		var dialogHistory;
		$(document).ready(function(e) {
			$('.wrap').show();   
        });
		
		xcpMsg = function( title, sound, html, buttons ) {
			
			var sound = $('<audio/>').addClass('xcp-msg-box-audio').attr( 'src', sound ).attr('type', 'audio/wav');
		
			
			$('.dialog').on('dialogopen',function( event, ui ) {
				sound.play();
			}).append(sound).dialog(options);
		}
		
		showDialog = function( options ) {
			dialogHistory = $('.dialog').dialog('option');
			
			$('.dialog').empty().dialog(options).dialog('show');	
		}
	})(jQuery);
</script>
</html>