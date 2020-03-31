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
 
 
 /* do NOT run this script through a web browser */
/* do NOT run this script through a web browser */
if (!isset($_SERVER["argv"][0]) || isset($_SERVER['REQUEST_METHOD'])  || isset($_SERVER['REMOTE_ADDR'])) {
	//die("<br><strong>This script is only meant to run at the command line.</strong>");
}

  /* We are not talking to the browser */
 $no_http_headers = true;
 

 /* Start Initialization Section */
include(dirname(__FILE__)."/../../include/global.php");
 //include_once($config["base_path"] . "/lib/poller.php");
include_once($config["base_path"] . "/plugins/bdcom/lib/bdcom_functions.php");


global $bdcom_debug;
$old_bdcom_debug = $bdcom_debug;
$bdcom_debug = true;

//bdcom_autoupdate();
//print bdcom_convert_hex_to_view_string('FF:C0:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00');
//bdcom_create_graphs();
//print (print_r($_SERVER["argv"], true));
//разберем сообщение
if (isset($_SERVER["argv"][1])) {
	$str_message = $_SERVER["argv"][1];
	//exit;
}else{
	//$str_message = "172.20.0.171,20180605134749, ONU 8479.7399.af6e is deregistered on EPON0/1:3.";
	//$str_message = "172.20.0.171,20180605180510, ONU 8479.7399.af6e is registered on EPON0/2:3."
	//$str_message = "172.20.0.171,20180627131018, ONU 9845.620b.efd7 is registered on EPON0/2:14.";
	//$str_message = "172.20.0.181,20190507140052, ONU 8479.7374.d89a is registered on EPON0/1:1.. ";
	//$str_message = "172.20.0.170,20190516115552, Interface EPON0/1:17's CTC OAM extension negotiated successfully!"
	//send_viber_msg("test");
	//bdcom_autoupdate();

}
//send_viber_msg("test", "+79377999153, +79377999152, +79631176333");
//$str_hostip = trim(strstr($str_message, ',',true));
//$str_message = str_replace(")","",str_replace("(","",strstr($str_message, '(')));

//if (preg_match("/^(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})[\,\s]+(\d{6,})[\,\s]*(ONU)[\s]+(\S{4}.\S{4}.\S{4}).*(deregistered).*(EPON)(\d)\/(\d)\:(\d+).*$/i", $str_message, $matches) == 1){
if (preg_match("/^(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})[\,\s]+(\d{6,})[\,\s]*(ONU)[\s]+(\S{4}.\S{4}.\S{4})\s+is\s+(\w+)\s+on\s+(EPON)(\d)\/(\d)\:(\d+).*$/i", $str_message, $matches) == 1){
	//ONU registred or DEregistred
	/*
	: array = 
	  0: string = "172.20.0.171,20180605134749, ONU 8479.7399.af6e is deregistered on EPON0/1:3."
	  1: string = "172.20.0.171"
	  2: string = "20180605134749"
	  3: string = "ONU"
	  4: string = "8479.7399.af6e"
	  5: string = "deregistered"
	  6: string = "EPON"
	  7: string = "0"
	  8: string = "1"
	  9: string = "3"	
	*/
	$onu_mac_ar=str_split(str_replace(".", '', $matches[4]),2);
	$matches["onu_name"]=$matches[6] . $matches[7] . "/" . $matches[8] . ":" . $matches[9];
	if (is_array($onu_mac_ar)){
		$onu_mac_str = "";
		$onu_mac_hex = "";
		foreach ($onu_mac_ar as $p) {
			$onu_mac_hex = $onu_mac_hex . "." . base_convert ( $p , 16 , 10 );
			$onu_mac_str = $onu_mac_str . "." . $p;
		}
		$onu_mac_hex = substr($onu_mac_hex, 1);		
		$onu_mac_str = substr($onu_mac_str, 1);	
		$onu_mac_str = str_replace(".", ':', $onu_mac_str);
		$matches["onu_mac"] = $onu_mac_str;
		
		$device =  db_fetch_row ("SELECT * FROM `plugin_bdcom_devices` WHERE hostname='" . $matches[1] . "';");				
		if (sizeof($device) > 0){		
			bdcom_debug("BDCOM:  Got syslog BDCOM message =[" . $str_message . "]");	
			if (abs(strtotime($matches[2]) - strtotime("now")) > 14400) {
				//разница во времени больше 4 часов - неправильное время на OLT
				$onu_time=date('Y-m-d H:i:s');
			}else{
				$onu_time=date('Y-m-d H:i:s', strtotime($matches[2]));
			}
		
			$onu_row =  db_fetch_row ("SELECT o.*, vg.uid FROM plugin_bdcom_onu o LEFT JOIN lb_vgroups_s vg ON (o.onu_agrm_id = vg.agrm_id) WHERE onu_macaddr='" . $matches["onu_mac"] . "' and `device_id`='" . $device["device_id"] . "';");
			if (sizeof($onu_row) > 0){
					//обновим запись
					$str_degr_status = "";
					if ($matches[5] == "deregistered") {
						$degrStatus = bdcom_xform_standard_indexed_data('.1.3.6.1.4.1.3320.101.11.1.1.11.' . $onu_row['onu_bindepon'] . "." . $onu_mac_hex, $device);
						if (count($degrStatus) == 1) {
							$degrStatus = intval(current($degrStatus));
							if (is_int($degrStatus)) {
								$str_degr_status = " , `onu_dereg_status`='" . $degrStatus . "' ";
							}							
						}
						$str_operstatus=2;
						$str_online=0 ;		
						db_execute("UPDATE plugin_bdcom_epons SET `epon_onu_active_total`=`epon_onu_active_total` - 1 WHERE epon_id='" . $onu_row["epon_id"] . "';");						
						db_execute("UPDATE plugin_bdcom_onu SET `onu_operstatus`='" . $str_operstatus . "', `onu_online`='" . $str_online . "', `onu_lastchange_date`='" . $onu_time . "', `onu_lastdereg_date`='" . $onu_time . "' " . $str_degr_status . " WHERE onu_id='" . $onu_row["onu_id"] . "';");
						//cacti_log("BDCOM ERROR1 = " .  print_r($onu_row, true), false, "bdcom_er");
						//db_execute("UPDATE1 plugin_bdcom_onu SET `onu_operstatus`='" . $str_operstatus . "', `onu_online`='" . $str_online . "', `onu_lastchange_date`='" . $onu_time . "', `onu_lastdereg_date`='" . $onu_time . "' " . $str_degr_status . " WHERE onu_id='" . $onu_row["onu_id"] . "';");
						if ($degrStatus == 8 and $onu_row["onu_dis_alarm"] == "0" and (read_config_option("bdcom_enable_msg_fiber") == "1" and ($onu_row["onu_soft_version"] == "10.0.26B.554" OR $onu_row["onu_soft_version"] == "10.0.22B.554")) ) {  //Onu dereg by fiber
							//start script to wait 100 sec - may be ONU just reboot
							db_execute("UPDATE plugin_bdcom_onu SET `onu_wait_up`=1 WHERE onu_id='" . $onu_row["onu_id"] . "';");
							sleep (100);
								$onu_row2 =  db_fetch_row ("SELECT * FROM plugin_bdcom_onu  WHERE onu_id='" . $onu_row["onu_id"] . "';");
								if ($onu_row2["onu_online"] == "0") {  //if onu still down - send message
									//cacti_log("BDCOM ERROR2 = " .  print_r($onu_row2, true), false, "bdcom_er");
									send_viber_msg(date('[H:i] ') ."BDCOM FIBER1 DOWN: ONU_IP=[" . $onu_row["onu_ipaddr"] . " MAC=[" . $matches["onu_mac"] . "] and  DEV=[" . $matches[1] . "]  https://sys.ion63.ru/graph_vg_view.php?uid=" . $onu_row["uid"]);
								}else{
									//send_viber_msg(date('[H:i] ') ."BDCOM SKIP FIBER1 DOWN: ONU_IP=[" . $onu_row["onu_ipaddr"] . " MAC=[" . $matches["onu_mac"] . "] and  DEV=[" . $matches[1] . "]  https://sys.ion63.ru/graph_vg_view.php?uid=" . $onu_row["uid"], "+79377999153");
								}
							db_execute("UPDATE plugin_bdcom_onu SET `onu_wait_up`=0 WHERE onu_id='" . $onu_row["onu_id"] . "';");
						}
					}elseif ($matches[5] == "registered"){
						//check if change EPON and ONU name/number
						if ($onu_row["onu_name"] == $matches["onu_name"]) {
							$str_operstatus=1;
							$str_online=1 ;	
							db_execute("UPDATE plugin_bdcom_epons SET `epon_onu_active_total`=`epon_onu_active_total` + 1 WHERE epon_id='" . $onu_row["epon_id"] . "';");							
							db_execute("UPDATE plugin_bdcom_onu SET `onu_operstatus`='" . $str_operstatus . "', `onu_online`='" . $str_online . "', `onu_lastreg_date`='" . $onu_time . "', `onu_lastchange_date`='" . $onu_time . "' " . $str_degr_status . " WHERE onu_id='" . $onu_row["onu_id"] . "';");
							//cacti_log("BDCOM ERROR3 = " .  print_r($onu_row, true), false, "bdcom_er");
							//db_execute("UPDATE2 plugin_bdcom_onu SET `onu_operstatus`='" . $str_operstatus . "', `onu_online`='" . $str_online . "', `onu_lastreg_date`='" . $onu_time . "', `onu_lastchange_date`='" . $onu_time . "' " . $str_degr_status . " WHERE onu_id='" . $onu_row["onu_id"] . "';");
							//check onu UP action
							if ($onu_row["onu_up_action"] == "msg") {
								$str_sms = date('[H:i] ') ."ONU UP ip=" . $onu_row["onu_ipaddr"] . " MAC=" . $onu_row["onu_macaddr"] . "  Name=" . $onu_row["onu_name"] . "  !" ;
								send_viber_msg($str_sms, "+79377999153");
								db_execute("UPDATE plugin_bdcom_onu SET `onu_up_action`=''  WHERE onu_id='" . $onu_row["onu_id"] . "';");
							}elseif($onu_row["onu_up_action"] == "com"){
								//need commit after onu update
								//wait 10 sec to ONU full load
								sleep(10);
								//$device=bdcom_array_rekey(db_fetch_assoc("SELECT `d`.`description` as dev_name, d.*, o.*, dt.* FROM plugin_bdcom_onu o LEFT JOIN plugin_bdcom_devices d on (d.device_id=o.device_id) LEFT JOIN plugin_bdcom_dev_types dt on (d.device_type_id =dt.device_type_id) where d.device_id in (" . $onu["device_id"] . ") ;"), "device_id");        
								bdcom_commit_onu(array('diid' => $onu_row['onu_index'],'ids' => $onu_row['onu_id']),$device);

								$onu_new_soft_version = bdcom_snmp_get($device["hostname"], $device["snmp_get_community"],'.1.3.6.1.4.1.3320.101.10.1.1.5.' . $onu_row["onu_index"] , $device["snmp_get_version"],$device["snmp_get_username"],$device["snmp_get_password"],$device["snmp_get_auth_protocol"], $device["snmp_get_priv_passphrase"], $device["snmp_get_priv_protocol"],  $device["snmp_get_context"],$device["snmp_port"], $device["snmp_timeout"], $device["snmp_retries"], SNMP_WEBUI);
								$onu_new_soft_version = hexToStr(rtrim(str_replace(':', '', format_snmp_string($onu_new_soft_version, true)),0)) ;
								db_execute("UPDATE plugin_bdcom_onu SET `onu_soft_version`='" . $onu_new_soft_version . "'  WHERE onu_id='" . $onu_row["onu_id"] . "';");
							}
							if ($onu_row["onu_online"] == "0" and $onu_row["onu_dereg_status"] == "8" and $onu_row["onu_dis_alarm"] == "0" and $onu_row["onu_wait_up"] <> "1" and (read_config_option("bdcom_enable_msg_fiber") == "1" and $onu_row["onu_soft_version"] == "10.0.26B.554")) {
								//cacti_log("BDCOM ERROR31 = " .  print_r($onu_row, true), false, "bdcom_er");
								send_viber_msg(date('[H:i] ') ."BDCOM FIBER1 UP: ONU_IP=[" . $onu_row["onu_ipaddr"] . "] \r\nMAC=[" . $matches["onu_mac"] . "]and \r\n  DEV=[" . $matches[1] . "]  https://sys.ion63.ru/graph_vg_view.php?uid=" . $onu_row["uid"]);
							}							
						}else{
							bdcom_debug("BDCOM MOVE:  MAC=[" . $matches["onu_mac"] . "] and  DEV=[" . $matches[1] . "], is found on other PON NUMBER=[" . $matches[8] . "], ONU_ID=[" . $onu_row["onu_id"] . "].");					
							update_onu_record($matches, $device, $onu_row);
							
						}
					}else{
						// not correct
						exit;
					}
					
				}else{ //noo onu - may be new ONU
					$onu_row =  db_fetch_row ("SELECT * FROM plugin_bdcom_onu WHERE onu_macaddr='" . $matches["onu_mac"] . "';");
					if (sizeof($onu_row) > 0){
						// onu exist on other device
						bdcom_debug("BDCOM ERROR:  MAC=[" . $matches["onu_mac"] . "] and  DEV=[" . $matches[1] . "], is found on other device ID=[" . $onu_row["device_id"] . "], ONU_ID=[" . $onu_row["onu_id"] . "].");					
					}else{
						if ($matches[5] == "registered") {
							bdcom_debug("BDCOM : NEW ONU MAC=[" . $matches["onu_mac"] . "] and  DEV=[" . $matches[1] . "] and  NAME=[" . $matches["onu_name"] . "]. ");					
							//try to collect some info
							update_onu_record($matches, $device, null);
						}
					}
				
				}
		} //noo device - noo work
	} //no mac - no work


	
}

$bdcom_debug = $old_bdcom_debug;


function update_onu_record($syslog_row, $device, $cur_row = null){

	$a_onuMac = 	bdcom_xform_standard_indexed_data('.1.3.6.1.4.1.3320.101.10.1.1.3', $device);
	if (sizeof($a_onuMac) == 0) {
		sleep(10); //sleep 10 seconds 
		$a_onuMac = 	bdcom_xform_standard_indexed_data('.1.3.6.1.4.1.3320.101.10.1.1.3', $device);
	}
	if (sizeof($a_onuMac) > 0) {
		$onu_ifindex=0;
		foreach ($a_onuMac as $key => $onuMac) {
			$onuMac = trim($onuMac);
			$onuMac = str_replace(" ", ':', $onuMac);
			If (strtoupper($syslog_row["onu_mac"]) == strtoupper ($onuMac)){
				$onu_ifindex=$key;
			}
		}
		if ($onu_ifindex > 0){
			//correct IfIndex
			$onuBindEpon = 	bdcom_xform_standard_indexed_data('.1.3.6.1.4.1.3320.101.9.1.1.2.' . $onu_ifindex , $device);
			$onuDistance = 	bdcom_xform_standard_indexed_data('.1.3.6.1.4.1.3320.101.10.1.1.27.' . $onu_ifindex , $device);
			$onuBindEpon = (isset($onuBindEpon[$onu_ifindex]) ? $onuBindEpon[$onu_ifindex] : 0 );
			$onuDistance = (isset($onuDistance[$onu_ifindex]) ? $onuDistance[$onu_ifindex] : 0 );
			$onu_time=date('Y-m-d H:i:s', strtotime($syslog_row[2]));
			$str_sms="";
			if (is_null($cur_row)) {
				$insert_string = "INSERT INTO plugin_bdcom_onu (device_id, onu_sequence, onu_macaddr, onu_index,
						onu_bindepon, onu_name, onu_agrm_id, onu_adminstatus, onu_operstatus, onu_done_reason, 
						onu_distance,onu_txpower,onu_rxpower, 
						onu_online, onu_scan_date, onu_first_scan_date,onu_lastchange_date,onu_lastreg_date)  VALUES ";
				
				$insert_string .= "('" .
					$device["device_id"] . "','" .
					$syslog_row[9] . "','" .
					$syslog_row["onu_mac"] . "','" .
					$onu_ifindex . "','" .
					$onuBindEpon . "','" .
					$syslog_row["onu_name"] . "','" .
					"0" . "','" .				//onu_agrement
					"1" . "','" .
					"1" . "','" .
					"CRT" . "','" .				//onu_done_reason
					$onuDistance . "','" .
					"0" . "','" .				//onu_txpower
					"0" . "','" .				//onu_rxpower
					"1" . "','" .				//onu_online
					$onu_time . "','" .
					$onu_time . "','" .
					$onu_time . "','" .
					$onu_time . "')";
				db_execute($insert_string);	
				// need reindex on next poller run
				db_execute("UPDATE plugin_bdcom_devices SET `need_reindex` = '1' WHERE `device_id`='" . $device["device_id"] . "'");
				$str_sms="NEW ";

				
			}else{  //update
				db_execute(" UPDATE plugin_bdcom_onu  " .
					" SET plugin_bdcom_onu.onu_sequence='" . $syslog_row[9] . "', " .
					" plugin_bdcom_onu.onu_index='" . $onu_ifindex . "', " .
					" plugin_bdcom_onu.onu_bindepon='" . $onuBindEpon . "', " .
					" plugin_bdcom_onu.onu_name='" . $syslog_row["onu_name"] . "', " . 
					" plugin_bdcom_onu.onu_operstatus='1', " .
					" plugin_bdcom_onu.onu_done_view_count='0', " .
					" plugin_bdcom_onu.onu_done_reason='MOV', " .
					" plugin_bdcom_onu.onu_lastchange_date='" . $onu_time . "' " .
					" WHERE plugin_bdcom_onu.onu_id = '" . $cur_row["onu_id"] ."';");
				$str_sms="MOVE ";
			}

			
			db_execute(" UPDATE plugin_bdcom_onu  
				LEFT JOIN lb_equipment    ON LOWER(plugin_bdcom_onu.onu_macaddr) = LOWER(lb_equipment.mac) 
				LEFT JOIN (select * from (select max(record_id) as record_id from  lb_equip_history  group by equip_id) hlast left join lb_equip_history using(record_id))  lb_equip_history ON lb_equipment.equip_id = lb_equip_history.equip_id	
				LEFT JOIN (SELECT * FROM lb_vgroups_s WHERE lb_vgroups_s.id is null or lb_vgroups_s.id =1)  lbv  ON lb_equip_history.agrm_id = lbv.agrm_id	 
				LEFT JOIN (select lbs.* from lb_staff lbs right join (SELECT vg_id, max(segment) as segment from lb_staff group by vg_id) lbt  ON lbt.vg_id=lbs.vg_id and lbt.segment=lbs.segment) lb_staff  ON lbv.vg_id = lb_staff.vg_id 
				SET plugin_bdcom_onu.onu_agrm_id=lbv.agrm_id, plugin_bdcom_onu.onu_ipaddr=lb_staff.ip 
				WHERE LOWER(plugin_bdcom_onu.onu_macaddr) = LOWER('" . $syslog_row["onu_mac"] . "');");
			db_execute(" UPDATE plugin_bdcom_onu  
				LEFT JOIN plugin_bdcom_epons    ON plugin_bdcom_epons.epon_index = plugin_bdcom_onu.onu_bindepon
				SET plugin_bdcom_onu.epon_id=plugin_bdcom_epons.epon_id
				WHERE plugin_bdcom_epons.device_id = plugin_bdcom_onu.device_id;");	
			bdcom_debug("BDCOM: Start update_onu_record - bdcom_update_auto_create.");					
			bdcom_update_auto_create();

			sleep(15); //sleep 15 seconds to Tx and Rx power
			$onuRxPower = 	bdcom_xform_standard_indexed_data('.1.3.6.1.4.1.3320.101.10.5.1.5.' . $onu_ifindex , $device);
			$onuTxPower = 	bdcom_xform_standard_indexed_data('.1.3.6.1.4.1.3320.101.10.5.1.6.' . $onu_ifindex , $device);
			$onuRxPower = (isset($onuRxPower[$onu_ifindex]) ? $onuRxPower[$onu_ifindex] : 0 );
			$onuTxPower = (isset($onuTxPower[$onu_ifindex]) ? $onuTxPower[$onu_ifindex] : 0 );
			db_execute("UPDATE plugin_bdcom_onu SET `onu_txpower`='" . $onuTxPower . "', `onu_rxpower`='" . $onuRxPower . "', `onu_rxpower_min`='" . $onuRxPower . "', `onu_rxpower_max`='" . $onuRxPower . "', `onu_rxpower_average`='" . $onuRxPower . "'  WHERE LOWER(plugin_bdcom_onu.onu_macaddr) = LOWER('" . $syslog_row["onu_mac"] . "');");
			
			//check vlan
				$onu_row =  db_fetch_row ("SELECT * FROM plugin_bdcom_onu WHERE LOWER(onu_macaddr)=LOWER('" . $syslog_row["onu_mac"] . "') and `device_id`='" . $device["device_id"] . "';");
			$onuVlanid = bdcom_snmp_get($device["hostname"], $device["snmp_get_community"],'.1.3.6.1.4.1.3320.101.12.1.1.3.' . $onu_ifindex . '.1', $device["snmp_get_version"],$device["snmp_get_username"],$device["snmp_get_password"],$device["snmp_get_auth_protocol"], $device["snmp_get_priv_passphrase"], $device["snmp_get_priv_protocol"],  $device["snmp_get_context"],$device["snmp_port"], $device["snmp_timeout"], $device["snmp_retries"], SNMP_WEBUI);
			$onuVlanid = format_snmp_string($onuVlanid, true);
			
			$onuIPvlanid = bdcom_get_vlanid_by_ip($onu_row["onu_ipaddr"]);
			
			if ($onuIPvlanid != $onuVlanid) {
				$bol_changeVlan = bdcom_change_pvid_onu($onu_row, $device, $onuIPvlanid);
			}
			//check vlan on EPON port
			$eponVlanid = bdcom_snmp_get($device["hostname"], $device["snmp_get_community"],'.1.3.6.1.2.1.17.7.1.4.2.1.4.0.' . $onuIPvlanid , $device["snmp_get_version"],$device["snmp_get_username"],$device["snmp_get_password"],$device["snmp_get_auth_protocol"], $device["snmp_get_priv_passphrase"], $device["snmp_get_priv_protocol"],  $device["snmp_get_context"],$device["snmp_port"], $device["snmp_timeout"], $device["snmp_retries"], SNMP_WEBUI);
			$ar_eponVlanId = bdcom_convert_hex_to_view_string($eponVlanid);
			//$onu_row['onu_bindepon']

			
			$str_sms .= 'ONU ip=' . $onu_row["onu_ipaddr"] . ' rx=' . round($onuRxPower/10,1) ;
			if ($onuIPvlanid != $onuVlanid) {
				$str_sms .= '\n Change vlan to ' . $onuIPvlanid . ' rez=' . $bol_changeVlan; 
			}
			//search ifindex epon in vlan array 
			if (!in_array($onu_row['onu_bindepon'],$ar_eponVlanId['port_arr'])) {
				$str_sms .= '\n ERROR: NO vlanid ' . $onuIPvlanid . ' ON EPON!';
			}
			$str_sms .= ' !';
			send_viber_msg($str_sms, "+79377999153, +79377999152, +79631176333");
			//send_viber_msg($str_sms, "+79377999153");
	
		}else{
			bdcom_debug("BDCOM ERROR: NOT found IfIndex for ONU MAC=[" . $matches["onu_mac"] . "] and  DEV=[" . $matches[1] . "] and  NAME=[" . $matches["onu_name"] . "]. EXIT. ");					
		}
	}else{
		bdcom_debug("BDCOM ERROR: update_onu_record - sizeof(a_onuMac) = [" . sizeof($a_onuMac) . "]. EXIT. ");					
	}
						
	
}

?>
