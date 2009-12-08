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
	public $allowed_subpayments = array('CC', 'GIROPAY', 'ELV');
	
	
	private $target_url_live = 'https://pay.vr-epay.de/pbr/transaktion';
	private $target_url_test = 'https://payinte.vr-epay.de/pbr/transaktion';
	
	/**
	 * Constructor
	 * @return void
	 */
	public function xt_vrepay() {
		global $xtPlugin;

		$this->check_license();

		if(XT_VREPAY_SERVICE == 'DIALOG') {
			$this->subpayments = true;
			$this->external = true;
		} else {

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
	}

	
	/**
	 * Kartendaten für Bestellübersicht formatieren
	 * @param array $data
	 * @return string
	 */
	private function build_payment_info($data){

		$payment_info = '';
		$payment_info .= ($data['vr_ccowner'] != '') ? TEXT_VREPAY_CCOWNER . ': ' . $data['vr_ccowner'] . '<br />' : '';
		$payment_info .= ($data['vr_ccno'] != '') ? TEXT_VREPAY_CCNO . ': ' .  substr($data['vr_ccno'], 0, 4).str_repeat('X', (strlen($data['vr_ccno']) - 8)) .substr($data['vr_ccno'], -4) . '<br />' : '';
		$payment_info .= ($data['vr_mto'] != '' && $data['vr_yto'] != '') ? TEXT_VREPAY_EXPIRES . ': ' . strftime('%B %Y', mktime(0, 0, 0, $data['vr_mto'], 1, $data['vr_yto'] )) . '<br />' : '';
		$payment_info .= ($data['vr_cvc2'] != '') ? TEXT_VREPAY_CVC2 . ': ' . $data['vr_cvc2'] : '';


		return $payment_info;

	}
	
	
	/**
	 * Daten für Monats-Dropdown
	 * @return array
	 */
	private function getMonthToList_data() {
		$expires_month = array();
		for ($i = 1; $i <= 12; $i++) {
			$expires_month[] = array ('id' => sprintf('%02d', $i), 'text' => sprintf('%02d', $i));
		}
		return $expires_month;
	}
	/**
	 * Daten für Jahres-Dropdown
	 * @return array
	 */
	private function getYearToList_data() {
		$today = getdate();
		$expires_year = array();
		for ($i = $today['year']; $i <= $today['year'] + 10; $i ++) {
			$expires_year[] = array ('id' => strftime('%Y', mktime(0, 0, 0, 1, 1, $i)), 'text' => strftime('%Y', mktime(0, 0, 0, 1, 1, $i)));
		}
		
		return $expires_year;
	}
	
	/**
	 * aktuelle Zeit ermitteln, gibt nur ein Element (z.B. year) bei Übergabe von $type, ansonsten Timestamp
	 * @param string $type
	 * @return int
	 */
	private function getCurrentTime($type = '0') {
		$today = getdate();
		return $today[$type];
	}
	
	

	/**
	 * Vorabvalidierung der eingegebenen Kreditkartendaten
	 * @param array $data
	 * @return array
	 * 
	 * @todo: Validierung Karteneigentümer angegeben
	 * @todo: Validierung CVC2 angegeben
	 */
	public function _vrepayValidation($data) {
		global $xtPlugin, $info;
	
		($plugin_code = $xtPlugin->PluginCode('class.xt_vrepay.php:_vrepayValidation_top')) ? eval($plugin_code) : false;
		if(isset($plugin_return_value))
		return $plugin_return_value;


		
		if(!is_numeric($data['vr_cvc2']) || strlen($data['vr_cvc2']) < 3 || strlen($data['vr_cvc2']) > 4) {
			$error['vr_cvc2'] = 'true';
			$info->_addInfo(ERROR_CHECK_VREPAY_CVC2);
		}
		
		if(empty($data['vr_ccowner'])) {
			$error['vr_ccowner'] = 'true';
			$info->_addInfo(ERROR_CHECK_VREPAY_CCOWNER);
		}
		
		if(!is_numeric($data['vr_ccno']) || empty($data['vr_ccno'])) {
			$error['vr_ccno'] = 'true';
			$info->_addInfo(ERROR_CHECK_VREPAY_CCNO);
		} else {
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
	
	/**
	 * Direkt Zahlung verarbeiten
	 * @param int $oID Orders ID
	 * @param array $data payment_info
	 * @param string $type cc or elv
	 * @return void
	 */
	public function _vrepayProcessPayment($oID, $data, $type = 'cc') {
		global $order, $store_handler,$xtLink, $info;


		//Lastschrift nicht verarbeiten, wenn deaktiviert.
		if($type == 'elv' && XT_VREPAY_ACTIVATE_ELV != 'true' && XT_VREPAY_SERVICE != 'DIALOG') return false;
		
		$post_data = &$this->get_post_data($data, $type);


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
					parse_str(substr($response,  $headers['header_size']), $response_body);

					if(isset($response_body['STATUS'])) {
						switch ( $response_body['STATUS'] ) {
							case "RESERVIERT":
									
								if(XT_VREPAY_PROCESSED) {
									$order->_updateOrderStatus(XT_VREPAY_PROCESSED, $response_body['STATUS'] . ': ' .$response_body['RMSG'], false, false, 'payment', $response_body['TSAID']);
								}
								unset($_SESSION['xt_vrepay_data']);
								break;
							case 'GEKAUFT':
							case 'RESERVIERT':
									
								if(XT_VREPAY_PROCESSED) {
									$order->_updateOrderStatus(XT_VREPAY_PROCESSED, $response_body['STATUS'] . ': ' .$response_body['RMSG'], false, false, 'payment', $response_body['TSAID']);
								}
									unset($_SESSION['xt_vrepay_data']);
								break;
							case 'ABGELEHNT':
								//Zahlung abgelehnt
								$info->_addInfoSession(utf8_encode($response_body['RMSG']));
								$tmp_link  = $xtLink->_link(array('page'=>'checkout', 'paction'=>'payment', 'conn'=>'SSL'));
								if(XT_VREPAY_DENIED) {
									$order->_updateOrderStatus(XT_VREPAY_DENIED, $response_body['STATUS'] . ': ' .$response_body['RMSG'], false, false, 'payment');
								}
								unset($_SESSION['last_order_id']);
								$xtLink->_redirect($tmp_link);
								break;

							default:
								//Systemfehler
								$info->_addInfoSession(ERROR_CHECK_VREPAY_SYSTEM_UNAVAILABLE);
								$tmp_link  = $xtLink->_link(array('page'=>'checkout', 'paction'=>'payment', 'conn'=>'SSL'));
								if(XT_VREPAY_FAILED) {
									$order->_updateOrderStatus(XT_VREPAY_FAILED, $response_body['STATUS'] . ' ' . $response_body['RMSG'], false, false, 'payment');
								}
								unset($_SESSION['last_order_id']);
								$xtLink->_redirect($tmp_link);

								break;
						}
					} else {
						//Systemfehler
						$info->_addInfoSession(utf8_encode($response_body['FEHLERTEXT']));
						$tmp_link  = $xtLink->_link(array('page'=>'checkout', 'paction'=>'payment', 'conn'=>'SSL'));
						if(XT_VREPAY_FAILED) {
							$order->_updateOrderStatus(XT_VREPAY_FAILED, $response_body['FEHLERTEXT'], false, false, 'payment');
						}
						unset($_SESSION['last_order_id']);
						$xtLink->_redirect($tmp_link);

					}

					break;
				default:
					
					$info->_addInfoSession(ERROR_CHECK_VREPAY_SYSTEM_UNAVAILABLE);
					$tmp_link  = $xtLink->_link(array('page'=>'checkout', 'paction'=>'payment', 'conn'=>'SSL'));
					if(XT_VREPAY_FAILED) {
								$order->_updateOrderStatus(XT_VREPAY_FAILED, 'http-code ['. $headers['http_code'].']' , false, false, 'payment');
							}
					unset($_SESSION['last_order_id']);
					$xtLink->_redirect($tmp_link);
					break;
			}			
		}	
	}
	
	/**
	 * POST-Daten erzeugen
	 * @param array $data
	 * @param string $type cc or elv
	 * @return array
	 */
	private function get_post_data(&$data, $type = 'cc') {
		global $order, $xtLink;
		
		$xtLink->amp = '&';
		$post_data = array();
		
		//Allgemeine Parameter
		$post_data['HAENDLERNR']	= XT_VREPAY_HAENDLERNR;
		$post_data['TSATYP']		= 'ECOM';
		
		//Bestelldaten
		$post_data['REFERENZNR']	= XT_VREPAY_ORDERPREFIX . $order->oID;
		
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
		$post_data['SERVICENAME'] 	= XT_VREPAY_SERVICE;

		if(XT_VREPAY_SERVICE == 'DIREKT') {
			if($type == 'elv') {
				$post_data['BLZ'] 			= $data['banktransfer_blz'];
				$post_data['KONTONR'] 		= $data['banktransfer_number'];
				$post_data['BRAND']			= 'ELV';
				$post_data['VERWENDUNG1'] = utf8_decode(substr($data['banktransfer_owner'], 0, 25));
			} elseif($type == 'cc') {
				//Kreditkarte
				$post_data['KARTENNR'] 			= $data['vr_ccno'];
				$post_data['GUELTIGKEITSMONAT']	= $data['vr_mto'];
				$post_data['GUELTIGKEITSJAHR'] 	= strftime('%y', mktime(0, 0, 0, 1, 1, $data['vr_yto']));
				$post_data['CVC2'] 				= $data['vr_cvc2'];

				$post_data['BRAND']				= $data['vr_ccbrand'];
				$post_data['VERWENDUNG1'] = utf8_decode(substr($data['vr_ccowner'], 0, 25));
			}
		}			
		
		if(XT_VREPAY_VERWENDUNG2 != '') {
			$post_data['VERWENDUNG2'] = utf8_decode(substr(XT_VREPAY_VERWENDUNG2, 0, 25));
			$post_data['VERWENDANZ'] = 2;
		} else {
			$post_data['VERWENDANZ'] = 1;	
		}
		
		$callback_secret = strtoupper(md5($post_data['BETRAG'].$post_data['REFERENZNR'].XT_VREPAY_ANTWGEHEIMNIS));
			
		$post_data['ANTWGEHEIMNIS']	= $callback_secret;
		
		if(XT_VREPAY_SERVICE == 'DIALOG') {
			
			if(defined('XT_VREPAY_CONTENT_AGB') && XT_VREPAY_CONTENT_AGB) {
				$shop_content_agb =  new content(XT_VREPAY_CONTENT_AGB);
				if ($shop_content_agb->data['content_status']) {
					$post_data['URLAGB'] = $shop_content_agb->data['content_link'];
				}

			}

			if(defined('XT_VREPAY_CONTENT_CVC') && XT_VREPAY_CONTENT_CVC) {
				$shop_content_agb =  new content(XT_VREPAY_CONTENT_CVC);
				if ($shop_content_agb->data['content_status']) {
					$post_data['URLCVC'] = $shop_content_agb->content_link;
				}
			}
			
			$post_data['URLERFOLG'] = $xtLink->_link(array('page'=>'checkout', 'paction'=>'payment_process', 'conn'=>'SSL'));
			
			$post_data['URLFEHLER'] = $xtLink->_link(array('page'=>'checkout', 'paction'=>'payment_process', 'params' => 'redirect=error', 'conn'=>'SSL'));
			$post_data['URLABBRUCH'] = $xtLink->_link(array('page'=>'checkout', 'paction'=>'payment_process', 'params' => 'redirect=userabort', 'conn'=>'SSL'));
			$post_data['URLANTWORT'] = $xtLink->_link(array('page'=>'callback', 'paction'=>'xt_vrepay', 'conn'=>'SSL'));;
			//		
			$post_data['BENACHRPROF']	= "ALL";
			

			$post_data['SPRACHE']		= ($order->order_data['language_code'] == 'de' || $order->order_data['language_code'] == 'en') ? strtoupper($order->order_data['language_code']) : 'EN';
			
			switch($_SESSION['selected_payment_sub']) {
				case 'ELV':
					$post_data['AUSWAHL'] = 'N';
					$post_data['BRAND'] = 'ELV';
					break;
				case 'GIROPAY':
					$post_data['AUSWAHL'] = 'N';
					$post_data['BRAND'] = 'GIROPAY';
					$post_data['ZAHLART'] = 'KAUFEN';
					break;
				
				case 'CC':
				case 'VISA':
				case 'ECMC':
				case 'DINERS':
				case 'AMEX':
				case 'JCB':
					$auswahl = array();
					
					if(XT_VREPAY_ACTIVATE_VISA == 'true') {
						$auswahl[] = 'VISA';
					}
					if(XT_VREPAY_ACTIVATE_ECMC == 'true') {
						$auswahl[] = 'ECMC';
					}
					if(XT_VREPAY_ACTIVATE_DINERS == 'true') {
						$auswahl[] = 'DINERS';
					}
					if(XT_VREPAY_ACTIVATE_AMEX == 'true') {
						$auswahl[] = 'AMEX';
					}
					if(XT_VREPAY_ACTIVATE_JCB == 'true') {
						$auswahl[] = 'JCB';
					}
					if(count($auswahl) > 0) {
						$post_data['AUSWAHL'] = 'J';					
						$post_data['BRAND'] = implode(';', $auswahl);
					} else {
						$post_data['AUSWAHL'] = 'J';
					}
					break;
					
				default:
					$post_data['AUSWAHL'] = 'J';					
					break;				
			}
		}
		
		return $post_data;
	}
	
	
	/**
	 * Dialog Zahlung verarbeiten
	 * @param array $order_data
	 * @return string
	 */
	public function pspRedirect($order_data) {
		global $order, $info, $xtLink;
		$post_data = $this->get_post_data($data);
		

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
			// close cURL handler
			curl_close($ch);

			$info->_addInfoSession(ERROR_CHECK_VREPAY_SYSTEM_UNAVAILABLE);
			$tmp_link  = $xtLink->_link(array('page'=>'checkout', 'paction'=>'payment', 'conn'=>'SSL'));
			if(XT_VREPAY_FAILED) {
				$order->_updateOrderStatus(XT_VREPAY_FAILED, 'Status:'.  curl_error($ch), false, false, 'payment');
			}
			unset($_SESSION['last_order_id']);
			$xtLink->_redirect($tmp_link);				
			
		} else {
			$headers = curl_getinfo($ch);
			curl_close($ch);
			parse_str(substr($response,  $headers['header_size']), $response_body);
			$response_header = http_parse_headers(substr($response, 0,  $headers['header_size']));
			switch ($headers['http_code']) {
				//Keine Weiterleitung auf Dialog erfolgt
				case '200':
				default:

					//Systemfehler
					$info->_addInfoSession(utf8_encode($response_body['FEHLERTEXT']));
					$tmp_link  = $xtLink->_link(array('page'=>'checkout', 'paction'=>'payment', 'conn'=>'SSL'));
					if(XT_VREPAY_FAILED) {
						$order->_updateOrderStatus(XT_VREPAY_FAILED, $response_body['FEHLERTEXT'], false, false, 'payment');
					}
					unset($_SESSION['last_order_id']);
					$xtLink->_redirect($tmp_link);
										
					break;
				case '302':
					//Weiterleitung auf Dialog
					return $response_header['LOCATION'];
					break;
			}
		}
	}
	
	/**
	 * Rücksprung von Dialog verarbeiten
	 * @return unknown_type
	 */
	public function pspSuccess() {
		global $info, $xtLink, $order;

		if($_GET['redirect'] == 'error') {
			$info->_addInfoSession(ERROR_CHECK_VREPAY_SYSTEM_UNAVAILABLE);
			if(XT_VREPAY_FAILED) {
				$order->_updateOrderStatus(XT_VREPAY_FAILED, '', false, false, 'payment');
			}
			unset($_SESSION['last_order_id']);
			 $xtLink->_redirect( $xtLink->_link(array('page'=>'checkout', 'paction'=>'payment', 'conn'=>'SSL')));
		}

		if($_GET['redirect'] == 'userabort') {
			
			if(XT_VREPAY_CANCELED) {
				$order->_updateOrderStatus(XT_VREPAY_CANCELED, 'Zahlung abgebrochen', false, false, 'payment');
			}
			unset($_SESSION['last_order_id']);
			 $xtLink->_redirect( $xtLink->_link(array('page'=>'checkout', 'paction'=>'payment', 'conn'=>'SSL')));
		}
		return true;
	}
	
	private function check_license() {
		
		include_once _SRV_WEBROOT . _SRV_WEB_PLUGINS . 'xt_vrepay/classes/class.license.lib.php';
		include_once _SRV_WEBROOT . _SRV_WEB_PLUGINS . 'xt_vrepay/classes/class.license.app.php';
		
		$application = new license_application( _SRV_WEBROOT . 'lic/xt_vrepay_license.txt',	false, true, true, false, true, true, array('VEYTON_LIC' => $GLOBALS['lic_parms']['key']['value']) );
		$results 	= $application->validate();
		
		if($results['RESULT'] != 'OK') {
			
			$application->print_error($results);
		}	
		
		return true;
	}
	
}

if(!function_exists('http_parse_headers')) {
	// http_parse_headers: without PECL Library
	function http_parse_headers($headers = false){

		if($headers === false) {
			return false;
		}
		// carriage return to nothing
		$headers = str_replace("\r","",$headers);
		// header divided by new line
		$headers = explode("\n",$headers);

		foreach($headers as $value) {
			$header = explode(": ",$value);
			if($header[0] && !$header[1]) {
				$headerdata['STATUS'] = $header[0];
			} elseif($header[0] && $header[1]) {
				// uppercase for all keys
				$headerdata[strtoupper($header[0])] = $header[1];
			}
		}
		return $headerdata;
	}
}
?>