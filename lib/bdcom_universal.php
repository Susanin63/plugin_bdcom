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
 
 
 /* register this functions scanning functions */
 if (!isset($bdcom_scanning_functions)) { $bdcom_scanning_functions = array(); }
 array_push($bdcom_scanning_functions, "scan_p3310C");
 
 
 /*	get_generic_switch_ports - This is a basic function that will scan the dot1d
   OID tree for all switch port to MAC address association and stores in the
   mac_track_temp_ports table for future processing in the finalization steps of the
   scanning process.
 */
 function scan_p3310C($device, $bdcom_debug = 0) {
 	global $scan_date;
 
	/* initialize port counters */
	$device['ports_total'] = 0;
	$device["ports_active"] = 0;
 	$device["epon_total"] = 0;
	$device["onu_Activetotal"] = 0;
    $ports_total=0;
    $ports_active=0;
    $onu_total=0;
	$epon_total=0;
    $store_to_db = TRUE;
	$bdcom_debug = 0;
	$vl = array();
 	
	
	$device["device_type_global"] =  db_fetch_row ("SELECT * FROM plugin_bdcom_dev_types WHERE device_type_id=" . $device["device_type_id"] . ";");
 	
 	/* get the ifIndexes for the device */
 	$ifIndexes = bdcom_xform_standard_indexed_data('.1.3.6.1.4.1.3320.9.64.4.1.1.1', $device);
 	//bdcom_debug("ifIndexes data collection complete" . sizeof($ifIndexes));
 
	
	$ifName = bdcom_xform_standard_indexed_data('.1.3.6.1.2.1.2.2.1.2', $device);
	
	$ifDescr = bdcom_xform_standard_indexed_data('.1.3.6.1.2.1.31.1.1.1.18', $device);
	
	$ifAdminStates = bdcom_array_strip_non_digital(bdcom_xform_standard_indexed_data('.1.3.6.1.2.1.2.2.1.7', $device));
	
	$ifOperStates = bdcom_array_strip_non_digital(bdcom_xform_standard_indexed_data('.1.3.6.1.2.1.2.2.1.8', $device));
	
	$ifTypes = bdcom_xform_standard_indexed_data('.1.3.6.1.4.1.3320.9.64.4.1.1.5', $device);
	

    $ifInterfaces=array();
	$ifPorts=array();
	$ifEpons=array();
	$ifOnus=array();
 
     foreach($ifIndexes as $ifIndex) {
         $ifInterfaces[$ifIndex]["ifIndex"] = $ifIndex;
         $ifInterfaces[$ifIndex]["ifDescr"] = @$ifDescr[$ifIndex];
		 $ifInterfaces[$ifIndex]["ifName"] = @$ifName[$ifIndex];
         if (isset($ifOperStates[$ifIndex])) {
             $ifInterfaces[$ifIndex]["ifOperState"] = $ifOperStates[$ifIndex];
         } else {
             $ifInterfaces[$ifIndex]["ifOperState"] = '0';
         }

         if (isset($ifAdminStates[$ifIndex])) {
             $ifInterfaces[$ifIndex]["ifAdminState"] = $ifAdminStates[$ifIndex];
         } else {
             $ifInterfaces[$ifIndex]["ifAdminState"] = '0';
         }

         if (isset($ifTypes[$ifIndex])) {
             $ifInterfaces[$ifIndex]["ifType"] = $ifTypes[$ifIndex];
				if (preg_match("/EPON0\/([1-9]{1,2})$/i", $ifName[$ifIndex], $rez)) {
					$ifEpons[]["ifIndex"]=$ifIndex;
					$ifInterfaces[$ifIndex]["ifNumber"] = $rez[1];
				} elseif (preg_match("/EPON0\/[1-9]{1,2}\:(\d{1,2})$/i", $ifName[$ifIndex], $rez)) {
					$ifOnus[]["ifIndex"]=$ifIndex;
					$ifInterfaces[$ifIndex]["ifNumber"] = $rez[1];
				} elseif (preg_match("/GigaEthernet0\/([1-9]{1,2})$/i", $ifName[$ifIndex], $rez)) {
					$ifPorts[]["ifIndex"]=$ifIndex;
					$ifInterfaces[$ifIndex]["ifNumber"] = $rez[1];
				} 
         } else {
             $ifInterfaces[$ifIndex]["ifType"] = '0';
         }
		 
     }    
    // bdcom_debug("ifInterfaces assembly complete. - " . sizeof($ifInterfaces));
	foreach($ifPorts as $key => $ifPort) {
		$ifPorts[$key]["ifNumber"] = $ifInterfaces[$ifPort["ifIndex"]]["ifNumber"];
		$ifPorts[$key]["descr"] = $ifInterfaces[$ifPort["ifIndex"]]["ifDescr"];
		$ifPorts[$key]["name"] = $ifName[$ifPort["ifIndex"]];
		$ifPorts[$key]["ifAdminState"] = $ifAdminStates[$ifPort["ifIndex"]];
		$ifPorts[$key]["ifOperState"] = $ifOperStates[$ifPort["ifIndex"]];
		$ifPorts[$key]["ifType"] = $ifTypes[$ifPort["ifIndex"]];
	}
    

	if (count($ifEpons) > 0){
		$eponCurActiveOnuCount = 	bdcom_xform_standard_indexed_data('.1.3.6.1.4.1.3320.101.6.1.1.21', $device);
		$eponCurInActiveOnuCount = 	bdcom_xform_standard_indexed_data('.1.3.6.1.4.1.3320.101.6.1.1.22', $device);
		$eponOnuIfindexString = 	bdcom_xform_standard_indexed_data('.1.3.6.1.4.1.3320.101.6.1.1.23', $device);
		$eponLinkStatus = 	bdcom_xform_standard_indexed_data('.1.3.6.1.4.1.3320.101.107.1.2', $device); //1-link up, 2-link down.
		$eponTxPower = 	bdcom_xform_standard_indexed_data('.1.3.6.1.4.1.3320.101.107.1.3', $device); // The unit is 0.1DBm.
		$eponTemper = 	bdcom_xform_standard_indexed_data_usdbin('.1.3.6.1.4.1.3320.101.107.1.6', $device); // The unit is 1/256 degree.
		$eponVoltage = 	bdcom_xform_standard_indexed_data_usdbin('.1.3.6.1.4.1.3320.101.107.1.7', $device); // The unit is 0.1mV.
		
	}
	
	foreach($ifEpons as $key => $ifEpon) {
		$ifEpons[$key]["ifNumber"] = $ifInterfaces[$ifEpon["ifIndex"]]["ifNumber"];
		$ifEpons[$key]["descr"] = $ifInterfaces[$ifEpon["ifIndex"]]["ifDescr"];
		$ifEpons[$key]["name"] = $ifName[$ifEpon["ifIndex"]];
		$ifEpons[$key]["CurActiveOnuCount"] = $eponCurActiveOnuCount[$ifEpon["ifIndex"]];
		$device["onu_Activetotal"] = $device["onu_Activetotal"] + $ifEpons[$key]["CurActiveOnuCount"];
		$ifEpons[$key]["CurInActiveOnuCount"] = $eponCurInActiveOnuCount[$ifEpon["ifIndex"]];
		$ifEpons[$key]["IfindexString"] = $eponOnuIfindexString[$ifEpon["ifIndex"]];
		$ifEpons[$key]["ifAdminState"] = $ifAdminStates[$ifEpon["ifIndex"]];
		$ifEpons[$key]["ifOperState"] = $ifOperStates[$ifEpon["ifIndex"]];
		$ifEpons[$key]["LinkStatus"] = $eponLinkStatus[$ifEpon["ifIndex"]];
		$ifEpons[$key]["TxPower"] = $eponTxPower[$ifEpon["ifIndex"]];
		$ifEpons[$key]["Temper"] = $eponTemper[$ifEpon["ifIndex"]]/256;
		$ifEpons[$key]["Voltage"] = $eponVoltage[$ifEpon["ifIndex"]]*0.1;
	}

	if (count($ifOnus) > 0){
		$onuVendor = 	bdcom_xform_standard_indexed_data('.1.3.6.1.4.1.3320.101.10.1.1.1', $device);
		$onuVersion = 	bdcom_xform_standard_indexed_data('.1.3.6.1.4.1.3320.101.10.1.1.2', $device);
		$onuSoftVersion=str_replace(" ", ":", bdcom_xform_standard_indexed_data('.1.3.6.1.4.1.3320.101.10.1.1.5', $device));
		$onuMac = 		str_replace(" ", ":", bdcom_xform_standard_indexed_data('.1.3.6.1.4.1.3320.101.10.1.1.3', $device));
		$onuMac1 = 		bdcom_xform2_standard_indexed_data('.1.3.6.1.4.1.3320.101.10.1.1.3', $device);
		$onuBindEpon = 	bdcom_xform_standard_indexed_data('.1.3.6.1.4.1.3320.101.9.1.1.2', $device);
		$onuBindSequenceNo = 	bdcom_xform_standard_indexed_data('.1.3.6.1.4.1.3320.101.9.1.1.3', $device);
		$onuStatus = 	bdcom_xform_standard_indexed_data('.1.3.6.1.4.1.3320.101.10.1.1.26', $device);  //authenticated(0), registered(1),deregistered(2),auto_config(3),lost(4),standby(5)
		$onuDistance = 	bdcom_xform_standard_indexed_data('.1.3.6.1.4.1.3320.101.10.1.1.27', $device);
		//$onuRTT = 		bdcom_xform_standard_indexed_data('.1.3.6.1.4.1.3320.101.11.1.1.8.7', $device);
		$onuAliveTime = bdcom_xform_standard_indexed_data('.1.3.6.1.4.1.3320.101.10.1.1.80', $device);
		//$onuTxPower1 = bdcom_xform_standard_indexed_data('.1.3.6.1.4.1.3320.101.108.1.3', $device);
		$onuTemper = bdcom_xform_standard_indexed_data('.1.3.6.1.4.1.3320.101.10.5.1.2', $device);
		$onuRxPower = bdcom_xform_standard_indexed_data('.1.3.6.1.4.1.3320.101.10.5.1.5', $device);  //nit is 0.1dB.
		$onuTxPower = bdcom_xform_standard_indexed_data('.1.3.6.1.4.1.3320.101.10.5.1.6', $device);  //nit is 0.1dB.
		$onuDeregDescr = 	bdcom_array_strip_dereg_descr(xform_standard_indexed_data_oid('.1.3.6.1.4.1.3320.101.11.1.1.11', $device)); //ONU binding last deregister reason. normal(2), mpcp-down(3), oam-down(4), firmware-download(5), illegal-mac(6) ,llid-admin-down(7) , wire-down(8) , power-off(9) ,unknow(255) 
	}

	$device["onu_online_total"] = 0;
	foreach($ifOnus as $key => $ifOnu) {
		$ifOnus[$key]["ifNumber"] = $ifInterfaces[$ifOnu["ifIndex"]]["ifNumber"];
		$ifOnus[$key]["name"] = $ifName[$ifOnu["ifIndex"]];
		$ifOnus[$key]["descr"] = $ifInterfaces[$ifOnu["ifIndex"]]["ifDescr"];
		$ifOnus[$key]["vendor"] = $onuVendor[$ifOnu["ifIndex"]];
		$ifOnus[$key]["version"] = $onuVersion[$ifOnu["ifIndex"]];
		$ifOnus[$key]["soft_version"] = hexToStr(rtrim(str_replace(':', '', $onuSoftVersion[$ifOnu["ifIndex"]]),0)) ;
		$ifOnus[$key]["mac"] = $onuMac[$ifOnu["ifIndex"]];
		$ifOnus[$key]["bindepon"] = $onuBindEpon[$ifOnu["ifIndex"]];
		$ifOnus[$key]["sequence"] = $onuBindSequenceNo[$ifOnu["ifIndex"]];
		$ifOnus[$key]["ifAdminState"] = $ifAdminStates[$ifOnu["ifIndex"]];
		$ifOnus[$key]["ifOperState"] = $ifOperStates[$ifOnu["ifIndex"]];
		if ($ifOnus[$key]["ifOperState"] == 1) {
			$device["onu_online_total"] = $device["onu_online_total"]+1;
		}
		$ifOnus[$key]["distance"] = $onuDistance[$ifOnu["ifIndex"]];
		$ifOnus[$key]["alive_time"] = $onuAliveTime[$ifOnu["ifIndex"]];
		$ifOnus[$key]["TxPower1"] = (isset($eponTxPower1[$ifOnu["ifIndex"]]) ? $eponTxPower1[$ifOnu["ifIndex"]] : null);
		$ifOnus[$key]["temper"] = $onuTemper[$ifOnu["ifIndex"]]/256;
		$ifOnus[$key]["rx_power"] = (isset($onuRxPower[$ifOnu["ifIndex"]]) ? $onuRxPower[$ifOnu["ifIndex"]] : 0);		
		$ifOnus[$key]["tx_power"] = (isset($onuTxPower[$ifOnu["ifIndex"]]) ? $onuTxPower[$ifOnu["ifIndex"]] : 0);
		$ifOnus[$key]["onuDeregDescr"] = (isset($onuDeregDescr[$ifOnus[$key]["bindepon"] . "." . bdcom_mac_16_to_10($ifOnus[$key]["mac"])]) ? $onuDeregDescr[$ifOnus[$key]["bindepon"] . "." . bdcom_mac_16_to_10($ifOnus[$key]["mac"])] : "255");
	}
	
	
 
     $device["ports_total"] = count($ifPorts);
     $device["epon_total"] = count($ifEpons);
	 $device["onu_total"] = count($ifOnus);

     if ($store_to_db) {
         if (sizeof($ifInterfaces) <= 0) {
             $device["last_runmessage"] = "Data collection completed ok with no active ports";
             db_execute("UPDATE plugin_bdcom_ports SET ports_total=0 WHERE device_id=" . $device["device_id"]);
         }elseif (sizeof($ifInterfaces) > 0) {
             $device["last_runmessage"] = "Data collection completed ok";
			if (sizeof($ifPorts)>0) {
				bdcom_db_store_device_port_results($device, $ifPorts, $scan_date);
			}
			if (sizeof($ifEpons)>0) {
				bdcom_db_store_device_epon_results($device, $ifEpons, $scan_date);
			}
			if (sizeof($ifOnus)>0) {
				if ($device["onu_online_total"] > 0) {
					bdcom_db_store_device_onu_results($device, $ifOnus, $scan_date);
				}
				bdcom_db_store_device_onu_off_results($device, $ifOnus, $scan_date);
				bdcom_alert_rxpower_change($device);
			}			
			if (sizeof($vl)>0) {
				bdcom_db_store_vlans_results($device, $vl, $scan_date);
            }
 
              
         }
 
         if(!$bdcom_debug) {
             print(" - Complete\n");
         }
     }
 
      return $device;
 
 }


 ?>
