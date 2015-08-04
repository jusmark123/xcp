<?php

include( 'functions.php' );

class Transaction {
	private $id;
	private $transAccount;
	private $transRef;
	private $transType;
	private $transMemo;
	private $transAmount;
	private $transDate;
	private $srcAccount;
	private $transCache = array();
	
	public function __construct( $srcAccount, $transAccount, $transData ) {
		$_SESSION['transaction'] = array();
				
		$this->transRef = $transData['refNum'];
		$this->transAccount = getAccount( $srcAccount );
		$this->srcAccount = getAccount( $transSource );
		$this->transType = $transData['type'];
		$this->transMemo = $transData['memo'];
		$this->transAmount = $transData['amount'];
		$this->transDate = date( 'Y-m-d', strtotime( $transData['trans_date'] ) );
		$this->transCache = array(
			'transAccount' => $transData['account'],
			'transSource' => $transData['trans_source']
		);			
	}
	
	public function getSourceAccount() {
		return $this->srcAccount;	
	}
	
	public function getTranactionAccount() {
		return $this->transAccount;	
	}
	
	public function getTransactionType() {
		return $this->transType;	
	}
	
	public function calculate() {
		$transaction = array();
		$fundsAvail = false;
		
		//make sure source account has sufficient funds
		switch( getAccountType( $this->srcAccount['type'], true ) ) {
			
			//cash accounts
			case 'asset':
				if( $this->srcAccount['balance'] > $this->transAmount )
					$fundsAvail = true;
				break;
				
			//credit accounts
			case 'revolving':
				if( $this->srcAccount['credit_limit'] - $this->srcAccount['balance'] > $this->transAmount )
					$fundsAvail = true;
				break;
		}
		
		if( $fundsAvail ) {
			//debit transaction amount from source account
			$transaction['srcAccount']['balance'] = $this->srcAcount['balance'] - $this->transAmount;

			//get account type
			switch( getAccountType($this->transAccount['type'], true) ) {
				case 'expense':
				
					//debit transaction amount from transaction account
					$transaction['transAccount']['balance'] = $this->transAccount['balance'] - $this->transAmount;
					
					//update transaction account due date
					$transaction['transAccount']['due_date'] = date( 'Y-m-d', strtotime( $this->transAccount['due_date'], getFrequencyOffest( $this->transAccount['repeat'] ) ) );
					break;
				case 'asset':
				case 'installment':
				case 'credit':
				case 'revolving':
	
					//credit transaction amount to  transaction account 
					$transaction['transAccount']['balance'] = $this->transAccount['balance'] + $this->transAmount;
					break;
			}
		}
				
		foreach( $transaction as $array => $account ) {
			$errors = array();
			$query = "UPDATE accounts SET ";
			
			$count = 0;
			
			foreach( $account as $key => $value ) {
				 if( $count < count( $account ) ) {
					$query .= $key . ' = ?, '; 						
				 } else {
				 	$query .= $key . ' = ? ';
				 }
				 
				 ++$count;
			}
			
			$params = getQueryParams( $account );
			
			$query .= "WHERE ID = " . $account['ID'];
			
			$queryData[$array]['query'] = $query;
			$queryData[$array]['params'] = $params;
		}
			
		$transaction['transaction'] = array(
			'transaction_account' => $this->transAccount['ID'],
			'transaction_source'  => $this->srcAccount['ID'],
			'transaction_amount'  => $this->transAccount,
			'transaction_date'	  => $this->transDate,
			'transaction_memo'	  => $this->transMemo,
			'user'				  => $_SESSION['user_id'] 
		);
		
		$query = 'INSERT INTO transactions ( ';
		
		$count = 0;
		
		foreach( $transactions as $key => $value ) {
			if( $count < count( $transaction ) ) {
				$params .= $key . ', ';
				$vars	.= '?, ';
			} else {
				$params .= $key;	
				$vars   .= '?';
			}
		}
		
		$query .= $params . ' ) VALUES ( ' . $vars . ' )';
		
		$params = getQueryParams( $transaction );
		
		$queryData['transaction']['query'] = $query;
		$queryData['transaction']['params'] = $params;
			
		if( $result = processQuery($queryData, false ) ) {
			
			//An errors has occured
			if( isset( $result['errors'] ) ) {
				$transaction['errors'] = $result['errors'];
				
				return $transaction;
			}
			
			$_SESSION['transaction'] = $transaction;
		}
		
		return $transaction;
	}

	public function amortize() {
			
	}
	
	public function undoTransaction() {
		$this->transAccount = $this->transCache['transAccount'];
		$this->srcAccount = $this->transCache['trans_source'];
	}
}