<?php global $user, $dbc; 
	
	include( MAIN_DIR . '/php/ajax.php' );
	
	$accountTypes = loadTypes( 'account_types' ); 
	$assets = loadAccounts( 'asset' );
	$payments = loadAccounts( 'payment', date( 'Y-m-d' ) );
	$assetSummary = getSummary( 'assets' ); 
	$paymentsSummary = getSummary( 'payments' );
	
	function loadSummary( $summaryArray, $type = 'assets' ) {
		global $accountTypes;
		$total = 0;
				
		//var_dump( $summaryArray );
		
		foreach( $summaryArray['summary'] as $key => $summary ) { 
		 	if( isset( $summary['min_payment'] ) ) {
				$total_due += $summary['min_payment'];
			}
			$total += $summary['total'];
			$type = $accountType[$summary['type']];?>

<tr class="summary-row summary-<?php echo $type; ?>">
  <?php
			foreach( $summary as $key => $value ) {
				$class = "summary-$key";
				$html = '';
				if( is_numeric( $value ) ) {
					if( is_float( $value ) ) {
						$class .= ' align-right';
						$value = number_format( $value, 2 );
						$html = '<span class="symbol">$ </span>';
					} else if( is_int( $value ) && $key == 'type' ) {
						$value = $accountTypes[$value];
					} else if( is_null( $value ) || ( is_int( $value ) && $key == 'count' ) ) {
						$class = ' align-center';	
					}
					$html .= '<span class="value">' . $value . '</span>';
				} else {
					$value = ucwords( $value );
					$html = '<span class="value">' . $value . '</span>';
				} ?>
  <td class="<?php echo $class; ?>"><?php echo $html; ?></td>
  <?php
			}?>
</tr>
<?php
		} ?>
<tr class="summary-row summary-total" style="border-top:1px solid #444;">
  <td class="total-text">TOTAL</td>
  <?php
  if( isset( $total_due ) ) {?>
  <td class="payment-total align-right"><span class="symbol">$ </span><span class="amount"><?php echo number_format( (float)$total_due, 2 ); ?></span></td>
  <?php } ?>
  <td class="align-right<?php echo $total < 0 ? ' negative' : ''; ?>" colspan="<?php echo count( $summary[0] ); ?>"><span class="symbol">$ </span><span class="value"><?php echo number_format( (float)$total, 2); ?></span></td>
</tr>
<?php
	}
?>
<div class="action-buttons">
  <table width="100%" style="margin:0 auto;">
    <tbody>
      <tr>
        <td><li id="add-account" class="action-button">Add Account</li>
          <li id="view-account" class="action-button">View Account</li>
          <li id="edit-account" class="action-button">Edit Account</li>
          <li id="delete-account" class="action-button">Delete Account</li></td>
        <td class="align-right"><li id="add-transaction" class="action-button">Add Transaction</li></td>
      </tr>
    </tbody>
  </table>
</div>
<div class="tab-overview">
<section id="assets" class="tab-section">
  <h3>Assets</h3>
  <div id="assets-detail" class="tab-section-detail">
    <div class="section-display">
      <div class="filters">
        <p>
          <label for="filter-select">Account Filter:</label>
          <select id="filter-select" class="chosen-select" width="250px">
            <option value="default">All Accounts Types</option>
            <?php
        foreach( $assetSummary['summary'] as $key => $value ) { ?>
            <option value="<?php echo $value['type']; ?>"><?php echo $accountTypes[$value['type']]; ?></option>
            <?php
		} ?>
          </select>
        </p>
      </div>
      <div class="summary">
        <table id="asset-table" class="display compact">
          <thead>
            <tr>
              <th>Account</th>
              <th>Type</th>
              <th>Balance</th>
            </tr>
          </thead>
          <tbody>
            <?php
        	if( ! empty( $assets ) ) {
			foreach( $assets['accounts'] as $key => $account ) { ?>
            <tr class="account-tile asset" id="<?php echo $account['ID']; ?>">
              <td class="account-title tile-cell"><?php echo $account['name']; ?></td>
              <td class="account-type tile-cell"><?php echo ucwords( $accountTypes[$account['type']] ); ?></td>
              <td class="account-bal tile-cell align-right">$ <?php echo number_format( $account['balance'], 2 ); ?></td>
            </tr>
            <?php }
            }?>
          </tbody>
        </table>
      </div>
      <div class="summary" id="asset-summary">
        <h3 class="account-title">Summary of Assets</h3>
        <table>
          <thead>
            <tr>
              <th>Account</th>
              <th class="align-right">Balance</th>
            </tr>
          </thead>
          <tbody>
            <?php loadSummary( $assetSummary); ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</section>
<section id="expenses" class="tab-section">
  <h3>Expenses</h3>
  <div id="expenses-detail" class="tab-section-detail">
    <div class="section-display">
      <div class="filters">
        <p>
          <label for="payment-accounts-select">Account Filters:</label>
          <select id="payment-accounts-select" class="filter-select chosen-select" width="250px">
            <option value="default">All Accounts Types</option>
            <?php
        foreach( $paymentsSummary['summary'] as $key => $value ) { ?>
            <option value="<?php echo $value['type']; ?>"><?php echo $accountTypes[$value['type']]; ?></option>
            <?php
		} ?>
          </select>
          <select id="expense-time-select" class="filter-select chosen-select" width="250px">
            <option value="default">This Week</option>
            <option value="1">Paycheck - <?php echo date( 'm/d', strtotime( $user->getPayDate() ) ); ?></option>
            <option value="2">Paycheck - <?php echo date( 'm/d', strtotime( '+2 weeks', strtotime( $user->getPayDate() ) ) ) ; ?></option>
            <option value="3">This Month</option>
            <option value="4">Next Month</option>
          </select>
        </p>
      </div>
      <div class="summary">
        <table id="expense-table" class="display compact">
          <thead>
            <tr>
              <th>Account</th>
              <th>Balance</th>
              <th>Due Date</th>
              <th>Amount Due</th>
            </tr>
          </thead>
          <tbody>
            <?php
			$payments = $payments['accounts'];
			if( ! empty( $payments ) ) {
				foreach( $payments as $key => $account ) { 
				$id = $account['ID'];
				$type = $accountTypes[$account['type']];
				$balance = number_format( (float)$account['balance'], 2 );
				$payment = is_null( $account['payment'] ) ? $account['balance'] : $account['payment'];
				$date = date( 'm/d/Y', strtotime($account['due_date'] ) );
				?>
            <tr class="account-tile expense" id="<?php echo $id; ?>">
              <td class="account-title tile-cell"><?php echo $account['name'];?></td>
              <td class="balance tile-cell align-right"><span class="symbol">$</span> <?php echo $balance; ?></td>
              <td class="due-date tile-cell align-right"><?php echo $date; ?></td>
              <td class="amount-due tile-cell align-right"><span class="symbol">$</span> <?php echo number_format( (float)$payment, 2 ); ?></td>
            </tr>
            <?php } 
			}?>
          </tbody>
        </table>
      </div>
      <div id="expense-detail" class="summary">
        <h3 class="account-title">Summary of Expenses</h3>
        <table>
          <thead>
            <tr>
              <th>Account Type</th>
              <th class="align-right">Minimum Payments</th>
              <th class="align-right">Balance</th>
            </tr>
          </thead>
          <tbody>
            <?php loadSummary( $paymentsSummary, 'expenses' );?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  </div>
</section>
<style type="text/css">
  .div.container {
	width:80%;  
  }
  
  #dashboard section {
	padding: .8em 0; 
	border: 1px solid #999;
  }
  
  .tab-overview {
	  text-align:center;
  }
  
  .tab-section {
	display: inline-block;
	width:49%;
	vertical-align:top;
	box-shadow: 2px 2px 3px #666;
	-moz-border-radius:2px 2px 3px #666;
	-webkit-border-radius: 2px 2px #666;
	text-align:left;
  }
  
  .section-display {
    margin-top: 1em;
  }
  
  .summary {
	  width: 95%;
	  padding: 0.5em;
	  font-size: 0.7em;
	  vertical-align:top;
	  margin:auto;
  }
  
  .summary table th {
	  font-weight: bold;
  }
  
  .summary h3 {
	padding-bottom: 0.5em;   
	border-bottom: 1px solid #555;
  }

  #assets {
	/* Permalink - use to edit and share this gradient: http://colorzilla.com/gradient-editor/#e4efc0+0,abbd73+100;Olive+3D+%232 */
background: #e4efc0; /* Old browsers */
background: -moz-radial-gradient(center, ellipse cover, #e4efc0 0%, #abbd73 100%); /* FF3.6+ */
background: -webkit-gradient(radial, center center, 0px, center center, 100%, color-stop(0%,#e4efc0), color-stop(100%,#abbd73)); /* Chrome,Safari4+ */
background: -webkit-radial-gradient(center, ellipse cover, #e4efc0 0%,#abbd73 100%); /* Chrome10+,Safari5.1+ */
background: -o-radial-gradient(center, ellipse cover, #e4efc0 0%,#abbd73 100%); /* Opera 12+ */
background: -ms-radial-gradient(center, ellipse cover, #e4efc0 0%,#abbd73 100%); /* IE10+ */
background: radial-gradient(ellipse at center, #e4efc0 0%,#abbd73 100%); /* W3C */
filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#e4efc0', endColorstr='#abbd73',GradientType=1 ); /* IE6-9 fallback on horizontal gradient */
  }
  
  #expenses {
	  /* Permalink - use to edit and share this gradient: http://colorzilla.com/gradient-editor/#f1e767+0,feb645+100;Yellow+3D */
background: #f1e767; /* Old browsers */
background: -moz-radial-gradient(center, ellipse cover, #f1e767 0%, #feb645 100%); /* FF3.6+ */
background: -webkit-gradient(radial, center center, 0px, center center, 100%, color-stop(0%,#f1e767), color-stop(100%,#feb645)); /* Chrome,Safari4+ */
background: -webkit-radial-gradient(center, ellipse cover, #f1e767 0%,#feb645 100%); /* Chrome10+,Safari5.1+ */
background: -o-radial-gradient(center, ellipse cover, #f1e767 0%,#feb645 100%); /* Opera 12+ */
background: -ms-radial-gradient(center, ellipse cover, #f1e767 0%,#feb645 100%); /* IE10+ */
background: radial-gradient(ellipse at center, #f1e767 0%,#feb645 100%); /* W3C */
filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#f1e767', endColorstr='#feb645',GradientType=1 ); /* IE6-9 fallback on horizontal gradient */
  }
  
  #asset-summary {

  }
  
  #assets-table, #expense-table {
	 width: 100% !important;
  }
  
  .summary table {
	  width:100%;
  }
  
  .negative {
	color: red;  
	font-weight: bold;
  }
  .summary-row td {
    padding: 0.3em;
  }
  
  .value {
	  text-align:right;
  }
  
  .selected {
  }
  
  .account-tile {
	  width:100%;
	  margin: 0.2em;
	  border-radius:5px;
	  border: 1px solid #aaa;
  }
  
  .account-tile:hover {
	cursor:pointer;
  }
  
  .tile-cell {
	  padding: 0.2em 0.8em;	
  }
  
  .account-tile-btm {
	  font-size: 0.3em;
  }
  
  .account-title {
	font-weight: bold;  
  }
  
  .align-center {
	  text-align: center;
  }
  
  .align-right {
	  text-align: right;  
  }
  
  #bills-table table {
	padding: 0.8em 0 ;
  }
  .xcp-form-input {
	width: 80%;
  }
  .past-due {
	background: #9C090B !important;
	color: #FFF;  
  }
  
  .past-due:hover {
	background: #EF9596 !important;
	color:#FFF;  
  }
  
  .past-due.selected, .past-due.selected > .sorting_1 {
	background-color: #B0BED9 !important;
	color: #000;
  }
  .past-due > .sorting_1 {
	background: #F42225 !important;  
  }
</style>
<script>
	(function($) {
		$(document).ready(function(e) { 

            $('.tabs').tabs();
			$('.filters').buttonset();
		
			$('.chosen-select').chosen({
				disable_search: true,
				width: '30%'
			});
			
			$('#asset-table').dataTable();
			
			$('#expense-table').dataTable({
				"order": [[ 2, "asc" ]]
			});
			
			$('td.due-date').each( function() {
				var date = new Date( $(this).text() );

				if( moment( date ).isBefore( moment(), 's' ) ) { 
					$(this).parent('tr').addClass('past-due');
				}
			});
			
			$('.filter').on( 'change', loadAccounts );
			
			$('.action-button').button().not('#add-account').button( 'option', 'disabled', true );
			
			$('#add-account' ).on( 'click', function() {
				buttonAction( 'add-account' );
			})
			
			$('.account-tile').on( 'click', function() { 				
				$('.selected').removeClass('selected');
				$(this).addClass('selected');
				
				$('.action-button').button( 'option', 'disabled', false ).on('click', function() {
					buttonAction( $(this).attr('id') );
				});
			});
		});
		
		buttonAction = function( id ) {
			var title;
			var text;
			var sound;
			var buttons = {};
			var options = {};
			var data = {};
			var type = $('.selected').hasClass('.expense') ? 'expense' : 'asset';
			
			$account = $('.selected');
			
			switch( id ) {
				case 'add-account':
					data = {
						action: 'getDialog',
						file: 'forms/form-add-account.php',
					}
					break;
				case 'add-transaction':
					data = {
						action: 'getDialog',
						file: 'forms/form-transaction.php', 
						id: $account.attr('id'),
						type: type	
					}
					break;
				case 'view-account':
					data = {
						action: 'getDialog',
						file: 'forms/form-view-account.php', 
						id: $account.attr('id')		
					}
					break;
				case 'edit-account':
					data = {
						action: 'getDialog',
						file: 'forms/form-edit-account.php', 
						id: $account.attr('id')			
					}
					break;
				case 'delete-account':						
					break;
			}		 
			actionCall( data );
		}
			
		function actionCall( data, title, button ) {
			$('.dialog').empty();
			
			$.post( $(location).attr('href') + 'templates/' + data.file, data, function( response ) {
				response = $.parseJSON( response );
				
				if( response.success ) {
					$('.dialog').html(response.html);
				} else {
					
				}
			});
		}		
		
		loadAccounts = function() {
			var data = {
				action:'loadAccounts',
				filter: $('.filter:checked').val()
			}
						
			$.post( './php/ajax.php', data, function( response ) {
				response = $.parseJSON( response );
				
				if( response.success ) {
					loadAccountsTbl( response.accounts );
				}
			});
		}
		
		function loadAccountsTbl( accounts ) {
			var due_date;
			
			$.each(accounts, function(i, v) {
				var $tile = $('<table/>').css( 'width', '100%' ).addClass('account-tile').append('<tbody/>').attr('id', i);
				var $trTop = $('<tr/>').addClass('account-tile-top');
				var $trBtm = $('<tr/>').addClass('account-tile-btm');
				var count = 0;

				$.each(v, function(k, d) {
					var $td = $('<td/>');
					$td.addClass( k + ' tile-cell').text(d);
					console.log(k);
					if( k == 'due-date' ) {
						due_date = moment(d);	
					}
					
					if( count % 2 != 0 )
						$td.addClass('align-right');
					
					if( count < 2 ) {
						$trTop.append($td);
					} else {
						$trBtm.append($td);
					}
							
					++count;	
				});
				
				if( due_date.isBefore(moment() ) ) {
					$tile.css('background-color', 'red'),css('color', '#fff');s	
				}

				$tile.find('tbody').append($trTop).append($trBtm);
	
				$('#bills-table table tbody').empty().append($tile);
			});	
		}
	})(jQuery);
</script>