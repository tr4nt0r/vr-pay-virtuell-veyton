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

class xt_vrepay {

	
	public $external = false;
	public $version = '1.0';
	public $subpayments = false;
	public $post_form = false;
	public $iframe = false;
	public $data = array();
	
	private $target_url_live = 'https://pay.vr-epay.de/pbr/transaktion';
	private $target_url_test = 'https://payinte.vr-epay.de/pbr/transaktion';
	
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

    //$tmp_data = $data;

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
   /* while (list ($key, $value) = each($tmp_data)) {
      $text = constant('TEXT_VREPAY_'.strtoupper(str_replace('vr_','',$key)));
      //if($key == 'vr_ccno') $value =  substr($value, 0, 4).str_repeat('X', (strlen($value) - 8)) .substr($value, -4);
      $new_data .= $text.': '.$value.'<br />';
    }*/
    
    $payment_info = '';    
    $payment_info .= TEXT_VREPAY_CCOWNER . ': ' . $data['vr_ccowner'] . '<br />';
    $payment_info .= TEXT_VREPAY_CCNO . ': ' .  substr($data['vr_ccno'], 0, 4).str_repeat('X', (strlen($data['vr_ccno']) - 8)) .substr($data['vr_ccno'], -4) . '<br />';
    $payment_info .= TEXT_VREPAY_EXPIRES . ': ' . strftime('%B %Y', mktime(0, 0, 0, $data['vr_mto'], 1, $data['vr_yto'] )) . '<br />';
    $payment_info .= TEXT_VREPAY_CVC2 . ': ' . $data['vr_cvc2'];
    

    return $payment_info;

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
		for ($i = $today['year']; $i <= $today['year'] + 10; $i ++) {
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
		global $order, $store_handler,$xtLink, $info;
		//print_r($order);
		//print_r($data);
		//die();
		
		
		$post_data = array();
		
		//Allgemeine Parameter
		$post_data['HAENDLERNR']	= XT_VREPAY_HAENDLERNR;
		$post_data['TSATYP']		= 'ECOM';
		
		//Bestelldaten
		$post_data['REFERENZNR']	= substr(XT_VREPAY_ORDERPREFIX. $store_handler->shop_id . '-' .$oID, -20);
		$currency = new currency($order->order_data['currency_code']);		
		$post_data['BETRAG']			= $order->order_total['total']['plain'] * pow(10, $currency->decimals);		
		$post_data['WAEHRUNG']		= $order->order_data['currency_code'];
		$post_data['INFOTEXT']		= '';
		$post_data['ARTIKELANZ']	= count($order->order_products);
		
		//Warenkorb
		
		
		for($i = 0; $i < count($order->order_products); $i++) {
			$post_data['ARTIKELNR' . ($i+1)] = $order->order_products[$i]['products_model'];
			$post_data['ARTIKELBEZ' . ($i+1)] = utf8_decode($order->order_products[$i]['products_name']);
			$post_data['ANZAHL' . ($i+1)] = (int)$order->order_products[$i]['products_quantity'];
			$post_data['EINZELPREIS' . ($i+1)] = $order->order_products[$i]['products_price']['plain'] * pow(10, $currency->decimals);
		}

		//Transaktion
		$post_data['ZAHLART'] 		= XT_VREPAY_ZAHLART;
		$post_data['SERVICENAME'] 		= 'DIREKT';
		
		$post_data['KARTENNR'] 			= $data['vr_ccno'];
		$post_data['GUELTIGKEITSMONAT']	= $data['vr_mto'];
		$post_data['GUELTIGKEITSJAHR'] 	= strftime('%y', mktime(0, 0, 0, 1, 1, $data['vr_yto']));
		$post_data['CVC2'] 				= $data['vr_cvc2'];
		
		$post_data['BRAND']				= $data['vr_ccbrand'];
		
		
		
		
		
		
		$post_data['VERWENDUNG1'] = utf8_decode(substr($data['vr_ccowner'], 0, 25));
		if(XT_VREPAY_VERWENDUNG2 != '') {
			$post_data['VERWENDUNG2'] = utf8_decode(substr(XT_VREPAY_VERWENDUNG2, 0, 25));
			$post_data['VERWENDANZ'] = 2;
		} else {
			$post_data['VERWENDANZ'] = 1;	
		}

		// cURL init & options
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, (XT_VREPAY_SYSTEM == 'LIVE') ? $this->target_url_live : $this->target_url_test);
		curl_setopt($ch, CURLOPT_USERPWD, XT_VREPAY_HAENDLERNR . ':' . XT_VREPAY_PASSWORT);
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_SSLVERSION, 3);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		// cURL execute request
		$response = curl_exec($ch);

		if ($response === false) {

			$err05 = MODULE_PAYMENT_VREPAY_DIREKT_CC_TEXT_ERR05 ." [". curl_error($ch) ."]";


			// close cURL handler
			curl_close($ch);

			$info->_addInfoSession(ERROR_CHECK_VREPAY_SYSTEM_UNAVAILABLE);
			$tmp_link  = $xtLink->_link(array('page'=>'checkout', 'paction'=>'payment', 'conn'=>'SSL'));
			if(XT_VREPAY_CANCELED) {
				$order->_updateOrderStatus(XT_VREPAY_CANCELED, 'Status:'.  curl_error($ch), false, false, 'payment');
			}
			unset($_SESSION['last_order_id']);
			$xtLink->_redirect($tmp_link);
				

				
		} else {
			$headers = curl_getinfo($ch);
			curl_close($ch);

			switch ($headers['http_code']) {
				case '200':
					//print_r($headers);
					parse_str(substr($response,  $headers['header_size']), $response_body);
					//echo substr($response,  $headers['header_size']);

					if(isset($response_body['STATUS'])) {
						switch ( $response_body['STATUS'] ) {
							case "RESERVIERT":
									
								if(XT_VREPAY_PROCESSED) {
									$order->_updateOrderStatus(XT_VREPAY_PROCESSED, $response_body['STATUS'] . ': ' .$response_body['RMSG'], false, false, 'payment', $response_body['TSAID']);
								}
								unset($_SESSION['xt_vrepay_data']);
								break;
							case "GEKAUFT":
									
								if(XT_VREPAY_PROCESSED) {
									$order->_updateOrderStatus(XT_VREPAY_PROCESSED, $response_body['STATUS'] . ': ' .$response_body['RMSG'], false, false, 'payment', $response_body['TSAID']);
								}
									unset($_SESSION['xt_vrepay_data']);
								break;
							case "ABGELEHNT":
								//Zahlung abgelehnt
								$info->_addInfoSession(utf8_encode($response_body['RMSG']));
								$tmp_link  = $xtLink->_link(array('page'=>'checkout', 'paction'=>'payment', 'conn'=>'SSL'));
								if(XT_VREPAY_CANCELED) {
									$order->_updateOrderStatus(XT_VREPAY_CANCELED, $response_body['STATUS'] . ': ' .$response_body['RMSG'], false, false, 'payment');
								}
								unset($_SESSION['last_order_id']);
								$xtLink->_redirect($tmp_link);
								break;

							default:
								//Systemfehler
								$info->_addInfoSession(ERROR_CHECK_VREPAY_SYSTEM_UNAVAILABLE);
								$tmp_link  = $xtLink->_link(array('page'=>'checkout', 'paction'=>'payment', 'conn'=>'SSL'));
								if(XT_VREPAY_CANCELED) {
									$order->_updateOrderStatus(XT_VREPAY_CANCELED, $response_body['STATUS'] . ' ' . $response_body['RMSG'], false, false, 'payment');
								}
								unset($_SESSION['last_order_id']);
								$xtLink->_redirect($tmp_link);

								break;
						}
					} else {
						//Systemfehler
						$info->_addInfoSession(ERROR_CHECK_VREPAY_SYSTEM_UNAVAILABLE . utf8_encode($response_body['FEHLERTEXT']));
						$tmp_link  = $xtLink->_link(array('page'=>'checkout', 'paction'=>'payment', 'conn'=>'SSL'));
						if(XT_VREPAY_CANCELED) {
							$order->_updateOrderStatus(XT_VREPAY_CANCELED, $response_body['FEHLERTEXT'], false, false, 'payment');
						}
						unset($_SESSION['last_order_id']);
						$xtLink->_redirect($tmp_link);

					}

					break;
				default:
					
					$info->_addInfoSession(ERROR_CHECK_VREPAY_SYSTEM_UNAVAILABLE);
					$tmp_link  = $xtLink->_link(array('page'=>'checkout', 'paction'=>'payment', 'conn'=>'SSL'));
					if(XT_VREPAY_CANCELED) {
								$order->_updateOrderStatus(XT_VREPAY_CANCELED, 'http-code ['. $headers['http_code'].']' , false, false, 'payment');
							}
					unset($_SESSION['last_order_id']);
					$xtLink->_redirect($tmp_link);
					break;
			}			
		}	
	}
	
	
	
	public function pspRedirect($order_data) {
		
	}
	
		
	function pspSuccess() {
		return true;
	}
	
}

?>