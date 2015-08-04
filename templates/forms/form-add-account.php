<?php 
global $user;

include_once( '../../php/functions.php' );
include_once( '../../php/ajax.php' );

sec_session_start();
$accountTypes = loadTypes( 'account_types' ); 
$frequency = loadTypes( 'frequency' );

ob_start(); ?>

<form>
  <p class="dialog-info" class="error">
  </p>
  <table width="100%">
    <tbody>
      <tr class="form-row">
        <td><label for="account-name">Account Name:</label></td>
        <td><input type="text" id="account-name" class="xcp-form-input" name="name" /></td>
        <td><label for="account-type">Account Type:</label></td>
        <td><select id="account-type" class="chosen" name="type" data-placeholder="Select Type">
            <option/>
            <?php
    	foreach( $accountTypes as $key => $type ) { ?>
            <option value="<?php echo $key; ?>"><?php echo $type; ?></option>
            <?php } ?>
          </select></td>
      </tr>
      <tr class="form-row">
        <td class="asset bill loan hide"><label for="opening-balance">Current Balance</label></td>
        <td class="asset bill loan hide"><input type="text" id="opening-bal" class="xcp-form-input" value="34470.84" name="balance" title="Enter total aamount financed if this is a new loan, or enter current loan balance." /></td>
        <td class="cc hide"><label for="credit-limit">Credit Limit</label></td>
        <td class="cc hide"><input type="text" id="credit_limit" class="xcp-form-input" name="credit_limit" /></td>
      </tr>
      <tr class="form-row">
        <td class="loan hide"><label for="fund-date">Date Funded</label></td>
        <td class="loan hide"><input type="text" id="fund-date" class="datepicker xcp-form-input" name="fund-date" value="11/28/2012" placeholder="mm/dd/yyyy" /></td>
        <td class="loan hide"><label for="start-date">First Payment Date</label></td>
        <td class="loan hide"><input type="text" id="start-date" class="datepicker xcp-form-input" name="start-date" value="01/11/2013" placeholder="mm/dd/yyyy" /></td>
      </tr>
      <tr class="form-row">
        <td class="loan cc hide"><label for="rate">Interest Rate (APR):</label></td>
        <td class="loan cc hide"><input type="text" id="rate" maxlength="7" class="xcp-form-input" value="3.90" name="interest" placeholder="0.00" /> %</td>
        <td class="loan cc hide"><label for="rate-type">Interest Type:</label></td>
        <td class="loan cc hide"><select id="rate-type" class="chosen" name="interest_type" data-placeholder="Interest Type">
            <option/>
            <option selected value="0">Simple</option>
            <option value="1">Compound</option>
          </select></td>
      </tr>
      <tr class="form-row">
      	<td class="loan hide"><label for="end-date">Termination Date:</label></td>
        <td class="loan hide"><input type="text" id="end-date" value="12/12/2018" name="end-date" class="datepicker xcp-form-input" placeholder="mm/dd/yyyy" /></td>
        <td class="bill loan cc hide"><label for="repeating">Payment Frequency</label></td>
        <td class="bill loan cc hide"><select id="repeating" class="chosen" name="repeating" data-placeholder="Select Frequency">
            <option/>
            <?php foreach( $frequency as $key => $value ) { ?>
            <option <?php echo $key == 4 ? 'selected' : ''; ?> value="<?php echo $key;?>"><?php echo ucwords($value);?></option>
            <?php } ?>
          </select></td>
      </tr>
      <tr class="form-row">
      	<td class="bill loan cc hide"><label for="payment">Payment Amount</label></td>
        <td class="bill loan cc hide"><input type="text" id="payment" value="538.60" class="xcp-form-input" name="payment" placeholder="Payment" /></td>
        <td class="bill loan cc hide"><label for="due_date">Due Date</label></td>
        <td class="bill loan cc hide"><input type="text" id="due-date" value="08/12/2015" class="xcp-form-input datepicker" name="due_date" placeholder="Next Due Date" /></td>
      </tr>
    </tbody>
  </table>
</form>
<style type="text/css">
	#rate, #term {
		width: 41.5% !important;
	}
	
	.hide {
		display:none;	
	}
</style>
<script>
	(function($) {
		var update = false;
		$(document).ready(function(e) {
			var height;
			var inputs;
			
			var options = {
				title: "Add Account",
				modal: true,
				width: 1000,
				buttons: {
					'Add Account': function() {
						addAccount();	
					},
					'Close': function() {
						$(this).dialog('close');	
						
						if( update ) {
							window.reload;
						}
					}
				}
			}
					
			$('.dialog').dialog( options );
			
			clearForm();	
						
            $('.chosen').chosen({
				width: '100%'	
			}).on( 'chosen:showing_dropdown', function() {
				height = $('.dialog').height();
				$('.dialog').dialog( 'option', 'minHeight', height + 400 );
			}).on( 'chosen:hiding_dropdown', function() {
				$('.dialog').dialog( 'option', 'minHeight', height ).on( 'dialogresizestop', function( event, ui ) {
					$(this).dialog( 'option', 'position',  { my: "center", at: "center", of: window } );
				}).trigger( 'dialogresizestop' );	
			});
			
			$('#opening-bal, #payment, #credit_limit').on('blur', function() {
				if( $(this).val() ) {
					var value = $(this).val();
					
					value = parseFloat(value).toFixed(2);
					$(this).val(value);	
				}
			});
			
			$('.datepicker').datepicker({
				width:275,
				defaultDate: 0
			});
			
			$('#account-type').on( 'change', function() {
				var data = {
					'action': 'calcPayment'
				};
			
				$('.hide').hide();
			
				switch( $(this).val() ) {
					case '1':
					case '2':
					case '5':
						$('.asset').show();
						break;
					case '3':	
						$('.asset').show();
						$('.cc').show();
						$('#rate-type').val('1');
						$('#repeating').val('3');		
						break;
					case '6':
						inputs = '.loan input, .loan select';
						loadAmort( inputs );
						$('.asset').show();
						$('.loan').show();
						
						$('#repeat').on( 'change', function() {
							if( $(this).val() == 'bi-weekly' || $(this).val() == 'weekly') {		
								$('#rate-type').val('0');
							} else {
								$('#rate-type').val('1');
							}
						});
						break;
					case '4':
						$('.bill').show();
						break;
				}
				
				$('.chosen').trigger('chosen:updated');	
			});
		});
		
		function loadAmort( inputs ) {
			$(inputs).on( 'change', function() {
				var count = 0;
				$(inputs).each(function() {
					if( ! $(this).val() && $(this).attr('class') != undefined ) {
						return false;	
					}
					
					++count;
											
					if( count == $(inputs).length ) {
						var principal = $('#opening-bal').val();
						var payment = $('#payment').val();
						var init_payment = moment( $('#start-date').datepicker( 'getDate' ) ); 
						var due_date = moment( $('#due-date').datepicker( 'getDate' ) );
						var start_date = moment( $('#fund-date').datepicker( 'getDate' ) );
						var end_date = moment( $('#end-date').datepicker( 'getDate' ) );
						var term = end_date.diff( start_date, 'months' );
						var rate = $('#rate').val() / 100;
						var repeat = $('#repeating').val();
						var daily_int = ( rate  * principal ) / 365;
						var int_days;
						var data = {
							action: 'amort',
							info: {
								dailyInt: daily_int,
								rate: parseFloat( rate * 100 ).toFixed(2) + '%',
								term: term,
								principal: principal,
								originDate: start_date.toDate(),
								termDate: end_date.toDate(),
								payment: payment
							},
							data: []
						}
												
						/*if( ! init_payment.isBefore( due_date ) ) {
							int_days = init_payment.diff( start_date, 'days' );
						} else {
							int_days = due_date.diff( start_date, 'days' );	
						}*/

						for( var i = 0; i < term; i++ ) {
							int_days = init_payment.diff( start_date, 'd');
							
							int_paid =  daily_int * int_days;
							
							principal = principal - payment; 
							
							var temp = {
								'payment-number': i + 1,
								'payment-date': init_payment.toDate(),
								'payment-amount': parseFloat( payment ).toFixed(2),
								'other-charges': 0.00,
								'interest-part': parseFloat( int_paid ).toFixed(2),
								'principal-part': parseFloat( payment - int_paid ).toFixed(2),
								'principal-bal': parseFloat( principal ).toFixed(2) 
							}
							
							data.data.push( temp );
							
							start_date.add( 1, 'M' ).date( due_date.date() );
							init_payment.add( 1, 'M').date( due_date.date() );
						}
					}
				});
				//console.log( count + ' of ' + $(inputs).length );
			});
		}
		
		function clearForm() {
			$('#dialog-info').text('');
			$('.dialog form input, .dialog form select').each(function() {
				$(this).val('');
				
				if( $(this).hasClass( 'chosen' ) ) {
					$(this).trigger( 'chosen:updated' );	
				}
			});
		}
		
		function addAccount( dbmatch ) {
			if( dbmatch === undefined ) {
				dbmatch = false;	
			}
			var term;
			var types = {
				name: 's',
				balance: 'd',
				due_date: 's',
				payment: 'd',
				repeating: 'i',
				interest: 'd',
				type: 'i',
				interest_type: 'i',
				term: 'i',
				credit_limit: 'd'
			};
			var vars = [];
			var data = {
				action: 'addAccount',
				dbmatch: dbmatch
			};
			
			
			$('.dialog form input, .dialog form select' ).each( function() {
				if( $(this).val() ) {
					
					var name = $(this).attr('name');
					
					if( name == 'fund-date' || name == 'start-date' || name == 'end-date' ) {
						var start;
						var end;
						
						if( $('#fund-date').val() && $('#end-date').val() ) {
							start = moment( new Date( $('#fund-date').val() ) );
							end = moment( new Date( $('#end-date').val() ) );
							
						} else if( $('#start-date').val() == '' && $('#fund-date').val() && $('#end-date').val() ) {
							start = moment( new Date( $('#start-date').val() ) );
							end = moment( new Date( $('#end-date').val() ) );
						}
						
						
						if( start && end && term == undefined ) {
							name = 'term';
							term = end.diff( start, 'M' );
						}  else {
							return true;	
						}
					}
					
					var temp = {};					
					temp[name] = {
						type: types[name],
						value: name == 'term' && term != undefined ? term : $(this).val()	
					}
					vars.push(temp);
				}
			});
			
			var temp = {};
			 
			temp['user'] = {
				type: 'i',
				value: '<?php echo $_SESSION['user_id'];?>'
			}
			
			vars.push(temp);
			
			data['query_vars'] = vars;
			
			//console.log( data );
											
			$.post( './php/ajax.php', data, function( response ) {
				response = $.parseJSON( response );
				if( response.success ) {
					$('.dialog-info').text('Account added. Add another account or click close' );	
					clearForm();
					update = true;
				} else {
					if( typeof response.errors != 'undefined' ) {
						$('.dialog-info').text( response.errors );	
					} else if( typeof response.dbmatch != 'undefined') {
					}
				}
			});
		}
	})(jQuery);
</script><?php

$response = array(
	'success' => true,
	'html'	  => ob_get_clean(),
);

echo json_encode( $response );
die();
