<?php
/**
 * @package	VM payment module for Joomla!
 * @version	1.0.0
 * @author	itmosfera.ru
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
require('../../../configuration.php');

$SIGN = $_POST['SIGN'];
$STATE = $_POST['STATE'];
$ORDER_ID = $_POST['ORDER_ID'];
$SHOP_ID = $_POST['SHOP_ID'];
if (isset($SIGN)) {
				$sign = strtoupper(md5(md5($SHOP_ID).'&'.md5($ORDER_ID).'&'.md5($STATE)));
				if ($SIGN == $sign) {
					switch ($STATE) {
						case 'paid':
						$conf = new JConfig; 
						$db_conn = new mysqli($conf->host, $conf->user, $conf->password, $conf->db);
							if (mysqli_connect_errno()) {
							printf("Ошибка доступа к БД: %s\n", mysqli_connect_error());
						exit();
						}
						$db_conn->query('UPDATE '.$conf->dbprefix.'virtuemart_orders SET order_status="C" WHERE order_number="'.$ORDER_ID.'"' );					
	 					  break;
					  }
			    }
			}  
?>