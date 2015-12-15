<?php
/**
 * @package	VM payment module for Joomla!
 * @version	1.0.0
 * @author	itmosfera.ru
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');


if (!class_exists('vmPSPlugin')) {
	require(JPATH_VM_PLUGINS .DIRECTORY_SEPARATOR. 'vmpsplugin.php');
}

require(dirname(__FILE__).'/UbrirClass.php');

class plgVmPaymentubrir extends vmPSPlugin {
	const RELEASE = 'VM 3.0.9';
	const SU_ubrirBANKING = 'su';

	$result = JRequest::getVar('result');
	$on = JRequest::getVar('on');
	$desc = JRequest::getVar('desc');

	function __construct (& $subject, $config) {

		parent::__construct($subject, $config);

		$this->_loggable = TRUE;
		$this->tableFields = array_keys($this->getTableSQLFields());
		$this->_tablepkey = 'id'; //virtuemart_ubrir_id';
		$this->_tableId = 'id'; //'virtuemart_ubrir_id';

		$varsToPush = $this->getVarsToPush();

		$this->setConfigParameterable($this->_configTableFieldName, $varsToPush);

	}
	
	 function plgVmOnSelectPayment () {
    	return 'УБРиР';
    }
 

	/**
	 * @return string
	 */
	public function getVmPluginCreateTableSQL () {

		return $this->createTableSQL('Payment Ubrir Table');
	}

	/**
	 * @return array
	 */
	function getTableSQLFields () {

		$SQLfields = array(
			'id' => 'int(11) UNSIGNED NOT NULL AUTO_INCREMENT',
			'virtuemart_order_id' => 'varchar(255)',
			'order_number' => 'char(64)',
			'virtuemart_paymentmethod_id' => 'mediumint(1) UNSIGNED',
			'payment_name' => 'varchar(1000)',
			'payment_order_total' => 'decimal(15,5) NOT NULL',
			'payment_currency' => 'smallint(1)',
			'custom' => 'varchar(255)',
			'type' => 'varchar(50)',
			'session_id' => 'varchar(255)',
		);
		return $SQLfields;
	}

	/**
	 * @param $cart
	 * @param $order
	 * @return bool|null
	 */
	function plgVmConfirmedOrder ($cart, $order) {

		if (!($this->_currentMethod = $this->getVmPluginMethod($order['details']['BT']->virtuemart_paymentmethod_id))) {
			return NULL; // Another method was selected, do nothing
		}
		if (!$this->selectedThisElement($this->_currentMethod->payment_element)) {
			return FALSE;
		}
		$this->sendTransactionRequest( $cart, $order);

	}


	function displayErrors ($errors) {

		foreach ($errors as $error) {
			vmError(vmText::sprintf('VMPAYMENT_ubrir_ERROR_FROM', $error ['message'], $error ['field'], $error ['code']));
			vmInfo(vmText::sprintf('VMPAYMENT_ubrir_ERROR_FROM', $error ['message'], $error ['field'], $error ['code']));
			if ($error ['message'] == 401) {
				vmdebug('check you payment parameters: custom_id, project_id, api key');
			}
		}
	}


	function sendTransactionRequest ( $cart, $order, $doRedirect = true) {
		
		$twpg_amount = round($order['details']['BT']->order_total, 2);
		
		
		if (!($method = $this->getVmPluginMethod ($order['details']['BT']->virtuemart_paymentmethod_id))) {
			return NULL;
		} 
		$bankHandler = new Ubrir(array(																											 // инициализируем объект операции в TWPG
							'shopId' => $method->twpg_id, 
							'order_id' => $order['details']['BT']->order_number, 
							'sert' => $method->twpg_sert,
							'amount' => $twpg_amount,
							'approve_url' => JURI::root () .'plugins/vmpayment/'.$this->_currentMethod->payment_element.'/result.php?id='.$order['details']['BT']->order_number,
							'cancel_url' => JURI::root () .'plugins/vmpayment/'.$this->_currentMethod->payment_element.'/result.php?id='.$order['details']['BT']->order_number,
							'decline_url' => JURI::root () .'plugins/vmpayment/'.$this->_currentMethod->payment_element.'/result.php?id='.$order['details']['BT']->order_number,
							));                    
		$response_order = $bankHandler->prepare_to_pay();
		
		if(!empty($response_order)) {	
		$db =& JFactory::getDBO();
		$sql = " INSERT INTO #__virtuemart_payment_plg_".$this->_currentMethod->payment_element." 
		(`virtuemart_order_id`, `order_number`, `type`, `session_id`) 
		VALUES  
		('".$order['details']['BT']->order_number."', '".$response_order->OrderID[0]."', 1, '".$response_order->SessionID[0]."') ";
		$db->setQuery($sql);
		if(!$db->query()) exit('error_1101'); 
		}
		else exit('error_1102'); 
		
		
		$cart->emptyCart();	
	
	
		$twpg_url = $response_order->URL[0].'?orderid='.$response_order->OrderID[0].'&sessionid='.$response_order->SessionID[0];
		echo '<p>Данный заказ необходимо оплатить одним из методов, приведенных ниже: </p> <INPUT TYPE="button" value="Оплатить Visa" onclick="document.location = \''.$twpg_url.'\'">';
		if($method->two == 0) {                                                                               // если активны два процессинга, то работаем еще и с Uniteller
	    echo ' <INPUT TYPE="button" onclick="document.forms.uniteller.submit()" value="Оплатить MasterCard">';
	    include(dirname(__FILE__)."/include/uni_form.php");
	  };

	}

	function redirectToCart ($msg = NULL) {

		if (!$msg) {
			$msg = vmText::_('VMPAYMENT_ubrir_ERROR_TRY_AGAIN');
		}
		$app = JFactory::getApplication();
		$app->redirect(JRoute::_('index.php?option=com_virtuemart&view=cart&Itemid=' . vRequest::getInt('Itemid').'&lang='.vRequest::getCmd('lang',''), false), $msg);
	}

	/**
	 * @param $virtuemart_paymentmethod_id
	 * @param $paymentCurrencyId
	 * @return bool|null
	 */
	function plgVmgetPaymentCurrency ($virtuemart_paymentmethod_id, &$paymentCurrencyId) {

		if (!($this->_currentMethod = $this->getVmPluginMethod($virtuemart_paymentmethod_id))) {
			return NULL; // Another method was selected, do nothing
		}
		if (!$this->selectedThisElement($this->_currentMethod->payment_element)) {
			return FALSE;
		}
		$this->getPaymentCurrency($this->_currentMethod);
		$paymentCurrencyId = $this->_currentMethod->payment_currency;
	}

	/**
	 * @param $virtuemart_paymentmethod_id
	 * @param $paymentCurrencyId
	 * @return bool|null
	 */
	function plgVmgetEmailCurrency ($virtuemart_paymentmethod_id, $virtuemart_order_id, &$emailCurrencyId) {

		if (!($this->_currentMethod = $this->getVmPluginMethod($virtuemart_paymentmethod_id))) {
			return NULL; // Another method was selected, do nothing
		}
		if (!$this->selectedThisElement($this->_currentMethod->payment_element)) {
			return FALSE;
		}
		if (!($payments = $this->getDatasByOrderId($virtuemart_order_id))) {
			// JError::raiseWarning(500, $db->getErrorMsg());
			return '';
		}
		//vmdebug('plgVmgetEmailCurrency', $payments);

		if (empty($payments[0]->email_currency)) {
			$vendorId = 1; //VirtueMartModelVendor::getLoggedVendor();
			$db = JFactory::getDBO();
			$q = 'SELECT   `vendor_currency` FROM `#__virtuemart_vendors` WHERE `virtuemart_vendor_id`=' . $vendorId;
			$db->setQuery($q);
			$emailCurrencyId = $db->loadResult();
		} else {
			$emailCurrencyId = $payments[0]->email_currency;
		}

	}

	/**
	 * @param $html
	 * @return bool|null|string
	 */
	function plgVmOnPaymentResponseReceived (&$html) {

		if (!class_exists('VirtueMartCart')) {
			require(VMPATH_SITE .DIRECTORY_SEPARATOR. 'helpers' .DIRECTORY_SEPARATOR. 'cart.php');
		}
		if (!class_exists('shopFunctionsF')) {
			require(VMPATH_SITE .DIRECTORY_SEPARATOR. 'helpers' .DIRECTORY_SEPARATOR. 'shopfunctionsf.php');
		}
		if (!class_exists('VirtueMartModelOrders')) {
			require(VMPATH_ADMIN .DIRECTORY_SEPARATOR. 'models' .DIRECTORY_SEPARATOR. 'orders.php');
		}

		// the payment itself should send the parameter needed.
		$virtuemart_paymentmethod_id = vRequest::getInt('pm', 0);
		$order_number = vRequest::getString('on', 0);

		if (!($this->_currentMethod = $this->getVmPluginMethod($virtuemart_paymentmethod_id))) {
			//vmdebug('plgVmOnPaymentResponseReceived NOT getVmPluginMethod');
			return NULL; // Another method was selected, do nothing
		}
		if (!$this->selectedThisElement($this->_currentMethod ->payment_element)) {
			//vmdebug('ubrir plgVmOnPaymentResponseReceived NOT selectedThisElement');
			return NULL;
		}

		if (!($virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber($order_number))) {
			//vmdebug('ubrir plgVmOnPaymentResponseReceived NOT getOrderIdByOrderNumber');
			return NULL;
		}
		if (!($payments = $this->getDatasByOrderId($virtuemart_order_id))) {
			// JError::raiseWarning(500, $db->getErrorMsg());
			return '';
		}
		$orderModel = VmModel::getModel('orders');
		$order = $orderModel->getOrder($virtuemart_order_id);
		// may be we did not receive the notification
		// Thus the call of the success-URL should check, if the notification has already been arrived at the shop  .
		//If this is not true, a transaction detail request (step 4) should be triggered with the call of the success-URL,


		$html = $this->_getPaymentResponseHtml($this->_currentMethod, $order, $payments);
		//We delete the old stuff
		// get the correct cart / session
		$cart = VirtueMartCart::getCart();
		$cart->emptyCart();
		return TRUE;
	}

	/**
	 * @return bool|null
	 */
	function plgVmOnUserPaymentCancel () {

		echo 'Заказ отменен';
		
	}

	/*
		 * plgVmOnPaymentNotification() - This event is fired by Offline Payment. It can be used to validate the payment data as entered by the user.
		 * Return:
		 * Parameters:
		 *  None
		 *  @author Valerie Isaksen
		 */

	/**
	 * @return bool|null
	 */
	function plgVmOnPaymentNotification () {
	
	switch ($result) {
				case '0':
					echo '<div class="ubr_f">Оплата не совершена</div>';                                                                                          //эти два пункта по Юнителлеру
					break;		
					
				case '1':
					echo '<div class="ubr_s">Оплата совершена успешно, ожидайте обработки заказа</div>';
					break;		
		
				case '3':
					echo '<div class="ubr_f">Оплата отменена пользователем</div>';
					break;
					
				case '4':
					echo '<div class="ubr_f">Оплата отменена банком. Причина - '.$desc.'</div>';
					break;
					
				case '2':
					$db =& JFactory::getDBO();
					$sql = "SELECT * FROM ".$this->_tablename." WHERE virtuemart_order_id = '".htmlspecialchars(stripslashes($on))."'";
					$db->setQuery($sql);
					$current = $db->loadObjectList();
					if(empty($current)) exit('error_1101'); 
					
					$modelOrder = VmModel::getModel ('orders');
					$virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber(stripslashes($on));
					$order = $modelOrder->getOrder($virtuemart_order_id);
					
					$method = $this->getVmPluginMethod ($order['details']['BT']->virtuemart_paymentmethod_id);
					$bankHandler = new Ubrir(array(																											 // инициализируем объект операции в TWPG
							'shopId' => $method->twpg_id, 
							'order_id' => $on, 
							'sert' => $method->twpg_sert,
						    'twpg_order_id' => $current[0]->order_number, 
						    'twpg_session_id' => $current[0]->session_id
							));
						
					if($bankHandler->check_status("APPROVED")) {
					$order['order_status'] = 'C';
				    $modelOrder->updateStatusForOneOrder($virtuemart_order_id, $order, TRUE);
					echo '<div class="ubr_s">Оплата успешно совершена</div>';
					}
					else echo '<div class="ubr_f">Неверный статус заказа</div>';
					break;
				
				case '5':
					$modelOrder = VmModel::getModel ('orders');
					$virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber(stripslashes($on));
					$order = $modelOrder->getOrder($virtuemart_order_id);
					$order['order_status'] = 'C';
				    $modelOrder->updateStatusForOneOrder($virtuemart_order_id, $order, TRUE);
					echo '<div class="ubr_s">Оплата успешно совершена</div>';
					break;
					
				default:
					# code...
					break;
					
			}
		
	}

	function _checkAmountAndCurrency ($ubrir_data, $payments) {
		$payment_currency_code_3 = shopFunctions::getCurrencyByID($payments[0]->payment_currency, 'currency_code_3');
		if (($ubrir_data['ubrir_response_amount'] != $payments[0]->payment_order_total) or ($ubrir_data['ubrir_response_currency'] != $payment_currency_code_3)) {
			$this->debugLog( $ubrir_data['ubrir_response_amount'] . ' ' . $payments[0]->payment_order_total.' '. $ubrir_data['ubrir_response_currency'] . ' ' . $payment_currency_code_3, 'plgVmOnPaymentNotification _checkAmountAndCurrency' , 'error');
			return false;
		}
		return true;
	}

	/**
	 * Display stored payment data for an order
	 * @param  int $virtuemart_order_id
	 * @param  int $payment_method_id
	 * @see components/com_virtuemart/helpers/vmPSPlugin::plgVmOnShowOrderBEPayment()
	 */
	function plgVmOnShowOrderBEPayment ($virtuemart_order_id, $payment_method_id) {

		if (!$this->selectedThisByMethodId($payment_method_id)) {
			return NULL; // Another method was selected, do nothing
		}

		if (!($payments = $this->getDatasByOrderId($virtuemart_order_id))) {
			// JError::raiseWarning(500, $db->getErrorMsg());
			return '';
		}

		$html = '<table class="adminlist table">' . "\n";
		$html .= $this->getHtmlHeaderBE();
		$code = "ubrir_response_";
		$first = TRUE;
		foreach ($payments as $payment) {
			$html .= '<tr class="row1"><th>' . vmText::_('COM_VIRTUEMART_DATE') . '</th><th align="left">' . $payment->created_on . '</th></tr>';
			// Now only the first entry has this data when creating the order
			if ($first) {
				$html .= $this->getHtmlRowBE('ubrir_PAYMENT_NAME', $payment->payment_name);
				// keep that test to have it backwards compatible. Old version was deleting that column  when receiving an IPN notification
				if ($payment->payment_order_total and  $payment->payment_order_total != 0.00) {
					$html .= $this->getHtmlRowBE('ubrir_PAYMENT_ORDER_TOTAL', $payment->payment_order_total . " " . shopFunctions::getCurrencyByID($payment->payment_currency, 'currency_code_3'));
				}
				if ($payment->email_currency and  $payment->email_currency != 0) {
					$html .= $this->getHtmlRowBE('ubrir_PAYMENT_EMAIL_CURRENCY', shopFunctions::getCurrencyByID($payment->email_currency, 'currency_code_3'));
				}
				if ($payment->email_currency and  $payment->email_currency != 0) {
					$html .= $this->getHtmlRowBE('ubrir_RESPONSE_TRANSACTION', $payment->ubrir_response_transaction);
				}
				$first = FALSE;
			} else {
				foreach ($payment as $key => $value) {
					// only displays if there is a value or the value is different from 0.00 and the value
					if ($value) {
						if (substr($key, 0, strlen($code)) == $code) {
							$html .= $this->getHtmlRowBE($key, $value);
						}
					}
				}
			}
		}
		$html .= '</table>' . "\n";
		return $html;
	}


	/**
	 * @param $method
	 * @param $order
	 * @return string
	 */
	function _getPaymentResponseHtml ($method, $order, $payments) {
		VmConfig::loadJLang('com_virtuemart_orders', TRUE);
		if (!class_exists('CurrencyDisplay')) {
			require(VMPATH_ADMIN .DIRECTORY_SEPARATOR. 'helpers' .DIRECTORY_SEPARATOR. 'currencydisplay.php');
		}

		if (!class_exists('VirtueMartCart')) {
			require(VMPATH_SITE .DIRECTORY_SEPARATOR. 'helpers' .DIRECTORY_SEPARATOR. 'cart.php');
		}

		VmConfig::loadJLang('com_virtuemart_orders',TRUE);

		$totalInPaymentCurrency = vmPSPlugin::getAmountInCurrency($order['details']['BT']->order_total,$order['details']['BT']->order_currency);
		$cart = VirtueMartCart::getCart();
		$currencyDisplay = CurrencyDisplay::getInstance($cart->pricesCurrency);

		$payment = end($payments);

		$pluginName = $this->renderPluginName($method, $where = 'post_payment');
		$html = $this->renderByLayout('post_payment', array(
		                                                   'order' => $order,
		                                                   'paymentInfos' => $payment,
		                                                   'pluginName' => $pluginName,
		                                                   'displayTotalInPaymentCurrency' => $totalInPaymentCurrency['display']
		                                              ));
		//vmdebug('_getPaymentResponseHtml', $html,$pluginName,$paypalTable );

		return $html;
	}

	/*
		 * @param $method plugin
	 *  @param $where from where tis function is called
		 */

	protected function renderPluginName ($method, $where = 'checkout') {

		
		$payment_name = $method->payment_name;
		$html = $this->renderByLayout('render_pluginname', array(
		                                                        'where' => $where,
		                                                    
		                                                        'payment_name' => $payment_name,
		                                                        'payment_description' => $method->payment_desc,
		                                                   ));

		return $html;
	}

	protected function checkConditions ($cart, $method, $cart_prices) {

		$this->convert_condition_amount($method);
		$amount = $this->getCartAmount($cart_prices);
		$address = (($cart->ST == 0) ? $cart->BT : $cart->ST);

		$amount_cond = ($amount >= $method->min_amount AND $amount <= $method->max_amount
			OR
			($method->min_amount <= $amount AND ($method->max_amount == 0)));

		$countries = array();
		if (!empty($method->countries)) {
			if (!is_array($method->countries)) {
				$countries[0] = $method->countries;
			} else {
				$countries = $method->countries;
			}
		}
		// probably did not gave his BT:ST address
		if (!is_array($address)) {
			$address = array();
			$address['virtuemart_country_id'] = 0;
		}

		if (!isset($address['virtuemart_country_id'])) {
			$address['virtuemart_country_id'] = 0;
		}
		if (in_array($address['virtuemart_country_id'], $countries) || count($countries) == 0) {
			if ($amount_cond) {
				return TRUE;
			}
		}

		return FALSE;
	}

	function plgVmOnStoreInstallPaymentPluginTable ($jplugin_id) {

		return $this->onStoreInstallPluginTable($jplugin_id);
	}

	public function plgVmOnSelectCheckPayment (VirtueMartCart $cart, &$msg) {

		return $this->OnSelectCheck($cart);
	}


	public function plgVmDisplayListFEPayment (VirtueMartCart $cart, $selected = 0, &$htmlIn) {

		if ($this->getPluginMethods($cart->vendorId) === 0) {
			if (empty($this->_name)) {
				$app = JFactory::getApplication();
				$app->enqueueMessage(vmText::_('COM_VIRTUEMART_CART_NO_' . strtoupper($this->_psType)));
				return false;
			} else {
				return false;
			}
		}
		$htmla = array();
		$html = '';
		VmConfig::loadJLang('com_virtuemart');
		$currency = CurrencyDisplay::getInstance();
		foreach ($this->methods as $this->_currentMethod) {
			if ($this->checkConditions($cart, $this->_currentMethod, $cart->cartPrices)) {
				$cartPrices = $cart->cartPrices;
				$methodSalesPrice = $this->calculateSalesPrice($cart, $this->_currentMethod, $cartPrices);

				$logo = $this->displayLogos($this->_currentMethod->payment_logos);
				$logo_link = $this->getLogoLink();
				$payment_cost = '';
				if ($methodSalesPrice) {
					$payment_cost = $currency->priceDisplay($methodSalesPrice);
				}
				if ($selected == $this->_currentMethod->virtuemart_paymentmethod_id) {
					$checked = 'checked="checked"';
				} else {
					$checked = '';
				}
				$html .= $this->renderByLayout('display_payment', array(
				                                                       'plugin' => $this->_currentMethod,
				                                                       'checked' => $checked,
				                                                       'payment_logo' => $logo,
				                                                       'payment_logo_link' => $logo_link,
				                                                       'payment_cost' => $payment_cost,
				                                                  ));

				$htmla[] = $html;
			}
		}
		if (!empty($htmla)) {
			$htmlIn[] = $htmla;
		}

		return true;
	}

	/**
	 * displays the logos of a VirtueMart plugin
	 *
	 * @author Valerie Isaksen
	 * @param array $logo_list
	 * @return html with logos
	 */
	protected function getLogoLink () {

		$jlang = JFactory::getLanguage ();
		$lang = $jlang->getTag ();
		$langArray = explode ("-", $lang);
		$lang = strtolower ($langArray[1]);
		$listOfLangs=array('de','en','nl', 'pl', 'fr', 'it','es');
		$linkLang='en';
		if (in_array($lang,$listOfLangs)) {
			$linkLang=$lang;
		}
		$logoLink="https://images.ubrir.com/".$linkLang."/su/landing.php";

		return $logoLink;
	}

	public function plgVmOnSelectedCalculatePricePayment (VirtueMartCart $cart, array &$cart_prices, &$cart_prices_name) {

		return $this->onSelectedCalculatePrice($cart, $cart_prices, $cart_prices_name);
	}


	function plgVmOnCheckAutomaticSelectedPayment (VirtueMartCart $cart, array $cart_prices = array(), &$paymentCounter) {

		return $this->onCheckAutomaticSelected($cart, $cart_prices, $paymentCounter);
	}

	public function plgVmOnShowOrderFEPayment ($virtuemart_order_id, $virtuemart_paymentmethod_id, &$payment_name) {


		if (!($this->selectedThisByMethodId($virtuemart_paymentmethod_id))) {
			return NULL;
		}
		$payments = $this->getDatasByOrderId($virtuemart_order_id);
		$nb = count($payments);

		$payment_name = $this->renderByLayout('order_fe', array(
		                                                       'paymentInfos' => @$payments[$nb - 1],
		                                                       'paymentName' => @$payments[0]->payment_name,
		                                                  ));
	}

	function plgVmonShowOrderPrintPayment ($order_number, $method_id) {

		return $this->onShowOrderPrint($order_number, $method_id);
	}

	function plgVmDeclarePluginParamsPaymentVM3( &$data) {
		return $this->declarePluginParams('payment', $data);
	}

	function plgVmSetOnTablePluginParamsPayment ($name, $id, &$table) {

		return $this->setOnTablePluginParams($name, $id, $table);
	}


	static function   getSuccessUrl ($order) {
		return JURI::root()."index.php?option=com_virtuemart&view=pluginresponse&task=pluginresponsereceived&pm=" . $order['details']['BT']->virtuemart_paymentmethod_id . '&on=' . $order['details']['BT']->order_number . "&Itemid=" . vRequest::getInt('Itemid'). '&lang='.vRequest::getCmd('lang',''); ;
	}

	static function   getCancelUrl ($order) {
		return  JURI::root()."index.php?option=com_virtuemart&view=pluginresponse&task=pluginUserPaymentCancel&pm=" . $order['details']['BT']->virtuemart_paymentmethod_id . '&on=' . $order['details']['BT']->order_number . '&Itemid=' . vRequest::getInt('Itemid').'&lang='.vRequest::getCmd('lang','');
	}

	static function   getNotificationUrl ($security, $order_number) {

		return JURI::root()  .  "index.php?option=com_virtuemart&view=pluginresponse&task=pluginnotification&tmpl=component&&security=" . $security . "&on=" . $order_number .'&lang='.vRequest::getCmd('lang','');
	}

	static function getSecurityKey () {
		if (!class_exists('ubrirLib_ubrirueberweisungClassic')) {
			require(VMPATH_ROOT .DIRECTORY_SEPARATOR. 'plugins' .DIRECTORY_SEPARATOR. 'vmpayment' .DIRECTORY_SEPARATOR. 'ubrir' .DIRECTORY_SEPARATOR. 'ubrir' .DIRECTORY_SEPARATOR. 'library' .DIRECTORY_SEPARATOR. 'ubrirLib_ubrirueberweisung_classic.php');
		}
		return ubrirLib_ubrirueberweisungClassic::generatePassword();
	}
}

