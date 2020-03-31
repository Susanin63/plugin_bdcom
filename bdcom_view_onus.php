<?php
 /*
  +-------------------------------------------------------------------------+
  | Copyright (C) 2007 Susanin                                          |
  |                                                                         |
  | This program is free software; you can redistribute it and/or           |
  | modify it under the terms of the GNU General Public License             |
  | as published by the Free Software Foundation; either version 2          |
  | of the License, or (at your option) any later version.                  |
  |                                                                         |
  | This program is distributed in the hope that it will be useful,         |
  | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
  | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
  | GNU General Public License for more details.                            |
  +-------------------------------------------------------------------------+
 */
 
 $guest_account = true;
 
 chdir('../../');
 include("./include/auth.php");
 
 include_once($config['base_path'] . "/plugins/bdcom/lib/bdcom_functions.php");
  
 //***********************************************************

 $onu_actions = array(
 	1 => __('Delete'),
	2 => __('Change ONU Name'),
	3 => __('View MAC on ONU'),
	4 => __('Port Status on ONU'),
	5 => __('Set Signal Power'),
	6 => __('Update to P1501D1_26B_554.tar'),
	7 => __('Update to P1501D1_between.tar'),
	8 => __('Commit Firmware'),
	9 => __('Сообщить при старте'),
	10 => __('Reboot ONU'),
	12 => __('SMS')
 	);	

$title = __('BDCOM - ONU Report View', 'bdcom');

/* check actions */
switch (get_request_var('action')) {
	case 'actions':
		form_actions_onus();

		break;
	case 'onu_query':
		onu_query();
		break;
	case 'onu_query_firm':
		onu_query_firm();
		break;		
	case 'onu_query_ajax':
		onu_query_ajax();
		break;			
	default:
		bdcom_redirect();
		general_header();
		bdcom_view_onus();
		bottom_footer();
		break;
}


 function onu_query() {
 	/* ================= input validation ================= */
 	input_validate_input_number(get_request_var("onu_id"));
 	/* ==================================================== */
 
 	update_onu_power(get_request_var("onu_id"));
	header("Location: bdcom_view_onus.php");
 } 

 function onu_query_firm() {
 	/* ================= input validation ================= */
 	input_validate_input_number(get_request_var("onu_id"));
 	/* ==================================================== */
 
 	update_onu_firm(get_request_var("onu_id"));
 }
 
  function onu_query_ajax() {
 	/* ================= input validation ================= */
 	input_validate_input_number(get_request_var("onu_id"));
 	/* ==================================================== */
 
 	print json_encode(array("pow"=>bdcom_color_power_cell(update_onu_power(get_request_var("onu_id")))));
	//header("Location: bdcom_view_onus.php");
 } 
 
function bdcom_view_get_onu_records(&$sql_where, $rows = '30', $apply_limits = TRUE) {

	/* form the 'where' clause for our main sql query */
	if (get_request_var('filter') != '') {
        $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " (plugin_bdcom_onu.onu_macaddr LIKE '%" . get_request_var('filter') . "%' OR " .
                "plugin_bdcom_onu.onu_name LIKE '%" . get_request_var('filter') . "%' OR " .
				"plugin_bdcom_onu.onu_descr LIKE '%" . get_request_var('filter') . "%' OR " .
				"plugin_bdcom_onu.onu_agrement LIKE '%" . get_request_var('filter') . "%')";		
	}

	
		switch (get_request_var('mac_filter_type_id')) {
			 case "1": /* do not filter */
				 break;
			 case "2": /* matches */
				 $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " plugin_bdcom_onu.onu_macaddr='" . get_request_var('mac_filter') . "'";
				 break;
			 case "3": /* contains */
				 $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " plugin_bdcom_onu.onu_macaddr LIKE '%%" . get_request_var('mac_filter') . "%%'";
				 break;
			 case "4": /* begins with */
				 $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " plugin_bdcom_onu.onu_macaddr LIKE '" . get_request_var('mac_filter') . "%%'";
				 break;
			 case "5": /* does not contain */
				 $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " plugin_bdcom_onu.onu_macaddr NOT LIKE '" . get_request_var('mac_filter') . "%%'";
				 break;
			 case "6": /* does not begin with */
				 $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " plugin_bdcom_onu.onu_macaddr NOT LIKE '" . get_request_var('mac_filter') . "%%'";
		}

        switch (get_request_var('ip_filter_type_id')) {
             case "1": // do not filter 
                 break;
             case "2": // matches 
                  $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " plugin_bdcom_onu.onu_ipaddr='" . get_request_var('ip_filter') . "'";
                 break;
             case "3": // contains 
                  $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " plugin_bdcom_onu.onu_ipaddr LIKE '%%" . get_request_var('ip_filter') . "%%'";
                 break;
             case "4": // begins with 
                  $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " plugin_bdcom_onu.onu_ipaddr LIKE '" . get_request_var('ip_filter') . "%%'";
                 break;
             case "5": // does not contain 
                  $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " plugin_bdcom_onu.onu_ipaddr NOT LIKE '" . get_request_var('ip_filter') . "%%'";
                 break;
             case "6": // does not begin with 
                  $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " plugin_bdcom_onu.onu_ipaddr NOT LIKE '" . get_request_var('ip_filter') . "%%'";
                 break;
             case "7": // is null 
                  $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " plugin_bdcom_onu.onu_ipaddr = ''";
                 break;
             case "8": // is not null 
                  $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " plugin_bdcom_onu.onu_ipaddr != ''";
        }
	
        
		if (!(get_request_var('epon_id') == "-1")) {
			 
			 $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " plugin_bdcom_onu.epon_id=" . get_request_var('epon_id');
		}		

		if (!(get_request_var('uzel_id') == "-1")) {
			 
			 $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " plugin_bdcom_onu.onu_us_enduzelid=" . get_request_var('uzel_id');
		}
		
        switch (get_request_var('sost')) {
             case "-1": /* do not filter */
				break;
             case "0": /* положительный баланс */
 				$sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') .  " lbv.blocked = 0 ";
                 break;
             case "1": /* отрицательный баланс */
 				$sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') .  " lbv.blocked = 1 ";
				break;
             case "3": /* заблокирован */
 				$sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') .  " lbv.blocked = 3 ";
				break;
             case "4": /* несуществ */
 				$sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') .  " lbs.segment is null and h.hostname is null ";
				break;					
             case "5": /* служебн */
 				$sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') .  " lbs.segment is null and h.hostname is not null ";
				break; 
             case "6": /* оборуд по акции */
 				$sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') .  " lbs.equipm_rtr is not null ";
				break;				
        }		

		switch (get_request_var('status')) {
             case "-1": /* do not filter */
				break;
             case "1": /* UP */
 				$sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') .  " `plugin_bdcom_onu`.`onu_operstatus` = 1 ";
                 break;
             case "2": /* DOWN */
 				$sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') .  " `plugin_bdcom_onu`.`onu_operstatus` = 2 ";
				break;
             case "3": /* old firmware */
 				$sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') .  " `plugin_bdcom_onu`.`onu_version` = '6014' and `onu_soft_version` <> '10.0.26B.554' ";
				break;
				
        }		

		switch (get_request_var('firm')) {
             case "-1": /* do not filter */
				break;
             case "1": /* UP */
				$sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') .  " `plugin_bdcom_onu`.`onu_version` = '6014' and `onu_soft_version` like '%.22B.%' ";
                 break;
             case "2": /* DOWN */
 				$sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') .  " `plugin_bdcom_onu`.`onu_version` = '6014' and `onu_soft_version` like '%.26B.%' and `onu_soft_version` not like '%.26B.554%' and `onu_soft_version` not like '%.26B.2001%' ";
				break;
             case "3": /* old firmware */
 				$sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') .  " `plugin_bdcom_onu`.`onu_version` = '6014' and `onu_soft_version` = '10.0.26B.2001' ";
				break;
             case "4": /* old firmware */
 				$sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') .  " `plugin_bdcom_onu`.`onu_version` = '6014' and `onu_soft_version` like '%.26B.554%' ";
				break;				
        }
		
    if (!(get_request_var('device_id') == "-1")) {
         
		 $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " plugin_bdcom_onu.device_id=" . get_request_var('device_id');
    }
	
	
 	$sortby = get_request_var('sort_column');
 	if ($sortby=="onu_index") {
 		$sortby = " LENGTH(onu_name), onu_name) ";
 	}elseif($sortby=="f_flat") {
 		$sortby = "ABS(f_flat)";
 	}elseif($sortby=="onu_name") {
 		$sortby = " plugin_bdcom_epons.epon_name, LENGTH(onu_name), onu_name ";
	}elseif($sortby=="onu_uzel") {
 		$sortby = " plugin_bdcom_epons.epon_name, onu_us_enduzelid, onu_ipaddr ";
	}
	
	if ($apply_limits) {
		$sql_limit = ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ', ' . $rows;
	}else{
		$sql_limit = '';
	}
	
 		$query_string = "SELECT 
					if (lbv.blocked is null, 'ip_noo' ,CONCAT('ipb_',lbv.blocked)) as sig,  If (lbs.segment is null, 'IP нигде не зарегистрирован', concat ('[', lbv.ag_num , '], ' , 
					CASE lbv.blocked  
					WHEN 0 THEN CONCAT('Баланс = ',ROUND(lbv.balance,2))  
					WHEN 1 THEN CONCAT('Минусовой баланс = ',ROUND(lbv.balance/100,2), ' c ', date(lbv.block_date))  
					WHEN 2 THEN CONCAT('Блок пользователя c ', date(lbv.acc_ondate))  
					WHEN 3 THEN CONCAT('Админ Блок c ', date(lbv.acc_ondate)) END )) as sig2,  
					f_addr, h.id as cid,   plugin_bdcom_devices.description, plugin_bdcom_devices.hostname, 
					plugin_bdcom_devices.last_rundate,            
					plugin_bdcom_onu.device_id, plugin_bdcom_onu.onu_id, plugin_bdcom_onu.onu_txpower, plugin_bdcom_onu.onu_rxpower, plugin_bdcom_onu.onu_distance, if (onu_done_view_count > 8 , '' , plugin_bdcom_onu.onu_done_reason) as onu_done_reason,
					plugin_bdcom_onu.onu_macaddr, plugin_bdcom_onu.onu_ipaddr, plugin_bdcom_onu.onu_name, plugin_bdcom_onu.onu_descr, plugin_bdcom_onu.onu_operstatus, plugin_bdcom_onu.onu_adminstatus, plugin_bdcom_onu.onu_dereg_status, 
					onu_done_view_count, onu_online, onu_first_scan_date, onu_lastchange_date, plugin_bdcom_onu.onu_scan_date,plugin_bdcom_onu.onu_rxpower_change, plugin_bdcom_onu.onu_rxpower_average, plugin_bdcom_onu.onu_version, plugin_bdcom_onu.onu_soft_version, onu_us_enduzelid, onu_us_enduzel_descr, onu_us_onuid,
					lbv.f_flat, lbv.equipm_rtr,  if(gl_ip.id is null,if(gl_ping.id is null,'0',gl_ping.id),gl_ip.id) as ip_local_graph_id,             
					lbv.login, plugin_bdcom_epons.epon_name, plugin_bdcom_epons.epon_index,
					h.id
				FROM  plugin_bdcom_onu             
				left JOIN plugin_bdcom_epons             ON plugin_bdcom_onu.onu_bindepon = plugin_bdcom_epons.epon_index and   plugin_bdcom_onu.device_id = plugin_bdcom_epons.device_id   
				left JOIN plugin_bdcom_devices             ON plugin_bdcom_onu.device_id = plugin_bdcom_devices.device_id    
				left JOIN (SELECT * FROM lb_vgroups_s WHERE lb_vgroups_s.id is null or lb_vgroups_s.id =1)  lbv  ON plugin_bdcom_onu.onu_agrm_id = lbv.agrm_id	
				LEFT JOIN (SELECT * FROM lb_staff group by vg_id) lbs ON  lbv.vg_id= lbs.vg_id      
				LEFT JOIN graph_local gl_ip ON gl_ip.snmp_index=inet_aton(plugin_bdcom_onu.onu_ipaddr) and gl_ip.graph_template_id=43
			LEFT JOIN plugin_fping fp ON (plugin_bdcom_onu.onu_ipaddr=fp.host)   
			LEFT JOIN graph_local gl_ping ON gl_ping.snmp_index=fp.id and gl_ping.graph_template_id=82					
				left JOIN host   h          ON plugin_bdcom_devices.hostname = h.hostname  		
 			$sql_where
 			ORDER BY " . $sortby . " " . get_request_var('sort_direction') . " " .
			$sql_limit;
 			
        return db_fetch_assoc($query_string);	
	
	
}
 
 function bdcom_onu_request_validation() {
	/* ================= input validation and session storage ================= */
	$filters = array(
		'rows' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
			),
		'page' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '1'
			),
		'filter' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'order_id',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'ASC',
			'options' => array('options' => 'sanitize_search_string')
			),
		'status' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '-1',
			'pageset' => true
			),
		'device_type_id' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '-1',
			'pageset' => true
			),
		'device_id' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '-1',
			'pageset' => true
			),
		'mac_filter_type_id' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '1',
			'pageset' => true
			),			
		'mac_filter' => array(
			'filter' => FILTER_VALIDATE_MAC,
			'default' => '',
			'pageset' => true
			),
		'ip_filter_type_id' => array(
			'filter' => FILTER_SANITIZE_STRING,
			'default' => '1',
			'pageset' => true
			),			
		'ip_filter' => array(
			'filter' => FILTER_SANITIZE_STRING,
			'default' => '',
			'pageset' => true
			),
		'epon_id' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '-1',
			'pageset' => true
			),
		'firm' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '-1',
			'pageset' => true
			),	
		'onu_id' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '-1',
			'pageset' => true
			),
		'uzel_id' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '-1',
			'pageset' => true
			),				
		'sost' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '-1',
			'pageset' => true
			),	
	);

	validate_store_request_vars($filters, 'sess_bdcomv_onus');
	/* ================= input validation ================= */
	
	
 }

 
 function bdcom_view_onus() {
    global $title, $report, $colors, $rows_selector, $config, $onu_actions;
 
 	print "<div id='element_to_pop_ping'>
			<a class='b-close'>x<a/>
			Ping Host
		  </div>
		";
		
		
	bdcom_onu_request_validation();

	$only_1_dev	 = false;
	$only_1_epon = false;
	
	if (get_request_var('rows') == -1) {
		$rows = read_config_option('num_rows_table');
	} elseif (get_request_var('rows') == -2) {
		$rows = 999999;
	} else {
		$rows = get_request_var('rows');
	}
	
	$webroot = $config['url_path'] . 'plugins/bdcom/';
	bdcom_tabs();

	html_start_box($title, '100%', '', '3', 'center', 'bdcom_view_onus.php?action=edit&status=' . get_request_var('status'));
	bdcom_onu_filter();
	html_end_box(); 
	//bdcom_group_tabs();


	$sql_where = '';
	$onus = array();
	$onus_devices = array();

    $onus = bdcom_view_get_onu_records($sql_where, $rows);
 	//$devices_sum = bdcom_view_get_onu_records($sql_where);
	$onus_devices = array_column($onus, 'device_id');
	$onus_devices_cnt = array_count_values($onus_devices);	
 	
    $total_rows = db_fetch_cell("SELECT
        COUNT(plugin_bdcom_onu.device_id)
        FROM plugin_bdcom_onu
		LEFT JOIN (SELECT l.segment,  v.*  FROM lb_staff l left JOIN lb_vgroups_s v ON l.vg_id = v.vg_id WHERE v.`archive`=0) lbs ON INET_ATON(plugin_bdcom_onu.onu_ipaddr) = lbs.segment 
		left JOIN (SELECT * FROM lb_vgroups_s WHERE lb_vgroups_s.id is null or lb_vgroups_s.id =1)  lbv  ON plugin_bdcom_onu.onu_agrm_id = lbv.agrm_id	
        $sql_where");
 
	 if (is_array($onus_devices_cnt) and count($onus_devices_cnt) == 1) {
		//if only one device - skip hostname and description 
		$only_1_dev= true;
		$onus_epons = array_column($onus, 'epon_index', 'epon_name');
		if (is_array($onus_epons) and count($onus_epons) == 1) {
			$only_1_epon = true;
		}
		$dev_info = "<tr bgcolor='#FFA07A'>
                 <td colspan='16'>
                     <table width='100%' cellspacing='0' cellpadding='0' border='0'>
                         <tr>
                             <td align='left' class='textHeaderDark'>
								 <a class='linkEditMain' href='bdcom_devices.php?action=edit&amp;device_id=" . $onus['0']["device_id"] . "'>" . $onus['0']["description"] . " (" . $onus['0']['hostname']  . ")</a>
                             </td>\n";
						
				    $epons_query_string = "SELECT ep.*, h.id FROM plugin_bdcom_epons  ep
											LEFT JOIN plugin_bdcom_devices on ep.device_id=plugin_bdcom_devices.device_id   
											LEFT JOIN host h ON plugin_bdcom_devices.hostname = h.hostname
											WHERE ep.device_id='" . $onus['0']['device_id'] . "';" ;
					$epons = db_fetch_assoc($epons_query_string);
					foreach ($epons as $epon) {
						$dev_info .= " <td align='center' > ";
							if (isset($onus_epons[$epon['epon_name']])) {

								//https://sys.ion63.ru/plugins/bdcom/bdcom_view_onus.php?report=onus&device_type_id=-1&device_id=+3&epon_id=&status=-1&status=-1&filter=&page=1
							 //$dev_info .= " <a class='linkEditMain' href='bdcom_view_onus.php?report=onus&    device_type_id=-1&device_id=" . $onus['0']['device_id'] . "&epon_id=" .  $epon['epon_id'] . "&status=-1&filter=&page=1>" . $epon['epon_name']  . "</a>" ;
								$dev_info .= "<a class='linkEditMain' href='bdcom_view_onus.php?report=onus&amp;o_device_type_id=-1&device_id=" . $onus['0']["device_id"] . "&epon_id=" .  $epon['epon_id'] . "&ip_filter_type_id=1&ip_filter=&status=-1&filter=&uzel_id=-1&page=1'>" . "<span style='background-color: #F8D93D;'>" . $epon['epon_name'] . "<br>"  . " (" . $epon['epon_descr']  . ")</span></a>";
							}else{
								$dev_info .= "<a class='linkEditMain' href='bdcom_view_onus.php?report=onus&amp;o_device_type_id=-1&device_id=" . $onus['0']["device_id"] . "&epon_id=" .  $epon['epon_id'] . "&ip_filter_type_id=1&ip_filter=&status=-1&filter=&uzel_id=-1&page=1'>" . $epon['epon_name'] . "<br>(" . $epon['epon_descr']  . ")</a>";
							}
						$dev_info .= " <a class='linkEditMain' href='". htmlspecialchars($config['url_path'] . "graph_ion_view.php?action=preview&host_id=" . $epon['id'] . "&snmp_index=&rfilter=" . $epon['epon_name'] . "$") . "'><img src='" . $webroot . "images/view_graphs.gif' border='0' alt='' title='View Graph' align='absmiddle'></a>";
																		

							
										//strlen($_REQUEST["o_filter"]) ? preg_replace("/(" . preg_quote($_REQUEST["o_filter"]) . ")/i", "<span style='background-color: #F8D93D;'>\\1</span>", $onu["epon_name"]) : " <a class='linkEditMain' href=bdcom_view_onus.php?report=epons&e_device_id=" . $onu['device_id'] . "&e_port_number=" . $onu["epon_index"]
										 
										// 	form_selectable_cell((strlen($_REQUEST["o_filter"]) ? preg_replace("/(" . preg_quote($_REQUEST["o_filter"]) . ")/i", "<span style='background-color: #F8D93D;'>\\1</span>", $onu["epon_name"]) : " <a class='linkEditMain' href=bdcom_view_onus.php?report=epons&e_device_id=" . $onu['device_id'] . "&e_port_number=" . $onu["epon_index"]) . ">" . $onu["epon_name"] . 
											//" <a class='linkEditMain' href='". htmlspecialchars($config['url_path'] . "graph_ip_view.php?action=preview&host_id=" . $onu['id'] . "&snmp_index=" . $onu["epon_index"] . "&filter=") . "'><img src='" . $webroot . "images/view_graphs.gif' border='0' alt='' title='View Graph' align='absmiddle'></a>", $onu["onu_id"] );
										 
										 
						$dev_info .= "</td>\n";	
					}
	

						 
        $dev_info .=     "</tr>
                     </table>
                 </td>
			</tr>\n
			<tr>\n
				<td colspan='16'>
				</td>
			</tr>\n";
		print $dev_info;
		
		//select us onu id 
		$us_onu_list = bdcom_array_rekey(db_fetch_assoc("SELECT onu_us_enduzelid, GROUP_CONCAT(onu_us_onuid SEPARATOR ',') as lst FROM plugin_bdcom_onu WHERE device_id ='" . $onus['0']['device_id'] . "' group by onu_us_enduzelid;"), 'onu_us_enduzelid');
	}
	 
	  

  	$display_text = array(
		"onu_ipaddr" => array(__('Abon<br>IP Address', 'bdcom'), "ASC"),
		"onu_done_reason" => array('', "ASC"),
		"onu_macaddr" => array(__('ONU<br>MAC Address', 'bdcom'), "ASC"),
		"onu_vers" => array(__('Firm', 'bdcom'), "ASC"),
		"onu_name" => array(__('ONU<br>NAME', 'bdcom'), "ASC"),
		"onu_uzel" => array(__('US uzel', 'bdcom'), "ASC"),
		"onu_descr" => array(__('ONU<br>Descr', 'bdcom'), "ASC"),
		"f_flat" => array(__('Komn', 'bdcom'), "DESC"));
		if (!$only_1_epon) {
			$display_text=$display_text+array("epon" => array(__('epon<br>name', 'bdcom'), "DESC"));
		}		
		$display_text=$display_text+array(
		"dist" => array(__('dist', 'bdcom'), "DESC"),
		"  " => array(__(' ', 'bdcom'), "DESC"),
		"power" => array(__('power', 'bdcom'), "DESC"),
		"status" => array(__('ONU<br>status', 'bdcom'), "ASC"),
		"onu_lastchange_date" => array(__('Дата<br>Изменения', 'bdcom'), "ASC"),
		"onu_scan_date" => array(__('Last<br>Scan Date', 'bdcom'), "DESC"),
		" " => array(' ','DESC'));
		
	if (!$only_1_dev) {
		$display_text = array("hostname" => array(__('Network<br>Hostname', 'bdcom'), "ASC"),"description" => array(__('Network<br>Device', 'bdcom'), "ASC")) + $display_text;
	}	
	
	$columns = sizeof($display_text) + 1;  
	
	$nav = html_nav_bar('bdcom_view_onus.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, $columns, __('ONUs', 'bdcom'), 'page', 'main');
	
	form_start('bdcom_view_onus.php', 'chk');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort_checkbox($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);

	
	if (cacti_sizeof($onus)) {
		$old_uzelid='';
		foreach ($onus as $onu) {
			form_alternate_row('line' . $onu['device_id'], true);
			if ($old_uzelid != $onu["onu_us_enduzelid"]) {
				//print "";
				print "<tr bgcolor='#DEB887'>
                 <td align='center' colspan='16'>
					<a class='linkEditMain'  href='https://sys.ion63.ru/plugins/bdcom/bdcom_view_onus.php?report=onus&device_id=-1&rows_selector=-1&mac_filter_type_id=1&mac_filter=&filter=&ip_filter_type_id=1&ip_filter=&epon_id=-1&status=-1&sost=-1&firm=-1&uzel_id=" . $onu["onu_us_enduzelid"] . "'> [(" . $onu["onu_us_enduzelid"] . ") " . $onu["onu_us_enduzel_descr"] . "] " .  
					"<a class='linkEditMain'  href='https://us.ion63.ru/oper/index.php?core_section=node&action=show&id=" . $onu["onu_us_enduzelid"] . "'>  открыть в US " ;
					
					if (isset($us_onu_list[$onu["onu_us_enduzelid"]])) {
						print "<a class='linkEditMain'  href='http://us.ion63.ru/oper/index.php?core_section=map&action=show&only_custom_load=1&device_list_id=" . $us_onu_list[$onu["onu_us_enduzelid"]]['lst'] . "&is_with_device_info=1&by_device=" . $onu["onu_us_onuid"] . "'>  ONU на карте ";
					}
					
                print  "</td>
				</tr>\n";	
				$old_uzelid = $onu["onu_us_enduzelid"];
			} 			
			bdcom_format_onu_row($onu, false, $only_1_dev, $only_1_epon);

		}
	} else {
		print '<tr><td colspan="' . $columns  . '"><em>' . __('No ONUs', 'bdcom') . '</em></td></tr>';
	}

	html_end_box(false);

	if (cacti_sizeof($onus)) {
		print $nav;
	}

	/* draw the dropdown containing a list of available actions for this form */
	draw_actions_dropdown($onu_actions, 1);

	form_end();		
	
}
 
 


 function bdcom_format_onu_row($onu, $actions=false, $only_1_dev = true, $only_1_epon = true) {
	global $config;
	
	$webroot = $config['url_path'] . 'plugins/bdcom/';

	/* viewer level */
	if ($actions) {
		$row = "<a href='" . htmlspecialchars($config['url_path'] . 'plugins/bdcom/bdcom_interfaces.php?device_id=' . $onu['device_id'] . '&issues=0&page=1') . "'><img src='" . $config['url_path'] . "plugins/bdcom/images/view_interfaces.gif' alt='' title='" . __('View Interfaces', 'bdcom') . "'></a>";

		/* admin level */
		if (api_user_realm_auth('bdcom_devices.php')) {
			if ($device['disabled'] == '') {
				$row .= "<img id='r_" . $onu['device_id'] . "' src='" . $config['url_path'] . "plugins/bdcom/images/rescan_device.gif' alt='' onClick='scan_device(" . $onu['device_id'] . ")' title='" . __('Rescan Device', 'bdcom') . "'>";
			} else {
				$row .= "<img src='" . $config['url_path'] . "plugins/bdcom/images/view_none.gif' alt=''>";
			}
		}

		print "<td style='width:40px;'>" . $row . "</td>";
	}


		$mac_font_size=read_config_option("bdcom_mac_addr_font_size");
		$scan_date = $onu["onu_scan_date"];
		$alt_distance=0;
		$onu_ver_row = $onu["onu_soft_version"] ;
		if ($onu["onu_distance"] > 0) {
			switch ($onu["onu_version"]) {
				 case '151C':
					$alt_distance=$onu["onu_distance"] - 2048;
						if ($onu["onu_soft_version"] = '10.0.17A 1017') {
							$onu_ver_row = "<span style='background-color: #00FF00;'>17A.1017</span>";
						}else{
							$onu_ver_row = "<span style='background-color: #F0E68C;'>" . $onu["onu_soft_version"] . "</span>";
						}					
					break;
				 case '6014':
					$alt_distance=$onu["onu_distance"] - 154;
						switch ($onu["onu_soft_version"]) {
							case '10.0.22B.554':
								$onu_ver_row = "<span style='background-color: #00FF00;'>22B.554</span>";
								break;							
							case '10.0.26B.554':
								$onu_ver_row = "<span style='background-color: #00FF00;'>26B.554</span>";
								break;
							case '10.0.26B.422':
								$onu_ver_row = "<span style='background-color: #F0E68C;'>26B.422</span>";								
								break;
							case '10.0.26B.2001':
								$onu_ver_row = "<span style='background-color: #F0E68C;'>26B.2001</span>";								
								break;	
							case '10.0.22B.501':
								$onu_ver_row = "<span style='background-color: #FFD700;'>22B.501</span>";								
								break;									
						}					
					break;
				 case '101Z':
				 case 'SE1G':
					$alt_distance=$onu["onu_distance"];
					if ($onu["onu_soft_version"] = 'V2.1.2') {
						$onu_ver_row = "<span style='background-color: #00FF00;'>V2.1.2</span>";
					}else{
						$onu_ver_row = "<span style='background-color: #F0E68C;'>" . $onu["onu_soft_version"] . "</span>";
					}
					break;					
				 default:
					$alt_distance=$onu["onu_distance"];
					break; 		 
			}			
		}else{
			$alt_distance=$onu["onu_distance"];
		}
		
		if ($onu["onu_operstatus"] == 2) {
			$alt_distance ="[" . $alt_distance . "]";
		}
		
	
		if (!$only_1_dev) {
			form_selectable_cell("<a class='linkEditMain' href='bdcom_devices.php?action=edit&amp;device_id=" . $onu["device_id"] . "'>" . 
				(strlen(get_request_var('filter')) ? preg_replace("/(" . preg_quote(get_request_var('filter')) . ")/i", "<span style='background-color: #F8D93D;'>\\1</span>", $onu["description"]) : $onu["description"]) . "</a>", $onu["onu_id"]);
			form_selectable_cell($onu["hostname"], $onu["onu_id"] );
		}		
		//IP
		form_selectable_cell("<img src='" . $config['url_path'] . "plugins/bdcom/images/term.png' onClick='show_ping_w(" . '"' . $onu["onu_ipaddr"] . '"' . ")' onMouseOver='style.cursor=" . '"' . "pointer" . '"' . "' align='absmiddle' /img> " . 
							 "<img src='" . $config['url_path'] . "plugins/bdcom/images/" . $onu["sig"] . ".png' TITLE='" . $onu["sig2"] . "' align='absmiddle'><a class='inkEditMain' TITLE='" . $onu["sig2"] . ' Адр:' . $onu["f_addr"] . "' href='bdcom_view_info.php?report=info&amp;device_id=-1&amp;ip_filter_type_id=2&amp;ip_filter=" . $onu["onu_ipaddr"] . "&amp;mac_filter_type_id=1&amp;mac_filter=&amp;port_filter_type_id=&amp;port_filter=&amp;rows_selector=-1&amp;filter=&amp;page=1&amp;report=info&amp;x=23&amp;y=10'>" . 
			 (strlen(get_request_var('ip_filter')) ? preg_replace("/(" . preg_quote(get_request_var('ip_filter')) . ")/i", "<span style='background-color: #F8D93D;'>\\1</span>", $onu["onu_ipaddr"]) : $onu["onu_ipaddr"]) . "</a>" . 
			 ($onu["ip_local_graph_id"]==0 ? '' : " <a class='linkEditMain' href='". htmlspecialchars($config['url_path'] . "graph_ion_view.php?action=preview&host_id=-1&snmp_index=&rfilter=" . $onu['onu_ipaddr']  ) . "'><img src='" . $webroot . "images/view_graphs.gif' border='0' alt='' title='View Graph' align='absmiddle'></a>") . 
			 (strlen($onu["equipm_rtr"])==0 ? '' : ' (R)') , $onu["onu_id"]);
		//onu_done_reason
		form_selectable_cell($onu["onu_done_reason"], $onu["onu_id"] ); 			
		//MAC
		form_selectable_cell("<a class='linkEditMain' href='bdcom_view_info.php?report=info&amp;device_id=-1&amp;ip_filter_type_id=8&amp;ip_filter=&amp;mac_filter_type_id=2&amp;mac_filter=" . $onu["onu_macaddr"] . "&amp;port_filter_type_id=&amp;port_filter=&amp;rows_selector=-1&amp;filter=&amp;page=1&amp;report=info&amp;x=14&amp;y=6'><font size='" . $mac_font_size . "' face='Courier'>" . 
			(strlen(get_request_var('mac_filter')) ? strtoupper(preg_replace("/(" . preg_quote(get_request_var('mac_filter')) . ")/i", "<span style='background-color: #F8D93D;'>\\1</span>", $onu["onu_macaddr"])) : $onu["onu_macaddr"]) . "</font></a>", $onu["onu_id"]);
		//onu_firmware
		form_selectable_cell($onu_ver_row, $onu["onu_id"] ); 		
		//name
		form_selectable_cell((strlen(get_request_var('filter')) ? preg_replace("/(" . preg_quote(get_request_var('filter')) . ")/i", "<span style='background-color: #F8D93D;'>\\1</span>", $onu["onu_name"]) : $onu["onu_name"]) . "</a>" . (false ? '' : " <a class='linkEditMain' href='". htmlspecialchars($config['url_path'] . "graph_ion_view.php?action=preview&host_id=" . $onu['id'] . "&snmp_index=&rfilter=" . $onu["onu_name"] ) . "'><img src='" . $webroot . "images/view_graphs.gif' border='0' alt='' title='View Graph' align='absmiddle'></a>") , $onu["onu_id"]);
		//us uzel
		form_selectable_cell("<a class='linkEditMain' TITLE='" . $onu["onu_us_enduzel_descr"] . "' href='https://us.ion63.ru/oper/uzel.php?type=vols&code=" . $onu["onu_us_enduzelid"] . "'>" . 
 				$onu["onu_us_enduzelid"] . "</a>", $onu["onu_id"]);		
		//descr
		form_selectable_cell(filter_value($onu["onu_descr"], get_request_var('filter')), $onu["onu_id"] );			
		//kvartira
		form_selectable_cell(filter_value($onu["f_flat"], get_request_var('filter')), $onu["onu_id"] );			
		//epon name
		if (!$only_1_epon) {
			form_selectable_cell((strlen(get_request_var('filter')) ? preg_replace("/(" . preg_quote(get_request_var('filter')) . ")/i", "<span style='background-color: #F8D93D;'>\\1</span>", $onu["epon_name"]) : " <a class='linkEditMain' href=bdcom_view_epons.php?report=epons&device_id=" . $onu['device_id'] . "&port_number=" . $onu["epon_index"]) . ">" . $onu["epon_name"] . 
				" <a class='linkEditMain' href='". htmlspecialchars($config['url_path'] . "graph_ion_view.php?action=preview&host_id=" . $onu['id'] .  "&snmp_index=&rfilter=" . $onu['epon_name'] . "$" ) . "'><img src='" . $webroot . "images/view_graphs.gif' border='0' alt='' title='View Graph' align='absmiddle'></a>", $onu["onu_id"] );
		}		
		//distance
		form_selectable_cell($alt_distance, $onu["onu_id"] );			
		//power
		form_selectable_cell(bdcom_color_power_cell($onu), $onu["onu_id"] );			
		//status
		form_selectable_cell(bdcom_convert_status_dereg_2str($onu["onu_operstatus"], $onu["onu_dereg_status"])  . " [" . bdcom_convert_status_2str($onu["onu_adminstatus"]) . "]", $onu["onu_id"] );
					
		form_selectable_cell(bdcom_fromat_datetime($onu["onu_lastchange_date"]), $onu["onu_id"] );
		
		form_selectable_cell(filter_value($onu["onu_scan_date"], get_request_var('filter')), $onu["onu_id"] );			
		
		//form_selectable_cell("<a class='linkEditMain' href='bdcom_view_onus.php?action=onu_query&drp_action=5&onu_id=" . $onu["onu_id"] . "'><img src='../../images/reload_icon_small.gif' alt='Update ONU' border='0' align='absmiddle'></a>",0);
		
		$rescan = "<img id='r_" . $onu["onu_id"] . "' src='" . $config['url_path'] . "plugins/bdcom/images/rescan.gif' alt='' onMouseOver='style.cursor=\"pointer\"' onClick='scan_onu(" . $onu["onu_id"] . ")' title='" . __esc('Rescan ONU', 'bdcom') . "'>";
		print  "<td nowrap style='width:1%;white-space:nowrap;'>" . $rescan . '</td>';
		
		form_checkbox_cell($onu["onu_ipaddr"], $onu["onu_id"]);
		form_end_row();	
	

}




 

 function form_actions_onus() {
     global $colors, $config, $onu_actions;

	$str_ids = "";
	
	/* ================= input validation ================= */
	get_filter_request_var('drp_action');
	/* ==================================================== */

	
     /* if we are to save this form, instead of display it */
     if (isset_request_var('selected_items')) {
        $selected_items = sanitize_unserialize_selected_items(get_nfilter_request_var('selected_items'));
		foreach ($selected_items as $item) {
			 /* ================= input validation ================= */
			 input_validate_input_number($item);
			 /* ==================================================== */			
			$str_ids = $str_ids . "'" . $item . "', ";
		}
		$str_ids = substr($str_ids, 0, strlen($str_ids) -2);
		$device_ids=db_fetch_assoc("SELECT device_id FROM plugin_bdcom_onu where onu_id IN (" . $str_ids . ") group by device_id;");
		$onus=db_fetch_assoc("SELECT * FROM plugin_bdcom_onu where onu_id in (" . $str_ids . ") ;");
		$onus_devices=bdcom_array_rekey(db_fetch_assoc("SELECT `d`.`description` as dev_name, d.*, o.*, dt.* FROM plugin_bdcom_onu o LEFT JOIN plugin_bdcom_devices d on (d.device_id=o.device_id) LEFT JOIN plugin_bdcom_dev_types dt on (d.device_type_id =dt.device_type_id) where onu_id in (" . $str_ids . ") ;"), "device_id");        
		
		
		if (get_request_var('drp_action') == "1") { /* удаление записи  */
			foreach ($onus as $onu) {	
				if (isset($onus_devices[$onu["device_id"]])) {
					bdcom_api_delete_onu($onu, $onus_devices[$onu["device_id"]]);
				}
            }
            //header("Location: bdcom_view_onus.php");
 
        } elseif (get_request_var('drp_action') == "2") { /* изменить запись */
            foreach ($selected_items as $item) {
 				$cur_onu_id = $item;
 				$onus_users[$cur_onu_id]["onu_id"] = $cur_onu_id;
				$onus_users[$cur_onu_id]["onu_descr"] = get_request_var('to_' . $cur_onu_id . '_descr');

 		    }
 	        if (!is_error_message()) {
 				if (sizeof($onus) > 0) {
 					foreach ($onus as $onu) {	
						if ((isset($onus_devices[$onu["device_id"]]))  and (isset($onus_users[$onu["onu_id"]]["onu_descr"]))){
							bdcom_rename_onu($onu, $onus_devices[$onu["device_id"]], $onus_users[$onu["onu_id"]]["onu_descr"]);
						}
 					}
 				}
 			}	
 		} elseif (get_request_var('drp_action') == "3") { /* Просмотр МАК - нет действий */

 		} elseif (get_request_var('drp_action') == "4") { /* изменение */
 			$save_data = array();
 			$save_data["to_ed_pvid"] = form_input_validate(get_request_var('to_ed_pvid'), "to_ed_pvid", "[^0]", false, 3);
			$save_data["to_ed_onu_pvid"] = form_input_validate(get_request_var('to_ed_onu_pvid'), "to_ed_onu_pvid", "[^0]", false, 3);
 			if (!is_error_message()) {
				
				if (sizeof($onus) == 1) {
 					foreach ($onus as $onu) {
						if ($save_data["to_ed_pvid"] <> $save_data["to_ed_onu_pvid"]) {
							bdcom_change_pvid_onu($onu, $onus_devices[$onu["device_id"]], $save_data["to_ed_pvid"]);
						}
					}	
				}
 			}		
 		} elseif (get_request_var('drp_action') == "5") { /* установка уровней */
			db_execute("UPDATE `plugin_bdcom_onu` SET `onu_rxpower_average`=`onu_rxpower`, `onu_rxpower_change`=0 where `onu_id` in (" . $str_ids . ");");
 		} elseif (get_request_var('drp_action') == "6" or get_request_var('drp_action') == "7") { /* обновление  */
			$onu_groups=db_fetch_assoc("SELECT `device_id`, GROUP_CONCAT(DISTINCT onu_index ORDER BY onu_index ASC SEPARATOR ', ') AS diid, GROUP_CONCAT(DISTINCT onu_id ORDER BY onu_id ASC SEPARATOR ', ') AS ids FROM `plugin_bdcom_onu` where onu_id in (" . $str_ids . ") group by `plugin_bdcom_onu`.`device_id` ;");
 				if (sizeof($onu_groups) > 0) {
 					foreach ($onu_groups as $onu_group) {	
						if (isset($onus_devices[$onu_group["device_id"]])){
							bdcom_update_onu($onu_group, $onus_devices[$onu_group["device_id"]], (get_request_var('drp_action') == "6" ? 'P1501D1_26B_554.tar':"ONU_22B_554_img.tar"));
						}
 					}
 				}			
			
			
			
			//db_execute("UPDATE `plugin_bdcom_onu` SET `onu_rxpower_average`=`onu_rxpower`, `onu_rxpower_change`=0 where `onu_id` in (" . $str_ids . ");");
        } elseif (get_request_var('drp_action') == "8") { /* commit прошивки */
			$onu_groups=db_fetch_assoc("SELECT `device_id`, GROUP_CONCAT(DISTINCT onu_index ORDER BY onu_index ASC SEPARATOR ', ') AS diid, GROUP_CONCAT(DISTINCT onu_id ORDER BY onu_id ASC SEPARATOR ', ') AS ids FROM `plugin_bdcom_onu` where onu_id in (" . $str_ids . ") group by `plugin_bdcom_onu`.`device_id` ;");
 				if (sizeof($onu_groups) > 0) {
 					foreach ($onu_groups as $onu_group) {	
						if (isset($onus_devices[$onu_group["device_id"]])){
							//bdcom_update_onu($onu_group, $onus_devices[$onu_group["device_id"]], "P1501D1_26B_554.tar");
							bdcom_commit_onu($onu_group, $onus_devices[$onu_group["device_id"]]);
							
						}
 					}
 				}	
        } elseif (get_request_var('drp_action') == "9") { /* msg on start */

			db_execute("UPDATE `plugin_bdcom_onu` SET `onu_up_action`='msg' where `onu_id` in (" . $str_ids . ") AND `onu_up_action`='';");
 
        } elseif (get_request_var('drp_action') == "10") { /* reboot  */
			
			foreach ($onus as $onu) {
				bdcom_reboot_onu($onu, $onus_devices[$onu["device_id"]]);
			}	

		} elseif (get_request_var('drp_action') == "14") { /* СМС рассылка */

			$macips_mobile_rows=db_fetch_assoc("SELECT mobile, i.macip_ipaddr, CONCAT(ag_num,'  ', REPLACE(f_addr , 'Россия,обл Самарская,,г Кинель,', '')) as addr FROM lb_vgroups_s l " .
				 " LEFT JOIN lb_staff lb ON (lb.vg_id=l.vg_id) " .
				 " LEFT JOIN bdcom_imb_macip i ON (i.macip_ipaddr=lb.ip) " .
				 " WHERE i.macip_id in (" . $str_ids . ");");
			$mobils = "";
			foreach ($macips_mobile_rows as $macips_mobile_row) {
				$mobils = $mobils . " " . $macips_mobile_row["mobile"] . ", ";		
			}		
			
				$_SESSION["ar_ssms_num"] = serialize($mobils);
 
 		}
		header("Location: bdcom_view_onus.php");
        exit;
     }
 
     /* setup some variables */
     $row_list = ""; $i = 0; $row_ids = ""; $post_if_error = ""; $colspan = 2; $str_macs="";
 
     
	if (!isset_request_var('post_error')) { /*Если установлено это значение - значит страница перезагружаеться из-за ошибки при вводе, и данные нужно брать не из POST, а из спец. переменной.*/
	 /* loop through each of the ports selected on the previous page and get more info about them для создания первой страницы типа [Вы действительно хотите ....]*/
     foreach ($_POST as $var => $val) {
         if (preg_match("/^chk_([0-9]+)$/", $var, $matches)) {
             /* ================= input validation ================= */
             input_validate_input_number($matches[1]);
             /* ==================================================== */
 			
			$row_info = db_fetch_row("SELECT d.description, onu_name, onu_ipaddr, onu_macaddr FROM plugin_bdcom_onu  o LEFT JOIN plugin_bdcom_devices d on (d.device_id=o.device_id) where onu_id=" . $matches[1]);
			$row_list .= "<li>" . $row_info["description"] . "      IP:" . $row_info["onu_ipaddr"] . "    MAC:" . $row_info["onu_macaddr"] . "      ID:" . $row_info["onu_name"] . "<br>";
			$row_array[$i] = $matches[1];
			$row_ids = $row_ids . "'" . $matches[1] . "', ";
			$i++;			
		}
	  }
 
    }else{
 		$row_array=unserialize(stripslashes(get_request_var('post_error')));
 		if (isset($row_array) && is_array($row_array) && (count($row_array) > 0)) {
 			foreach ($row_array as $row_id) {
 	            $row_info = db_fetch_row("SELECT d.description, onu_name, onu_ipaddr, onu_macaddr FROM plugin_bdcom_onu  o LEFT JOIN plugin_bdcom_devices d on (d.device_id=o.device_id) where onu_id=" . $row_id);
 				$row_list .= "<li>" . $row_info["description"] . "      IP:" . $row_info["onu_ipaddr"] . "    MAC:" . $row_info["onu_macaddr"] . "      ID:" . $row_info["onu_name"] . "<br>";
 				$row_ids = $row_ids . "'" . $matches[1] . "', ";	
 			}
 		}
 	}
 
    $row_ids = substr($row_ids, 0, strlen($row_ids) -2);
	
 
 	top_header();

	form_start('bdcom_view_onus.php?header=false');

	html_start_box($onu_actions[get_request_var('drp_action')], '60%', '', '3', 'center', '');    
		
	

	if ((!isset($row_array) or (!sizeof($row_array))) && (((isset_request_var('drp_action')) && (get_request_var('drp_action') != "4")) || ((isset_request_var('post_error') && (isset_request_var('drp_action')) && (get_request_var('drp_action') != "4"))))) {
		print "<tr><td class='even'><span class='textError'>" . __('You must select at least one row.') . "</span></td></tr>\n";
		$save_html = "";
	}else{
		
		$save_html = "<input type='submit' value='" . __('Yes') . "' name='save'>";	
		$save_html = "<input type='submit' value='" . __esc('Continue', 'bdcom') . "' name='save'>";
 	
    if (get_request_var('drp_action') == "1") {  /* удаление записей */
			print "<tr>
				<td colspan='2' class='textArea'>
					<p>" . __('Click \'Continue\' to delete the following ONUs.', 'bdcom') . "</p>
					<ul>$row_list</ul>
				</td>
			</tr>";
    }elseif ((get_request_var('drp_action') == "2") ){  /* измениен записей */
			$onus_rows=db_fetch_assoc("SELECT o.*,  d.hostname, d.description" . 
				" FROM plugin_bdcom_onu o LEFT JOIN plugin_bdcom_devices d on (d.device_id=o.device_id) " . 
				" WHERE o.onu_id in (" . $row_ids . ");");
	 
			html_start_box(__('Click \'Continue\' to change the following ONUs.', 'bdcom'), '100%', '', '3', 'center', '');   
			//html_start_box("Для изменения записи IP-MAC-PORT проверьте/измените следующие поля.", "100%", 'true', "", "center", "");			
	 
			html_header(array("","Host<br>Description","Hostname<br>", "ONU name","IP-адресс", "MAC-адресс",  "Описание"));
	 
			$i = 0;
			if (sizeof($onus_rows) > 0) {
				foreach ($onus_rows as $onu_row) {
					$onu_id = $onu_row["onu_id"];
					//form_alternate_row_color($colors["alternate"],$colors["light"],$i); $i++;
						?>
						<td><?php form_hidden_box("to_" . $onu_id . "_onu_id", $onu_id, "form_default_value");?></td>
						<td><?php print $onu_row["description"];?></td>
						<td><?php print $onu_row["hostname"];?></td>
						<td><?php print $onu_row["onu_name"];?></td>
						<td><?php print $onu_row["onu_ipaddr"];?></td>
						<td><?php print $onu_row["onu_macaddr"];?></td>
						<td><?php form_text_box("to_" . $onu_id . "_descr", $onu_row["onu_descr"], "", 17, 15, "text", 1) ;?></td>
					</tr>
					<?php
				}
			}
			$colspan = 7;
			//html_end_box(false);			
			
	}elseif ((get_request_var('drp_action') == "3") ){  /* show mac */		
			
 		if (isset($row_array) && is_array($row_array) && (count($row_array) > 0)) {
			$onus=db_fetch_assoc("SELECT * FROM plugin_bdcom_onu where onu_id in (" . $row_ids . ") ;");
			$onus_devices=bdcom_array_rekey(db_fetch_assoc("SELECT `d`.`description` as dev_name, d.*, o.*, dt.* FROM plugin_bdcom_onu o LEFT JOIN plugin_bdcom_devices d on (d.device_id=o.device_id) LEFT JOIN plugin_bdcom_dev_types dt on (d.device_type_id =dt.device_type_id) where onu_id in (" . $row_ids . ") ;"), "device_id");			
			
			$mac_ar_full=array();
			foreach ($onus as $onu) {
	            if (count($onus) > 1) {
					sleep(1);
				}
				$mac_ar =  xform_standard_indexed_data_oid('.1.3.6.1.4.1.3320.152.1.1.3.' . $onu["onu_index"], $onus_devices[$onu["device_id"]]);
				if (isset($mac_ar) and count($mac_ar) > 0 ){
					foreach ($mac_ar as $key => $mac) {
						$mac = str_replace(' ',':',$mac);
						$mac_ar_full[]=array("onu_name"=>$onu["onu_name"],"onu_ipaddr"=>$onu["onu_ipaddr"],"onu_descr"=>$onu["onu_descr"],"vlan"=>substr($key,0,strpos($key,".")),"mac"=>$mac);
						$str_macs = $str_macs . "'" . substr($mac,0,8) . "', ";
					}
				}
 			}
			$str_macs = substr($str_macs, 0, strlen($str_macs) -2);
			$macs_vend=bdcom_array_rekey(db_fetch_assoc("SELECT * FROM mac_track_oui_database where vendor_mac  in (" . $str_macs . ") ;"),"vendor_mac");
			
 		}

		//html_start_box(__('MACs from ONU.', 'bdcom'), '100%', '', '3', 'center', '');  
	 
		html_header(array("ONU name","ONU IP","Desription<br>", "Vlan", "MAC","Vendor"));
	 
		 $i = 0;
		 if (sizeof($mac_ar_full) > 0) {
			 foreach ($mac_ar_full as $mac_row) {
				//$port_id = $port_row["port_id"];
				 //form_alternate_row_color($colors["alternate"],$colors["light"],$i); $i++;
					 ?>
					<td><?php print $mac_row["onu_name"];?></td>
					<td><?php print $mac_row["onu_ipaddr"];?></td>
					<td><?php print $mac_row["onu_descr"];?></td>
					<td><?php print $mac_row["vlan"];?></td>
					<td><?php print $mac_row["mac"];?></td>
					<td><?php print (isset($macs_vend[substr($mac_row["mac"],0,8)]) ? substr($macs_vend[substr($mac_row["mac"],0,8)]["vendor_name"],0,20): 'NOT in DB');?></td>
				 </tr>
				 <?php
			 }
		 }
		 $colspan = 6;
		 //html_end_box(false);			
			
	}elseif ((get_request_var('drp_action') == "4") ){  /* show port */			
			
 		if (isset($row_array) && is_array($row_array) && (count($row_array) == 1)) {
			//$onus=db_fetch_assoc("SELECT * FROM plugin_bdcom_onu where onu_id in (" . $row_ids . ") ;");
			$onu=db_fetch_assoc("SELECT `d`.`description` as dev_name, d.*, o.*, dt.* FROM plugin_bdcom_onu o LEFT JOIN plugin_bdcom_devices d on (d.device_id=o.device_id) LEFT JOIN plugin_bdcom_dev_types dt on (d.device_type_id =dt.device_type_id) where onu_id in (" . $row_ids . ") ;");			
			// "ONU UNI port administration status." 
			$onu=$onu[0];
			$onu_full["uni_port_adminstate"]["n"]="ETH Port Admin State";
			$onu_full["uni_port_adminstate"]["v"] = bdcom_api_snmp_get('.1.3.6.1.4.1.3320.101.12.1.1.7.' . $onu["onu_index"], $onu);
			$onu_full["uni_port_adminstate"]["d"] = array(0=>"Other",1=>"UP",2=>"Down")[$onu_full["uni_port_adminstate"]["v"]];
			//"ONU UNI port operating status." 
			$onu_full["uni_port_operstate"]["n"] = "ETH Port Oper state";
			$onu_full["uni_port_operstate"]["v"] = bdcom_api_snmp_get('.1.3.6.1.4.1.3320.101.12.1.1.8.' . $onu["onu_index"], $onu);
			$onu_full["uni_port_operstate"]["d"] = array(0=>"Other",1=>"UP",2=>"Down")[$onu_full["uni_port_operstate"]["v"]];
			// "ONU PVID, range is 1 to 4094. Only UNI set is supported." 
			$onu_full["uni_pvid"]["n"] = "PVID";
			$onu_full["uni_pvid"]["v"] = bdcom_api_snmp_get('.1.3.6.1.4.1.3320.101.12.1.1.3.' . $onu["onu_index"], $onu);
			$onu_full["uni_pvid"]["d"] = "";
			// "ONU UNI port VLAN mode." 
			$onu_full["uni_vlanmode"]["n"]="Vlan Mode";
			$onu_full["uni_vlanmode"]["v"] = bdcom_api_snmp_get('.1.3.6.1.4.1.3320.101.12.1.1.18.' . $onu["onu_index"], $onu);
			$onu_full["uni_vlanmode"]["d"] = array(0=>"transparent-mode",1=>"tag-mode", 2=>"translation-mode",3=>"aggregation-mode",4=>"trunk-mode",253=>"stacking-mode")[$onu_full["uni_vlanmode"]["v"]];
			
			
			
			//html_start_box(__('Port settings', 'bdcom'), '100%', '', '3', 'center', '');  
		 
			html_header(array("Name","Value","Description"));
		 
			 $i = 0;
			foreach ($onu_full as $key => $row) {
				//$port_id = $port_row["port_id"];
					 ?>
					<td><?php print $row["n"];?></td>
					<td><?php 
					if ($key == "uni_pvid" && $onu_full["uni_vlanmode"]["v"]==1) {
						 form_text_box("to_ed_pvid", $row["v"], "", 5, 5, "text", 1) ;
						 //form_hidden_box("to_ed_onu_pvid", $row["v"], "form_default_value");
					}else{
						print $row["v"];
					}
					
					?></td>
					<td><?php print $row["d"];?></td>
				 </tr>
				 <?php
			 }
			form_hidden_box("to_ed_onu_pvid", $onu_full["uni_pvid"]["v"], "form_default_value");
			//html_end_box(false);			
			$colspan = 3;
		
		}else{
			print "<tr><td class='even'><span class='textError'>" . __('You must select only one row.') . "</span></td></tr>\n";
			$save_html = "";
		}
		
	}elseif ((get_request_var('drp_action') == "5") ){  /* set base power */			
			print "<tr>
				<td colspan='2' class='textArea'>
					<p>" . __('Click \'Continue\' to set base power of the following ONUs.', 'bdcom') . "</p>
					<ul>$row_list</ul>
				</td>
			</tr>";		
		
	}elseif ((isset_request_var('drp_action')) && (get_request_var('drp_action') == "6" or get_request_var('drp_action') == "7"))  {  /* update firmware */			
			print "<tr>
				<td colspan='2' class='textArea'>
					<p>" . __('Click \'Continue\' to Update ONU Firmware to P1501D1_26B_554.tar', 'bdcom') . "</p>
					<ul>$row_list</ul>
				</td>
			</tr>";		
	}elseif ((isset_request_var('drp_action')) && (get_request_var('drp_action') == "8"))  {  /* commit firmware */
			print "<tr>
				<td colspan='2' class='textArea'>
					<p>" . __('Click \'Continue\' to commit firmware', 'bdcom') . "</p>
					<ul>$row_list</ul>
				</td>
			</tr>";	
	}elseif ((isset_request_var('drp_action')) && (get_request_var('drp_action') == "9"))  {  /* msg on UP */
			print "<tr>
				<td colspan='2' class='textArea'>
					<p>" . __('Click \'Continue\' send message when ONU is UP', 'bdcom') . "</p>
					<ul>$row_list</ul>
				</td>
			</tr>";	
	}elseif ((isset_request_var('drp_action')) && (get_request_var('drp_action') == "10"))  {  /* reboot ONU */
			print "<tr>
				<td colspan='2' class='textArea'>
					<p>" . __('Click \'Continue\' reboot ONU', 'bdcom') . "</p>
					<ul>$row_list</ul>
				</td>
			</tr>";				
    }elseif ((isset_request_var('drp_action')) && (get_request_var('drp_action') == "12"))  {  /* СМС Рассылка */
 		$macips_mobile_rows=db_fetch_assoc("SELECT mobile, i.macip_ipaddr, CONCAT(ag_num,'  ', REPLACE(f_addr, 'Россия,обл Самарская,,г Кинель,', '')) as addr FROM lb_vgroups_s l " .
 			 " LEFT JOIN lb_staff lb ON (lb.vg_id=l.vg_id) " .
 			 " LEFT JOIN imb_macip i ON (i.macip_ipaddr=lb.ip) " .
 			 " WHERE i.macip_id in (" . $row_ids . ");");
		$row_list = "";
		$mobils = "";
		foreach ($macips_mobile_rows as $macips_mobile_row) {
			//$row_info = db_fetch_row("SELECT imb_macip.*, imb_devices.hostname, imb_devices.description FROM imb_macip left join imb_devices on (imb_macip.device_id = imb_devices.device_id) WHERE imb_macip.macip_id=" . $row_id);
			$row_list .= "<li>IP:" . $macips_mobile_row["macip_ipaddr"] . "      Mobile:" . $macips_mobile_row["mobile"] . "    DESC:" . $macips_mobile_row["addr"] . "<br>";
			$mobils = $mobils . " " . $macips_mobile_row["mobile"] . ", ";		
		} 
		$mobils = substr($mobils, 0, strlen($mobils) -2);		
		print "    <tr>
                 <td class='textArea' >
                     <p>Подтверждаете создание рассылки для следующих записей ?</p>
                     <p>$row_list</p>
                 </td>
             </tr>\n
             ";
     };
	};			

	print "<tr>
		<td colspan='$colspan' align='right' class='saveRow'>
			<input type='hidden' name='action' value='actions'>
			<input type='hidden' name='selected_items' value='" . (isset($row_array) ? serialize($row_array) : '') . "'>
			<input type='hidden' name='drp_action' value='" . get_request_var('drp_action') . "'>" . (strlen($save_html) ? "
			<input type='button' name='cancel' onClick='cactiReturnTo()' value='" . __esc('Cancel', 'bdcom') . "'>
			$save_html" : "<input type='button' onClick='cactiReturnTo()' name='cancel' value='" . __esc('Return', 'bdcom') . "'>") . "
		</td>
	</tr>";

	html_end_box();

	form_end();

	bottom_footer();	
	
	
	
	
	
	
 }
 

 

 
function bdcom_onu_filter() {
	global $item_rows, $bdcom_search_types, $bdcom_port_search_types;

	?>
	<td width="100%" valign="top"><?php bdcom_display_output_messages();?>
	<tr class='even'>
		<td>
		<form id='bdcom'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Type', 'bdcom');?>
					</td>
					<td>
						<select id='device_type_id' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('device_type_id') == '-1') {?> selected<?php }?>><?php print __('Any');?></option>
							 <?php
								$device_types = db_fetch_assoc('SELECT device_type_id, description FROM plugin_bdcom_dev_types i;');
							 if (sizeof($device_types) > 0) {
								 foreach ($device_types as $device_type) {
									 if ($device_type["device_type_id"] == 0) {
										 $display_text = "Unknown Device Type";
									 }else{
										 $display_text = $device_type["description"];
									 }
									print '<option value="' . $device_type['device_type_id'] . '"'; if (get_request_var('device_type_id') == $device_type['device_type_id']) { print ' selected'; } print '>' . $display_text . '</option>';
								 }
							 }
							 ?>							
 						</select>
					</td>	
  					<td width="1">
 						<?php print __('Device', 'bdcom');?>
 					</td>
 					<td width="1">
 						<select id='device_id' onChange='applyFilter()'>
							<option value="-1"<?php if (get_request_var('device_id') == "-1") {?> selected<?php }?>><?php print __('Any');?></option>
							<?php
							$filter_devices = db_fetch_assoc("SELECT device_id, description, hostname FROM plugin_bdcom_devices ORDER BY order_id");
							if (sizeof($filter_devices) > 0) {
								foreach ($filter_devices as $filter_device) {
									print '<option value=" ' . $filter_device['device_id'] . '"'; if (get_request_var('device_id') == $filter_device['device_id']) { print ' selected'; } print '>' . $filter_device['description'] . '(' . $filter_device["hostname"] . ')' .  '</option>\n';
								}
							}
 						?>
 						</select>
 					</td>					
					<td>
						<?php print __('Search', 'bdcom');?>
					</td>
					<td>
						<input type='text' id='filter' size='25' value='<?php print get_request_var('filter');?>'>
					</td>
					<td>
						<span class='nowrap'>
							<input type='submit' id='go' value='<?php print __esc('Go', 'bdcom');?>'>
							<input type='button' id='clear' value='<?php print __esc('Clear', 'bdcom');?>'>
							<input type='button' id='import' value='<?php print __esc('Import', 'bdcom');?>'>
							<input type='submit' id='export' value='<?php print __esc('Export', 'bdcom');?>'>
						</span>
					</td>
				</tr>
			</table>
 			<table class='filterTable'>
 				<tr>
 					<td width="100">
 						<?php print __('Состояние IP', 'bdcom');?>
 					</td>
 					<td>
                         <select id='sost' onChange='applyFilter()'>
                         <option value="-1"<?php if (get_request_var('sost') == '-1') {?> selected<?php }?>>Любой</option>
 						 <option value="0"<?php if (get_request_var('sost') == '0') {?> selected<?php }?>>Положительный баланс</option>
                         <option value="1"<?php if (get_request_var('sost') == '1') {?> selected<?php }?>>Отрицательный баланс</option>
						 <option value="3"<?php if (get_request_var('sost') == '3') {?> selected<?php }?>>Заблокирован Адм.</option>
						 <option value="4"<?php if (get_request_var('sost') == '4') {?> selected<?php }?>>Несуществует</option>
						 <option value="5"<?php if (get_request_var('sost') == '5') {?> selected<?php }?>>Служебный</option>
						 <option value="6"<?php if (get_request_var('sost') == '6') {?> selected<?php }?>>Оборудование по акции</option>
                         </select>
 					</td>				
						<td width="100">
							<?php print __('Состояние ONU', 'bdcom');?>
						</td>
						<td width="1">
							 <select id='status' onChange='applyFilter()'>
							 <option value="-1"<?php if (get_request_var('status') == '-1') {?> selected<?php }?>>Любой</option>
							 <option value="1"<?php if (get_request_var('status') == '1') {?> selected<?php }?>>UP</option>
							 <option value="2"<?php if (get_request_var('status') == '2') {?> selected<?php }?>>DOWN</option>
							 <option value="3"<?php if (get_request_var('status') == '3') {?> selected<?php }?>>BDCOM != 554v</option>
							 </select>
						</td>				
  					<td width="1">
 						<?php print __('Uzel', 'bdcom');?>
 					</td>
 					<td width="1">
 						<select id='uzel_id' onChange='applyFilterUzel()'>
							<option value="-1"<?php if (get_request_var('uzel_id') == "-1") {?> selected<?php }?>><?php print __('Any');?></option>
							<?php
								$str_uzelsql = "SELECT onu_us_enduzelid, onu_us_enduzel_descr FROM plugin_bdcom_onu ";
								if (get_request_var('device_id') != "-1") {
									$str_uzelsql .= " WHERE device_id = '" . get_request_var('device_id') . "' ";
								}
								$str_uzelsql .= " group by onu_us_enduzelid ORDER BY onu_us_enduzelid ";
								$filter_uzels = db_fetch_assoc($str_uzelsql);							
							
								if (sizeof($filter_uzels) > 0) {
									foreach ($filter_uzels as $filter_uzel) {
										print '<option value=" ' . $filter_uzel['onu_us_enduzelid'] . '"'; if (get_request_var('uzel_id') == $filter_uzel['onu_us_enduzelid']) { print ' selected'; } print '> [' . $filter_uzel['onu_us_enduzelid'] . ']  ' . $filter_uzel['onu_us_enduzel_descr'] .  '</option>\n';
									}
								}
 						?>
 						</select>
 					</td>					
 				</tr>				
 			</table>
 			<table class='filterTable'>
 				<tr>
 					<td width="100">
						<?php print __('IP Address:', 'bdcom');?>
 					</td>
 					<td width="1">
 						<select id='ip_filter_type_id' onChange='applyFilter()'>
 						<?php
 						for($i=1;$i<=sizeof($bdcom_search_types);$i++) {
 							print "<option value='" . $i . "'"; if (get_request_var('ip_filter_type_id') == $i) { print " selected"; } print ">" . $bdcom_search_types[$i] . "</option>\n";
 						}
 						?>
 						</select>
 					</td>
 					<td width="1">
 						<input type="text" id="ip_filter" size="20" value="<?php print get_request_var('ip_filter');?>">
 					</td>
 					<td width="100">
						<?php print __('MAC Address:', 'bdcom');?>
 					</td>
 					<td width="1">
 						<select id='mac_filter_type_id' onChange='applyFilter()'>
 						<?php
 						for($i=1;$i<=sizeof($bdcom_search_types);$i++) {
 							print "<option value='" . $i . "'"; if (get_request_var('mac_filter_type_id') == $i) { print ' selected'; } print '>' . $bdcom_search_types[$i] . '</option>\n';
 						}
 						?>
 						</select>
 					</td>
 					<td width="1">
 						<input type="text" id="mac_filter" size="20" value="<?php print get_request_var('mac_filter');?>">
 					</td>

					<td width="100">
						<?php print __('Firmware', 'bdcom');?>
					</td>
					<td width="1">
						 <select id='firm' onChange='applyFilter()'>
						 <option value="-1"<?php if (get_request_var('firm') == '-1') {?> selected<?php }?>>Any</option>
						 <option value="1"<?php if (get_request_var('firm') == '1') {?> selected<?php }?>>22B</option>
						 <option value="2"<?php if (get_request_var('firm') == '2') {?> selected<?php }?>>26B.*</option>
						 <option value="3"<?php if (get_request_var('firm') == '3') {?> selected<?php }?>>26B.2001</option>
						 <option value="3"<?php if (get_request_var('firm') == '4') {?> selected<?php }?>>26B.554</option>
						 </select>
					</td>						
					
 				</tr>
 			</table>			
 			<table class='filterTable'>
 				<tr>
 					<td width="100">
						<?php print __('Epon', 'bdcom');?>
 					</td>
 					<td width="1">
 						<select id='epon_id' onChange='applyFilter()'>
							<?php
								if (get_request_var('device_id') !== "-1") {
									$str_eponsql = " SELECT epon_id, epon_name, epon_descr FROM plugin_bdcom_epons WHERE device_id = '" . get_request_var('device_id') . "' order by epon_id ";
									
									$filter_epons = db_fetch_assoc($str_eponsql);
									if (sizeof($filter_epons) > 0) {
									foreach ($filter_epons as $filter_epon) {
										print '<option value=" ' . $filter_epon['epon_id'] . '"'; if (get_request_var('epon_id') == $filter_epon['epon_id']) { print ' selected'; } print '> [' . $filter_epon['epon_id'] . ']  ' . $filter_epon['epon_name'] . '  ' . $filter_epon['epon_descr'] .  '</option>\n';
									}
									}								
									
								}else{
									?>
									<option value="-1"<?php if (get_request_var('epon_id') == "-1") {?> selected<?php }?>>All</option>
									<?php
									
								}								
							?>
 						</select>
 					</td>
					<td>
						<?php print __('Rows', 'bdcom');?>
					</td>
					<td>
						<select id='rows' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('rows') == '-1') {?> selected<?php }?>><?php print __('Default', 'bdcom');?></option>
							<?php
							if (cacti_sizeof($item_rows)) {
								foreach ($item_rows as $key => $value) {
									print "<option value='" . $key . "'"; if (get_request_var('rows') == $key) { print ' selected'; } print '>' . $value . '</option>';
								}
							}
							?>
						</select>
					</td>					
 				</tr>
 			</table>
		</form>
		<script type='text/javascript'>
		function applyFilter() {
			strURL  = urlPath+'plugins/bdcom/bdcom_view_onus.php?header=false';
			strURL += '&device_type_id=' + $('#device_type_id').val();
			strURL += '&device_id=' + $('#device_id').val();
			strURL += '&filter=' + $('#filter').val();
			strURL += '&sost=' + $('#sost').val();
			strURL += '&status=' + $('#status').val();
			strURL += '&ip_filter_type_id=' + $('#ip_filter_type_id').val();
			strURL += '&ip_filter=' + $('#ip_filter').val();
			strURL += '&mac_filter_type_id=' + $('#mac_filter_type_id').val();
			strURL += '&mac_filter=' + $('#mac_filter').val();
			strURL += '&epon_id=' + $('#epon_id').val();
			strURL += '&rows=' + $('#rows').val();
			loadPageNoHeader(strURL);
		}
		function applyFilterUzel() {
			strURL  = urlPath+'plugins/bdcom/bdcom_view_onus.php?header=false';
			strURL += '&device_type_id=' + $('#device_type_id').val();
			strURL += '&device_id=' + $('#device_id').val();
			strURL += '&filter=' + $('#filter').val();
			strURL += '&sost=' + $('#sost').val();
			strURL += '&status=' + $('#status').val();
			strURL += '&ip_filter_type_id=' + $('#ip_filter_type_id').val();
			strURL += '&ip_filter=' + $('#ip_filter').val();
			strURL += '&mac_filter_type_id=' + $('#mac_filter_type_id').val();
			strURL += '&mac_filter=' + $('#mac_filter').val();
			strURL += '&epon_id=-1';
			strURL += '&rows=' + $('#rows').val();
			loadPageNoHeader(strURL);
		}
		function clearFilter() {
			strURL  = urlPath+'plugins/bdcom/bdcom_view_onus.php?header=false&clear=true';
			loadPageNoHeader(strURL);
		}

		function exportRows() {
			strURL  = urlPath+'plugins/bdcom/bdcom_view_onus.php?export=true';
			document.location = strURL;
		}

		function importRows() {
			strURL  = urlPath+'plugins/bdcom/bdcom_view_onus.php?import=true';
			loadPageNoHeader(strURL);
		}

		$(function() {
			$('#bdcom').submit(function(event) {
				event.preventDefault();
				applyFilter();
			});

			$('#clear').click(function() {
				clearFilter();
			});

			$('#export').click(function() {
				exportRows();
			});

			$('#import').click(function() {
				importRows();
			});
		});
		</script>
		</td>
	</tr>
	<?php
}


 
?>
