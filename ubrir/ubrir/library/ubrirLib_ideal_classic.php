<?php
defined('_JEXEC') or die('Restricted access');
define('VERSION_CLASSIC','1.2.0');

require_once 'ubrirLib_http.inc.php';
require_once 'ubrirLib_ubrirueberweisung_classic.php';
require_once 'ubrirLib_Logger.inc.php';
require_once 'ubrirLib_ideal_banks.inc.php';
/**
 * iDeal_Classic extends ubrirueberweisung_Classic, implementing payment via iDeal
 * Setup a session within iDeal using the classic api
 * You get the so called payment-url after successful configuration
 * Payment is enabled with this url being sent to iDeal
 *
 * eg:
 * $ubrir = $ubrirLib_iDealClassic = new ubrirLib_iDealClassic ($configurationKey, $password, $hashfunction = 'sha1');
 * $ubrir->getRelatedBanks(); //get all iDEAL-Banks
 * $ubrir->getPaymentUrl(); //returns paymentUrl including (including ...&hash=1234567890&...)
 *
 * Copyright (c) 2012 ubrir AG
 * 
 * Released under the GNU General Public License (Version 2)
 * [http://www.gnu.org/licenses/gpl-2.0.html]
 *
 * $Date: 2012-11-23 17:15:47 +0100 (Fri, 23 Nov 2012) $
 * @version ubrirLib 1.5.4  $Id: ubrirLib_ideal_classic.php 5773 2012-11-23 16:15:47Z dehn $
 * @author ubrir AG http://www.ubrir.com (integration@ubrir.com)
 *
 */
class ubrirLib_iDealClassic extends ubrirLib_ubrirueberweisungClassic {
	
	private $_apiUrl = '';
	
	private $_apiKey = '';
	
	private $_relatedBanks = array();
	
	private $_ubrirLib_iDeal_Banks = null;
	
	protected $_password;
	
	protected $_userId;
	
	protected $_projectId;
	
	protected $_paymentUrl = 'https://www.ubrir.com/payment/ideal';
	
	protected $_hashFields = array(
		'user_id',
		'project_id',
		'sender_holder',
		'sender_account_number',
		'sender_bank_code',
		'sender_country_id',
		'amount',
		'reason_1',
		'reason_2',
		'user_variable_0',
		'user_variable_1',
		'user_variable_2',
		'user_variable_3',
		'user_variable_4',
		'user_variable_5',
	);
	
	
	/**
	 * 
	 * Contructor for ubrirLib_iDealClassic
	 * @param string $configKey
	 * @param string $password
	 * @param string $hashFunction
	 */
	public function __construct($configKey, $password, $hashFunction = 'sha1') {
		list($userId, $projectId, $apiKey) = explode(':', $configKey);
		$this->_password = $password;
		$this->_userId = $this->params['user_id'] = $userId;
		$this->_projectId = $this->params['project_id'] = $projectId;
		$this->_hashFunction = strtolower($hashFunction);
		$this->_paymentUrl = $this->_getPaymentDomain();
		$this->_ubrirLib_iDeal_Banks = new ubrirLib_iDeal_Banks($configKey, $this->_paymentUrl);
	}
	
	
	/**
	 *
	 * Set sender's country id
	 * @param string $senderCountryId
	 * @return instance
	 */
	public function setSenderCountryId($senderCountryId = 'NL') {
		$this->params['sender_country_id'] = $senderCountryId;
	}
	
	
	/**
	 *
	 * Set sender's bank code
	 * @param string $senderBankCode
	 * @return instance
	 */
	public function setSenderBankCode($senderBankCode) {
		$this->params['sender_bank_code'] = $senderBankCode;
		return $this;
	}
	
	
	/**
	 * Getter for occurred errors
	 * (non-PHPdoc)
	 * @see ubrirLib_ubrirueberweisungClassic::getError()
	 */
	public function getError(){
		return $this->error;
	}
	
	
	/**
	 * Get related banks of iDeal
	 * @return array
	 */
	public function getRelatedBanks() {
		$this->_ubrirLib_iDeal_Banks->sendRequest();
		return $this->_ubrirLib_iDeal_Banks->getBanks();
	}
	
	
	/**
	 * Getter for the payment domain
	 * (non-PHPdoc)
	 * @see ubrirLib_ubrirueberweisungClassic::_getPaymentDomain()
	 */
	protected function _getPaymentDomain() {
		return (getenv('idealApiUrl') != '') ? getenv('idealApiUrl') : $this->_paymentUrl;
	}
}
?>