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
 chdir('../../');
 /* include cacti base functions */
 include("./include/auth.php");
 include_once("./lib/snmp.php");
 
 /* include base and vendor functions to obtain a list of registered scanning functions */
 include_once($config['base_path'] . "/plugins/bdcom/lib/bdcom_functions.php");
 include_once($config['base_path'] . "/plugins/bdcom/lib/bdcom_vendors.php");
 
 /* store the list of registered bdcom scanning functions */
 foreach($bdcom_scanning_functions as $scanning_function) {
 	db_execute("REPLACE INTO plugin_bdcom_scan_functions (scanning_function) VALUES ('" . $scanning_function . "')");
 }
 
  
 $device_types_actions = array(
	1 => __('Delete'),
	2 => __('Duplicate')
 	);
 
 set_default_action();
 
 
 
 switch (get_request_var('action')) {
	case 'save':
		form_bdcomt_save();

		break;
	case 'actions':
		form_devicet_actions();

		break;
	case 'edit':
		top_header();
		bdcom_devicet_type_edit();
		bottom_footer();

		break;
	case 'import':
		top_header();
		bdcom_devicet_import();
		bottom_footer();

		break;
	default:
		if (isset_request_var('import')) {
			header('Location: bdcom_devices_types.php?action=import');
		}elseif (isset_request_var('export')) {
			bdcom_devicet_export();
		}else{
			top_header();
			bdcom_device_type();
			bottom_footer();
		}

		break;
}
 
 

 
 /* --------------------------
     The Save Function
    -------------------------- */
 
 function form_bdcomt_save() {
 	if ((isset_request_var('save_component_device_type')) && (empty(get_nfilter_request_var('add_dq_y')))) {
 		$device_type_id = api_bdcom_device_type_save(get_nfilter_request_var('device_type_id'), get_nfilter_request_var('description'), get_nfilter_request_var('scanning_function'));
 
 		if ((is_error_message()) || (get_nfilter_request_var('device_type_id') != get_nfilter_request_var('_device_type_id'))) {
 			header("Location: bdcom_device_types.php?action=edit&device_type_id=" . (empty($device_type_id) ? get_nfilter_request_var('device_type_id') : $device_type_id));
 		}else{
 			header("Location: bdcom_device_types.php");
 		}
 	}
 
 	if (isset_request_var('save_component_import')) {
 		if (($_FILES['import_file']["tmp_name"] != "none") && ($_FILES['import_file']["tmp_name"] != "")) {
 			/* file upload */
 			$csv_data = file($_FILES['import_file']["tmp_name"]);
 
 			/* obtain debug information if it's set */
 			$debug_data = bdcom_device_type_import_processor($csv_data);
 			if(sizeof($debug_data) > 0) {
 				$_SESSION['import_debug_info'] = $debug_data;
 			}
 		}else{
 			header("Location: bdcom_device_types.php?action=import"); exit;
 		}
 
 		header("Location: bdcom_device_types.php?action=import");
 	}
 }
 
 
 function api_bdcom_device_type_remove($device_type_id){
 	db_execute("DELETE FROM plugin_bdcom_dev_types WHERE device_type_id='" . $device_type_id . "'");
 }
 
  
 function api_bdcom_device_type_save($device_type_id, $description, $scanning_function) {
 
 	$save['device_type_id'] = $device_type_id;
 	$save['description'] = form_input_validate($description, 'description', '', false, 3);
 	$save['scanning_function'] = form_input_validate($scanning_function, 'scanning_function', '', true, 3);
 	
 	$device_type_id = 0;
 	if (!is_error_message()) {
 		$device_type_id = sql_save($save, "plugin_bdcom_dev_types", "device_type_id");
 
 		if ($device_type_id) {
 			raise_message(1);
 		}else{
 			raise_message(2);
 		}
 	}
 
 	return $device_type_id;
 }
 
 /* ------------------------
     The "actions" function
    ------------------------ */
 
 function form_devicet_actions() {
 	global $colors, $config, $device_types_actions, $fields_bdcom_device_types_edit;
 
 	/* if we are to save this form, instead of display it */
 	if (isset_request_var('selected_items')) {
 		$selected_items = unserialize(stripslashes(get_nfilter_request_var('selected_items')));
 
 		if (get_filter_request_var('drp_action') == "1") { /* delete */
 			for ($i=0; $i<count($selected_items); $i++) {
 				/* ================= input validation ================= */
 				input_validate_input_number($selected_items[$i]);
 				/* ==================================================== */
 
 				api_bdcom_device_type_remove($selected_items[$i]);
 			}
 		}elseif (get_nfilter_request_var('drp_action') == "2") { /* duplicate */
 			for ($i=0;($i<count($selected_items));$i++) {
 				/* ================= input validation ================= */
 				input_validate_input_number($selected_items[$i]);
 				/* ==================================================== */
 
 				duplicate_device_type($selected_items[$i], get_nfilter_request_var('title_format'));
 			}
 		}
 
 		header("Location: bdcom_device_types.php");
 		exit;
 	}
 
 	/* setup some variables */
 	$device_types_list = ""; $i = 0;
 
 	/* loop through each of the device types selected on the previous page and get more info about them */
 	foreach ($_POST as $var => $val) {
 		if (preg_match("/^chk_([0-9]+)$/", $var, $matches)) {
 			/* ================= input validation ================= */
 			input_validate_input_number($matches[1]);
 			/* ==================================================== */
 
 			$device_types_info = db_fetch_row("SELECT description FROM plugin_bdcom_dev_types WHERE device_type_id=" . $matches[1]);
 			$device_types_list .= "<li>" . $device_types_info['description'] . "<br>";
 			$device_types_array[$i] = $matches[1];
			$i++;
 		}
 
 	}
 

	top_header();

	form_start('bdcom_device_types.php');
	
	html_start_box($device_actions[get_request_var('drp_action')], '60%', '', '3', 'center', '');


	if (!isset($device_types_array)) {
		print "<tr><td class='even'><span class='textError'>" . __('You must select at least one device type.', 'bdcom') . "</span></td></tr>\n";
		$save_html = '';
	} else {
		$save_html = "<input type='submit' value='" . __esc('Continue', 'bdcom') . "' name='save'>";

		if (get_request_var('drp_action') == '1') { /* delete */
			print "<tr>
				<td class='textArea'>
					<p>" . __('Click \'Continue\' to delete the following Device Type(s).', 'bdcom') . "</p>
					<ul>$device_types_list</ul>
				</td>
			</tr>";
		} elseif (get_request_var('drp_action') == '2') { /* duplicate */
			print "<tr>
				<td class='textArea'>
					<p>" . __('Click \'Continue\' to duplciate the following Device Type(s). You may optionally change the description for the new device types.  Otherwise, do not change value below and the original name will be replicated with a new suffix.', 'bdcom') . "</p>
					<ul>$device_types_list</ul>
					<p>" . __('Device Type Prefix:', 'bdcom') . '<br>'; form_text_box('title_format', __('<description> (1)', 'bdcom'), '', '255', '30', 'text'); print "</p>
				</td>
			</tr>";
		}
	}


	print "<tr>
		<td colspan='2' align='right' class='saveRow'>
			<input type='hidden' name='action' value='actions'>
			<input type='hidden' name='selected_items' value='" . (isset($device_types_array) ? serialize($device_types_array) : '') . "'>
			<input type='hidden' name='drp_action' value='" . get_nfilter_request_var('drp_action') . "'>" . (strlen($save_html) ? "
			<input type='button' onClick='cactiReturnTo()' name='cancel' value='" . __esc('Cancel', 'bdcom') . "'>
			$save_html" : "<input type='submit' onClick='cactiReturnTo()' name='cancel' value='" . __esc('Return') . "'>") . "
		</td>
	</tr>";

	html_end_box();

	form_end();


 }
 
 /* ---------------------
     bdcom Device Type Functions
    --------------------- */
 
 
 function bdcom_devicet_type_remove() {
 	global $config;
 
 	/* ================= input validation ================= */
 	input_validate_input_number(get_request_var("device_type_id"));
 	/* ==================================================== */
 
 	if ((read_config_option("remove_verification") == "on") && (!isset($_GET['confirm']))) {
 		include("./include/top_header.php");
 		form_confirm("Are You Sure?", "Are you sure you want to delete the device type<strong>'" . db_fetch_cell("select description from host where id=" . $_GET['device_id']) . "'</strong>?", "bdcom_device_types.php", "bdcom_device_types.php?action=remove&id=" . $_GET['device_type_id']);
 		include("./include/bottom_footer.php");
 		exit;
 	}
 
 	if ((read_config_option("remove_verification") == "") || (isset($_GET['confirm']))) {
 		api_bdcom_device_type_remove($_GET['device_type_id']);
 	}
 }
 
 function bdcom_devicet_type_edit() {

	global $config, $fields_bdcom_device_type_edit;

	/* ================= input validation ================= */
	get_filter_request_var('device_type_id');
	/* ==================================================== */

	if (!isempty_request_var('device_type_id')) {
		$device_type = db_fetch_row_prepared('SELECT *
			FROM plugin_bdcom_dev_types
			WHERE device_type_id = ?',
			array(get_request_var('device_type_id')));

		$header_label = __('BDCOM Device Types [edit: %s]', $device_type['description'], 'bdcom');
	} else {
		$header_label = __('DBCOM Device Types [new]', 'bdcom');
	}

	form_start('bdcom_device_types.php', 'chk');

	html_start_box($header_label, '100%', '', '3', 'center', '');

	draw_edit_form(
		array(
			'config' => array('no_form_tag' => 'true'),
			'fields' => inject_form_variables($fields_bdcom_device_type_edit, (isset($device_type) ? $device_type : array()))
		)
	);

	html_end_box();

	form_save_button('bdcom_device_types.php', 'return', 'device_type_id');

 }
 
 function bdcom_get_device_types(&$sql_where) {
 	return db_fetch_assoc("SELECT plugin_bdcom_dev_types.*, count(plugin_bdcom_devices.device_id) as count_devices  from plugin_bdcom_dev_types  " .
 		" left join plugin_bdcom_devices " .
 		" on (plugin_bdcom_devices.device_type_id=plugin_bdcom_dev_types.device_type_id) group by device_type_id " .
 		" ORDER BY " . get_request_var('sort_column') . " " . get_request_var('sort_direction') . ";");
 
 }
 
function bdcom_device_type_request_validation() {
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
			'default' => 'description',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'ASC',
			'options' => array('options' => 'sanitize_search_string')
			)
	);

	validate_store_request_vars($filters, 'sess_mt_devicet');
} 
 
 function bdcom_device_type() {

	global $device_types_actions, $bdcom_device_types, $config, $item_rows, $bdcom_imb_yes_no;;
 
	bdcom_device_type_request_validation();
	
	if (get_request_var('rows') == -1) {
		$rows = read_config_option('num_rows_table');
	} elseif (get_request_var('rows') == -2) {
		$rows = 999999;
	} else {
		$rows = get_request_var('rows');
	}

	html_start_box(__('Bdcom Device Type Filters', 'bdcom'), '100%', '', '3', 'center', 'bdcom_device_types.php?action=edit');
	bdcom_device_type_filter();
	html_end_box();

	$sql_where = '';

	$device_types = bdcom_get_device_types($sql_where, $rows);

	$total_rows = db_fetch_cell("SELECT
		COUNT(plugin_bdcom_dev_types.device_type_id)
		FROM plugin_bdcom_dev_types" . $sql_where);

	form_start('bdcom_device_types.php', 'chk');

	$display_text = array(
		'description'             => array(__('Device Type Description', 'bdcom'), 'ASC'),
		'scanning_function'       => array(__('Port Scanner', 'bdcom'), 'ASC'),
		'count_devices'			  => array(__('Count Devices', 'bdcom'), 'ASC')
	);

	$columns = sizeof($display_text) + 1;

	$nav = html_nav_bar('bdcom_device_types.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, $columns, __('Device Types', 'bdcom'), 'page', 'main');

	print $nav;	
	
	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort_checkbox($display_text, get_request_var('sort_column'), get_request_var('sort_direction'));

	if (cacti_sizeof($device_types)) {
		foreach ($device_types as $device_type) {
			form_alternate_row('line' . $device_type['device_type_id'], true);
		
			form_selectable_cell('<a class="linkEditMain" href="bdcom_device_types.php?action=edit&device_type_id=' . $device_type['device_type_id'] . '">' . $device_type['description'] . '</a>', $device_type['device_type_id']);
 			form_selectable_cell($device_type['scanning_function'], $device_type['device_type_id'] );				
 			form_selectable_cell($device_type['count_devices'], $device_type['device_type_id'] );	
 			form_checkbox_cell($device_type['description'], $device_type['device_type_id']);
			form_end_row();
		}		
	} else {
		print '<tr><td colspan="' . $columns . '"><em>' . __('No DBCOM Device Types Found', 'bdcom') . '</em></td></tr>';
	}

	html_end_box(false);

	if (cacti_sizeof($device_types)) {
		print $nav;
	}

	draw_actions_dropdown($device_types_actions);

	form_end();	
	

 }
 
 
function bdcom_device_type_filter() {
	global $item_rows;

	?>
	<tr class='even'>
		<td>
		<form id='bdcom'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Search', 'bdcom');?>
					</td>
					<td>
						<input type='text' id='filter' size='25' value='<?php print get_request_var('filter');?>'>
					</td>
					<td>
						<?php print __('Device Types', 'bdcom');?>
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
					<td>
						<span class='nowrap'>
							<input type='submit' id='go' title='<?php print __esc('Submit Query');?>' value='<?php print __esc('Go');?>'>
							<input type='button' id='clear' title='<?php print __esc('Clear Filtered Results');?>' value='<?php print __esc('Clear');?>'>
							<input type='button' id='scan' title='<?php print __esc('Scan Active Devices for Unknown Device Types');?>' value='<?php print __esc('Rescan');?>'>
							<input type='button' id='import' title='<?php print __esc('Import Device Types from a CSV File');?>' value='<?php print __esc('Import');?>'>
							<input type='button' id='export' title='<?php print __esc('Export Device Types to Share with Others');?>' value='<?php print __esc('Export');?>'>
						</span>
					</td>
					<td>
						<span id="text" style="display:none;"></span>
					</td>
				</tr>
			</table>

			<script type='text/javascript'>
			function applyFilter(myFunc) {
				strURL  = urlPath+'plugins/bdcom/bdcom_device_types.php?header=false';
				strURL += '&filter=' + $('#filter').val();
				strURL += '&rows=' + $('#rows').val();
				loadPageNoHeader(strURL);
			}

			function clearFilter() {
				strURL  = urlPath+'plugins/bdcom/bdcom_device_types.php?header=false&clear=true';
				loadPageNoHeader(strURL);
			}

			function exportRows() {
				strURL  = urlPath+'plugins/bdcom/bdcom_device_types.php?export=true';
				document.location = strURL;
			}

			function importRows() {
				strURL  = urlPath+'plugins/bdcom/bdcom_device_types.php?import=true';
				loadPageNoHeader(strURL);
			}

			function scanDeviceType() {
				strURL  = urlPath+'plugins/bdcom/bdcom_device_types.php?scan=true';
				$.get(strURL, function(data) {
					var message = data;
					applyFilter();
				});
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

				$('#scan').click(function() {
					scanDeviceType();
				});
			});
			</script>
		</form>
		</td>
	</tr>
	<?php
}
