<?php
 /*
  +-------------------------------------------------------------------------+
  | Copyright (C) 2007 Susanin                                         |
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
 chdir('../../');
 include("./include/auth.php");
 include_once("./lib/snmp.php");
  include_once($config['base_path'] . "/plugins/bdcom/lib/bdcom_functions.php");
 
 
  
 $device_actions = array(
	1 => __('Delete'),
	2 => __('Enable'),
	3 => __('Disable'),
	4 => __('Change SNMP Options'),
	5 => __('Update info'),
	6 => __('Save configs')
);

set_default_action();


switch (get_request_var('action')) {
	case 'save':
		form_bdcom_save();

		break;
	case 'actions':
		form_device_actions();

		break;
	case 'edit':
		top_header();
		bdcom_device_edit();
		bottom_footer();

		break;
	case 'import':
		top_header();
		bdcom_device_import();
		bottom_footer();

		break;
	default:
		if (isset_request_var('import')) {
			header('Location: bdcom_devices.php?action=import');
		}elseif (isset_request_var('export')) {
			bdcom_device_export();
		}else{
			top_header();
			bdcom_device();
			bottom_footer();
		}

		break;
}



 /* --------------------------
     The Save Function
    -------------------------- */
 
 function form_bdcom_save() {
 	if ((isset_request_var('save_component_device')) && (isempty_request_var('add_dq_y'))) {
 		$device_id = api_bdcom_device_save(get_nfilter_request_var('device_id'), 
 			get_nfilter_request_var('hostname'), get_nfilter_request_var('device_type_id'), get_nfilter_request_var('description'),get_nfilter_request_var('order_id'),get_nfilter_request_var('color_row'),  get_nfilter_request_var('snmp_max_oids'), 
 			get_nfilter_request_var('snmp_get_version'), get_nfilter_request_var('snmp_get_community'),  get_nfilter_request_var('snmp_get_username'), get_nfilter_request_var('snmp_get_password'), get_nfilter_request_var('snmp_get_auth_protocol'),get_nfilter_request_var('snmp_get_priv_passphrase'),get_nfilter_request_var('snmp_get_priv_protocol'),get_nfilter_request_var('snmp_get_context'),
 			get_nfilter_request_var('snmp_set_version'), get_nfilter_request_var('snmp_set_community'), get_nfilter_request_var('snmp_set_username'), get_nfilter_request_var('snmp_set_password'),get_nfilter_request_var('snmp_set_auth_protocol'),get_nfilter_request_var('snmp_set_priv_passphrase'),get_nfilter_request_var('snmp_set_priv_protocol'),get_nfilter_request_var('snmp_set_context'),
 			get_nfilter_request_var('snmp_port'), get_nfilter_request_var('snmp_timeout'),
 			get_nfilter_request_var('snmp_retries'), 
 			(isset_request_var('disabled') ? get_nfilter_request_var('disabled') : ''));
 
 		if ((is_error_message()) || (get_nfilter_request_var('device_id') != get_nfilter_request_var('_device_id'))) {
 			header("Location: bdcom_devices.php?action=edit&device_id=" . (empty($device_id) ? get_nfilter_request_var('device_id') : $device_id));
 		}else{
 			header("Location: bdcom_devices.php");
 		}
 	}
 
 	if (isset_request_var('save_component_import')) {
 		if (($_FILES['import_file']['tmp_name'] != 'none') && ($_FILES['import_file']['tmp_name'] != '')) {
 			/* file upload */
 			$csv_data = file($_FILES['import_file']['tmp_name']);
 
 			/* obtain debug information if it's set */
 			$debug_data = bdcom_device_import_processor($csv_data);
 			if(sizeof($debug_data) > 0) {
 				$_SESSION['import_debug_info'] = $debug_data;
 			}
 		}else{
 			header('Location: bdcom_devices.php?action=import'); exit;
 		}
 
 		header('Location: bdcom_devices.php?action=import');
 	}
 }
 
 function api_bdcom_device_remove($device_id){
 	db_execute('DELETE FROM plugin_bdcom_devices WHERE device_id=' . $device_id);
 	db_execute('DELETE FROM plugin_bdcom_ports WHERE device_id=' . $device_id);
	db_execute('DELETE FROM plugin_bdcom_epons WHERE device_id=' . $device_id);
	db_execute('DELETE FROM plugin_bdcom_onu WHERE device_id=' . $device_id);
 }
 
 function api_bdcom_device_save($device_id, $hostname, $device_type_id, $description, $order_id, $color_row, $snmp_max_oids,
 			$snmp_get_version,$snmp_get_community,  $snmp_get_username, $snmp_get_password,$snmp_get_auth_protocol,$snmp_get_priv_passphrase,$snmp_get_priv_protocol,$snmp_get_context,
 			$snmp_set_version,$snmp_set_community,  $snmp_set_username, $snmp_set_password,$snmp_set_auth_protocol,$snmp_set_priv_passphrase,$snmp_set_priv_protocol,$snmp_set_context,
 			$snmp_port, $snmp_timeout, $snmp_retries,
 			$disabled) {
 			
 	$save['device_id'] = $device_id;
 	$save['hostname'] = form_input_validate($hostname, 'hostname', '', false, 3);
 	$save['device_type_id'] = form_input_validate($device_type_id, 'device_type_id', '', false, 3);
 	$save['description'] = form_input_validate($description, 'description', '', false, 3);
	$save['order_id'] = form_input_validate($order_id, 'order_id', '', false, 3);
	$save['color_row'] = form_input_validate($color_row, 'color_row', '', false, 3);
 	
 	$save['snmp_get_community'] = form_input_validate($snmp_get_community, 'snmp_get_community', '', true, 3);
 	$save['snmp_get_version'] = form_input_validate($snmp_get_version, 'snmp_get_version', '', true, 3);
 	$save['snmp_get_username'] = form_input_validate($snmp_get_username, 'snmp_get_username', '', true, 3);
 	$save['snmp_get_password'] = form_input_validate($snmp_get_password, 'snmp_get_password', '', true, 3);
 	$save['snmp_get_auth_protocol']   = form_input_validate($snmp_get_auth_protocol, 'snmp_get_auth_protocol', '', true, 3);
 	$save['snmp_get_priv_passphrase'] = form_input_validate($snmp_get_priv_passphrase, 'snmp_get_priv_passphrase', '', true, 3);
 	$save['snmp_get_priv_protocol']   = form_input_validate($snmp_get_priv_protocol, 'snmp_get_priv_protocol', '', true, 3);
 	$save['snmp_get_context']         = form_input_validate($snmp_get_context, 'snmp_get_context', '', true, 3);	
 	
 	$save['snmp_set_community'] = form_input_validate($snmp_set_community, 'snmp_set_community', '', true, 3);
 	$save['snmp_set_version'] = form_input_validate($snmp_set_version, 'snmp_set_version', '', true, 3);
 	$save['snmp_set_username'] = form_input_validate($snmp_set_username, 'snmp_set_username', '', true, 3);
 	$save['snmp_set_password'] = form_input_validate($snmp_set_password, 'snmp_set_password', '', true, 3);
 	$save['snmp_set_auth_protocol']   = form_input_validate($snmp_set_auth_protocol, 'snmp_set_auth_protocol', '', true, 3);
 	$save['snmp_set_priv_passphrase'] = form_input_validate($snmp_set_priv_passphrase, 'snmp_set_priv_passphrase', '', true, 3);
 	$save['snmp_set_priv_protocol']   = form_input_validate($snmp_set_priv_protocol, 'snmp_set_priv_protocol', '', true, 3);
 	$save['snmp_set_context']         = form_input_validate($snmp_set_context, 'snmp_set_context', '', true, 3);	
 	
 	$save['snmp_port'] = form_input_validate($snmp_port, 'snmp_port', '^[0-9]+$', false, 3);
 	$save['snmp_timeout'] = form_input_validate($snmp_timeout, 'snmp_timeout', '^[0-9]+$', false, 3);
 	$save['snmp_retries'] = form_input_validate($snmp_retries, 'snmp_retries', '^[0-9]+$', false, 3);
 	$save['snmp_max_oids'] = form_input_validate($snmp_max_oids, 'snmp_max_oids', '^[0-9]+$', false, 3);
 	$save['disabled'] = form_input_validate($disabled, 'disabled', '', true, 3);
 
 	$device_id = 0;
 	if (!is_error_message()) {
 		$device_id = sql_save($save, 'plugin_bdcom_devices', 'device_id');
 
 		if ($device_id) {
 			raise_message(1);
 		}else{
 			raise_message(2);
 		}
 	}
 
 	return $device_id;
 }
 
 
 /* ------------------------
     The "actions" function
    ------------------------ */
 
 function form_device_actions() {
 	global $colors, $config, $device_actions, $fields_bdcom_device_edit;
 
 	/* if we are to save this form, instead of display it */
 	if (isset_request_var('selected_items')) {
 		$selected_items = unserialize(stripslashes(get_nfilter_request_var('selected_items')));
 
 		if (get_nfilter_request_var('drp_action') == "2") { /* Enable Selected Devices */
 			for ($i=0;($i<count($selected_items));$i++) {
 				/* ================= input validation ================= */
 				input_validate_input_number($selected_items[$i]);
 				/* ==================================================== */
 
 				db_execute("update plugin_bdcom_devices set disabled='' where device_id='" . $selected_items[$i] . "'");
 			}
 		}elseif (get_nfilter_request_var('drp_action') == "3") { /* Disable Selected Devices */
 			for ($i=0;($i<count($selected_items));$i++) {
 				/* ================= input validation ================= */
 				input_validate_input_number($selected_items[$i]);
 				/* ==================================================== */
 
 				db_execute("update plugin_bdcom_devices set disabled='on' where device_id='" . $selected_items[$i] . "'");
 			}
 		}elseif (get_nfilter_request_var('drp_action') == "4") { /* change snmp options */
 			for ($i=0;($i<count($selected_items));$i++) {
 				/* ================= input validation ================= */
 				input_validate_input_number($selected_items[$i]);
 				/* ==================================================== */
 
 				reset($fields_bdcom_device_edit);
				foreach ($fields_bdcom_device_edit as $field_name => $field_array) {
 					if (isset_request_var('t_$field_name')) {
 						db_execute("update plugin_bdcom_devices set $field_name = '" . get_request_var($field_name) . "' where device_id='" . $selected_items[$i] . "'");
 					}
 				}
 			}
         }elseif (get_nfilter_request_var('drp_action') == "5") { /* Poll NOW */
             for ($i=0;($i<count($selected_items));$i++) {
                 /* ================= input validation ================= */
                 input_validate_input_number($selected_items[$i]);
                 /* ==================================================== */
                 run_poller_bdcom($selected_items[$i]);
             }
         
         
         }elseif (get_nfilter_request_var('drp_action') == "6") { /* change port settngs for multiple devices */
 			for ($i=0;($i<count($selected_items));$i++) {
 				/* ================= input validation ================= */
 				input_validate_input_number($selected_items[$i]);
 				/* ==================================================== */
 
 				reset($fields_bdcom_device_edit);
				foreach ($fields_host_edit as $field_name => $field_array) {
 					if (isset_request_var('t_$field_name')) {
 						db_execute("update plugin_bdcom_devices set $field_name = '" . get_request_var($field_name) . "' where id='" . $selected_items[$i] . "'");
 					}
 				}
 			}
 		}elseif (get_nfilter_request_var('drp_action') == "1") { /* delete */
 			for ($i=0; $i<count($selected_items); $i++) {
 				/* ================= input validation ================= */
 				input_validate_input_number($selected_items[$i]);
 				/* ==================================================== */
 
 				api_bdcom_device_remove($selected_items[$i+1]);
 			}
 		}
 
 		header("Location: bdcom_devices.php");
 		exit;
 	}
 
 	/* setup some variables */
 	$device_list = ""; $i = 0;
 
 	/* loop through each of the host templates selected on the previous page and get more info about them */
 	foreach ($_POST as $var => $val) {
 		if (preg_match("/^chk_([0-9]+)$/", $var, $matches)) {
 			/* ================= input validation ================= */
 			input_validate_input_number($matches[1]);
 			/* ==================================================== */
 
 			$device_info = db_fetch_row("SELECT hostname, description FROM plugin_bdcom_devices WHERE device_id=" . $matches[1]);
 			$device_list .= "<li>" . $device_info['description'] . " (" . $device_info['hostname'] . ")<br>";
 			$device_array[$i] = $matches[1];
	 		$i++;
 		}
 
 	}
 
 	include_once("./include/top_header.php");
 
 	html_start_box("<strong>" . $device_actions{get_nfilter_request_var('drp_action')} . "</strong>", "60%", $colors['header_panel'], "3", "center", "");
 
 	print "<form action='bdcom_devices.php' method='post'>\n";
 
 	if (get_nfilter_request_var('drp_action') == "2") { /* Enable Devices */
 		print "	<tr>
 				<td colspan='2' class='textArea' bgcolor='#" . $colors['form_alternate1']. "'>
 					<p>To enable the following devices, press the \"yes\" button below.</p>
 					<p>$device_list</p>
 				</td>
 				</tr>";
 	}elseif (get_nfilter_request_var('drp_action') == "3") { /* Disable Devices */
 		print "	<tr>
 				<td colspan='2' class='textArea' bgcolor='#" . $colors['form_alternate1']. "'>
 					<p>To disable the following devices, press the \"yes\" button below.</p>
 					<p>$device_list</p>
 				</td>
 				</tr>";
 	}elseif (get_nfilter_request_var('drp_action') == "4") { /* change snmp options */
 		print "	<tr>
 				<td colspan='2' class='textArea' bgcolor='#" . $colors['form_alternate1']. "'>
 					<p>To change SNMP parameters for the following devices, check the box next to the fields
 					you want to update, fill in the new value, and click Save.</p>
 					<p>$device_list</p>
 				</td>
 				</tr>";
 				$form_array = array();
				foreach ($fields_bdcom_device_edit as $field_name => $field_array) {
 					if (preg_match("/^snmp_/", $field_name)) {
 						$form_array += array($field_name => $fields_bdcom_device_edit[$field_name]);
 
 						$form_array[$field_name]["value"] = "";
 						$form_array[$field_name]["description"] = "";
 						$form_array[$field_name]["form_id"] = 0;
 						$form_array[$field_name]["sub_checkbox"] = array(
 							"name" => "t_" . $field_name,
 							"friendly_name" => "Update this Field",
 							"value" => ""
 							);
 					}
 				}
 
 				draw_edit_form(
 					array(
 						"config" => array("no_form_tag" => true),
 						"fields" => $form_array
 						)
 					);
 	}elseif (get_nfilter_request_var('drp_action') == "1") { /* delete */
 		print "	<tr>
 				<td class='textArea' bgcolor='#" . $colors['form_alternate1']. "'>
 					<p>Are you sure you want to delete the following devices?</p>
 					<p>$device_list</p>
 				</td>
 			</tr>\n
 			";
 	}
 
 	if (!isset($device_array)) {
 		print "<tr><td bgcolor='#" . $colors['form_alternate1']. "'><span class='textError'>You must select at least one device.</span></td></tr>\n";
 		$save_html = "";
 	}else{
 		$save_html = "<input type='image' src='" . $config['url_path'] . "images/button_yes.gif' alt='Save' align='absmiddle'>";
 	}
 
 	print "	<tr>
 			<td colspan='2' align='right' bgcolor='#eaeaea'>
 				<input type='hidden' name='action' value='actions'>
 				<input type='hidden' name='selected_items' value='" . (isset($device_array) ? serialize($device_array) : '') . "'>
 				<input type='hidden' name='drp_action' value='" . get_nfilter_request_var('drp_action') . "'>
 				<a href='bdcom_devices.php'><img src='" . $config['url_path'] . "images/button_no.gif' alt='Cancel' align='absmiddle' border='0'></a>
 				$save_html
 			</td>
 		</tr>
 		";
 
 	html_end_box();
 
 	include_once("./include/bottom_footer.php");
 }
 
 /* ---------------------
     bdcom Device Functions
    --------------------- */
 
 function bdcom_device_remove() {
 	global $config;
 
 	/* ================= input validation ================= */
 	input_validate_input_number(get_request_var("device_id"));
 	input_validate_input_number(get_request_var("device_type_id"));
 	/* ==================================================== */
 
 	if ((read_config_option("remove_verification") == "on") && (!isset($_GET['confirm']))) {
 		include("./include/top_header.php");
 		form_confirm("Are You Sure?", "Are you sure you want to delete the host <strong>'" . db_fetch_cell("select description from host where id=" . $_GET['device_id']) . "'</strong>?", "bdcom_devices.php", "bdcom_devices.php?action=remove&id=" . $_GET['device_id']);
 		include("./include/bottom_footer.php");
 		exit;
 	}
 
 	if ((read_config_option("remove_verification") == "") || (isset($_GET['confirm']))) {
 		api_bdcom_device_remove($_GET['device_id']);
 	}
 }
 
 function bdcom_device_edit() {
 	global $colors, $fields_bdcom_device_edit;
 
 	/* ================= input validation ================= */
 	input_validate_input_number(get_request_var("device_id"));
 	/* ==================================================== */

 	display_output_messages();
 
 	if (!empty($_GET['device_id'])) {
 		$device = db_fetch_row("select * from plugin_bdcom_devices where device_id=" . $_GET['device_id']);
 		$header_label = "[edit: " . $device['description'] . "]";
 	}else{
 		$header_label = "[new]";
 	}
 
 	if (!empty($device['device_id'])) {
 		?>
 		<table width="98%" align="center">
 			<tr>
 				<td class="textInfo" colspan="2">
 					<?php print $device['description'];?> (<?php print $device['hostname'];?>)
 				</td>
 			</tr>
 			<tr>
 				<td class="textHeader">
 					SNMP Information<br>
 
 					<span style="font-size: 10px; font-weight: normal; font-family: monospace;">
 					<?php
 					/* force php to return numeric oid's */
 					if (function_exists("snmp_set_oid_numeric_print")) {
 						snmp_set_oid_numeric_print(TRUE);
 					}
					#http://bugs.cacti.net/view.php?id=2296
					if (function_exists("snmp_set_oid_output_format")) {
							snmp_set_oid_output_format(SNMP_OID_OUTPUT_NUMERIC);
					}	
					
 					$snmp_system = cacti_snmp_get($device['hostname'], $device['snmp_get_community'], ".1.3.6.1.2.1.1.1.0", $device['snmp_get_version'], $device['snmp_get_username'], $device['snmp_get_password'], $device['snmp_get_auth_protocol'], $device['snmp_get_priv_passphrase'], $device['snmp_get_priv_protocol'],  $device['snmp_get_context'],$device['snmp_port'], $device['snmp_timeout'], $device['snmp_retries'], SNMP_WEBUI);
 
 					if ($snmp_system == "") {
 						print "<span style='color: #ff0000; font-weight: bold;'>SNMP error</span>\n";
 					}else{
 
 						$snmp_uptime=0;
						$snmp_uptime = cacti_snmp_get($device['hostname'], $device['snmp_get_community'], ".1.3.6.1.2.1.1.3.0", $device['snmp_get_version'], $device['snmp_get_username'], $device['snmp_get_password'], $device['snmp_get_auth_protocol'], $device['snmp_get_priv_passphrase'], $device['snmp_get_priv_protocol'],  $device['snmp_get_context'],$device['snmp_port'], $device['snmp_timeout'], $device['snmp_retries'], SNMP_WEBUI);
 						$snmp_hostname = cacti_snmp_get($device['hostname'], $device['snmp_get_community'], ".1.3.6.1.2.1.1.5.0", $device['snmp_get_version'], $device['snmp_get_username'], $device['snmp_get_password'], $device['snmp_get_auth_protocol'], $device['snmp_get_priv_passphrase'], $device['snmp_get_priv_protocol'],  $device['snmp_get_context'],$device['snmp_port'], $device['snmp_timeout'], $device['snmp_retries'], SNMP_WEBUI);
 						$snmp_objid = cacti_snmp_get($device['hostname'], $device['snmp_get_community'], ".1.3.6.1.2.1.1.2.0", $device['snmp_get_version'], $device['snmp_get_username'], $device['snmp_get_password'], $device['snmp_get_auth_protocol'], $device['snmp_get_priv_passphrase'], $device['snmp_get_priv_protocol'],  $device['snmp_get_context'],$device['snmp_port'], $device['snmp_timeout'], $device['snmp_retries'], SNMP_WEBUI);
						$snmp_serial = cacti_snmp_get($device['hostname'], $device['snmp_get_community'], ".1.3.6.1.4.1.171.12.1.1.12.0", $device['snmp_get_version'], $device['snmp_get_username'], $device['snmp_get_password'], $device['snmp_get_auth_protocol'], $device['snmp_get_priv_passphrase'], $device['snmp_get_priv_protocol'],  $device['snmp_get_context'],$device['snmp_port'], $device['snmp_timeout'], $device['snmp_retries'], SNMP_WEBUI);
 						
 						$snmp_objid = str_replace("enterprises", ".1.3.6.1.4.1", $snmp_objid);
 						$snmp_objid = str_replace("OID: ", "", $snmp_objid);
 						$snmp_objid = str_replace(".iso", ".1", $snmp_objid);
 
 							$days = intval($snmp_uptime / (60*60*24*100));
 							$remainder = $snmp_uptime % (60*60*24*100);
 							$hours = intval($remainder / (60*60*100));
 							$remainder = $remainder % (60*60*100);
 							$minutes = intval($remainder / (60*100));
						print '<strong>' . __('System:', 'bdcom') . "</strong> $snmp_system<br>\n";
						print '<strong>' . __('Uptime:', 'bdcom') . "</strong> ($days days, $hours hours, $minutes minutes)<br>\n";
						print '<strong>' . __('Hostname:', 'bdcom') . "</strong> $snmp_hostname<br>\n";
						print '<strong>' . __('ObjectID:', 'bdcom') . "</strong> $snmp_objid<br>\n";

 					}
 					?>
 					</span>
 				</td>
 			</tr>
 		</table>
 		<br>
 		<?php
 	}

	form_start('bdcom_devices.php');

	html_start_box($header_label, '100%', '', '3', 'center', '');
	

 
	/* preserve the devices site id between refreshes via a GET variable */
	if (!isempty_request_var('site_id')) {
		$fields_host_edit['site_id']['value'] = get_request_var('site_id');
	}
 
 	draw_edit_form(array(
 		"config" => array("form_name" => "chk"),
		"config" => array("no_form_tag" => true),
 		"fields" => inject_form_variables($fields_bdcom_device_edit, (isset($device) ? $device : array()))
 		));
 

	html_end_box();


 	?>
 	<script type="text/javascript">
 	<!--
 
 	// default snmp information
 	var snmp_get_community       = document.getElementById('snmp_get_community').value;
 	var snmp_get_username        = document.getElementById('snmp_get_username').value;
 	var snmp_get_password        = document.getElementById('snmp_get_password').value;
 	var snmp_get_auth_protocol   = document.getElementById('snmp_get_auth_protocol').value;
 	var snmp_get_priv_passphrase = document.getElementById('snmp_get_priv_passphrase').value;
 	var snmp_get_priv_protocol   = document.getElementById('snmp_get_priv_protocol').value;
 	var snmp_get_context         = document.getElementById('snmp_get_context').value;
 
 	var snmp_set_community       = document.getElementById('snmp_set_community').value;
 	var snmp_set_username        = document.getElementById('snmp_set_username').value;
 	var snmp_set_password        = document.getElementById('snmp_set_password').value;
 	var snmp_set_auth_protocol   = document.getElementById('snmp_set_auth_protocol').value;
 	var snmp_set_priv_passphrase = document.getElementById('snmp_set_priv_passphrase').value;
 	var snmp_set_priv_protocol   = document.getElementById('snmp_set_priv_protocol').value;
 	var snmp_set_context         = document.getElementById('snmp_set_context').value;
 
 
 
 	function changeBdcomHostForm() {
 		snmp_get_version        = document.getElementById('snmp_get_version').value;
 		snmp_set_version        = document.getElementById('snmp_set_version').value;
 
 
 		switch(snmp_get_version) {
 		case "1":
 		case "2":
 			setSNMP("v1v2", "get");
 
 			break;
 		case "3":
 			setSNMP("v3", "get");
 
 			break;
 		}
 		switch(snmp_set_version) {
 		case "1":
 		case "2":
 			setSNMP("v1v2", "set");
 
 			break;
 		case "3":
 			setSNMP("v3", "set");
 
 			break;
 		}		
 	}
 
 	function setSNMP(snmp_type, snmp_t) {
 		switch(snmp_type) {
 		case "v1v2":
 			document.getElementById('row_snmp_' + snmp_t + '_username').style.display        = "none";
 			document.getElementById('row_snmp_' + snmp_t + '_password').style.display        = "none";
 			document.getElementById('row_snmp_' + snmp_t + '_community').style.display       = "";
 			document.getElementById('row_snmp_' + snmp_t + '_auth_protocol').style.display   = "none";
 			document.getElementById('row_snmp_' + snmp_t + '_priv_passphrase').style.display = "none";
 			document.getElementById('row_snmp_' + snmp_t + '_priv_protocol').style.display   = "none";
 			document.getElementById('row_snmp_' + snmp_t + '_context').style.display         = "none";
 
 
 			break;
 		case "v3":
 			document.getElementById('row_snmp_' + snmp_t + '_username').style.display        = "";
 			document.getElementById('row_snmp_' + snmp_t + '_password').style.display        = "";
 			document.getElementById('row_snmp_' + snmp_t + '_community').style.display       = "none";
 			document.getElementById('row_snmp_' + snmp_t + '_auth_protocol').style.display   = "";
 			document.getElementById('row_snmp_' + snmp_t + '_priv_passphrase').style.display = "";
 			document.getElementById('row_snmp_' + snmp_t + '_priv_protocol').style.display   = "";
 			document.getElementById('row_snmp_' + snmp_t + '_context').style.display         = "";
 
 
 			break;
 		}
 	}
 
 	window.onload = changeBdcomHostForm();
 
 	-->
 	</script>
 	<?php
 
 	form_save_button("bdcom_devices.php", "", "device_id");
 }
 
 function bdcom_get_devices(&$sql_where) {
 	/* form the 'where' clause for our main sql query */
 	$sql_where = "WHERE ((plugin_bdcom_devices.hostname like '%%" . get_request_var('filter') . "%%' OR plugin_bdcom_devices.description like '%%" . get_request_var('filter') . "%%')";
 
 	if (get_request_var('status') == "-1") {
 		/* Show all items */
 	}elseif (get_request_var('status') == "-2") {
 		$sql_where .= " AND plugin_bdcom_devices.disabled='on'";
 	}else {
 		$sql_where .= " AND (plugin_bdcom_devices.snmp_status=" . get_request_var('status') . " AND plugin_bdcom_devices.disabled = '')";
 	}
 
 	if (get_request_var('device_type_id') == "-1") {
 		/* Show all items */
 	}else{
 		$sql_where .= " AND (plugin_bdcom_devices.device_type_id=" . get_request_var('device_type_id') . ")";
 	}
 
 		$sql_where .= ")";
 		
 	return db_fetch_assoc("SELECT
 		plugin_bdcom_devices.device_id,
 		plugin_bdcom_dev_types.description as dev_type_description,
 		plugin_bdcom_devices.description,
 		plugin_bdcom_devices.hostname,
		plugin_bdcom_devices.order_id,
		plugin_bdcom_devices.color_row,
 		plugin_bdcom_devices.count_unsaved_actions,
 		plugin_bdcom_devices.snmp_port,
 		plugin_bdcom_devices.snmp_timeout,
 		plugin_bdcom_devices.snmp_retries,
 		plugin_bdcom_devices.snmp_status,
 		plugin_bdcom_devices.disabled,
 		plugin_bdcom_devices.epon_total,
   	    plugin_bdcom_devices.ports_total,
 		plugin_bdcom_devices.onu_total,
 		plugin_bdcom_devices.ports_active,
 		plugin_bdcom_devices.last_rundate,
 		plugin_bdcom_devices.last_runmessage,
 		plugin_bdcom_devices.last_runduration,
        plugin_bdcom_devices.snmp_get_community,
        plugin_bdcom_devices.snmp_get_version,
        plugin_bdcom_devices.snmp_get_username,
        plugin_bdcom_devices.snmp_get_password,
        plugin_bdcom_devices.snmp_set_community,
        plugin_bdcom_devices.snmp_set_version,
        plugin_bdcom_devices.snmp_set_username,
        plugin_bdcom_devices.snmp_set_password
 		FROM plugin_bdcom_dev_types
 		RIGHT JOIN plugin_bdcom_devices ON plugin_bdcom_dev_types.device_type_id = plugin_bdcom_devices.device_type_id
 		$sql_where
 		ORDER BY " . get_request_var('sort_column') . " " . get_request_var('sort_direction') . "
 		LIMIT " . (read_config_option("bdcom_num_rows")*(get_request_var('page')-1)) . "," . read_config_option("bdcom_num_rows"));
 }

 function bdcom_device_request_validation() {
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

			
	);

	validate_store_request_vars($filters, 'sess_bdcom_device');
	/* ================= input validation ================= */
	
	
 }

 
 function bdcom_format_device_row($device, $actions=false) {
	global $config;

	/* viewer level */
	if ($actions) {
		$row = "<a href='" . htmlspecialchars($config['url_path'] . 'plugins/bdcom/bdcom_interfaces.php?device_id=' . $device['device_id'] . '&issues=0&page=1') . "'><img src='" . $config['url_path'] . "plugins/bdcom/images/view_interfaces.gif' alt='' title='" . __('View Interfaces', 'bdcom') . "'></a>";

		/* admin level */
		if (api_user_realm_auth('bdcom_devices.php')) {
			if ($device['disabled'] == '') {
				$row .= "<img id='r_" . $device['device_id'] . "' src='" . $config['url_path'] . "plugins/bdcom/images/rescan_device.gif' alt='' onClick='scan_device(" . $device['device_id'] . ")' title='" . __('Rescan Device', 'bdcom') . "'>";
			} else {
				$row .= "<img src='" . $config['url_path'] . "plugins/bdcom/images/view_none.gif' alt=''>";
			}
		}

		print "<td style='width:40px;'>" . $row . "</td>";
	}

	
		$bgc = db_fetch_cell("SELECT hex FROM colors WHERE id='" . $device['color_row'] . "'");
		//form_alternate_row_color($colors['alternate'], $colors['light'], $i, 'line' . $device['device_id']); $i++;
		form_selectable_cell(filter_value($device['description'], get_request_var('filter'), "bdcom_devices.php?action=edit&device_id=" . $device['device_id']), $device['device_id']);
		form_selectable_cell($device['order_id'], $device['order_id']);
		form_selectable_cell(get_colored_device_status(($device['disabled'] == "on" ? true : false), $device['snmp_status']), $device['device_id'] );
		form_selectable_cell(filter_value($device['hostname'], get_request_var('filter')), $device['device_id']);
		form_selectable_cell("<a class='linkEditMain' href='bdcom_view_epons.php?m_device_id=+" . $device['device_id'] . "&m_ip_filter_type_id=1&m_ip_filter=&m_mac_filter_type_id=1&m_mac_filter=&i_port_filter_type_id=&i_port_filter=&m_rows_selector=-1&m_filter=&m_page=1&report=macs&x=22&y=4'>" . $device['epon_total'] . "</a>", $device['device_id']);
		form_selectable_cell("<a class='linkEditMain' href='bdcom_view_ports.php?report=ports&p_device_type_id=-1&p_device_id=+" . $device['device_id'] . "&p_status=-1&p_zerro_status=-1&p_filter=&p_page=1&report=ports&x=11&y=7'>" . $device['ports_total'] . "</a>", $device['device_id']);
		form_selectable_cell("<a class='linkEditMain' href='bdcom_view_onus.php?report=onus&p_device_type_id=-1&p_device_id=+" . $device['device_id'] . "&p_status=2&p_zerro_status=-1&p_filter=&p_page=1&report=ports&x=11&y=7'>" . $device['onu_total'] . "</a>", $device['device_id']);							
		form_selectable_cell(bdcom_format_datetime($device['last_rundate']), $device['device_id'] );
		form_selectable_cell(number_format($device['last_runduration']), $device['device_id'] );				
		form_selectable_cell("<a class='linkEditMain' href='bdcom_devices.php?action=device_query&id=1&host_id=" . $device['device_id'] . "'><img src='../../images/reload_icon_small.gif' alt='Reload Data Query' border='0' align='absmiddle'></a>", $device['device_id']);
		form_checkbox_cell($device['description'], $device['device_id']);	
		form_end_row();

}



 
 function bdcom_device() {
 	global $colors, $device_actions, $bdcom_device_types, $config;
 
	bdcom_device_request_validation();
	
	if (get_request_var('rows') == -1) {
		$rows = read_config_option('num_rows_table');
	} elseif (get_request_var('rows') == -2) {
		$rows = 999999;
	} else {
		$rows = get_request_var('rows');
	}

	html_start_box(__('BDCOM Device Filters', 'bdcom'), '100%', '', '3', 'center', 'bdcom_devices.php?action=edit&status=' . get_request_var('status'));
	bdcom_device_filter();
	html_end_box();
 
 	$sql_where = "";
 
 	$devices = bdcom_get_devices($sql_where);
 
 	$total_rows = db_fetch_cell("SELECT
 		COUNT(plugin_bdcom_devices.device_id)
 		FROM plugin_bdcom_devices
 		$sql_where");
 
  	$display_text = array(
	
 		'description' => array(__('Device Name', 'bdcom'), 'ASC'),
		'description' => array(__('Host<br>Description', 'bdcom'), 'ASC'),
		'order_id' => array(__('ord', 'bdcom'), 'ASC'),
 		'disabled' => array(__('Status', 'bdcom'), 'ASC'),
 		'hostname' => array(__('Hostname', 'bdcom'), 'ASC'),
 		'epon_total' => array(__('epon', 'bdcom'), 'ASC'),
 		'ports_total' => array(__('Ports', 'bdcom'), 'ASC'),
 		'onu_total' => array(__('ONU', 'bdcom'), 'ASC'),
 		'last_rundate' => array(__('Last<br>Run date', 'bdcom'), 'ASC'),
 		'last_runduration' => array(__('Last<br>Duration', 'bdcom'), 'ASC'),
		' ' => array(' ','DESC'));
 
	$columns = sizeof($display_text) + 1;

	$nav = html_nav_bar('bdcom_devices.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, $columns, __('Devices', 'bdcom'), 'page', 'main');

	form_start('bdcom_devices.php', 'chk');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort_checkbox($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);

	if (cacti_sizeof($devices)) {
		foreach ($devices as $device) {
			form_alternate_row('line' . $device['device_id'], true);
			bdcom_format_device_row($device);
		}
	} else {
		print '<tr><td colspan="' . $columns  . '"><em>' . __('No DBCOM Devices', 'bdcom') . '</em></td></tr>';
	}

	html_end_box(false);

	if (cacti_sizeof($devices)) {
		print $nav;
	}

	/* draw the dropdown containing a list of available actions for this form */
	draw_actions_dropdown($device_actions);

	form_end();	
	
}
 
function bdcom_device_filter() {
	global $item_rows;

	?>
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
					<td>
						<?php print __('Status', 'bdcom');?>
					</td>
					<td>
						<select id='status' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('status') == '-1') {?> selected<?php }?>><?php print __('Any', 'bdcom');?></option>
							<option value='3'<?php if (get_request_var('status') == '3') {?> selected<?php }?>><?php print __('Up', 'bdcom');?></option>
							<option value='-2'<?php if (get_request_var('status') == '-2') {?> selected<?php }?>><?php print __('Disabled', 'bdcom');?></option>
							<option value='1'<?php if (get_request_var('status') == '1') {?> selected<?php }?>><?php print __('Down', 'bdcom');?></option>
							<option value='0'<?php if (get_request_var('status') == '0') {?> selected<?php }?>><?php print __('Unknown', 'bdcom');?></option>
							<option value='4'<?php if (get_request_var('status') == '4') {?> selected<?php }?>><?php print __('Error', 'bdcom');?></option>
						</select>
					</td>
					<td>
						<?php print __('Devices', 'bdcom');?>
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
			strURL  = urlPath+'plugins/bdcom/bdcom_devices.php?header=false';
			strURL += '&status=' + $('#status').val();
			strURL += '&device_type_id=' + $('#device_type_id').val();
			strURL += '&filter=' + $('#filter').val();
			strURL += '&rows=' + $('#rows').val();
			loadPageNoHeader(strURL);
		}

		function clearFilter() {
			strURL  = urlPath+'plugins/bdcom/bdcom_devices.php?header=false&clear=true';
			loadPageNoHeader(strURL);
		}

		function exportRows() {
			strURL  = urlPath+'plugins/bdcom/bdcom_devices.php?export=true';
			document.location = strURL;
		}

		function importRows() {
			strURL  = urlPath+'plugins/bdcom/bdcom_devices.php?import=true';
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


 
 