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
 
 define("SNMP_METHOD_PHP_SET", 1);
 define("SNMP_METHOD_BINARY_SET", 2);
 
 include_once($config["base_path"] . "/lib/poller.php");
 include_once($config["base_path"] . "/lib/snmp.php");
 include_once($config["base_path"] . "/plugins/bdcom/lib/bdcom_functions_ext.php");
 

 if ($config["cacti_server_os"] == "unix") {
 	define("SNMP_SET_ESCAPE_CHARACTER", "'");
 }else{
 	define("SNMP_SET_ESCAPE_CHARACTER", "\"");
 }
 
 if (phpversion () < "5"){ // define PHP5 functions if server uses PHP4
 
 function str_split($text, $split = 1) {
 if (!is_string($text)) return false;
 if (!is_numeric($split) && $split < 1) return false;
 $len = strlen($text);
 $array = array();
 $s = 0;
 $e=$split;
 while ($s <$len)
     {
         $e=($e <$len)?$e:$len;
         $array[] = substr($text, $s,$e);
         $s = $s+$e;
     }
 return $array;
 }
 }
 if (! function_exists("array_fill_keys")) {
 	function array_fill_keys($array, $values) {
 	    $arraydisplay = array();
		if(is_array($array)) {
 	        foreach($array as $key => $value) {
 	            $arraydisplay[$array[$key]] = $values;
 	        }
			return $arraydisplay;
 	    }
 	} 
 }
 /* register this functions scanning functions */
 function bdcom_debug($message, $is_log = false) {
 	global $bdcom_debug;
 
 	if ($bdcom_debug) {
 		print("bdcom_DEBUG (" . date("H:i:s") . "): [" . $message . "]\n");
 	}
 
 	if ((substr_count($message, "ERROR:")) or ($bdcom_debug) or ($is_log)) {
 		cacti_log($message, false, "bdcom");
 	}
 }
 
 function bdcom_check_changed($request, $session) {
     if ((isset($_REQUEST[$request])) && (isset($_SESSION[$session]))) {
         if ($_REQUEST[$request] != $_SESSION[$session]) {
             return 1;
         }
     }
 }

  /*	bdcom_valid_snmp_device - This function validates that the device is reachable via snmp.
   It first attempts	to utilize the default snmp readstring.  If it's not valid, it
   attempts to find the correct read string and then updates several system
   information variable. it returns the status	of the host (up=true, down=false)
 */
 /* we must use an apostrophe to escape community names under Unix in case the user uses
 characters that the shell might interpret. the ucd-snmp binaries on Windows flip out when
 you do this, but are perfectly happy with a quotation mark. */
 
 function bdcom_valid_snmp_device(&$device) {
     /* initialize variable */
     $host_up = FALSE;
     $device["snmp_status"] = HOST_DOWN;
 
	/* force php to return numeric oid's */
	cacti_oid_numeric_format();
 
 
 	$session = cacti_snmp_session($device['hostname'], $device['snmp_get_community'], $device['snmp_get_version'],
		$device['snmp_get_username'], $device['snmp_get_password'], $device['snmp_get_auth_protocol'], $device['snmp_get_priv_passphrase'],
		$device['snmp_get_priv_protocol'], $device['snmp_get_context'], '', $device['snmp_port'],
		$device['snmp_timeout']);

		if ($session !== false) {
			/* Community string is not used for v3 */
			$snmp_sysObjectID = cacti_snmp_session_get($session, '.1.3.6.1.2.1.1.2.0');

			if ($snmp_sysObjectID != 'U') {
				$snmp_sysObjectID = str_replace('enterprises', '.1.3.6.1.4.1', $snmp_sysObjectID);
				$snmp_sysObjectID = str_replace('OID: ', '', $snmp_sysObjectID);
				$snmp_sysObjectID = str_replace('.iso', '.1', $snmp_sysObjectID);

				if ((strlen($snmp_sysObjectID)) &&
					(!substr_count($snmp_sysObjectID, 'No Such Object')) &&
					(!substr_count($snmp_sysObjectID, 'Error In'))) {
					$snmp_sysObjectID = trim(str_replace('"', '', $snmp_sysObjectID));
					$device['snmp_status'] = HOST_UP;
					$host_up = true;
				}
			}
		}
 

 
     if ($host_up) {
         $device["snmp_sysObjectID"] = $snmp_sysObjectID;
 
         /* get system name */
 		$snmp_sysName = @bdcom_snmp_get($device["hostname"], $device["snmp_get_community"], ".1.3.6.1.2.1.1.5.0", $device["snmp_get_version"], $device["snmp_get_username"], $device["snmp_get_password"], $device["snmp_get_auth_protocol"], $device["snmp_get_priv_passphrase"], $device["snmp_get_priv_protocol"],  $device["snmp_get_context"],$device["snmp_port"], $device["snmp_timeout"], $device["snmp_retries"], SNMP_WEBUI);		
 
         if (strlen($snmp_sysName) > 0) {
             $snmp_sysName = trim(strtr($snmp_sysName,"\""," "));
             $device["snmp_sysName"] = $snmp_sysName;
         }
 
         /* get system location */
 		$snmp_sysLocation = @bdcom_snmp_get($device["hostname"], $device["snmp_get_community"], ".1.3.6.1.2.1.1.6.0", $device["snmp_get_version"], $device["snmp_get_username"], $device["snmp_get_password"], $device["snmp_get_auth_protocol"], $device["snmp_get_priv_passphrase"], $device["snmp_get_priv_protocol"],  $device["snmp_get_context"],$device["snmp_port"], $device["snmp_timeout"], $device["snmp_retries"], SNMP_WEBUI);				
 
         if (strlen($snmp_sysLocation) > 0) {
             $snmp_sysLocation = trim(strtr($snmp_sysLocation,"\""," "));
             $device["snmp_sysLocation"] = $snmp_sysLocation;
         }
 
         /* get system contact */
 		$snmp_sysContact = @bdcom_snmp_get($device["hostname"], $device["snmp_get_community"], ".1.3.6.1.2.1.1.4.0", $device["snmp_get_version"], $device["snmp_get_username"], $device["snmp_get_password"], $device["snmp_get_auth_protocol"], $device["snmp_get_priv_passphrase"], $device["snmp_get_priv_protocol"],  $device["snmp_get_context"],$device["snmp_port"], $device["snmp_timeout"], $device["snmp_retries"], SNMP_WEBUI);				
 
         if (strlen($snmp_sysContact) > 0) {
             $snmp_sysContact = trim(strtr($snmp_sysContact,"\""," "));
             $device["snmp_sysContact"] = $snmp_sysContact;
         }
 
         /* get system description */
 		$snmp_sysDescr = @bdcom_snmp_get($device["hostname"], $device["snmp_get_community"], ".1.3.6.1.2.1.1.1.0", $device["snmp_get_version"], $device["snmp_get_username"], $device["snmp_get_password"], $device["snmp_get_auth_protocol"], $device["snmp_get_priv_passphrase"], $device["snmp_get_priv_protocol"],  $device["snmp_get_context"],$device["snmp_port"], $device["snmp_timeout"], $device["snmp_retries"], SNMP_WEBUI);				
 
         if (strlen($snmp_sysDescr) > 0) {
             $snmp_sysDescr = trim(strtr($snmp_sysDescr,"\""," "));
             $device["snmp_sysDescr"] = $snmp_sysDescr;
         }
 
         /* get system uptime */
 		$snmp_sysUptime = @bdcom_snmp_get($device["hostname"], $device["snmp_get_community"], ".1.3.6.1.2.1.1.3.0", $device["snmp_get_version"], $device["snmp_get_username"], $device["snmp_get_password"], $device["snmp_get_auth_protocol"], $device["snmp_get_priv_passphrase"], $device["snmp_get_priv_protocol"],  $device["snmp_get_context"],$device["snmp_port"], $device["snmp_timeout"], $device["snmp_retries"], SNMP_WEBUI);				
 
         if (strlen($snmp_sysUptime) > 0) {
             $snmp_sysUptime = trim(strtr($snmp_sysUptime,"\""," "));
             $device["snmp_sysUptime"] = $snmp_sysUptime;
         }
     }
 
     return $host_up;
 }
 
 function bdcom_find_scanning_function(&$device, &$device_types) {
     $sysDescr_match = FALSE;
     $sysObjectID_match = FALSE;
 
     /* scan all device_types to determine the function to call */
     foreach($device_types as $device_type) {
         /* search for a matching snmp_sysDescr */
 
         if ($device["device_type_id"] == $device_type["device_type_id"])  {
             $device["device_type_id"] = $device_type["device_type_id"];
             return $device_type;
         }
     }
 
     return array();
 }
function bdcom_api_snmp_get($oid, $dev) {

	$ret = bdcom_snmp_walk_usdbin($dev["hostname"], $dev["snmp_get_community"],$oid, $dev["snmp_get_version"],$dev["snmp_get_username"],$dev["snmp_get_password"],$dev["snmp_get_auth_protocol"], $dev["snmp_get_priv_passphrase"], $dev["snmp_get_priv_protocol"],  $dev["snmp_get_context"],$dev["snmp_port"], $dev["snmp_timeout"], $dev["snmp_retries"], SNMP_WEBUI);
	if (is_array($ret) and isset($ret[0]) and count($ret)==1 and isset($ret[0]["value"])){
		$ret = $ret[0]["value"];
	}

	return $ret;
}

function bdcom_api_snmp_walk($oid, $dev) {

return bdcom_snmp_walk_usdbin($dev["hostname"], $dev["snmp_get_community"],$oid, $dev["snmp_get_version"],$dev["snmp_get_username"],$dev["snmp_get_password"],$dev["snmp_get_auth_protocol"], $dev["snmp_get_priv_passphrase"], $dev["snmp_get_priv_protocol"],  $dev["snmp_get_context"],$dev["snmp_port"], $dev["snmp_timeout"], $dev["snmp_retries"], SNMP_WEBUI);
	
}
 
function bdcom_snmp_get($hostname, $community, $oid, $version, $username, $password, $auth_proto, $priv_pass, $priv_proto, $context, $port = 161, $timeout = 500, $retries = 0, $environ = SNMP_POLLER) {
	global $config;

	/* determine default retries */
	if (($retries == 0) || (!is_numeric($retries))) {
		$retries = read_config_option("snmp_retries");
		if ($retries == "") $retries = 3;
	}

	/* do not attempt to poll invalid combinations */
	if (($version == 0) || (!is_numeric($version)) ||
		(!is_numeric($port)) ||
		(!is_numeric($retries)) ||
		(!is_numeric($timeout)) ||
		(($community == "") && ($version != 3))
		) {
		return "U";
	}

	if ((snmp_get_method($version) == SNMP_METHOD_PHP) &&
		(!strlen($context) || ($version != 3))) {
		/* make sure snmp* is verbose so we can see what types of data
		we are getting back */
		snmp_set_quick_print(0);

		if ($version == "1") {
			$snmp_value = @snmpget("$hostname:$port", "$community", "$oid", ($timeout * 1000), $retries);
		}elseif ($version == "2") {
			$snmp_value = @snmp2_get("$hostname:$port", "$community", "$oid", ($timeout * 1000), $retries);
		}else{
			if ($priv_proto == "[None]" || $priv_pass == '') {
				$proto = "authNoPriv";
				$priv_proto = "";
			}else{
				$proto = "authPriv";
			}

			$snmp_value = @snmp3_get("$hostname:$port", "$username", $proto, $auth_proto, "$password", $priv_proto, "$priv_pass", "$oid", ($timeout * 1000), $retries);
		}

		if ($snmp_value === false) {
			cacti_log("WARNING: bdcom SNMP Get Timeout for Device[$hostname], and OID:'$oid'", false);
		}
	}else {
		$snmp_value = '';
		/* ucd/net snmp want the timeout in seconds */
		$timeout = ceil($timeout / 1000);

		if ($version == "1") {
			$snmp_auth = (read_config_option("snmp_version") == "ucd-snmp") ? cacti_escapeshellarg($community): "-c " . cacti_escapeshellarg($community); /* v1/v2 - community string */
		}elseif ($version == "2") {
			$snmp_auth = (read_config_option("snmp_version") == "ucd-snmp") ? cacti_escapeshellarg($community) : "-c " . cacti_escapeshellarg($community); /* v1/v2 - community string */
			$version = "2c"; /* ucd/net snmp prefers this over '2' */
		}elseif ($version == "3") {
			if ($priv_proto == "[None]" || $priv_pass == '') {
				$proto = "authNoPriv";
				$priv_proto = "";
			}else{
				$proto = "authPriv";
			}

			if (strlen($priv_pass)) {
				$priv_pass = "-X " . cacti_escapeshellarg($priv_pass) . " -x " . cacti_escapeshellarg($priv_proto);
			}else{
				$priv_pass = "";
			}

			if (strlen($context)) {
				$context = "-n " . cacti_escapeshellarg($context);
			}else{
				$context = "";
			}

			$snmp_auth = trim("-u " . cacti_escapeshellarg($username) .
				" -l " . cacti_escapeshellarg($proto) .
				" -a " . cacti_escapeshellarg($auth_proto) .
				" -A " . cacti_escapeshellarg($password) .
				" "    . $priv_pass .
				" "    . $context); /* v3 - username/password */
		}

		/* no valid snmp version has been set, get out */
		if (empty($snmp_auth)) { return; }

		if (read_config_option("snmp_version") == "ucd-snmp") {
			/* escape the command to be executed and vulnerable parameters
			 * numeric parameters are not subject to command injection
			 * snmp_auth is treated seperately, see above */
			exec(cacti_escapeshellcmd(read_config_option("path_snmpget")) . " -O vt -v$version -t $timeout -r $retries " . cacti_escapeshellarg($hostname) . ":$port $snmp_auth " . cacti_escapeshellarg($oid), $snmp_value);
		}else {
			exec(cacti_escapeshellcmd(read_config_option("path_snmpget")) . " -O fntevU " . $snmp_auth . " -v $version -t $timeout -r $retries " . cacti_escapeshellarg($hostname) . ":$port " . cacti_escapeshellarg($oid), $snmp_value);
		}

		/* fix for multi-line snmp output */
		if (is_array($snmp_value)) {
			$snmp_value = implode(" ", $snmp_value);
		}
	}

	/* fix for multi-line snmp output */
	if (isset($snmp_value)) {
		if (is_array($snmp_value)) {
			$snmp_value = implode(" ", $snmp_value);
		}
	}

	if (substr_count($snmp_value, "Timeout:")) {
		cacti_log("WARNING: bdcom SNMP Get Timeout for Device[$hostname], and OID:'$oid'", false);
	}

	/* strip out non-snmp data */
	$snmp_value = format_snmp_string($snmp_value, false);

	return $snmp_value;
}

function bdcom_snmp_get_hex($hostname, $community, $oid, $version, $username, $password, $auth_proto, $priv_pass, $priv_proto, $context, $port = 161, $timeout = 500, $retries = 0, $environ = SNMP_POLLER) {
	global $config;

	/* determine default retries */
	if (($retries == 0) || (!is_numeric($retries))) {
		$retries = read_config_option("snmp_retries");
		if ($retries == "") $retries = 3;
	}

	/* do not attempt to poll invalid combinations */
	if (($version == 0) || (!is_numeric($version)) ||
		(!is_numeric($port)) ||
		(!is_numeric($retries)) ||
		(!is_numeric($timeout)) ||
		(($community == "") && ($version != 3))
		) {
		return "U";
	}

	if ((snmp_get_method($version) == SNMP_METHOD_PHP) &&
		(!strlen($context) || ($version != 3))) {
		/* make sure snmp* is verbose so we can see what types of data
		we are getting back */
		snmp_set_quick_print(0);

		if ($version == "1") {
			$snmp_value = @snmpget("$hostname:$port", "$community", "$oid", ($timeout * 1000), $retries);
		}elseif ($version == "2") {
			$snmp_value = @snmp2_get("$hostname:$port", "$community", "$oid", ($timeout * 1000), $retries);
		}else{
			if ($priv_proto == "[None]" || $priv_pass == '') {
				$proto = "authNoPriv";
				$priv_proto = "";
			}else{
				$proto = "authPriv";
			}

			$snmp_value = @snmp3_get("$hostname:$port", "$username", $proto, $auth_proto, "$password", $priv_proto, "$priv_pass", "$oid", ($timeout * 1000), $retries);
		}

		if ($snmp_value === false) {
			cacti_log("WARNING: bdcom SNMP Get Timeout for Device[$hostname], and OID:'$oid'", false);
		}
	}else {
		$snmp_value = '';
		/* ucd/net snmp want the timeout in seconds */
		$timeout = ceil($timeout / 1000);

		if ($version == "1") {
			$snmp_auth = (read_config_option("snmp_version") == "ucd-snmp") ? cacti_escapeshellarg($community): "-c " . cacti_escapeshellarg($community); /* v1/v2 - community string */
		}elseif ($version == "2") {
			$snmp_auth = (read_config_option("snmp_version") == "ucd-snmp") ? cacti_escapeshellarg($community) : "-c " . cacti_escapeshellarg($community); /* v1/v2 - community string */
			$version = "2c"; /* ucd/net snmp prefers this over '2' */
		}elseif ($version == "3") {
			if ($priv_proto == "[None]" || $priv_pass == '') {
				$proto = "authNoPriv";
				$priv_proto = "";
			}else{
				$proto = "authPriv";
			}

			if (strlen($priv_pass)) {
				$priv_pass = "-X " . cacti_escapeshellarg($priv_pass) . " -x " . cacti_escapeshellarg($priv_proto);
			}else{
				$priv_pass = "";
			}

			if (strlen($context)) {
				$context = "-n " . cacti_escapeshellarg($context);
			}else{
				$context = "";
			}

			$snmp_auth = trim("-u " . cacti_escapeshellarg($username) .
				" -l " . cacti_escapeshellarg($proto) .
				" -a " . cacti_escapeshellarg($auth_proto) .
				" -A " . cacti_escapeshellarg($password) .
				" "    . $priv_pass .
				" "    . $context); /* v3 - username/password */
		}

		/* no valid snmp version has been set, get out */
		if (empty($snmp_auth)) { return; }

		if (read_config_option("snmp_version") == "ucd-snmp") {
			/* escape the command to be executed and vulnerable parameters
			 * numeric parameters are not subject to command injection
			 * snmp_auth is treated seperately, see above */
			exec(cacti_escapeshellcmd(read_config_option("path_snmpget")) . " -O vt -v$version -t $timeout -r $retries " . cacti_escapeshellarg($hostname) . ":$port $snmp_auth " . cacti_escapeshellarg($oid), $snmp_value);
		}else {
			exec(cacti_escapeshellcmd(read_config_option("path_snmpget")) . " -O fntevU " . $snmp_auth . " -v $version -t $timeout -r $retries " . cacti_escapeshellarg($hostname) . ":$port " . cacti_escapeshellarg($oid), $snmp_value);
		}

		/* fix for multi-line snmp output */
		if (is_array($snmp_value)) {
			$snmp_value = implode(" ", $snmp_value);
		}
	}

	/* fix for multi-line snmp output */
	if (isset($snmp_value)) {
		if (is_array($snmp_value)) {
			$snmp_value = implode(" ", $snmp_value);
		}
	}

	if (substr_count($snmp_value, "Timeout:")) {
		cacti_log("WARNING: bdcom SNMP Get Timeout for Device[$hostname], and OID:'$oid'", false);
	}
	//fix incorect hex 
	if (substr(strtolower($snmp_value), 0, 4) == 'hex-') {
		$snmp_value = trim(str_ireplace('hex-', '', $snmp_value));
	}
	/* strip out non-snmp data */
	$snmp_value = format_snmp_string($snmp_value, false);

	return $snmp_value;
}



function bdcom_snmp_get_ucd($hostname, $community, $oid, $version, $username, $password, $auth_proto, $priv_pass, $priv_proto, $context, $port = 161, $timeout = 500, $retries = 0, $environ = SNMP_POLLER) {
	global $config;

	/* determine default retries */
	if (($retries == 0) || (!is_numeric($retries))) {
		$retries = read_config_option("snmp_retries");
		if ($retries == "") $retries = 3;
	}

	/* do not attempt to poll invalid combinations */
	if (($version == 0) || (!is_numeric($version)) ||
		(!is_numeric($port)) ||
		(!is_numeric($retries)) ||
		(!is_numeric($timeout)) ||
		(($community == "") && ($version != 3))
		) {
		return "U";
	}

	if (((snmp_get_method($version) == SNMP_METHOD_PHP) &&
		(!strlen($context) || ($version != 3))) and false) {
		/* make sure snmp* is verbose so we can see what types of data
		we are getting back */
		snmp_set_quick_print(0);

		if ($version == "1") {
			$snmp_value = @snmpget("$hostname:$port", "$community", "$oid", ($timeout * 1000), $retries);
		}elseif ($version == "2") {
			$snmp_value = @snmp2_get("$hostname:$port", "$community", "$oid", ($timeout * 1000), $retries);
		}else{
			if ($priv_proto == "[None]" || $priv_pass == '') {
				$proto = "authNoPriv";
				$priv_proto = "";
			}else{
				$proto = "authPriv";
			}

			$snmp_value = @snmp3_get("$hostname:$port", "$username", $proto, $auth_proto, "$password", $priv_proto, "$priv_pass", "$oid", ($timeout * 1000), $retries);
		}

		if ($snmp_value === false) {
			cacti_log("WARNING: bdcom SNMP Get Timeout for Device[$hostname], and OID:'$oid'", false);
		}
	}else {
		$snmp_value = '';
		/* ucd/net snmp want the timeout in seconds */
		$timeout = ceil($timeout / 1000);

		if ($version == "1") {
			$snmp_auth = (read_config_option("snmp_version") == "ucd-snmp") ? cacti_escapeshellarg($community): "-c " . cacti_escapeshellarg($community); /* v1/v2 - community string */
		}elseif ($version == "2") {
			$snmp_auth = (read_config_option("snmp_version") == "ucd-snmp") ? cacti_escapeshellarg($community) : "-c " . cacti_escapeshellarg($community); /* v1/v2 - community string */
			$version = "2c"; /* ucd/net snmp prefers this over '2' */
		}elseif ($version == "3") {
			if ($priv_proto == "[None]" || $priv_pass == '') {
				$proto = "authNoPriv";
				$priv_proto = "";
			}else{
				$proto = "authPriv";
			}

			if (strlen($priv_pass)) {
				$priv_pass = "-X " . cacti_escapeshellarg($priv_pass) . " -x " . cacti_escapeshellarg($priv_proto);
			}else{
				$priv_pass = "";
			}

			if (strlen($context)) {
				$context = "-n " . cacti_escapeshellarg($context);
			}else{
				$context = "";
			}

			$snmp_auth = trim("-u " . cacti_escapeshellarg($username) .
				" -l " . cacti_escapeshellarg($proto) .
				" -a " . cacti_escapeshellarg($auth_proto) .
				" -A " . cacti_escapeshellarg($password) .
				" "    . $priv_pass .
				" "    . $context); /* v3 - username/password */
		}

		/* no valid snmp version has been set, get out */
		if (empty($snmp_auth)) { return; }

		if (read_config_option("snmp_version") == "ucd-snmp") {
			/* escape the command to be executed and vulnerable parameters
			 * numeric parameters are not subject to command injection
			 * snmp_auth is treated seperately, see above */
			exec(cacti_escapeshellcmd(read_config_option("path_snmpget")) . " -O vt -v$version -t $timeout -r $retries " . cacti_escapeshellarg($hostname) . ":$port $snmp_auth " . cacti_escapeshellarg($oid), $snmp_value);
		}else {
			exec(cacti_escapeshellcmd(read_config_option("path_snmpget")) . " -O fntevU " . $snmp_auth . " -v $version -t $timeout -r $retries " . cacti_escapeshellarg($hostname) . ":$port " . cacti_escapeshellarg($oid), $snmp_value);
		}

		/* fix for multi-line snmp output */
		if (is_array($snmp_value)) {
			$snmp_value = implode(" ", $snmp_value);
		}
	}

	/* fix for multi-line snmp output */
	if (isset($snmp_value)) {
		if (is_array($snmp_value)) {
			$snmp_value = implode(" ", $snmp_value);
		}
	}

	if (substr_count($snmp_value, "Timeout:")) {
		cacti_log("WARNING: bdcom SNMP Get Timeout for Device[$hostname], and OID:'$oid'", false);
	}

	/* strip out non-snmp data */
	$snmp_value = format_snmp_string($snmp_value, false);

	return $snmp_value;
}
 
 
function bdcom_snmp_walk($hostname, $community, $oid, $version, $username, $password, $auth_proto, $priv_pass, $priv_proto, $context, $port = 161, $timeout = 500, $retries = 0, $max_oids = 10, $environ = SNMP_POLLER) {
	global $config, $banned_snmp_strings;

	$snmp_oid_included = true;
	$snmp_auth	       = '';
	$snmp_array        = array();
	$temp_array        = array();

	/* determine default retries */
	if (($retries == 0) || (!is_numeric($retries))) {
		$retries = read_config_option("snmp_retries");
		if ($retries == "") $retries = 3;
	}

	/* determine default max_oids */
	if (($max_oids == 0) || (!is_numeric($max_oids))) {
		$max_oids = read_config_option("max_get_size");

		if ($max_oids == "") $max_oids = 10;
	}

	/* do not attempt to poll invalid combinations */
	if (($version == 0) || (!is_numeric($version)) ||
		(!is_numeric($max_oids)) ||
		(!is_numeric($port)) ||
		(!is_numeric($retries)) ||
		(!is_numeric($timeout)) ||
		(($community == "") && ($version != 3))
		) {
		return array();
	}

	$path_snmpbulkwalk = read_config_option("path_snmpbulkwalk");

	if ((snmp_get_method($version) == SNMP_METHOD_PHP) &&
		(!strlen($context) || ($version != 3)) &&
		(($version == 1) ||
		(version_compare(phpversion(), "5.1") >= 0) ||
		(!file_exists($path_snmpbulkwalk)))) {
		/* make sure snmp* is verbose so we can see what types of data
		we are getting back */

		/* force php to return numeric oid's */
		if (function_exists("snmp_set_oid_numeric_print")) {
			snmp_set_oid_numeric_print(TRUE);
		}

		if (function_exists("snmprealwalk")) {
			$snmp_oid_included = false;
		}

		snmp_set_quick_print(0);

		/* force php to return numeric oid's */
		if (function_exists("snmp_set_oid_numeric_print")) {
			snmp_set_oid_numeric_print(TRUE);
		}
		#http://bugs.cacti.net/view.php?id=2296
		if (function_exists("snmp_set_oid_output_format")) {
			snmp_set_oid_output_format(SNMP_OID_OUTPUT_NUMERIC);
		}	
	
		if ($version == "1") {
			$temp_array = @snmprealwalk("$hostname:$port", "$community", "$oid", ($timeout * 1000), $retries);
		}elseif ($version == "2") {
			$temp_array = @snmp2_real_walk("$hostname:$port", "$community", "$oid", ($timeout * 1000), $retries);
		}else{
			if ($priv_proto == "[None]" || $priv_pass == '') {
				$proto = "authNoPriv";
				$priv_proto = "";
			}else{
				$proto = "authPriv";
			}

			$temp_array = @snmp3_real_walk("$hostname:$port", "$username", $proto, $auth_proto, "$password", $priv_proto, "$priv_pass", "$oid", ($timeout * 1000), $retries);
		}

		if ($temp_array === false) {
			cacti_log("WARNING: bdcom SNMP Walk Timeout for Device[$hostname], and OID:'$oid'", false);
		}

		/* check for bad entries */
		if (is_array($temp_array) && sizeof($temp_array)) {
		foreach($temp_array as $key => $value) {
			foreach($banned_snmp_strings as $item) {
				if(strstr($value, $item) != "") {
					unset($temp_array[$key]);
					continue 2;
				}
			}
		}
		}

		$o = 0;
		for (@reset($temp_array); $i = @key($temp_array); next($temp_array)) {
			if ($temp_array[$i] != "NULL") {
				$snmp_array[$o]["oid"] = preg_replace("/^\./", "", $i);
				$snmp_array[$o]["value"] = format_snmp_string($temp_array[$i], $snmp_oid_included);
			}
			$o++;
		}
	}else{
		/* ucd/net snmp want the timeout in seconds */
		$timeout = ceil($timeout / 1000);

		if ($version == "1") {
			$snmp_auth = (read_config_option("snmp_version") == "ucd-snmp") ? cacti_escapeshellarg($community): "-c " . cacti_escapeshellarg($community); /* v1/v2 - community string */
		}elseif ($version == "2") {
			$snmp_auth = (read_config_option("snmp_version") == "ucd-snmp") ? cacti_escapeshellarg($community): "-c " . cacti_escapeshellarg($community); /* v1/v2 - community string */
			$version = "2c"; /* ucd/net snmp prefers this over '2' */
		}elseif ($version == "3") {
			if ($priv_proto == "[None]" || $priv_pass == '') {
				$proto = "authNoPriv";
				$priv_proto = "";
			}else{
				$proto = "authPriv";
			}

			if (strlen($priv_pass)) {
				$priv_pass = "-X " . cacti_escapeshellarg($priv_pass) . " -x " . cacti_escapeshellarg($priv_proto);
			}else{
				$priv_pass = "";
			}

			if (strlen($context)) {
				$context = "-n " . cacti_escapeshellarg($context);
			}else{
				$context = "";
			}

			$snmp_auth = trim("-u " . cacti_escapeshellarg($username) .
				" -l " . cacti_escapeshellarg($proto) .
				" -a " . cacti_escapeshellarg($auth_proto) .
				" -A " . cacti_escapeshellarg($password) .
				" "    . $priv_pass .
				" "    . $context); /* v3 - username/password */
		}

		if (read_config_option("snmp_version") == "ucd-snmp") {
			/* escape the command to be executed and vulnerable parameters
			 * numeric parameters are not subject to command injection
			 * snmp_auth is treated seperately, see above */
			$temp_array = exec_into_array(cacti_escapeshellcmd(read_config_option("path_snmpwalk")) . " -v$version -t $timeout -r $retries " . cacti_escapeshellarg($hostname) . ":$port $snmp_auth " . cacti_escapeshellarg($oid));
		}else {
			if (file_exists($path_snmpbulkwalk) && ($version > 1) && ($max_oids > 1)) {
				$temp_array = exec_into_array(cacti_escapeshellcmd($path_snmpbulkwalk) . " -O Qn $snmp_auth -v $version -t $timeout -r $retries -Cr$max_oids " . cacti_escapeshellarg($hostname) . ":$port " . cacti_escapeshellarg($oid));
			}else{
				$temp_array = exec_into_array(cacti_escapeshellcmd(read_config_option("path_snmpwalk")) . " -O Qn $snmp_auth -v $version -t $timeout -r $retries " . cacti_escapeshellarg($hostname) . ":$port " . cacti_escapeshellarg($oid));
			}
		}

		if (substr_count(implode(" ", $temp_array), "Timeout:")) {
			cacti_log("WARNING: bdcom SNMP Walk Timeout for Device[$hostname], and OID:'$oid'", false);
		}

		/* check for bad entries */
		if (is_array($temp_array) && sizeof($temp_array)) {
		foreach($temp_array as $key => $value) {
			foreach($banned_snmp_strings as $item) {
				if(strstr($value, $item) != "") {
					unset($temp_array[$key]);
					continue 2;
				}
			}
		}
		}

		for ($i=0; $i < count($temp_array); $i++) {
			if ($temp_array[$i] != "NULL") {
				/* returned SNMP string e.g. 
				 * .1.3.6.1.2.1.31.1.1.1.18.1 = STRING: === bla ===
				 * split off first chunk before the "="; this is the OID
				 */
				list($oid, $value) = explode("=", $temp_array[$i], 2);
				$snmp_array[$i]["oid"]   = trim($oid);
				$snmp_array[$i]["value"] = format_snmp_string($temp_array[$i], true);
			}
		}
	}

	return $snmp_array;
}

function bdcom_snmp_walk_usdbin($hostname, $community, $oid, $version, $username, $password, $auth_proto, $priv_pass, $priv_proto, $context, $port = 161, $timeout = 500, $retries = 0, $max_oids = 10, $environ = SNMP_POLLER) {
	global $config, $banned_snmp_strings;

	$snmp_oid_included = true;
	$snmp_auth	       = '';
	$snmp_array        = array();
	$temp_array        = array();

	/* determine default retries */
	if (($retries == 0) || (!is_numeric($retries))) {
		$retries = read_config_option("snmp_retries");
		if ($retries == "") $retries = 3;
	}

	/* determine default max_oids */
	if (($max_oids == 0) || (!is_numeric($max_oids))) {
		$max_oids = read_config_option("max_get_size");

		if ($max_oids == "") $max_oids = 10;
	}

	/* do not attempt to poll invalid combinations */
	if (($version == 0) || (!is_numeric($version)) ||
		(!is_numeric($max_oids)) ||
		(!is_numeric($port)) ||
		(!is_numeric($retries)) ||
		(!is_numeric($timeout)) ||
		(($community == "") && ($version != 3))
		) {
		return array();
	}

	$path_snmpbulkwalk = read_config_option("path_snmpbulkwalk");

	if (((snmp_get_method($version) == SNMP_METHOD_PHP) &&
		(!strlen($context) || ($version != 3)) &&
		(($version == 1) ||
		(version_compare(phpversion(), "5.1") >= 0) ||
		(!file_exists($path_snmpbulkwalk)))) and false) {
		/* make sure snmp* is verbose so we can see what types of data
		we are getting back */

		/* force php to return numeric oid's */
		if (function_exists("snmp_set_oid_numeric_print")) {
			snmp_set_oid_numeric_print(TRUE);
		}

		if (function_exists("snmprealwalk")) {
			$snmp_oid_included = false;
		}

		snmp_set_quick_print(0);

		/* force php to return numeric oid's */
		if (function_exists("snmp_set_oid_numeric_print")) {
			snmp_set_oid_numeric_print(TRUE);
		}
		#http://bugs.cacti.net/view.php?id=2296
		if (function_exists("snmp_set_oid_output_format")) {
			snmp_set_oid_output_format(SNMP_OID_OUTPUT_NUMERIC);
		}	
	
		if ($version == "1") {
			$temp_array = @snmprealwalk("$hostname:$port", "$community", "$oid", ($timeout * 1000), $retries);
		}elseif ($version == "2") {
			$temp_array = @snmp2_real_walk("$hostname:$port", "$community", "$oid", ($timeout * 1000), $retries);
		}else{
			if ($priv_proto == "[None]" || $priv_pass == '') {
				$proto = "authNoPriv";
				$priv_proto = "";
			}else{
				$proto = "authPriv";
			}

			$temp_array = @snmp3_real_walk("$hostname:$port", "$username", $proto, $auth_proto, "$password", $priv_proto, "$priv_pass", "$oid", ($timeout * 1000), $retries);
		}

		if ($temp_array === false) {
			cacti_log("WARNING: bdcom SNMP Walk Timeout for Device[$hostname], and OID:'$oid'", false);
		}

		/* check for bad entries */
		if (is_array($temp_array) && sizeof($temp_array)) {
		foreach($temp_array as $key => $value) {
			foreach($banned_snmp_strings as $item) {
				if(strstr($value, $item) != "") {
					unset($temp_array[$key]);
					continue 2;
				}
			}
		}
		}

		$o = 0;
		for (@reset($temp_array); $i = @key($temp_array); next($temp_array)) {
			if ($temp_array[$i] != "NULL") {
				$snmp_array[$o]["oid"] = preg_replace("/^\./", "", $i);
				$snmp_array[$o]["value"] = format_snmp_string($temp_array[$i], $snmp_oid_included);
			}
			$o++;
		}
	}else{
		/* ucd/net snmp want the timeout in seconds */
		$timeout = ceil($timeout / 1000);

		if ($version == "1") {
			$snmp_auth = (read_config_option("snmp_version") == "ucd-snmp") ? cacti_escapeshellarg($community): "-c " . cacti_escapeshellarg($community); /* v1/v2 - community string */
		}elseif ($version == "2") {
			$snmp_auth = (read_config_option("snmp_version") == "ucd-snmp") ? cacti_escapeshellarg($community): "-c " . cacti_escapeshellarg($community); /* v1/v2 - community string */
			$version = "2c"; /* ucd/net snmp prefers this over '2' */
		}elseif ($version == "3") {
			if ($priv_proto == "[None]" || $priv_pass == '') {
				$proto = "authNoPriv";
				$priv_proto = "";
			}else{
				$proto = "authPriv";
			}

			if (strlen($priv_pass)) {
				$priv_pass = "-X " . cacti_escapeshellarg($priv_pass) . " -x " . cacti_escapeshellarg($priv_proto);
			}else{
				$priv_pass = "";
			}

			if (strlen($context)) {
				$context = "-n " . cacti_escapeshellarg($context);
			}else{
				$context = "";
			}

			$snmp_auth = trim("-u " . cacti_escapeshellarg($username) .
				" -l " . cacti_escapeshellarg($proto) .
				" -a " . cacti_escapeshellarg($auth_proto) .
				" -A " . cacti_escapeshellarg($password) .
				" "    . $priv_pass .
				" "    . $context); /* v3 - username/password */
		}

		if (read_config_option("snmp_version") == "ucd-snmp") {
			/* escape the command to be executed and vulnerable parameters
			 * numeric parameters are not subject to command injection
			 * snmp_auth is treated seperately, see above */
			$temp_array = exec_into_array(cacti_escapeshellcmd(read_config_option("path_snmpwalk")) . " -v$version -t $timeout -r $retries " . cacti_escapeshellarg($hostname) . ":$port $snmp_auth " . cacti_escapeshellarg($oid));
		}else {
			if (file_exists($path_snmpbulkwalk) && ($version > 1) && ($max_oids > 1)) {
				$temp_array = exec_into_array(cacti_escapeshellcmd($path_snmpbulkwalk) . " -O Qn $snmp_auth -v $version -t $timeout -r $retries -Cr$max_oids " . cacti_escapeshellarg($hostname) . ":$port " . cacti_escapeshellarg($oid));
			}else{
				$temp_array = exec_into_array(cacti_escapeshellcmd(read_config_option("path_snmpwalk")) . " -O Qn $snmp_auth -v $version -t $timeout -r $retries " . cacti_escapeshellarg($hostname) . ":$port " . cacti_escapeshellarg($oid));
			}
		}

		if (substr_count(implode(" ", $temp_array), "Timeout:")) {
			cacti_log("WARNING: bdcom SNMP Walk Timeout for Device[$hostname], and OID:'$oid'", false);
		}

		/* check for bad entries */
		if (is_array($temp_array) && sizeof($temp_array)) {
		foreach($temp_array as $key => $value) {
			foreach($banned_snmp_strings as $item) {
				if(strstr($value, $item) != "") {
					unset($temp_array[$key]);
					continue 2;
				}
			}
		}
		}

		for ($i=0; $i < count($temp_array); $i++) {
			if ($temp_array[$i] != "NULL") {
				/* returned SNMP string e.g. 
				 * .1.3.6.1.2.1.31.1.1.1.18.1 = STRING: === bla ===
				 * split off first chunk before the "="; this is the OID
				 */
				list($oid, $value) = explode("=", $temp_array[$i], 2);
				$snmp_array[$i]["oid"]   = trim($oid);
				$snmp_array[$i]["value"] = format_snmp_string($temp_array[$i], true);
			}
		}
	}

	return $snmp_array;
}
  
 
 /*    bdcom_xform_standard_indexed_data - This function takes an OID, and a device, and
   optionally an alternate snmp_readstring as input parameters and then walks the
   OID and returns the data in array[index] = value format.
 */
 function bdcom_xform_standard_indexed_data($xformOID, $device, $snmp_readstring = "") {
     /* get raw index data */
     //print ("=== [START1]\n");
 //  if ($snmp_readstring == "") {
 //        $snmp_readstring = $device["snmp_readstring"];
 //        print ("=== snmp_readstring=[". $device["snmp_timeout"] . "]\n");
 //    }
 
     $xformArray = bdcom_snmp_walk($device["hostname"], $device["snmp_get_community"], $xformOID, $device["snmp_get_version"], $device["snmp_get_username"], $device["snmp_get_password"], $device["snmp_get_auth_protocol"], $device["snmp_get_priv_passphrase"], $device["snmp_get_priv_protocol"],  $device["snmp_get_context"], $device["snmp_port"], $device["snmp_timeout"]);
 
     $i = 0;
     foreach($xformArray as $xformItem) {
         $perPos = strrpos($xformItem["oid"], ".");
         $xformItemID = substr($xformItem["oid"], $perPos+1);
         $xformArray[$i]["oid"] = $xformItemID;
         //print ("=]=[". $xformArray[$i]["oid"] . "--" . $xformArray[$i]["value"] ."]\n");
		 $xformArray[$i]["value"] = trim(preg_replace ("/^((HEX\-00|HEX\-)\:?)/", "",$xformItem["value"]));
		 
         $i++;
     }
 
     return array_rekey($xformArray, "oid", "value");
 }

 function bdcom_xform2_standard_indexed_data($xformOID, $device, $snmp_readstring = "") {
     /* get raw index data */
     //print ("=== [START1]\n");
 //  if ($snmp_readstring == "") {
 //        $snmp_readstring = $device["snmp_readstring"];
 //        print ("=== snmp_readstring=[". $device["snmp_timeout"] . "]\n");
 //    }
 
     $xformArray = cacti_snmp_walk($device["hostname"], $device["snmp_get_community"], $xformOID, $device["snmp_get_version"], $device["snmp_get_username"], $device["snmp_get_password"], $device["snmp_get_auth_protocol"], $device["snmp_get_priv_passphrase"], $device["snmp_get_priv_protocol"],  $device["snmp_get_context"], $device["snmp_port"], $device["snmp_timeout"]);
 
     $i = 0;
     foreach($xformArray as $xformItem) {
         $perPos = strrpos($xformItem["oid"], ".");
         $xformItemID = substr($xformItem["oid"], $perPos+1);
         $xformArray[$i]["oid"] = $xformItemID;
         //print ("=]=[". $xformArray[$i]["oid"] . "--" . $xformArray[$i]["value"] ."]\n");
		 $xformArray[$i]["value"] = trim(preg_replace ("/^((HEX\-00|HEX\-)\:?)/", "",$xformItem["value"]));
		 
         $i++;
     }
 
     return array_rekey($xformArray, "oid", "value");
 }
 
  function bdcom_xform_standard_indexed_data_oid_usdbin($xformOID, $device, $snmp_readstring = "") {

   
    $xformArray = bdcom_snmp_walk_usdbin($device["hostname"], $device["snmp_get_community"], $xformOID, $device["snmp_get_version"], $device["snmp_get_username"], $device["snmp_get_password"], $device["snmp_get_auth_protocol"], $device["snmp_get_priv_passphrase"], $device["snmp_get_priv_protocol"],  $device["snmp_get_context"], $device["snmp_port"], $device["snmp_timeout"]);
 
    $i = 0;
         
    if (substr($xformOID, 0,1) != ".") {
		$xformOID = "." . $xformOID ;
    }	 
    foreach($xformArray as $xformItem) {
         
        if (substr($xformItem["oid"], 0,1) != ".") {
            $xformItem["oid"] = "." . $xformItem["oid"] ;
        }
      
         $xformItem["oid"] = str_replace("iso","1",$xformItem["oid"]);
		 //$perPos = strrpos($xformItem["oid"], ".");
         $xformItemID = str_replace($xformOID . ".", "", $xformItem["oid"]); 
		 $xformArray[$i]["value"] = trim(preg_replace ("/^((HEX\-00|HEX\-)\:?)/", "",$xformItem["value"]));
		 
         $i++;
    }
 
    return array_rekey($xformArray, "oid", "value");
 }
 
 
  function bdcom_xform_standard_indexed_data_usdbin($xformOID, $device, $snmp_readstring = "") {
     /* get raw index data */
     //print ("=== [START1]\n");
 //  if ($snmp_readstring == "") {
 //        $snmp_readstring = $device["snmp_readstring"];
 //        print ("=== snmp_readstring=[". $device["snmp_timeout"] . "]\n");
 //    }
 
     $xformArray = bdcom_snmp_walk_usdbin($device["hostname"], $device["snmp_get_community"], $xformOID, $device["snmp_get_version"], $device["snmp_get_username"], $device["snmp_get_password"], $device["snmp_get_auth_protocol"], $device["snmp_get_priv_passphrase"], $device["snmp_get_priv_protocol"],  $device["snmp_get_context"], $device["snmp_port"], $device["snmp_timeout"]);
 
     $i = 0;
     foreach($xformArray as $xformItem) {
         $perPos = strrpos($xformItem["oid"], ".");
         $xformItemID = substr($xformItem["oid"], $perPos+1);
         $xformArray[$i]["oid"] = $xformItemID;
         //print ("=]=[". $xformArray[$i]["oid"] . "--" . $xformArray[$i]["value"] ."]\n");
		 $xformArray[$i]["value"] = trim(preg_replace ("/^((HEX\-00|HEX\-)\:?)/", "",$xformItem["value"]));
		 
         $i++;
     }
 
     return array_rekey($xformArray, "oid", "value");
 }
 
 function xform_standard_indexed_data_oid($xformOID, &$device, $snmp_readstring = "") {
     /* get raw index data */
     //print ("=== [START1]\n");
 //  if ($snmp_readstring == "") {
 //        $snmp_readstring = $device["snmp_readstring"];
 //        print ("=== snmp_readstring=[". $device["snmp_timeout"] . "]\n");
 //    }
 
     $xformArray = bdcom_snmp_walk($device["hostname"], $device["snmp_get_community"],$xformOID, $device["snmp_get_version"], $device["snmp_get_username"], $device["snmp_get_password"], $device["snmp_get_auth_protocol"], $device["snmp_get_priv_passphrase"], $device["snmp_get_priv_protocol"],  $device["snmp_get_context"], $device["snmp_port"], $device["snmp_timeout"]);
 
                     
	if (substr($xformOID, 0,1) != ".") {
		$xformOID = "." . $xformOID ;
	}                            
     $i = 0;
     foreach($xformArray as $xformItem) {
 
         
         if (substr($xformItem["oid"], 0,1) != ".") {
             $xformItem["oid"] = "." . $xformItem["oid"] ;
         }

		// replace output like ".iso.3.6.1.4.1.171.12.23.4.1.1.1.172.19.16.194"  ==>  ".1.3.6.1.4.1.171.12.23.4.1.1.1.172.19.16.194"
         $xformItem["oid"] = str_replace("iso","1",$xformItem["oid"]);
         $xformItemID = str_replace($xformOID . ".", "", $xformItem["oid"]);        
 
 
         $xformArray[$i]["oid"] = $xformItemID;
		 $xformArray[$i]["value"] = trim(preg_replace("/^((HEX\-00|HEX\-)\:?)/", "",$xformItem["value"]));

         $i++;
     }
 	//print_r($xformArray);
     return array_rekey($xformArray, "oid", "value");
 }
 
 
 //создает массив со значением = индексу
 function xform_standard_indexed_data_oid_index($xformOID, &$device, $snmp_readstring = "") {
     /* get raw index data */
     //print ("=== [START1]\n");
 //  if ($snmp_readstring == "") {
 //        $snmp_readstring = $device["snmp_readstring"];
 //        print ("=== snmp_readstring=[". $device["snmp_timeout"] . "]\n");
 //    }
 
     $xformArray = bdcom_snmp_walk($device["hostname"], $device["snmp_get_community"], $xformOID, $device["snmp_get_version"], $device["snmp_get_username"],$device["snmp_get_password"], $device["snmp_get_auth_protocol"], $device["snmp_get_priv_passphrase"], $device["snmp_get_priv_protocol"],  $device["snmp_get_context"], $device["snmp_port"], $device["snmp_timeout"]);
 
                     
                            
     $i = 0;
     foreach($xformArray as $xformItem) {
         
         if (substr($xformItem["oid"], 0,1) != ".") {
             $xformItem["oid"] = "." . $xformItem["oid"] ;
         }
         if (substr($xformOID, 0,1) != ".") {
             $xformOID = "." . $xformOID ;
         }
         //$perPos = strrpos($xformItem["oid"], ".");
        // $xformItemID = substr($xformItem["oid"], $perPos+1);
		// replace output like ".iso.3.6.1.4.1.171.12.23.4.1.1.1.172.19.16.194"  ==>  ".1.3.6.1.4.1.171.12.23.4.1.1.1.172.19.16.194"
         $xformItem["oid"] = str_replace("iso","1",$xformItem["oid"]);
		 
         $xformItemID = str_replace($xformOID . ".", "", $xformItem["oid"]);
         $xformArray[$i]["oid"] = $xformItemID;
         $xformArray[$i]["value"] = $xformItemID;
 //        print ("=]=[". $xformArray[$i]["oid"] . "--" . $xformArray[$i]["value"] ."]\n");
         $i++;
     }
 
     return array_rekey($xformArray, "oid", "value");
 }
 
 function bdcom_array_rekey($array, $key) {
	$ret_array = array();

	if (sizeof($array) > 0) {
	foreach ($array as $item) {
		$ret_array[$item[$key]] = $item;
	}
	}

	return $ret_array;
}

 function bdcom_array_compress_strip($array) {
	$ret_array = array();

	if (sizeof($array) > 0) {
		foreach ($array as $item => $key) {
			$ind = substr ($item, 0, strpos ($item,"."));
			if ((strpos($item, ".100") > 0) or (strpos($item, ".101") > 0)) {
				if (isset($array[$ind . ".101"])){
					$ret_array[$ind] = $array[$ind . ".101"];
				}else{
					$ret_array[$ind] = $array[$ind . ".100"];			
				}			
			}elseif((strpos($item, ".1") > 0) or (strpos($item, ".2") > 0)) {
				if (isset($array[$ind . ".2"])){
					$ret_array[$ind] = $array[$ind . ".2"];
				}else{
					$ret_array[$ind] = $array[$ind . ".1"];			
				}			
			}else{
				$ret_array[$item] = $key;
			}
			
		}
	}

	return $ret_array;
}
 
 /*    bdcom_db_update_device_status - This function is used by the scanner to save the status
   of the current device including the number of ports, it's readstring, etc.
 */
   function bdcom_db_update_device_status(&$device, $host_up, $scan_date, $start_time) {
     global $debug;
 
     list($micro,$seconds) = explode(" ", microtime());
     $end_time = $seconds + $micro;
     $runduration = $end_time - $start_time;
 	
 	//$count_ban_ipmac = db_fetch_cell("SELECT count(*) FROM bdcom_imb_macip where `device_id`='" . $device["device_id"] . "' and `macip_banned`=1;");
	$count_ban_ipmac = 0;
 
     if ($host_up == TRUE) {
         $update_string = "UPDATE plugin_bdcom_devices " .
            "SET device_type_id='" . $device["device_type_id"] . "'," .
            "epon_total='" . $device["epon_total"] . "'," .
 			"ports_total='" . $device["ports_total"] . "'," .
            "onu_total='" . $device["onu_total"] . "'," .
			"onu_online_total='" . $device["onu_Activetotal"] . "'," .
             "snmp_sysName='" . addslashes($device["snmp_sysName"]) . "'," .
             "snmp_sysLocation='" . addslashes($device["snmp_sysLocation"]) . "'," .
             "snmp_sysContact='" . addslashes($device["snmp_sysContact"]) . "'," .
             "snmp_sysObjectID='" . $device["snmp_sysObjectID"] . "'," .
             "snmp_sysDescr='" . addslashes($device["snmp_sysDescr"]) . "'," .
             "snmp_sysUptime='" . $device["snmp_sysUptime"] . "'," .
             "snmp_status='" . $device["snmp_status"] . "'," .
             "last_runmessage='" . $device["last_runmessage"] . "'," .
             "last_rundate='" . $scan_date . "'," .
             "last_runduration='" . round($runduration,4) . "' " .
             "WHERE device_id ='" . $device["device_id"] . "'";
     }else{
         $update_string = "UPDATE plugin_bdcom_devices " .
             "SET snmp_status='" . $device["snmp_status"] . "'," .
             "device_type_id='" . $device["device_type_id"] . "'," .
             "epon_total='0'," .
             "ports_total='0'," .
             "onu_total='0'," .
             "last_runmessage='Device Unreachable', " .
             "last_runduration='" . round($runduration,4) . "' " .
             "WHERE device_id ='" . $device["device_id"] . "'";
     }
 
     //bdcom_debug("SQL: " . $update_string);
 
     db_execute($update_string);
 }
 
 
 /*    bdcom_db_process_add - This function adds a process to the process table with the entry
   with the device_id as key.
 */
 function bdcom_db_process_add($device_id, $storepid = FALSE) {
     /* store the PID if required */
     if ($storepid) {
         $pid = getmypid();
     }else{
         $pid = 0;
     }
 
     /* store pseudo process id in the database */
     db_execute("INSERT INTO plugin_bdcom_processes (device_id, process_id, status, start_date) VALUES ('" . $device_id . "', '" . $pid . "', 'Running', NOW())");
 }
 
 /*    bdcom_db_process_remove - This function removes a devices entry from the processes
   table indicating that the device is done processing and the next device may start.
 */
 function bdcom_db_process_remove($device_id) {
     db_execute("DELETE FROM plugin_bdcom_processes WHERE device_id='" . $device_id . "'");
 }
 
 function bdcom_db_store_device_port_results(&$device, $port_array, $scan_date) {
     global $debug;
  $first_row=0;
 
 
    /* output details to database */
             $insert_string = "INSERT INTO plugin_bdcom_ports " .
                 "(device_id,port_number,port_ifndex,port_name,port_descr,port_type,port_admin_status,port_oper_status,port_online,port_lastchange_date,scan_date)  VALUES ";
                    
     foreach($port_array as $port_value) {
         if ($first_row == 1) {
          $insert_string .= ", ";
         }else{
              $first_row=1;
         }
             
         $insert_string .= "('" .
                $device["device_id"] . "','" .
                $port_value["ifNumber"] . "','" .
				$port_value["ifIndex"] . "','" .
                $port_value["name"] . "','" .
				$port_value["descr"] . "','" .
				$port_value["ifType"] . "','" .
                $port_value["ifAdminState"] . "','" .
				$port_value["ifOperState"] . "','" .
				"1" . "','" .
                $scan_date . "','" .
				$scan_date . "')";

 
            // bdcom_debug("SQL: " . $insert_string);
         }
         $insert_string .= " ON DUPLICATE KEY UPDATE " .
				"port_number = VALUES(port_number) , " .
				"port_name = VALUES(port_name) , " .
				"port_descr = VALUES(port_descr) , " .
				"port_type = VALUES(port_type) , " .
				"port_admin_status = VALUES(port_admin_status) , " .
				"port_oper_status = VALUES(port_oper_status) , " .
				"port_online = VALUES(port_online) , " .
				"port_lastchange_date=IF (port_oper_status=VALUES(port_oper_status),port_lastchange_date,VALUES(scan_date)), " .
				"scan_date = VALUES(scan_date), " .
				"port_oper_status = VALUES(port_oper_status);";
				
         db_execute($insert_string);

 }
 
		
function bdcom_db_store_device_epon_results(&$device, $epon_array, $scan_date) {
	global $debug;
	$first_row=0;
 
 
    /* output details to database */
             $insert_string = "INSERT INTO plugin_bdcom_epons " .
                 "(device_id,epon_index,epon_number,epon_name,epon_descr,epon_adminstatus,epon_operstatus,epon_linkstatus,epon_txpower,epon_rxpower,epon_volt,epon_temp,epon_onu_list,epon_onu_total,epon_onu_active_total,epon_online,epon_lastchange_date,epon_scan_date)  VALUES ";
                    
     foreach($epon_array as $epon) {
         if ($first_row == 1) {
          $insert_string .= ", ";
         }else{
              $first_row=1;
         }
             
         $insert_string .= "('" .
                $device["device_id"] . "','" .
                $epon["ifIndex"] . "','" .
				$epon["ifNumber"] . "','" .
                $epon["name"] . "','" .
				$epon["descr"] . "','" .
				$epon["ifAdminState"]  . "','" .				//epon_adminstatus
				$epon["ifOperState"]  . "','" .				//epon_operstatus
				$epon["LinkStatus"] . "','" .
                $epon["TxPower"] . "','" .
				"0" . "','" .				//epon_rxpower
				$epon["Voltage"] . "','" .
				$epon["Temper"] . "','" .
				$epon["IfindexString"] . "','" .
				($epon["CurActiveOnuCount"] + $epon["CurInActiveOnuCount"]) . "','" .
				$epon["CurActiveOnuCount"] . "','" .
                "1" . "','" .				//epon_online
				$scan_date . "','" .
				$scan_date . "')";

 
            // bdcom_debug("SQL: " . $insert_string);
         }
         $insert_string .= " ON DUPLICATE KEY UPDATE " .
				"epon_name = VALUES(epon_name) , " .
				"epon_number = VALUES(epon_number) , " .
				"epon_descr = VALUES(epon_descr) , " .
				"epon_adminstatus = VALUES(epon_adminstatus) , " .
				"epon_operstatus = VALUES(epon_operstatus) , " .
				"epon_linkstatus = VALUES(epon_linkstatus) , " .
				"epon_txpower = VALUES(epon_txpower) , " .
				"epon_rxpower = VALUES(epon_rxpower) , " .
				"epon_volt = VALUES(epon_volt) , " .
				"epon_temp = VALUES(epon_temp) , " .
				"epon_onu_list = VALUES(epon_onu_list) , " .
				"epon_onu_total = VALUES(epon_onu_total) , " .
				"epon_onu_active_total = VALUES(epon_onu_active_total) , " .
				"epon_online = VALUES(epon_online)," .
				"epon_lastchange_date=IF (epon_operstatus=VALUES(epon_operstatus),epon_lastchange_date,VALUES(epon_scan_date)), " .
				"epon_scan_date = VALUES(epon_scan_date), " .
				"epon_operstatus = VALUES(epon_operstatus);";
				
         db_execute($insert_string);

 }


function bdcom_db_store_device_onu_results(&$device, $onu_array, $scan_date) {
	global $debug;
	$first_row=0;
    /* output details to database */
             $insert_string = "INSERT INTO plugin_bdcom_onu " .
                 "(device_id,
					onu_sequence,
					onu_macaddr,
					onu_vendor,
					onu_version,
					onu_soft_version,
					onu_bindepon,
					epon_id,
					onu_index,
					onu_name,
					onu_descr,
					onu_agrm_id,
					onu_adminstatus,
					onu_operstatus,
					onu_txpower,
					onu_rxpower,
					onu_rxpower_average,
					onu_rxpower_min,
					onu_rxpower_max,
					onu_volt,
					onu_temp,
					onu_distance,
					onu_alivetime,
					onu_dereg_status,
					onu_done_reason,
					onu_aclist,
					onu_online,
					onu_scan_date)  VALUES ";

    foreach($onu_array as $onu) {
        if (($onu["ifOperState"] == 1) and ($onu["distance"] > 0)) {  //update only UP onu
			if ($first_row == 1) {
				$insert_string .= ", ";
			}else{
				$first_row=1;
			}
			$insert_string .= "('" .
					$device["device_id"] . "','" .
					$onu["sequence"] . "','" .
					$onu["mac"] . "','" .
					$onu["vendor"] . "','" .
					$onu["version"] . "','" .
					$onu["soft_version"] . "','" .
					$onu["bindepon"] . "','" .
					"0" . "','" .				//epon_id
					$onu["ifIndex"] . "','" .
					$onu["name"] . "','" .
					$onu["descr"] . "','" .
					"0" . "','" .				//onu_agrement
					$onu["ifAdminState"] . "','" .
					$onu["ifOperState"] . "','" .
					$onu["tx_power"] . "','" .
					$onu["rx_power"] . "','" .
					$onu["rx_power"] . "','" .   //onu_rxpower_average
					$onu["rx_power"] . "','" .   //onu_rxpower_min
					$onu["rx_power"] . "','" .   //onu_rxpower_max
					"0" . "','" .				//onu_volt
					$onu["temper"] . "','" .
					$onu["distance"] . "','" .
					$onu["alive_time"] . "','" . 
					$onu["onuDeregDescr"] . "','" . 
					"CRT" . "','" .				//onu_done_reason
					$onu["onu_aclist"] . "','" .				//onu_aclist
					"1" . "','" .				//onu_online
					$scan_date . "')";

	 
				// bdcom_debug("SQL: " . $insert_string);
				
		}		
				
				
    }
         $insert_string .= " ON DUPLICATE KEY UPDATE " .
				"onu_sequence = VALUES(onu_sequence) , " .
				"onu_macaddr = VALUES(onu_macaddr) , " .
				"onu_vendor = VALUES(onu_vendor) , " .
				"onu_version = VALUES(onu_version) , " .
				"onu_soft_version = VALUES(onu_soft_version) , " .
				"onu_bindepon = VALUES(onu_bindepon) , " .
				"epon_id = VALUES(epon_id) , " .
				"onu_index = VALUES(onu_index) , " .
				"onu_name = VALUES(onu_name) , " .
				"onu_descr = VALUES(onu_descr) , " .
				"onu_adminstatus = VALUES(onu_adminstatus) , " .
				"onu_operstatus = VALUES(onu_operstatus) , " .
				"onu_txpower = VALUES(onu_txpower) , " .
				//"onu_rxpower_change = ABS(ABS(VALUES(onu_rxpower)) - ABS(onu_rxpower)), " .
				//"onu_rxpower_change = IF (onu_rxpower_cnt > 10 ,IF ((ABS((VALUES(onu_rxpower)/onu_rxpower_average) - 1)*100) > 10, onu_rxpower_change+1,(IF (onu_rxpower_change < 1, 0 , onu_rxpower_change-1))),0), " .
				"onu_rxpower = IF (ABS(VALUES(onu_rxpower)) < 500,VALUES(onu_rxpower),onu_rxpower) , " .
				"onu_rxpower_min_date = IF (VALUES(onu_rxpower) > onu_rxpower_min , VALUES(onu_scan_date),onu_rxpower_min_date  ), " .
				"onu_rxpower_min = GREATEST(onu_rxpower_min,VALUES(onu_rxpower)) , " .
				"onu_rxpower_max_date = IF (ABS(VALUES(onu_rxpower)) > ABS(onu_rxpower_max) , VALUES(onu_scan_date),onu_rxpower_max_date  ), " .
				"onu_rxpower_max = GREATEST(ABS(onu_rxpower_max),ABS(VALUES(onu_rxpower))) * -1 , " .
				"onu_rxpower_average = IF (onu_rxpower_cnt > 10,IF (ABS(VALUES(onu_rxpower)) > 0,IF (ABS(VALUES(onu_rxpower)) < 500,((onu_rxpower_average*19 + VALUES(onu_rxpower))/(20)),onu_rxpower_average),onu_rxpower_average), onu_rxpower), " .
				"onu_rxpower_change = ABS(ABS(VALUES(onu_rxpower)) - ABS(onu_rxpower_average)), " .
				"onu_rxpower_cnt = onu_rxpower_cnt + 1, " .
				"onu_volt = VALUES(onu_volt) , " .
				"onu_temp = VALUES(onu_temp) , " .
				"onu_distance = VALUES(onu_distance) , " .
				"onu_alivetime = VALUES(onu_alivetime) , " .
				"onu_dereg_status = VALUES(onu_dereg_status) , " .
				"onu_aclist = VALUES(onu_aclist) , " .
				"onu_online = VALUES(onu_online) , " .
				"onu_scan_date = VALUES(onu_scan_date);";
				
         db_execute($insert_string);
		 //db_execute("bdcom_err_on=" . $insert_off_string);

 } 

function bdcom_db_store_device_onu_off_results(&$device, $onu_array, $scan_date) {
	global $debug;
	$first_row=0;
    /* output details to database */

             $insert_off_string = "INSERT INTO plugin_bdcom_onu " .
                 "(device_id,
					onu_sequence,
					onu_macaddr,
					onu_vendor,
					onu_version,
					onu_bindepon,
					epon_id,
					onu_index,
					onu_name,
					onu_descr,
					onu_agrm_id,
					onu_adminstatus,
					onu_operstatus,
					onu_dereg_status,
					onu_aclist,
					onu_online,
					onu_scan_date)  VALUES ";
                    
    foreach($onu_array as $onu) {
        if (($onu["ifOperState"] == 2) and ($onu["distance"] == 0)) {  //update only DOWN onu
			if ($first_row == 1) {
				$insert_off_string .= ", ";
			}else{
				$first_row=1;
			}
			$insert_off_string .= "('" .
					$device["device_id"] . "','" .
					$onu["sequence"] . "','" .
					$onu["mac"] . "','" .
					$onu["vendor"] . "','" .
					$onu["version"] . "','" .
					$onu["bindepon"] . "','" .
					"0" . "','" .				//epon_id
					$onu["ifIndex"] . "','" .
					$onu["name"] . "','" .
					$onu["descr"] . "','" .
					"0" . "','" .				//onu_agrement
					$onu["ifAdminState"] . "','" .
					$onu["ifOperState"] . "','" .
					$onu["onuDeregDescr"] . "','" . 
					"0" . "','" .				//onu_aclist
					"0" . "','" .				//onu_online
					$scan_date . "')";

	 
				// bdcom_debug("SQL: " . $insert_string);
				
		}		
				
				
    }
         $insert_off_string .= " ON DUPLICATE KEY UPDATE " .
				"onu_sequence = VALUES(onu_sequence) , " .
				"onu_macaddr = VALUES(onu_macaddr) , " .
				"onu_vendor = VALUES(onu_vendor) , " .
				"onu_version = VALUES(onu_version) , " .
				"onu_bindepon = VALUES(onu_bindepon) , " .
				"epon_id = VALUES(epon_id) , " .
				"onu_index = VALUES(onu_index) , " .
				"onu_name = VALUES(onu_name) , " .
				"onu_descr = VALUES(onu_descr) , " .
				"onu_adminstatus = VALUES(onu_adminstatus) , " .
				"onu_operstatus = VALUES(onu_operstatus) , " .
				"onu_dereg_status = VALUES(onu_dereg_status) , " .
				"onu_aclist = VALUES(onu_aclist) , " .
				"onu_online = VALUES(onu_online) , " .
				"onu_scan_date = VALUES(onu_scan_date);";
				
    if ($first_row == 1) {  // есть хоть одна запись
		db_execute($insert_off_string);
		//db_execute("bdcom_err_off=" . $insert_off_string);
	}

 } 

  function bdcom_snmp_escape_string($string) {
	global $config;

	if (! defined("SNMP_ESCAPE_CHARACTER")) {
		if ($config["cacti_server_os"] == "win32") {
			define("SNMP_ESCAPE_CHARACTER", "\"");
		}else{
			define("SNMP_ESCAPE_CHARACTER", "'");
		}
	}

	if (substr_count($string, SNMP_ESCAPE_CHARACTER)) {
		$string = substr_replace(SNMP_ESCAPE_CHARACTER, "\\" . SNMP_ESCAPE_CHARACTER, $string);
	}

	return SNMP_ESCAPE_CHARACTER . $string . SNMP_ESCAPE_CHARACTER;
}

  function bdcom_snmp_set_method($version = 1) {
 	if ((function_exists("snmpgset")) && ($version == 1)) {
 		return SNMP_METHOD_PHP_SET;
 	}else if ((function_exists("snmp2_set")) && ($version == 2) && (PHP_VERSION_ID < 50417)) {
 		return SNMP_METHOD_PHP_SET; //not working on php 5.4

 	}else if ((function_exists("snmp3_set")) && ($version == 3)) {
 		return SNMP_METHOD_PHP_SET;
 	}else if ((($version == 2) || ($version == 3)) && (file_exists(read_config_option("bdcom_path_snmpset")))) {
 		return SNMP_METHOD_BINARY_SET;
 	}else if (function_exists("snmpset")) {
 		/* last resort (hopefully it isn't a 64-bit result) */
 		return SNMP_METHOD_PHP_SET;
 	}else if (file_exists(read_config_option("bdcom_path_snmpset"))) {
 		return SNMP_METHOD_BINARY_SET;
 	}else{
 		/* looks like snmp is broken */
 		return SNMP_METHOD_BINARY_SET;
 	}
 }
 
 
 function bdcom_snmp_set($hostname, $community, $oid, $val_type, $value, $version, $username, $password, $auth_proto, $priv_pass, $priv_proto, $context, $port = 161, $timeout = 500, $retries = 0, $environ = SNMP_POLLER) {
 	global $config;
 
 	/* determine default retries */
 	if (($retries == 0) || (!is_numeric($retries))) {
 		$retries = read_config_option("snmp_retries");
 		if ($retries == "") $retries = 3;
 	}
 
 	/* do not attempt to poll invalid combinations */
 	if (($version == 0) || (($community == "") && ($version != 3))) {
 		return "U";
 	}
 
 	if (bdcom_snmp_set_method($version) == SNMP_METHOD_PHP_SET) {
 		/* make sure snmp* is verbose so we can see what types of data
 		we are getting back */
 		snmp_set_quick_print(0);
 
 		if ($version == "1") {
 			$snmp_value = @snmpset("$hostname:$port", "$community", "$oid", "$val_type", "$value", ($timeout * 1000), $retries);
 		}elseif ($version == "2") {
 			$snmp_value = snmp2_set("$hostname:$port", "$community", "$oid", "$val_type", "$value", ($timeout * 1000), $retries);
 		}else{
 			if ($priv_proto == "[None]") {
 				$proto = "authNoPriv";
 				$priv_proto = "";
 			}else{
 				$proto = "authPriv";
 			}
 			$snmp_value = @snmp3_set("$hostname:$port", "$username", $proto, $auth_proto, "$password", $priv_proto, "$priv_pass", "$oid",  $val_type, $value, ($timeout * 1000), $retries);
 		}
 	}else {
 		/* ucd/net snmp want the timeout in seconds */
 		$timeout = ceil($timeout / 1000);
 
 		if ($version == "1") {
 			$snmp_auth = (read_config_option("snmp_version") == "ucd-snmp") ? bdcom_snmp_escape_string($community): "-c " . bdcom_snmp_escape_string($community); /* v1/v2 - community string */			
 		}elseif ($version == "2") {
 			$snmp_auth = (read_config_option("snmp_version") == "ucd-snmp") ? bdcom_snmp_escape_string($community) : "-c " . bdcom_snmp_escape_string($community); /* v1/v2 - community string */			
 			$version = "2c"; /* ucd/net snmp prefers this over '2' */
 		}elseif ($version == "3") {
 			if ($priv_proto == "[None]") {
 				$proto = "authNoPriv";
 				$priv_proto = "";
 			}else{
 				$proto = "authPriv";
 			}
 
 			if (strlen($priv_pass)) {
 				$priv_pass = "-X " . bdcom_snmp_escape_string($priv_pass) . " -x " . bdcom_snmp_escape_string($priv_proto);
 			}else{
 				$priv_pass = "";
 			}
 
 			if (strlen($context)) {
 				$context = "-n " . bdcom_snmp_escape_string($context);
 			}else{
 				$context = "";
 			}
 
 			$snmp_auth = trim("-u " . bdcom_snmp_escape_string($username) .
 				" -l " . bdcom_snmp_escape_string($proto) .
 				" -a " . bdcom_snmp_escape_string($auth_proto) .
 				" -A " . bdcom_snmp_escape_string($password) .
 				" "    . $priv_pass .
 				" "    . $context); /* v3 - username/password */
 		}			
 
 		/* no valid snmp version has been set, get out */
 		if (empty($snmp_auth)) { return; }
 
 		if (read_config_option("snmp_version") == "ucd-snmp") {
 			exec(read_config_option("bdcom_path_snmpset") . " -O vt -v$version -t $timeout -r $retries $hostname:$port $snmp_auth $oid $val_type $value", $snmp_value);
 		}else {
 			exec(read_config_option("bdcom_path_snmpset") . " -O fntev $snmp_auth -v $version -t $timeout -r $retries $hostname:$port $oid $val_type $value", $snmp_value);
 		}
 	}
 
 	if (isset($snmp_value)) {
 		/* fix for multi-line snmp output */
 		if (is_array($snmp_value)) {
 			$snmp_value = implode(" ", $snmp_value);
 		}
 	}
 
 	/* strip out non-snmp data */
 	$snmp_value = format_snmp_string($snmp_value, true);
 
 	return $snmp_value;
 }
 
 
  function bdcom_increment_unsaved_count($device_id, $count_opertions=1) {
 	
	$max_autosave_count = 10;
 	if ($max_autosave_count > 0){
 		$unsaved_operations = db_fetch_cell ("SELECT count_unsaved_actions FROM plugin_bdcom_devices WHERE device_id=" . $device_id . ";");
 	  	if (($unsaved_operations + $count_opertions) >= $max_autosave_count) {
 	  		db_store_bdcom_log("Выполняеться автосохранение текущей кофигурации", "device", $device_id, "auto_save",$device_id, "OK","OK", "OK", "OK");
 	      bdcom_save_config_main($device_id);
 	  	} else {
 	  		db_execute("UPDATE `imb_devices` SET count_unsaved_actions=count_unsaved_actions + " . $count_opertions . " where device_id=" . $device_id );
 	  	}
   }
 }
 
 
 function bdcom_set_and_check($device, $oid, $val_type, $value, $type_change, $message, $need_check = true, $cellpading = true, $banned = false){
 $rezult = array();
 $rezult["step_rez"] = "Error";
 $rezult["check_rez"] = "Error";
 $rezult["rezult_final"] = "Error";
 
 
 			$retries=0;
 			$snmp_timeout = $device["snmp_timeout"];
 			
 			$rezult["step_data"] = bdcom_snmp_set($device["hostname"], $device["snmp_set_community"], $oid, $val_type, $value, $device["snmp_set_version"],$device["snmp_set_username"],$device["snmp_set_password"],$device["snmp_set_auth_protocol"], $device["snmp_set_priv_passphrase"], $device["snmp_set_priv_protocol"],  $device["snmp_get_context"],$device["snmp_port"], $snmp_timeout, $retries);
 			switch ($type_change){
 				case 'del_onu':
 					$rezult["step_rez"] = (((strtolower(str_replace(":", "",$rezult["step_data"] )) == strtolower($value)) || ($rezult["step_data"] == '1'))? "OK" : "Error");
 					break;
 				case 'port_state':
 					$rezult["step_rez"] =  ((($rezult["step_data"] == format_snmp_string($value, true)) || ($rezult["step_data"] == '1')) ? "OK" : "Error");
 					break;
 				case 'port_name':
 					$rezult["step_rez"] = ((($rezult["step_data"] == format_snmp_string($value, true)) || ($rezult["step_data"] == '1')) ? "OK" : "Error");
 					break;
 				case 'onu_pvid':
 					$rezult["step_rez"] = ((($rezult["step_data"] == format_snmp_string($value, true)) || ($rezult["step_data"] == '1')) ? "OK" : "Error");
 					break;
 				case 'onu_update_diid':
					$rezult["step_rez"] = (((bdcom_hex_trim($rezult["step_data"]) == format_snmp_string($value, true)) || ($rezult["step_data"] == '1')) ? "OK" : "Error");
 					break;
 				case 'onu_update_name':
 					$rezult["step_rez"] = ((($rezult["step_data"] == format_snmp_string($value, true)) || ($rezult["step_data"] == '1')) ? "OK" : "Error");
 					break;					
 				case 'onu_update':
 					$rezult["step_rez"] = ((($rezult["step_data"] == format_snmp_string($value, true)) || ($rezult["step_data"] == '1')) ? "OK" : "Error");
 					break;	
 				case 'onu_commit':
 					$rezult["step_rez"] = ((($rezult["step_data"] == format_snmp_string($value, true)) || ($rezult["step_data"] == '1')) ? "OK" : "Error");
 					break;
 				case 'onu_reboot':
 					$rezult["step_rez"] = ($rezult["step_data"] == format_snmp_string($value, true) ? "OK" : "Error");
 					break;						
					
 			}
 			
 			if ($need_check == true)  {
 				if ($type_change != 'onu_update_diid') {
					$rezult["check_data"] = bdcom_snmp_get($device["hostname"], $device["snmp_get_community"],$oid, $device["snmp_get_version"],$device["snmp_get_username"],$device["snmp_get_password"],$device["snmp_get_auth_protocol"], $device["snmp_get_priv_passphrase"], $device["snmp_get_priv_protocol"],  $device["snmp_get_context"],$device["snmp_port"], $device["snmp_timeout"], $device["snmp_retries"], SNMP_WEBUI);
				}else{
					$rezult["check_data"] = bdcom_snmp_get_hex($device["hostname"], $device["snmp_get_community"],$oid, $device["snmp_get_version"],$device["snmp_get_username"],$device["snmp_get_password"],$device["snmp_get_auth_protocol"], $device["snmp_get_priv_passphrase"], $device["snmp_get_priv_protocol"],  $device["snmp_get_context"],$device["snmp_port"], $device["snmp_timeout"], $device["snmp_retries"], SNMP_WEBUI);				
				}
				
				switch ($type_change){
 					case 'del_onu':
 						$rezult["check_rez"] = ((($rezult["check_data"] == 'U') || (substr_count($rezult["check_data"], "No Such Instance currently")) || ($rezult["check_data"] == '')) ? "OK" : "Error");
 						break;
 					case 'port_state':
 						$rezult["check_data"]=str_replace("down(", "", str_replace("up(", "", str_replace(")", "", $rezult["check_data"])));
						$rezult["check_rez"] = (($rezult["check_data"] == format_snmp_string($value, true)) ? "OK" : "Error");					
 						break;	
 					case 'port_name':
 						$rezult["check_rez"] = (($rezult["check_data"] == format_snmp_string($value, true)) ? "OK" : "Error");
 						break;						
 					case 'onu_pvid':
 						$rezult["check_rez"] = (($rezult["check_data"] == format_snmp_string($value, true)) ? "OK" : "Error");
 						break;
					case 'onu_update_diid':
						$rezult["check_rez"] = ((bdcom_hex_trim($rezult["check_data"]) == bdcom_hex_trim($value)) ? "OK" : "Error");
 						$rezult["check_data"] = bdcom_hex_trim($rezult["check_data"]);
 						break;
					case 'onu_update_name':
 						$rezult["check_rez"] = (($rezult["check_data"] == format_snmp_string($value, true)) ? "OK" : "Error");
 						break;							
					case 'onu_update':
 						$rezult["check_rez"] = ((($rezult["check_data"] == format_snmp_string($value, true)) || ($rezult["check_data"] == '0')) ? "OK" : "Error");
 						break;						
					case 'onu_commit':
 						$rezult["check_rez"] = ((($rezult["check_data"] == format_snmp_string($value, true)) || ($rezult["check_data"] == '0')) ? "OK" : "Error");
 						break;							
					case 'onu_reboot':
 						$rezult["check_rez"] = ((($rezult["check_data"] == format_snmp_string($value, true)) || ($rezult["check_data"] == '1')) ? "OK" : "Error");
 						break;
 				}
 				if (($rezult["step_rez"] == "OK") && ($rezult["check_rez"] == "OK") ) {
 					$rezult["rezult_final"] = "OK";
 				}			
			}
 		
 		if (isset($device["dev_name"])) {
 			$dev_name = $device["dev_name"];
 		}else{
 			$dev_name = $device["description"];
 		}
 		$rezult["mes_id"] = bdcom_raise_message3(array("device_descr" => $dev_name, "type" => "action_check", "object"=>$type_change,"cellpading" => $cellpading, "message" => $message, "step_data" => $rezult["step_data"], "step_rezult" => $rezult["step_rez"], "check_data" => $rezult["check_data"], "check_rezult" => $rezult["check_rez"]));     
 
 		//db_store_bdcom_log($message, "ipmac", $macip_id, "change",$device_id, imb_check_mes_create_ipmac_s1_check($step1,$mac_adrress), $step1, imb_check_mes_create_ipmac_s1($check_step1, $mac_adrress ), $check_step1);
 	return $rezult;
 }
 
 
 
 

 
function bdcom_db_store_vlans_results(&$device, $vlans_array, $scan_date) {
     global $debug;
    $first_row=0;
 
     //$insert_string = "delete from imb_temp_macip where device_id='" .  $device["device_id"] . "'";
     //db_execute($insert_string);
 
	
	db_execute("UPDATE plugin_bdcom_vlans SET vlans_active=0 WHERE device_id=" . $device["device_id"] . " ;");
	
	$insert_string="INSERT INTO plugin_bdcom_vlans (device_id,vlan_name,vlan_id,members_ports, " .
		"uttagget_ports,tagget_ports,forbidden_ports,vlans_scan_date,vlans_active) VALUES "; 
	foreach($vlans_array as $vl_id => $vlan) {
		 if ($first_row == 1) {
		  $insert_string .= ", ";
		 }else{
			  $first_row=1;
		 }
		 $insert_string .= "('" .
				$device["device_id"] . "','" .
				$vlan["name"] . "','" .
				$vlan["id"] . "','" .
				$vlan["m_p"] . "','" .
				$vlan["u_p"] . "','" .                
				$vlan["t_p"] . "','" .                                
				$vlan["f_p"] . "','" .                                
				$scan_date . "','1')";
 
			// bdcom_debug("SQL: " . $insert_string);
	}			
		
	$insert_string .= " ON DUPLICATE KEY UPDATE  vlan_name=VALUES(vlan_name),members_ports=VALUES(members_ports),uttagget_ports=VALUES(uttagget_ports),tagget_ports=VALUES(tagget_ports),forbidden_ports=VALUES(forbidden_ports);";
	db_execute($insert_string);			
		
	db_execute("DELETE FROM plugin_bdcom_vlans WHERE device_id=" . $device["device_id"] . " and vlans_active=0;");
 		
 }



 
 function db_store_bdcom_log($log_poller,$log_device_id,$log_object,$log_object_id,$log_old_value,$log_new_value,$log_message,$log_rezult_short=0,$log_rezult=0,$log_check_rezult_short=0,$log_check_rezult=0) {
 //если поллер
 if (!(isset($_SESSION["sess_user_id"]))) {
	$_SESSION["sess_user_id"] = 0;
 }
 
 if(($log_object_id === NULL) or (is_null($log_object_id))) {
	$log_object_id = 0;
 }
 
 $user_full_name = db_fetch_cell("SELECT full_name FROM user_auth WHERE id='" . $_SESSION["sess_user_id"] . "';");
 
 $insert_string="INSERT INTO plugin_bdcom_logs (log_user_id,log_poller,log_device_id,log_object,log_object_id,log_old_value,log_new_value,log_message,log_rezult_short,log_rezult,log_check_rezult_short,log_check_rezult) " . 
 "VALUES ('" . $_SESSION["sess_user_id"] . "', '" . $log_poller . "', '" . $log_device_id  . "', '" . $log_object  . "', '" . $log_object_id . "', '" . $log_old_value . "', '" . $log_new_value . "', '" . $log_message . "', '" . $log_rezult_short . "', '" . $log_rezult . "', '" . $log_check_rezult_short . "', '" . $log_check_rezult . "')";
 
 db_execute($insert_string);
 
 }
 
 
 
 
 function run_poller_bdcom($device_id) {
 global  $config;
 
 $exit_bdcom = FALSE;
 $current=0;
 
     $command_string = read_config_option("path_php_binary"); 
     $extra_args = " -q " . $config["base_path"] . "/plugins/bdcom/poller_bdcom.php -id=" . $device_id . " -f";
     exec_background($command_string, $extra_args);
     sleep(2);
     /* wait for last process to exit */
         $processes_running = db_fetch_cell("SELECT count(*) FROM plugin_bdcom_processes WHERE device_id = '" . $device_id . "'");
         while (($processes_running > 0) && (!$exit_bdcom)) {
             $processes_running = db_fetch_cell("SELECT count(*) FROM plugin_bdcom_processes WHERE device_id = '" . $device_id . "'");
 
             /* wait the correct number of seconds for proccesses prior to
                attempting to update records */
             sleep(2);
 
             /* take time to check for an exit condition */
             list($micro,$seconds) = explode(" ", microtime());
             $current = $seconds + $micro;
 
             /* exit if we've run too long */
  //           if (($current - $start) > $max_run_duration) {
  //              $exit_bdcom = TRUE;
  //               cacti_log("ERROR: BDCOM timed out during main script processing.\n");
  //               break;
  //           }
 
             bdcom_debug("Waiting on " . $processes_running . " to complete prior to exiting.");
             //Print("Waiting on " . $processes_running . " to complete prior to exiting.");
         }
         
 }

 function bdcom_display_output_messages() {
 global $config, $colors;
 	if (isset($_SESSION["bdcom_output_messages"])) {
 		//$error_message = is_error_message();
 		$i = 0;
 		if (is_array($_SESSION["bdcom_output_messages"])) {
         html_start_box("<strong>Результаты выполнения</strong>", "98%", "009F67" , "5", "center", ""); 
 			    $nav = "<tr bgcolor='#" . "009F67" . "'>
 					<td colspan='5'>
 	                <table width='100%' cellspacing='0' cellpadding='0' border='0'>
 	                    
 	                </table>
 					</td>
 					</tr>\n";
 			print $nav;
 			html_header(array("Устройство","","Действие",   "Результат", "Проверка"));
 			
 			foreach ($_SESSION["bdcom_output_messages"] as $current_message) {
 				//eval ('$message = "' . $current_message["message"] . '";');
				form_alternate_row('line' . $i, true);$i++;
 					?>
 					<td><?php print $current_message["device_descr"];?></td>
 					<?php 
 					if ($current_message["type"] != 'title') {
 						if ($current_message["cellpading"] == "true")  {
 							print "<td width=2%></td>";
 							?><td COLSPAN="1"><?php print $current_message["message"];?></td><?php
 						}else {
 							?><td COLSPAN="2"><?php print $current_message["message"];?></td><?php
 						}
 					}
 					
 					
 				switch ($current_message["type"]) {
 					case 'title' :
 						?>
 							<td COLSPAN="4"><?php print $current_message["message"];?></td>
 						<?php	
 						break;
 					case 'title_count' :
 						?>
 							<td COLSPAN="2" ALIGN="CENTER" BGCOLOR="#<?php print bdcom_covert_rezult_2_color($current_message["count_rez"]) ?>"><?php print "Выполнено " . ((isset($current_message["count_done"]) ) ? $current_message["count_done"] : "-") . " из " . ((isset($current_message["count_all"]) ) ? $current_message["count_all"] : "-");?></td>
 						<?php	
 						break;
 					case 'action_check':
 						?>
 							<td ALIGN="CENTER" BGCOLOR="#<?php print bdcom_covert_rezult_2_color($current_message["step_rezult"] ) ?>"><?php print $current_message["step_rezult"] . " [" . $current_message["step_data"] . "]";?></td>
 							<td ALIGN="CENTER" BGCOLOR="#<?php print bdcom_covert_rezult_2_color($current_message["check_rezult"]) ?>"><?php print $current_message["check_rezult"] . " [" . $current_message["check_data"] . "]";?></td>							
 						<?php					
 						break;
 					case 'action_check2':
 						?>
 							<td  COLSPAN="2" ALIGN="CENTER" BGCOLOR="#<?php print bdcom_covert_rezult_2_color($current_message["step_rezult"] ) ?>"><?php print $current_message["step_rezult"] . " [" . $current_message["step_data"] . "]";?></td>
 						<?php					
 						break;
 					case 'update_db':
 						?>
 							<td ALIGN="CENTER" BGCOLOR="#<?php print bdcom_covert_rezult_2_color($current_message["step_rezult"] ) ?>"><?php print $current_message["step_rezult"] ;?></td>
 							<td ALIGN="CENTER" BGCOLOR="#<?php print bdcom_covert_rezult_2_color($current_message["step_rezult"]) ?>"><?php print $current_message["step_data"];?></td>
 						<?php					
 						break;
 				}		
 			}
 		print "</table><br>";
 		html_end_box(false);
 		print "<br>";
 		}
 	}
 kill_session_var("bdcom_output_messages");
 }


 function bdcom_covert_rezult_2_color ($str_rezult) {
	 $rezult="";
	 
	 if ( $str_rezult == "OK" ) {
		$rezult="00BF47";
	 } elseif ($str_rezult == "OK") {
		$rezult="";
	 } else {
		$rezult="ff7d00";
	 }
	 return $rezult;
 };

 function bdcom_draw_actions_dropdown($actions_array, $actions_type, $def_choice="1") {
     global $config;
     ?>
     <table align='center' width='98%'>
         <tr>
             <td width='1' valign='top'>
                 <img src='<?php echo $config['url_path']; ?>images/arrow.gif' alt='' align='absmiddle'>&nbsp;
             </td>
             <td align='right'>
                 Выберите действие:
                 <?php form_dropdown("drp_action",$actions_array,"","",$def_choice,"","");?>
             </td>
             <td width='1' align='right'>
				 <input type='submit'  style="width: 85px"  name='go' value='Далее >>'>
             </td>
         </tr>
     </table>
     <input type='hidden' name='action' value='actions_<?php echo $actions_type; ?>'>
     <?php
 }
 
function bdcom_convert_port_type_2_html($port_type) {

 switch($port_type) {
 	case '1':
 		$imp_convert_port_type_2str_full = "<span style='color: #198e32'>SFP(" . $port_type . ")</span>";
 		break;
 	case '2':
 		$imp_convert_port_type_2str_full = "<span style='color: #750F7D'>ETH(" . $port_type . ")</span>";
 		break;
 	case '4':
 		$imp_convert_port_type_2str_full = "<span style='color: #198e32'>COMBO(" . $port_type . ")</span>";
 		break;
 	default:
 		$imp_convert_port_type_2str_full = "<span style='color: #750F7D'>unk(" . $port_type .")</span>";
 		break;		
 }
 return $imp_convert_port_type_2str_full;
 }









 //bdcom_raise_message3(array(type => "title",leftmargin => '0', message => 'Старт'));     
 //bdcom_raise_message3(array(device_descr => "Свича", type => "action_check",leftmargin => "10", message => "шаг 1", step_data => "10", step_rezult => "OK", check_data => "20", check_rezult => "Error"));     
 
 function bdcom_raise_message3($args) {
 	
 	if (count($args) > 0){
 		if (!isset($args["mes_id"])) {
 			if (isset($_SESSION["bdcom_output_messages"])) {
 		        $mes_id = count($_SESSION["bdcom_output_messages"]) + 1;
 		    }else{
 			$mes_id = 1;
 			}	
 		}else{
 			$mes_id = $args["mes_id"];
 		}
 		foreach($args as $arg => $value) {
 			$_SESSION["bdcom_output_messages"][$mes_id][$arg] = $value;
 		}
 	}
 return $mes_id;
 
 // $user_full_name = db_fetch_cell("SELECT full_name FROM user_auth WHERE id='" . $_SESSION["sess_user_id"] . "';");
 // $insert_string="INSERT INTO plugin_bdcom_logs (log_user_id,log_user_full_name,log_date,log_object,log_object_id, log_operation,log_device_id,log_message,log_rezult_short,log_rezult,log_check_rezult_short,log_check_rezult,log_saved) " . 
 // "VALUES ('" . $_SESSION["sess_user_id"] . "', '" . $user_full_name . "', '" .  date("Y-m-d H:i:s") . "', '" . $log_object . "', '" . $log_object_id . "', '" . $log_operation . "', '" . $log_device_id . "', '" . $log_message . "', '" . $log_rezult_short . "', '" . $log_rezult . "', '" . $log_check_rezult_short . "', '" . $log_rezult_check . "', 0);";
 // db_execute($insert_string);
 }
 

 function print_last_poll_stat() {
 	$last_poll_date = db_fetch_cell ("SELECT `value` FROM settings WHERE `name` = 'bdcom_scan_date';");
     print ("Последние опрос был в:" . bdcom_format_datetime($last_poll_date));
 } 

 function bdcom_add_row_any ($sql) {
 	 $rezult=db_fetch_assoc($sql);
	 
	 if (is_array($rezult)) {
		array_unshift($rezult, array("id"=>"0", "name"=>"Any Device"));
	 }
	  return $rezult;
 } 

function bdcom_sendemail($to, $from, $subject, $message, $email_format) {
 	
	if (read_config_option("camm_dependencies")) {
 		bdcom_debug("  Sending Alert email to '" . $to . "'\n");
		if ($email_format == 1) {
			send_mail($to, $from, $subject, $message);
		}else{
			$headers = "MIME-Version: 1.0\r\n";
			$headers .= "Content-Type: text/plain; charset=utf-8\r\n";
			send_mail($to, $from, $subject, "<html><body>$message</body></html>", $headers); 
		}
 	} else {
 		bdcom_debug("  Error: Could not send alert, you are missing the Settings plugin\n");
 	}
 }

function bdcom_tabs() {
	global $config;

	/* present a tabbed interface */
	$tabs_bdcom = array(

		'devices'    	=> __('Devices'),
		'epons'    		=> __('Epons'),
		'ports'     	=> __('Ports'),
		'onus'     		=> __('ONU'),		
		'netadd'    	=> __('Auto Create'),
		//'recentmacs' 	=> __('Scans'),
		'info'     		=> __('Search')
	);

	/* set the default tab */
	$current_tab = get_request_var('report');

	/* draw the tabs */
	print "<div class='tabs'><nav><ul>\n";

	if (sizeof($tabs_bdcom)) {
		foreach ($tabs_bdcom as $tab_short_name => $tab_name) {
			print '<li><a class="tab' . (($tab_short_name == $current_tab) ? ' selected"' : '"') . " href='" . htmlspecialchars($config['url_path'] .
				'plugins/bdcom/bdcom_view_' . $tab_short_name . '.php?' .
				'report=' . $tab_short_name) .
				"'>$tab_name</a></li>\n";
		}
	}
	print "<td width='100%' valign='top'>" . bdcom_display_output_messages();
	print "</ul></nav></div>\n";
}

function bdcom_redirect() {
	/* set the default tab */
    get_filter_request_var('report', FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => '/^([a-zA-Z]+)$/')));

	load_current_session_value('report', 'sess_bdcom_view_report', 'devices');
	$current_tab = get_nfilter_request_var('report');

	$current_page = str_replace('bdcom_', '', str_replace('view_', '', str_replace('.php', '', basename($_SERVER['PHP_SELF']))));
	$current_dir  = dirname($_SERVER['PHP_SELF']);

	if ($current_page != $current_tab) {
		header('Location: ' . $current_dir . '/bdcom_view_' . $current_tab . '.php');
	}
}

function bdcom_format_port_status($port) {
 	
	switch ($port["port_status"]) {
	 case "1":
		$port_status = 'UP';
		$port_imp_active_color = '00BD27';
		if ($port["type_revision"] == "1") {  //A1, B1
			switch ($port["port_speed"]) {
				 case "0":
					 break; 				
				 case "1":
					 $port_status = $port_status . ", Auto";
					 break;
				 case "2":
					 $port_status = $port_status . ", 10H";
					 break;                        
				 case "3":
					 $port_status = $port_status . ", 10F";
					 break;
				 case "4":
					 $port_status = $port_status . ", 100H";
					 break;
				 case "5":
					 $port_status = $port_status . ", 100F";
					 break;
				 case "7":
					 $port_status = $port_status . ", 1G";
					 break;   						 
				 default:
					 $port_status = $port_status . "," . $port["port_speed"];
					 break;
			};
		}else{  //C1  INTEGER  { other ( 1 ) , nway-enabled ( 2 ) , nway-disabled-10Mbps-Half ( 3 ) , nway-disabled-10Mbps-Full ( 4 ) , nway-disabled-100Mbps-Half ( 5 ) , nway-disabled-100Mbps-Full ( 6 ) , nway-disabled-1Gigabps-Half ( 7 ) , nway-disabled-1Gigabps-Full ( 8 ) , nway-disabled-1Gigabps-Full-master ( 9 ) , nway-disabled-1Gigabps-Full-slave ( 10 ) } 
			switch ($port["port_speed"]) {
				 case "0":
					 break; 				
				 case "1":
					 $port_status = $port_status . ", other";
					 break;
				 case "2":
					 $port_status = $port_status . ", enabled";
					 break;                        
				 case "3":
					 $port_status = $port_status . ", 10H";
					 break;
				 case "4":
					 $port_status = $port_status . ", 10F";
					 break;
				 case "5":
					 $port_status = $port_status . ", 100H";
					 break;
				 case "6":
					 $port_status = $port_status . ", 100F";
					 break;
				 case "7":
					 $port_status = $port_status . ", 1Gh";
					 break;
				 case "8":
					 $port_status = $port_status . ", 1Gf";
					 break; 
				 case "9":
					 $port_status = $port_status . ", 1Gf";
					 break;
				 case "10":
					 $port_status = $port_status . ", 1Gf";
					 break;
				 case "14":
					 $port_status = $port_status . ", 10G";
					 break; 					 
				 default:
					 $port_status = $port_status . "," . $port["port_speed"];
					 break;
			};						
		};
		break;
	 case "0":
		 $port_status = 'DOWN';
		 $port_imp_active_color = 'FF0000';
		 break;                        
	 default:
		 $port_status = $port["port_status"];
		 break;
	};
return $port_status;
} 

Function DateTimeDiff ($date_start) {
     // получает количество секунд между двумя датами 
     $timedifference =  strtotime("now") - strtotime($date_start);
 	$days = 0;
 	$hours = 0;
 	$minutes = 0;
 	$seconds = 0;
 	$str_rezult = "";
 	
 	if ($timedifference > 86400) {
 		$days = bcdiv($timedifference,86400);
 		$str_rezult = $days . "дн. ";
 	}
 
 	if ($timedifference > 3600) {
 		$hours = bcdiv(($timedifference - $days*86400),3600);
 		$str_rezult = $str_rezult . $hours . ":";
 	}
 
 	if ($timedifference > 60) {
 		$minutes = bcdiv(($timedifference - $days*86400 - $hours*3600),60);
 		$str_rezult = $str_rezult . sprintf("%02u",$minutes) . ":";
 	}
 	
 	$seconds = ($timedifference - $days*86400 - $hours*3600 - $minutes*60);
 	$str_rezult = $str_rezult . sprintf("%02u",$seconds);
 	
 	return $str_rezult;
 
 }

 
function bdcom_format_datetime($date_src){
	 $today=date('Y-m-d');
	 if ($today == date('Y-m-d',strtotime($date_src))) {
		$date_return = date('H:i:s',strtotime($date_src)) . " ( " .  DateTimeDiff($date_src) . ")";
		} else {
		$date_return = $date_src;
	 }
	 return $date_return;
 }
 
  function translate_mac_address($old_mac_address) {
 	$old_mac_address = str_replace("-", ":", $old_mac_address);
 	
 	//$old_mac_address = str_replace("-", ":", $old_mac_address);
 return $old_mac_address;  
 }

  
 /*translate_ip_address*/
 function translate_ip_address($old_ip_address) {
 
 	//[172.18.1.1  ]  => [172.18.1.1]
 	$old_ip_address = trim($old_ip_address);
 	//[172.18-1-1]  => [172.18.1.1]
 	$old_ip_address = str_replace("-", ".", $old_ip_address);
 	//[172,18,1.1]  => [17218.1.1]
 	$old_ip_address = str_replace(",", ".", $old_ip_address);
 	//[172 18 1-1]  => [172.18.1.1]
 	$old_ip_address = str_replace(" ", ".", $old_ip_address);
 	
 	$new_ip_address = $old_ip_address;
 	//[172.018.001.1]  => [172.18.1.1]
 		$pieces = explode(".", $old_ip_address);
 		$new_pieces = array();
 	if (count($pieces) == 4) {
 		$new_pieces[0] = intval($pieces[0]);
 		$new_pieces[1] = intval($pieces[1]);
 		$new_pieces[2] = intval($pieces[2]);
 		$new_pieces[3] = intval($pieces[3]);
 		$new_ip_address = implode(".", $new_pieces);
 	}
 return $new_ip_address; 
 }
  
function bdcom_mac_16_to_10($mac_address) {
	if (strlen($mac_address) == 0) {
		$rez = "0";
	}else{
		$mac_address = str_replace(".", ':', $mac_address);
		$ar = explode (":",$mac_address);
		if (is_array($ar)){
			$rez = "";
			foreach ($ar as $arp) {
				$rez = $rez . "." . base_convert ( $arp , 16 , 10 );
			}
			$rez = substr($rez, 1);			
		}

	}

	return $rez;
}

 function bdcom_form_alternate_row_color($row_color1, $row_color2, $row_value, $row_id = "", $style = "") {
	if (($row_value % 2) == 1) {
			$class='odd';
			$current_color = $row_color1;
	}else{
		if ($row_color2 == '' || $row_color2 == "E5E5E5") {
			$class = 'even';
		}else{
			$class = 'even-alternate';
		}
		$current_color = $row_color1;
	}

	if (strlen($row_id)) {
		print "<tr class='$class' id='$row_id' " . (strlen($style) ? " style='$style;'" : "") . " >\n";
	}else{
		print "<tr class='$class' " . (strlen($style) ? " style='$style;'" : "") . " >\n";
	}

	return $current_color;
}

function  bdcom_update_auto_create($device_id = 0) {
	
	$create_ips = db_fetch_assoc("select i.net_id, d.hostname, d.description, ep.epon_descr, onu.onu_ipaddr, onu.onu_descr, onu.onu_name from plugin_bdcom_onu onu " .
		" JOIN imb_auto_updated_nets i ON ((inet_aton(`onu_ipaddr`) & `net_mask`) = `net_ipaddr`) " .
		" LEFT JOIN plugin_bdcom_epons  ep  ON ep.epon_index = onu.onu_bindepon and ep.device_id=onu.device_id " .
		" LEFT JOIN plugin_bdcom_devices d ON d.device_id = onu.device_id " .
		" where i.net_type=2 ;");

	if (sizeof($create_ips)) {
		foreach($create_ips as $key => $create_ip) {
		
			$log_message = "BDCOM: Auto create ONU name=[" . $create_ip["onu_name"] . "], IP=[" .  $create_ip["onu_ipaddr"] . "], EPON=[" . $create_ip["epon_descr"] . "], on DEVICE=[" . $create_ip["hostname"] . "]. ";
			
			
			$uid = db_fetch_cell(" SELECT lbv.uid FROM imb_auto_updated_nets i LEFT JOIN lb_staff lbs ON (i.net_ipaddr=lbs.segment) " .
			" LEFT JOIN lb_vgroups_s lbv ON (lbs.vg_id=lbv.vg_id) where i.net_id='" . $create_ip["net_id"] . "';");
			bdcom_debug("BDCOM: Restore balance for net_id=" . $create_ip["net_id"] . ", uid=" . $uid);
			$arrContextOptions=array(
				"ssl"=>array(
					"verify_peer"=>false,
					"verify_peer_name"=>false,
				),
			);
			$rest_balance = file_get_contents('https://iserver.ion63.ru/admin/_cacti/cacti.php?uid=' . $uid . '&t=onu', false, stream_context_create($arrContextOptions));
			bdcom_debug("BDCOM: rezult [" . print_r($rest_balance . "]", true));
			cacti_log($log_message, TRUE);
			db_execute("DELETE FROM `imb_auto_updated_nets` WHERE `net_id`='" . $create_ip["net_id"] . "';");
		}
	}
	
	db_execute("DELETE FROM `imb_auto_updated_nets` WHERE `net_ttl`<>0 and `net_type`='2' and DATE_ADD(imb_auto_updated_nets.net_change_time, INTERVAL  `net_ttl` HOUR) < NOW() ;");
}

function bdcom_create_graphs() {
global $config;


$grs[0]["graph-template-id"]=108;
$grs[0]["snmp-query-id"]=20;
$grs[0]["snmp-query-type-id"]=60;
$grs[0]["snmp-field"]="SegmentIndex";
$grs[1]["graph-template-id"]=32;
$grs[1]["snmp-query-id"]=1;
$grs[1]["snmp-query-type-id"]=21;
$grs[1]["snmp-field"]="ifIndex";
$grs[2]["graph-template-id"]=22;
$grs[2]["snmp-query-id"]=1;
$grs[2]["snmp-query-type-id"]=2;
$grs[2]["snmp-field"]="ifIndex";

$bdcom_hosts = db_fetch_assoc( "SELECT * FROM host where host_template_id=32;");
if (sizeof($bdcom_hosts)) {
foreach($bdcom_hosts as $key => $bdcom_host) {

	foreach($grs as $key => $gr) {
		$ar_graphs = db_fetch_assoc( "SELECT host_id, snmp_query_id, lc.snmp_index FROM host_snmp_cache lc " . 
						" LEFT JOIN (SELECT DISTINCT data_input_data.value, data_local.snmp_index FROM (data_local,data_template_data) LEFT JOIN data_input_data ON (data_template_data.id=data_input_data.data_template_data_id) LEFT JOIN data_input_fields ON (data_input_data.data_input_field_id=data_input_fields.id)  " . 
						" WHERE data_local.id=data_template_data.local_data_id  " . 
						" AND data_input_fields.type_code='output_type'  " . 
						" AND data_input_data.value='" . $gr["snmp-query-type-id"] . "'  " . 
						" AND data_local.host_id=" . $bdcom_host["id"] . ") as t ON (lc.snmp_index=t.snmp_index) " . 
						" WHERE host_id=" . $bdcom_host["id"] . " AND snmp_query_id=" . $gr["snmp-query-id"] . " and t.snmp_index is null and lc.snmp_index <> '' " . 
						" GROUP BY host_id, lc.snmp_query_id, lc.snmp_index	 ;");
			if (sizeof($ar_graphs)) {
				$command_string = read_config_option("path_php_binary");
				foreach($ar_graphs as $key => $new_graph) {
						$extra_args = " -q " . $config["base_path"] . "/cli/add_graphs.php --graph-type=ds  --graph-template-id=" . $gr["graph-template-id"] . " --host-id='" . $new_graph["host_id"] . "' --snmp-query-id=" . $gr["snmp-query-id"] . " --snmp-query-type-id=" . $gr["snmp-query-type-id"] . " --snmp-field=" . $gr["snmp-field"] . " --snmp-value='" . $new_graph["snmp_index"] . "'";
						unset ($out);
						exec($command_string . " " . $extra_args . " &", $out);
						$log_message = "BDCOM: Auto create ONU GRAPHS. Rezult=[" . ($out[0] !== null ? $out[0] : "") . "]. ";
						cacti_log($log_message, TRUE);				
				}	
			}
	}
}	
}	
	
}

function update_onu_power($onu_id){

	
	$onu_row =  db_fetch_row ("SELECT * FROM plugin_bdcom_onu WHERE `onu_id`='" . $onu_id . "';");
	

	if (sizeof($onu_row) > 0) {
		$device =  db_fetch_row ("SELECT * FROM `plugin_bdcom_devices` WHERE device_id='" . $onu_row["device_id"] . "';");		
		
			if (sizeof($device) > 0) {
				$onuRxPower = 	bdcom_xform_standard_indexed_data('.1.3.6.1.4.1.3320.101.10.5.1.5.' . $onu_row["onu_index"] , $device);
				$onuTxPower = 	bdcom_xform_standard_indexed_data('.1.3.6.1.4.1.3320.101.10.5.1.6.' . $onu_row["onu_index"] , $device);
				$onuRxPower = (isset($onuRxPower[$onu_row["onu_index"]]) ? $onuRxPower[$onu_row["onu_index"]] : 0 );
				$onuTxPower = (isset($onuTxPower[$onu_row["onu_index"]]) ? $onuTxPower[$onu_row["onu_index"]] : 0 );	
				$onu_row['onu_rxpower']=$onuRxPower;
				$onu_row['onu_txpower']=$onuTxPower;
				
				db_execute("UPDATE plugin_bdcom_onu SET `onu_txpower`='" . $onuTxPower . "', `onu_rxpower`='" . $onuRxPower . "', `onu_scan_date`='" . date('Y-m-d H:i:s') . "'  WHERE `onu_id`='" . $onu_id . "';");
				
			}
	}
	return $onu_row;
}

function update_onu_firm($onu_id){

	
	$onu_row =  db_fetch_row ("SELECT * FROM plugin_bdcom_onu WHERE `onu_id`='" . $onu_id . "';");
	

	if (sizeof($onu_row) > 0) {
		$device =  db_fetch_row ("SELECT * FROM `plugin_bdcom_devices` WHERE device_id='" . $onu_row["device_id"] . "';");		
		
			if (sizeof($device) > 0) {
				$onu_new_soft_version = 	bdcom_xform_standard_indexed_data('.1.3.6.1.4.1.3320.101.10.1.1.5.' . $onu_row["onu_index"] , $device);
				$onu_new_soft_version = hexToStr(rtrim(str_replace(':', '', format_snmp_string($onu_new_soft_version, true)),0)) ;
				
				db_execute("UPDATE plugin_bdcom_onu SET `onu_soft_version`='" . $onu_new_soft_version . "'  WHERE onu_id='" . $onu_row["onu_id"] . "';");
			}
	}
	
}

function bdcom_color_power_cell($onu){
	

	$str_power=bdcom_color_power($onu);

	$str_power = '<div class="fit_div" id=pw' . $onu["onu_id"] . '>' . $str_power . '</div>';
	//$str_power = $str_power;
	return $str_power;
}

function bdcom_color_power($onu){
	
	
	if ($onu["onu_rxpower"] > -100) {
		$onu_rx_pow = "<span style='background-color: #f7dc6f ;'>" . round($onu["onu_rxpower"]*0.1,1) ."</span>";
	}elseif($onu["onu_rxpower"] > -220){
		$onu_rx_pow = "<span>" . round($onu["onu_rxpower"]*0.1,1) ."</span>";
	}elseif($onu["onu_rxpower"] > -240){
		$onu_rx_pow = "<span style='background-color: #fad7a0;'>" . round($onu["onu_rxpower"]*0.1,1) ."</span>";
	}elseif($onu["onu_rxpower"] > -260){	
		$onu_rx_pow = "<span style='background-color: #e67e22 ;'>" . round($onu["onu_rxpower"]*0.1,1) ."</span>";
	}elseif($onu["onu_rxpower"] > -280){	
		$onu_rx_pow = "<span style='background-color: #a04000;'>" . round($onu["onu_rxpower"]*0.1,1) ."</span>";
	}else{
		$onu_rx_pow = "<span style='background-color:  #FF3399  ;'>" . round($onu["onu_rxpower"]*0.1,1) ."</span>";
	}

	$str_power=round($onu["onu_txpower"]*0.1,1) . "/" . $onu_rx_pow;
	
	if ($onu["onu_operstatus"] == 2) {
		$str_power    ="[" . $str_power . "]";
	}
	
	return $str_power;

}

 function bdcom_array_strip_non_digital($array) {
	$ret_array = array();

	if (sizeof($array) > 0) {
		foreach ($array as $item => $key) {
			$ret_array[$item] = preg_replace("/\D/", "",$key);
		}
	}

	return $ret_array;
}

 function bdcom_array_strip_dereg_descr($array) {
	$ret_array = array();

	if (sizeof($array) > 0) {
		foreach ($array as $item => $key) {
			$item = preg_replace("/.enterprises/", "",$item);
			$item = preg_replace("/.3320.101.11.1.1.11./", "",$item);
			$ret_array[$item] = $key;
		}
	}

	return $ret_array;
}

 function bdcom_fromat_datetime($date_src){
	$today=date('Y-m-d');
	 if ($today == date('Y-m-d',strtotime($date_src))) {
		$date_return = date('H:i',strtotime($date_src)) . " ( " .  DateTimeDiff($date_src) . ")";
	} else {
		$date_return = $date_src;
	 }
	return $date_return;
 }

function bdcom_convert_macip_state_2str($macip_state) {
 	switch($macip_state) {
 	case 1:
       $str_macip_state = "<span style='color: #198e32'>active(1)</span>";
 	  break;
 	case 2:
       $str_macip_state = "<span style='color: #a1a1a1'>notInService(2)</span>";
 	  break;	  
 	case 3:
       $str_macip_state = "<span style='color: #a1a1a1'>notReady(3)</span>";
 	  break;
 	case 4:
       $str_macip_state = "<span style='color: #750F7D'>createAndGo(4)</span>";
 	  break;
 	case 5:
       $str_macip_state = "<span style='color: #a1a1a1'>createAndWait(5)</span>";
 	  break;
 	case 6:
       $str_macip_state = "<span style='color: #750F7D'>destroy(6)</span>";
 	  break;
 	default:
 		$str_macip_state = "<span style='color: #750F7D'>unk(" . $macip_state .")</span>";
 		break;	
 	}
 		return $str_macip_state;
}

function bdcom_convert_macip_action_2str($macip_action, $type_conversion_action) {
 	if ($macip_action == -1) {
 		$str_macip_action = "<span style='color: #a1a1a1'>unUse</span>";
 	}else{
 		switch($type_conversion_action) {
 		case 1:
 				switch($macip_action) {
 					case 1:
 						$str_macip_action = "<span style='color: #750F7D'>inactive(1)</span>";
 						break;
 					case 2:
 						$str_macip_action = "<span style='color: #198e32'>active(2)</span>";
 						break;
 					default:
 						$str_macip_action = "<span style='color: #750F7D'>unk(" . $macip_action .")</span>";
 						break;						
 				}
 		break;
 		case 2:
 				switch($macip_action) {
 					case 0:
 						$str_macip_action = "<span style='color: #750F7D'>inactive(0)</span>";
 						break;
 					case 1:
 						$str_macip_action = "<span style='color: #198e32'>active(1)</span>";
 						break;
 					default:
 						$str_macip_action = "<span style='color: #750F7D'>unk(" . $macip_action .")</span>";
 						break;						
 				}		
 		break;
 		default:
 			$str_macip_action = "<span style='color: #750F7D'>unk(" . $macip_action .")</span>";
 			break;
 		}
 	}
 		return $str_macip_action;
}

function bdcom_convert_macip_mode_2str_full($macip_mode, $device_id) {
 $macip_mode_str = bdcom_convert_macip_mode_2str($macip_mode, $device_id);
 switch($macip_mode_str) {
 	case 'ARP':
 		$imp_convert_macip_mode_2str_full = "<span style='color: #198e32'>ARP(" . $macip_mode . ")</span>";
 		break;
 	case 'ACL':
 		$imp_convert_macip_mode_2str_full = "<span style='color: #750F7D'>ACL(" . $macip_mode . ")</span>";
 		break;
 	case 'unUse':
 		$imp_convert_macip_mode_2str_full = "<span style='color: #a1a1a1'>unUse</span>";
 		break;
 	default:
 		$imp_convert_macip_mode_2str_full = "<span style='color: #750F7D'>unk(" . $macip_mode .")</span>";
 		break;		
 }
 return $imp_convert_macip_mode_2str_full;
} 


function bdcom_convert_macip_mode_2str($macip_mode, $device_id) {
 $type_conversion_mode = db_fetch_cell ("SELECT imb_device_types.type_imb_mode FROM imb_devices " . 
 	" left JOIN imb_device_types ON imb_devices.device_type_id = imb_device_types.device_type_id " .
 	" where device_id=" . $device_id . ";");
 	if ($macip_mode == -1) {
 		$str_macip_mode = "unUse";
 	}else{
 		switch($type_conversion_mode) {
 		case 1:
 				switch($macip_mode) {
 					case 1:
 						$str_macip_mode = "ARP";
 						break;
 					case 2:
 						$str_macip_mode = "ACL";
 						break;
 					default:
 						$str_macip_mode = "unk";
 						break;						
 				}
 		break;
 		case 2:
 				switch($macip_mode) {
 					case 0:
 						$str_macip_mode = "ARP";
 						break;
 					case 1:
 						$str_macip_mode = "ACL";
 						break;
 					default:
 						$str_macip_mode = "unk";
 						break;						
 				}		
 		break;
 		default:
 			$str_macip_mode = "unk";
 			break;
 		}
 	}
 		return $str_macip_mode;
}
 
function bdcom_convert_free_2str($macip_free) {
 	switch($macip_free) {
 	case 0:
       $str_macip_state = "<span style='color: #750F7D'>off(0)</span>";
 	  break;
 	case 1:
       $str_macip_state = "<span style='color: #198e32'>ON(1)</span>";
 	  break;	  
 	default:
 		$str_macip_state = "<span style='color: #750F7D'>unk(" . $macip_state .")</span>";
 		break;	
 	}
 		return $str_macip_state;
}
 
 
 function send_viber_msg($str_msg, $tel = "+79377999153 +79377999152"){
	
	$ar_tel=preg_split("/[\s,]+/",$tel);

	foreach($ar_tel as $key => $t) {
		db_execute("INSERT INTO sms.outbox (SendBefore,SendAfter,DestinationNumber, TextDecoded, CreatorID, Coding, SenderID) VALUES ('22:00:00','9:00:00','" . $t . "' , '" . $str_msg . "' , 'sys_bdcom', 'Default_No_Compression','0');");	
		//bdcom_debug("BDCOM ERROR: SEND MSG =[" . $str_msg . "] and  T=[" . $t . "] and TEL=[" . $tel . "] ");					
	}
	bdcom_debug($str_msg, true);					
	//Yasha
	//db_execute("INSERT INTO sms.outbox (SendBefore,SendAfter,DestinationNumber, TextDecoded, CreatorID, Coding, SenderID) VALUES ('22:00:00','9:00:00','+79372068684' , '" . $str_msg . "' , 'sys_bdcom', 'Default_No_Compression','0');");		
} 

function bdcom_alert_rxpower_change($device){

	
	if (read_config_option("bdcom_enable_msg_rx_change") == "1") {
		
		$ar_onus =  db_fetch_assoc ("SELECT o.*, vg.uid FROM plugin_bdcom_onu o LEFT JOIN lb_vgroups_s vg ON (o.onu_agrm_id = vg.agrm_id and vg.id=1) " . 
		" WHERE onu_us_enduzelid <> 311 and `device_id`='" . $device["device_id"] . "' and onu_online=1 and ((ABS((onu_rxpower/onu_rxpower_average) - 1)*100) > 10) and  (UNIX_TIMESTAMP()-UNIX_TIMESTAMP(`onu_rxpower_alert_date`) > 3600) and onu_rxpower <> 0;");
		

		if (sizeof($ar_onus) > 0) {
			if (sizeof($ar_onus) < 6) {
				sleep (7);
			}
			foreach ($ar_onus as $key => $val) {
				//если онушек меньше 5 - попробуем перепроверить уровень
				if (sizeof($ar_onus) < 6) {
					sleep (1);
					$onu=update_onu_power($val["onu_id"]);
					//db_execute("UPDATE plugin_bdcom_onu SET `onu_txpower`='" . $onuTxPower . "', `onu_rxpower`='" . $onuRxPower . "', `onu_scan_date`='" . date('Y-m-d H:i:s') . "'  WHERE `onu_id`='" . $onu_id . "';");
					cacti_log("WARNING: ONU RX RESTORE! IP=" . $onu["onu_ipaddr"] . " RX=" . round($onu["onu_rxpower"]/10,1) . ". WAS=" . round($val["onu_rxpower"]/10,1));
					$val["onu_rxpower"] = $onu["onu_rxpower"];					
				}
				if ((ABS(($val["onu_rxpower"]/$val["onu_rxpower_average"]) - 1)*100) > 10) {
					$str_sms = "WARNING: ONU RX CHANGE! IP=" . $val["onu_ipaddr"] . " RX=" . round($val["onu_rxpower"]/10,1) . ". AVG=" . round($val["onu_rxpower_average"]/10,1) . "  https://sys.ion63.ru/graph_ion_view.php?action=preview&host_id=-1&snmp_index=&rfilter=" . $val["onu_ipaddr"] ;
					send_viber_msg($str_sms);
					//$results1 = print_r($val, true);
					cacti_log($str_sms);
					//cacti_log("BDCOM ERROR_2 = " . print_r($ar_onus, true), false, "bdcom_er2");
					db_execute("UPDATE plugin_bdcom_onu SET `onu_rxpower_alert_date`=NOW() WHERE `onu_id`='" . $val["onu_id"] . "';");		
				}
			}
		}
	}
	
}


function send_viber(){
	global $config;
	

$config = require($config["base_path"] . "/vb/config.php");
$apiKey = $config['apiKey'];
//$service_url = 'https://chatapi.viber.com/pa/get_user_details';
$send_url = 'https://chatapi.viber.com/pa/send_message';
$test=true;
	
	//echo "01";
	$sql_vbs = "SELECT o.*, v.ID as receiver_id, TextDecoded as TextDecoded2,  " .
	" TIMESTAMPDIFF(MINUTE,o.SendingTimeOut,o.InsertIntoDB) as timeout " .
	" FROM `sms`.`outbox` o " .
	" left join sms.vb_users v on (o.DestinationNumber=v.PhoneNumber) " .
	" where SenderID = 'viber1' and `SendBefore` > CURTIME() and `SendAfter` < CURTIME() and `SendingTimeOut` < NOW();";
	$out_vbs = db_fetch_assoc($sql_vbs);	
	if ((sizeof($out_vbs)>0) && $test) {
	
		foreach($out_vbs as $key => $out_vb) {
			
			$curl = curl_init($send_url);
			$curl_post_data = array(
					'message' 		=> $out_vb["TextDecoded2"],
					'text' 			=> $out_vb["TextDecoded2"],
					'receiver' 		=> $out_vb["receiver_id"],
					'type' 			=> 'text',
					'tracking_data' => 'tracking data',
					'sender.name' 	=> 'ion63.ru',
					'sender.avatar' => 'http://sys.ion63.ru/vb/ion63_logo1.jpg'
			);	

			$data_string = json_encode($curl_post_data); 
			curl_setopt($curl, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				'X-Viber-Auth-Token: ' . $apiKey
				));
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
			$curl_response = curl_exec($curl);
			
			//: string = "{\"status\":0,\"status_message\":\"ok\",\"message_token\":5055004995223964101}"
			if (isJson($curl_response)){
				$js_resp = json_decode($curl_response, true);
				if (isset($js_resp["status"])){
					switch ($js_resp["status"]) {
						case "0":
							$rez = db_execute ("INSERT INTO `sms`.`sentitems` (`CreatorID`,`ID`,`SequencePosition`,`Status`, " .
							"`SendingDateTime`,`SMSCNumber`,`TPMR`,`SenderID`,`Text`,`DestinationNumber`,`Coding`, " .
							" `UDH`,`Class`,`TextDecoded`,`InsertIntoDB`,`RelativeValidity`,`cost`,`vb_mes_token`) " .
							" VALUES('" . $out_vb["CreatorID"] . "','" . $out_vb["ID"] . "',1,'SendingOK', " .
							" NOW(), '',-1,'viber','','" . $out_vb["DestinationNumber"] . "','Unicode_No_Compression', " .
							" '',-1,'" . $out_vb["TextDecoded2"] . "','" . $out_vb["InsertIntoDB"] . "',255,'0','" . $js_resp["message_token"] . "');");
							if ($rez == 1) {
								db_execute ("DELETE FROM `sms`.`outbox` WHERE `ID`='" . $out_vb["ID"] . "';");
							}							
							break;
						case "6":
							//"{\"status\":6,\"status_message\":\"notSubscribed\",\"message_token\":5055297682581900098}"
							db_execute ("UPDATE `sms`.`outbox` SET `SenderID` = 'err' WHERE `ID`='" . $out_vb["ID"] . "';");	
							break;							
						default:
							if ($out_vb["timeout"] < 20) {
								db_execute ("UPDATE `sms`.`outbox` SET `SendingTimeOut` = `SendingTimeOut` + INTERVAL 5 MINUTE, `Retries`=`Retries`+1 WHERE `ID`='" . $out_vb["ID"] . "';");	
							}
							break;
					}
				
				}
			}
			
			curl_close($curl);
		
		}
	}
}


 function bdcom_api_delete_onu($onu_row, $device_id){

	if (isset($device_id) and is_array($device_id)) {
		$device =  $device_id;
	}else{
		$device =  db_fetch_row ("SELECT `d`.`description` as dev_name, o.*, dt.* FROM plugin_bdcom_onu LEFT JOIN plugin_bdcom_devices d on (d.device_id=o.device_id) LEFT JOIN plugin_bdcom_dev_types dt on (d.device_type_id =dt.device_type_id) where onu_id ='" . $device_id . "';");
	}				 
	$snmp_oid = ".1.3.6.1.4.1.3320.101.11.1.1.2." . $onu_row["onu_bindepon"] . '.' . bdcom_mac_16_to_10($onu_row["onu_macaddr"]);
	$ar_step1 = bdcom_set_and_check($device, $snmp_oid, "i", 0, "del_onu", "Удаление ONU  IP [" . $onu_row["onu_ipaddr"] . "], МАС [" . $onu_row["onu_macaddr"] . "] ", true, false);

 	if ($ar_step1["rezult_final"] == "OK") {
		db_execute("DELETE FROM `plugin_bdcom_onu` where onu_id=" . $onu_row["onu_id"] );
		//теперь именим количество активных записей  у порта устройства, 
		//db_execute("UPDATE `imb_ports` SET count_macip_record=(SELECT count(*) FROM imb_macip where device_id=" . $device["device_id"] . " and  macip_port_list = " . $macip_row["macip_port_list"] . ") where device_id=" . $device["device_id"] . " and  port_number = " . $macip_row["macip_port_list"] );			
		//increment_unsaved_count($device["device_id"], '1');			
 	}
 return $ar_step1["rezult_final"];
 }
 
 function bdcom_api_change_port_state($port_row, $device, $new_state){

	If (isset($port_row["epon_name"])) {
		$tbl_name="plugin_bdcom_epons";
		$col_name="epon_adminstatus";
		$col_id="epon_id";
		$col_index="epon_index";
	}else{
		$tbl_name="plugin_bdcom_ports";
		$col_name="port_admin_status";
		$col_id="port_id";
		$col_index="port_ifndex";
	}
	
	if ($new_state == "1"){
		$str_state = "Включено";
	}else{
		$str_state = "Отключено";
	};

	$ar_step1 = bdcom_set_and_check($device, ".1.3.6.1.2.1.2.2.1.7." . $port_row[$col_index] , "i", $new_state, "port_state", "Изменение состояния порта № " . $port_row["port_number"] . "  на [" . $str_state . "]", true);
	if ($ar_step1["rezult_final"] == "OK") {
			db_execute("UPDATE `" . $tbl_name . "` set " . $col_name . " = '" . $new_state . "' where " . $col_id . "=" . $port_row[$col_id] );
			//increment_unsaved_count($device["device_id"], '1');
	}			


 return $ar_step1["rezult_final"];
}

 function bdcom_api_change_port_name($port_row, $device, $str_port_name){
 	
	If (isset($port_row["epon_name"])) {
		$tbl_name="plugin_bdcom_epons";
		$col_name="epon_descr";
		$col_id="epon_id";
		$col_index="epon_index";
	}else{
		$tbl_name="plugin_bdcom_ports";
		$col_name="port_descr";
		$col_id="port_id";
		$col_index="port_ifndex";
	}
	
 	if (bdcom_snmp_set_method($device["snmp_set_version"]) == 1) {
 		$str_new_port_name=  $str_port_name ;
 	} else {
 		$str_new_port_name= html_entity_decode("&quot;") . $str_port_name . html_entity_decode("&quot;");
 	}
 	
 	$ar_step1 = bdcom_set_and_check($device, ".1.3.6.1.2.1.31.1.1.1.18." . $port_row[$col_index], "s", $str_new_port_name, "port_name", "Изменение описание порта № " . (isset($port_row["port_number"]) ? $port_row["port_number"] : $port_row["epon_number"]) . "  на [" . $str_port_name . "]", true);
 	if ($ar_step1["rezult_final"] == "OK") {
 			db_execute("UPDATE `" . $tbl_name . "` set " . $col_name . " = '" . $str_port_name . "' where " . $col_id . "=" . $port_row[$col_id] );
 			//increment_unsaved_count($device["device_id"], '1');
 	}
 return $ar_step1["rezult_final"];
 }
 

 function bdcom_rename_onu($onu_row, $device, $onu_name){
 	
 	if (bdcom_snmp_set_method($device["snmp_set_version"]) == 1) {
 		$str_new_onu_name=  $onu_name ;
 	} else {
 		$str_new_onu_name= html_entity_decode("&quot;") . $onu_name . html_entity_decode("&quot;");
 	}
 	
 	$ar_step1 = bdcom_set_and_check($device, ".1.3.6.1.2.1.31.1.1.1.18." . $onu_row["onu_index"], "s", $str_new_onu_name, "port_name", "Изменение описание ONU № " . $onu_row["onu_name"] . "  на [" . $onu_name . "]", true);
 	if ($ar_step1["rezult_final"] == "OK") {
 			db_execute("UPDATE `plugin_bdcom_onu` set `onu_descr` = '" . $onu_name . "' where onu_id=" . $onu_row["onu_id"] );
 			//increment_unsaved_count($device["device_id"], '1');
 	}
 return $ar_step1["rezult_final"];
 }

 function bdcom_update_onu($onu_group, $device, $firm_name){
 	
	$ar_actions["global_rezult"] = "Error";
	
	$str_diid_hex=bdcom_convert_port_to_hex($onu_group['diid'], ($device['device_type_id'] == 2));
 	
 	$ar_step1 = bdcom_set_and_check($device, ".1.3.6.1.4.1.3320.101.23.1.0" , "x", $str_diid_hex, "onu_update_diid", "Step 1. Set ONU diids" . $onu_group['diid'] . " ", true);
 	if ($ar_step1["rezult_final"] == "OK") {
 			
		$ar_step2 = bdcom_set_and_check($device, ".1.3.6.1.4.1.3320.101.23.2.0" , "s", $firm_name, "onu_update_name", "Step 2.  Set Firm name " . $firm_name . " ", true);
			if ($ar_step2["rezult_final"] == "OK") {
				$ar_step3 = bdcom_set_and_check($device, ".1.3.6.1.4.1.3320.101.23.3.0" , "i", "1", "onu_update", "Step 3.  Start update process  ", true);
				if ($ar_step3["rezult_final"] == "OK") {
					$ar_actions["global_rezult"] = "OK";
					db_execute("UPDATE `plugin_bdcom_onu` set `onu_up_action` = 'com' where onu_id IN (" . $onu_group['ids'] . ");");
				}				
			}
			
			
 	}
 return $ar_actions["global_rezult"];
 }
 
 function bdcom_commit_onu($onu_group, $device){
 	
	$ar_actions["global_rezult"] = "Error";
	
	$str_diid_hex=bdcom_convert_port_to_hex($onu_group['diid'], ($device['device_type_id'] == 2));
 	
 	$ar_step1 = bdcom_set_and_check($device, ".1.3.6.1.4.1.3320.101.23.1.0" , "x", $str_diid_hex, "onu_update_diid", "Step 1. Set ONU diids" . $onu_group['diid'] . " ", true);
 	if ($ar_step1["rezult_final"] == "OK") {

		$ar_step2 = bdcom_set_and_check($device, ".1.3.6.1.4.1.3320.101.23.3.0" , "i", "2", "onu_commit", "Step 2.  Commit ONU update  ", true);
		if ($ar_step2["rezult_final"] == "OK") {
			$ar_actions["global_rezult"] = "OK";
			db_execute("UPDATE `plugin_bdcom_onu` set `onu_up_action` = '' where onu_id IN (" . $onu_group['ids'] . ");");
		}				
			
			
 	}
 return $ar_actions["global_rezult"];
 }
 


/**
 * Check if a given ip is in a network
 * @param  string $ip    IP to check in IPV4 format eg. 127.0.0.1
 * @param  string $range IP/CIDR netmask eg. 127.0.0.0/24, also 127.0.0.1 is accepted and /32 assumed
 * @return boolean true if the ip is in this range / false if not.
 */
function ip_in_range( $ip, $range ) {
	if ( strpos( $range, '/' ) == false ) {
		$range .= '/32';
	}
	// $range is in IP/CIDR format eg 127.0.0.1/24
	list( $range, $netmask ) = explode( '/', $range, 2 );
	$range_decimal = ip2long( $range );
	$ip_decimal = ip2long( $ip );
	$wildcard_decimal = pow( 2, ( 32 - $netmask ) ) - 1;
	$netmask_decimal = ~ $wildcard_decimal;
	return ( ( $ip_decimal & $netmask_decimal ) == ( $range_decimal & $netmask_decimal ) );
}
 
 function bdcom_get_vlanid_by_ip($ip){
	
	$vlanid = '0';
	
	if (substr($ip,0,7) == '172.20.'){
		$oct3 = substr($ip,7,strpos($ip,'.',7)-7);
			if ($oct3 == 0) {
				$vlanid = 301;
			}elseif($oct3 == 1){
				$vlanid = 0;
			}elseif($oct3 > 1 and $oct3 < 99){
				$vlanid = '3' . $oct3;
			}else{
				$vlanid = '0';
			}
	}elseif (ip_in_range($ip, '178.216.174.64/27')) {
		$vlanid = '324';
	}elseif(ip_in_range($ip, '178.216.174.96/27')) {
		$vlanid = '325';
	}else{
		$vlanid = '0';
	}
	return $vlanid;
 }
 
 

 
 function bdcom_change_pvid_onu($onu_row, $device, $onu_pvid){
 	
 	
 	$ar_step1 = bdcom_set_and_check($device, '.1.3.6.1.4.1.3320.101.12.1.1.3.' . $onu_row["onu_index"] . ".1", "i", $onu_pvid, "onu_pvid", "Изменение VLAN ONU " . $onu_row["onu_name"] . "  на [" . $onu_pvid . "]", true);
 	if ($ar_step1["rezult_final"] == "OK") {
 			//db_execute("UPDATE `plugin_bdcom_onu` set `onu_descr` = '" . $onu_name . "' where onu_id=" . $onu_row["onu_id"] );
 			//increment_unsaved_count($device["device_id"], '1');
 	}
 return $ar_step1["rezult_final"];
 } 

 function bdcom_reboot_onu($onu_row, $device){
 	
 	
 	$ar_step1 = bdcom_set_and_check($device, '1.3.6.1.4.1.3320.101.10.1.1.29.' . $onu_row["onu_index"], "i", "0", "onu_reboot", "Перезагрузка ONU " . $onu_row["onu_name"] , true);
 	if ($ar_step1["rezult_final"] == "OK") {
 			//db_execute("UPDATE `plugin_bdcom_onu` set `onu_descr` = '" . $onu_name . "' where onu_id=" . $onu_row["onu_id"] );
 			//increment_unsaved_count($device["device_id"], '1');
 	}
	
	return $ar_step1["rezult_final"];
 }
 
function strToHex($string){
    $hex = '';
    for ($i=0; $i<strlen($string); $i++){
        $ord = ord($string[$i]);
        $hexCode = dechex($ord);
        $hex .= substr('0'.$hexCode, -2);
    }
    return strToUpper($hex);
}
function hexToStr($hex){
    $string='';
    for ($i=0; $i < strlen($hex)-1; $i+=2){
        $string .= chr(hexdec($hex[$i].$hex[$i+1]));
    }
    return $string;
}

  function hex_to_string ($hex) {
    if (strlen($hex) % 2 != 0) {
      throw new Exception('String length must be an even number.', 1);
    }
    $string = '';
  
    for ($i = 0; $i < strlen($hex) - 1; $i += 2) {
      $string .= chr(hexdec($hex[$i].$hex[$i+1]));
    }
    
    return $string;
  }




function bdcom_isJson($string) {
	json_decode($string);
	return (json_last_error() == JSON_ERROR_NONE);
}


 function bdcom_convert_port_to_hex($portlist, $use_long = false, $bol_reverse=false) {

	$portlist = str_replace(" ", ",", trim($portlist));
	$portlist = str_replace(".", ",", $portlist); 
	 
	 
	 $str_ports = explode(",", $portlist);
	 $rezult = "";
	 //$new_str_ports = array();
	if ($use_long) {
		$port_max = 384;
	}else{
	$port_max = 84;
	}

	 $arr_ports = array_fill(1, $port_max*4, 0);
	 foreach ($str_ports as $key => $str_port) {
		 if (substr_count($str_port, "-") > 0) {
			$temp_ports_string = str_replace("-", ",", trim($str_port));
			$arr_diapazon = explode(",", $temp_ports_string);
			for ($i=$arr_diapazon[0];$i<=$arr_diapazon[1];$i++)  {
				 $arr_ports[$i]='1';
			}
		}else {
			$arr_ports[$str_port]='1';
		}
	 }
	 for ($i=1;$i<=$port_max;$i++)  {
		$j=(($i-1)*4);
		if ($bol_reverse) {
			$port_summ_bin = $arr_ports[$j+4] . $arr_ports[$j+3] . $arr_ports[$j+2] . $arr_ports[$j+1];
			$rezult = sprintf("%X", bindec($port_summ_bin)) . $rezult;
		}else{
			$port_summ_bin = $arr_ports[$j+1] . $arr_ports[$j+2] . $arr_ports[$j+3] . $arr_ports[$j+4];
			$rezult = $rezult . sprintf("%X", bindec($port_summ_bin));
		
		}
	 }	
	$rezult = rtrim($rezult,'0');
	//длина должна быть кратна двум
	if (strlen($rezult)%2 !== 0){
		$rezult = $rezult . '0';
	}
	 return $rezult;
 }
 
 function bdcom_hex_trim ($str_hex) {
	$str_hex = str_replace(array(' ',':'),'',$str_hex);
	
	$str_hex = rtrim($str_hex,'0');
	
	if (strlen($str_hex)%2 !== 0){
		$str_hex = $str_hex . '0';
	}
	return $str_hex;	
 }
 
 function bdcom_convert_hex_to_view_string($xport) {
 $bol_reverse = false;
 $port_string = "";

	$xport = str_replace(array(':',' '),'',$xport);
	$arr_xport = str_split($xport);

	 foreach ($arr_xport as $str_xport) {
		 $port_string = $port_string . sprintf("%04b", hexdec($str_xport));
	 }
	 
	 $arr_port = str_split($port_string);
	 
	 //$arr_port = array_splice($arr_port,27);
	 $arr_rezult=array();
	 foreach ($arr_port as $key => $value) {
		if ($value == 1){
			array_push($arr_rezult, $key+1);
		}
	 }
	 $port_list = implode(",", $arr_rezult);
	 /*next, create port View*/
	 //array_push($arr_rezult, 255);
	 //$size_arr_rezult = sizeof($arr_rezult)-1;
	 $i = 0;
	 $str_rezult = "";
	 $last_symbol="";
	 
// $arr_rezult
// : array = 
  // 0: long = 1
  // 1: long = 7
  // 2: long = 8
  // 3: long = 13
  
	 foreach ($arr_rezult as $r){
		if (isset($arr_rezult[$i+1]) and $r == ($arr_rezult[$i+1]-1)) {
			if (($last_symbol == ",") || ($last_symbol == "")) {
				$str_rezult .= $r . "-";
				$last_symbol = "-";
			}
		}else{
			$str_rezult .= $r . ",";
			$last_symbol = ",";
		}
		
		$i++;
	 };
	 $port_view = substr($str_rezult, 0, strlen($str_rezult)-1);
	 $arr_finish=array();
 
 $arr_finish["port_view"]=$port_view;
 $arr_finish["port_list"]=$port_list;
 $arr_finish["port_arr"]=$arr_rezult;
 
 return $arr_finish;
 }

 function bdcom_convert_hex_to_view_string2($xport) {
 $bol_reverse = false;
 $port_string = "";

	$xport = str_replace(array(':',' '),'',$xport);
	$arr_xport = str_split($xport,4);

	 foreach ($arr_xport as $str_xport) {
		 $port_string = $port_string . sprintf("%04b", hexdec($str_xport));
	 }
	 
	 //$arr_port = str_split($port_string);
	 $arr_port = array_keys ( str_split($port_string) , '1');
	 
	 //$arr_port = array_splice($arr_port,27);
	 $arr_rezult=array();
	 foreach ($arr_port as $key => $value) {
			array_push($arr_rezult, $value+1);
	 }
	 $port_list = implode(",", $arr_rezult);
	 /*next, create port View*/
	 //array_push($arr_rezult, 255);
	 //$size_arr_rezult = sizeof($arr_rezult)-1;
	 $i = 0;
	 $str_rezult = "";
	 $last_symbol="";
	 
// $arr_rezult
// : array = 
  // 0: long = 1
  // 1: long = 7
  // 2: long = 8
  // 3: long = 13
  
	 foreach ($arr_rezult as $r){
		if (isset($arr_rezult[$i+1]) and $r == ($arr_rezult[$i+1]-1)) {
			if (($last_symbol == ",") || ($last_symbol == "")) {
				$str_rezult .= $r . "-";
				$last_symbol = "-";
			}
		}else{
			$str_rezult .= $r . ",";
			$last_symbol = ",";
		}
		
		$i++;
	 };
	 $port_view = substr($str_rezult, 0, strlen($str_rezult)-1);
	 $arr_finish=array();
 
 $arr_finish["port_view"]=$port_view;
 $arr_finish["port_list"]=$port_list;
 $arr_finish["port_arr"]=$arr_rezult;
 
 return $arr_finish;
 }

 
 function bdcom_autoupdate_22b() {
	$str_ids = '';

	$onus_list=db_fetch_assoc("SELECT onu_id FROM plugin_bdcom_onu where `onu_online`=1 and `onu_soft_version` like '10.0.22B.%' and `onu_soft_version`<> '10.0.22B.554' group by device_id;");
	
	if (count($onus_list) > 0) {
		foreach ($onus_list as  $r) {
			$str_ids .= (strlen($str_ids) ? ', ': '') . $r['onu_id'];
		}
	
		$onus_devices=bdcom_array_rekey(db_fetch_assoc("SELECT `d`.`description` as dev_name, d.*, o.*, dt.* FROM plugin_bdcom_onu o LEFT JOIN plugin_bdcom_devices d on (d.device_id=o.device_id) LEFT JOIN plugin_bdcom_dev_types dt on (d.device_type_id =dt.device_type_id) where onu_id in (" . $str_ids . ") ;"), "device_id");

		foreach ($onus_devices as  $d) {
			cacti_log("bdcom AUTO UPD: id=" . $d['onu_id'] . ", " . $d['onu_name'] . " MAC " . $d['onu_macaddr'] . " FW=" . $d['onu_soft_version'] . " to ONU_22B_554_img.tar" ,true,"SYSTEM");
			bdcom_update_onu(array('diid' => $d['onu_index'],'ids' => $d['onu_id']), $d, 'ONU_22B_554_img.tar');	
			sleep(3);
		}	
	}
	
 
}

 function bdcom_autoupdate_26b() {
	$str_ids = '';

	$onus_list=db_fetch_assoc("SELECT onu_id FROM plugin_bdcom_onu where `onu_online`=1 and `onu_soft_version` like '10.0.26B.%' and `onu_soft_version`<> '10.0.26B.554' group by device_id;");
	
	if (count($onus_list) > 0) {
		foreach ($onus_list as  $r) {
			$str_ids .= (strlen($str_ids) ? ', ': '') . $r['onu_id'];
		}
		$onus_devices=bdcom_array_rekey(db_fetch_assoc("SELECT `d`.`description` as dev_name, d.*, o.*, dt.* FROM plugin_bdcom_onu o LEFT JOIN plugin_bdcom_devices d on (d.device_id=o.device_id) LEFT JOIN plugin_bdcom_dev_types dt on (d.device_type_id =dt.device_type_id) where onu_id in (" . $str_ids . ") ;"), "device_id");

		foreach ($onus_devices as  $d) {
			cacti_log("bdcom AUTO UPD: id=" . $d['onu_id'] . ", " . $d['onu_name'] . " MAC " . $d['onu_macaddr'] . " FW=" . $d['onu_soft_version'] . " to P1501D1_26B_554.tar" ,true,"SYSTEM");
			bdcom_update_onu(array('diid' => $d['onu_index'],'ids' => $d['onu_id']), $d, 'P1501D1_26B_554.tar');	
			sleep(3);
		}

	}
	
 
}

/* form_alternate_row - starts an HTML row with an alternating color scheme
   @arg $light - Alternate odd style
   @arg $row_id - The id of the row
   @arg $reset - Reset to top of table */
function bdcom_form_alternate_row($row_id = '', $light = false, $disabled = false, $style='') {
	static $i = 1;

	if ($i % 2 == 1) {
		$class = 'odd';
	} elseif ($light) {
		$class = 'even-alternate';
	} else {
		$class = 'even';
	}

	$i++;

	if ($row_id != '' && !$disabled && substr($row_id, 0, 4) != 'row_') {
		print "<tr class='$class selectable tableRow' " . (strlen($style) ? " style='$style;'" : "") . " id='$row_id'>\n";
	} elseif (substr($row_id, 0, 4) == 'row_') {
		print "<tr class='$class tableRow'  " . (strlen($style) ? " style='$style;'" : "") . "  id='$row_id'>\n";
	} elseif ($row_id != '') {
		print "<tr class='$class tableRow'  " . (strlen($style) ? " style='$style;'" : "") . "  id='$row_id'>\n";
	} else {
		print "<tr class='$class  " . (strlen($style) ? " style='$style;'" : "") . "  tableRow'>\n";
	}
}
 
 ?>
