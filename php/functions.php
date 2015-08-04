<?php
include_once( 'class-dbconnect.php' );
include_once( 'config.php' );

sec_session_start();

$accountTypes = loadTypes( 'account_types' ); 

function checkbrute( $user_id ) {
	global $dbc;
	
	$now = time();
	
	$valid_attempts = $now - (2 * 60 *60 );
	
	if( $stmt = $dbc->prepare( "SELECT time FROM login_attempts WHERE user_id = ? AND time > '$valid_attempts'")) {
		$stmt->bind_param( 'i', $user_id );
		
		$stmt->execute();
		$stmt->store_result();
		
		if( $stmt->num_rows > 5 ) {
			return true;
		} else {
			return false;
		}
	}
}

function checkLogin() {
  global $dbc;
    
  if( isset( $_SESSION['user_id'], $_SESSION['login_string'] ) ) {
	  
	  $user_id = $_SESSION['user_id'];
	  $login_string = $_SESSION['login_string'];
	  $user_browser = $_SERVER['HTTP_USER_AGENT'];
	 
	  
	  if( $stmt = $dbc->prepare( "SELECT password FROM users WHERE id = ? LIMIT 1")) {
		  $stmt->bind_param( 'i', $user_id );
		  $stmt->execute();
		  $stmt->store_result();
		  
		  if( $stmt->num_rows == 1 ) {
			 $stmt->bind_result( $password );
			 $stmt->fetch();
		 	 $login_check = hash( 'sha512', $password, $user_browser );
  
			 if( $login_check == $login_string ) {
				return true;
			 } else {
				return false;
			 }
		  } else {
			 return false;
		  }
	  } else {
		return false;
	  }
  } else {
	 return false;
  }
}

function getAccountType( $type, $group = false ) {
	global $accountTypes;
	
	if( $group ) {
		switch( $type ) {
			//Asset accountSs - cash
			case 2:
				$accType =  'asset';
				break;
			//revolving accounts - credit cards
			case 3:	
			case 10:
				$accType = 'revolving';
				break;
			//installment accounts - loans
			case 6:
				$accType = 'installment';
				break;
			//expense accounts - Bills/Utitlies/Other
			case 4:
			case 8:
			case 9:
				$accType = 'expense';
				break;
			//investment accounts - 401K/CD/Mutual Funds
			case 5:
			case 1:
				$accType = 'investment';
				break;
		}
		
		return $accType;
	}
	
	return $accountTypes[$type];
}

function esc_url( $url ) {
	if( '' == $url ) {
		return $url;
	}
	
    $url = preg_replace('|[^a-z0-9-~+_.?#=!&;,/:%@$\|*\'()\\x80-\\xff]|i', '', $url);
	
	$strip = array('%0d', '%0a', '%0D', '%0A');
    $url = (string) $url;
	
	$count = 1;
	while ($count) {
        $url = str_replace($strip, '', $url, $count);
    }
	
    $url = str_replace(';//', '://', $url);
 
    $url = htmlentities($url);
 
    $url = str_replace('&amp;', '&#038;', $url);
    $url = str_replace("'", '&#039;', $url);
 
    if ($url[0] !== '/') {
        // We're only interested in relative links from $_SERVER['PHP_SELF']
        return '';
    } else {
        return $url;
    }
}

function getQueryParams( $srcArray ) {
	$params = array();
	
	$params['types']= '';
	
	$srcArray = array_filter( $srcArray, FILTER_SANITIZE_STRING );
	
	foreach( $srcArray as $key => $value ) {
		if( $key == 'due_date' ) {
			$params['types'] .= 'd';
		} else if( is_numeric( $value ) && is_int( $value ) ) {
			$params['types'] .= 'i';
		} else if( is_numeric( $value ) && is_float( $value ) ) {
			$params['types'] .= 'f';
		} else {
			$params['types'] .= 's'; 
		}
		
		array_push( $params['vars'], $value ); 
	}
	
	$params = array_merge( $params['types'], $params['vars'] );
	
	$tmp = array();
	
	foreach( $params as $key => $value ) {
		$tmp[$key] = &$params[$key];
	}
	
	return $tmp;
}

function processQuery( $queryData, $autoCommit = true ) {
	global $dbc;
	
	$errors = array();
	
	foreach( $queryData as $key => $data ) {
		if( $stmt = $dbc->prepare( $data['query'] ) ) {
			call_user_func_array( array( $stmt, 'bind_param' ), $data['params'] );
		} else {
			$error['errors'][$key] = $stmt->error;	
		}
	}
	
	$dbc->autocommit($autoCommit);
	
	if( ! $stmt->execute() ) {
		$errors['errors']['execute'] = $stmt->error;
	} 
	
	if( ! empty( $errors ) ) {
	
		if( ! $autoCommit ) 
			$dbc->rollback();
				
		return $errors;
	}
	
	if( ! $autoCommit )
		$dbc->commit();
	
	return true;	
}

function userLogin( $email, $password ) {
	global $dbc;
		
	if( $stmt = $dbc->prepare( "SELECT id, password, salt FROM users WHERE email = ? LIMIT 1" ) ) {
			$stmt->bind_param( 's', $email );
			$stmt->execute();
			$stmt->store_result();
			
			$stmt->bind_result( $user_id, $pass, $salt );
			$stmt->fetch();
			
			$password = hash( 'sha512', $password . $salt );
			
			if( $stmt->num_rows == 1 ) {
				
				if( checkbrute( $user_id ) == true ) {
					$errors[] = 'Account Locked. Too many unsuccessful login attempts.'; 
				} else {
					
					if( $pass == $password ) {
						
						$user_browser = $_SERVER['HTTP_USER_AGENT'];
						$user_id = preg_replace( "/[^0-9]+/", "", $user_id );
						$_SESSION['user_id'] = $user_id;
						$_SESSION['login_string'] = hash( 'sha512', $password, $user_browser );
						
					} else {
						$now = time();
						$dbc->query(" INSERT INTO login_attempts(user_id, time) VALUES ('$user_id', '$now' )");
						
						$errors[] = 'Email/Password combination does not match our records. Please try again!';
					}
				}
			} else {
				$errors[] = 'No Users Found';
			}
		} else {
			$errors[] = 'No Users Found';
		}
	
	if( ! empty( $errors ) ) {
		$response['success'] = false;
		$response['errors'] = $errors;
	} else {
		$response['success'] = true;
	}
	return $response;
 }
	 
 function userSignup( $fname, $lname, $email, $pass ) {
	global $dbc;
	
	$response = array();
	$errors = array();
	$prep_stmt = "SELECT id FROM users WHERE email = ? LIMIT 1";
	$stmt = $dbc->prepare( $prep_stmt );
	
	if( $stmt ) {
		$stmt->bind_param( 's', $email );
		$stmt->execute();
		$stmt->store_result();
		
		if( $stmt->num_rows == 1 ) {
			$errors[] = 'Email address already exists'; 
		}
		
		$stmt->close();
	} else {
		$errors[] = 'Database Error Line 119';
		
		$stmt->close();
	}
 
	if( empty( $errors ) ) {
		$random_salt = hash( 'sha512', uniqid( mt_rand( 1, mt_getrandmax() ), true) );
		
		$password = hash( 'sha512', $pass . $random_salt );
	
		if( $insert_stmt = $dbc->prepare( "INSERT INTO users ( first_name, last_name, email, password, salt ) VALUES ( ?, ?, ?, ?, ? )" ) ) {
			$insert_stmt->bind_param( 'sssss', $fname, $lname, $email, $password, $random_salt );
			
			if( ! $insert_stmt->execute() ) {
				$errors[] = 'Registration failure: INSERT';
				$response['success'] = false;
				$response['errors'] = $errors;
				
				return $response;
			}
			
			$response['success'] = true;
		}
	} else {
		$response['success'] = false;
		$response['errors'] = $errors;
	}
	
	return $response;
 }
 
 function userLogout() {
	$_SESSION = array();
	
	$params = session_get_cookie_params();
	
	setcookie( session_name(),'', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly'] );
		
	session_destroy();
	
	return $response['success'] = true;
 }
 
 function getFormField( $key, $field ) { 
 	$html = '';
	$options = '<option/>';
	
	$label = '<label for="' . $key. '">' . $field['label'] . '</label>';
	 	
	switch( $field['type'] ) {
		case 'textarea':
			$html .= '<textarea ';
			
			foreach( $field as $key => $value ) {
				if( $key == 'label' || empty( $value ) ) 
					continue;
			
				$html .= $key . '="' . $value . '" ';
			}
			$html .= '></textarea>';	
			break;
		case 'text':
        	$html = '<input ';
			foreach( $field as $key => $value ) {
				if( $key == 'label' || empty( $value ) ) 
					continue;
			
				$html .= $key . '="' . $value . '" ';
			}
			$html .= ' />';
			break;
		case 'select': 
			$html = '<select ';
			foreach( $field as $key => $value ) {
				if( $key == 'label' || $key == 'type' || empty( $value ) ) 
					continue;
					
				if( $key == 'options' ) {
					foreach( $value as $k => $v ) { 
						$options .= '<option	 value="' . $k . '">' . $v . '</option>';
					}
				} else if( $key == 'placeholder' ) {
					$html .= 'data-placeholder="' . $value . '" ';
				} else {
					$html .= $key . '="' . $value . '" ';
				}
			}

			$html .= '>' . $options;
				
			$html .= '</select>';
	}
	
	return array(
		'label' => $label,
		'field' => $html
	);
 }
 
 function getAccount( $id ) {
	global $dbc, $user;	
	
	$response = array();
	
	$query = "SELECT * FROM accounts WHERE user = ? AND ID = ? LIMIT 1";
	
	if( $stmt = $dbc->prepare( $query ) ) {
		$stmt->bind_param( 'ii', $_SESSION['user_id'], $id );
		$stmt->execute();
		$result = $stmt->get_result();
				
		return $result->fetch_array(MYSQLI_ASSOC) ;
	} else {
		return false;
	}
 }
 
 function loadTypes( $type, $group = NULL ) {
	global $dbc;
	
	$types = array();
	
	$query = "SELECT * FROM $type";	
	
	if( ! is_null( $group ) )  {
		if( $group == 1 || $group == 2 ) {
			$query .= " WHERE 'group' = 1 OR 'group' = 2";
		} else {
			$query .= " WHERE 'group' = 3";
		}
	}
	
	if( $result = $dbc->query( $query ) ) {		
		while( $rows = $result->fetch_assoc() ) {	
			$types[$rows['ID']] = $rows['type'];	
		}
		
		return $types;
	} else {
		return $dbc->error;	
	}
}