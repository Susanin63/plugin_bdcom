<?php

 /* Start Initialization Section */

 $dir = dirname(__FILE__);
 
 
 if (strpos($dir, 'bdcom') !== false) {
 	chdir('../../');
 }

include_once('./include/global_for_ajax.php');

 
if (file_exists('./include/global.php')) {
	include_once('./lib/functions.php');
	include_once('./lib/database.php');
} 

include_once("./lib/html_validate.php");
include_once("./lib/html_utility.php");




	
	//echo "type=" . get_request_var_post("type") . "\n";
	//echo "POST=" . print_r($_REQUEST,true);

	input_validate_input_regex(get_request_var_post("type"),"^(iping|start_html_ping|get_info)$");
	$t = get_request_var_post("type");
	//echo "type=" . $t . "\n";
	
	if (isset($t)){
		switch ($t) {
			case "iping":
				if (filter_var(get_request_var_post("ip"), FILTER_VALIDATE_IP)){
					iping(filter_var(get_request_var_post("ip"), FILTER_VALIDATE_IP));
				}
			break;
			case "get_info":
				if (filter_var(get_request_var_post("ip"), FILTER_VALIDATE_IP)){
					get_info(filter_var(get_request_var_post("ip"), FILTER_VALIDATE_IP));
				}
			break;			
			case "start_html_ping":
				start_html_ping();
			break;
			default:
				echo $t;
		};
	};

function start_html_ping(){
	echo "
	<table>
		<tr>
			<div id='a_info'></div>
		</tr>
		<tr>
			<div id='i_ping'></div>
		</tr>
	</table>";
}
function start_html_graph(){
	echo "
	<table>
		<td>
			<div id='a_info'></div>
			<div style='min-height: 200px;'><a href='/graph.php?action=view&amp;rra_id=all&amp;local_graph_id=914'><img class='graphimage' id='graph_914' src='/graph_image.php?local_graph_id=914&amp;rra_id=0&amp;graph_height=150&amp;graph_width=470&amp;graph_nolegend=true&amp;title_font_size=10&amp;graph_start=1522851232&amp;graph_end=1522937632' border='0' alt=' LB Traffic Per IP [172.20.12.101] Reverse Account v3 '></a></div>
			<div style='min-height: 200px;'><a href='/graph.php?action=view&amp;rra_id=all&amp;local_graph_id=914'><img class='graphimage' id='graph_914' src='/graph_image.php?local_graph_id=914&amp;rra_id=0&amp;graph_height=150&amp;graph_width=470&amp;graph_nolegend=true&amp;title_font_size=10&amp;graph_start=1522851232&amp;graph_end=1522937632' border='0' alt=' LB Traffic Per IP [172.20.12.101] Reverse Account v3 '></a></div>
		</td>
		<td>
			<div id='i_ping'></div>
		</td>
	</table>";
}
function iping($ip){	
	
    //echo "<p>[" . date("H:i:s") . "] Hello  $name<p>";
	
	//$result = exec('ping -c 1 -s 250 ' . $name, $outcome, $status);
	$result = shell_exec("ping -W 1 -c 1 -s 200 " . $ip);

		$position = strpos($result, "min/avg/max");

		if ($position > 0) {
			$output  = trim(str_replace(" ms", "", substr($result, $position)));
			$pieces  = explode("=", $output);
			$results = explode("/", $pieces[1]);

			$ping_status = $results[1];
			$ping_response = "ICMP Ping Success (" . $results[1] . " ms)";
			//$status = "[" . date('H:i:s') . "] > " . preg_replace("/Timed out/", "<span style='background-color: #f51d30;'>$0</span>", $ping->ping_response);
			$status = "[" . date('H:i:s') . "] > " . preg_replace("/Success/", "<span style='background-color: #96E78A;'>$0</span>", $ping_response);
		}else{
			$status = "down";
			$ping_response = "ICMP ping Timed out";
			$status = "[" . date('H:i:s') . "] > " . preg_replace("/Timed out/", "<span style='background-color: #f51d30;'>$0</span>", $ping_response);
		}
	echo "<p>" . $status . "</p>";
}
function get_info($ip){	
	
    $rez = "";
	//echo "<p>[" . date("H:i:s") . "] Hello  $name<p>";
	
	$query_string = " SELECT  If (lbs.segment is null, if(host.hostname is null,'ip_noo','ip_cacti'),CONCAT('ipb_',lbs.blocked)) as sig, " .
		" If (lbs.segment is null, if(host.hostname is null,'IP нигде не зарегистрирован','Служебный IP'), concat ('[', lbs.ag_num , '], ' , CASE lbs.blocked  WHEN 0 THEN CONCAT('Баланс = ',ROUND(lbs.balance,2))  WHEN 1 THEN CONCAT('Минусовой баланс = ',ROUND(lbs.balance/100,2), ' c ', date(lbs.block_date))  WHEN 2 THEN CONCAT('Блок пользователя c ', date(lbs.acc_ondate))  WHEN 3 THEN CONCAT('Админ Блок c ', date(lbs.acc_ondate)) END )) as sig2,  " .
		" f_addr, h.id as cid,   lbs.f_flat, lbs.equipm_rtr,   " .
		" if(gl_ip.id is null,'0',gl_ip.id) as ip_local_graph_id,             lbs.login     " .
		" FROM  plugin_bdcom_onu              " .
		" left JOIN plugin_bdcom_devices             ON plugin_bdcom_onu.device_id = plugin_bdcom_devices.device_id              " .
		" LEFT JOIN (SELECT l.segment,  v.*  FROM lb_staff l left JOIN lb_vgroups_s v ON l.vg_id = v.vg_id WHERE v.`archive`=0) lbs ON INET_ATON(plugin_bdcom_onu.onu_ipaddr) = lbs.segment    " .
		" left JOIN host             ON plugin_bdcom_onu.onu_ipaddr = host.hostname      " .
		" LEFT JOIN graph_local gl_ip ON gl_ip.snmp_index=inet_aton(plugin_bdcom_onu.onu_ipaddr) and gl_ip.graph_template_id=43    " .
		" left JOIN host   h          ON plugin_bdcom_devices.hostname = h.hostname      " .
		" WHERE  plugin_bdcom_onu.onu_ipaddr='" . $ip . "'    LIMIT 10 ";
	
	
	$arr = db_fetch_assoc($query_string);
	//echo "row=" . print_r($query_string, true);
	if (is_array($arr) and sizeof($arr) > 0) {
		$rez = "";
		foreach ($arr as $row) {
			$rez = $rez . "<p>Login [" . $row["login"] . "] ";
			$rez = $rez . " BALAN [" . $row["sig2"] . "]</p>";
			$rez = $rez . "<p>ADDR1: [" . str_replace(",,,446442","",str_replace("пгт Усть-Кинельский,","СХИ ",str_replace("Россия,обл Самарская,,г Кинель,","",$row["f_addr"]))) . "]</p>";
			$rez = $rez . "<p>EQUIP RTR [" . $row["equipm_rtr"] . "]</p>";
			
		}
		
	}else{
		$rez =  "<span style='background-color: #f51d30;'>NO_DATA</span>";
	}

	echo $rez;
}
?>