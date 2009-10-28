<?php

defined('_VALID_CALL') or die('Direct Access is not allowed.');

class xt_vrepay {

	
	public $external = false;
	public $version = '1.0';
	public $subpayments = false;
	public $post_form = false;
	public $iframe = false;
	public $data = array();
	
	public function xt_vrepay() {

		if(is_data($_SESSION['xt_vrepay_data'])){			
			$this->data['payment_info'] = $this->build_payment_info($_SESSION['xt_vrepay_data']);
			$tmp_data = $_SESSION['xt_vrepay_data'];

			while (list ($key, $value) = each($tmp_data)) {
				$this->data[$key] = $value;
			}
		} else {
			$this->data['vr_mto'] = sprintf('%02d', $this->getCurrentTime('mon'));
			$this->data['vr_yto'] = strftime('%Y', mktime(0, 0, 0, 1, 1, $this->getCurrentTime('year') ));
		}
		$this->data['vr_mto_list'] = $this->getMonthToList_data();
		$this->data['vr_yto_list'] = $this->getYearToList_data();

	}
	
	
	
function build_payment_info($data){

    $tmp_data = $data;

    // Keine Konstante im Checkout.
   /* unset($tmp_data['customer_id']);
    unset($tmp_data['banktransfer_save']);
    unset($tmp_data['banktransfer_country_code']);
    unset($tmp_data['banktransfer_amount']);
    unset($tmp_data['banktransfer_trxamount']);
    unset($tmp_data['banktransfer_currency']);

    // Keine Konstante Konto bearbeiten.
    unset($tmp_data['action']);
    unset($tmp_data['account_id']);
    unset($tmp_data['x']);
    unset($tmp_data['y']);
*/
    while (list ($key, $value) = each($tmp_data)) {
      $text = constant('TEXT_'.strtoupper($key));
      if($key == 'vr_ccno') $value =  substr($value, 0, 4).str_repeat('X', (strlen($value) - 8)) .substr($value, -4);
      $new_data .= $text.': '.$value.'<br />';
    }

    return $new_data;

  }
	
	
	
	private function getMonthToList_data() {
		$expires_month = array();
		for ($i = 1; $i <= 12; $i++) {
			$expires_month[] = array ('id' => sprintf('%02d', $i), 'text' => sprintf('%02d', $i));
		}
		return $expires_month;
	}
	
	private function getYearToList_data() {
		$today = getdate();
		$expires_year = array();
		for ($i = $today['year']; $i < $today['year'] + 10; $i ++) {
			$expires_year[] = array ('id' => strftime('%Y', mktime(0, 0, 0, 1, 1, $i)), 'text' => strftime('%Y', mktime(0, 0, 0, 1, 1, $i)));
		}
		
		return $expires_year;
	}
	
	private function getCurrentTime($type = '0') {
		$today = getdate();
		return $today[$type];
	}
	
	//TODO: Validierung Owner
	public function _vrepayValidation($data) {
		global $xtPlugin, $info;

		($plugin_code = $xtPlugin->PluginCode('class.xt_vrepay.php:_vrepayValidation_top')) ? eval($plugin_code) : false;
		if(isset($plugin_return_value))
		return $plugin_return_value;

		// check cardtype
		if ( ereg('^4([0-9]){12,18}$', $data['vr_ccno'])) {		
			$data['vr_ccbrand'] = 'VISA';
			echo XT_VREPAY_HAENDLERNR;
			if(XT_VREPAY_ACTIVATE_VISA != 'true') {
				$error['vr_ccbrand'] = 'true';
				$info->_addInfo(ERROR_CHECK_VREPAY_BRAND_VISA_UNSUPPORTED);
			}
		} elseif ( ereg('^5([0-9]){15}$', $data['vr_ccno'])) {
			$data['vr_ccbrand'] = 'ECMC';
			if(XT_VREPAY_ACTIVATE_ECMC != 'true') {
				$error['vr_ccbrand'] = 'true';
				$info->_addInfo(ERROR_CHECK_VREPAY_BRAND_ECMC_UNSUPPORTED);
			}
		} elseif ( ereg('^3(4|7)([0-9]){1,14}$', $data['vr_ccno'])) {
			$data['vr_ccbrand'] = 'AMEX';
			if(XT_ACTIVATE_AMEX != 'true') {
				$error['vr_ccbrand'] = 'true';
				$info->_addInfo(ERROR_CHECK_VREPAY_BRAND_AMEX_UNSUPPORTED);
			}
		} elseif ( ereg('^3(0|6|8)([0-5])([0-9]){1,13}$', $data['vr_ccno'])) {
			$data['vr_ccbrand'] = 'DINERS';
			if(XT_VREPAY_ACTIVATE_JCB != 'true') {
				$error['vr_ccbrand'] = 'true';
				$info->_addInfo(ERROR_CHECK_VREPAY_BRAND_DINERS_UNSUPPORTED);
			}
		} elseif ( ereg('^35(2|3|4|5|6|7|8)([0-9]){1,20}$', $data['vr_ccno'])) {
			$data['vr_ccbrand'] = 'JCB';
			if(XT_VREPAY_ACTIVATE_DINERS != 'true') {
				$error['vr_ccbrand'] = 'true';
				$info->_addInfo(ERROR_CHECK_VREPAY_BRAND_JCB_UNSUPPORTED);
			}
		} else {
			// redirect to checkout_payment with errormessage
			$error['vr_ccbrand'] = 'true';
			$info->_addInfo(ERROR_CHECK_VREPAY_BRAND_UNSUPPORTED);
		}			
			// check range of expiry month
		if ( !is_numeric($data['vr_mto']) || ($data['vr_mto'] < 1) || ($data['vr_mto'] > 12) ) {
			$error['vr_mto'] = 'true';
			//TODO: Sprachvariable anpassen
			$info->_addInfo(ERROR_CHECK_VREPAY_MTO);
		}
		
			// check range of expiry year		
		if ( !is_numeric($data['vr_yto']) || ($data['vr_yto'] < $this->getCurrentTime('year')) || ($data['vr_yto'] > ($this->getCurrentTime('year') +10)) ) {
			// redirect to checkout_payment with errormessage
			$error['vr_yto'] = 'true';
			$info->_addInfo(ERROR_CHECK_VREPAY_YTO);			
		}
		
		// check expiry month in current year
		if ( ($data['vr_yto'] == $this->getCurrentTime('year')) && ($data['vr_mto'] < $this->getCurrentTime('mon')) ) {

			$error['vr_mto'] = 'true';
			$error['vr_yto'] = 'true';
			$info->_addInfo(ERROR_CHECK_VREPAY_EXPIRED);			
		}

		
		
		$vrepayValidationReturnValue['data']  = $data;
		$vrepayValidationReturnValue['error'] = $error;

		($plugin_code = $xtPlugin->PluginCode('class.xt_vrepay.php:_vrepayValidation_bottom')) ? eval($plugin_code) : false;
		if(isset($plugin_return_value))	return $plugin_return_value;

		return $vrepayValidationReturnValue;
	}
	
	
	function _vrepayProcessPayment($oID, $data) {
		global $order;
		print_r($order);
		//print_r($data);
		//die();
		
		_VREPAY_SERVICENAME;
		$verwendung1 = substr($data['vr_ccowner'], 0, 25);
		$verwendung2 = substr(MODULE_PAYMENT_VREPAY_DIREKT_CC_VERWENDUNG2, 0, 25);
		
		$waehrung = $order->order_data['currency_code'];
		
		
		// collect http-postdata
		$post = array(
			'HAENDLERNR'		=> $this->haendlernr,
			'REFERENZNR'		=> MODULE_PAYMENT_VREPAY_DIREKT_CC_ORDERPREFIX . $this->referenznr,
			'BETRAG'			=> $this->betrag,
			'ARTIKELANZ'		=> '0',
			'BRAND'				=> $data['cc_brand'],
			'KARTENNR' 			=> $data['vr_ccno'],
			'GUELTIGKEITSJAHR' 	=> substr($data['vr_yto'], 2, 2),
			'GUELTIGKEITSMONAT' => $data['vr_mto'],
			'CVC2' 				=> $data['vr_cvc2'],
			
			
			
			'WAEHRUNG'			=> $order->order_data['currency_code'],
			'ZAHLART'			=> $this->zahlart,
			'SERVICENAME'		=> 'DIREKT',
			'ANTWGEHEIMNIS'		=> $this->md5,
			// some defines...
			'ARTIKELANZ'		=> count($order->order_products),
			'BENACHRPROF'		=> "KEI",
			'TSATYP'			=> $this->tsatyp,
			'VERWENDANZ'		=> "0",
		);

		print_r($post);
		die();
	}
	
	
	
	public function pspRedirect($order_data) {
		
	}
	
		
	function pspSuccess() {
		return true;
	}
	
}

?>