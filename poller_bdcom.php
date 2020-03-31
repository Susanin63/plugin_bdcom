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
 if (!isset($_SERVER["argv"][0])) {
 //	die("<br><strong>This script is only meant to run at the command line.</strong>");
 }
 
 /* We are not talking to the browser */
 $no_http_headers = true;
 
 $dir = dirname(__FILE__);
 chdir($dir);
 
 if (strpos($dir, 'bdcom') !== false) {
 	chdir('../../');
 }
 
 /* Start Initialization Section */
 include("./include/global.php");
 include_once($config["base_path"] . "/lib/poller.php");
 include_once($config["base_path"] . "/plugins/bdcom/lib/bdcom_functions.php");
 
 
 
 /* get the max script runtime and kill old scripts. Удаляються все процессы, который работают больше положенного срока (5 минут)*/
 
 $max_script_runtime = read_config_option("bdcom_collection_timing");
 if (is_numeric($max_script_runtime)) {
     /* let PHP a 5 minutes less than the rerun frequency */
     $max_run_duration = ($max_script_runtime * 60) ;
     //$max_run_duration = ($max_run_duration * 60) - 300;
     ini_set("max_execution_time", $max_run_duration);
 }
 
 $delete_time = date("Y-m-d H:i:s", strtotime("-" . $max_script_runtime . " Minutes"));
 db_execute("delete from plugin_bdcom_processes where start_date < '" . $delete_time . "'");
 
 /* Disable Mib File Loading */
 putenv("MIBS=RFC-1215");

 	if (read_config_option("bdcom_use_camm_syslog") == "on") {
		bdcom_poller_process_camm_syslog(0);
	}elseif(read_config_option("bdcom_use_snmptt_plugin") == "on"){
		process_snmptt_traps(0);
		//cimpb_poller_process_camm_traps($device_id_arr, $str_devices_id);
	}
			
 
 if (read_config_option("bdcom_collection_timing") != "disabled" ||  true) {
 	/* initialize variables */
 	$site_id = "";
 
 	/* process calling arguments */
 	$parms = $_SERVER["argv"];
 	array_shift($parms);
 
 	$debug = FALSE;
 	$forcerun = FALSE;
    $device_id=0;
    print_r($parms); 
 	if (sizeof($parms) > 0 ){
		foreach($parms as $parameter) {
			@list($arg, $value) = @explode("=", $parameter);
	 
			switch ($arg) {
			 case "-id":
				 $device_id = $value;
				 break;
			case "-sid":
				$site_id = $value;
				break;
			case "-d":
				$debug = TRUE;
				break;
			case "-h":
				display_help();
				exit;
			case "-f":
				$forcerun = TRUE;
				break;
			case "-v":
				display_help();
				exit;
			case "--version":
				display_help();
				exit;
			case "--help":
				display_help();
				exit;
			default:
				print "ERROR: Invalid Parameter " . $parameter . "\n\n";
				display_help();
				exit;
			}
		}
	}
 	bdcom_debug("About to enter BDCOM poller processing");
 	$seconds_offset = read_config_option("bdcom_collection_timing");
 	if (($seconds_offset <> "disabled" || true) || $forcerun) {
 		bdcom_debug("Into Processing.  Checking to determine if it's time to run.");
 		$seconds_offset = $seconds_offset * 60;
 		/* find out if it's time to collect device information */
 		$base_start_time = read_config_option("bdcom_base_time");
 		$last_run_time = read_config_option("bdcom_last_run_time");
 		$previous_base_start_time = read_config_option("bdcom_prev_base_time");
 		
		/* see if the user desires a new start time */
		bdcom_debug("Checking if user changed the start time");
		if (!empty($previous_base_start_time)) {
			if ($base_start_time <> $previous_base_start_time) {
				bdcom_debug("Detected that user changed the start time\n");
				unset($last_run_time);
				db_execute("DELETE FROM settings WHERE name='bdcom_last_run_time'");
			}
		}
 
 			/* set to detect if the user cleared the time between polling cycles */
			db_execute("REPLACE INTO settings (name, value) VALUES ('bdcom_prev_base_time', '$base_start_time')");
 
 
			/* determine the next start time */
			$current_time = strtotime("now");
			if (empty($last_run_time)) {
				if ($current_time > strtotime($base_start_time)) {
					/* if timer expired within a polling interval, then poll */
					if (($current_time - 300) < strtotime($base_start_time)) {
						$next_run_time = strtotime(date("Y-m-d") . " " . $base_start_time);
					}else{
						$next_run_time = strtotime(date("Y-m-d") . " " . $base_start_time) + 3600*24;
					}
				}else{
					$next_run_time = strtotime(date("Y-m-d") . " " . $base_start_time);
				}
			}else{
				$next_run_time = $last_run_time + $seconds_offset;
			}
			$time_till_next_run = $next_run_time - $current_time;

			if ($time_till_next_run < 0) {
				bdcom_debug("The next run time has been determined to be NOW");
			}else{
				bdcom_debug("The next run time has been determined to be at '" . date("Y-m-d G:i:s", $next_run_time) . "'");
			}			
			
			
			
			
			if ($time_till_next_run < 0 || $forcerun == TRUE) {
				bdcom_debug("Either a scan has been forced, or it's time to check for BDCOM");
					/* take time and log performance data */
	 
				list($micro,$seconds) = explode(" ", microtime());
				$start = $seconds + $micro;
				$current_time = strtotime("now");
				 
	 
				db_execute("REPLACE INTO settings (name, value) VALUES ('bdcom_last_run_time', '$current_time')");
				$running_processes = db_fetch_cell("SELECT count(*) FROM plugin_bdcom_processes");
	 
				if ($running_processes) {
					cacti_log("ERROR: Can not start bdcom process.  There is already one in progress", TRUE);
					 print ("ERROR: Can not start bdcom poller process.  There is already one in progress");
				}else{
				  db_execute("REPLACE INTO settings (name, value) VALUES ('bdcom_bdcom_finish', '0')");
				   collect_bdcom_data($start, $site_id, $device_id);
					 
					db_execute("REPLACE INTO settings (name, value) VALUES ('bdcom_bdcom_finish', '1')");
					log_bdcom_statistics("collect");
					//update_banip_records();

					if (read_config_option("bdcom_use_camm_syslog") == "on") {
						//bdcom_poller_process_camm_syslog($device_id);
					}elseif(read_config_option("bdcom_use_snmptt_plugin") == "on"){
						//process_snmptt_traps($device_id);
					}				
					//run autoupdate process
					//bdcom_autoupdate_22b();
					bdcom_autoupdate_26b();
				}
			}
			
			//update uzel stat
			db_execute ('UPDATE `plugin_bdcom_uzel` SET `uzel_exist`=0;');
			// stat all onu
			db_execute ('INSERT INTO plugin_bdcom_uzel (uzel_id, uzel_descr, uzel_onu_total, epon_id, uzel_exist) 
							SELECT onu_us_enduzelid, onu_us_enduzel_descr, count(*) cnt, epon_id, "1" FROM plugin_bdcom_onu group by onu_us_enduzelid
							ON DUPLICATE KEY UPDATE
							uzel_descr=VALUES(uzel_descr),
							uzel_onu_total=VALUES(uzel_onu_total),
							epon_id=VALUES(epon_id),
							uzel_exist=VALUES(uzel_exist);');
			// stat UP onu
			db_execute ('INSERT INTO plugin_bdcom_uzel (uzel_id, uzel_descr, uzel_onu_up, epon_id, uzel_exist) 
							SELECT onu_us_enduzelid, onu_us_enduzel_descr, count(*) cnt, epon_id, "1" FROM plugin_bdcom_onu WHERE onu_online=1 group by onu_us_enduzelid
							ON DUPLICATE KEY UPDATE
							uzel_descr=VALUES(uzel_descr),
							uzel_onu_up=VALUES(uzel_onu_up),
							epon_id=VALUES(epon_id),
							uzel_exist=VALUES(uzel_exist);');		
 		
 
 	}
 }
 
 /*	display_help - displays the usage of the function */
 function display_help () {
 	print "D-Link IP_Mac-Port Blinding Master Process Control Version 1.0, Copyright 2005 - Larry Adams\n\n";
 	print "usage: poller_bdcom.php [-d] [-h] [--help] [-v] [--version]\n\n";
 	print "-f            - Force the execution of a collection process\n";
 	print "-d            - Display verbose output during execution\n";
 	print "-v --version  - Display this help message\n";
 	print "-h --help     - display this help message\n";
 }
 
 function collect_bdcom_data($start, $site_id = 0, $only_device) {
 	global $max_run_duration, $config, $debug;
 	/* reset the processes table */
 	//if ($only_device == 0) { /*Если запущен процесс сканирования всех устройств, то удаляем данные со всех таблиц, кроме блоков (что бы сохранить информацию о времени появления записи о блоке*/
 	  // db_execute("TRUNCATE TABLE bdcom_temp_macip");
 	  // db_execute("TRUNCATE TABLE bdcom_temp_blmacs");
      // db_execute("TRUNCATE TABLE bdcom_temp_blmacinfo");
    // } else {
        // db_execute("DELETE bdcom_temp_macip.* FROM imb_temp_macip where device_id='" . $only_device . "'");
       // db_execute("DELETE bdcom_temp_blmacinfo.* FROM imb_temp_blmacinfo left join imb_temp_blmacs on imb_temp_blmacinfo.blmacinfo_info_id = imb_temp_blmacs.blmac_id where device_id='" . $only_device . "'");
        // db_execute("DELETE bdcom_temp_blmacs.* FROM imb_temp_blmacs where device_id='" . $only_device . "'");
    // }
     /* dns resolver binary */
 
 	/* get php binary path */
 	$command_string = read_config_option("path_php_binary");
 
 	/* save the scan date information */
 	$scan_date = date("Y-m-d H:i:s");
 	db_execute("REPLACE INTO settings (name, value) VALUES ('bdcom_scan_date', '$scan_date')");
 
 	/* just in case we've run too long */
 	$exit_bdcom = FALSE;
 
 	/* start mainline processing, order by site_id to keep routers grouped with switches */
 	//$device_ids = db_fetch_assoc("SELECT device_id FROM plugin_bdcom_devices WHERE disabled='' ORDER BY device_id");
 
     if ($site_id > 0) {
         $device_ids = db_fetch_assoc("SELECT device_id FROM plugin_bdcom_devices WHERE site_id='" . $site_id . "' and disabled='' ORDER BY device_id");
     }else{
         if ($only_device > 0) {
             $device_ids = db_fetch_assoc("SELECT device_id FROM plugin_bdcom_devices WHERE disabled='' and device_id='" . $only_device . "' ORDER BY device_id");
         } else {
             $device_ids = db_fetch_assoc("SELECT device_id FROM plugin_bdcom_devices WHERE disabled='' ORDER BY device_id");
         }
     }
     //db_execute("REPLACE INTO settings (name, value) VALUES ('imb_test', '" . $only_device . "')"); 
 
    $total_devices = sizeof($device_ids);
 
 	$concurrent_processes = read_config_option("bdcom_processes");
 
 	if ($debug == TRUE) {
 		$e_debug = " -d";
 	}else{
 		$e_debug = "";
 	}
 
 	/* add the parent process to the process list */
 	if ($total_devices > 0) {
		bdcom_db_process_add("-1");
 		/* scan through all devices */
 		$j = 0;
 		$i = 0;
 		$last_time = strtotime("now");
 		$processes_available = $concurrent_processes;
 		while ($j < $total_devices) {
 			/* retreive the number of concurrent mac_track processes to run */
 			/* default to 10 for now */
 			$concurrent_processes = db_fetch_cell("SELECT value FROM settings WHERE name='bdcom_processes'");
 
 			for ($i = 0; $i < $processes_available; $i++) {
 				if (($j+$i) >= $total_devices) break;
 
 				$extra_args = " -q " . $config["base_path"] . "/plugins/bdcom/bdcom_scanner.php -id=" . $device_ids[$i+$j]["device_id"] . $e_debug;
 				//bdcom_debug("ppp------>>CMD: " . $command_string . $extra_args);
 				exec_background($command_string, $extra_args);
 			}
 			$j = $j + $i;
 
 
 			bdcom_debug("A process cycle launch just completed.");
 			
 
 			/* wait the correct number of seconds for proccesses prior to
 			   attempting to update records */
 			sleep(2);
 			$current_time = strtotime("now");

 
 			$processes_running = db_fetch_cell("SELECT count(*) FROM plugin_bdcom_processes");
 
 
 			/* take time to check for an exit condition */
 			list($micro,$seconds) = explode(" ", microtime());
 			$current = $seconds + $micro;
 
 			/* exit if we've run too long */
 			if (($current - $start) > $max_run_duration) {
 				$exit_bdcom = TRUE;
 				cacti_log("ERROR: BDCOM timed out during main script processing.\n");
 				bdcom_db_process_remove("-1");
 				break;
 			}
 		}
 
 		/* wait for last process to exit */
 		$processes_running = db_fetch_cell("SELECT count(*) FROM plugin_bdcom_processes WHERE device_id > 0");
 		while (($processes_running > 0) && (!$exit_bdcom)) {
 			$processes_running = db_fetch_cell("SELECT count(*) FROM plugin_bdcom_processes WHERE device_id > 0");
 
 			/* wait the correct number of seconds for proccesses prior to
 			   attempting to update records */
 			sleep(2);
 
 			/* take time to check for an exit condition */
 			list($micro,$seconds) = explode(" ", microtime());
 			$current = $seconds + $micro;
 
 			/* exit if we've run too long */
 			if (($current - $start) > $max_run_duration) {
 				$exit_bdcom = TRUE;
 				cacti_log("ERROR: BDCOM timed out during main script processing.\n");
 				break;
 			}
 
 			bdcom_debug("Waiting on " . $processes_running . " to complete prior to exiting.");
 		}
 
 
 		/* let the resolver know that the parent process is finished and then wait
 		   for the resolver if applicable */
 		bdcom_db_process_remove("-1");
 		
 
		$processes_running_1 = db_fetch_cell("SELECT count(*) FROM plugin_bdcom_processes WHERE device_id > 0");
		bdcom_debug("ppp------>> ALL FINISH! START transferring scan results to main table. Count processes=" . $processes_running_1 . "] [" . db_fetch_cell("SELECT device_id FROM mac_track_processes WHERE device_id > 0") );
		// update agrm_id and ip
		db_execute(" UPDATE plugin_bdcom_onu  
		LEFT JOIN lb_equipment    ON LOWER(plugin_bdcom_onu.onu_macaddr) = LOWER(lb_equipment.mac) 
		LEFT JOIN (select * from (select max(record_id) as record_id from  lb_equip_history  group by equip_id) hlast left join lb_equip_history using(record_id))  lb_equip_history ON lb_equipment.equip_id = lb_equip_history.equip_id	
		LEFT JOIN (SELECT * FROM lb_vgroups_s WHERE lb_vgroups_s.id is null or lb_vgroups_s.id =1)  lbv  ON lb_equip_history.agrm_id = lbv.agrm_id	 
		LEFT JOIN (select lbs.* from lb_staff lbs right join (SELECT vg_id, max(segment) as segment from lb_staff group by vg_id) lbt  ON lbt.vg_id=lbs.vg_id and lbt.segment=lbs.segment) lb_staff ON lbv.vg_id = lb_staff.vg_id 
		SET plugin_bdcom_onu.onu_agrm_id=lbv.agrm_id, plugin_bdcom_onu.onu_ipaddr=lb_staff.ip 
		WHERE LOWER(plugin_bdcom_onu.onu_macaddr) = LOWER(lb_equipment.mac);");
		
		db_execute(" UPDATE plugin_bdcom_onu  
		LEFT JOIN plugin_bdcom_epons    ON plugin_bdcom_epons.epon_index = plugin_bdcom_onu.onu_bindepon
		SET plugin_bdcom_onu.epon_id=plugin_bdcom_epons.epon_id
		WHERE plugin_bdcom_epons.device_id = plugin_bdcom_onu.device_id;");
		
		bdcom_update_auto_create();
	
		//re-index 
		$reind_devices =  db_fetch_assoc ("SELECT h.id, bd.device_id FROM plugin_bdcom_devices bd left JOIN host   h  ON bd.hostname = h.hostname WHERE bd.need_reindex = 1;");				
			if (sizeof($reind_devices) > 0){
				foreach($reind_devices as $dev) {
					$command_string = read_config_option("path_php_binary");
					$extra_args = " -q " . $config["base_path"] . "/cli/poller_reindex_hosts.php --id=" . $dev["id"];
					exec($command_string . " " . $extra_args . " &", $out);	
					db_execute("UPDATE plugin_bdcom_devices SET `need_reindex` = '0' WHERE `device_id`='" . $dev["device_id"] . "'");				
				}
			}
	
		bdcom_create_graphs();
	
	
	
		if ($only_device > 0) {
				//db_store_imp_log("Завершен процесс опроса устройства [" . $only_device . "]", "device", $only_device, "poll",$only_device, !$exit_bdcom, !$exit_bdcom, !$exit_bdcom, !$exit_bdcom);
			}
         
 	}else{
		cacti_log('ERROR: Can not start BDCOM Scan process.  NO Devices with ID=[' . $only_device . '] found!', TRUE);		
	}
 }
 function log_bdcom_statistics($type = "collect") {
 	global $start;
 
 	/* let's get the number of devices */
 		$devices = db_fetch_cell("SELECT Count(*) FROM plugin_bdcom_devices");
 		$epons = db_fetch_cell("SELECT Count(*) FROM plugin_bdcom_epons");
		$ports = db_fetch_cell("SELECT Count(*) FROM plugin_bdcom_ports");
 		$onus = db_fetch_cell("SELECT Count(*) FROM plugin_bdcom_onu");
 		$Active_onus = db_fetch_cell("SELECT Count(*) FROM plugin_bdcom_onu where onu_operstatus = 1");
 
 	$concurrent_processes = read_config_option("bdcom_processes");
 
 	/* take time and log performance data */
 	list($micro,$seconds) = explode(" ", microtime());
 	$end = $seconds + $micro;
 
 
 		$imb_stats_general = sprintf(
 			"Time:%01.4f " .
 			"ConcurrentProcesses:%s " .
 			"Devices:%s ",
 			round($end-$start,4),
 			$concurrent_processes,
 			$devices);
 		/* log to the database */
 		db_execute("REPLACE INTO settings (name,value) VALUES ('bdcom_stats_general', '" . $imb_stats_general . "')");
 		$imb_stats = sprintf(
 			"epons:%s " .
 			"ports:%s " .
			"onus:%s " .
 			"active_onus:%s " ,      			
 			$epons,
 			$ports,
			$onus,
 			$Active_onus);
 		/* log to the database */
 		db_execute("REPLACE INTO settings (name,value) VALUES ('bdcom_stats', '" . $imb_stats . "')");
 		/* log to the logfile */
 		cacti_log("bdcom STATS: " . $imb_stats_general . "; " . $imb_stats ,true,"SYSTEM");
 		
 
 }
 

 function  process_snmptt_traps($device_id = 0) {
 
 	// if ($device_id == 0) { /*Если запущен процесс сканирования всех устройств, то удаляем данные со всех таблиц, кроме блоков (что бы сохранить информацию о времени появления записи о блоке*/
 		// db_execute("TRUNCATE TABLE imb_traps_blocked");
     // } else {
         // $ipaddr = db_fetch_cell("SELECT `hostname` FROM `plugin_bdcom_devices` where `device_id` = '" . $only_device . "';");
 		// db_execute("DELETE imb_traps_blocked.* FROM imb_traps_blocked where device_id='" . $ipaddr . "'");
     // }
	 
	//db_execute("DELETE imb_traps_blocked.* FROM imb_traps_blocked where device_id='" . $ipaddr . "'");
	
 	$evenids = db_fetch_assoc("SELECT distinct `plugin_bdcom_dev_types`.`snmp_oid_Trap_eventid` FROM `plugin_bdcom_devices` " .
 					" left join `plugin_bdcom_dev_types` on `plugin_bdcom_devices`.`device_type_id`=`plugin_bdcom_dev_types`.`device_type_id` " .
 					" where `plugin_bdcom_devices`.`disabled` = ''");
 	$str_eventids = "";
 	if (sizeof($evenids)) {
 		foreach($evenids as $key => $evenid) {
 			$str_eventids = $str_eventids . "'" . $evenid["snmp_oid_Trap_eventid"] . "', ";
 		}
 		$str_eventids = substr($str_eventids, 0, strlen($str_eventids) -2);
 		$traps=db_fetch_assoc("SELECT * FROM plugin_camm_snmptt where traptime > '" .  read_config_option("bdcom_scan_date"). "' and " .
 		//$traps=db_fetch_assoc("SELECT * FROM plugin_camm_snmptt where traptime > '2008-03-16 14:42:06' and " .
 			" eventid in (" . $str_eventids . ") " .
 			" order by traptime;");
 		if (sizeof($traps)) {
 			$sql_replace = "REPLACE INTO `imb_traps_blocked` (`traps_hostname`,`traps_time`,`traps_macaddr`,`traps_ipaddr`,`traps_port`) VALUES " ;
			foreach($traps as $key => $trap) {
 			$matches = array();
 			preg_match("/IP:\ *([0-9.]*),\ *MAC\:\ *([0-9a-fA-F\:]*),\ *Port\:\ *([0-9]{1,2})/", $trap["formatline"], $matches);
 			//preg_match("/\[port=([0-9]{1,2})\ *ip\=([0-9\.]*)\ *mac\=([0-9a-fA-F\:]*)\]/", $trap["formatline"], $matches);
 				if (sizeof($matches)) {
 					$sql_replace .= " ('" . $trap["hostname"] . "','" . $trap["traptime"] . "','" . $matches[2] . "','" . $matches[1] . "','" . $matches[3] . "'),";
 				}
 			}
			$sql_replace = substr($sql_replace, 0, strlen($sql_replace) - 1);
			$sql_replace .= ";";
			db_execute($sql_replace);
			db_execute("UPDATE `imb_traps_blocked`,`plugin_bdcom_devices` SET `imb_traps_blocked`.`traps_device_id`=`plugin_bdcom_devices`.`device_id` " .
 					"WHERE `imb_traps_blocked`.`traps_hostname`=`plugin_bdcom_devices`.`hostname`; ");
 		
 			
 		}
 	}
 	db_execute("UPDATE imb_blmacs,imb_traps_blocked SET imb_blmacs.blmac_blocked_ip=imb_traps_blocked.traps_ipaddr " .
 			"WHERE (imb_blmacs.device_id=imb_traps_blocked.traps_device_id and " .
 			"imb_blmacs.blmac_port=imb_traps_blocked.traps_port and " .
 			"imb_blmacs.blmac_macaddr=imb_traps_blocked.traps_macaddr);");
	
	db_execute("DELETE FROM `imb_traps_blocked` WHERE DATE_ADD(`traps_time`, INTERVAL  10 DAY) < NOW() ;");
 
 }
 

 function  bdcom_poller_process_camm_syslog($device_id = 0) {
  	global $plugins, $config;
	
 $sql_device_hostname = '';
 
 $plugin_camm_status = db_fetch_cell("SELECT `status`  FROM `plugin_config` WHERE `directory`='camm'; ");
 
	// if camm plugin installed
 	if ($plugin_camm_status == '1') {
		// if syslog component enabled ? and syslog_db name is set
		if ((read_config_option("camm_use_syslog", true)==1) && (strlen(trim("camm_syslog_db_name")) > 0)) {
			// check for use syslog pre table 
	 		if ((strlen(trim(read_config_option("camm_syslog_pretable_name"))) > 0) && (read_config_option("camm_syslog_pretable_name") != "plugin_camm_syslog")) {
	 			$pre_table = read_config_option("camm_syslog_pretable_name");
				$table = "plugin_camm_syslog";
				$use_pre_table = true;
	 		}else{
	 			//$table = '`' . read_config_option("camm_syslog_db_name") . '`.`plugin_camm_syslog`';
				$table = "plugin_camm_syslog";
				$use_pre_table = false;
	 		}			
			
			//if table exist and accecable ...
			$result = db_fetch_assoc("show tables from `" . read_config_option("camm_syslog_db_name") . "`;");
  		  	$tables = array();
		  	if (count($result) > 1) {
		  		foreach($result as $index => $arr) {
		  			foreach ($arr as $t) {
		  				$tables[] = $t;
		  			}
		  		}
		  	}
		  	if ((($use_pre_table == false) && in_array($table, $tables)) || (($use_pre_table == true) && in_array($table, $tables) && in_array($pre_table, $tables))) {
		 		// table exist. Now work
				$table = '`' . read_config_option("camm_syslog_db_name") . '`.`' . $table . '`';
				
				if ($device_id != 0) {
				// создадим строку с именами/ип устройств
					$arr_hostnames = db_fetch_cell("SELECT `hostname` FROM `plugin_bdcom_devices` where `device_id`='" . $device_id . "';");
			 		$sql_device_hostname = " AND `host` = '" . $arr_hostnames . "'";
				}
				
				// возьмем качестве максимального - время между запросами
				 $max_script_runtime = read_config_option("bdcom_script_runtime");
				 if (is_numeric($max_script_runtime)) {
				     /* let PHP a 5 minutes less than the rerun frequency */
				     $max_run_duration = (($max_script_runtime + 1) * 60 + 5*60*60) ;
				 }					
				$sys_time = date("Y-m-d H:i:s", strtotime(read_config_option("bdcom_scan_date", true))- $max_run_duration);
		
				$str_sql =  "SELECT host, sys_date,message FROM " . $table . " WHERE `message` like '%Unauthenticated%' AND `sys_date` > '" .  $sys_time  . "' " .  $sql_device_hostname ;
				
				if ($use_pre_table) {
						$str_sql = $str_sql . " UNION SELECT host, sys_date,message FROM `" . read_config_option("camm_syslog_db_name") . '`.`' . read_config_option("camm_syslog_pretable_name") .  "`  WHERE `message` like '%WARN%Unauthenticated%' AND `sys_date` > '" .  $sys_time  . "' " .  $sql_device_hostname ;
				}
				$str_sql = $str_sql . ";";
				
				$records=db_fetch_assoc($str_sql);
		 		if (sizeof($records)) {
					$sql_replace = "REPLACE INTO `imb_traps_blocked` (`traps_hostname`,`traps_time`,`traps_macaddr`,`traps_ipaddr`,`traps_port`) VALUES " ;
					foreach($records as $key => $record) {
		 			$matches = array();
		 			preg_match("/IP:(?:\ |\<)*([0-9.]*)\>?,\ *MAC\:(?:\ |\<)*([0-9a-fA-F(?:\:|\-)]*)\>?,\ *Port\:?(?:\ |\<)*(?:[0-9]{1}\:)?([0-9]{1,2})\>?/i", $record["message"], $matches);
					//preg_match("/IP:\ *([0-9.]*),\ *MAC\:\ *([0-9a-fA-F(?:\:|\-)]*),\ *Port\:\ *([0-9]{1,2})/i", $record["message"], $matches);
		 			//preg_match("/\[port=([0-9]{1,2})\ *ip\=([0-9\.]*)\ *mac\=([0-9a-fA-F\:]*)\]/", $trap["formatline"], $matches);
		 				if (sizeof($matches)) {
							$matches[2] = str_replace("-",":",$matches[2]);
		 					$sql_replace .= " ('" . $record["host"] . "','" . $record["sys_date"] . "','" . $matches[2] . "','" . $matches[1] . "','" . $matches[3] . "'),";
		 				}
		 			}
					$sql_replace = substr($sql_replace, 0, strlen($sql_replace) - 1);
					$sql_replace .= ";";
					db_execute($sql_replace);
					db_execute("UPDATE `imb_traps_blocked`,`plugin_bdcom_devices` SET `imb_traps_blocked`.`traps_device_id`=`plugin_bdcom_devices`.`device_id` " .
							"WHERE `imb_traps_blocked`.`traps_hostname`=`plugin_bdcom_devices`.`hostname`; ");
		 		
		 			
		 		}				
			
			}
		db_execute("UPDATE imb_blmacs,imb_traps_blocked SET imb_blmacs.blmac_blocked_ip=imb_traps_blocked.traps_ipaddr " .
				"WHERE (imb_blmacs.device_id=imb_traps_blocked.traps_device_id and " .
				"imb_blmacs.blmac_port=imb_traps_blocked.traps_port and " .
				"imb_blmacs.blmac_macaddr=imb_traps_blocked.traps_macaddr);");
		
		db_execute("DELETE FROM `imb_traps_blocked` WHERE DATE_ADD(`traps_time`, INTERVAL  10 DAY) < NOW() ;");
		
		}
	}

 }
 


 ?>
