<?php

include_once( 'functions.php' );

class Account {
	private $id;
	private $name;
	private $balance;
	private $due_date;
	private $payment;
	private $billingFrequency;
	private $interest;
	private $type;
	private $interestType;
	private $term;
	private $creditLimit;
	
	public function __construct( $id = NULL ) {	
		if( ! is_null( $id ) ) {
			$account = getAccount( $id );
			
			$this->id = $account['ID'];
		   	$this->name = $account['name'];
		   	$this->balance = $account['balance'];
		   	$this->due_date = new DateTime( $account['due_date'] );
		   	$this->payment = $account['payment'];
		   	$this->billingFrequency = $account['repeating'];
		   	$this->interest = $account['interest'];
		   	$this->type = $account['type'];
		   	$this->interestType = $account['interest_type'];
		   	$this->term = $account['term'];
		   	$this->creditLimit = $account['credit_limit'];
		}
	}
	
	public function getName() {
		return ucwords( $this->name );	
	}
	
	public function getbalance() {
		return $this->balance;
	}
	
	public function getAvailableFunds() {
		if( $type == 3 || $type == 10 ) {
			return $this->credit_limit - $this->balance;
		} else {
			return $this->balance;
		}
	}
	
	public function getType() {
		return $this->type;	
	}
	
	public function amortize() { 
		global $frequency;
		
		$type = getAccountType( $this->type, true );
		
		$amortTable = array();
		
		if( $type == 'installment' ) {
			for( $d = 0; $d < $this->term; $d++ ) {
				$amortData = array(
					'payment-date' => $this->due_date->add( new DateInterval( "P$frequencyD" ) ),
					
				);		
			}
		} else if( $type == 'revolving' ) {
				
		}
	}
}