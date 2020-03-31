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
 

 function bdcom_convert_status_2str($status) {
 	switch($status) {
 	case 2:
       $str_status = "<span style='color: #750F7D'>DW(2)</span>";
 	  break;
 	case 1:
       $str_status = "<span style='color: #198e32'>UP(1)</span>";
 	  break;	  
 	default:
 		$str_status = "<span style='color: #750F7D'>unk(" . $status .")</span>";
 		break;	
 	}
	return $str_status;
 } 

  function bdcom_convert_status_dereg_2str($status, $dereg_status) {
 	switch($status) {
 	case 2:
		switch($dereg_status) {
		case 2:
		   $str_status = "<span style='color: #750F7D'>NORM(2)</span>";
		  break;
		case 8:
		   $str_status = "<span style='color: #750F7D'>DW(FIB)</span>";
		  break;
		case 9:
		   $str_status = "<span style='color: #750F7D'>DW(PWR)</span>";
		  break;	  
		default:
			$str_status = "<span style='color: #750F7D'>DW(" . $dereg_status .")</span>";
			break;	
		}	
 	  break;
 	case 1:
       $str_status = "<span style='color: #198e32'>UP(1)</span>";
 	  break;	  
 	default:
 		$str_status = "<span style='color: #750F7D'>unk(" . $status .")</span>";
 		break;	
 	}
	return $str_status;
 } 
 
  function bdcom_convert_dereg_status_2str($status) {
 	//ONU binding last deregister reason. normal(2), mpcp-down(3), oam-down(4), firmware-download(5), illegal-mac(6) ,llid-admin-down(7) , wire-down(8) , power-off(9) ,unknow(255) 
	switch($status) {
 	case 2:
       $str_status = "<span style='color: #198e32'>NORM(2)</span>";
 	  break;
 	case 8:
       $str_status = "<span style='color: #750F7D'>FIB(8)</span>";
 	  break;
 	case 9:
       $str_status = "<span style='color: #750F7D'>PWR(9)</span>";
 	  break;	  
 	default:
 		$str_status = "<span style='color: #750F7D'>unk(" . $status .")</span>";
 		break;	
 	}
	return $str_status;
 } 

 ?>
