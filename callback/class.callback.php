<?php
/**
 * 
 * @package xt_vrepay 
 * @access public
 * 
 * @author Manfred Dennerlein Rodelo <manni@zapto.de>
 * @copyright Copyright (c) 2009, Manfred Dennerlein Rodelo
 * 
 * @version $Id$
 */

defined('_VALID_CALL') or die('Direct Access is not allowed.');


class callback_xt_vrepay extends callback {
	
	private $data = array();
	var $log_callback = true;
	var $version = '1.0';

	function process() {
		global $filter;

		if (!is_array($_POST)) return;


		foreach ($_POST as $key => $val) {
			$this->data[$key] = $filter->_filter($val);
		}

		$this->_callbackProcess();


	

	}

	
	private function _callbackProcess() {


		
		if ($this->log_callback_data == true) {
			$log_data = array();
			$log_data['module'] = 'xt_vrepay';
			$log_data['class'] = 'callback_data';
			$log_data['transaction_id'] = $this->data['REFERENZNR'];
			$log_data['callback_data'] = serialize($this->data);
			$this->_addLogEntry($log_data);
		}


		$err = $this->_getOrderID();
		if (!$err)
		return false;


		// validate md5signature
		$err = $this->_checkMD5Signature();
		if (!$err)
		return false;
		
		
		$this->_setStatus();
		

		
	}
	
	

	/**
	 * Calculate and check MD5 Signature of Callback
	 *
	 * @return boolean
	 */
	private function _checkMD5Signature() {

		if (XT_VREPAY_ANTWGEHEIMNIS == '')
		return true;
		
		$order = new order($this->orders_id,$this->customers_id);
		
		$amount = $order->order_total['total']['plain'];
		$currency = new currency($order->order_data['currency_code']);
		$betrag = $order->order_total['total']['plain'] * pow(10, $currency->decimals);

		$referenz = XT_VREPAY_ORDERPREFIX . $this->orders_id;
		$callback_secret = strtoupper(md5($betrag.$referenz.XT_VREPAY_ANTWGEHEIMNIS));
		
		if ($callback_secret != $this->data['ANTWGEHEIMNIS']) {
			$log_data['module'] = 'xt_vrepay';
			$log_data['class'] = 'error';
			$log_data['orders_id'] = $this->orders_id;
			$log_data['error_msg'] = 'md5 check failed';
			$log_data['error_data'] = serialize($this->data);
			$this->_addLogEntry($log_data);
			return false;
		}

		return true;

	}

	
	
	
	private function _getOrderID() {
		global $db;
		
		if(XT_VREPAY_ORDERPREFIX != '') {
			$order_id = substr_replace($this->data['REFERENZNR'],'',  0, strlen(XT_VREPAY_ORDERPREFIX));
		}

		$order_query = "SELECT orders_id, customers_id FROM ".TABLE_ORDERS." WHERE orders_id = '" . $this->data['REFERENZNR'] . "'";
		$rs = $db->Execute($order_query);

		if ($rs->RecordCount() == 1) {
			$this->orders_id = $rs->fields['orders_id'];
			$this->customers_id = $rs->fields['customers_id'];
			return true;
		}

		$log_data = array();
		$log_data['module'] = 'xt_vrepay';
		$log_data['class'] = 'error';
		$log_data['error_msg'] = 'order id not found';
		$log_data['error_data'] = serialize($this->data);
		$this->_addLogEntry($log_data);
		return false;

	}
	
	

	private function _setStatus() {


		switch ($this->data['STATUS']) {

			// processed
			case 'RESERVIERT':
			case 'GEKAUFT':
				$status = XT_VREPAY_PROCESSED;
				break;

				// denied
			case 'ABGELEHNT':			
				$status = XT_VREPAY_CANCELED;
				break;
				
				// pending
			case 'IN BEARBEITUNG':			
				$status = XT_VREPAY_PENDING;
				break;

				//Failed
			default:
				$status = XT_VREPAY_FAILED;
				break;

		}

		$log_data = array();
		$log_data['orders_id'] = $this->orders_id;
		$log_data['module'] = 'xt_vrepay';
		$log_data['class'] = 'success';
		$log_data['transaction_id'] = $this->data['REFERENZNR'];
		if($this->data['RMSG']) {
			$log_data['error_msg'] = $this->data['RMSG'];
		}
		$log_data['callback_data'] = array('message'=> $this->data['STATUS'],'transaction_id'=>$this->data['REFERENZNR']);
		$txn_log_id = $this->_addLogEntry($log_data);

		// update order status
		if($status) {
			$this->_updateOrderStatus($status,XT_VREPAY_STATUS_NOTIFY,$txn_log_id);
		}
		
		echo 'STATUS=SUCCESS';
	}
	
	
}

?>