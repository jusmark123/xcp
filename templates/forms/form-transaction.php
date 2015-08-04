<?php 
		
	include_once( '../../php/functions.php' );
	include_once( '../../php/ajax.php');
	
	sec_session_start();
	
	$random_salt = hash( 'sha512', uniqid( mt_rand( 1, mt_getrandmax() ), true) );
	
	$_SESSION['cipher'] = $random_salt;
	
	$_SESSION['xcp_id'] = hash( 'sha512', $random_salt, $_SERVER['HTTP_USER_AGENT'] );
				
	$account = getAccount( $_POST['id'] );
	
	$_SESSION['account_id'] = $account['ID'];
	
	$accountTypes = loadTypes( 'account_types' );
	
	$transTypes = loadTypes( 'trans_types', $account['type'] );
		
	$formFields = array(
		array(
			'transaction-ref' => array(
				'class' 		=> 'xcp-form-input',
				'type' 			=> 'text',
				'label' 	 	=> 'Confirmation/Ref #:',
				'placeholder' 	=> '',
				'title' 	=> '',
			),
			'transaction-date' => array(
				'class'			=> 'xcp-form-input datepicker',
				'type'			=> 'text',
				'label'			=> 'Transaction Date:',
				'placeholder'	=> 'mm/dd/yyyy',
				'desciption'		=> '',
			),
		),
		array(
			'transaction-source' => array(
				'class' 		=> 'chosen-select expense',
				'type'			=> 'select',
				'label'			=> 'Transaction Source:',
				'data-placeholder'	=> 'Select Account',
				'title'			=> '',
				'options'		=> loadSources(),
			),
			'transaction-amount' 	=> array(
				'class' 		=> 'xcp-form-input',
				'type'			=> 'text',
				'label'			=> 'Transaction Amount:',
				'placeholder'	=> '',
				'title'			=> '',
				'style'			=> $account['due_date'] < date() ? 'color: red; font-weight:bold' : '',
				'value'			=> number_format( $account['payment'], 2 ),
			),
		),
		array(
			'transaction-type'  => array(
				'class'			=> 'chosen-select expense',
				'type'			=> 'select',
				'label'			=> 'Transaction Type:',
				'data-placeholder' => 'Select Type',
				'title'			=> '',
				'options'		=> $transTypes
			),
			'transaction-memo'  => array(
				'class'			=> 'xcp-form-input xcp-textarea',
				'label'			=> 'Transaction Memo:',
				'title'			=> '',
				'type'			=> 'textarea',
			),
		)
	);
	
	ob_start(); ?>
<form id="add_transaction">
  <section id="" class="page-1 top account-detail">
  	<h2 class="account-name"><?php echo $account['name']; ?></h2>
    <table>
      <tbody>
      	<tr>
        	<td colspan="3"><h3>Account Status: <span id="account-status"></span></td>
        </tr>
        <?php
		$count = 1;
		$rowCount = 4;
		
		unset( $account['user'] );
	
		foreach( $account as $key => $value ) {
			if( is_null( $value ) || $key == 'ID' || $key == 'name' ) {
				continue;
			}
			
			if( $key == 'type' ) {
				$value =  $accountTypes[$value];	
			} else if( $key == 'due_date' ) {
				$value = date( 'm/d/Y', strtotime( $value ) );	
			}
			
			$label = ucwords( str_replace( '_', ' ', $key ) ); 
			$value = is_float( $value ) ? $key == 'interest' ? number_format( $value, 2 ) . '%' : '$' . number_format( $value, 2 ) : ucwords( $value );
			 
			if( $count == 1 ) {?>
             	<tr> <?php
			} ?>

          <td><span class="<?php echo $key; ?>-label label"><?php echo $label; ?>: </span><span class="<?php echo $key; ?>-value value"><?php echo $value; ?></span></td>
          <?php
			
			if( $count < $rowCount || ! next(  $account ) ) { 
				++$count; 
			} else {
				$count = 1;?>
        </tr>
        <?php 
			}
		}?>
      </tbody>
    </table><?php
	if( $account['type'] == 3 || $account['type'] == 6 || $account['type'] == 10  ) { ?>
    	<a href="<?php echo $_SERVER['HTTP_REFERER'] . "/templates/forms/form-amort-table.php"?>" target="blank" id="view-amort-table">View Amortization Schedule</a>
	<?php
    }?>
	
  </section>
  <section id="" class="page-1 middle transaction">
    <div id="expense-detail">
      <table width="100%">
        <tbody>
          <?php
			foreach( $formFields as $row ) { ?>
          <tr>
            <?php 
            	foreach( $row as $key => $value ) { 
					$field =  getFormField( $key, $value ); ?>
            <td><?php echo $field['label']; ?></td>
            <td><?php echo $field['field']; ?></td>
            <?php
                } ?>
          </tr>
          <?php 		
           	} ?>
        </tbody>
      </table>
    </div>
  </section>
  <section id="" class="page-1 bottom transactio">
  	<div class="">
    
    </div>
  </section>
</form>
<style type="text/css">
	.dialog section {
		text-aign: center;	
		margin: 1em auto;
		width: 100%;
	}
	.top table {
	   	width: 100%;
 	   	font-size: 0.8em;
 	   	text-align: left;
		background-color: #FBD983;
	}
	.top tr:last-child {
		border-bottom: 1px solid #888888;	
	}
	form td {
		padding: 0.8em 5px;	
	}
	.past-due-text {
		color:#E71013;
		font-weight: bold;	
	}
</style>
<script> 
	(function($) {
		$(document).ready(function(e) {
            var options = {
				autoOpen: true,
				modal: true,
				width: 850,
				title: 'New Transaction',
				draggable: false,
				buttons: {
					'Next': function() {
						
					},
					'Close': function() {
						
					}
				}
			}
			
			$('.dialog').dialog( options );
			
			$('.chosen-select').chosen({
				width:'80%'	
			}).on( 'chosen:showing_dropdown', function() {
				height = $('.dialog').height();
				$('.dialog').dialog( 'option', 'minHeight', height + 400 );
			}).on( 'chosen:hiding_dropdown', function() {
				$('.dialog').dialog( 'option', 'minHeight', height ).on( 'dialogresizestop', function( event, ui ) {
					$(this).dialog( 'option', 'position',  { my: "center", at: "center", of: window } );
				}).trigger( 'dialogresizestop' );	
			});
			
			$('.datepicker').datepicker().datepicker('setDate', new Date() );
			
			var due_date = moment( new Date( $('.due_date-value').html() ) );
			
			if( due_date.isBefore( moment(), 's' ) ) {
				$('.due_date-value').addClass('past-due-text'); 	
				$('#account-status').text('PAST DUE').addClass('past-due-text');
			} else {
				var weeks = due_date.diff( moment(), 'w' );
				if(  weeks >= 1 ) {
					$('#account-status').text('Due in ' + weeks + weeks > 0 ? 'weeks': 'week' + '.' );
				} else {
					var days = due_date.diff( moment(), 'd' ); 
					$('#account-status').text('Due in ' + days + days > 1 ? 'days' : 'day' + '.' );
				}
			}
        });
	})(jQuery);
</script>
<?
	$response = array(
		'success' => true,
		'html'	  => ob_get_clean(),
	);
	
	echo json_encode( $response );
	die();

	function getSourceAccounts( $account ) {
		$sources = loadSources( $account['type'] );
		$assetSel = array();
	
		foreach( $assets['accounts'] as $value ) {
			if( $value['type'] != 3 ) {
				$name = $value['name'];
				$balance = $value['balance'];
			} else if( $value == 3 ) {
				$name = $value['name'];
				$balance = $value['credit_limit'] - $value['balance'];
			} else {
				continue;
			}
		
			$assetSel[$value['ID']] = $name . ' - $' . (string)number_format( $balance, 2);	
		}
		return $assetSel;
	}
	
	function loadSources( $type ) {
		/*switch( getAccountType( $type, true ) ) {
			case 'asset':
				'
		}*/
	}