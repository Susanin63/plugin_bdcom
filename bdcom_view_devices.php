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
 $device_actions = array(
    5 => __('Update info'),
 	7 => __('Save configs')
 	);
	
bdcom_convert_port_to_hex(5);
bdcom_convert_port_to_hex("5-8");
bdcom_convert_port_to_hex("1,2");
bdcom_convert_port_to_hex("22,28");
bdcom_convert_port_to_hex("74");

$title = __('BDCOM - Device Report View', 'bdcom');

/* check actions */
switch (get_request_var('action')) {
	case 'actions':
		form_actions_devices();

		break;
	default:
		bdcom_redirect();
		general_header();
		bdcom_view_devices();
		bottom_footer();
		break;
}


 /*bdcom_view_get_ip_range_records
 Делает выборку данных 
 */
 function host_device_query() {
 	/* ================= input validation ================= */
 	input_validate_input_number(get_request_var("id"));
 	input_validate_input_number(get_request_var("host_id"));
 	/* ==================================================== */
 
 	run_poller_bdcom($_GET['host_id']);
 }


function bdcom_view_get_device_records(&$sql_where, $rows = '30', $apply_limits = TRUE) {

	/* form the 'where' clause for our main sql query */
	if (get_request_var('filter') != '') {
		$sql_where = (strlen($sql_where) ? ' AND ': 'WHERE ') . "(plugin_bdcom_devices.hostname like '%" . get_request_var('filter') . "%'
			OR plugin_bdcom_devices.description like '%" . get_request_var('filter') . "%')";
	}
	
	if (get_request_var('device_type_id') == '-1') {
		/* Show all items */
	} elseif (get_request_var('device_type_id') == '-2') {
		$sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . "(plugin_bdcom_devices.device_type_id=='')";
	} else {
		$sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . '(plugin_bdcom_devices.device_type_id=' . get_request_var('device_type_id') . ')';
	}
	
 
     if (get_request_var('status') == "-1") {
         /* Show all items */
     }elseif (get_request_var('status') == "-2") {
         $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " (plugin_bdcom_devices.disabled='on')";
     }else {
         $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . ' (plugin_bdcom_devices.snmp_status=' . get_request_var('status') . ") AND (plugin_bdcom_devices.disabled = '')";
     }
 
  	$sortby = get_request_var('sort_column');
 	if ($sortby=="hostname") {
 		$sortby = "INET_ATON(hostname)";
 	}

	$sql_order = get_order_string();
	
	if ($apply_limits) {
		$sql_limit = ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ', ' . $rows;
	}else{
		$sql_limit = '';
	}
	
     $sql_query = "SELECT
        plugin_bdcom_devices.device_id,
        plugin_bdcom_dev_types.description as dev_type_description,
        plugin_bdcom_devices.description,
		plugin_bdcom_devices.order_id,
		plugin_bdcom_devices.color_row,
        plugin_bdcom_devices.hostname,
        plugin_bdcom_devices.snmp_get_community,
        plugin_bdcom_devices.snmp_get_version,
 		plugin_bdcom_devices.snmp_get_username,
 		plugin_bdcom_devices.snmp_get_password,
        plugin_bdcom_devices.snmp_set_community,
        plugin_bdcom_devices.snmp_set_version,
 		plugin_bdcom_devices.snmp_set_username,
 		plugin_bdcom_devices.snmp_set_password,
        plugin_bdcom_devices.snmp_port,
        plugin_bdcom_devices.snmp_timeout,
        plugin_bdcom_devices.snmp_retries,
        plugin_bdcom_devices.snmp_status,
        plugin_bdcom_devices.disabled,
        plugin_bdcom_devices.epon_total,
 		plugin_bdcom_devices.count_unsaved_actions,
 		plugin_bdcom_devices.ports_total,
        plugin_bdcom_devices.onu_total,
        plugin_bdcom_devices.ports_active,
        plugin_bdcom_devices.last_rundate,
        plugin_bdcom_devices.last_runmessage,
        plugin_bdcom_devices.last_runduration
        FROM plugin_bdcom_dev_types
        RIGHT JOIN plugin_bdcom_devices ON plugin_bdcom_dev_types.device_type_id = plugin_bdcom_devices.device_type_id
        $sql_where
        $sql_order
		$sql_limit";
 

 
     return db_fetch_assoc($sql_query);
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

	validate_store_request_vars($filters, 'sess_bdcomv_device');
	/* ================= input validation ================= */
	
	
 }

 
 function bdcom_view_devices() {
     global $title, $report, $colors, $bdcom_search_types, $rows_selector, $config, $device_actions;
 
	bdcom_device_request_validation();

	if (get_request_var('rows') == -1) {
		$rows = read_config_option('bdcom_num_rows');
	} elseif (get_request_var('rows') == -2) {
		$rows = 999999;
	} else {
		$rows = get_request_var('rows');
	}
	
	$webroot = $config['url_path'] . 'plugins/bdcom/';
	bdcom_tabs();

	html_start_box($title, '100%', '', '3', 'center', 'bdcom_devices.php?action=edit&status=' . get_request_var('status'));
	bdcom_device_filter();
	html_end_box(); 
	//bdcom_group_tabs();


	$sql_where = '';

    $devices = bdcom_view_get_device_records($sql_where, $rows);
 	//$devices_sum = bdcom_view_get_device_records($sql_where);
 	
 	$devices_sum = db_fetch_row("SELECT        count(plugin_bdcom_devices.device_id),
               sum(plugin_bdcom_devices.epon_total),
               sum(plugin_bdcom_devices.count_unsaved_actions),
               sum(plugin_bdcom_devices.epon_total),
               sum(plugin_bdcom_devices.ports_total),
               sum(plugin_bdcom_devices.onu_total),
               sum(plugin_bdcom_devices.ports_active),
               sum(plugin_bdcom_devices.last_runduration)
 			FROM plugin_bdcom_dev_types
 			RIGHT JOIN plugin_bdcom_devices ON plugin_bdcom_dev_types.device_type_id = plugin_bdcom_devices.device_type_id
 			$sql_where
 			ORDER BY plugin_bdcom_devices.hostname LIMIT 0,45");
 	
     $total_rows = db_fetch_cell("SELECT
         COUNT(plugin_bdcom_devices.device_id)
         FROM plugin_bdcom_devices
         $sql_where");

  	$display_text = array(
	
 		'description' => array(__('Device Name', 'bdcom'), 'ASC'),
		'order_id' => array(__('ord', 'bdcom'), 'ASC'),
 		"snmp_status" => array(__('Status', 'bdcom'), "ASC"),
 		'hostname' => array(__('Hostname', 'bdcom'), 'ASC'),
 		'epon_total' => array(__('epon', 'bdcom'), 'ASC'),
 		'ports_total' => array(__('Ports', 'bdcom'), 'ASC'),
 		'onu_total' => array(__('ONU', 'bdcom'), 'ASC'),
 		'last_rundate' => array(__('Last<br>Run date', 'bdcom'), 'ASC'),
 		'last_runduration' => array(__('Last<br>Duration', 'bdcom'), 'ASC'),
		' ' => array(' ','DESC')); 

	$columns = sizeof($display_text) + 1;  
	
	$nav = html_nav_bar('bdcom_view_devices.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, $columns, __('Devices', 'bdcom'), 'page', 'main');
	
	form_start('bdcom_view_devices.php', 'chk');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort_checkbox($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);

	$str_count_epon_total = 0;
 	$str_count_ports_total = 0;
 	$str_count_onu_total = 0;
	
	if (cacti_sizeof($devices)) {
		foreach ($devices as $device) {
			form_alternate_row('line' . $device['device_id'], true);
			$str_count_epon=0;
			$str_count_port=0;
			$str_count_onu=0;                
			if ($device['snmp_status'] == 3) {
				$str_count_epon = $device['epon_total'];
				$str_count_port = $device['ports_total'];
				$str_count_onu = $device['onu_total'];
			}
			$str_count_epon_total = $str_count_epon_total + $str_count_epon;
			$str_count_ports_total = $str_count_ports_total + $str_count_port;
			$str_count_onu_total = $str_count_onu_total + $str_count_onu;
			
			bdcom_format_device_row($device);

		}
 		?>
		<tr>
			<td>ИТОГО:</td>
			<td></td>
			<td></td>
			<td></td>

			 <td >
				 <a class="linkEditMain" href="bdcom_view_epons.php?device_id-1=&ip_filter_type_id=1&ip_filter=&mac_filter_type_id=1&mac_filter=&port_filter_type_id=&port_filter=&filter=&report=epons"><?php print $str_count_epon_total ;?></a>
			 </td>
			 <td >
				 <a class="linkEditMain" href="bdcom_view_ports.php?device_id=-1&ip_filter_type_id=1&ip_filter=&port_filter=&mac_filter_type_id=1&mac_filter=&port_filter_type_id=&port_filter=&filter=&report=ports"><?php print $str_count_ports_total ;?></a>
			 </td >	
			 <td >
				 <a class="linkEditMain" href="bdcom_view_onus.php?device_type_id=-1&device_id=-1&status=-1&filter=&report=onus"><?php print $str_count_onu_total ;?></a>
			 </td >
		</tr>
		<?php		
		
		
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
		form_selectable_cell(filter_value($device['description'], get_request_var('filter'), "bdcom_devices.php?action=edit&device_id=" . $device['device_id']), $device['device_id']);
		form_selectable_cell($device['order_id'], $device['order_id']);
		form_selectable_cell(get_colored_device_status(($device['disabled'] == "on" ? true : false), $device['snmp_status']), $device['device_id'] );
		form_selectable_cell(filter_value($device['hostname'], get_request_var('filter')), $device['device_id']);
		form_selectable_cell("<a class='linkEditMain' href='bdcom_view_epons.php?report=epons&device_id=+" . $device['device_id'] . "&port_filter_type_id=&port_filter=&filter='>" . $device['epon_total'] . "</a>", $device['device_id']);
		form_selectable_cell("<a class='linkEditMain' href='bdcom_view_ports.php?report=ports&device_type_id=-1&device_id=+" . $device['device_id'] . "&status=-1&filter='>" . $device['ports_total'] . "</a>", $device['device_id']);
		form_selectable_cell("<a class='linkEditMain' href='bdcom_view_onus.php?report=onus&device_type_id=-1&device_id=+" . $device['device_id'] . "&epon_id=-1&ip_filter_type_id=1&ip_filter=&uzel_id=-1&mac_filter_type_id=1&mac_filter=&filter='>" . $device['onu_total'] . "</a>", $device['device_id']);							
		form_selectable_cell(bdcom_format_datetime($device['last_rundate']), $device['device_id'] );
		form_selectable_cell(number_format($device['last_runduration']), $device['device_id'] );				
		form_selectable_cell("<a class='linkEditMain' href='bdcom_view_devices.php?action=actions&drp_action=5&id=1&selected_items=" . serialize(array(1=>$device["device_id"])) . "'><img src='../../images/reload_icon_small.gif' alt='Reload Data Query' border='0' align='absmiddle'></a>", $device['device_id']);
		form_checkbox_cell($device['description'], $device['device_id']);	
		form_end_row();

}




 

 function form_actions_devices() {
     global $colors, $config, $device_actions;


	/* ================= input validation ================= */
	get_filter_request_var('drp_action');
	/* ==================================================== */

	
     /* if we are to save this form, instead of display it */
     if (isset_request_var('selected_items')) {
        $selected_items = sanitize_unserialize_selected_items(get_nfilter_request_var('selected_items'));
         if (get_request_var('drp_action') == "5") { /* Опросить устройство */
			foreach ($selected_items as $item) {
                 /* ================= input validation ================= */
                 input_validate_input_number($item);
                 /* ==================================================== */
 
 				run_poller_bdcom($item);
             }
             header("Location: bdcom_view_devices.php");
 
         } elseif (get_request_var('drp_action') == "7") { /* Сохранить конфигурацию */
            foreach ($selected_items as $item) {
                 /* ================= input validation ================= */
                 input_validate_input_number($item);
                 /* ==================================================== */
 				
 				//bdcom_save_config_main($item);
             }		
 		}
		header("Location: bdcom_view_devices.php");
         exit;
     }
 
     /* setup some variables */
     $row_list = ""; $i = 0;
 
     /* loop through each of the ports selected on the previous page and get more info about them для создания первой страницы типа [Вы действительно хотите ....]*/
     foreach ($_POST as $var => $val) {
         if (preg_match("/^chk_([0-9]+)$/", $var, $matches)) {
             /* ================= input validation ================= */
             input_validate_input_number($matches[1]);
             /* ==================================================== */
 			$device_info = db_fetch_row("SELECT hostname, description FROM plugin_bdcom_devices WHERE device_id=" . $matches[1]);
 			$row_list .= "<li>" . $device_info['description'] . " (" . $device_info['hostname'] . ")<br>";
 			$row_array[$i] = $matches[1];
			$i++;
		 }                                  
 
         
     }
 
 	top_header();

	form_start('bdcom_view_devices.php?header=false');

	html_start_box($device_actions[get_request_var('drp_action')], '60%', '', '3', 'center', '');     
 
 
     if (get_request_var('drp_action') == "5") {  /* Update Info */
			print "<tr>
				<td colspan='2' class='textArea'>
					<p>" . __('Click \'Continue\' to update info on the following devices.', 'bdcom') . "</p>
					<ul>$row_list</ul>
				</td>
			</tr>";          
     } else if (get_request_var('drp_action') == "7") { /*Сохранить конфигурацию*/
			print "<tr>
				<td colspan='2' class='textArea'>
					<p>" . __('Click \'Continue\' to save config on the following devices.', 'bdcom') . "</p>
					<ul>$row_list</ul>
				</td>
			</tr>";        
 	}
 
 	
     if (!isset($row_array)) {
         print "<tr><td bgcolor='#" . $colors['form_alternate1']. "'><span class='textError'>Вы должны выбрать хотябы одно устройство.</span></td></tr>\n";
         $save_html = "";
     }else{
		$save_html = "<input type='submit' name='Save' value='Продолжить'>";
     }
 	
     print "    <tr>
             <td colspan='2' align='right' bgcolor='#eaeaea'>
                 <input type='hidden' name='action' value='actions'>
                 <input type='hidden' name='selected_items' value='" . (isset($row_array) ? serialize($row_array) : '') . "'>
                 <input type='hidden' name='drp_action' value='" . get_request_var('drp_action') . "'>
                 <input type='submit' name='Cancel' value='Отменить'>
                 $save_html
             </td>
         </tr>
         ";
 
     html_end_box();
 }
 

 

 
function bdcom_device_filter() {
	global $item_rows;

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
			strURL  = urlPath+'plugins/bdcom/bdcom_view_devices.php?header=false';
			strURL += '&status=' + $('#status').val();
			strURL += '&device_type_id=' + $('#device_type_id').val();
			strURL += '&filter=' + $('#filter').val();
			strURL += '&rows=' + $('#rows').val();
			loadPageNoHeader(strURL);
		}

		function clearFilter() {
			strURL  = urlPath+'plugins/bdcom/bdcom_view_devices.php?header=false&clear=true';
			loadPageNoHeader(strURL);
		}

		function exportRows() {
			strURL  = urlPath+'plugins/bdcom/bdcom_view_devices.php?export=true';
			document.location = strURL;
		}

		function importRows() {
			strURL  = urlPath+'plugins/bdcom/bdcom_view_devices.php?import=true';
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
