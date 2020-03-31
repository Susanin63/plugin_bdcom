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
 	);	

$title = __('BDCOM - ONU Report View', 'bdcom');

/* check actions */
switch (get_request_var('action')) {
	case 'actions':
		form_actions_info();

		break;
	case 'onu_query':
		onu_query();
		break;
	case 'onu_query_ajax':
		onu_query_ajax();
		break;			
	default:
		bdcom_redirect();
		general_header();
		bdcom_view_info();
		bottom_footer();
		break;
}


 function onu_query() {
 	/* ================= input validation ================= */
 	input_validate_input_number(get_request_var("onu_id"));
 	/* ==================================================== */
 
 	update_onu_power(get_request_var("onu_id"));
	header("Location: bdcom_view_info.php");
 } 

  function onu_query_ajax() {
 	/* ================= input validation ================= */
 	input_validate_input_number(get_request_var("onu_id"));
 	/* ==================================================== */
 
 	print json_encode(array("pow"=>bdcom_color_power_cell(update_onu_power(get_request_var("onu_id")))));
	//header("Location: bdcom_view_info.php");
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
	
        switch (get_request_var('epon_filter_type_id')) {
             case "1": /* do not filter */
                 break;
             case "2": /* состоит */
 				$sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') .  " FIND_IN_SET('" . trim(get_request_var('epon_filter')) . "',plugin_bdcom_onu.epon_id)";
                 break;
             case "3": /* не состоит */
 				$sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') .  " NOT FIND_IN_SET('" . trim(get_request_var('epon_filter')) . "',epon_id)";
 
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

    if (!(get_request_var('device_id') == "-1")) {
         
		 $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " plugin_bdcom_onu.device_id=" . get_request_var('device_id');
    }
    if (!(get_request_var('status') == "-1")) {

         $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " plugin_bdcom_onu.onu_operstatus=" . get_request_var('status');
    }		
	
 	$sortby = get_request_var('sort_column');
 	if ($sortby=="onu_index") {
 		$sortby = " LENGTH(onu_name), onu_name) ";
 	}elseif($sortby=="f_flat") {
 		$sortby = "ABS(f_flat)";
 	}elseif($sortby=="onu_name") {
 		$sortby = " plugin_bdcom_epons.epon_name, LENGTH(onu_name), onu_name ";
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
					plugin_bdcom_onu.onu_macaddr, plugin_bdcom_onu.onu_ipaddr, plugin_bdcom_onu.onu_name, plugin_bdcom_onu.onu_descr, plugin_bdcom_onu.onu_operstatus, plugin_bdcom_onu.onu_adminstatus, plugin_bdcom_onu.onu_dereg_status, onu_soft_version, plugin_bdcom_onu.onu_index, plugin_bdcom_onu.epon_id, onu_us_enduzelid, onu_us_enduzel_descr,
					onu_done_view_count, onu_online, onu_first_scan_date, onu_lastchange_date, plugin_bdcom_onu.onu_scan_date, plugin_bdcom_onu.onu_rxpower_change,  plugin_bdcom_onu.onu_version,
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


function bdcom_view_get_bindings_records(&$sql_where, $apply_limits = TRUE, $rows = '30') {
     /* form the 'where' clause for our main sql query */
 	switch (get_request_var('mac_filter_type_id')) {
             case "1": /* do not filter */
                 break;
             case "2": /* matches */
                 $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " imb_macip.macip_macaddr='" . get_request_var('mac_filter') . "'";
                 break;
             case "3": /* contains */
                 $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " imb_macip.macip_macaddr LIKE '%%" . get_request_var('mac_filter') . "%%'";
                 break;
             case "4": /* begins with */
                 $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " imb_macip.macip_macaddr LIKE '" . get_request_var('mac_filter') . "%%'";
                 break;
             case "5": /* does not contain */
                 $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " imb_macip.macip_macaddr NOT LIKE '" . get_request_var('mac_filter') . "%%'";
                 break;
             case "6": /* does not begin with */
                 $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " imb_macip.macip_macaddr NOT LIKE '" . get_request_var('mac_filter') . "%%'";
         }

 

         switch (get_request_var('ip_filter_type_id')) {
             case "1": /* do not filter */
                 break;
             case "2": /* matches */
                 $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " imb_macip.macip_ipaddr='" . get_request_var('ip_filter') . "'";
                 break;
             case "3": /* contains */
                 $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " imb_macip.macip_ipaddr LIKE '%%" . get_request_var('ip_filter') . "%%'";
                 break;
             case "4": /* begins with */
                 $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " imb_macip.macip_ipaddr LIKE '" . get_request_var('ip_filter') . "%%'";
                 break;
             case "5": /* does not contain */
                 $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " imb_macip.macip_ipaddr NOT LIKE '" . get_request_var('ip_filter') . "%%'";
                 break;
             case "6": /* does not begin with */
                 $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " imb_macip.macip_ipaddr NOT LIKE '" . get_request_var('ip_filter') . "%%'";
                 break;
             case "7": /* is null */
                 $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " imb_macip.macip_ipaddr = ''";
                 break;
             case "8": /* is not null */
                 $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " imb_macip.macip_ipaddr != ''";
         }
 
 	
     if (strlen(get_request_var('filter'))) {
             $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " (imb_macip.macip_port_hex LIKE '%" . get_request_var('filter') . "%' OR " .
                 "imb_macip.macip_port_list LIKE '%" . get_request_var('filter') . "%' OR " .
 				"lbs.login LIKE '%" . get_request_var('filter') . "%' OR " .
				"imb_macip.macip_lastchange_date LIKE '%" . get_request_var('filter') . "%' OR " .
 				"f_flat LIKE '%" . get_request_var('filter') . "%' OR " .
				"imb_macip.macip_scan_date LIKE '%" . get_request_var('filter') . "%')";
    }
 
         switch (get_request_var('sost')) {
             case "-1": /* do not filter */
				break;
             case "0": /* положительный баланс */
 				$sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " lbs.blocked = 0 ";
                 break;
             case "1": /* отрицательный баланс */
 				$sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " lbs.blocked = 1 ";
				break;
             case "3": /* заблокирован */
 				$sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " lbs.blocked = 3 ";
				break;
             case "4": /* несуществ */
 				$sql_where .= " lbs.segment is null and host.hostname is null ";
				break;					
             case "5": /* служебн */
 				$sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " lbs.segment is null and host.hostname is not null ";
				break; 
             case "6": /* оборуд по акции */
 				$sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " lbs.equipm_rtr is not null ";
				break;				
         }
	
     if (!(get_request_var('device_id') == "-1")) {

         $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " imb_macip.device_id=" . get_request_var('device_id');
     }


	if ($apply_limits) {
		$sql_limit = ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ', ' . $rows;
	}else{
		$sql_limit = '';
	}
	
 	$sortby = get_request_var('sort_column');
 	if ($sortby=="onu_id") {
 		$sortby = "INET_ATON(macip_ipaddr)";
 	}	
 	if ($sortby=="macip_ipaddr") {
 		$sortby = "INET_ATON(macip_ipaddr)";
 	}
 	if ($sortby=="f_flat") {
 		$sortby = "ABS(f_flat)";
 	}	
//								 If (lbs.segment is null, if(host.hostname is null,'IP нигде не зарегистрирован','Служебный IP'),if(lbs.blocked = 0,'Все ОК',if(lbs.blocked = 1,'Минусовой баланс',)) as sig1,
 	
 		$query_string = "SELECT  If (lbs.segment is null, if(host.hostname is null,'ip_noo','ip_cacti'),CONCAT('ipb_',lbs.blocked)) as sig, " .

		
								//" If (lbs.segment is null, if(host.hostname is null,'IP нигде не зарегистрирован','Служебный IP'),CASE lbs.blocked WHEN 0 THEN 'Все ОК' WHEN 1 THEN CONCAT('Минусовой баланс = ',ROUND(lbs.balance/100,2), ' c ', date(lbs.acc_ondate)) WHEN 2 THEN CONCAT('Блок пользователя c ', date(lbs.acc_ondate)) WHEN 3 THEN CONCAT('Админ Блок c ', date(lbs.acc_ondate)) END ) as sig2, " .
								" If (lbs.segment is null, if(host.hostname is null,'IP нигде не зарегистрирован','Служебный IP'), " . 
									"concat ('[', lbs.ag_num , '], ' , " .
									"CASE lbs.blocked " .
										" WHEN 0 THEN CONCAT('Баланс = ',ROUND(lbs.balance,2)) " .
										" WHEN 1 THEN CONCAT('Минусовой баланс = ',ROUND(lbs.balance/100,2), ' c ', date(lbs.block_date)) " .
										" WHEN 2 THEN CONCAT('Блок пользователя c ', date(lbs.acc_ondate)) " .
										" WHEN 3 THEN CONCAT('Админ Блок c ', date(lbs.acc_ondate)) END )) as sig2, " .
								
			" f_addr, h.id as cid,
			imb_device_types.device_type_id, imb_device_types.type_imb_action, imb_device_types.type_imb_mode, imb_devices.description, imb_devices.hostname, imb_devices.last_rundate,
            imb_macip.device_id, imb_macip.macip_id, imb_macip.macip_macaddr, imb_macip.macip_ipaddr, imb_macip.macip_port_list, imb_macip.macip_port_view, imb_macip.macip_imb_status, imb_macip.macip_banned, imb_macip.macip_imb_action,imb_macip.macip_mode, macip_online, macip_first_scan_date, macip_lastchange_date, imb_macip.macip_scan_date, macip_count_scan, imb_macip.macip_active_last_poll, imb_macip.macip_may_move,
			 lbs.f_flat, lbs.equipm_rtr,  if(gl_ip.id is null,if(gl_ping.id is null,'0',gl_ping.id),gl_ip.id) as ip_local_graph_id,
             lbs.login
			 FROM  imb_macip
             left JOIN imb_devices
             ON imb_macip.device_id = imb_devices.device_id
             JOIN imb_device_types ON imb_devices.device_type_id = imb_device_types.device_type_id 
			 LEFT JOIN (SELECT l.segment,  v.*  FROM lb_staff l left JOIN lb_vgroups_s v ON l.vg_id = v.vg_id WHERE v.`archive`=0) lbs ON INET_ATON(imb_macip.macip_ipaddr) = lbs.segment
			left JOIN host             ON imb_macip.macip_ipaddr = host.hostname		
			LEFT JOIN graph_local gl_ip ON gl_ip.snmp_index=inet_aton(imb_macip.macip_ipaddr) and gl_ip.graph_template_id=43
			LEFT JOIN plugin_fping fp ON (imb_macip.macip_ipaddr=fp.host)   
			LEFT JOIN graph_local gl_ping ON gl_ping.snmp_index=fp.id and gl_ping.graph_template_id=82
			left JOIN host   h          ON imb_devices.hostname = h.hostname
 			$sql_where
 			ORDER BY " . $sortby . " " . get_request_var('sort_direction') .
			$sql_limit;
 			
                                                                                  
         return db_fetch_assoc($query_string);
     
 }
 



function bdcom_view_get_info_macips_records(&$sql_where, $apply_limits = TRUE, $row_limit = -1) {
     /* form the 'where' clause for our main sql query */
     switch (get_request_var('i_mac_filter_type_id')) {
             case "1": /* do not filter */
                 break;
             case "2": /* matches */
                 $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . (strlen($sql_where) ? ' AND ': 'WHERE ') . (strlen($sql_where) ? ' AND ': 'WHERE ') . " imb_macip.macip_macaddr='" . get_request_var('i_mac_filter') . "'";
                 break;
             case "3": /* contains */
                 $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " imb_macip.macip_macaddr LIKE '%%" . get_request_var('i_mac_filter') . "%%'";
                 break;
             case "4": /* begins with */
                 $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " imb_macip.macip_macaddr LIKE '" . get_request_var('i_mac_filter') . "%%'";
                 break;
             case "5": /* does not contain */
                 $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " imb_macip.macip_macaddr NOT LIKE '" . get_request_var('i_mac_filter') . "%%'";
                 break;
             case "6": /* does not begin with */
                 $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " imb_macip.macip_macaddr NOT LIKE '" . get_request_var('i_mac_filter') . "%%'";
         }
 
         switch (get_request_var('i_ip_filter_type_id')) {
             case "1": /* do not filter */
                 break;
             case "2": /* matches */
                 $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " imb_macip.macip_ipaddr='" . get_request_var('i_ip_filter') . "'";
                 break;
             case "3": /* contains */
                 $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " imb_macip.macip_ipaddr LIKE '%%" . get_request_var('i_ip_filter') . "%%'";
                 break;
             case "4": /* begins with */
                 $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " imb_macip.macip_ipaddr LIKE '" . get_request_var('i_ip_filter') . "%%'";
                 break;
             case "5": /* does not contain */
                 $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " imb_macip.macip_ipaddr NOT LIKE '" . get_request_var('i_ip_filter') . "%%'";
                 break;
             case "6": /* does not begin with */
                 $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " imb_macip.macip_ipaddr NOT LIKE '" . get_request_var('i_ip_filter') . "%%'";
                 break;
             case "7": /* is null */
                 $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " imb_macip.macip_ipaddr = ''";
                 break;
             case "8": /* is not null */
                 $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " imb_macip.macip_ipaddr != ''";
         }
 
     if ((strlen(get_request_var('i_epon_filter')) > 0)||(get_request_var('i_epon_filter_type_id') > 5)) {
         switch (get_request_var('i_epon_filter_type_id')) {
             case "1": /* do not filter */
                 break;
             case "2": /* состоит */
 				$sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " imb_macip.macip_port_list='" . get_request_var('i_epon_filter') . "'";
                 break;
             case "3": /* не состоит */
 				$sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " imb_macip.macip_port_list NOT LIKE '" . get_request_var('i_epon_filter') . "'";
 
         }
     }	
 	
     if (strlen(get_request_var('i_filter'))) {
             $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " (imb_macip.macip_port_hex LIKE '%" . get_request_var('i_filter') . "%' OR " .
                 "imb_macip.macip_port_list LIKE '%" . get_request_var('i_filter') . "%')";
    }
 
     if (!(get_request_var('i_device_id') == "-1")) {
 
         $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " imb_macip.device_id=" . get_request_var('i_device_id');
     }
         $query_string = "SELECT  imb_devices.description, imb_devices.hostname, imb_devices.device_id, imb_devices.last_rundate,
             imb_macip.macip_id, imb_macip.macip_macaddr, imb_macip.macip_ipaddr, imb_macip.macip_banned, imb_macip.macip_port_list, imb_macip.macip_port_view, imb_macip.macip_imb_status, imb_macip.macip_mode, macip_online, macip_first_scan_date, macip_lastchange_date, imb_macip.macip_scan_date, macip_count_scan
             FROM  imb_macip
             LEFT JOIN imb_devices
             ON imb_macip.device_id = imb_devices.device_id
             $sql_where
             ORDER BY macip_ipaddr " . get_request_var('sort_direction');
             
                                                                                  
         if (($apply_limits) && ($row_limit != 999999)) {
             $query_string .= " LIMIT " . ($row_limit*(get_request_var('i_page')-1)) . "," . $row_limit;
         }
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
			'default' => 'onu_id',
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
		'epon_filter_type_id' => array(
			'filter' => FILTER_SANITIZE_STRING,
			'default' => '1',
			'pageset' => true
			),			
		'epon_filter' => array(
			'filter' => FILTER_SANITIZE_STRING,
			'default' => '',
			'pageset' => true
			),
		'sost' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '-1',
			'pageset' => true
			),	
	);

	validate_store_request_vars($filters, 'sess_bdcomv_info');
	/* ================= input validation ================= */
	
	
 }

 
 function bdcom_view_info() {
     global $title, $report, $colors, $rows_selector, $config, $onu_actions;
 
 	print "<div id='element_to_pop_ping'>
			<a class='b-close'>x<a/>
			Ping Host
		  </div>
		";
		
	bdcom_onu_request_validation();

	if (get_request_var('rows') == -1) {
		$rows = read_config_option('num_rows_table');
	} elseif (get_request_var('rows') == -2) {
		$rows = 999999;
	} else {
		$rows = get_request_var('rows');
	}
	
	$webroot = $config['url_path'] . 'plugins/bdcom/';
	bdcom_tabs();

	html_start_box($title, '100%', '', '3', 'center', 'bdcom_view_info.php?action=edit&status=' . get_request_var('status'));
	bdcom_onu_filter();
	html_end_box(); 
	//bdcom_group_tabs();


	$sql_where = '';

    $onus = bdcom_view_get_onu_records($sql_where, $rows);
 	//$devices_sum = bdcom_view_get_onu_records($sql_where);
 	
    $total_rows = db_fetch_cell("SELECT
        COUNT(plugin_bdcom_onu.device_id)
        FROM plugin_bdcom_onu
		LEFT JOIN (SELECT l.segment,  v.*  FROM lb_staff l left JOIN lb_vgroups_s v ON l.vg_id = v.vg_id WHERE v.`archive`=0) lbs ON INET_ATON(plugin_bdcom_onu.onu_ipaddr) = lbs.segment 
		left JOIN (SELECT * FROM lb_vgroups_s WHERE lb_vgroups_s.id is null or lb_vgroups_s.id =1)  lbv  ON plugin_bdcom_onu.onu_agrm_id = lbv.agrm_id	
        $sql_where");
 
	if (sizeof($onus) == 1) {
		$ip_full_info = db_fetch_assoc("SELECT login, blocked,balance, ag_num, f_addr, f_flat, equipm_rtr, mobile, l.vg_id, onu_ipaddr , agrm_id " .
			" FROM plugin_bdcom_onu o " .
				" LEFT JOIN lb_staff l ON (l.ip=o.onu_ipaddr ) " .
				" LEFT JOIN lb_vgroups_s lv ON (lv.vg_id=l.vg_id)  " .
				" WHERE onu_id="  . $onus[0]["onu_id"] . ";");
			
			
			if (sizeof($ip_full_info) == 1) {
				$o = reset($onus);
				$i = reset($ip_full_info);
				html_start_box('Информация по IP', '98%', '', '1', 'center', '');
				?>
				<tr>
					<td>
						<table>
							<tr><td><?php print ("OLT = " . $o["description"] . "\n");?></td></tr>
							<tr><td><?php print ("EPON = <a class='linkEditMain' href='bdcom_view_onus.php?report=onus&device_type_id=-1&device_id=+" . $o["device_id"] . "&epon_id=+" . $o["epon_id"] . "&ip_filter_type_id=-1&ip_filter=&status=-1&status=-1&filter=&page=1'>" . $o["epon_name"] . "</a>");?></td></tr>
							<tr><td><?php print ("MAC = " . $o["onu_macaddr"] . "<a class='linkEditMain' href='bdcom_view.php?action=actions_onu&drp_action=3&post_error=" . serialize(array(0 => $o["onu_id"])) . "'><img src='" . $config['url_path'] . "plugins/bdcom/images/view_macs.gif' alt='View MACs' border='0' align='absmiddle'></a>");?></td></tr>
							<tr><td><?php print ("ONU = " . $o["onu_name"] . "\n");?></td></tr>
							<tr><td><?php print ("ONU_ID=<a class='linkEditMain' href='bdcom_view_onus.php?report=onus&device_id=" . $o['device_id'] . "&sost=-1&status=-1&firm=-1&ip_filter_type_id=2&ip_filter=" . $o["onu_ipaddr"] . "'>" . $o["onu_id"] . "</a>" );?></td></tr>
							<tr><td><?php print ("liid = " . $o["onu_index"] . "\n");?></td></tr>
							<tr><td><?php print ("FIRM = " . $o["onu_soft_version"] . " <a class='linkEditMain' href='bdcom_view.php?action=onu_query_firm&onu_id=" . $o["onu_id"] . "'><img src='../../images/reload_icon_small.gif' alt='Update ONU' border='0' align='absmiddle'></a>");?></td></tr>
							<tr><td><?php print ("DIST = " . $o["onu_distance"] . "\n");?></td></tr>
							<tr><td><?php print ("RX = " . round($o["onu_rxpower"]/10,2) . " <a class='linkEditMain' href='bdcom_view.php?action=onu_query&onu_id=" . $o["onu_id"] . "'><img src='../../images/reload_icon_small.gif' alt='Update ONU' border='0' align='absmiddle'></a>");?></td></tr>
							<tr><td><?php print ("UZEL = " . $o["onu_us_enduzelid"] . "  <a class='linkEditMain'  href='https://us.ion63.ru/oper/uzel.php?type=vols&code=" . $o["onu_us_enduzelid"] . "'> [" . $o["onu_us_enduzel_descr"] . "] " . "</a>");?></td></tr>
							<tr><td><?php print ("Change = " . $o["onu_lastchange_date"] . "\n");?></td></tr>
							
						</table>
					</td>
					<td>
						<table>
							<tr><td><?php print ("VG = " . $i["vg_id"] . "\n");?></td></tr>
							<tr><td><?php print ("IP = " . $i["onu_ipaddr"] . "\n");?></td></tr>
							<tr><td><?php print ("Login = " . $i["login"] . "\n");?></td></tr>
							<tr><td><?php print ("Status = " . $i["blocked"] . "\n");?></td></tr>
							<tr><td><?php print ("AG = " . $i["ag_num"] . "\n");?></td></tr>
							<tr><td><?php print ("Balance = " . round($i["balance"],3) );?></td></tr>
							<tr><td><?php print ("Addr = " . $i["f_addr"] . "\n");?></td></tr>
							<tr><td><?php print ("mobile = " . $i["mobile"] . "\n");?></td></tr>
							<?php
							$ip_equip_info = db_fetch_assoc("SELECT * FROM lb_equip_history eh left join lb_equipment e on (e.equip_id=eh.equip_id)  " .
								" left join (SELECT equip_id, count(record_id) as eq_new FROM lb_equip_history group by equip_id) eh2 on (eh.equip_id=eh2.equip_id)  " .
								" where eh.agrm_id="  . $i["agrm_id"] . ";");
							if (sizeof($ip_equip_info) > 0) {
								foreach ($ip_equip_info as $equip) {
									?>
									<tr><td><?php print ("Equipm = " . $equip["name"] . " [" . $equip["serial"] . "] [" . $equip["mac"] . "]\n");?></td></tr>
									<?php
								}
								
							}
							?>
						</table>
					</td>
				</tr>				
				<?php
				html_end_box();
			}			

	}	  

  	$display_text = array(
		"onu_id" => array(__('ID', 'bdcom'), "ASC"),
		"description" => array(__('Network<br>Device', 'bdcom'), "ASC"),
		"hostname" => array(__('Network<br>Hostname', 'bdcom'), "ASC"),
		"onu_ipaddr" => array(__('Abon<br>IP Address', 'bdcom'), "ASC"),
		"onu_done_reason" => array('', "ASC"),
		"onu_macaddr" => array(__('ONU<br>MAC Address', 'bdcom'), "ASC"),
		"onu_name" => array(__('ONU<br>NAME', 'bdcom'), "ASC"),
		"onu_descr" => array(__('ONU<br>Descr', 'bdcom'), "ASC"),
		"f_flat" => array(__('Komn', 'bdcom'), "DESC"),
		"epon" => array(__('epon<br>name', 'bdcom'), "DESC"),
		"dist" => array(__('dist', 'bdcom'), "DESC"),
		"power" => array(__('power', 'bdcom'), "DESC"),
		"status" => array(__('ONU<br>status', 'bdcom'), "ASC"),
		"onu_lastchange_date" => array(__('Дата<br>Изменения', 'bdcom'), "ASC"),
		"onu_scan_date" => array(__('Last<br>Scan Date', 'bdcom'), "DESC"),
		" " => array(' ','DESC'));
		
		
	$columns = sizeof($display_text) + 1;  
	
	$nav = html_nav_bar('bdcom_view_info.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, $columns, __('ONUs', 'bdcom'), 'page', 'main');
	
	form_start('bdcom_view_info.php', 'chk');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort_checkbox($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);

	
	if (cacti_sizeof($onus)) {
		foreach ($onus as $onu) {
			form_alternate_row('line' . $onu['device_id'], true);
			bdcom_format_onu_row($onu);

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


/* IMPB */

	$webroot = $config['url_path'] . 'plugins/impb/';
    $sql_where = "";
    $bindings = bdcom_view_get_bindings_records($sql_where, true, $rows);
    $total_rows = db_fetch_cell("SELECT
             COUNT(imb_macip.device_id)
             FROM imb_macip
			 LEFT JOIN (SELECT l.segment,  v.*  FROM lb_staff l left JOIN lb_vgroups_s v ON l.vg_id = v.vg_id WHERE v.`archive`=0) lbs ON INET_ATON(imb_macip.macip_ipaddr) = lbs.segment 
             $sql_where");	
	
	$nav = html_nav_bar('bdcom_view_info.php?report=info', MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, 14, __('Bindings'), 'page', 'main');

	form_start('bdcom_view_infob.php', 'chk');

	html_start_box('', '100%', '', '3', 'center', '');

 
	$display_text = array(
		'description'      => array(__('Device'), 'ASC'),
		'hostname'        => array(__('IP(имя)'), 'ASC'),
		'macip_ipaddr'      => array(__('IP Address'), 'ASC'),
		'macip_macaddr'      => array(__('MAC Address'), 'ASC'),
		'f_flat'      => array(__('Komn'), 'ASC'),
		'macip_port_view'        => array(__('Port<br>List'), 'ASC'),
		'macip_imb_status'      => array(__('Record<br>status'), 'ASC'),
		'macip_imb_action'     => array(__('Record<br>action'), 'ASC'),
		'macip_mode'     => array(__('Mode'), 'ASC'),
		'macip_may_move'     => array(__('Free'), 'DESC'),
		'macip_lastchange_date'     => array(__('Дата<br>Изменения'), 'ASC'),
		'macip_scan_date'      => array(__('Scan Date'), 'DESC'));
 
	html_header_sort_checkbox($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);

    $i = 0;
 	$mac_font_size=read_config_option("dimpb_mac_addr_font_size");
     if (sizeof($bindings) > 0) {
         foreach ($bindings as $binding) {
			$scan_date = $binding["macip_scan_date"];

			if ($binding["macip_active_last_poll"] == 1)  {
				$color_line_date="<span style='font-weight: bold;'>";
			}else{
				$color_line_date="";
			}			
 			
			form_alternate_row('line' . $binding["macip_id"], true);
			form_selectable_cell("<a class='linkEditMain' href='" . htmlspecialchars($webroot . 'impb_devices.php?action=edit&device_id=' . $binding["device_id"]) . "'>'" . 
 				(strlen(get_request_var('filter')) ? preg_replace("/(" . preg_quote(get_request_var('filter')) . ")/i", "<span style='background-color: #F8D93D;'>\\1</span>", $binding["description"]) : $binding["description"]) . "</strong></a>", $binding["macip_id"]);			
				
 			form_selectable_cell($binding["hostname"], $binding["macip_id"] );
 			
			
			//ip
			form_selectable_cell("<img src='" . $config['url_path'] . "plugins/impb/images/term.png' onClick='show_ping_w(" . '"' . $binding["macip_ipaddr"] . '"' . ")' onMouseOver='style.cursor=" . '"' . "pointer" . '"' . "' align='absmiddle' /img> " .
								 "<img src='" . $config['url_path'] . "plugins/impb/images/" . $binding["sig"] . ".png' TITLE='" . $binding["sig2"] . "' align='absmiddle'><a class='inkEditMain' TITLE='" . $binding["sig2"] . ' Адр:' . $binding["f_addr"] . "' href='impb_view_info.php?report=info&amp;device_id=-1&amp;ip_filter_type_id=2&amp;ip_filter=" . $binding["macip_ipaddr"] . "&amp;mac_filter_type_id=1&amp;mac_filter=&amp;port_filter_type_id=&amp;port_filter=&amp;rows=-1&amp;filter=&amp;page=1&amp;report=info&amp;x=23&amp;y=10'>" . 
 				 (strlen(get_request_var('ip_filter')) ? preg_replace("/(" . preg_quote(get_request_var('ip_filter')) . ")/i", "<span style='background-color: #F8D93D;'>\\1</span>", $binding["macip_ipaddr"]) : $binding["macip_ipaddr"]) . "</a>" . ($binding["ip_local_graph_id"]==0 ? '' : " <a class='linkEditMain' href='". htmlspecialchars($config['url_path'] . "graph_view.php?action=preview&host_id=62&graph_template_id=0&snmp_index=&rfilter=" . $binding['macip_ipaddr'] ) . "'><img src='" . $webroot . "images/view_graphs.gif' alt='' title='View Graph' align='absmiddle'></a>") . (strlen($binding["equipm_rtr"])==0 ? '' : ' (R)') , $binding["macip_id"]);
 			
				 
			
 			form_selectable_cell("<a class='linkEditMain' href='impb_view_info.php?report=info&amp;device_id=-1&amp;ip_filter_type_id=8&amp;ip_filter=&amp;mac_filter_type_id=2&amp;mac_filter=" . $binding["macip_macaddr"] . "&amp;port_filter_type_id=&amp;port_filter=&amp;rows=-1&amp;filter=&amp;page=1&amp;report=info&amp;x=14&amp;y=6'><font size='" . $mac_font_size . "' face='Courier'>" . 
 				(strlen(get_request_var('mac_filter')) ? strtoupper(preg_replace("/(" . preg_quote(get_request_var('mac_filter')) . ")/i", "<span style='background-color: #F8D93D;'>\\1</span>", $binding["macip_macaddr"])) : $binding["macip_macaddr"]) . "</font></a>", $binding["macip_id"]);
 				
			form_selectable_cell((strlen(get_request_var('m_filter')) ? preg_replace("/(" . preg_quote(get_request_var('m_filter')) . ")/i", "<span style='background-color: #F8D93D;'>\\1</span>", $binding["f_flat"]) : $binding["f_flat"]), $binding["macip_id"] );
			
			form_selectable_cell((strlen(get_request_var('m_filter')) ? preg_replace("/(" . preg_quote(get_request_var('m_filter')) . ")/i", "<span style='background-color: #F8D93D;'>\\1</span>", $binding["macip_port_view"]) : " <a class='linkEditMain' href=impb_view_ports.php?report=ports&device_id=" . $binding['device_id'] . "&port_number=" . $binding["macip_port_view"]) . ">" . $binding["macip_port_view"] . 
				" <a class='linkEditMain' href='". htmlspecialchars($config['url_path'] . "graph_view.php?action=preview&host_id=" . $binding['cid'] . "&snmp_index=" . $binding["macip_port_view"] . "&graph_template_id=0&rfilter=") . "'><img src='" . $webroot . "images/view_graphs.gif' alt='' title='View Graph' align='absmiddle'></a>", $binding["macip_id"] );
			
 			form_selectable_cell(bdcom_convert_macip_state_2str($binding["macip_imb_status"]), $binding["macip_id"] );
 			form_selectable_cell(bdcom_convert_macip_action_2str($binding["macip_imb_action"], $binding["type_imb_action"]), $binding["macip_id"]  );
 			form_selectable_cell(bdcom_convert_macip_mode_2str_full($binding["macip_mode"], $binding["device_id"]), $binding["macip_id"]  );
 			
			form_selectable_cell(bdcom_convert_free_2str($binding["macip_may_move"]), $binding["macip_id"] );
						
			form_selectable_cell($binding["macip_lastchange_date"], $binding["macip_id"] );
 			
 			form_selectable_cell((strlen(get_request_var('m_filter')) ? $color_line_date . " " .preg_replace("/(" . preg_quote(get_request_var('m_filter')) . ")/i", "<span style='background-color: #F8D93D;'>\\1</span>" , $binding["macip_scan_date"]) : $color_line_date . " " . $binding["macip_scan_date"]), $binding["macip_id"] );
 			
 			
			if ($binding["macip_online"] == 1) {
 				form_checkbox_cell($binding["macip_ipaddr"], $binding["macip_id"]);
 			} else {
 				print "<td></td>";
 			}
 			form_end_row();			
         }
 
         /* put the nav bar on the bottom as well */
         print $nav;
     }else{
         print "<tr><td><em>No IMP Bindings found</em></td></tr>";
     }

	html_end_box(false);

	if (sizeof($bindings)) {
		print $nav;
	}

	form_end();	
	
	
}
 
 


 function bdcom_format_onu_row($onu, $actions=false) {
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
		if ($onu["onu_distance"] > 0) {
			switch ($onu["onu_version"]) {
				 case '151C':
					$alt_distance=$onu["onu_distance"] - 2048;
					break;
				 case '6014':
					$alt_distance=$onu["onu_distance"] - 154;
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
		
		//id
		form_selectable_cell("<a class='linkEditMain' href='bdcom_view_info.php?report=info&amp;device_id=-1&amp;ip_filter_type_id=2&amp;ip_filter=" . $onu["onu_ipaddr"] . "&amp;mac_filter_type_id=2&amp;mac_filter=" . $onu["onu_macaddr"] . "&amp;port_filter_type_id=&amp;port_filter=&amp;rows_selector=-1&amp;filter=&amp;page=1&amp;report=info&amp;x=14&amp;y=6'><font size='" . $mac_font_size . "' face='Courier'>" . 
			$onu["onu_id"] . "</font></a>", $onu["onu_id"]);
			
		form_selectable_cell("<a class='linkEditMain' href='bdcom_devices.php?action=edit&amp;device_id=" . $onu["device_id"] . "'>" . 
			(strlen(get_request_var('filter')) ? preg_replace("/(" . preg_quote(get_request_var('filter')) . ")/i", "<span style='background-color: #F8D93D;'>\\1</span>", $onu["description"]) : $onu["description"]) . "</a>", $onu["onu_id"]);
		form_selectable_cell($onu["hostname"], $onu["onu_id"] );
				
		//IP
		form_selectable_cell("<img src='" . $config['url_path'] . "plugins/bdcom/images/term.png' onClick='show_ping_w(" . '"' . $onu["onu_ipaddr"] . '"' . ")' onMouseOver='style.cursor=" . '"' . "pointer" . '"' . "' align='absmiddle' /img> " . 
							 "<img src='" . $config['url_path'] . "plugins/bdcom/images/" . $onu["sig"] . ".png' TITLE='" . $onu["sig2"] . "' align='absmiddle'><a class='inkEditMain' TITLE='" . $onu["sig2"] . ' Адр:' . $onu["f_addr"] . "' href='bdcom_view_info.php?report=info&amp;device_id=-1&amp;ip_filter_type_id=2&amp;ip_filter=" . $onu["onu_ipaddr"] . "&amp;mac_filter_type_id=1&amp;mac_filter=&amp;port_filter_type_id=&amp;port_filter=&amp;rows_selector=-1&amp;filter=&amp;page=1&amp;report=info&amp;x=23&amp;y=10'>" . 
			 (strlen(get_request_var('ip_filter')) ? preg_replace("/(" . preg_quote(get_request_var('ip_filter')) . ")/i", "<span style='background-color: #F8D93D;'>\\1</span>", $onu["onu_ipaddr"]) : $onu["onu_ipaddr"]) . "</a>" . 
			 ($onu["ip_local_graph_id"]==0 ? '' : " <a class='linkEditMain' href='". htmlspecialchars($config['url_path'] . "graph_ion_view.php?action=preview&host_id=0&graph_template_id=-1&snmp_index=&rfilter=" . $onu['onu_ipaddr'] ) . "'><img src='" . $webroot . "images/view_graphs.gif' border='0' alt='' title='View Graph' align='absmiddle'></a>") . 
			 (strlen($onu["equipm_rtr"])==0 ? '' : ' (R)') , $onu["onu_id"]);
		//onu_done_reason
		form_selectable_cell($onu["onu_done_reason"], $onu["onu_id"] ); 			
		//MAC
		form_selectable_cell("<a class='linkEditMain' href='bdcom_view_info.php?report=info&amp;device_id=-1&amp;ip_filter_type_id=8&amp;ip_filter=&amp;mac_filter_type_id=2&amp;mac_filter=" . $onu["onu_macaddr"] . "&amp;port_filter_type_id=&amp;port_filter=&amp;rows_selector=-1&amp;filter=&amp;page=1&amp;report=info&amp;x=14&amp;y=6'><font size='" . $mac_font_size . "' face='Courier'>" . 
			(strlen(get_request_var('mac_filter')) ? strtoupper(preg_replace("/(" . preg_quote(get_request_var('mac_filter')) . ")/i", "<span style='background-color: #F8D93D;'>\\1</span>", $onu["onu_macaddr"])) : $onu["onu_macaddr"]) . "</font></a>", $onu["onu_id"]);
		//name
		form_selectable_cell((strlen(get_request_var('filter')) ? preg_replace("/(" . preg_quote(get_request_var('filter')) . ")/i", "<span style='background-color: #F8D93D;'>\\1</span>", $onu["onu_name"]) : $onu["onu_name"]) . "</a>" . (false ? '' : " <a class='linkEditMain' href='". htmlspecialchars($config['url_path'] . "graph_ion_view.php?action=preview&host_id=" . $onu['id'] . "&graph_template_id=-1&snmp_index=&rfilter=" . $onu["onu_name"] ) . "'><img src='" . $webroot . "images/view_graphs.gif' border='0' alt='' title='View Graph' align='absmiddle'></a>") , $onu["onu_id"]);
		//descr
		form_selectable_cell(filter_value($onu["onu_descr"], get_request_var('filter')), $onu["onu_id"] );			
		//kvartira
		form_selectable_cell(filter_value($onu["f_flat"], get_request_var('filter')), $onu["onu_id"] );			
		//epon name
		form_selectable_cell((strlen(get_request_var('filter')) ? preg_replace("/(" . preg_quote(get_request_var('filter')) . ")/i", "<span style='background-color: #F8D93D;'>\\1</span>", $onu["epon_name"]) : " <a class='linkEditMain' href=bdcom_view_epons.php?report=epons&device_id=" . $onu['device_id'] . "&port_number=" . $onu["epon_index"]) . ">" . $onu["epon_name"] . 
			" <a class='linkEditMain' href='". htmlspecialchars($config['url_path'] . "graph_ion_view.php?action=preview&host_id=" . $onu['id'] . "&snmp_index=" . $onu["epon_index"] . "&graph_template_id=-1&rfilter=") . "'><img src='" . $webroot . "images/view_graphs.gif' border='0' alt='' title='View Graph' align='absmiddle'></a>", $onu["onu_id"] );
		//distance
		form_selectable_cell($alt_distance, $onu["onu_id"] );			
		//power
		form_selectable_cell(bdcom_color_power_cell($onu), $onu["onu_id"] );			
		//status
		form_selectable_cell(bdcom_convert_status_dereg_2str($onu["onu_operstatus"], $onu["onu_dereg_status"])  . " [" . bdcom_convert_status_2str($onu["onu_adminstatus"]) . "]", $onu["onu_id"] );
					
		form_selectable_cell(bdcom_fromat_datetime($onu["onu_lastchange_date"]), $onu["onu_id"] );
		
		form_selectable_cell(filter_value($onu["onu_scan_date"], get_request_var('filter')), $onu["onu_id"] );			
		
		//form_selectable_cell("<a class='linkEditMain' href='bdcom_view_info.php?action=onu_query&drp_action=5&onu_id=" . $onu["onu_id"] . "'><img src='../../images/reload_icon_small.gif' alt='Update ONU' border='0' align='absmiddle'></a>",0);
		
		$rescan = "<img id='r_" . $onu["onu_id"] . "' src='" . $config['url_path'] . "plugins/bdcom/images/rescan.gif' alt='' onMouseOver='style.cursor=\"pointer\"' onClick='scan_onu(" . $onu["onu_id"] . ")' title='" . __esc('Rescan ONU', 'bdcom') . "'>";
		print  "<td nowrap style='width:1%;white-space:nowrap;'>" . $rescan . '</td>';
		
		form_checkbox_cell($onu["onu_ipaddr"], $onu["onu_id"]);
		form_end_row();	
	

}




 

 function form_actions_info() {
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
            //header("Location: bdcom_view_info.php");
 
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
 		}
		header("Location: bdcom_view_info.php");
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

	form_start('bdcom_view_info.php?header=false');

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
 	            sleep(1);
				$mac_ar =  xform_standard_indexed_data_oid('.1.3.6.1.4.1.3320.152.1.1.3.' . $onu["onu_index"], $onus_devices[$onu["device_id"]]);
				if (isset($mac_ar) and count($mac_ar) > 0 ){
					foreach ($mac_ar as $key => $mac) {
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
		
    }elseif ((isset_request_var('drp_action')) && (get_request_var('drp_action') == "6"))  {  /* СМС Рассылка */
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
 					</td>
 					<td>
 					<td width="100">
						<?php print __('Состояние ONU', 'bdcom');?>
 					</td>
 					<td width="1">
                         <select id='status' onChange='applyFilter()'>
                         <option value="-1"<?php if (get_request_var('status') == '-1') {?> selected<?php }?>>Любой</option>
 						 <option value="1"<?php if (get_request_var('status') == '1') {?> selected<?php }?>>UP</option>
                         <option value="2"<?php if (get_request_var('status') == '2') {?> selected<?php }?>>DOWN</option>
                         </select>
 					</td>				
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
 				</tr>
 			</table>			
 			<table class='filterTable'>
 				<tr>
 					<td width="100">
						<?php print __('Port', 'bdcom');?>
 					</td>
 					<td width="1">
 						<select id='epon_filter_type_id' onChange='applyFilter()'>
							<?php
							for($i=1;$i<=sizeof($bdcom_port_search_types);$i++) {
								print "<option value='" . $i . "'"; if (get_request_var('epon_filter_type_id') == $i) { print 'selected'; } print '>' . $bdcom_port_search_types[$i] . '</option>\n';
							}
							?>
 						</select>
 					</td>
 					<td width="1">
 						<input type="text" if="epon_filter" size="20" value="<?php print get_request_var('epon_filter');?>">
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
			strURL  = urlPath+'plugins/bdcom/bdcom_view_info.php?header=false';
			strURL += '&device_type_id=' + $('#device_type_id').val();
			strURL += '&device_id=' + $('#device_id').val();
			strURL += '&filter=' + $('#filter').val();
			strURL += '&sost=' + $('#sost').val();
			strURL += '&status=' + $('#status').val();
			strURL += '&ip_filter_type_id=' + $('#ip_filter_type_id').val();
			strURL += '&ip_filter=' + $('#ip_filter').val();
			strURL += '&mac_filter_type_id=' + $('#mac_filter_type_id').val();
			strURL += '&mac_filter=' + $('#mac_filter').val();
			strURL += '&epon_filter_type_id=' + $('#epon_filter_type_id').val();
			strURL += '&epon_filter=' + $('#epon_filter').val();
			strURL += '&rows=' + $('#rows').val();
			loadPageNoHeader(strURL);
		}

		function clearFilter() {
			strURL  = urlPath+'plugins/bdcom/bdcom_view_info.php?header=false&clear=true';
			loadPageNoHeader(strURL);
		}

		function exportRows() {
			strURL  = urlPath+'plugins/bdcom/bdcom_view_info.php?export=true';
			document.location = strURL;
		}

		function importRows() {
			strURL  = urlPath+'plugins/bdcom/bdcom_view_info.php?import=true';
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
