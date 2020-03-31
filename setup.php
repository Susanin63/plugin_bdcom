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
 
 /*******************************************************************************
 
     Author ......... Susanin 
     Program ........ BDCOM GePON for cacti
     Version ........ 1.0.01b
     Purpose ........ BDCOM GePON Viewer and Manage
 
 *******************************************************************************/

function plugin_bdcom_install() {
	api_plugin_register_hook('bdcom', 'top_header_tabs',       'bdcom_show_tab',             'setup.php');
	api_plugin_register_hook('bdcom', 'top_graph_header_tabs', 'bdcom_show_tab',             'setup.php');
	api_plugin_register_hook('bdcom', 'config_arrays',         'bdcom_config_arrays',        'setup.php');
	api_plugin_register_hook('bdcom', 'draw_navigation_text',  'bdcom_draw_navigation_text', 'setup.php');
	api_plugin_register_hook('bdcom', 'config_form',           'bdcom_config_form',          'setup.php');
	api_plugin_register_hook('bdcom', 'config_settings',       'bdcom_config_settings',      'setup.php');
	api_plugin_register_hook('bdcom', 'poller_bottom',         'bdcom_poller_bottom',        'setup.php');
	api_plugin_register_hook('bdcom', 'page_head',             'bdcom_page_head',            'setup.php');


	# Register our realms
	api_plugin_register_realm('bdcom', 'bdcom_view.php,bdcom_view_devices.php,bdcom_view_olts.php,bdcom_view_epons.php,bdcom_view_ports.php,bdcom_view_onus.php,bdcom_view_netadd.php,bdcom_view_info.php,bdcom_view_info.php,bdcom_ajax.php,graph_ion_view.php', 'bdcom Viewer', 1);
	api_plugin_register_realm('bdcom', 'bdcom_devices.php,bdcom_logs.php,bdcom_device_types.php,bdcom_utilities.php', 'bdcom Administrator', 1);

	bdcom_setup_table ();
}

 function plugin_bdcom_uninstall () {
	/*db_execute('DROP TABLE IF EXISTS `plugin_bdcom_dev_types`');
	db_execute('DROP TABLE IF EXISTS `plugin_bdcom_devices`');
	db_execute('DROP TABLE IF EXISTS `plugin_bdcom_epons`');
	db_execute('DROP TABLE IF EXISTS `plugin_bdcom_logs`');
	db_execute('DROP TABLE IF EXISTS `plugin_bdcom_onu`');
	db_execute('DROP TABLE IF EXISTS `plugin_bdcom_ports`');
	db_execute('DROP TABLE IF EXISTS `plugin_bdcom_processes`');
	db_execute('DROP TABLE IF EXISTS `plugin_bdcom_scan_functions`');
	db_execute('DROP TABLE IF EXISTS `plugin_bdcom_t_ports`');
	db_execute('DROP TABLE IF EXISTS `plugin_bdcom_tab_dev`');
	db_execute('DROP TABLE IF EXISTS `plugin_bdcom_tabs`');
	db_execute('DROP TABLE IF EXISTS `plugin_bdcom_vlans`');
	db_execute('DROP TABLE IF EXISTS `plugin_bdcom_uzel`');*/


}


function plugin_bdcom_version () {
	global $config;
	$info = parse_ini_file($config['base_path'] . '/plugins/bdcom/INFO', true);
	return $info['info'];
}

function plugin_bdcom_check_config () {
	/* Here we will check to ensure everything is configured */
	bdcom_check_upgrade();
	return true;
}

function plugin_bdcom_upgrade () {
	/* Here we will upgrade to the newest version */
	bdcom_check_upgrade();
	return false;
}

function bdcom_show_tab () {
	global $config, $user_auth_realm_filenames;

	if (api_user_realm_auth('bdcom_view_devices.php')) {
		if (substr_count($_SERVER['REQUEST_URI'], 'bdcom_view')) {
			print '<a href="' . $config['url_path'] . 'plugins/bdcom/bdcom_view_devices.php"><img src="' . $config['url_path'] . 'plugins/bdcom/images/tab_bdcom_red.gif" alt="' . __('BDCOM') . '"></a>';
		}else{
			print '<a href="' . $config['url_path'] . 'plugins/bdcom/bdcom_view_devices.php"><img src="' . $config['url_path'] . 'plugins/bdcom/images/tab_bdcom.gif" alt="' . __('BDCOM') . '"></a>';
		}
	}
}



 
 function bdcom_config_arrays () {
 	global $user_auth_realms, $menu,$user_auth_realm_filenames, $bdcom_snmp_versions;
 	global $bdcom_search_types, $bdcom_port_search_types, $bdcom_search_recent_date, $bdcom_poller_frequencies;
 	global $dbcom_timespans;


 
   $bdcom_snmp_versions = array(1 =>
 	"Version 1",
 	"Version 2",
 	"Version 3");
 	
 	$menu2 = array ();
 	foreach ($menu as $temp => $temp2 ) {
 		$menu2[$temp] = $temp2;
 		if ($temp == 'Management') {
 			$menu2['BDCOM']['plugins/bdcom/bdcom_devices.php'] = 'OLT';
 			$menu2['BDCOM']['plugins/bdcom/bdcom_device_types.php'] = 'OLT Types';
 			$menu2['BDCOM']['plugins/bdcom/bdcom_logs.php'] = 'bdcom Logs';
 			$menu2['BDCOM']['plugins/bdcom/bdcom_utilities.php'] = 'BDCOM Utilities';
 		}
 	}
 	$menu = $menu2;


	
     $bdcom_search_types = array(
     1 => '',
     2 => "Matches",
     3 => "Contains",
     4 => "Begins With",
     5 => "Does Not Contain",
     6 => "Does Not Begin With",
     7 => "Is Null",
     8 => "Is Not Null");	  
 
 	    
     $bdcom_port_search_types = array(
     1 => '',
     2 => "Состоит",
     3 => "НЕ состоит");
 	

 	$bdcom_search_recent_date = array(
 	1 => "All",
 	2 => "Current only",
 	3 => "Last 10 minute",
 	4 => "Last 30 minute",
 	5 =>"Last Hour",
 	6 =>"Last Day",
 	7 =>"Last Week",
 	8 =>"Last Month"
 	);
 	
 	$dbcom_timespans = array(
 	1 => "Last Half Hour",
 	2 => "Last Hour",
 	3 => "Last 2 Hours",
 	4 =>"Last Day",
 	5 =>"Last Week",
 	6 =>"Last Month",
 	7 =>"Last Year"
 	);
 	
 
 $refresh_interval = array(
 		5 => "5 Seconds",
 		10 => "10 Seconds",
 		20 => "20 Seconds",
 		30 => "30 Seconds",
 		60 => "1 Minute",
 		300 => "5 Minutes");
		
$bdcom_poller_frequencies = array(
		"disabled" => "Disabled",
		"5" => "Every 5 Minutes",
		"9" => "Every 9 Minutes",
		"10" => "Every 10 Minutes",
		"14" => "Every 14 Minutes",
		"15" => "Every 15 Minutes",
		"20" => "Every 20 Minutes",
		"30" => "Every 30 Minutes",
		"60" => "Every 1 Hour",
		"120" => "Every 2 Hours",
		"240" => "Every 4 Hours",
		"480" => "Every 8 Hours",
		"720" => "Every 12 Hours",
		"1440" => "Every Day");		
 
 }
 
 function bdcom_config_settings () {
 	global $tabs, $settings, $bdcom_snmp_versions;
 	global $snmp_auth_protocols, $snmp_priv_protocols, $bdcom_poller_frequencies;
 
 	$tabs["bdcom"] = "bdcom";
 
 	$settings["bdcom"] = array(
 		"bdcom_hdr_timing" => array(
 			'friendly_name' => "bdcom General Settings",
 			'method' => "spacer",
 			),
		"bdcom_collection_timing" => array(
			'friendly_name' => "Scanning Frequency",
			'description' => "Choose when to collect info statistics from your network devices.",
			'method' => 'drop_array',
			'default' => "disabled",
			"array" => $bdcom_poller_frequencies,
			),			
 		"bdcom_processes" => array(
 			'friendly_name' => "Number of Concurrent Processes",
 			'description' => "Specify how many devices will be polled simultaneously until all devices have been polled.",
 			'default' => "7",
 			'method' => 'textbox',
 			"max_length" => "10"
 			),
 		"bdcom_path_snmpset" => array(
 			'friendly_name' => "snmpset Binary Path",
 			'description' => "The path to your snmpset binary.",
 			'default' => '',
 			'method' => 'textbox',
 			"max_length" => "100"
 			),
 		"bdcom_autosave_count" => array(
 			'friendly_name' => "Max count unsaved operations",
 			'description' => "Count unsaved operations for autosave start.",
 			'default' => "0",
 			'method' => 'textbox',
 			"max_length" => "100"
 			),
 		"bdcom_enable_msg_rx_change" => array(
 			'friendly_name' => "Enable or Disable [RX change] messages",
 			'description' => "Enable or Disable [RX change] messages",
 			'method' => 'drop_array',
 			'default' => 'Enable',
 			"array" => array(1=>'Enable',0=>"Disable"),			
 			),
 		"bdcom_enable_msg_fiber" => array(
 			'friendly_name' => "Enable or Disable [Fiber UP/DOWN] messages",
 			'description' => "Enable or Disable [Fiber UP/DOWN] messages",
 			'method' => 'drop_array',
 			'default' => 'Enable',
 			"array" => array(1=>'Enable',0=>"Disable"),			
 			),			
 		"bdcom_hdr_general" => array(
 			'friendly_name' => "bdcom SNMP General Settings",
 			'method' => "spacer",
 			),
 		"bdcom_snmp_port" => array(
 			'friendly_name' => "SNMP Port",
 			'description' => "The UDP/TCP Port to poll the SNMP agent on.",
 			'method' => 'textbox',
 			'default' => "161",
 			"max_length" => "100"
 			),			
 		"bdcom_snmp_timeout" => array(
 			'friendly_name' => "SNMP Timeout",
 			'description' => "Default SNMP timeout in milli-seconds.",
 			'method' => 'textbox',
 			'default' => "500",
 			"max_length" => "100"
 			),
 		"bdcom_snmp_retries" => array(
 			'friendly_name' => "SNMP Retries",
 			'description' => "The number times the SNMP poller will attempt to reach the host before failing.",
 			'method' => 'textbox',
 			'default' => "3",
 			"max_length" => "100"
 			),			
 		"bdcom_hdr_read_snmp" => array(
 			'friendly_name' => "bdcom SNMP READ Settings",
 			'method' => "spacer",
 			),
 		"bdcom_read_snmp_ver" => array(
 			'friendly_name' => "SNMP Version",
 			'description' => "Default SNMP version for all new hosts.",
 			'method' => 'drop_array',
 			'default' => "Version 2",
 			"array" => $bdcom_snmp_versions,
 			),
 		"bdcom_read_snmp_community" => array(
 			'friendly_name' => "SNMP Community",
 			'description' => "Default SNMP read community for all new hosts.",
 			'method' => 'textbox',
 			'default' => "public",
 			"max_length" => "100"
 			),
 		"bdcom_read_snmp_username" => array(
 			'friendly_name' => "SNMP Username (v3)",
 			'description' => "Default SNMP v3 username for all new hosts.",
 			'method' => 'textbox',
 			'default' => "public",
 			"max_length" => "100"
 			),
 		"bdcom_read_snmp_password" => array(
 			'friendly_name' => "SNMP Password (v3)",
 			'description' => "Default SNMP v3 password for all new hosts.",
 			'method' => 'textbox',
 			'default' => "public",
 			"max_length" => "100"
 			),
 		"bdcom_snmp_get_auth_protocol" => array(
 			'method' => 'drop_array',
 			'friendly_name' => "SNMP Auth Protocol (v3)",
 			'description' => "Choose the SNMPv3 Authorization Protocol.",
 			'default' => "MD5 (default)",
 			"array" => $snmp_auth_protocols,
 			),
 		"bdcom_snmp_get_priv_passphrase" => array(
 			'method' => 'textbox',
 			'friendly_name' => "SNMP Privacy Passphrase (v3)",
 			'description' => "Choose the SNMPv3 Privacy Passphrase.",
 			'default' => '',
 			"max_length" => "200",
 			"size" => "40"
 			),
 		"bdcom_snmp_get_priv_protocol" => array(
 			'method' => 'drop_array',
 			'friendly_name' => "SNMP Privacy Protocol (v3)",
 			'description' => "Choose the SNMPv3 Privacy Protocol.",
 			'default' => "DES (default)",
 			"array" => $snmp_priv_protocols,
 			),
 		"bdcom_hdr_write_snmp" => array(
 			'friendly_name' => "bdcom SNMP WRITE Settings",
 			'method' => "spacer",
 			),
 		"bdcom_write_snmp_ver" => array(
 			'friendly_name' => "SNMP Version",
 			'description' => "Default SNMP version for all new hosts.",
 			'method' => 'drop_array',
 			'default' => "Version 2",
 			"array" => $bdcom_snmp_versions,
 			),
 		"bdcom_write_snmp_community" => array(
 			'friendly_name' => "SNMP Community",
 			'description' => "Default SNMP read community for all new hosts.",
 			'method' => 'textbox',
 			'default' => "private",
 			"max_length" => "100",
 			"size" => "15"
 			),
 		"bdcom_write_snmp_username" => array(
 			'friendly_name' => "SNMP Username (v3)",
 			'description' => "Default SNMP v3 username for all new hosts.",
 			'method' => 'textbox',
 			'default' => "private",
 			"max_length" => "50",
 			"size" => "15"
 			),
 		"bdcom_write_snmp_password" => array(
 			'friendly_name' => "SNMP Password (v3)",
 			'description' => "Default SNMP v3 password for all new hosts.",
 			'method' => 'textbox',
 			'default' => '',
 			"max_length" => "50",
 			"size" => "15"
 			),
 		"bdcom_snmp_set_auth_protocol" => array(
 			'method' => 'drop_array',
 			'friendly_name' => "SNMP Auth Protocol (v3)",
 			'description' => "Choose the SNMPv3 Authorization Protocol.",
 			'default' => "MD5 (default)",
 			"array" => $snmp_auth_protocols,
 			),
 		"bdcom_snmp_set_priv_passphrase" => array(
 			'method' => 'textbox',
 			'friendly_name' => "SNMP Privacy Passphrase (v3)",
 			'description' => "Choose the SNMPv3 Privacy Passphrase.",
 			'default' => '',
 			"max_length" => "200",
 			"size" => "40"
 			),
 		"bdcom_snmp_set_priv_protocol" => array(
 			'method' => 'drop_array',
 			'friendly_name' => "SNMP Privacy Protocol (v3)",
 			'description' => "Choose the SNMPv3 Privacy Protocol.",
 			'default' => "DES (default)",
 			"array" => $snmp_priv_protocols,
 			),
 		"bdcom_hdr_configs" => array(
 			'friendly_name' => "bdcom Configs Settings",
 			'method' => "spacer",
 			),
 		"bdcom_use_camm_syslog" => array(
 			'method' => "checkbox",
 			'friendly_name' => "Try to use info from CAMM plugin for cacti ?",
 			'description' => "Если флаг установлен, то при каждом опросе будет производиться попытка получить данные SYSLOG для определения IP-адреса, с которым пришел заблокированный пакет. Имеет больший приоритет перед SNMPTT",
 			'default' => ''
 			),			
 		"bdcom_mac_addr_font_size" => array(
 			'method' => 'textbox',
 			'friendly_name' => "Font size for mac-address",
 			'description' => "Размер шрифта по умолчанию для отображения mac-address.",
 			'default' => "2",
 			"max_length" => "2",
 			"size" => "15"
 			)		
 		);
 
 		$settings["visual"]["bdcom_header"] = array(
 			'friendly_name' => "bdcom",
 			'method' => "spacer",
 			);
 		$settings["visual"]["bdcom_num_rows"] = array(
 			'friendly_name' => "Rows Per Page",
 			'description' => "The number of rows to display on a single page for bdcom devices and reports.",
 			'method' => 'textbox',
 			'default' => "30",
 			"max_length" => "10"
 			);
	bdcom_check_upgrade();
 }
 
 
 function bdcom_draw_navigation_text ($nav) {
  // $nav['bdcom.php:'] = array('title' => 'BDCOM', 'mapping' => 'index.php:', 'url' => 'bdcom.php', 'level' => '1');
   $nav['bdcom_devices.php:'] = array('title' => __('OLT Devices', 'bdcom'), 'mapping' => 'index.php:', 'url' => 'bdcom_devices.php', 'level' => '1');
   $nav['bdcom_devices.php:actions'] = array('title' => __('Actions', 'bdcom'), 'mapping' => 'index.php:,bdcom_devices.php:', 'url' => '', 'level' => '2');  
   $nav['bdcom_devices.php:edit'] = array('title' => __('(Edit)', 'bdcom'), 'mapping' => 'index.php:,bdcom_devices.php:', 'url' => '', 'level' => '2');
   
   $nav['bdcom_device_types.php:'] = array('title' => __('OLT Devices Types', 'bdcom'), 'mapping' => 'index.php:', 'url' => 'bdcom_device_types.php', 'level' => '1');
   $nav['bdcom_device_types.php:edit'] = array('title' => __('(Edit)','bdcom'), 'mapping' => 'index.php:,bdcom_device_types.php:', 'url' => '', 'level' => '2');
   //$nav['bdcom_device_types.php:import'] = array('title' => '(Import)', 'mapping' => 'index.php:,bdcom_device_types.php:', 'url' => '', 'level' => '2');
   $nav['bdcom_device_types.php:actions'] = array('title' => __('Actions', 'bdcom'), 'mapping' => 'index.php:,bdcom_device_types.php:', 'url' => '', 'level' => '2');
  

   $nav['bdcom_utilities.php:'] = array('title' => __('BDCOM Utilities', 'bdcom'), 'mapping' => 'index.php:', 'url' => 'bdcom_utilities.php', 'level' => '1');
   $nav['bdcom_utilities.php:bdcom_utilities_purge_scanning_funcs'] = array('title' => __('Refresh Scanning Functions', 'bdcom'), 'mapping' => 'index.php:,bdcom_utilities.php:', 'url' => 'bdcom_utilities.php', 'level' => '2');   
   $nav['bdcom_utilities.php:bdcom_view_proc_status'] = array('title' => __('View BDCOM Process Status', 'bdcom'), 'mapping' => 'index.php:,bdcom_utilities.php:', 'url' => 'bdcom_utilities.php', 'level' => '2');   
   
   $nav['bdcom_logs.php:'] = array('title' => __('BDCOM logs', 'bdcom'), 'mapping' => 'index.php:', 'url' => 'bdcom_logs.php', 'level' => '1');
   $nav['bdcom_logs.php:actions_logs'] = array('title' => __('DELETE BDCOM logs', 'bdcom'), 'mapping' => 'index.php:,bdcom_logs.php:', 'url' => 'bdcom_logs.php', 'level' => '2');   
   
   
   
   $nav['bdcom_view_ports.php:'] = array('title' => __('BDCOM View Ports'), 'mapping' => '', 'url' => 'bdcom_view_ports.php', 'level' => '0');
   $nav['bdcom_view_ports.php:actions'] = array('title' => __('Actions'), 'mapping' => 'bdcom_view_ports.php:', 'url' => '', 'level' => '1');
   $nav['bdcom_view_olts.php:'] = array('title' => __('BDCOM View OLTs'), 'mapping' => '', 'url' => 'bdcom_view_olts.php', 'level' => '0');
   $nav['bdcom_view_olts.php:actions'] = array('title' => __('Actions'), 'mapping' => 'bdcom_view_olts.php:', 'url' => '', 'level' => '1');
   $nav['bdcom_view_epons.php:'] = array('title' => __('BDCOM View OLTs'), 'mapping' => '', 'url' => 'bdcom_view_epons.php', 'level' => '0');
   $nav['bdcom_view_epons.php:actions'] = array('title' => __('Actions'), 'mapping' => 'bdcom_view_epons.php:', 'url' => '', 'level' => '1');
   $nav['bdcom_view_onus.php:'] = array('title' => __('BDCOM View ONUs'), 'mapping' => '', 'url' => 'bdcom_view_onus.php', 'level' => '0');
   $nav['bdcom_view_onus.php:actions'] = array('title' => __('Actions'), 'mapping' => 'bdcom_view_onus.php:', 'url' => '', 'level' => '1');
   $nav['bdcom_view_info.php:'] = array('title' => __('BDCOM View Info'), 'mapping' => '', 'url' => 'bdcom_view_info.php', 'level' => '0');
   $nav['bdcom_view_info.php:actions'] = array('title' => __('Actions'), 'mapping' => 'bdcom_view_info.php:', 'url' => '', 'level' => '1');
   $nav['bdcom_view_add.php:'] = array('title' => __('BDCOM View ADD'), 'mapping' => '', 'url' => 'bdcom_view_add.php', 'level' => '0');
   $nav['bdcom_view_add.php:actions'] = array('title' => __('Actions'), 'mapping' => 'bdcom_view_add.php:', 'url' => '', 'level' => '1');
           
    return $nav;
 }


 function bdcom_page_head() {
	global $config;

	if (substr_count(get_current_page(), 'bdcom_')) {
		if (!isset($config['base_path'])) {
			print "<script type='text/javascript' src='" . URL_PATH . "plugins/bdcom/bdcom.js'></script>\n";
		}else{
			if (file_exists($config['base_path'] . '/plugins/bdcom/themes/' . get_selected_theme() . '/bdcom.css')) {
				print "<link type='text/css' href='" . $config['url_path'] . "plugins/bdcom/themes/" . get_selected_theme() . "/bdcom.css' rel='stylesheet'>\n";
			}else{
				print "<link type='text/css' href='" . $config['url_path'] . "plugins/bdcom/bdcom.css' rel='stylesheet'>\n";
			}
		}
		print "<script type='text/javascript' src='" . $config['url_path'] . "plugins/bdcom/jquery.bpopup.min.js'></script>\n";
		print "<script type='text/javascript' src='" . $config['url_path'] . "plugins/bdcom/bdcom.js'></script>\n";
		print "<script type='text/javascript' src='" . $config['url_path'] . "plugins/bdcom/bdcom_snmp.js'></script>\n";
	}
}


 
 function bdcom_poller_bottom () {
 	global $config;
 	include_once($config["base_path"] . "/lib/poller.php");
 	include_once($config["base_path"] . "/lib/data_query.php");

 
 	$command_string = read_config_option("path_php_binary");
 	$extra_args = "-q " . $config["base_path"] . "/plugins/bdcom/poller_bdcom.php";
 	exec_background($command_string, "$extra_args");
 }
 
 
 function bdcom_config_form () {
 	global $fields_bdcom_device_type_edit, $bdcom_device_types, $fields_bdcom_device_edit;
 	global $bdcom_snmp_versions, $bdcom_revision, $bdcom_imp_MacBindingPortState;
 	global $bdcom_operation_macip_types, $bdcom_type_port_num_conversion, $bdcom_alue_save_cfg;
 	global $bdcom_imp_mode_type, $bdcom_imp_action_type, $bdcom_imb_create_macip_type;
 	global $snmp_auth_protocols, $snmp_priv_protocols, $bdcom_imp_zerrostate_mode_type, $bdcom_imp_mode,$bdcom_imb_yes_no, $bdcom_func_version;
 
 	/* file: bdcom_device_types.php, action: edit */
 	$fields_bdcom_device_type_edit = array(
 	"spacer0" => array(
 		'method' => "spacer",
 		'friendly_name' => "General Device Type Options"
 		),
 	'description' => array(
 		'method' => 'textbox',
 		'friendly_name' => 'description',
 		'description' => "Give this device type a meaningful description.",
 		"value" => "|arg1:description|",
 		"max_length" => "250"
 		)	,
 	"scanning_function" => array(
 		'method' => "drop_sql",
 		'friendly_name' => "Scanning Function",
 		'description' => "The BDCOM scanning function to call in order to obtain and store rows details.  The function name is all that is required. ",
 		"value" => "|arg1:scanning_function|",
 		'default' => 1,
 		"sql" => "select scanning_function as id, scanning_function as name from plugin_bdcom_scan_functions order by scanning_function"
 		),

 	"device_type_id" => array(
 		'method' => "hidden_zero",
 		"value" => "|arg1:device_type_id|"
 		),
 	"_device_type_id" => array(
 		'method' => "hidden_zero",
 		"value" => "|arg1:device_type_id|"
 		),		
 	"save_component_device_type" => array(
 		'method' => "hidden",
 		"value" => "1"
 		)
 	);
 
 	$fields_bdcom_device_edit = array(
 	"spacer0" => array(
 		'method' => "spacer",
 		'friendly_name' => "General Device Settings"
 		),
 	'description' => array(
 		'method' => 'textbox',
 		'friendly_name' => 'description',
 		'description' => "Give this device a meaningful description.",
 		"value" => "|arg1:description|",
 		"max_length" => "250"
 		),
 	"hostname" => array(
 		'method' => 'textbox',
 		'friendly_name' => "Hostname",
 		'description' => "Fill in the fully qualified hostname for this device.",
 		"value" => "|arg1:hostname|",
 		"max_length" => "250"
 		),
 	"device_type_id" => array(
 		'method' => "drop_sql",
 		'friendly_name' => "Device Type",
 		'description' => "Choose the Device Type to associate with this device.",
 		"value" => "|arg1:device_type_id|",
 		"none_value" => "None",
 		"sql" => "select device_type_id as id,description as name from plugin_bdcom_dev_types order by name"
 		),		
 	"disabled" => array(
 		'method' => "checkbox",
 		'friendly_name' => "Disable Device",
 		'description' => "Check this box to disable all checks for this host.",
 		"value" => "|arg1:disabled|",
 		'default' => '',
 		"form_id" => false
 		),
 	"order_id" => array(
 		'method' => 'textbox',
 		'friendly_name' => "Sort order ID",
 		'description' => "Fill in the sort order id for this device.",
 		"value" => "|arg1:order_id|",
 		"max_length" => "3"
 		),	
 	"color_row" => array(
 		'method' => "drop_color",
 		'friendly_name' => "Row color",
 		'description' => "Fill in the color row for this device.",
 		"value" => "|arg1:color_row|",
 		'default' => 0,
 		),		
 	"spacer2" => array(
 		'method' => "spacer",
 		'friendly_name' => "SNMP Default Settings"
 		),
 	"snmp_port" => array(
 		'method' => 'textbox',
 		'friendly_name' => "SNMP Port",
 		'description' => "The UDP/TCP Port to poll the SNMP agent on.",
 		"value" => "|arg1:snmp_port|",
 		"max_length" => "8",
 		'default' => read_config_option("bdcom_snmp_port"),
 		"size" => "15"
 		),
 	"snmp_timeout" => array(
 		'method' => 'textbox',
 		'friendly_name' => "SNMP Timeout",
 		'description' => "The maximum number of milliseconds Cacti will wait for an SNMP response (does not work with php-snmp support).",
 		"value" => "|arg1:snmp_timeout|",
 		"max_length" => "8",
 		'default' => read_config_option("bdcom_snmp_timeout"),
 		"size" => "15"
 		),
 	"snmp_retries" => array(
 		'method' => 'textbox',
 		'friendly_name' => "SNMP Retries",
 		'description' => "The maximum number of attempts to reach a device via an SNMP readstring prior to giving up.",
 		"value" => "|arg1:snmp_retries|",
 		"max_length" => "8",
 		'default' => read_config_option("bdcom_snmp_retries"),
 		"size" => "15"
 		),
 	"snmp_max_oids" => array(
 		'method' => 'textbox',
 		'friendly_name' => "Maximum OID's Per Get Request",
 		'description' => "Specified the number of OID's that can be obtained in a single SNMP Get request.  <br><i>NOTE: This feature only works when using Spine</i>",
 		"value" => "|arg1:snmp_max_oids|",
 		"max_length" => "8",
 		'default' => read_config_option("max_get_size"),
 		"size" => "15"
 		),		
 	"spacer3" => array(
 		'method' => "spacer",
 		'friendly_name' => "SNMP READ Settings"
 		),
 	"snmp_get_version" => array(
 		'method' => 'drop_array',
 		'friendly_name' => "SNMP Version",
 		'description' => "Choose the SNMP version for this device.",
 		"on_change" => "changeBdcomHostForm()",
 		"value" => "|arg1:snmp_get_version|",
 		'default' => read_config_option("bdcom_read_snmp_ver"),
 		"array" => $bdcom_snmp_versions,
 		),	
 	"snmp_get_community" => array(
 		'method' => 'textbox',
 		'friendly_name' => "SNMP Community",
 		'description' => "Fill in the SNMP read community for this device.",
 		"value" => "|arg1:snmp_get_community|",
 		"form_id" => "|arg1:id|",
 		'default' => read_config_option("bdcom_read_snmp_community"),
 		"max_length" => "100",
 		"size" => "40"
 		),
 	"snmp_get_username" => array(
 		'method' => 'textbox',
 		'friendly_name' => "SNMP Username (v3)",
 		'description' => "Fill in the SNMP v3 username for this device.",
 		"value" => "|arg1:snmp_get_username|",
 		'default' => read_config_option("bdcom_read_snmp_username"),
 		"max_length" => "50",
 		"size" => "40"
 		),
 	"snmp_get_password" => array(
 		'method' => "textbox_password",
 		'friendly_name' => "SNMP Password (v3)",
 		'description' => "Fill in the SNMP v3 password for this device.",
 		"value" => "|arg1:snmp_get_password|",
 		'default' => read_config_option("bdcom_read_snmp_password"),
 		"max_length" => "50",
 		"size" => "40"
 		),
 	"snmp_get_auth_protocol" => array(
 		'method' => 'drop_array',
 		'friendly_name' => "SNMP Auth Protocol (v3)",
 		'description' => "Choose the SNMPv3 Authorization Protocol.",
 		"value" => "|arg1:snmp_get_auth_protocol|",
 		'default' => read_config_option("bdcom_snmp_get_auth_protocol"),
 		"array" => $snmp_auth_protocols,
 		),
 	"snmp_get_priv_passphrase" => array(
 		'method' => 'textbox',
 		'friendly_name' => "SNMP Privacy Passphrase (v3)",
 		'description' => "Choose the SNMPv3 Privacy Passphrase.",
 		"value" => "|arg1:snmp_get_priv_passphrase|",
 		'default' => read_config_option("bdcom_snmp_get_priv_passphrase"),
 		"max_length" => "200",
 		"size" => "40"
 		),
 	"snmp_get_priv_protocol" => array(
 		'method' => 'drop_array',
 		'friendly_name' => "SNMP Privacy Protocol (v3)",
 		'description' => "Choose the SNMPv3 Privacy Protocol.",
 		"value" => "|arg1:snmp_get_priv_protocol|",
 		'default' => read_config_option("bdcom_snmp_get_priv_protocol"),
 		"array" => $snmp_priv_protocols,
 		),
 	"snmp_get_context" => array(
 		'method' => 'textbox',
 		'friendly_name' => "SNMP Context",
 		'description' => "Enter the SNMP Context to use for this device.",
 		"value" => "|arg1:snmp_get_context|",
 		'default' => '',
 		"max_length" => "64",
 		"size" => "40"
 		),
 	"spacer4" => array(
 		'method' => "spacer",
 		'friendly_name' => "SNMP WRITE Settings"
 		),
 	"snmp_set_version" => array(
 		'method' => 'drop_array',
 		'friendly_name' => "SNMP Version",
 		'description' => "Choose the SNMP version for this device.",
 		"on_change" => "changeBdcomHostForm()",
 		"value" => "|arg1:snmp_set_version|",
 		'default' => read_config_option("bdcom_write_snmp_ver"),
 		"array" => $bdcom_snmp_versions,
 		),	
 	"snmp_set_community" => array(
 		'method' => 'textbox',
 		'friendly_name' => "SNMP Community",
 		'description' => "Fill in the SNMP read community for this device.",
 		"value" => "|arg1:snmp_set_community|",
 		"form_id" => "|arg1:id|",
 		'default' => read_config_option("bdcom_write_snmp_community"),
 		"max_length" => "100",
 		"size" => "40"
 		),
 	"snmp_set_username" => array(
 		'method' => 'textbox',
 		'friendly_name' => "SNMP Username (v3)",
 		'description' => "Fill in the SNMP v3 username for this device.",
 		"value" => "|arg1:snmp_set_username|",
 		'default' => read_config_option("bdcom_write_snmp_username"),
 		"max_length" => "50",
 		"size" => "40"
 		),
 	"snmp_set_password" => array(
 		'method' => "textbox_password",
 		'friendly_name' => "SNMP Password (v3)",
 		'description' => "Fill in the SNMP v3 password for this device.",
 		"value" => "|arg1:snmp_set_password|",
 		'default' => read_config_option("bdcom_write_snmp_password"),
 		"max_length" => "50",
 		"size" => "40"
 		),
 	"snmp_set_auth_protocol" => array(
 		'method' => 'drop_array',
 		'friendly_name' => "SNMP Auth Protocol (v3)",
 		'description' => "Choose the SNMPv3 Authorization Protocol.",
 		"value" => "|arg1:snmp_set_auth_protocol|",
 		'default' => read_config_option("bdcom_snmp_set_auth_protocol"),
 		"array" => $snmp_auth_protocols,
 		),
 	"snmp_set_priv_passphrase" => array(
 		'method' => 'textbox',
 		'friendly_name' => "SNMP Privacy Passphrase (v3)",
 		'description' => "Choose the SNMPv3 Privacy Passphrase.",
 		"value" => "|arg1:snmp_set_priv_passphrase|",
 		'default' => read_config_option("bdcom_snmp_set_priv_passphrase"),
 		"max_length" => "200",
 		"size" => "40"
 		),
 	"snmp_set_priv_protocol" => array(
 		'method' => 'drop_array',
 		'friendly_name' => "SNMP Privacy Protocol (v3)",
 		'description' => "Choose the SNMPv3 Privacy Protocol.",
 		"value" => "|arg1:snmp_set_priv_protocol|",
 		'default' => read_config_option("bdcom_snmp_set_priv_protocol"),
 		"array" => $snmp_priv_protocols,
 		),
 	"snmp_set_context" => array(
 		'method' => 'textbox',
 		'friendly_name' => "SNMP Context",
 		'description' => "Enter the SNMP Context to use for this device.",
 		"value" => "|arg1:snmp_set_context|",
 		'default' => '',
 		"max_length" => "64",
 		"size" => "40"
 		),
 		
 	"device_id" => array(
 		'method' => "hidden_zero",
 		"value" => "|arg1:device_id|"
 		),
 	"_device_id" => array(
 		'method' => "hidden_zero",
 		"value" => "|arg1:device_id|"
 		),
 	"save_component_device" => array(
 		'method' => "hidden",
 		"value" => "1"
 		)
 	);	

 
 	}
 
 function bdcom_check_upgrade() {
	global $config;

	$files = array('index.php', 'plugins.php', 'bdcom_devices.php');
	if (!in_array(get_current_page(), $files)) {
		return;
	}

	include_once($config['base_path'] . '/plugins/bdcom/lib/bdcom_functions.php');

	$current = plugin_bdcom_version();
	$current = $current['version'];

	$old     = db_fetch_row("SELECT * FROM plugin_config WHERE directory='bdcom'");
	if (!sizeof($old) || $current != $old['version']) {
		/* if the plugin is installed and/or active */
		if (!sizeof($old) || $old['status'] == 1 || $old['status'] == 4) {
			/* re-register the hooks */
			plugin_bdcom_install();
			if (api_plugin_is_enabled('bdcom')) {
				# may sound ridiculous, but enables new hooks
				api_plugin_enable_hooks('bdcom');
			}

			/* perform a database upgrade */
			bdcom_setup_table();
		}

		// If are realms are not present in plugin_realms recreate them with the old realm ids (minus 100) so that upgraded installs are not broken
		if (!db_fetch_cell("SELECT id FROM plugin_realms WHERE plugin = 'bdcom'")) {
			db_execute("INSERT INTO plugin_realms
				(id, plugin, file, display)
				VALUES (3020, 'bdcom', 'bdcom_view.php,bdcom_view_devices.php,bdcom_view_olts.php,bdcom_view_epons.php,bdcom_view_ports.php,bdcom_view_onus.php,bdcom_view_netadd.php,bdcom_view_info.php,bdcom_view_info.php,bdcom_ajax.php', 'BDCOM Viewer')");
			db_execute("INSERT INTO plugin_realms
				(id, plugin, file, display)
				VALUES (3021, 'bdcom', 'bdcom_devices.php,bdcom_logs.php,bdcom_device_types.php,bdcom_utilities.php', 'BDCOM Administrator')");
		}

		
		
		/* rebuild the scanning functions */
		//bdcom_rebuild_scanning_funcs();

		/* update the plugin information */
		$info = plugin_bdcom_version();
		$id   = db_fetch_cell("SELECT id FROM plugin_config WHERE directory='bdcom'");

		db_execute("UPDATE plugin_config
			SET name='" . $info['longname'] . "',
			author='"   . $info['author']   . "',
			webpage='"  . $info['homepage'] . "',
			version='"  . $info['version']  . "'
			WHERE id='$id'");
	}
}

 

 function bdcom_check_dependencies() {
	global $plugins, $config;

	return true;
 }
 
 
 	
 function bdcom_setup_table () {
 	global $config, $database_default;;
 
 	include_once($config["library_path"] . "/database.php");
	include_once($config['base_path'] . '/plugins/bdcom/lib/bdcom_functions.php');
 
 	// Set the new version
 	$new = plugin_bdcom_version();
 	$new = $new['version'];
 	$old = db_fetch_cell("SELECT `value` FROM `settings` where name = 'bdcom_version'");
 	db_execute("REPLACE INTO settings (name, value) VALUES ('bdcom_version', '$new')");
 	if (trim($old) == '') {
 		$old = "0.0.1";
 	}
 	$sql = "show tables from `" . $database_default . "`";
 	$result = db_fetch_assoc($sql) or die (mysql_error());
 
 	$tables = array();
 	$sql = array();
 
 	if (count($result) > 1) {
 		foreach($result as $index => $arr) {
 			foreach ($arr as $t) {
 				$tables[] = $t;
 			}
 		}
 	}
 	$result = db_fetch_assoc("SELECT `name` FROM `settings` where name like 'bdcom%%' order by name");
 	foreach($result as $row) {
 		$result_new[] =$row['name'];
 	}
 	
 if (!in_array("bdcom_num_rows", $result_new))
 	$sql[] = array("bdcom_execute_sql","Insert into [settings] new parametr [bdcom_num_rows]","INSERT INTO settings VALUES ('bdcom_num_rows',50);");	
 if (!in_array("bdcom_path_snmpset", $result_new))
 		$sql[] = array("bdcom_execute_sql","Insert into [settings] new parametr [bdcom_path_snmpset]","INSERT INTO settings VALUES ('bdcom_path_snmpset','C:\\usr\\bin\\snmpset.exe');");
 if (!in_array("bdcom_last_run_time", $result_new))		
 		$sql[] = array("bdcom_execute_sql","Insert into [settings] new parametr [bdcom_last_run_time]","INSERT INTO settings VALUES ('bdcom_last_run_time',0);");
 if (!in_array("bdcom_scan_date", $result_new))	
 		$sql[] = array("bdcom_execute_sql","Insert into [settings] new parametr [bdcom_scan_date]","INSERT INTO settings VALUES ('bdcom_scan_date',0);");
 if (!in_array("bdcom_read_snmp_community", $result_new))	
 		$sql[] = array("bdcom_execute_sql","Insert into [settings] new parametr [bdcom_read_snmp_community]","INSERT INTO settings VALUES ('bdcom_read_snmp_community','public');");
 if (!in_array("bdcom_stats_general", $result_new))	
 		$sql[] = array("bdcom_execute_sql","Insert into [settings] new parametr [bdcom_stats_general]","INSERT INTO settings VALUES ('bdcom_stats_general','Time:0 ConcurrentProcesses:0 Devices:0');");
 if (!in_array("bdcom_processes", $result_new))	
 		$sql[] = array("bdcom_execute_sql","Insert into [settings] new parametr [bdcom_snmp_get_priv_protocol]","INSERT INTO settings VALUES ('bdcom_processes',5);");
 if (!in_array("bdcom_bdcom_finish", $result_new))	
 		$sql[] = array("bdcom_execute_sql","Insert into [settings] new parametr [bdcom_bdcom_finish]","INSERT INTO settings VALUES ('bdcom_bdcom_finish',1);");
 if (!in_array("bdcom_stats", $result_new))	
 		$sql[] = array("bdcom_execute_sql","Insert into [settings] new parametr [bdcom_stats]","INSERT INTO settings VALUES ('bdcom_stats','ipmacs:0 Blockedmacs:0 Active_ports:0');");
 if (!in_array("bdcom_read_snmp_communities", $result_new))	
 		$sql[] = array("bdcom_execute_sql","Insert into [settings] new parametr [bdcom_read_snmp_communities]","INSERT INTO settings VALUES ('bdcom_read_snmp_communities','public:private:secret');");
 if (!in_array("bdcom_read_snmp_ver", $result_new))	
 		$sql[] = array("bdcom_execute_sql","Insert into [settings] new parametr [bdcom_read_snmp_ver]","INSERT INTO settings VALUES ('bdcom_read_snmp_ver',2);");
 if (!in_array("bdcom_read_snmp_port", $result_new))	
 		$sql[] = array("bdcom_execute_sql","Insert into [settings] new parametr [bdcom_read_snmp_port]","INSERT INTO settings VALUES ('bdcom_read_snmp_port',161);");
 if (!in_array("bdcom_read_snmp_timeout", $result_new))	
 		$sql[] = array("bdcom_execute_sql","Insert into [settings] new parametr [bdcom_read_snmp_timeout]","INSERT INTO settings VALUES ('bdcom_read_snmp_timeout',500);");
 if (!in_array("bdcom_read_snmp_retries", $result_new))	
 		$sql[] = array("bdcom_execute_sql","Insert into [settings] new parametr [bdcom_read_snmp_retries]","INSERT INTO settings VALUES ('bdcom_read_snmp_retries',3);");
 if (!in_array("bdcom_write_snmp_ver", $result_new))	
 		$sql[] = array("bdcom_execute_sql","Insert into [settings] new parametr [bdcom_write_snmp_ver]","INSERT INTO settings VALUES ('bdcom_write_snmp_ver',2);");
 if (!in_array("bdcom_write_snmp_community", $result_new))	
 		$sql[] = array("bdcom_execute_sql","Insert into [settings] new parametr [bdcom_write_snmp_community]","INSERT INTO settings VALUES ('bdcom_write_snmp_community','private');");
 if (!in_array("bdcom_write_snmp_communities", $result_new))	
 		$sql[] = array("bdcom_execute_sql","Insert into [settings] new parametr [bdcom_write_snmp_communities]","INSERT INTO settings VALUES ('bdcom_write_snmp_communities','public:private:secret');");
 if (!in_array("bdcom_write_snmp_port", $result_new))	
 		$sql[] = array("bdcom_execute_sql","Insert into [settings] new parametr [bdcom_write_snmp_port]","INSERT INTO settings VALUES ('bdcom_write_snmp_port',161);");
 if (!in_array("bdcom_write_snmp_timeout", $result_new))	
 		$sql[] = array("bdcom_execute_sql","Insert into [settings] new parametr [bdcom_write_snmp_timeout]","INSERT INTO settings VALUES ('bdcom_write_snmp_timeout',500);");
 if (!in_array("bdcom_write_snmp_retries", $result_new))	
 		$sql[] = array("bdcom_execute_sql","Insert into [settings] new parametr [bdcom_write_snmp_retries]","INSERT INTO settings VALUES ('bdcom_write_snmp_retries',3);");
 if (!in_array("bdcom_snmp_port", $result_new))	
 		$sql[] = array("bdcom_execute_sql","Insert into [settings] new parametr [bdcom_snmp_port]","INSERT INTO settings VALUES ('bdcom_snmp_port',161);");
 if (!in_array("bdcom_snmp_timeout", $result_new))	
 		$sql[] = array("bdcom_execute_sql","Insert into [settings] new parametr [bdcom_snmp_timeout]","INSERT INTO settings VALUES ('bdcom_snmp_timeout',500);");
 if (!in_array("bdcom_snmp_retries", $result_new))	
 		$sql[] = array("bdcom_execute_sql","Insert into [settings] new parametr [bdcom_snmp_retries]","INSERT INTO settings VALUES ('bdcom_snmp_retries',3);");
 if (!in_array("bdcom_read_snmp_username", $result_new))	
 		$sql[] = array("bdcom_execute_sql","Insert into [settings] new parametr [bdcom_read_snmp_username]","INSERT INTO settings VALUES ('bdcom_read_snmp_username','public');");
 if (!in_array("bdcom_read_snmp_password", $result_new))	
 		$sql[] = array("bdcom_execute_sql","Insert into [settings] new parametr [bdcom_read_snmp_password]","INSERT INTO settings VALUES ('bdcom_read_snmp_password','public');");
 if (!in_array("bdcom_write_snmp_username", $result_new))	
 		$sql[] = array("bdcom_execute_sql","Insert into [settings] new parametr [bdcom_write_snmp_username]","INSERT INTO settings VALUES ('bdcom_write_snmp_username','private');");
 if (!in_array("bdcom_write_snmp_password", $result_new))	
 		$sql[] = array("bdcom_execute_sql","Insert into [settings] new parametr [bdcom_write_snmp_password]","INSERT INTO settings VALUES ('bdcom_write_snmp_password','private');");
 
 if (!in_array("bdcom_snmp_set_auth_protocol", $result_new))	
 		$sql[] = array("bdcom_execute_sql","Insert into [settings] new parametr [bdcom_snmp_set_auth_protocol]","INSERT INTO settings VALUES ('bdcom_snmp_set_auth_protocol','MD5');");
 if (!in_array("bdcom_snmp_set_priv_passphrase", $result_new))	
 		$sql[] = array("bdcom_execute_sql","Insert into [settings] new parametr [bdcom_snmp_set_priv_passphrase]","INSERT INTO settings VALUES ('bdcom_snmp_set_priv_passphrase','');");
 if (!in_array("bdcom_snmp_set_priv_protocol", $result_new))	
 		$sql[] = array("bdcom_execute_sql","Insert into [settings] new parametr [bdcom_snmp_set_priv_protocol]","INSERT INTO settings VALUES ('bdcom_snmp_set_priv_protocol','DES');");
 if (!in_array("bdcom_snmp_get_auth_protocol", $result_new))	
 		$sql[] = array("bdcom_execute_sql","Insert into [settings] new parametr [bdcom_snmp_get_auth_protocol]","INSERT INTO settings VALUES ('bdcom_snmp_get_auth_protocol','MD5');");
 if (!in_array("bdcom_snmp_get_priv_passphrase", $result_new))	
 		$sql[] = array("bdcom_execute_sql","Insert into [settings] new parametr [bdcom_snmp_get_priv_passphrase]","INSERT INTO settings VALUES ('bdcom_snmp_get_priv_passphrase','');");
 if (!in_array("bdcom_snmp_get_priv_protocol", $result_new))	
 		$sql[] = array("bdcom_execute_sql","Insert into [settings] new parametr [bdcom_snmp_get_priv_protocol]","INSERT INTO settings VALUES ('bdcom_snmp_get_priv_protocol','DES');");

 if (!in_array("bdcom_mac_addr_font_size", $result_new))	
 		$sql[] = array("bdcom_execute_sql","Insert into [settings] new parametr [bdcom_mac_addr_font_size]","INSERT INTO settings VALUES ('bdcom_mac_addr_font_size','2');");
 if (!in_array("bdcom_script_runtime", $result_new))	
 		$sql[] = array("bdcom_execute_sql","Insert into [settings] new parametr [bdcom_script_runtime]","INSERT INTO settings VALUES ('bdcom_script_runtime','5');");
 if (!in_array("bdcom_collection_timing", $result_new))	
 		$sql[] = array("bdcom_execute_sql","Insert into [settings] new parametr [bdcom_collection_timing]","INSERT INTO settings VALUES ('bdcom_collection_timing','14');");	
if (!in_array("bdcom_enable_msg_rx_change", $result_new))	
 		$sql[] = array("bdcom_execute_sql","Insert into [settings] new parametr [bdcom_enable_msg_rx_change]","INSERT INTO settings VALUES ('bdcom_enable_msg_rx_change','0');");		
if (!in_array("bdcom_enable_msg_fiber", $result_new))	
 		$sql[] = array("bdcom_execute_sql","Insert into [settings] new parametr [bdcom_enable_msg_fiber]","INSERT INTO settings VALUES ('bdcom_enable_msg_fiber','0');");		
	
		
 	$result = db_fetch_assoc("SELECT realm_id FROM user_auth_realm;");
 	foreach($result as $row) {
 		$result_new[] =$row['realm_id'];
 	}
 
 if (!in_array("8888", $result_new))	
 		$sql[] = array("bdcom_execute_sql","Insert into [user_auth_realm] value for admin","INSERT INTO `user_auth_realm` VALUES (3020, 1);");
 if (!in_array("8889", $result_new))	
 		$sql[] = array("bdcom_execute_sql","Insert into [user_auth_realm] value for admin","INSERT INTO `user_auth_realm` VALUES (3021, 1);");
 		

 	
 	if (!in_array('plugin_bdcom_dev_types', $tables)) {
 		$sql[] = array("bdcom_create_table","plugin_bdcom_dev_types","CREATE TABLE `plugin_bdcom_dev_types` (
 		  `device_type_id` int(10) unsigned NOT NULL auto_increment,
 		  `description` varchar(100) NOT NULL default '',
 		  `scanning_function` varchar(100) NOT NULL default '',
 		  PRIMARY KEY  (`device_type_id`)
 		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");	
 		$sql[] = array("bdcom_execute_sql","Insert into [plugin_bdcom_dev_types] new device type [BDCOM P3310C]","INSERT INTO plugin_bdcom_dev_types (`device_type_id`,`description`,`scanning_function`)  VALUES (1,'BDCOM P3310C','scan_p3310C');");
		$sql[] = array("bdcom_execute_sql","Insert into [plugin_bdcom_dev_types] new device type [BDCOM P3608-2TE]","INSERT INTO plugin_bdcom_dev_types (`device_type_id`,`description`,`scanning_function`)  VALUES (2,'BDCOM P3608-2TE','scan_p3310C');");

 	}
 
 	if (!in_array('plugin_bdcom_devices', $tables)) {
 		$sql[] = array("bdcom_create_table","plugin_bdcom_devices","CREATE TABLE `plugin_bdcom_devices` (
				`device_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				`device_type_id` int(10) unsigned DEFAULT '0',
				`hostname` varchar(40) NOT NULL DEFAULT '',
				`description` varchar(100) NOT NULL DEFAULT '',
				`disabled` char(2) DEFAULT '',
				`epon_total` int(10) unsigned NOT NULL default '0',
				`ports_total` int(10) unsigned NOT NULL DEFAULT '0',
				`ports_active` int(10) unsigned NOT NULL DEFAULT '0',
				`onu_total` int(10) unsigned NOT NULL default '0',
				`onu_online_total` int(10) unsigned NOT NULL default '0',  
				`count_unsaved_actions` int(10) unsigned NOT NULL DEFAULT '0',
				`scan_type` tinyint(11) NOT NULL DEFAULT '1',
				`snmp_port` int(10) NOT NULL DEFAULT '161',
				`snmp_timeout` int(10) unsigned NOT NULL DEFAULT '500',
				`snmp_retries` tinyint(11) unsigned NOT NULL DEFAULT '3',
				`snmp_max_oids` int(12) unsigned DEFAULT '10',
				`snmp_sysName` varchar(100) DEFAULT '',
				`snmp_sysLocation` varchar(100) DEFAULT '',
				`snmp_sysContact` varchar(100) DEFAULT '',
				`snmp_sysObjectID` varchar(100) DEFAULT NULL,
				`snmp_sysDescr` varchar(250) DEFAULT NULL,
				`snmp_sysUptime` varchar(100) DEFAULT NULL,
				`snmp_status` int(10) unsigned NOT NULL DEFAULT '0',
				`last_runmessage` varchar(100) DEFAULT '',
				`last_rundate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
				`last_runduration` decimal(10,5) NOT NULL DEFAULT '0.00000',
				`snmp_get_community` varchar(100) DEFAULT NULL,
				`snmp_get_version` tinyint(1) unsigned NOT NULL DEFAULT '1',
				`snmp_get_username` varchar(50) DEFAULT NULL,
				`snmp_get_password` varchar(50) DEFAULT NULL,
				`snmp_get_auth_protocol` varchar(5) DEFAULT '',
				`snmp_get_priv_passphrase` varchar(200) DEFAULT '',
				`snmp_get_priv_protocol` varchar(6) DEFAULT '',
				`snmp_get_context` varchar(64) DEFAULT '',
				`snmp_set_community` varchar(100) DEFAULT NULL,
				`snmp_set_version` tinyint(1) unsigned NOT NULL DEFAULT '1',
				`snmp_set_username` varchar(50) DEFAULT NULL,
				`snmp_set_password` varchar(50) DEFAULT NULL,
				`snmp_set_auth_protocol` varchar(5) DEFAULT '',
				`snmp_set_priv_passphrase` varchar(200) DEFAULT '',
				`snmp_set_priv_protocol` varchar(6) DEFAULT '',
				`snmp_set_context` varchar(64) DEFAULT '',
				`order_id` tinyint(3) DEFAULT '0',
				`color_row` int(10) DEFAULT '0',
				`need_reindex` int(1) NOT NULL DEFAULT '0',
				PRIMARY KEY (`hostname`,`snmp_port`),
				KEY `device_id` (`device_id`),
				KEY `device_type_id` (`device_type_id`)
 		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Devices to be scanned for BDCOM';");
 	}
 
 	if (!in_array('plugin_bdcom_logs', $tables)) {
 		$sql[] = array("bdcom_create_table","plugin_bdcom_logs","CREATE TABLE `plugin_bdcom_logs` (
 		  `log_id` int(10) unsigned NOT NULL auto_increment,
 		  `log_user_id` int(11) NOT NULL default '0',
 		  `log_user_full_name` varchar(60) NOT NULL default '',
 		  `log_date` datetime NOT NULL default '0000-00-00 00:00:00',
 		  `log_object` varchar(20) NOT NULL default '',
 		  `log_object_id` varchar(20) NOT NULL default '',
 		  `log_operation` varchar(20) NOT NULL default '',
 		  `log_device_id` int(11) NOT NULL default '0',
 		  `log_message` text NOT NULL,
 		  `log_rezult_short` varchar(10) NOT NULL default '',
 		  `log_rezult` varchar(80) NOT NULL default '',
 		  `log_check_rezult_short` varchar(10) NOT NULL default '',
 		  `log_check_rezult` varchar(80) NOT NULL default '',
 		  `log_read_this_user` char(2) NOT NULL default '0',
 		  `log_read_admin` char(2) NOT NULL default '0',
 		  `log_saved` char(2) NOT NULL default '0',
 		  PRIMARY KEY  (`log_id`)
 		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
 	}
 
 	if (!in_array('plugin_bdcom_epons', $tables)) {
 		$sql[] = array("bdcom_create_table","plugin_bdcom_epons","CREATE TABLE `plugin_bdcom_epons` (
			  `epon_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `device_id` int(11) NOT NULL DEFAULT '0',
			  `epon_index` smallint(6) NOT NULL DEFAULT '0',
			  `epon_number` smallint(6) NOT NULL DEFAULT '0',
			  `epon_name` varchar(20) NOT NULL DEFAULT '',
			  `epon_descr` varchar(60) NOT NULL DEFAULT '',
			  `epon_adminstatus` int(2) NOT NULL DEFAULT '0',
			  `epon_operstatus` int(2) NOT NULL DEFAULT '0',
			  `epon_linkstatus` int(2) NOT NULL DEFAULT '0',
			  `epon_txpower` smallint(6) NOT NULL DEFAULT '0',
			  `epon_rxpower` smallint(6) NOT NULL DEFAULT '0',
			  `epon_volt` smallint(6) NOT NULL DEFAULT '0',  
			  `epon_temp` smallint(6) NOT NULL DEFAULT '0',  
			  `epon_onu_list` varchar(20) NOT NULL DEFAULT '',
			  `epon_onu_total` int(11) NOT NULL DEFAULT '0',
			  `epon_onu_active_total` int(11) NOT NULL DEFAULT '0',
			  `epon_online` int(2) NOT NULL DEFAULT '0',
			  `epon_scan_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			  `epon_lastchange_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			  PRIMARY KEY (`device_id`,`epon_index`),
			  KEY `epon_id` (`epon_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
 	}
 
 	if (!in_array('plugin_bdcom_onu', $tables)) {
 		$sql[] = array("bdcom_create_table","plugin_bdcom_onu","CREATE TABLE `plugin_bdcom_onu` (
			  `onu_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `onu_macaddr` varchar(20) NOT NULL DEFAULT '',
			  `onu_ipaddr` varchar(20) NOT NULL DEFAULT '',
			  `onu_vendor` varchar(20) NOT NULL DEFAULT '',
			  `onu_version` varchar(20) NOT NULL DEFAULT '',
			  `onu_soft_version` varchar(45) DEFAULT NULL,
			  `device_id` int(11) NOT NULL DEFAULT '0',
			  `onu_bindepon` int(11) NOT NULL DEFAULT '0',
			  `onu_sequence` int(11) NOT NULL DEFAULT '0',
			  `epon_id` int(11) NOT NULL DEFAULT '0',
			  `onu_index` varchar(20) NOT NULL DEFAULT '',
			  `onu_name` varchar(20) NOT NULL DEFAULT '',
			  `onu_descr` varchar(60) NOT NULL DEFAULT '',
			  `onu_agrm_id` int(11) NOT NULL DEFAULT '0',
			  `onu_adminstatus` int(2) NOT NULL DEFAULT '0',
			  `onu_operstatus` int(2) NOT NULL DEFAULT '0',
			  `onu_txpower` smallint(6) NOT NULL DEFAULT '0',
			  `onu_rxpower` smallint(6) NOT NULL DEFAULT '0',
			  `onu_volt` smallint(6) NOT NULL DEFAULT '0',
			  `onu_temp` smallint(6) NOT NULL DEFAULT '0',
			  `onu_level` smallint(6) NOT NULL DEFAULT '0',
			  `onu_rxpower_max` smallint(6) NOT NULL DEFAULT '0',
			  `onu_rxpower_min` smallint(6) NOT NULL DEFAULT '0',
			  `onu_distance` smallint(6) NOT NULL DEFAULT '0',
			  `onu_alivetime` int(11) NOT NULL DEFAULT '0',
			  `onu_aclist` varchar(40) NOT NULL DEFAULT '',
			  `onu_firm` varchar(40) NOT NULL DEFAULT '',
			  `onu_online` int(2) NOT NULL DEFAULT '0',
			  `onu_dereg_status` int(2) NOT NULL DEFAULT '0',
			  `onu_done_reason` char(3) DEFAULT '',
			  `onu_done_view_count` tinyint(1) NOT NULL DEFAULT '0',
			  `onu_first_scan_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			  `onu_scan_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			  `onu_lastchange_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			  `onu_lastreg_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			  `onu_lastdereg_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			  `onu_rxpower_max_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			  `onu_rxpower_min_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			  `onu_rxpower_alert_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			  `onu_rxpower_average` decimal(5,2) NOT NULL DEFAULT '0.00',
			  `onu_rxpower_cnt` int(11) NOT NULL DEFAULT '0',
			  `onu_rxpower_change` decimal(5,2) NOT NULL DEFAULT '0.00',
			  `onu_up_action` varchar(5) NOT NULL DEFAULT '',
			  `onu_dis_alarm` int(2) NOT NULL DEFAULT '0' COMMENT 'If set to 1 - no UP/DOWN FIBER messages',
			  `onu_wait_up` int(2) NOT NULL DEFAULT '0' COMMENT 'If set to 1 - we wait 100 sec - may be onu just reboot and not FIBER DOWN',
			  `onu_us_enduzelid` int(11) NOT NULL,
			  `onu_us_enduzel_descr` varchar(150) DEFAULT NULL,
			  `onu_us_onuid` int(11) unsigned NOT NULL DEFAULT '0',
			  `onu_us_devid` int(11) NOT NULL DEFAULT '0',			  
		  PRIMARY KEY (`device_id`,`onu_macaddr`),
		  KEY `onu_id` (`onu_id`),
		  KEY `idx_plugin_bdcom_onu_onu_macaddr` (`onu_macaddr`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
 	}
	

 	if (!in_array('plugin_bdcom_ports', $tables)) {
 		$sql[] = array("bdcom_create_table","plugin_bdcom_ports","CREATE TABLE `plugin_bdcom_ports` (
 		  `port_id` int(10) unsigned NOT NULL auto_increment,
		  `device_id` int(11) NOT NULL DEFAULT '0',
		  `port_number` int(11) NOT NULL DEFAULT '0',
		  `port_ifndex` int(2) NOT NULL DEFAULT '0',
		  `port_name` varchar(50) NOT NULL DEFAULT '',
		  `port_descr` varchar(50) NOT NULL DEFAULT '',
		  `port_type` int(10) unsigned NOT NULL DEFAULT '0',
		  `port_admin_status` int(2) NOT NULL DEFAULT '0',
		  `port_oper_status` int(2) NOT NULL DEFAULT '0',
		  `port_online` int(2) NOT NULL DEFAULT '0',
		  `scan_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
		  `port_lastchange_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
 		  PRIMARY KEY  (`device_id`,`port_number`),
 		  KEY `port_id` (`port_id`)
 		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
 	}
 	
 	if (!in_array('plugin_bdcom_processes', $tables)) {
 		$sql[] = array("bdcom_create_table","plugin_bdcom_processes","CREATE TABLE `plugin_bdcom_processes` (
 		  `device_id` int(11) NOT NULL default '0',
 		  `process_id` int(10) unsigned default NULL,
 		  `status` varchar(20) NOT NULL default 'Queued',
 		  `start_date` datetime NOT NULL default '0000-00-00 00:00:00',
 		  PRIMARY KEY  (`device_id`)
 		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
 	}
 	
 
 	if (!in_array('plugin_bdcom_scan_functions', $tables)) {
 		$sql[] = array("bdcom_create_table","plugin_bdcom_scan_functions","CREATE TABLE `plugin_bdcom_scan_functions` (
 		  `scanning_function` varchar(100) NOT NULL default '',
 		  `description` varchar(200) NOT NULL default '',
 		  PRIMARY KEY  (`scanning_function`)
 		) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COMMENT='Registered Scanning Functions';");		
 		$sql[] = array("bdcom_execute_sql","Insert into [plugin_bdcom_scan_functions] new function [scan_p3310C]","INSERT INTO plugin_bdcom_scan_functions (`scanning_function`,`description`)  VALUES ('scan_p3310C','');");
 	}
 	

	
 	if (!in_array('plugin_bdcom_t_ports', $tables)) {
 		$sql[] = array("bdcom_create_table","plugin_bdcom_t_ports","CREATE TABLE `plugin_bdcom_t_ports` (
 		  `port_id` int(10)  unsigned NOT NULL auto_increment,
 		  `device_id` int(11) NOT NULL default '0',
 		  `port_number` int(2) NOT NULL default '0',
 		  `port_name` varchar(50) NOT NULL default '',
 		  `port_type` int(10) unsigned NOT NULL default '0',
 		  `port_status` char(2) default '',
 		  `scan_date` datetime NOT NULL default '0000-00-00 00:00:00',
 		  PRIMARY KEY  (`device_id`,`port_number`),
 		  KEY `port_id` (`port_id`)
 		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
 	}
 	

 	if (!in_array('plugin_bdcom_tabs', $tables)) {
 		$sql[] = array("bdcom_create_table","plugin_bdcom_tabs","CREATE TABLE  `plugin_bdcom_tabs` (		
			  `tab_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `tab_name` varchar(45) NOT NULL DEFAULT '',
			  PRIMARY KEY (`tab_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");		
		}

 	if (!in_array('plugin_bdcom_tab_dev', $tables)) {
 		$sql[] = array("bdcom_create_table","plugin_bdcom_tab_dev","CREATE TABLE  `plugin_bdcom_tab_dev` (		
				`row_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				`tab_id` int(10) unsigned NOT NULL,
				`dev_id` int(10) unsigned NOT NULL,
				PRIMARY KEY (`row_id`),
				UNIQUE KEY `dev_on_tab` (`tab_id`,`dev_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");		
		}

 	if (!in_array('plugin_bdcom_vlans', $tables)) {
 		$sql[] = array("bdcom_create_table","plugin_bdcom_vlans","CREATE TABLE  `plugin_bdcom_vlans` (		
				  `row_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				  `device_id` int(11) NOT NULL DEFAULT '0',
				  `vlan_name` varchar(40) NOT NULL DEFAULT '',
				  `vlan_id` int(10) NOT NULL ,
				  `members_ports` char(40) NOT NULL DEFAULT '',
				  `uttagget_ports` char(40) NOT NULL DEFAULT '',
				  `tagget_ports` char(40) NOT NULL DEFAULT '',
				  `forbidden_ports` char(40) NOT NULL DEFAULT '',
				  `uttagget_ports_list` char(100) NOT NULL DEFAULT '',
				  `vlans_scan_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
				  `vlans_active` int(2) NOT NULL DEFAULT '0',
			  PRIMARY KEY (`row_id`),
			  UNIQUE KEY `vlan_on_device` (`device_id`,`vlan_id`),
			  KEY `device_id` (`device_id`),
			  KEY `vlan_id` (`vlan_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='vlans';");		
		}		
		
 	if (!in_array('plugin_bdcom_uzel', $tables)) {
 		$sql[] = array("bdcom_create_table","plugin_bdcom_uzel","CREATE TABLE  `plugin_bdcom_uzel` (		
				  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				  `uzel_id` int(11) NOT NULL DEFAULT '0',
				  `uzel_descr` varchar(100) NOT NULL DEFAULT '',
				  `uzel_onu_up` int(11) NOT NULL DEFAULT '0',
				  `uzel_onu_total` int(11) NOT NULL DEFAULT '0',
				  `uzel_onu_up_last` int(11) NOT NULL DEFAULT '0',
				  `uzel_onu_total_last` int(11) NOT NULL DEFAULT '0',
				  `epon_id` int(11) NOT NULL DEFAULT '0',
				  `uzel_exist` int(1) NOT NULL DEFAULT '0',
			  PRIMARY KEY (`id`),
			  UNIQUE KEY `uzel_id` (`uzel_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='uzels';");		
		}
		
	switch($old) {
	case '0.1':
		$sql[] = array("bdcom_add_column","plugin_bdcom_onu","onu_done_view_count","alter table `plugin_bdcom_onu` add column `onu_done_view_count` TINYINT(1) NOT NULL DEFAULT 0 AFTER `onu_dereg_status`;");
		$old = '0.2'; 
	case '0.2':
		$sql[] = array("bdcom_add_column","plugin_bdcom_onu","onu_done_reason","alter table `plugin_bdcom_onu` add column `onu_done_reason` char(3) DEFAULT '' AFTER `onu_dereg_status`;");
		$old = '0.3'; 	
	case '0.3':
		$sql[] = array("bdcom_add_column","plugin_bdcom_devices","need_reindex","alter table `plugin_bdcom_devices` add column `need_reindex` int(1) NOT NULL DEFAULT '0' ;");
		$old = '0.4'; 
	case '0.4':
		$sql[] = array("bdcom_modify_column","plugin_bdcom_devices","snmp_sysDescr","ALTER TABLE `plugin_bdcom_devices` MODIFY COLUMN `snmp_sysDescr` varchar(250) ;");
		$sql[] = array("bdcom_modify_column","plugin_bdcom_onu","onu_us_enduzelid","ALTER TABLE `plugin_bdcom_onu` MODIFY COLUMN `onu_us_enduzelid` int(11) NOT NULL DEFAULT '0' ;");
		$old = '0.5'; 		

	}		
		
	
 
 	if (!empty($sql)) {
 		for ($a = 0; $a < count($sql); $a++) {
 			$step_sql = $sql[$a];
 			$rezult = '';
 			switch ($step_sql[0]) {
 				case 'bdcom_execute_sql':
 					$rezult = bdcom_execute_sql ($step_sql[1], $step_sql[2]);
 					break;
 				case 'bdcom_create_table':
 					$rezult = bdcom_create_table ($step_sql[1], $step_sql[2]);
 					break;
 				case 'bdcom_add_column':
 					$rezult = bdcom_add_column ($step_sql[1], $step_sql[2],$step_sql[3]);
 					break;				
 				case 'bdcom_modify_column':
 					$rezult = bdcom_modify_column ($step_sql[1], $step_sql[2],$step_sql[3]);
 					break;
 				case 'bdcom_delete_column':
 					$rezult = bdcom_delete_column ($step_sql[1], $step_sql[2],$step_sql[3]);
 					break;
 				case 'bdcom_add_index':
 					$rezult = bdcom_add_index ($step_sql[1], $step_sql[2],$step_sql[3]);
 					break;
 				case 'bdcom_delete_index':
 					$rezult = bdcom_delete_index ($step_sql[1], $step_sql[2],$step_sql[3]);
 					break;
 			}
 			bdcom_raise_message3(array("device_descr" => "Обновление до версии [" . $new . "]" , "type" => "update_db", "object"=> "update","cellpading" => false, "message" => $rezult["message"], "step_rezult" => $rezult["step_rezult"], "step_data" => $rezult["step_data"]));     
 			//$result = db_execute($sql[$a]);
 			//bdcom_raise_message3(array("device_descr" => "Обновление до версии [" . $new . "]" , "type" => "title_count", "object"=> "update","cellpading" => false, "message" => $sql[$a], "count_rez" => ($result == 1) ));     
 		}
 	}
 
  db_execute('REPLACE INTO settings (name, value) VALUES ("bdcom_version", "' .  $new . '")');
 }
 
 function bdcom_execute_sql($message, $syntax) {
 	$result = db_execute($syntax);
 	$return_rezult = array();
 	
 	if ($result) {
 		$return_rezult['message'] =  "SUCCESS: Execute SQL,   $message";
 		$return_rezult['step_rezult'] = 'OK';
 	}else{
 		$return_rezult['message'] =  "ERROR: Execute SQL,   $message";
 		$return_rezult['step_rezult'] = 'Error';
 	}
 	$return_rezult['step_data'] = $return_rezult['step_rezult'] ;
 	return $return_rezult;
 }
 
 function bdcom_create_table($table, $syntax) {
 	$tables = db_fetch_assoc("SHOW TABLES LIKE '$table'");
 	$return_rezult = array();
 
 	if (!sizeof($tables)) {
 		$result = db_execute($syntax);
 		if ($result) {
 			$return_rezult['message'] =  "SUCCESS: Create Table,  Table -> $table";
 			$return_rezult['step_rezult'] = 'OK';
 		}else{
 			$return_rezult['message'] =  "ERROR: Create Table,  Table -> $table";
 			$return_rezult['step_rezult'] = 'Error';
 		}
 		$return_rezult['step_data'] = $return_rezult['step_rezult'] ;
 	}else{
 		$return_rezult['message'] =  "SUCCESS: Create Table,  Table -> $table";
 		$return_rezult['step_rezult'] = 'OK';
 		$return_rezult['step_data'] = "Already Exists";
 	}
 	return $return_rezult;
 }
 
 function bdcom_add_column($table, $column, $syntax) {
 	$return_rezult = array();
 	$columns = db_fetch_assoc("SHOW COLUMNS FROM $table LIKE '$column'");
 
 	if (sizeof($columns)) {
 		$return_rezult['message'] = "SUCCESS: Add Column,    Table -> $table, Column -> $column";
 		$return_rezult['step_rezult'] = 'OK';
 		$return_rezult['step_data'] = "Already Exists";
 	}else{
 		$result = db_execute($syntax);
 
 		if ($result) {
 			$return_rezult['message'] ="SUCCESS: Add Column,    Table -> $table, Column -> $column";
 			$return_rezult['step_rezult'] = 'OK';
 		}else{
 			$return_rezult['message'] ="ERROR: Add Column,    Table -> $table, Column -> $column";
 			$return_rezult['step_rezult'] = 'Error';
 		}
 		$return_rezult['step_data'] = $return_rezult['step_rezult'] ;
 	}
 	return $return_rezult;
 }
 
 function bdcom_add_index($table, $index, $syntax) {
 	$tables = db_fetch_assoc("SHOW TABLES LIKE '$table'");
 	$return_rezult = array();
 
 	if (sizeof($tables)) {
 		$indexes = db_fetch_assoc("SHOW INDEXES FROM $table");
 
 		$index_exists = FALSE;
 		if (sizeof($indexes)) {
 			foreach($indexes as $index_array) {
 				if ($index == $index_array["Key_name"]) {
 					$index_exists = TRUE;
 					break;
 				}
 			}
 		}
 
 		if ($index_exists) {
 			$return_rezult['message'] =  "SUCCESS: Add Index,     Table -> $table, Index -> $index";
 			$return_rezult['step_rezult'] = 'OK';
 			$return_rezult['step_data'] = "Already Exists";
 		}else{
 			$result = db_execute($syntax);
 
 			if ($result) {
 				$return_rezult['message'] =  "SUCCESS: Add Index,     Table -> $table, Index -> $index";
 				$return_rezult['step_rezult'] = 'OK';
 			}else{
 				$return_rezult['message'] =  "ERROR: Add Index,     Table -> $table, Index -> $index";
 				$return_rezult['step_rezult'] = 'Error';
 			}
 			$return_rezult['step_data'] = $return_rezult['step_rezult'] ;
 		}
 	}else{
 		$return_rezult['message'] ="ERROR: Add Index,     Table -> $table, Index -> $index";
 		$return_rezult['step_rezult'] = 'Error';
 		$return_rezult['step_data'] = 'Table Does NOT Exist';
 	}
 	return $return_rezult;
 }
 
 function bdcom_modify_column($table, $column, $syntax) {
 	$tables = db_fetch_assoc("SHOW TABLES LIKE '$table'");
 	$return_rezult = array();
 
 	if (sizeof($tables)) {
 		$columns = db_fetch_assoc("SHOW COLUMNS FROM $table LIKE '$column'");
 
 		if (sizeof($columns)) {
 			$result = db_execute($syntax);
 
 			if ($result) {
 				$return_rezult['message'] =  "SUCCESS: Modify Column, Table -> $table, Column -> $column";
 				$return_rezult['step_rezult'] = 'OK';
 			}else{
 				$return_rezult['message'] =  "ERROR: Modify Column, Table -> $table, Column -> $column";
 				$return_rezult['step_rezult'] = 'Error';
 			}
 			$return_rezult['step_data'] = $return_rezult['step_rezult'] ;
 		}else{
 			$return_rezult['message'] =  "ERROR: Modify Column, Table -> $table, Column -> $column";
 			$return_rezult['step_rezult'] = 'Error';
 			$return_rezult['step_data'] = "Column Does NOT Exist";
 		}
 	}else{
 		$return_rezult['message'] =  "ERROR: Modify Column, Table -> $table, Column -> $column";
 		$return_rezult['step_rezult'] = 'Error';
 		$return_rezult['step_data'] = 'Table Does NOT Exist';
 	}
 	return $return_rezult;
 }
 
 function bdcom_delete_column($table, $column, $syntax) {
 	$tables = db_fetch_assoc("SHOW TABLES LIKE '$table'");
 	$return_rezult = array();
 
 	if (sizeof($tables)) {
 		$columns = db_fetch_assoc("SHOW COLUMNS FROM $table LIKE '$column'");
 
 		if (sizeof($columns)) {
 			$result = db_execute($syntax);
 
 			if ($result) {
 				$return_rezult['message'] =  "SUCCESS: Delete Column, Table -> $table, Column -> $column";
 				$return_rezult['step_rezult'] = 'OK';
 			}else{
 				$return_rezult['message'] =  "ERROR: Delete Column, Table -> $table, Column -> $column";
 				$return_rezult['step_rezult'] = 'Error';
 			}
 			$return_rezult['step_data'] = $return_rezult['step_rezult'] ;
 		}else{
 			$return_rezult['message'] =  "SUCCESS: Delete Column, Table -> $table, Column -> $column";
 			$return_rezult['step_rezult'] = 'Error';
 			$return_rezult['step_data'] = "Column Does NOT Exist";			
 		}
 	}else{
 		$return_rezult['message'] =  "SUCCESS: Delete Column, Table -> $table, Column -> $column";
 		$return_rezult['step_rezult'] = 'Error';
 		$return_rezult['step_data'] = 'Table Does NOT Exist';
 	}
 	return $return_rezult;
 }
 
 function bdcom_delete_index($table, $index, $syntax) {
 	$tables = db_fetch_assoc("SHOW TABLES LIKE '$table'");
 	$return_rezult = array();
 
 	if (sizeof($tables)) {
 		$indexes = db_fetch_assoc("SHOW INDEXES FROM $table");
 
 		$index_exists = FALSE;
 		if (sizeof($indexes)) {
 			foreach($indexes as $index_array) {
 				if ($index == $index_array["Key_name"]) {
 					$index_exists = TRUE;
 					break;
 				}
 			}
 		}
 
 		if (!$index_exists) {
 			$return_rezult['message'] =  "SUCCESS: Delete Index,     Table -> $table, Index -> $index";
 			$return_rezult['step_rezult'] = 'OK';
 			$return_rezult['step_data'] = "Index Does NOT Exist!";
 		}else{
 			$result = db_execute($syntax);
 
 			if ($result) {
 				$return_rezult['message'] =  "SUCCESS: Delete Index,     Table -> $table, Index -> $index";
 				$return_rezult['step_rezult'] = 'OK';
 			}else{
 				$return_rezult['message'] =  "ERROR: Delete Index,     Table -> $table, Index -> $index";
 				$return_rezult['step_rezult'] = 'Error';
 			}
 			$return_rezult['step_data'] = $return_rezult['step_rezult'] ;
 		}
 	}else{
 		$return_rezult['message'] ="ERROR: Delete Index,     Table -> $table, Index -> $index";
 		$return_rezult['step_rezult'] = 'Error';
 		$return_rezult['step_data'] = 'Table Does NOT Exist';
 	}
 	return $return_rezult;
 }
 	
 	
 	?>
