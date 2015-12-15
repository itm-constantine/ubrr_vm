<?php
/**
 * @package	VM payment module for Joomla!
 * @version	1.0.0
 * @author	itmosfera.ru
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */			
	  $callbackurl = JURI::root () .'plugins/vmpayment/'.$this->_currentMethod->payment_element.'/result.php?id='.$order['details']['BT']->order_number;
	  $sign = strtoupper(md5(md5($method->uni_id).'&'.md5($method->uni_login).'&'.md5($method->uni_pass).'&'.md5($order['details']['BT']->order_number).'&'.md5($twpg_amount)));
	  echo '<form action="https://91.208.121.201/estore_listener.php" name="uniteller" method="post">
		<input type="hidden" name="SHOP_ID" value="'.$method->uni_id.'" >
		<input type="hidden" name="LOGIN" value="'.$method->uni_login.'" >
		<input type="hidden" name="ORDER_ID" value="'.$order['details']['BT']->order_number.'">
		<input type="hidden" name="PAY_SUM" value="'.$twpg_amount.'" >
		<input type="hidden" name="VALUE_1" value="'.$order['details']['BT']->order_number.'" >
		<input type="hidden" name="URL_OK" value="'.$callbackurl.'&status=1&" >
		<input type="hidden" name="URL_NO" value="'.$callbackurl.'&status=0&" >
		<input type="hidden" name="SIGN" value="'.$sign.'" >
		<input type="hidden" name="LANG" value="RU" >
	  </form>';
					
?>

