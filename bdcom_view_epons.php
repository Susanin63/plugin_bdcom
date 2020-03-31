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

 $epon_actions = array(
 	2 => __('Change Port Name'),
 	3 => __('Enable Port'),
 	4 => __('Disable Port'),
 	);
 	

$title = __('BDCOM - PORT Report View', 'bdcom');

/* check actions */
switch (get_request_var('action')) {
	case 'actions':
		form_actions_epons();

		break;
	default:
		bdcom_redirect();
		general_header();
		bdcom_view_epons();
		bottom_footer();
		break;
}



function bdcom_view_get_epon_records(&$sql_where, $rows = '30', $apply_limits = TRUE) {

	/* form the 'where' clause for our main sql query */
	if (get_request_var('filter') != '') {
		$sql_where = (strlen($sql_where) ? ' AND ': 'WHERE ') . "(plugin_bdcom_epons.epon_name like '%" . get_request_var('filter') . "%'
			OR plugin_bdcom_epons.epon_descr like '%" . get_request_var('filter') . "%'
			OR plugin_bdcom_epons.epon_number like '%" . get_request_var('filter') . "%')";
	}

    if (!(get_request_var('device_id') == "-1")) {
         
		 $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " plugin_bdcom_epons.device_id=" . get_request_var('device_id');
    }
	
	if (get_request_var('device_type_id') == '-1') {
		/* Show all items */
	} elseif (get_request_var('device_type_id') == '-2') {
		$sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . "(plugin_bdcom_devices.device_type_id=='')";
	} else {
		$sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . '(plugin_bdcom_devices.device_type_id=' . get_request_var('device_type_id') . ')';
	}
	
 
	 switch (get_request_var('status')) {
		 case "-1": /* do not filter */
			break;
		 case "1": /* Down */
			$sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') .  " plugin_bdcom_epons.epon_operstatus = 2 ";
			 break;
		 case "2": /* UP */
			$sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') .  " plugin_bdcom_epons.epon_operstatus = 1 ";
			break;
		 case "3": /* Disabled */
			$sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') .  " plugin_bdcom_epons.epon_adminstatus = 2 ";
			break;
		 case "4": /* Enable */
			$sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') .  " plugin_bdcom_epons.epon_adminstatus = 1 ";
			break;					
			
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
	
	$query_string = "SELECT
			plugin_bdcom_epons.epon_id,
			plugin_bdcom_epons.device_id,
			plugin_bdcom_epons.epon_index,
			plugin_bdcom_epons.epon_number,
			plugin_bdcom_epons.epon_name,
			plugin_bdcom_epons.epon_descr,
			plugin_bdcom_epons.epon_adminstatus,
			plugin_bdcom_epons.epon_operstatus,
			plugin_bdcom_epons.epon_linkstatus,
			plugin_bdcom_epons.epon_txpower,
			plugin_bdcom_epons.epon_rxpower,
			plugin_bdcom_epons.epon_volt,
			plugin_bdcom_epons.epon_temp,
			plugin_bdcom_epons.epon_onu_list,
			plugin_bdcom_epons.epon_onu_total,
			plugin_bdcom_epons.epon_onu_active_total,
			plugin_bdcom_epons.epon_online,
			plugin_bdcom_epons.epon_scan_date,
			plugin_bdcom_epons.epon_lastchange_date,
            plugin_bdcom_devices.hostname,
            plugin_bdcom_devices.description,
			h.id
            FROM plugin_bdcom_epons  
			LEFT JOIN plugin_bdcom_devices on plugin_bdcom_epons.device_id=plugin_bdcom_devices.device_id   
			LEFT JOIN host h ON plugin_bdcom_devices.hostname = h.hostname
        $sql_where
        $sql_order
		$sql_limit";
 

 
     return db_fetch_assoc($query_string);
 }
 
 function bdcom_epon_request_validation() {
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
			'default' => 'epon_number',
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
		'epon_number' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '',
			'pageset' => true
			),			

			
	);

	validate_store_request_vars($filters, 'sess_bdcomv_epon');
	/* ================= input validation ================= */
	
	
 }

 
 function bdcom_view_epons() {
    global $title, $report, $colors, $rows_selector, $config, $epon_actions;
 
	bdcom_epon_request_validation();

	if (get_request_var('rows') == -1) {
		$rows = read_config_option('num_rows_table');
	} elseif (get_request_var('rows') == -2) {
		$rows = 999999;
	} else {
		$rows = get_request_var('rows');
	}
	
	$webroot = $config['url_path'] . 'plugins/bdcom/';
	bdcom_tabs();

	html_start_box($title, '100%', '', '3', 'center', 'bdcom_view_epons.php?action=edit&status=' . get_request_var('status'));
	bdcom_epon_filter();
	html_end_box(); 
	//bdcom_group_tabs();


	$sql_where = '';

    $epons = bdcom_view_get_epon_records($sql_where, $rows);    
	
    $total_rows = db_fetch_cell("SELECT
            COUNT(plugin_bdcom_epons.epon_number)
            FROM plugin_bdcom_epons
			LEFT JOIN plugin_bdcom_devices on plugin_bdcom_epons.device_id=plugin_bdcom_devices.device_id 
			LEFT JOIN host h ON plugin_bdcom_devices.hostname = h.hostname			 
            $sql_where");
 	

  	$display_text = array(
	
 		'description' => array(__('Device Name', 'bdcom'), 'ASC'),
 		'hostname' => array(__('Hostname', 'bdcom'), 'ASC'),
 		'epon_number' => array(__('Port number', 'bdcom'), 'ASC'),
 		'epon_name' => array(__('Port name', 'bdcom'), 'ASC'),
 		'epon_descr' => array(__('Description', 'bdcom'), 'ASC'),
		'epon_txpower' => array(__('TX Power', 'bdcom'), 'ASC'),
		'epon_onu_total' => array(__('ONU Total', 'bdcom'), 'ASC'),
		'epon_onu_active_total' => array(__('ONU Active', 'bdcom'), 'ASC'),
		'epon_status' => array(__('Status', 'bdcom'), 'ASC'),
 		'epon_lastchange_date' => array(__('Change Date', 'bdcom'), 'ASC'),
		'epon_scan_date' => array(__('Scan Date', 'bdcom'), 'ASC')); 

	$columns = sizeof($display_text) ;  
	
	$nav = html_nav_bar('bdcom_view_epons.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, $columns, __('Epons ports', 'bdcom'), 'page', 'main');
	
	form_start('bdcom_view_epons.php', 'chk');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort_checkbox($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);


	if (cacti_sizeof($epons)) {
		foreach ($epons as $epon) {
			form_alternate_row('line' . $epon['epon_id'], true);
			bdcom_format_epon_row($epon);

		}
		
		
	} else {
		print '<tr><td colspan="' . $columns  . '"><em>' . __('No DBCOM Epon ports', 'bdcom') . '</em></td></tr>';
	}

	html_end_box(false);

	if (cacti_sizeof($epons)) {
		print $nav;
	}

	/* draw the dropdown containing a list of available actions for this form */
	draw_actions_dropdown($epon_actions);

	form_end();		
	
}
 
 


 function bdcom_format_epon_row($epon, $actions=false) {
	global $config;

	
		//$bgc = db_fetch_cell("SELECT hex FROM colors WHERE id='" . $epon['color_row'] . "'");
		form_selectable_cell(filter_value($epon['description'], get_request_var('filter'), "bdcom_devices.php?action=edit&device_id=" . $epon['device_id']), $epon['epon_id']);
		form_selectable_cell(filter_value($epon['hostname'], get_request_var('filter')), $epon['epon_id']);
		form_selectable_cell(filter_value($epon['epon_number'], get_request_var('filter')), $epon['epon_id']);
		form_selectable_cell(filter_value($epon['epon_name'], get_request_var('filter')), $epon['epon_id']);
		form_selectable_cell(filter_value($epon['epon_descr'], get_request_var('filter')), $epon['epon_id']);

		form_selectable_cell($epon['epon_txpower']*0.1, $epon['epon_id'] );
			//onu total
		form_selectable_cell("<a class='linkEditMain' href='bdcom_view_onus.php?report=onus&device_type_id=-1&device_id=+" . $epon['device_id'] . "&uzel_id=-1&epon_id=+" . $epon['epon_id'] . "&status=-1&filter=&page=1'>" . $epon['epon_onu_total'] . "</a>", $epon['epon_id']);
			//onu active total
		form_selectable_cell("<a class='linkEditMain' href='bdcom_view_onus.php?report=onus&device_type_id=-1&device_id=+" . $epon['device_id'] . "&uzel_id=-1&epon_id=+" . $epon['epon_id'] . "&status=1&filter=&page=1'>" . $epon['epon_onu_active_total'] . "</a>", $epon['epon_id']);		
		
		
		form_selectable_cell(bdcom_convert_status_2str($epon['epon_operstatus']) . " [" . bdcom_convert_status_2str($epon['epon_adminstatus']) . "]", $epon['epon_id'] );
		form_selectable_cell(bdcom_fromat_datetime($epon["epon_lastchange_date"]), $epon["epon_id"] );
		form_selectable_cell(bdcom_fromat_datetime($epon["epon_scan_date"]), $epon["epon_id"] );

		form_checkbox_cell($epon['epon_name'], $epon['epon_id']);	
		form_end_row();

}




 

 function form_actions_epons() {

    global $colors, $config, $epon_actions;

	$str_ids = '';
	
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
		$device_ids=db_fetch_assoc("SELECT device_id FROM plugin_bdcom_epons where epon_id in (" . $str_ids . ") group by device_id;");
		$epons=db_fetch_assoc("SELECT * FROM plugin_bdcom_epons where epon_id in (" . $str_ids . ") ;");
		$epon_devices=bdcom_array_rekey(db_fetch_assoc("SELECT `d`.`description` as dev_name , d.*, dt.* FROM plugin_bdcom_epons p LEFT JOIN plugin_bdcom_devices d on (p.device_id=d.device_id) LEFT JOIN plugin_bdcom_dev_types dt on (d.device_type_id = dt.device_type_id) WHERE `p`.`epon_id` in (" . $str_ids . ") GROUP by p.device_id;"), 'device_id');		

		
		if (get_request_var('drp_action') == "2") { /* Изменить описание порта */
			if (sizeof($epons) > 0) {
				foreach ($epons as $epon) {	
					bdcom_api_change_port_name($epon, $epon_devices[$epon['device_id']], get_request_var('t_' . $epon['epon_id'] . '_epon_name'));
				}
			}	
 
        } elseif (get_request_var('drp_action') == "3") { /* Enable port */
			foreach ($epons as $epon) {		
				bdcom_api_change_port_state($epon, $epon_devices[$epon['device_id']], "1");
			}		
 		} elseif (get_request_var('drp_action') == "4") { /* Disable port */
			foreach ($epons as $epon) {		
				bdcom_api_change_port_state($epon, $epon_devices[$epon['device_id']], "2");
			}	
 		}
		header("Location: bdcom_view_epons.php");
         exit;
     }
 
     /* setup some variables */
     $row_list = ""; $i = 0; $row_ids = ""; $post_if_error = ""; $colspan = 2;

	if (!isset_request_var('post_error')) { /*Если установлено это значение - значит страница перезагружаеться из-за ошибки при вводе, и данные нужно брать не из POST, а из спец. переменной.*/
	 /* loop through each of the ports selected on the previous page and get more info about them для создания первой страницы типа [Вы действительно хотите ....]*/
     foreach ($_POST as $var => $val) {
         if (preg_match("/^chk_([0-9]+)$/", $var, $matches)) {
             /* ================= input validation ================= */
             input_validate_input_number($matches[1]);
             /* ==================================================== */
			$row_array[$i] = $matches[1];
			$row_ids = $row_ids . "'" . $matches[1] . "', ";
			$i++;			
		}
	  }
 
    }else{
 		$row_array=unserialize(stripslashes(get_request_var('post_error')));
 		if (isset($row_array) && is_array($row_array) && (count($row_array) > 0)) {
 			foreach ($row_array as $row_id) {
 				$row_ids = $row_ids . "'" . $matches[1] . "', ";	
 			}
 		}
 	}
 
 
  	$row_ids = substr($row_ids, 0, strlen($row_ids) -2);    
	$epons=db_fetch_assoc("SELECT * FROM plugin_bdcom_epons where epon_id in (" . $row_ids . ") ;");
	$epons_devices=bdcom_array_rekey(db_fetch_assoc("SELECT `d`.`description` as dev_name , d.*, dt.* FROM plugin_bdcom_epons p LEFT JOIN plugin_bdcom_devices d on (p.device_id=d.device_id) LEFT JOIN plugin_bdcom_dev_types dt on (d.device_type_id = dt.device_type_id) WHERE `p`.`epon_id` in (" . $row_ids . ") GROUP by p.device_id;"), 'device_id');

	foreach ($epons as $epon) {
		$row_list .= "<li>" . $epons_devices[$epon['device_id']]["dev_name"] . " port:" . $epon['epon_number'] . " <br>";
	}
 
 	top_header();

	form_start('bdcom_view_epons.php?header=false');

	html_start_box($epon_actions[get_request_var('drp_action')], '60%', '', '3', 'center', '');     
 
 
	if ((!isset($row_array) or (!sizeof($row_array))) && (((isset_request_var('drp_action')) && (get_request_var('drp_action') != "4")) || ((isset_request_var('post_error') && (isset_request_var('drp_action')) && (get_request_var('drp_action') != "4"))))) {
		print "<tr><td class='even'><span class='textError'>" . __('You must select at least one row.') . "</span></td></tr>\n";
		$save_html = "";
	}else{
		
		$save_html = "<input type='submit' value='" . __('Yes') . "' name='save'>";	
		$save_html = "<input type='submit' value='" . __esc('Continue', 'bdcom') . "' name='save'>";
 	}
    if ((get_request_var('drp_action') == "2") ){  /* измениен записей */
			
			$epon_rows=db_fetch_assoc("SELECT plugin_bdcom_epons.*, plugin_bdcom_devices.hostname, plugin_bdcom_devices.description FROM plugin_bdcom_epons left join plugin_bdcom_devices on (plugin_bdcom_epons.device_id = plugin_bdcom_devices.device_id) WHERE plugin_bdcom_epons.epon_id in (" . $row_ids . ");");
		 
			html_start_box(__('Click \'Continue\' to change the following ONUs.', 'bdcom'), '100%', '', '3', 'center', '');   
		 
			html_header(array("","Host<br>Description","Hostname<br>", "№ порта", "Описание порта"));

		 
			 $i = 0;
			 if (sizeof($epon_rows) > 0) {
				 foreach ($epon_rows as $epon_row) {
					$epon_id = $epon_row['epon_id'];
					 //form_alternate_row_color($colors['alternate'],$colors['light'],$i); $i++;
						 ?>
						<td><?php form_hidden_box("t_" . $epon_id . "_epon_id", $epon_id, "form_default_value");?></td>
						<td><?php print $epon_row['description'];?></td>
						<td><?php print $epon_row['hostname'];?></td>
						<td><?php print $epon_row['epon_number'];?></td>
						<td><?php form_text_box("t_" . $epon_id . "_epon_name", $epon_row['epon_descr'], "", 100, 100, "text", 1) ;?></td>
					 </tr>
					 <?php
				 }
			 }
			$colspan = 5;
			//html_end_box(false);			
			
	}if (get_request_var('drp_action') == "3") {  /* Enable port */
			print "<tr>
				<td colspan='2' class='textArea'>
					<p>" . __('Click \'Continue\' to Enable following Epon Ports.', 'bdcom') . "</p>
					<ul>$row_list</ul>
				</td>
			</tr>";          
     } else if (get_request_var('drp_action') == "4") { /*Disable port*/
			print "<tr>
				<td colspan='2' class='textArea'>
					<p>" . __('Click \'Continue\' to Disable following Epon Ports.', 'bdcom') . "</p>
					<ul>$row_list</ul>
				</td>
			</tr>";        
 	}
 
 	
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
 

 

 
function bdcom_epon_filter() {
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
					<td>
						<?php print __('Status', 'bdcom');?>
					</td>
					<td>
						<select id='status' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('status') == '-1') {?> selected<?php }?>><?php print __('Any', 'bdcom');?></option>
							<option value='2'<?php if (get_request_var('status') == '2') {?> selected<?php }?>><?php print __('Up', 'bdcom');?></option>
							<option value='1'<?php if (get_request_var('status') == '1') {?> selected<?php }?>><?php print __('Down', 'bdcom');?></option>
							<option value='3'<?php if (get_request_var('status') == '3') {?> selected<?php }?>><?php print __('Disabled', 'bdcom');?></option>
							<option value='4'<?php if (get_request_var('status') == '4') {?> selected<?php }?>><?php print __('Enable', 'bdcom');?></option>
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
			strURL  = urlPath+'plugins/bdcom/bdcom_view_epons.php?header=false';
			strURL += '&status=' + $('#status').val();
			strURL += '&device_type_id=' + $('#device_type_id').val();
			strURL += '&device_id=' + $('#device_id').val();
			strURL += '&filter=' + $('#filter').val();
			strURL += '&rows=' + $('#rows').val();
			loadPageNoHeader(strURL);
		}

		function clearFilter() {
			strURL  = urlPath+'plugins/bdcom/bdcom_view_epons.php?header=false&clear=true';
			loadPageNoHeader(strURL);
		}

		function exportRows() {
			strURL  = urlPath+'plugins/bdcom/bdcom_view_epons.php?export=true';
			document.location = strURL;
		}

		function importRows() {
			strURL  = urlPath+'plugins/bdcom/bdcom_view_epons.php?import=true';
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
