<?php

include_once( '../../php/config.php' );
include_once( '../../php/ajax.php');
include_once( '../../php/class-account.php' );
	
sec_session_start();

if( isset( $_SESSION['xcp_id'], $_SESSION['cipher'] ) && $_SESSION['xcp_id'] == hash( 'sha512', $_SESSION['cipher'], $_SERVER['HTTP_USER_AGENT'] ) ) :

$account = new Account( $_SESSION['account_id'] );

$title = $account->getName() . ' Amoritzation Schedule';

include_once( '../../php/xcp-head.php' ); ?>

<body>
<h2><?php echo ucwords( $account->getName() . ' ' . getAccountType( $account->getType() ) ); ?></h2>
<table id="amort" width="100%">
  <thead>
  <th>Payment</th>
    <th>Payment Date</th>
    <th>Payment Amount</th>
    <th>Interest Portion</th>
    <th>Other Charges</th>
    <th>Prinicpal Portion</th>
    <th>Prinicpal Balance</th>
      </thead>
  <tbody>
    <?php
foreach( $data as $key => $payment ) { ?>
    <tr id="payment-<?php echo $key + 1; ?>" class="align-center">
      <?php
    foreach( $payment as $key => $value ) {
		if( is_int( $value ) || is_string( $value ) ) {
			$class .= 'align-center';
		} else {
			$class .= 'align-right';
			$value = number_format( $value, 2 );
		}
		
		if( $key != 'payment' ) { ?>
      <td class="cell-<?php echo $key . ' ' . $class;?>"><?php echo $value; ?></td>
      <?php
		} else { ?>
      <td class="cell-<?php echo $key . ' ' . $class;?>"><span>$ </span>
        <input type="text" class="payment-textbox align-right" value="<?php echo $value;?>" /></td>
      <?php
		}
	}?>
    </tr>
    <?php
} ?>
  </tbody>
  <tfoot>
    <tr class="align-right">
      <td class="align-center !important;">--</td>
      <td id="pmt-total"/>
      <td id="int-total"/>
      <td id="misc-total"/>
      <td id="prp-total"/>
      <td id="prb-total"/>
    </tr>
  </tfoot>
</table>
<style type="text/css">
</style>
<script>
</script>
</body>
<?php
endif;

