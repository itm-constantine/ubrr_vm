<?php
require('../../../configuration.php');

if (isset($_POST['SIGN'])) {
				$sign = strtoupper(md5(md5($_POST['SHOP_ID']).'&'.md5($_POST["ORDER_ID"]).'&'.md5($_POST['STATE'])));
				if ($_POST['SIGN'] == $sign) {
					switch ($_POST['STATE']) {
						case 'paid':
						$conf = new JConfig; 
						$db_conn = new mysqli($conf->host, $conf->user, $conf->password, $conf->db);
							if (mysqli_connect_errno()) {
							printf("Ошибка доступа к БД: %s\n", mysqli_connect_error());
						exit();
						}
						$db_conn->query('UPDATE '.$conf->dbprefix.'virtuemart_orders SET order_status="C" WHERE order_number="'.$_POST["ORDER_ID"].'"' );					
	 					  break;
					  }
			    }
			}  
?>