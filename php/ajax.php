<?php 
include_once( 'class-dbconnect.php' );
include_once( 'functions.php' );

$errors = array();

if( isset( $_POST['action'] ) ) {
	if( function_exists( $_POST['action'] ) ) 
		call_user_func( $_POST['action'] );
}

function loginFunctions() {
	global $dbc;
	
	$response = array();
	
	if( isset( $_POST['email'], $_POST['password'] ) ) { 
		$email = filter_input( INPUT_POST, 'email', FILTER_SANITIZE_EMAIL );
		$email = filter_var( $email, FILTER_VALIDATE_EMAIL ) ;
		
		if( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
			$errors[] = 'Invalid Email Address';
		}
		
		$password = filter_input( INPUT_POST, 'password', FILTER_SANITIZE_STRING );
		
		if( strlen( $password ) != 128 ) {
			$errors[] = 'Invalid password configuration.	';	
		}
	}
	
	if( isset( $_POST['firstName'], $_POST['lastName'] ) ) {
		$firstName = filter_input( INPUT_POST, 'firstName', FILTER_SANITIZE_STRING );
		$lastName = filter_input( INPUT_POST, 'lastName', FILTER_SANITIZE_STRING );
	}
	
	if( empty( $errors ) ) {
	
		if( $_POST['call'] == 'login' ) {
			$response = userLogin( $email, $password );
		} else {
			$response = userSignup( $firstName, $lastName, $email, $password );
		}
	} else {
		$response['success'] = false;
		$response['errors'] = $errors;	
	}
	
	echo json_encode( $response );
}

function addAccount() {
	global $dbc, $user, $accountTypes;

	$query = '';; 
	$params = array();
	$values = array();
	$ret_type = 'i';
	
	if( isset( $_POST['query_vars'] ) ) {		
		$args = array( 
			'query_vars' => array( 	
				'filter' => FILTER_VALIDATE_STRING,
				'flags'  => FILTER_REQUIRE_ARRAY
			)
		);
		
		$vars = filter_input_array( INPUT_POST, $args );
		
		$name = $vars['query_vars'][0]['account-name'];
		$type = $vars['query_vars'][0]['account-type'];
		
		if( duplicateCheck( $name, $type ) ) {
			$response['success'] = false;
			$response['dbmatch'] = array(
				'name' => $name,
				'type' => $accountTypes[$type]
			);
			
			echo json_encode( $response );
			die();
		}
		
		$count = 1;
		$query = "INSERT INTO accounts(";
		$params = '';
		$ret_type = '';
		$values = array();
				
		foreach( $vars['query_vars'] as $var ) {
			foreach( $var as $key => $val ) {
				if( $count < count( $vars['query_vars'] ) ) {
					$query .=  $key . ", ";
					$params .= '? ,';
				} else {
					$query .= $key;
					$params .= '?';
				}
				
				$ret_type .= $val['type'];
				
				if( $key == 'due_date' ) {
					$val['value'] = date( 'Y-m-d', strtotime($val['value'] ) );	
				}
				
				array_push( $values, $val['value'] );
			
				++$count;
			}
		}
		
		$types[] = $ret_type;
		
		$query .= ") VALUES ($params);";
				
		if( $stmt = $dbc->prepare( $query ) ) { 
		
			$params = array_merge( $types, $values );
			
			$tmp = array();
			
			foreach( $params as $key => $value ) {
				$tmp[$key] = &$params[$key];
			}
						
			call_user_func_array( array( $stmt, 'bind_param' ), $tmp );
			
		 	if( ! $stmt->execute() ) {
			 	$errors[] = 'Add Account failure: ' . $stmt->error;
			  	$response['success'] = false;
			  	$response['errors'] = $errors;
		  	} else {
				$response['success'] = true;
			}
		} else {
			var_dump( $dbc );
			$errors[] = 'SQL Statment invalid: ' . $dbc->info;
			$response['success'] = false;
			$response['errors'] = $errors;	
		}
	} else {
		$errors[] = 'Add Account failure: Missing Parameters';
		$response['success'] = false;
		$response['errors'] = $errors;	
	}
	
	echo json_encode( $response );
	die();
}

function createTransaction() {
	include_once( 'class-transaction.php' );
	
	if( isset( $_POST['scrAccount'], $_POST['transAccount'], $_POST['transData'] ) ) {
		
		$sourceAccount = $_POST['scrAccount'];
		$transAccount = $_POST['transAccount'];
		
		foreach( $_POST['transData'] as $key => $value ) {
			$transdata[$key] = isset( $value ) ? $value : NULL; 
		}
		
		$trans = new Transaction( $sourceAccount, $transAccount, $transdata );
		
		echo json_encode( $trans->calculate() );
		die();
	}
}

function duplicateCheck( $name, $type )  {
	global $dbc;
	if( $stmt = $dbc->prepare( "SELECT ID FROM accounts WHERE name = ? AND $type = ?" ) ) {
		$stmt->bind_params( 'si', $name, $type );
		$stmt->execute();
		$stmt->store_result();
				
		if( $stmt->num_rows > 0  ) {
			return true;	
		}
		return false;
	}
}

function loadAccounts( $type = '', $filter = '' ) {
	global $dbc;
		
	$filters = array(
		'asset' => "( type != 4 AND type != 6 )",
		'payment' => "( type != 1 AND type != 2 AND type != 5 ) AND due_date <= ?"
	);
	
	if( isset( $_POST['filter'] ) ) {
		
		$filter = filter_input( INPUT_POST, 'fitler', FILTER_SANITIZE_STRING );
		$type = 'payment';
	}
		
		$accounts = array();
		
		$user_id = $_SESSION['user_id'];
		
		if( $stmt = $dbc->prepare( "SELECT * FROM accounts WHERE user = ? AND " . $filters[$type] . " ORDER BY due_date" ) ) {
			if( $type == 'asset' ) {
				$stmt->bind_param( 'i', $user_id );
			} else {
				$stmt->bind_param( 'is', $user_id, $filter );
			}
			$stmt->execute();
			$stmt = $stmt->get_result();
			
			while( $rows = $stmt->fetch_array( MYSQLI_ASSOC ) ) {
				
				$accounts[] = $rows;
			}
			
			$response['success'] = true;
			$response['accounts'] = $accounts;
		} else {
			$response['success'] = false; 
			$response['errors'] = "Load Accounts error: " . $stmt->error;
		}
		
		if( isset($_POST['action'] ) && empty( $type ) ){
			echo json_encode( $response );
			die();
		} else {
			//var_dump( $response['accounts'] );
			return $response;	
		}
	}

function getSummary( $type, $single = false ) {
	global $dbc, $user;
	$cash = 0;
	$investments= 0;
	$credit = 0;
	$total = 0;
	$date = date( 'Y-m-d', strtotime( 'this sunday' ) );
	$types = array( 
		'assets' => "SELECT type, SUM( credit_limit ) `limit`, SUM( balance ) total FROM accounts WHERE user = ? AND ( type = 1 OR type = 2 OR type = 3 OR type = 5  ) GROUP BY type",
		'payments' => "SELECT a.ID type, SUM( b.credit_limit ) `limit`, SUM( b.payment ) min_payment, SUM( b.balance ) total FROM ( SELECT ID FROM `account_types` WHERE `group` != 1 ) AS a LEFT JOIN ( SELECT type, balance, payment, credit_limit FROM accounts WHERE user = ? AND due_date <= '$date' ) AS b ON a.ID = b.type GROUP BY a.ID",
	);
		
	if( $stmt = $dbc->prepare( $types[$type] ) ) {
		$stmt->bind_param( 'i', $user->id );
		$stmt->execute();
		$rows = $stmt->get_result();
		
		$response['summary'] = array();
					
		while( $row = $rows->fetch_array( MYSQLI_ASSOC ) ) {
			if( !is_null( $row['limit'] ) && $type == 'assets' ) {
				$row['total'] = $row['limit'] - $row['total'];
			}
			unset( $row['limit'] );
			$response['summary'][] = $row;
		}
		
		$response['success'] = true;
	} else {
		$response['success'] = false; 
		$response['errors'] = "Load Accounts error: " . $dbc->error;
	}
	return $response;
}
	
function calcInterest() {
	$args = array( 
		'query_vars' => array( 
			'filter' => FILTER_SANITIZE_STRING,
			'flag'	 => FILTER_REQUIRE_ARRAY
		)
	);
	
	$vars = filter_input_array( INPUT_POST, $args );
	$vars = $vars['query_vars'];
	
	$rate = (float)$vars['rate'] / 100;
	$intType = $vars['int_type'];
	$int_acc = $vars['intAcc'];
	$principal = $vars['balance'];
	$term = $vars['term'];
	$repeat = $vars['repeat'];
	$remaining = $vars['due_date'];
	
	
	switch( $int_acc ) {
		case 'daily':
			$interest = $rate / 365;
		case 'monthly':
			$interest = $rate / 12;
	}
	
	if( $intType == 'simple' ) {
		$payment = ( $principal / $term ) * ( $interest * $remaining ); 
	} else {
			
	}
	
	echo $payment;
}