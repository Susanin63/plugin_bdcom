 function show_ping_w(device_id) {
	//$('#element_to_pop_up').bPopup({appending : false, modalColor: 'greenYellow',position: [550, 400]});
	var post_data = {type:"start_html_ping"};
	
	$('#element_to_pop_ping').bPopup({
		//position: [‘auto’,’auto’],
        content: 'ajax',
		scrollBar  : true,
        contentContainer: '#element_to_pop_ping',
        loadData: post_data,
        loadUrl: 'bdcom_ajax.php'
		//loadCallback: function(){ update(device_id); }
    });
	setTimeout(get_info, 50, device_id);

}


function ping_host(device_id){
    var post_data = {type:"iping",ip:device_id};
	$.ajax({
        url: "bdcom_ajax.php",
		type: 'post',
		data: post_data,
        success: 
          function(result){
			rez  = "";
			var div = document.getElementById('i_ping');
			if ('null' != div && div !== null){
				if (div.childNodes.length > 13){
					for (var i = 2; i < 14; i++) {
					  rez = rez + div.childNodes[i].outerHTML; // Text, DIV, Text, UL, ..., SCRIPT
					}					
					div.innerHTML = rez + result;
					
					
				}else{
					div.innerHTML = div.innerHTML + result;
				}
				setTimeout(function(){
					ping_host(device_id); //this will send request again and again;
				}, 1000);				
			}

        }});
}

function get_info(device_id){
    var post_data = {type:"get_info",ip:device_id};
	$.ajax({
        url: "bdcom_ajax.php",
		type: 'post',
		data: post_data,
        success: 
          function(result){
			rez  = "";
			var div = document.getElementById('a_info');
			if ('null' != div && div !== null){
				div.innerHTML = div.innerHTML + result;
				//start ping
				setTimeout(ping_host, 500, device_id);
			}

        }});
}

var url

function scan_onu(onu_id) {
	url=urlPath+'plugins/bdcom/bdcom_view_onus.php?action=onu_query_ajax&onu_id='+onu_id
	$('#r_'+onu_id).attr('src', 'images/view_busy.gif');
    $.get(url, function(data){
        var json = jQuery.parseJSON(data);
        $(function () {
            var content = '';
            //content += '<tbody>'; -- **superfluous**
            for (var i = 0; i < json.length; i++) {
            content += '<tr id="' + json[i].ID + '">';
            content += '<td><input id="check_' + json[i].ID + '" name="check_' + json[i].ID + '" type="checkbox" value="' + json[i].ID + '" autocomplete=OFF /></td>';
            content += '<td>' + json[i].ID + '</td>';
            content += '<td>' + json[i].Name + '</td>';
            content += '<td>' + json[i].CountryCode + '</td>';
            content += '<td>' + json[i].District + '</td>';
            content += '<td>' + json[i].Population + '</td>';
            content += '<td><a href="#" class="edit">Edit</a> <a href="#" class="delete">Delete</a></td>';
            content += '</tr>';
            }
           // content += '</tbody>';-- **superfluous**
            //$('table tbody').replaceWith(content);  **incorrect..**

			//$('#r77').innerHTML=json['rx']; 
			$('#pw'+onu_id).html(json['pow'])
			$('#r_'+onu_id).attr('src', 'images/reload_icon_small.gif'); 
       });  
    });	
	
	
}

