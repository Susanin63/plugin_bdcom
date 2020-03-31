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

 $netadd_actions = array(
 	2 => __('Change Port Name'),
 	3 => __('Enable Port'),
 	4 => __('Disable Port'),
 	);
 	

$title = __('BDCOM - PORT Report View', 'bdcom');

/* check actions */
switch (get_request_var('action')) {
	case 'actions':
		form_actions_netadds();

		break;
	default:
		bdcom_redirect();
		general_header();
		bdcom_view_netadds();
		bottom_footer();
		break;
}



function bdcom_view_get_netadd_records(&$sql_where, $rows = '30', $apply_limits = TRUE) {

	/* form the 'where' clause for our main sql query */
	if (get_request_var('filter') != '') {
		$sql_where = (strlen($sql_where) ? ' AND ': 'WHERE ') . "(net_change_time like '%" . get_request_var('filter') . "%'
			OR net_description like '%" . get_request_var('filter') . "%'
			OR net_mask like '%" . get_request_var('filter') . "%')";
	}

	
	switch (get_request_var('ip_filter_type_id')) {
		 case "1": // do not filter 
			 break;
		 case "2": // matches 
			  $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " INET_NTOA(n.net_ipaddr) ='" . get_request_var('ip_filter') . "'";
			 break;
		 case "3": // contains 
			  $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " INET_NTOA(n.net_ipaddr) LIKE '%%" . get_request_var('ip_filter') . "%%'";
			 break;
		 case "4": // begins with 
			  $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " INET_NTOA(n.net_ipaddr) LIKE '" . get_request_var('ip_filter') . "%%'";
			 break;
		 case "5": // does not contain 
			  $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " INET_NTOA(n.net_ipaddr) NOT LIKE '" . get_request_var('ip_filter') . "%%'";
			 break;
		 case "6": // does not begin with 
			  $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " INET_NTOA(n.net_ipaddr) NOT LIKE '" . get_request_var('ip_filter') . "%%'";
			 break;
		 case "7": // is null 
			  $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " INET_NTOA(n.net_ipaddr) = ''";
			 break;
		 case "8": // is not null 
			  $sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " INET_NTOA(n.net_ipaddr) != ''";
	}
	
	$sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " n.`net_type`=3 ";
	
 	$sortby = get_request_var('sort_column');
 	if ($sortby=="net_ipaddr") {
 		$sortby = "INET_ATON(net_ipaddr)";
 	}
	
	$sql_order = get_order_string();
	
	if ($apply_limits) {
		$sql_limit = ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ', ' . $rows;
	}else{
		$sql_limit = '';
	}
	
 		$query_string = "SELECT  n.* , INET_NTOA(n.net_ipaddr) as ipa , 
		 IF(`net_ttl`='0', 'Постоянно',DATE_ADD(n.net_change_time, INTERVAL  `net_ttl` HOUR)) as net_ttl_date,user_auth.username as net_change_user_name, '<== Any Device ==>' as description 
		 from imb_auto_updated_nets n
		 left join user_auth on (n.net_change_user=user_auth.id)
        $sql_where
        $sql_order
		$sql_limit";
 

 
     return db_fetch_assoc($query_string);
 }
 
 function bdcom_netadd_request_validation() {
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
			'default' => 'net_id',
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

			
	);

	validate_store_request_vars($filters, 'sess_bdcomv_netadd');
	/* ================= input validation ================= */
	
	
 }

 
 function bdcom_view_netadds() {
    global $title, $report, $colors, $rows_selector, $config, $netadd_actions;
 
	bdcom_netadd_request_validation();

	if (get_request_var('rows') == -1) {
		$rows = read_config_option('num_rows_table');
	} elseif (get_request_var('rows') == -2) {
		$rows = 999999;
	} else {
		$rows = get_request_var('rows');
	}
	
	$webroot = $config['url_path'] . 'plugins/bdcom/';
	bdcom_tabs();

	html_start_box($title, '100%', '', '3', 'center', 'bdcom_view_netadd.php?action=edit');
	bdcom_netadd_filter();
	html_end_box(); 
	//bdcom_group_tabs();


	$sql_where = '';

    $nets = bdcom_view_get_netadd_records($sql_where, $rows);    

    $total_rows = db_fetch_cell("SELECT COUNT(*) FROM imb_auto_updated_nets n 			 
            $sql_where");
 	

  	$display_text = array(
	
 		'ipa' => array(__('IP', 'bdcom'), 'ASC'),
 		'net_ttl_date' => array(__('TTL', 'bdcom'), 'ASC'),
 		'net_description' => array(__('Description', 'bdcom'), 'ASC'),
 		'net_change_user_name' => array(__('Author', 'bdcom'), 'ASC'));		
		
	$columns = sizeof($display_text) +1 ;  
	
	$nav = html_nav_bar('bdcom_view_netadd.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, $columns, __('Nets', 'bdcom'), 'page', 'main');
	
	form_start('bdcom_view_netadd.php', 'chk');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort_checkbox($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);


	if (cacti_sizeof($nets)) {
		foreach ($nets as $net) {
			form_alternate_row('line' . $net['net_id'], true);
			bdcom_format_netadd_row($net);

		}
		
		
	} else {
		print '<tr><td colspan="' . $columns  . '"><em>' . __('No AutoCreate Record', 'bdcom') . '</em></td></tr>';
	}

	html_end_box(false);

	if (cacti_sizeof($nets)) {
		print $nav;
	}

	/* draw the dropdown containing a list of available actions for this form */
	draw_actions_dropdown($netadd_actions);

	form_end();		
	
}
 
 


 function bdcom_format_netadd_row($net, $actions=false) {
	global $config;

	
		//$bgc = db_fetch_cell("SELECT hex FROM colors WHERE id='" . $net['color_row'] . "'");
		form_selectable_cell("<a class='linkEditMain' href='bdcom_view_info.php?report=info&amp;device_id=-1&amp;ip_filter_type_id=2&amp;ip_filter=" . $net['ipa'] . "&amp;mac_filter_type_id=2&amp;mac_filter=&amp;port_filter_type_id=&amp;port_filter=&amp;rows_selector=-1&amp;filter=&amp;page=1&amp;report=info&amp;x=14&amp;y=6'>" . 
			(strlen(get_request_var('ip_filter')) ? strtoupper(preg_replace("/(" . preg_quote(get_request_var('ip_filter')) . ")/i", "<span style='background-color: #F8D93D;'>\\1</span>", $net['ipa'])) : $net['ipa']) . "</font></a>", $net['net_id']);		
		
		form_selectable_cell($net['net_ttl_date'], $net['net_id']);
		form_selectable_cell(filter_value($net['net_description'], get_request_var('filter')), $net['net_id']);
		form_selectable_cell(filter_value($net['net_change_user_name'], get_request_var('filter')), $net['net_id']);
		form_checkbox_cell($net['net_description'], $net['net_id']);	
		form_end_row();

}




 

 function form_actions_netadds() {

    global $colors, $config, $netadd_actions;

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
		$device_ids=db_fetch_assoc("SELECT device_id FROM plugin_bdcom_ports where net_id in (" . $str_ids . ") group by device_id;");
		$nets=db_fetch_assoc("SELECT * FROM plugin_bdcom_ports where net_id in (" . $str_ids . ") ;");
		$net_devices=bdcom_array_rekey(db_fetch_assoc("SELECT `d`.`description` as dev_name , d.*, dt.* FROM plugin_bdcom_ports p LEFT JOIN plugin_bdcom_devices d on (p.device_id=d.device_id) LEFT JOIN plugin_bdcom_dev_types dt on (d.device_type_id = dt.device_type_id) WHERE `p`.`net_id` in (" . $str_ids . ") GROUP by p.device_id;"), 'device_id');		

		
		if (get_request_var('drp_action') == "2") { /* Изменить описание порта */
			if (sizeof($nets) > 0) {
				foreach ($nets as $net) {	
					bdcom_api_change_net_name($port, $port_devices[$port['device_id']], get_request_var('t_' . $net['net_id'] . '_port_name'));
				}
			}	
 
        } elseif (get_request_var('drp_action') == "3") { /* Enable port */
			foreach ($nets as $net) {		
				bdcom_api_change_port_state($port, $port_devices[$port['device_id']], "1");
			}		
 		} elseif (get_request_var('drp_action') == "4") { /* Disable port */
			foreach ($nets as $net) {		
				bdcom_api_change_port_state($port, $port_devices[$port['device_id']], "2");
			}	
 		}
		header("Location: bdcom_view_netadd.php");
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
	$nets=db_fetch_assoc("SELECT * FROM plugin_bdcom_ports where net_id in (" . $row_ids . ") ;");

	foreach ($nets as $net) {
		$row_list .= "<li>" . $ports_devices[$port['device_id']]["dev_name"] . " port:" . $net['port_number'] . " <br>";
	}
 
 	top_header();

	form_start('bdcom_view_netadd.php?header=false');

	html_start_box($netadd_actions[get_request_var('drp_action')], '60%', '', '3', 'center', '');     
 
 
	if ((!isset($row_array) or (!sizeof($row_array))) && (((isset_request_var('drp_action')) && (get_request_var('drp_action') != "4")) || ((isset_request_var('post_error') && (isset_request_var('drp_action')) && (get_request_var('drp_action') != "4"))))) {
		print "<tr><td class='even'><span class='textError'>" . __('You must select at least one row.') . "</span></td></tr>\n";
		$save_html = "";
	}else{
		
		$save_html = "<input type='submit' value='" . __('Yes') . "' name='save'>";	
		$save_html = "<input type='submit' value='" . __esc('Continue', 'bdcom') . "' name='save'>";
 	}
    if ((get_request_var('drp_action') == "2") ){  /* измениен записей */
			
			$net_rows=db_fetch_assoc("SELECT plugin_bdcom_ports.*, plugin_bdcom_devices.hostname, plugin_bdcom_devices.description FROM plugin_bdcom_ports left join plugin_bdcom_devices on (plugin_bdcom_ports.device_id = plugin_bdcom_devices.device_id) WHERE plugin_bdcom_ports.net_id in (" . $row_ids . ");");
		 
			html_start_box(__('Click \'Continue\' to change the following ONUs.', 'bdcom'), '100%', '', '3', 'center', '');   
		 
			html_header(array("","Host<br>Description","Hostname<br>", "№ порта", "Описание порта"));

		 
			 $i = 0;
			 if (sizeof($net_rows) > 0) {
				 foreach ($net_rows as $net_row) {
					$net_id = $net_row['net_id'];
					 //form_alternate_row_color($colors['alternate'],$colors['light'],$i); $i++;
						 ?>
						<td><?php form_hidden_box("t_" . $net_id . "_net_id", $net_id, "form_default_value");?></td>
						<td><?php print $net_row['description'];?></td>
						<td><?php print $net_row['hostname'];?></td>
						<td><?php print $net_row['net_number'];?></td>
						<td><?php form_text_box("t_" . $net_id . "_net_name", $net_row['net_descr'], "", 100, 100, "text", 1) ;?></td>
					 </tr>
					 <?php
				 }
			 }
			$colspan = 5;
			//html_end_box(false);			
			
	}if (get_request_var('drp_action') == "3") {  /* Enable port */
			print "<tr>
				<td colspan='2' class='textArea'>
					<p>" . __('Click \'Continue\' to Enable following Ports.', 'bdcom') . "</p>
					<ul>$row_list</ul>
				</td>
			</tr>";          
     } else if (get_request_var('drp_action') == "4") { /*Disable port*/
			print "<tr>
				<td colspan='2' class='textArea'>
					<p>" . __('Click \'Continue\' to Disable following Ports.', 'bdcom') . "</p>
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
 

 

 
function bdcom_netadd_filter() {
	global $item_rows, $bdcom_search_types;

	?>
	<td width="100%" valign="top"><?php bdcom_display_output_messages();?>
	<tr class='even'>
		<td>
		<form id='bdcom'>
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
			strURL  = urlPath+'plugins/bdcom/bdcom_view_netadd.php?header=false';
			strURL += '&ip_filter_type_id=' + $('#ip_filter_type_id').val();
			strURL += '&ip_filter=' + $('#ip_filter').val();
			strURL += '&filter=' + $('#filter').val();
			strURL += '&rows=' + $('#rows').val();
			loadPageNoHeader(strURL);
		}

		function clearFilter() {
			strURL  = urlPath+'plugins/bdcom/bdcom_view_netadd.php?header=false&clear=true';
			loadPageNoHeader(strURL);
		}

		function exportRows() {
			strURL  = urlPath+'plugins/bdcom/bdcom_view_netadd.php?export=true';
			document.location = strURL;
		}

		function importRows() {
			strURL  = urlPath+'plugins/bdcom/bdcom_view_netadd.php?import=true';
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
