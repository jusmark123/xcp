<?php 

/**
 * Class Name: User
 * Description: Class definition for Expense Calc Pro Users
 * Version: 1.0.0
 */
 
 class User {
	 var $id;
	 protected $firstName;
	 protected $lastName;
	 protected $email;
	 private $login_string;
	 private $pay_date;
	 
	 public function __construct( $uid, $loginStr ) {
		global $dbc;
		
		if( ! is_null( $uid ) || is_null( $loginStr ) ) {
			if( $stmt = $dbc->prepare( "SELECT first_name, last_name, email, pay_date FROM users WHERE id = ? LIMIT 1")) {
				$stmt->bind_param( 'i', $uid );
				$stmt->execute();
				$stmt->store_result();

				if( $stmt->num_rows == 1 ) {
					$stmt->bind_result( $fname, $lname, $email, $pay_date);
					$stmt->fetch();
					
					$this->id = $uid;
					$this->firstName = $fname;
					$this->lastName = $lname;
					$this->email = $email;
					$this->pay_date = $pay_date;
					$this->login_string = $loginStr;
					
					return $this;
				} else {
					return false;
				}
			}
		} else {
			return false;
		}
	 }
	 
	 public function getFirstName() {
		return $this->firstName;	 
	 }
	 
	 public function getFullName() {
		return $this->firstName . " " . $this->lastName;	 
	 }
	 
	 public function getPayDate() {
		return $this->pay_date;	 
	 }
	 
	 public function setPayDate( $payDate ) { 
	 	if( strtotime( $payDate ) <  time() ) {
			$payDate = strtotime( '2 weeks' );	
		}
		return date( 'm/d/Y' , $payDate );
	 }
 }