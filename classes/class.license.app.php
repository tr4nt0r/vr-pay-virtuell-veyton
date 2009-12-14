<?php

	/**
	* Project:		Distrubution License Class
	* File:			class.license.app.php
	*
	* Copyright (C) 2005 Oliver Lillie
	* 
	* This program is free software; you can redistribute it and/or modify it 
	* under the terms of the GNU General Public License as published by  the Free 
	* Software Foundation; either version 2 of the License, or (at your option) 
	* any later version.
	*
	* This program is distributed in the hope that it will be useful, but 
	* WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY 
	* or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License 
	* for more details.
	*
	* You should have received a copy of the GNU General Public License along 
	* with this program; if not, write to the Free Software Foundation, Inc., 
	* 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA	
	*
	* @link http://www.buggedcom.co.uk/
	* @link http://www.phpclasses.org/browse/package/2298.html
	* @author Oliver Lillie, buggedcom <publicmail at buggedcom dot co dot uk>
	* @history---------------------------------------------
	* see CHANGELOG
	*/
	
	class license_application extends padl {
		/**
		* the path of the license key file, remember this would be relative to the 
		* include path of the class file.
		*/
		private $_LICENSE_PATH;
		
		private $USE_DOMAIN;
		private $USE_IP;
		private $USERDEFINED;
		private $USE_MAC;
		private $USE_TIME;
		
		
		/**
		* Constructor
		*
		* @access public 
		* @param $use_mcrypt boolean Determines if mcrypt encryption is used or not (defaults to true, 
		*					 however if mcrypt is not available, it is set to false) 
		* @param $use_time boolean Sets if time binding should be used in the key (defaults to true) 
		* @param $use_server boolean Sets if server binding should be used in the key (defaults to true) 
		* @param $allow_local boolean Sets if server binding is in use then localhost servers are valid (defaults to false) 
		**/
		public function __construct($license_path='license.dat', $use_mcrypt=true, $use_time=true, $use_ip=true, $allow_local=false, $use_mac=true, $use_domain = true, $userdefined = false)
		{
			# check to see if the class has been secured
			$this->_check_secure();
			$this->_LICENSE_PATH = $license_path;
			$this->init($use_mcrypt, $use_time, $use_ip, $allow_local, $use_mac, $use_domain, $userdefined);

		}
		
		
		/**
		* init
		*
		* init the license class
		*
		* @access public 
		* @param $use_mcrypt boolean Determines if mcrypt encryption is used or not (defaults to true, 
		*					 however if mcrypt is not available, it is set to false) 
		* @param $use_time boolean Sets if time binding should be used in the key (defaults to true) 
		* @param $use_server boolean Sets if server binding should be used in the key (defaults to true) 
		* @param $allow_local boolean Sets if server binding is in use then localhost servers are valid (defaults to false) 
		**/
		private function init(
		$use_mcrypt		= true,
		$use_time		= true,
		$use_ip			= true,
		$allow_local	= false,
		$use_mac		= true,
		$use_domain		= true,
		$userdefined	= false
		) {

			# check to see if the class has been secured
			$this->_check_secure();
			$this->USE_MCRYPT			= ($use_mcrypt && function_exists('mcrypt_generic'));
			$this->USE_TIME				= $use_time;
			$this->ALLOW_LOCAL			= $allow_local;
			$this->USE_IP				= $use_ip;
			$this->USE_MAC				= $use_mac;
			$this->USE_DOMAIN			= $use_domain;

			$this->USERDEFINED			= $userdefined;


			$this->_LINEBREAK			= $this->_get_os_linebreak();
			
			if($this->USE_MAC) {
				$this->_MAC = $this->_get_mac_address();
			}
		}

		/**
		* set_server_vars
		*
		* to protect against spoofing you should copy the $_SERVER vars into a
		* seperate array right at the first line of your script so parameters can't 
		* be changed in unencoded php files. This doesn't have to be set. If it is
		* not set then the $_SERVER is copied when _get_server_info (private) function
		* is called.
		*
		* @access public 
		* @param $array array The copied $_SERVER array
		**/
		/*function set_server_vars($array)
		{
			# check to see if the class has been secured
			$this->_check_secure();
			$this->_SERVER_VARS = $array;
			# some of the ip data is dependant on the $_SERVER vars, so update them
			# after the vars have been set
			$this->_IPS			= $this->_get_ip_address();
			# update the server info
			$this->_SERVER_INFO	= $this->_get_server_info();
		}*/
		
		/**
		* _get_os_var
		*
		* gets various vars depending on the os type 
		*
		* @access private 
  		* @return string various values
		**/
		private function _get_os_var($var_name, $os)
		{
			$var_name = strtolower($var_name);
			# switch between the os's
			switch($os)
			{
				# not sure if the string is correct for FreeBSD
				# not tested
				case 'freebsd' : 
				# not sure if the string is correct for NetBSD
				# not tested
				case 'netbsd' : 
				# not sure if the string is correct for Solaris
				# not tested
				case 'solaris' : 
				# not sure if the string is correct for SunOS
				# not tested
				case 'sunos' : 
				# darwin is mac os x
				# tested only on the client os
				case 'darwin' : 
					# switch the var name
					switch($var_name)
					{
						case 'conf' :
							$var = '/sbin/ifconfig';
							break;
						case 'mac' :
							$var = 'ether';
							break;
						case 'ip' :
							$var = 'inet ';
							break;
					}
					break;
				# linux variation
				# tested on server
				case 'linux' : 
					# switch the var name
					switch($var_name)
					{
						case 'conf' :
							$var = '/sbin/ifconfig';
							break;
						case 'mac' :
							$var = 'HWaddr';
							break;
						case 'ip' :
							$var = 'inet addr:';
							break;
					}
					break;
			}
			return $var;
		}
		
		/**
		* _get_config
		*
		* gets the server config file and returns it. tested on Linux, 
		* Darwin (Mac OS X), and Win XP. It may work with others as some other
		* os's have similar ifconfigs to Darwin but they haven't been tested
		*
		* @access private 
  		* @return string config file data
		**/
		private function _get_config()
		{
			# check to see if the class has been secured
			$this->_check_secure();
			if(ini_get('safe_mode'))
			{
				# returns invalid because server is in safe mode thus not allowing 
				# sbin reads but will still allow it to open. a bit weird that one.
				return 'SAFE_MODE';
			}
			# if anyone has any clues for windows environments
			# or other server types let me know
			$os = strtolower(PHP_OS);
			if(substr($os, 0, 3)=='win')
			{
				# this windows version works on xp running apache 
				# based server. it has not been tested with anything
				# else, however it should work with NT, and 2000 also
				
				# execute the ipconfig
				@exec('ipconfig/all', $lines);
				# count number of lines, if none returned return MAC_404
				# thanks go to Gert-Rainer Bitterlich <bitterlich -at- ima-dresden -dot- de>
				if(count($lines) == 0) return 'ERROR_OPEN';
				# $path the lines together
				$conf = implode($this->_LINEBREAK, $lines);
			}
			else
			{
				# get the conf file name
				$os_file = $this->_get_os_var('conf', $os);
				# open the ipconfig
				$fp = @popen($os_file, "rb");
				# returns invalid, cannot open ifconfig
				if (!$fp) return 'ERROR_OPEN';
				# read the config
				$conf = @fread($fp, 4096);
				@pclose($fp);
			}
			return $conf;
		}
		
		/**
		* _get_ip_address
		*
		* Used to get the MAC address of the host server. It works with Linux,
		* Darwin (Mac OS X), and Win XP. It may work with others as some other
		* os's have similar ifconfigs to Darwin but they haven't been tested
		*
		* @access private 
  		* @return array IP Address(s) if found (Note one machine may have more than one ip)
  		* @return string ERROR_OPEN means config can't be found and thus not opened
  		* @return string IP_404 means ip adress doesn't exist in the config file and can't be found in the $_SERVER
  		* @return string SAFE_MODE means server is in safe mode so config can't be read
		**/
		/*function _get_ip_address()
		{
			$ips = array();
			# get the cofig file
			$conf = $this->_get_config();
			# if the conf has returned and error return it
			if($conf != 'SAFE_MODE' && $conf != 'ERROR_OPEN')
			{
				# if anyone has any clues for windows environments
				# or other server types let me know
				$os = strtolower(PHP_OS);
				if(substr($os, 0, 3)=='win')
				{
					# anyone any clues on win ip's
				}
				else
				{
					# explode the conf into seperate lines for searching
					$lines = explode($this->_LINEBREAK, $conf);
					# get the ip delim
					$ip_delim = $this->_get_os_var('ip', $os);
					
					# ip pregmatch 
					$num = "(\\d|[1-9]\\d|1\\d\\d|2[0-4]\\d|25[0-5])";
					# seperate the lines
					foreach ($lines as $key=>$line)
					{
						# check for the ip signature in the line
						if(!preg_match("/^$num\\.$num\\.$num\\.$num$/", $line) && strpos($line, $ip_delim)) 
						{
							# seperate out the ip
							$ip 	= substr($line, strpos($line, $ip_delim)+strlen($ip_delim));
							$ip 	= trim(substr($ip, 0, strpos($ip, " ")));
							# add the ip to the collection
							if(!isset($ips[$ip])) $ips[$ip] = $ip;
						}
					}
				}
			}
			
			# if the conf has returned nothing
			# attempt to use the $_SERVER data
			if(isset($this->_SERVER_VARS['SERVER_NAME']))
			{
				$ip = gethostbyname ($this->_SERVER_VARS['SERVER_NAME']);
				if(!isset($ips[$ip])) $ips[$ip] = $ip;
			}
			if(isset($this->_SERVER_VARS['SERVER_ADDR']))
			{
				$name 	= gethostbyaddr ($this->_SERVER_VARS['SERVER_ADDR']);
				$ip 	= gethostbyname ($name);
				if(!isset($ips[$ip])) $ips[$ip] = $ip;
				# if the $_SERVER addr is not the same as the returned ip include it aswell
				if($addr != $this->_SERVER_VARS['SERVER_ADDR'])
				{
					if(!isset($ips[$this->_SERVER_VARS['SERVER_ADDR']])) $ips[$this->_SERVER_VARS['SERVER_ADDR']] = $this->_SERVER_VARS['SERVER_ADDR'];
				}
			}
			# count return ips and return if found
			if(count($ips) > 0) return $ips;
			# failed to find an ip check for conf error or return 404
			if($conf == 'SAFE_MODE' || $conf == 'ERROR_OPEN') return $conf;
			return 'IP_404';
		}*/
		
		/**
		* _get_mac_address
		*
		* Used to get the MAC address of the host server. It works with Linux,
		* Darwin (Mac OS X), and Win XP. It may work with others as some other
		* os's have similar ifconfigs to Darwin but they haven't been tested
		*
		* @access private 
  		* @return string Mac address if found
  		* @return string ERROR_OPEN means config can't be found and thus not opened
  		* @return string MAC_404 means mac adress doesn't exist in the config file
  		* @return string SAFE_MODE means server is in safe mode so config can't be read
		**/
		private function _get_mac_address()
		{
			# open the config file
			$conf = $this->_get_config();
			
			# if anyone has any clues for windows environments
			# or other server types let me know
			$os = strtolower(PHP_OS);
			if(substr($os, 0, 3)=='win')
			{
				# explode the conf into lines to search for the mac
				$lines = explode($this->_LINEBREAK, $conf);
				# seperate the lines for analysis
				foreach ($lines as $key=>$line)
				{
					# check for the mac signature in the line
					# originally the check was checking for the existence of string 'physical address'
					# however Gert-Rainer Bitterlich pointed out this was for english language
					# based servers only. preg_match updated by Gert-Rainer Bitterlich. Thanks
					if(preg_match("/([0-9a-f][0-9a-f][-:]){5}([0-9a-f][0-9a-f])/i", $line)) 
					{
						$trimmed_line = trim($line);
						# take of the mac addres and return
						return trim(substr($trimmed_line, strrpos($trimmed_line, " ")));
					}
				}
			}
			else
			{
				# get the mac delim
				$mac_delim = $this->_get_os_var('mac', $os);
				
				# get the pos of the os_var to look for
				$pos = strpos($conf, $mac_delim);
				if($pos)
				{
					# seperate out the mac address
					$str1 = trim(substr($conf, ($pos+strlen($mac_delim))));
					return trim(substr($str1, 0, strpos($str1, "\n")));
				}
			}
			# failed to find the mac address
			return 'MAC_404'; 
		}

		/**
		* _get_server_info
		*
		* used to generate the server binds when server binding is needed.
		*
		* @access private 
  		* @return array server bindings
  		* @return boolean false means that the number of bindings failed to 
  		*		  meet the required number
		**/
		/*function _get_server_info()
		{
			if(empty($this->_SERVER_VARS)) {
				$this->set_server_vars($_SERVER);
			}
			# get the server specific uris
			$a = array();
			if(isset($this->_SERVER_VARS['SERVER_ADDR']) && (!strrpos($this->_SERVER_VARS['SERVER_ADDR'], '127.0.0.1') || $this->ALLOW_LOCAL)) {
				$a['SERVER_ADDR'] = $this->_SERVER_VARS['SERVER_ADDR'];
			}
				
			# corrected by Gert-Rainer Bitterlich <bitterlich -at- ima-dresden -dot- de>, Thanks
			if(isset($this->_SERVER_VARS['HTTP_HOST']) && (!strrpos($this->_SERVER_VARS['HTTP_HOST'], '127.0.0.1') || $this->ALLOW_LOCAL)) {
				$a['HTTP_HOST'] =  $this->_SERVER_VARS['HTTP_HOST'];
			}
				
			if(isset($this->_SERVER_VARS['SERVER_NAME'])) {
				$a['SERVER_NAME'] =  $this->_SERVER_VARS['SERVER_NAME'];
			}
				
			if(isset($this->_SERVER_VARS['PATH_TRANSLATED'])) {
				$a['PATH_TRANSLATED'] = substr($this->_SERVER_VARS['PATH_TRANSLATED'], 0, strrpos($this->_SERVER_VARS['PATH_TRANSLATED'], '/'));
			} elseif(isset($this->_SERVER_VARS['SCRIPT_FILENAME']))	{
				$a['SCRIPT_FILENAME'] =  substr($this->_SERVER_VARS['SCRIPT_FILENAME'], 0, strrpos($this->_SERVER_VARS['SCRIPT_FILENAME'], '/'));
			}
				
			if(isset($_SERVER['SCRIPT_URI'])) {
				$a['SCRIPT_URI'] =  substr($this->_SERVER_VARS['SCRIPT_URI'], 0, strrpos($this->_SERVER_VARS['SCRIPT_URI'], '/'));
			}
				
			# if the number of different uris is less than the required amount,
			# fail the request
			if(count($a) < $this->REQUIRED_URIS) {
				return 'SERVER_FAILED';
			}
				
			return $a;

		}*/

		/**
		* validate
		*
		* validates the server key and returns a data array. 
		*
		* @access public 
  		* @return array Main object in array is 'RESULT', it contains the result
  		*		 of the validation.
  		*		 OK 		- key is valid
  		*		 CORRUPT 	- key has been tampered with
  		*		 TMINUS 	- the key is being used before the valid start date
  		*		 EXPIRED 	- the key has expired
  		*		 ILLEGAL 	- the key is not on the same server the license was registered to
  		*		 ILLEGAL_LOCAL 	- the key is not allowed to be installed on a local machine
  		*		 INVALID 	- the the encryption key used to encrypt the key differs or the key is not complete
  		*		 EMPTY	 	- the the key is empty
  		*		 404	 	- the the key is missing
		**/
		function validate($str=false, $dialhome=false, $dialhost="", $dialpath="", $dialport="80")
		{
			# check to see if the class has been secured
			$this->_check_secure();
			# get the dat string
			$dat_str = (!$str) ? @file_get_contents($this->_LICENSE_PATH) : $str;
				
			if(strlen($dat_str)>0) {
				# decrypt the data
				$DATA = $this->_unwrap_license($dat_str);
				
				if(is_array($DATA))	{
					$DATA['RESULT'] = 'OK';
					# missing / incorrect id therefore it has been tampered with
					if($DATA['ID'] != md5($this->ID1)) {
						$DATA['RESULT'] = 'INVALID';
					}
						
					if($this->USE_TIME) {
						# the license is being used before it's official start
						if($DATA['DATE']['START'] > time()+$this->START_DIF) {
							$DATA['RESULT'] = 'TMINUS';
						}

						# the license has expired
						if($DATA['DATE']['END'] != '' && $DATA['DATE']['END']-time() < 0 && $DATA['DATE']['SPAN'] != 'NEVER') {
							$DATA['RESULT'] = 'EXPIRED';
						}
						if($DATA['DATE']['START'] != '')
						$DATA['DATE']['HUMAN']['START'] = date($this->DATE_STRING, $DATA['DATE']['START']);
						if($DATA['DATE']['END'] != '')
						$DATA['DATE']['HUMAN']['END'] 	= date($this->DATE_STRING, $DATA['DATE']['END']);
					}


					if($this->USE_DOMAIN) {						
						if(is_array($DATA['SERVER']['DOMAIN'])) {
							//license is valid for several domains
							if(!in_array($_SERVER['SERVER_NAME'], $DATA['SERVER']['DOMAIN'])) {
								$DATA['RESULT'] = 'ILLEGAL';
							}							
						} elseif($DATA['SERVER']['DOMAIN']) {
							//license is valid for one domain only
							if($DATA['SERVER']['DOMAIN'] != $_SERVER['SERVER_NAME']) {
								$DATA['RESULT'] = 'ILLEGAL';
								$DATA['REASON'] = 'DOMAIN';
							}
						}						
					}
					
					if($this->USE_IP) {						
						if(is_array($DATA['SERVER']['IP'])) {
							//license is valid for several domains
							if(!in_array($_SERVER['SERVER_ADDR'], $DATA['SERVER']['IP'])) {
								$DATA['RESULT'] = 'ILLEGAL';
							}							
						} elseif($DATA['SERVER']['IP']) {
							//license is valid for one domain only
							if($DATA['SERVER']['IP'] != $_SERVER['SERVER_ADDR']) {
								$DATA['RESULT'] = 'ILLEGAL';
								$DATA['REASON'] = 'IP';
							}
						}						
					}
						
					//license is bound to specific mac address
					if($this->USE_MAC && $DATA['SERVER']['MAC'] && $DATA['SERVER']['MAC'] != $this->_MAC) {
						$DATA['RESULT'] = 'ILLEGAL';
						$DATA['REASON'] = 'MAC';
					}
					if($this->USERDEFINED && is_array($this->USERDEFINED)) {
						
						for(reset($this->USERDEFINED);current($this->USERDEFINED);next($this->USERDEFINED)) {							
							if ($DATA['USERDEFINED'][key($this->USERDEFINED)] && $DATA['USERDEFINED'][key($this->USERDEFINED)] != current($this->USERDEFINED)) {								
								$DATA['RESULT'] = 'ILLEGAL';
							}  							
						}						
					}
					
					
					# passed all current test so license is ok
					if(!isset($DATA['RESULT'])) {
						# dial to home server if required
						if($dialhome) {
							# create the details to send to the home server
							$stuff_to_send = array();
							$stuff_to_send['LICENSE_DATA'] = $DATA;
							$stuff_to_send['LICENSE_DATA']['KEY'] = md5($dat_str);
							# dial home
							$DATA['RESULT'] = $this->_call_home($stuff_to_send, $dialhost, $dialpath, $dialport);
						} else {
							# result is ok all test passed, license is legal
							$DATA['RESULT'] = 'OK';
						}
					}
					
					# check if local
					if($this->ALLOW_LOCAL && ($_SERVER['SERVER_ADDR'] == '127.0.0.1')) {
						$DATA['RESULT'] = 'OK';
					}
					# data is returned for use
					return $DATA;
				} else {
					# the are two reason that mean a invalid return
					# 1 - the other hash key is different
					# 2 - the key has been tampered with
								# check if local
					if($this->ALLOW_LOCAL && ($_SERVER['SERVER_ADDR'] == '127.0.0.1')) {
						return array('RESULT'=>'OK');
					} else {
						return array('RESULT'=>'INVALID');
					}
				}
			}
			# returns empty because there is nothing in the dat_string
			return array('RESULT'=>'EMPTY');
		}

		/**
		* _call_home
		*
		* calls the dial home server (your server) andvalidates the clients license
		* with the info in the mysql db
		*
		* @access private 
		* @param $data array Array that contains the info to be validated
		* @param $dialhost string Host name of the server to be contacted
		* @param $dialpath string Path of the script for the data to be sent to
		* @param $dialport number Port Number to send the data through
  		* @return string Returns: the encrypted server validation result from the dial home call
  		*						: SOCKET_FAILED		=> socket failed to connect to the server
		**/
		private function _call_home($data, $dialhost, $dialpath, $dialport)
		{
			# post the data home
			$data = $this->_post_data($dialhost, $dialpath, $data, $dialport);
			return (empty($data['RESULT'])) ? 'SOCKET_FAILED' : $data['RESULT'];
		}
		

		public function print_error($results) {
						
			$LANG['LICENSE_OK']				= "License Key supplied with this file is valid. If this application was not a demonstration the end user would not see this display, your application could then run as normal.";
			$LANG['LICENSE_TMINUS']			= "License Key supplied you are using with this application has not yet entered its valid period. The License Key is valid from <b>{[DATE_START]}</b> to <b>{[DATE_END]}</b>.";
			$LANG['LICENSE_EXPIRED']		= "License Key has expired and is no longer valid. The License Key was valid from <b>{[DATE_START]}</b> to <b>{[DATE_END]}</b>.";
			$LANG['LICENSE_ILLEGAL']		= "The License Key is not valid for this server. This means that you cannot make further use of this application untill you purchase a valid key. HOWEVER, if you have you have purchased a valid key and you get this message in error, please contact the applications reseller.";
			$LANG['LICENSE_ILLEGAL_LOCAL']	= "This application can not be run on the localhost. The application can only be run under a valid domain.";
			$LANG['LICENSE_INVALID']		= "The License Key is invalid. This means that your License Key file has become corrupted. Please replace the file '".basename($this->_LICENSE_PATH)."' with a copy of the original license. If you do not still have a copy of the original license please contact the applications reseller.<br /><br />HOWEVER, you should note that this might be because the key was generated using mcrypt, where as your PHP install does not have access to the Mcrypt library (this is very unlikley).";
			$LANG['LICENSE_EMPTY']			= "License Key not found. Please copy the license to '". $this->_LICENSE_PATH. "' on the web server";

			$LANG['WRITE_ERROR']			= "<span class='warning'>The License Key was unable to be written! Please make sure that license.supplied.dat is writeable by the web server.</span><br /><br />";
				
			# switch through the results
			switch($results['RESULT'])
			{
				case 'OK' :					
					$message 		= $LANG['LICENSE_OK'];
					$image 			= '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';
					break;
				case 'TMINUS' :					
					$message 		= str_replace(array('{[DATE_START]}', '{[DATE_END]}'), array($results['DATE']['HUMAN']['START'], $results['DATE']['HUMAN']['END']), $LANG['LICENSE_TMINUS']);
					$image			= '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';
					break;
				case 'EXPIRED' :					
					$message 		= str_replace(array('{[DATE_START]}', '{[DATE_END]}'), array($results['DATE']['HUMAN']['START'], $results['DATE']['HUMAN']['END']), $LANG['LICENSE_EXPIRED']);
					$image			= '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';
					break;
				case 'ILLEGAL' :					
					$message 		= $LANG['LICENSE_ILLEGAL'];
					$image			= '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';
					break;
				case 'ILLEGAL_LOCAL' :					
					$message 		= $LANG['LICENSE_ILLEGAL_LOCAL'];
					$image			= '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';
					
					break;
				case 'INVALID' :					
					$message 		= $LANG['LICENSE_INVALID'];
					$image			= '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';
					break;
				case 'EMPTY' :					
					$message 		= $LANG['LICENSE_EMPTY'];
					$image			= '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';
					//if(defined('write_error')) $message = $LANG['WRITE_ERROR'].$message;
					break;
				default :
					break;
			}

			# template base64'd
			$template = "PGhlYWQ+Cgk8bGluayByZWw9InN0eWxlc2hlZXQiIHR5cGU9InRleHQvY3NzIiBocmVmPSJkYXRhOnRleHQvY3NzO2Jhc2U2NCxZbTlrZVEwS2V3MEtDV1p2Ym5RdFptRnRhV3g1T2lCQmNtbGhiQ3hJWld4MlpYUnBZMkVzVm1WeVpHRnVibUU3RFFvSlptOXVkQzF6YVhwbE9pQXhNM0I0T3cwS0NXTnZiRzl5T2lBak5UYzVNa016T3cwS2ZRMEtEUW91WW05a2VXeHBibXNOQ25zTkNnbGpiMnh2Y2pvZ0l6VTNPVEpETXpzTkNuME5DbUV1WW05a2VXeHBibXM2YUc5MlpYSU5DbnNOQ2dsamIyeHZjam9nSTBZd01Ec05DbjBOQ2cwS0xuZGhjbTVwYm1jTkNuc05DZ2xtYjI1MExXWmhiV2xzZVRvZ1FYSnBZV3dzU0dWc2RtVjBhV05oTEZabGNtUmhibTVoT3cwS0NXWnZiblF0YzJsNlpUb2dNVE53ZURzTkNnbGpiMnh2Y2pvZ0kwWXdNRHNOQ24wTkNnMEtMbTFsYzNOaFoyVU5DbnNOQ2dsbWIyNTBMV1poYldsc2VUb2dRWEpwWVd3c1NHVnNkbVYwYVdOaExGWmxjbVJoYm01aE93MEtDV1p2Ym5RdGMybDZaVG9nTVROd2VEc05DZ2xqYjJ4dmNqb2dJelUzT1RKRE16c05DbjBOQ2c9PSIgLz4KPC9oZWFkPgo8Ym9keSBsZWZ0bWFyZ2luPSIwIiB0b3BtYXJnaW49IjAiPgo8dGFibGUgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgYm9yZGVyPSIwIiBjZWxscGFkZGluZz0iMCIgY2VsbHNwYWNpbmc9IjAiIGFsaWduPSJjZW50ZXIiPgoJCTx0ciBhbGlnbj0iY2VudGVyIiB2YWxpZ249Im1pZGRsZSI+CgkJCTx0ZCBhbGlnbj0iY2VudGVyIiB2YWxpZ249Im1pZGRsZSI+CgkJCQk8ZGl2IGFsaWduPSJjZW50ZXIiPgoJCQkJCTx0YWJsZSB3aWR0aD0iNjAwIiBib3JkZXI9IjAiIGNlbGxwYWRkaW5nPSIwIiBjZWxsc3BhY2luZz0iMCI+CgkJCQkJCTx0cj4KCQkJCQkJCTx0ZCBoZWlnaHQ9IjIzIiBjb2xzcGFuPSI1IiB2YWxpZ249InRvcCI+CgoJCQkJCQkJCTxpbWcgc3JjPSJkYXRhOmltYWdlL2dpZjtiYXNlNjQsUjBsR09EbGhXQUlYQU9aaUFMRE00MUdPd1ZPUHdWYVJ3bEtQd1pTNTJXcWV5bFNRd2srTndNN2Y3YzNmN1ZpU3cxQ053VitYeGxtVHhLVEQzdVh1OXBDMzEvSDErbENPd1BuNy9jYmE2OS9xOU8vMStXYWJ5RmFTdzhMWDZYMnEwTVhhNnVyeDk4RFc2V2lkeWR2bzhrdUt2OVRqNzNtb3oyeWZ5cC9CM1Z5VnhWMld4YXZJNGRQaTcrNzArVjZYeGxPUXd1UHQ5ZHJuOFZ1VnhhWEYzNEN0MHBHMzE0NjIxMktaeDZEQjNmVDQrNkhDM3ZqNi9Oem84K254OTYzSzRySE00K1R0OWVIcjlOZmw4VzJoeThQWTZzL2c3clhQNUptODJvcXoxV1NheDB1THY0MjExbGlUdzAyTXY1Mi8zRXlMdjUzQTNINnIwVTZNd0p5LzNNdmQ3T0RyOUdhY3lHZWR5TGZRNWEvTDRuS2t6YlBPNU5IaDc3N1Y2TC9XNk1UWjZsYVJ3MVdSd3BXNjJWZVN3Ly8vLy8vLy93QUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUNINUJBRUFBR0lBTEFBQUFBQllBaGNBQUFmL2dHS0NnNFFkQ1ZnbEJWK0xqSTJPajVDUmtwT1VsWmFYbUptYW01eWRucCtnb2FLanBLV21wNmlwcXBJRkpVTlpPb1N5czRNaUJRWmd1YnE3dkwyK3Y4REJ3c1BFeGNiSHlNbkt5OHpOenMvUTBkTFQxTlhXMTlqSUJnVWl0TFFRRVE3WjQrVGw1dWZvNmVycjdPM3U3OFlPRVJEZWd3bTQ4UG42Ky96OS92OEFBd3JNWlNCQnZRb05CaXBjeUxDaHc0Y1FJekpyVUlHV2tCTVNNMnJjeUxHang0L0pWcVNRMVFFSXlKTW9VNnBjeWZMY0ZSV0VvRER6UXJPbXpaczRjK3JjeWJPbno1OUFnd29kU3JTbzBhTklreXBkeXJTcDA2ZFFvMHBWeXF6R0lDa21rQTBJRUNMRWdTNWd3NG9kUzdhczJiTm8wNnBkeTdhdDI3ZHcvK1BLblV1M3J0MjdlUFBxM2N1M3IxKzZCNDR3Q1RBQTJZa2VnbTRZeXlBQXdZc1lEM2g0NE1DbHN1WExtRE5yM3N5NXMrZlBvRU9MSGsyNnRPblRxRk9yWHMyNnRldlhzR1BMbm4yYWd3Y0FUWndzWUNBZ2d6RVlZbXlNS0hhQXdRY1VFQ2lFV2M2OHVmUG4wS05MbjA2OXV2WHIyTE5yMzg2OXUvZnY0TU9MSDArK3ZQbno2Tk9YcDlBQ3hRY0dCNHB0d0pFajRUQUNDeDVJVU0rL3YvLy9BQVlvNElBRUZtamdnUWhLSjhFREN4QkFqQkUrUkVITUJCZ29rT0NGR0dhbzRZWWNkdWpoaHhvcWdNRUV4Q2hReFRBQmtHQUJpQ3kyNk9LTE1NWW80NHpjV1VCQ0FNTm9ZWVV3QXREZ0FvMUFCaW5ra0VRV2FlUjFQNWdnZ0pZd093QVFqQmNzQkhIa2xGUldhZVdWV0txM2hRQmVCQU9BazhBZ01FT1daSlpwNXBsb0dsa0VBbDZDNlVzWFNZQ1E1cHgwMW1ubm5mK0I0RUJodjN3SmpCSXk0Q25vb0lRV2FpaHpTRHdCakorK2VER0FCb2RHS3Vta2xCS3B3UUJkK3NKb0x3UlFjVUdsb0lZcTZxZ0lYakNGZzVxNnlVc0FHNURxNnF1d3hncmVDRGltK2tzQVM4aXE2NjY4N2twRXJiMEFFQWdBT3c9PSIgd2lkdGg9IjYwMCIgaGVpZ2h0PSIyMyIgLz4KCQkJCQkJCTwvdGQ+CgkJCQkJCTwvdHI+CgkJCQkJCTx0cj4KCQkJCQkJCTx0ZCB3aWR0aD0iMjIiIGhlaWdodD0iNTMiIHZhbGlnbj0idG9wIj4KCQkJCQkJCQk8aW1nIHNyYz0iZGF0YTppbWFnZS9naWY7YmFzZTY0LFIwbEdPRGxoRmdBS0FLSUFBS3JJNEtuSDRMRE00MUdPd1ZlU3cvLy8vd0FBQUFBQUFDSDVCQUFBQUFBQUxBQUFBQUFXQUFvQUFBTW1LTFM4QXlYS1dWUmpJOUJ0TDhrYjFWMWdLSTFOYVZhZW82MXNxNW9vOXE2MUM4ZmVIQ1lBT3c9PSIgd2lkdGg9IjIyIiBoZWlnaHQ9IjUzIiAvPgoJCQkJCQkJPC90ZD4KCQkJCQkJCTx0ZCB3aWR0aD0iNTkiIHZhbGlnbj0idG9wIj4KCQkJCQkJCQk8aW1nIHNyYz0iZGF0YTppbWFnZS9naWY7YmFzZTY0LFIwbEdPRGxoT3dBMUFQY0FBTWZhNitMczlMSE00NjdMNHF2STRWZVN3NkxDM2xHT3dmcjgvVytpekc2aXkrbng5OGpiNjZYRTMvMzkvdnI3L2V6eitGQ053RTJNdjg3ZzdYQ2h5N0RMNHAyLzNKRzMxNGV3MUhPa3ptaWR5bFNQd2xtVXhHT2F4MTJXeFZtVHhHS1p4MkNZeG4rczBWdVV4RitYeGw2V3hXU2F4MWFSd21DWXgxS093ZVR1OW11Znl2ZjUvRnlWeEdxZXltV2J5R2FjeUdpZHlXR1l4bWVjeUYyV3htbWR5WENpekZtVHcxMlZ4VnVWeFc2aHkybWV5VjZYeG51cDBNM2U3YmpSNXJmUTVXMmd5MStYeFdTYnlIYW16bmVuejNTbHpYS2t6Vk9Qd1dXY3lHZWN5VjZYeFdHWngyZWR5WHlxMEdHWnhsU1F3bXFleVhtb3ozZW56bW1leW1DWHhudXEwR0taeG5xb3o3YlA1WDJyMFg2cjBXT1p4MjJneW1TYngzT2t6YmpRNVZXUXdtYWN5WHFwMEhpbno3TE41RzZoekd5ZnltR1l4MzJyMExYUDVXK2h5M0dqekdxZnlucXB6M0tqekdhYnlHeWd5bXlneTN5cDBHS2F4N1BPNUc2Z3kxV1J3aytNd0dpY3lMVFA1RzJoeTNLanpXK2h6RnVWeExUTzVHdWZ5V2lleWJiUTVYU2t6WFdtem5lbXozV2x6bk9qelhtbnoyZWR5SEdpekUrTndINnMwY0xYNld5Znk3UE41SDJxMEhHa3piWFA1SENqekdPYngxT1B3bGFSdzh6ZTdIbXB6M2lvejNHanpYV2x6YmJQNUYrWXhtS1l4cy9nN2t5THY4bmM2MmVieU5UajhGV1J3NmJGMzduUjV2YjUvSEtrek9IczlLeko0V3VleW51cHo2bkg0SGFuem5lbXpuaW56blNsenN2ZDdGaVV3NWE2MldxZHlmMysvbHFUeEdXYngxYVF3dnY4L29DczBXaWN5WGFsem1pZHlPYnU5bHlVeEl5ejFtdWZ5NTdCM05QaTcrVHQ5ZVR0OWxlUnczMnEwV3VneXFQRTNyVFA1YkhNNUc2Z3ltNmh5c3pkN2ZUNCsxU1J3dTMwK2JyUzVveTExdmY2L0ZlUndzM2U3SHFvMFBQMyszdW8wR3FmeVd1ZXlabTkycHkrMjhUWjZyYk81YmZQNVhhbXovSDErZkQyK2s2THdIV216WVN2MDZEQjNZaXgxSWl5MUl1ejFyN1Y2RTJNd0tuSTRHR1h4MkNYeFplNzJrNkx2OG5jN0pXNjJYU2t6bWVkeW1pZXlvT3UwbW1meW9XdzFGeVZ4VnFVeEZpVHcxaVN3Ly8vL3lINUJBQUFBQUFBTEFBQUFBQTdBRFVBQUFqL0FQOEpIRWl3b01HRENCTXFYTWl3b2NPSEVDTktuUGh2d1Njd2ovN28wU05KVXBhUG5JQ0lIQ21TMDBjMWFpUnQvUE1JSElNQXVpZ0svQlV2Q3A1eWl5SWNTSkVDQ1pRd2R5ejVLMUNnWHoraS9peUZDVk9wRXM4REVSYmhBWFVsM1FTS2plRGhRV0wwdzRjUis4S1dJTkVwQklpemFNK0dJRUhDQTQ2dy9EZ1k3UWRGM2pGME1TR2FTckhCbnordi9QaUI5VkRDYkljT1ExNG9YanlrZzlvbFlVZnc4MnJVM3dsUUZ5QVNvK3YzN3dmQkxYQ012UUxDeEJBWU0vS29uakVEUm1NUUlVS1UyRGRpQk9WK2ZndElHT0RRZ1pORm5mdHg0TUN2QmVFUU1qcDRTVElqeG80b0xsenMyQkVEQmk0dklKNlFLSUdqUmR4WnVQMVcveUtScXVHbEFyazZmd1k5bGdrSUx6Q2ExSEN4UWd3VEppYWcxNWlSeFBGYTdwSng4RUZuL2tqZ1RrTUdnQkxjZWkyMHNBUUpwY0dSUnhTQXJOSUdQZE93bzR3Tk8yeER4VDB3bU9ESk8wSjRFS0JjblNGaEJFTVBuQlBCZ29IdDR3RUp5UTNSUkJRck5GTURBYllNRk1va2dLeEF4UXd2Y0lGR1c5NE5GNTQvVUpBQXdVS25iSEZBY01TQlZSZ0lReVFSeUFwQnJLQk9OZ1dWVWtzWGJWUm5BZ2hWbE5CQ2dFdVNna0l4QzdFZ3dwUis5Y05QRGpJSzhVUUhTVFNSanh3SjdOQ0tRUXpvb0VPWE1ielFnUXhDaE9YSFpKM2RnWUlLY2M0WjNKMTU3cGxFREN2b29NZ0twUmowalEwMkpOQkZMYUtJU0lLamtQb2xLYVVLeWY5SjUxK1llbEFGQ0IySXNRT2dmTHlSempnRkdaQUFLamJZc1VvZVJZYmdBVzJ0V2pacHBTbW9oeWtQeWIyZ3dRb0tzR0hFRklNMEFNMUE3TEN5aVJGOFVIQkdvbHpJTUJ1YnVKM3diS3dpUk92WFp6bmdRS01KTTFBaHh5UkhFSkxKR0poY3dJd2FGcmdoQlJsRVpLQklFRHVJWWNJVkphNHBWN3Z2SmlTbnZMVFdleThNTHRpaDdTdFlRS0xGRklVVVFnUXJhYmdCU3ozRlJ0R0VGMDlFUE1MRXpzSnFjYnorR01YQllDUmM4VUlNYmVqUUx5eHo5RUJOR1N6OTBZMFZUbVJpeUJ0cnJCQUR6RUtvR1ZkbDd0cU0wTVU1QzJkbENEN0g0SWdOc1JUaFJnL3JhRjNOTUloZ1VjZ2dDY1JSd3d0MFZMRnNEalJuRFczWE8rLy9VeGdkY05Rd2RpeGs5RkFFYndXRjBnTVdSUmhoZzl4SmNLSHNQbmhqWGZIV09PczhnbWdoZEFCRERYTFlZSWdVVHBBaGdFSFhPTkVER1VhdzBRVVZJY3F3N0Q3OFdLNzFRVnpyTEdNSmFKaWdSTWR2RUNKRkdsaVVZWkFQYVRnaEJSR1I3T0VDREl2eVFBUHR0dSt0dXdkTDlQNjdIVzhRSVlVV3hSK3ZCU1RMUjZLREN6TjBFSUwwMUZOOHUwRzUzOENQakV1VTlyc1poNGd6UnNyR0YrUURJMW9ZZ3l1T29JTVZwRzk5MDZ1ZCs2d25QL3JaendWbU9FSVJyTUNJSHRUaGVMc0k0QlFJdUlKRVVBS0J0UE5LelJnNFB4cEF5QVNjNnNNZ0ppaUNPVnpRZnlMWVFnK204SVVFZFBDRDdKdk1nUFFHcjJqMW80RW14RThLLzFkb2hSYStrQ0EraU9FTWEzaERFT3B3aEQzTUdSQkpJTVFWOU9FTExIVGg4WlM0d1FJZU1JY2k1T0hOZkRqRjBxUVFpMFhVSWd4bDJFVURxZytNTzd3YzdqSlhSdDlCVUlKVzJJSTIrb2RFQUFyUWZHNTBZdldpK01QNVllK0IrQ09DRzdhQURUNE94QWZqVzU0bW5IZkEyU2tRaW1PVW9pSHJaOGRFWW9GNGpoUUk4cHlBaUY1TUVuWW1rQjFjQnBuSjYvSE9jelVJZ2lKR1I4b0tHT1FTcW1QZDQyRFhBYnRSTG05eWhGL21oTE03Tk1BeUNHUWpneFdLWU11Q1hNSUtZeWpFRjNhWmhMcmREWmp2SzBqdXZNYTVzRGxpRFVjZ0FpWUlnVGlDeUFJVFRvT2ExS2htTld4YXoyc3k2dGtMZU9HQ0lFeGlXMGRRaFVIbThmOEtJa3d6Q0ZGUWdnbWVVQUlQU0l5Vm1NTVl2ZXdsQXhPSW9nWm5HSlk1SnFIUGdqQmdYR3hZd3hscUFBZkpyYXRaWWt5b3RPckZneERRWVFqMmFNTWViUEFGTXhDQVVIemdRd0tDMElZbURBRUVuZmpvZ1B3UzBvT2NBaG16b2xlbWNyV0RPS3lCRGNJWVZFRUFrQUFiNk9BTU80QWVvMWkxVTMrNENVNEtlWUErWGxTbktoMkhDVU9Zd1E3T29JTXU0R01CQkVHQUJRRFJoUlhVSUVSTTJNNmFydFlaS0lSQUdneFJob0s2S3BoNGR1NW5VVGlETTFhQUFYWThReGFOb0VVWHVnQ0lIUkJKY2tzd2FGeFE1QmNWTmVRVC9BZ0RYK2UzanlXWTFBVHhvY0lLNURDZklBU0JHNzVJeGdwMjBJUVhQRXcyekZKU1p5UlEwWVhzMktJSGVKQVdlMGlBaHRLOFFBbnppYzRLVmhBZEY4U0FTSTZwQXBJQzA2d05rTWNobzhoc1Y0bGpuQkpVUVFhNHlwSVlZaENJN3NhZ0NXSklnaGNjSTRQdDRPQlJIQUNQWHl6Ump0TTl4QlIzb0pQbVFqTVc1QnpHQkhDQWdYN2g0RnJ5S3BjN3dPREhEWllVQmxEUVFpS040SUlFVHBDejRReG1SaUZ3RDY0T1ErR3p5R0I5bHB6WVVmQlFnQVlnWUNMRzhFWUhrT0FUS056aEJBWHd4dzNrVjVzR3VYaE5JN2pGZ1AxaGpSTkVBd29rUnNJdGd2RUptUWlFSEtOWWhocXlJQWhCaUFRTVlQaUJrcGZNNUI4Z0dReEFDRWVSczRBU0FFREFBVDdPc3BhM3pPVXVlNWtoQVFFQU93PT0iIHdpZHRoPSI1OSIgaGVpZ2h0PSI1MyIgLz4KCgkJCQkJCQk8L3RkPgoJCQkJCQkJPHRkIGNvbHNwYW49IjIiIHZhbGlnbj0idG9wIj4KCQkJCQkJCQk8aW1nIHNyYz0iZGF0YTppbWFnZS9naWY7YmFzZTY0LHtbSU1BR0VdfSIgd2lkdGg9IjQ5NyIgaGVpZ2h0PSI1MyIgLz4KCQkJCQkJCTwvdGQ+CgkJCQkJCQk8dGQgd2lkdGg9IjIyIiB2YWxpZ249InRvcCI+CgkJCQkJCQkJPGltZyBzcmM9ImRhdGE6aW1hZ2UvZ2lmO2Jhc2U2NCxSMGxHT0RsaEZnQUtBS0lBQUtuSDRMRE00MUdPd1ZlU3cvLy8vd0FBQUFBQUFBQUFBQ0g1QkFBQUFBQUFMQUFBQUFBV0FBb0FBQU1tU0xwTUlDUEtHSnA5YzFiTGNLWmNCMzNERmpyalo0YWVlaXB0dG5LeDlxSmtlZGRTa0FBQU93PT0iIHdpZHRoPSIyMiIgaGVpZ2h0PSI1MyIgLz4KCQkJCQkJCTwvdGQ+CgkJCQkJCTwvdHI+CgkJCQkJCTx0cj4KCgkJCQkJCQk8dGQgaGVpZ2h0PSIyNyIgdmFsaWduPSJ0b3AiIGJhY2tncm91bmQ9ImRhdGE6aW1hZ2UvZ2lmO2Jhc2U2NCxSMGxHT0RsaEZnQUtBS0lBQUtySTRLbkg0TERNNDFHT3dWZVN3Ly8vL3dBQUFBQUFBQ0g1QkFBQUFBQUFMQUFBQUFBV0FBb0FBQU1tS0xTOEF5WEtXVlJqSTlCdEw4a2IxVjFnS0kxTmFWYWVvNjFzcTVvbzlxNjFDOGZlSENZQU93PT0iPgoJCQkJCQkJCTxpbWcgc3JjPSJkYXRhOmltYWdlL2dpZjtiYXNlNjQsUjBsR09EbGhBUUFCQUlBQUFQLy8vd0FBQUNINUJBRUFBQUFBTEFBQUFBQUJBQUVBQUFJQ1JBRUFPdz09IiB3aWR0aD0iMjIiIGhlaWdodD0iMjciIC8+CgkJCQkJCQk8L3RkPgoJCQkJCQkJPHRkIGNvbHNwYW49IjIiIHZhbGlnbj0idG9wIj4KCQkJCQkJCQk8aW1nIHNyYz0iZGF0YTppbWFnZS9naWY7YmFzZTY0LFIwbEdPRGxoQVFBQkFJQUFBUC8vL3dBQUFDSDVCQUVBQUFBQUxBQUFBQUFCQUFFQUFBSUNSQUVBT3c9PSIgd2lkdGg9IjEwIiBoZWlnaHQ9IjI3IiAvPgoJCQkJCQkJPC90ZD4KCQkJCQkJCTx0ZCB3aWR0aD0iNTQxIiB2YWxpZ249InRvcCI+CgkJCQkJCQkJPHNwYW4gY2xhc3M9Im1lc3NhZ2UiPgoJCQkJCQkJCQl7W01FU1NBR0VdfSAKCQkJCQkJCQk8L3NwYW4+CgoJCQkJCQkJPC90ZD4KCQkJCQkJCTx0ZCB2YWxpZ249InRvcCIgYmFja2dyb3VuZD0iZGF0YTppbWFnZS9naWY7YmFzZTY0LFIwbEdPRGxoRmdBS0FLSUFBS25INExETTQxR093VmVTdy8vLy93QUFBQUFBQUFBQUFDSDVCQUFBQUFBQUxBQUFBQUFXQUFvQUFBTW1TTHBNSUNQS0dKcDljMWJMY0taY0IzM0RGanJqWjRhZWVpcHR0bkt4OXFKa2VkZFNrQUFBT3c9PSI+CgkJCQkJCQkJPGltZyBzcmM9ImRhdGE6aW1hZ2UvZ2lmO2Jhc2U2NCxSMGxHT0RsaEFRQUJBSUFBQVAvLy93QUFBQ0g1QkFFQUFBQUFMQUFBQUFBQkFBRUFBQUlDUkFFQU93PT0iIHdpZHRoPSIyMiIgaGVpZ2h0PSIyNyIgLz4KCQkJCQkJCTwvdGQ+CgkJCQkJCTwvdHI+CgkJCQkJCTx0cj4KCQkJCQkJCTx0ZCBoZWlnaHQ9IjEwNCIgY29sc3Bhbj0iNSIgdmFsaWduPSJ0b3AiPgoJCQkJCQkJCTxpbWcgc3JjPSJkYXRhOmltYWdlL2dpZjtiYXNlNjQsUjBsR09EbGhXQUpvQVBmL0FHT2F4OHJjN09QdDlYU2t6Wmk4MnJUTzVNRFc2SjdBM0xMTjVQajYvR0theDlIaDdwSzQySWl5MUdpZXlhVEUzNFd3MUgycjBMN1Y2S3JJNGZyOC9iclM1bStpekhLanpIbW96NXkvM0ZHT3dYU2t6cjNVNTJ5ZnltYWJ5SUt1MHE3SzRwYTYyWVN3MDh6ZTdjVFo2b3kwMXNMWTZhTEMzbW1leXNiYTY3UE40NXErMjJPYXlLZkc0STYyMTNTbXp2ejkvbW1leWVyeDk0dTAxWnE5Mi8zKy90bm04cGE3MnAvQjNmSDIrcFM1MklTdjA3YlE1WSsyMTMrczBZQ3QwWUNzMHNqYzYxYVJ3NXpBM0U2TndKeSsyNkRBM0plODJudXAwR3VneTVHNDJHV2N5TjdwODgvZzduQ2l5N0RMNDRteTFhdkk0VmFTd202aHpLeks0bG1VeEtiRjM4VFk2V0NYeHJqUjVvMjExcHE5M0lleDFNamI2N2pSNWMzZTdaQzMxMWlVeE5YazhJcXoxYTNLNGF6SjRhTEMzWGltem5Ha3pjN2Y3Y3ZkN01YYTZtaWR5cmpTNVUyTHYvZjUvSXkwMWJ2VDUxMld4cW5INEtQRTMxaVN3MWlUdzFxVXhKVzYyVlNRd3ZYNCsxbVR4RnlWeFZ1VnhHQ1l4bCtYeGwyV3hXV2J5R0taeDE2V3hXaWR5V2FjeUhHanpHdWZ5bUdZeDE2WHhyRE00M09reldTYnlIQ2l6RzZoeTJlZHlXcWV5bUdZeG5La3pXZWN5VzJneTJ5Z3kxaVR4RjJWeFdlY3lHMmh5MXVWeFdPWngyK2h5MXVVeEdHWngycWZ5bmVuem1tZHlYYW16bXlneWx5V3hmNysvMmFjeVc2Z3kyU2F5RitZeGxxVnhIZW56MStYeFY2WHhiSE00N0hONDIraXkxbVR3MmlkeUcyZ3lsdVV4V3VmeTJLWnhuR2p6WlM2MmY3Ky92LysvMXlWeEY2V3htQ1l4M0NqekhHaXpHdWV5bk9rem5hbnptZWR5UDcvLzNTbHpWV1J3bWFkeVdpY3liblM1cUhDM1ZxVHhIV216bTJoekhLa3pHS1l4NEd1MG5lbXpuZW16M1dsemFQRDNuQ2p6V1dheUZpU3hNamI3RzZneWwyWHhXV2J5ZlQ0KzRLdTBiTE81SEdpeldxZnkyeWh5bXloeTlEZzdhL000MXlVeFYyVnhIYW16Zno4L1dXY3gxK1h4M09rekxETTRySE00bWljeUdHWnhuV2x6bCtXeGJuUjVwZTcyc2ZiNjRHdDBzWFo2bWFkeUhDaHpGeVd4Rm1TdzFPUHdvMjIxdi8vLzFlU3cvLy8veUg1QkFFQUFQOEFMQUFBQUFCWUFtZ0FBQWovQUJYNUcwaHdvSVlIL1JJcVhNaXdvY09IRUNOS25FaXhvc1dMR0ROcTNNaXhvOGVQSUVPS0hFbXlwTW1US0RFcTAxQ3dvQ0tCTFExQ1NVbXpwczJiT0hQcTNNbXpwOCtmUUNVMllCblQzOHVpL2pUQUNzcTBxZE9uVUtOS25VcjFLVHBIKzVBZUxab25rWTJxWU1PS0hVdTJyTm16R2NVQXlxTVZabEVpRTlES25VdTNydDI3ZUNlMmNJUFVxTnVZR2hybHlFdTRzT0hEaUJPUGxERXJhOXUrL29nVVVFeTVzdVhMbU5FKzRkdDNLOUk4TEFSa0hrMjZ0T25USkFXZ1l0djU3MXNSQ1ZETG5rMjdOdWtFSW9oQTlyczc2UTBZdG9NTEgwNmNMSXdiUkNGNzdpdUV5RHdLeGFOTG4wNjlaZ0lkUklUMFhzNThYejNSMWNPTC94OVAvcUdBZWhxMGIzZk5uQWlpT1RMS3k1OVBuM1lwR1ZaNEVaSFNleUQzM1hrUWdRd2NCZ2pBalI0SUpxamdnZ3cyNk9DREVFWW80WVFVVm1qaGhSaG1xT0dHSEhibzRZY2doaWppaUNTV2FLS0hPUWhnQUJ3ZUVHRk1md1Q5MTVzR0d1eVRSeVRWV0tEampqejI2T09QUUFZcDVKQkVGbW5ra1VnbXFlU1NURGJwNUpOUVJpbmxsRlJXYWVXVldEcFpUU1I1N0ZNampDNnhCNlkvUXVSaDVwbG9wcW5tbW15MjZlYWJjTVlwNTV4MDFtbm5uWGptcWVlZWZQYnA1NStBQmlyb29JVHlxZDZZWVNLcTZLS01OdXJvbzVCR0t1bWtsRlpxNmFXWVpxcnBwcHgyZXFtTW5vWXE2cWlrbG1ycXFhaW1xdXFxckM0S2FxdXd4djhxNjZ5MDFtcnJyYmh1K21xdXZQYnE2Ni9BQml2c3NJa1NhK3l4eUNhcjdMTE10dGJzczlCR0srMjAxSDRxWnJYWVpxdnR0dHdDdXl0a2RZUXJiaDIxam1zdXVkMm1xKzY2N0hyNmJWL25vanRydk9LMmErKzkrT1lMNDdmMDl1dHZmLzRHTFBEQThlcHI4TUVJVXl2andIWTA3TEFkQkVjc3NjQVBWd3p4eE9ZbXJQSEdITSs2TU1VV1l5enl5QlkvUEhLOUhhZXM4c3J1L3RWdnlUREhmSEhBTXRkczg4MGxZOHp5emp6M3JLaG41bHBzeWRCRUYyMjAwVGpYWExRZFVsamN0TU5IUnkwMTBUZ0w3UFBWV0Y4TjlMZ1Z4N0xIMTJDSExiYllTY3NjdGgyNzNQRndMTHZFNHZEWWNNY05kdFVCWjIzMzNSMi9kRzdGWU4vLzRmZmZnQWNPK0I3WGtNMDMySVhEUGZnODB1VHh0VCtDSERDSkVGOExidm5sZjhkOU03MTRkKzY1dlhxTCszYmZndmRoK3Vtbzl3RjRKM3dFSHJmZnJXUCtOd2dmNU9HM1A0VzBRWWtRbDZmdXUrbXkreTMzSGpBWC9Qbnh5Q3VzQ05jTmg3MTZKM2Y0SHIzcDBIZlNpUkRNekRQNDJKWVF6d2tlbnRqaGR4V0NrMTlHUGJiZmdYc0JsZkQrdC9YVy8rNDcvSmdQWC95NXllZXYvN0toMTlHdzEzc0lYQjlVVVlVOThPSU9lN2hFT2F4M0NUN3dvUTU4a0lNZE9tR01IN1RqRHAySWhULzRzQWQvM0E2Q3NUQUdLTWdRQ0R0VXdRNkFxRUlzVEJlR08rVERIMlg0Z0RGTVo0ZGg4QUFTL3RpRkhmcWdpVDA0a0JaVklNVWwvNFpJUkNJNjhJaXBnMTd3RUhnMmgzRnVmMUNNb3EvME5qckFuYTRUNTRBQUJBN2dBa3gwSWh0Y2dNSUtHbEVGSkJCZ0JUOEFCQjlDNEFvSS9DRVNJc2lBRWo1aHdtRm80UUFsWUlVTEZLR0ZQMnlDQzBYb0FUS1djWWNPS0dFTEVTRERCNFRnd0QwUW9nQVEwQUVCemtBTERIQmhFSDJJQkFSQTBZZEJlUEtUbmdTRUtOVjRSQ1NtTG5oTmJCaitwTWpLVnNycUpXOERuQ3I2Y01ST2FDRUxlSUJBQzNBQUNBenc0QVJnNkFBUUVPQ0NCcFNoQkswZ2dEZkdnSWdlek1FSDg2QUdBQUJnQldWOFlCNVRjTUVybENDSUc3VEFCeXM0UVNRMllRVnE3SUFHV2ZpQVAwUjVoeGdVZ0E0UUNNRXJMakFBQkNCQkNCZ29RQ05JOGYrSGZ2cXpuNGtJYUNKR1NkQlNPbEFUbWppbDRGSTVMbGM2OUtHb2dxVWR2UFkrMC9GaGlIM1FnaklHRVlaRlBFRVVxeWpEQmZiZ2lTaU1vWUFZZUVVZ2ZQQUVUYURpQ1VmQXdBNTRnQUVmVElBRi92Z0RGcVJSQmxRY2dnd0VBQVVVRURDTmVwVGhFSFY0eHdSMlVBZFB0dE1ia2xobkJsWndoeU5ZQVFEVTBNRWxFcUZUTEhnVkMvL3NKeXRDT1VvamxqS0pDNTJiS2xFRzBiYTZWVmVLc0VQbE1FaExCd0xDazZUb2dRNzZzQXRDekFFSUdKZ0RKeTd4VXlSVVFSTXhTRVlsV0NvSFNDQWdBenJRQVM0YzBZTU14TzRPR0NoRElHNUJCY2pxSUpnbE9NRWYrTkFKSEVCZ0QvM1VSQkxLNEE2L3plQUJ2S0FFR1E0UUJTZi9BTUlQZmtDRWJuV0wyOTdpRnF3Q0JlVW9EV3BLMVcyUGVHdGw2MXVYeTl4SHZTU0Fmck1vSDBoeDEzNjJ3Z1VnYUFRTHhxQUNUa1FnQ29iQTdRbUtnSWxBTUdBT252aUFJbEt4aVNpVTRBK0hZTUFucFBFS0RQVGlBcERBQUJsaU1Jd0phSUVWamRCQkREQ2dDQ0FnQWg2UXZBTnVBWkVFSG5BaEVyQW9BeGorY0FrdHhJRUJyUkNFaHFjNVRRMTdXTU9JOEMxdS9SbmNRUkNVbEE0ODNYR2RxTnptdXZqRjIzRUZkTzl3UkVBRTlBOGpkb0VLSGtDTkoyZ2hFUkdZd0FVODRZbGl6S0VGRHloREJLcXdneWNnQWhBUm9BSTE1bkFBV2Z3QkRHUXdRd3RnRWRKRDhHRUhJRkRHQkRKQUNEL2c0UW5Lb0FZUFJLQUpJditoL3hJRnNBSU9vdkNBRHZ3aEVUc29nQk1pd1lJK00rTFBqT2d6Q3pnTUFFRVErZEFoeG0wcUZnM1FnSklWeFh3d3J2QytsbHg1d2ZqU21IYUpqUDlXNHh0SEloSlhwb1loZGdBS0FQaTBHSVFJUkNBVUFBa2dmS0FEbmhBRUpCWWhpRWkwb2dNZlFJSWdXdEhQQzRpZ0dING94QzlRNFlsSU5HSUhHRUFGSWdRQkFKbFN3aENmWURZQU5oR0lBVURDQno4Z2hDYzJNWXdvZ0NFU3FGQzF1RDBnN2tDZzR0eW80UEFtUVB6cFNQUTJySjhVWlFOVHZPS1pXVHJUK0g2eElsenh0N3JhdUo5KzBMQWZ3UEFBRmdpQzJvVWc5eVFtVVloQ0JFTERMR0JFdW0xUjZGcmoyQk82N1lVZmNLemhTS2c3RW9JQU5DTU16UWdGaVB6Y0ZQKzNoU2NpM29zeHNNTUNnZ2hFd3d1eGNGUE0zT0hpQm5TNkt3NWkzdm9Xb1BGR291dFlISzU4RzkzRis1NGVIMFE1aUQra0F1UWREc0VFYVA2SVJ4QWlCbGlQQVNFSVVYV0c0endRT2ljMGg5RU42SExML09ab043dXFHdzZBUlJTQUFRcW8rdGF2bnZXNWQ1M2hacGY0dVhrdWlOMisrODRtSmlYd0pvM2NpeDM5OEc5VnhDdVVmbGRXdk5FV0NqQTFJMWJoQTB6TVBSU1F5RHdrc0Q3M1lXQ2k0ZVQrTTdyVFBmcHpqL3ZtbUVpOTZsZVA5b2FuUGgyTDRJSWpKckgxVU5nKzgxaTNmU2ptVG9qUEoxelZldC81dE5mZDkwOXYzTG9tVHJHazFXcDR4RHZmb1lwQUFJMGQ2RW1PTTVzUkR3OFhJTFFoaUVNUXd0MStTRVgvSUlEeGlFUzB3c1NwZUFUZTg0Nzljczk4RXFnZ0JYVUJBWUFZQkdMK3BQQ0UzZVUrOXhqSVF0azM5Z2VUMEFHQmtBb0FSUWlIb0FDdHNJQ0FnQXJxMTNEbHBuT293QWRrSWdSQzhFWWpGa3BDTjJrczlud2V5RXJSNTI5Y2xWdlRoSDBzNEFNR2tJTDI4QUNlRUFFU1lBSW1rQUlIOEFnL3dBRW1ZQUFtWUFXUG9IVmQxM29MTndsVlozV0ZBQVFwaUlONDBBczdrQUlxV0FTRWdBS2E5NFNRY0FpSEFBbktjSU0zQ0FHZXNBTDBBSU5yRUFFQWNBQTNtSUpjVUFoMjUzVmZod2dyWUFBY0lBRVZjQUYrNEFrajlnZko1MEJLcEZZTjlZRjRtRHdoZUZIL1JvS293QWdlc0FrNnNCQnBzQXRhQUJ3SlFRS01vQVBRa1JBMi96QUpXamQzcTVkNnRNZDdvUkFJZzZnUWNYQUhCTEFRSmtBSVVTaUZvaWlGSFFBTWgvQUZDNUVCZDhBQkN6RUdyUkFBQzZFTXFMQjdrdWg3Q1RjSXNKZ1FGRkFQZjFCcmZnQldualJ2ZFVocDlwYUh4dmc1MGFjS1M5ZDBXQUJ5NFJZSThLY0VDOUVFZzFBQ2pkZ1BKdUFCU25DTllvQ0FtaGVKbG9oMW1uY0ltSkNKQ2NFRGw5Q0pDbUVDa05BQmxQQ084QWlQSGRBQnVkZ1BwVUFEdThDS0NqRURma0FDQzNFQWdmQ0U0TGgxazhBSy9xaUxRT0FIaFpab2Y5QUtvaFJweTBkMHh6aVJkNk1JS3JCME5vWmJ6TFoyajRBS0REQ05mNkFGMTJnQ3BzQUEzSWdDbFNDS2tJQUNMTm1TNHlpRmxVQUl1TEFRUERBSU5PQ0poLzlBQ1k2d2t6ekprNmR3Q21xZ0VLV3dBb0FnQVF0UkFwRmdEd3VCQTZZd2lpdkprcHpYQzBxcGl6OEFib1VHZm5JNENFZVVPWlIyaHhUNWxUeGprWm9nU29uZ0IrTjBia3Z3Q0E2QWlkTVlDV0J3alNRUUNyaHdqVGJ3Q2VKQUNaVlFDNkU0aXArUUJKVkFDWm1RQ1k0QUNmT3dFQlVRRHplcEVGZlFBWnpRQ0k3NW1JL0pDYWN3QXYvSUNrYXBFRm9nQ0VFUWk4Y0FqNVd3bDVBUUNnN2dDVjJnRUJUUURjOWdEWXhRYUhCNFo0bEFDZzZrQ2x5WlhHQlpteXdqbHRWbGxwdndoNFh3Q0U1NEFSREFCVnd3QnREZ0NORUFCV013QmpNQUJMOEFDZzB3QmczQUJUNGdtSGhaQ2ZOSWl2UFlBWlh3bHo0NURRM3duV01RQVk3L2dBRXprSnd6NEFPR0FKbnE2WmlHOEFQbG1ad1k0QWcrOEo0ekFBL3lHWjNDS1F6eGFKM1hPWVdoS1ZNZkFBMUFjQWpBeDVxL2VHZUFjQWtKTll5MGFac095akZpV1gyNkJRQ01VQWlXaHdLMWdBS0NFSEdGY0FqRmNBRTkyUWlMY0F1Wk1BcFNXQXNXNEFoY3AzNmhnSmZ4K0FsV1ozdVYwQWdXTUFvMk9ncStzQWlHRUppQmFRR1M0QXVqNkFpR01LUkRLZ21IRUlTUFlBSEZZQUU4T2dxTkFLS0g4QWxTT2d1Y2NLUmRsNU1wU1lvc01JZUI4SG5tdG00aDFrK0RJSXdjV0l3UGVxWUhFNkdza0Z1Q0VHNGVjSFdmRUFOY29BWjB1Z0FnUUFtTFVBeDZxcWVMMEtlU01LU040QWlyRUFDRTJnUlU4QWc4T3BnNm9BYUUvNW9HeWlDa2t0Q25ranFwZmRvSVVOQUZYNkFHNDBBTklpcXBrbEFHNHpBQ2FhQUdXdUFJa3lvSnFFcWs2V2tCbFVBQWFSQUFqRG9HRHBBSjhNZ0dWakFDWFJBRTlvQUJhZWx3NmVhTGZqQlFEVFI0Q0VSMDk0YW15S291aWxBQW1qQUlhN3BzYmdxbmozQURDN0VBTVpDbmUxb01ubm9CNlVrSkVVQU1DckVBak5DVG1CQUZDMkVBMlhBQmtqQUE3TnF1N2pvQW5QQWM2NGdQN1hvQkY1QUdTM2tJcDVxcVJPcVloNEFBLzFnSWp2Q1RsTEFFOVVnQk84QUlqK0NyVjRsYmlUQ21FSms1eHBxc0ZMc3V5MG9LSTNodzRXWjFLSkFFRG9BSDFRb0p4UUFQSkV1eXhUQUFmdnFuanZDdDRSb0luUEN5RnZBSWM3QVFFc0FKZmYrNnB5V2JzL0RRQ1BLYUVBYlFDSHZhcDVTWkVMbHdBTE9nclpUNnA2bzZDcSt3RU9URkNZNFFtS2FRQXFhNUE0RkFDSk1BZHF2cGl3MnBsUTQwbTVWV3NXSzdMUmVic2JzWkNJK0FlUjRMc3VINkNmREFESERMRENRN0RkcWFxaXNMcmdteEFFdXdJNDBRQTNHaEVCTGdCQ05Mc25BckNvWjd1S0pnQ0FRd2tyY1F0eTlRREtoSXRBZEFDWHlhdEVzTHNBcVJBVW1xSTV6d0NOaGdtaEJnQ2lpQXRlU0dDcHZBVzMrZ0RZSW5zWlYyckdQN3VzMVN0aHdIQURJbkM1ajNtU0JiQ3FYUUQrUGd0bkVydC9CQXQ0dGd0eEZRQTdxd3UzcjdtSVlRQXpOcmovM0FBYi93dG9VckRLQlF2ZFlMQ3M1QUFMR1JDLzFnQUJkd3VITC9TNW03Q3dNSGNBcndFTFNleXErakFMQzdXd05ENEFDUGFRR2hjQVUxa0FBVXdBMGlNQW1idDdCTHNMV0NjSHdRRzdGTVpESXRCcnNHakN5eUczQzBXd2kyQ3dsL2lRUVpjQUFIY0FKS1lBanc4QUlZL0FJa2k3S1NnQVpva0o3VGtBRWlqQU11TUtQc3lRazdnQU1TckF3enNBRVhYTGpYZTczTUFBMDRZQVpwcGdYd2dMaWlBQVluZ0FQS2dBTWZjQUhuVzdrcHE3U3dBQUVxTE1FKzRBanhld29Sd0FVaUlBSWZBQXU0eDcvK0M4RHpObmpFR0xZSDNNVUlYQUFZTzd1MWU3dU8wQUVlVUltendBd2JZQWkrME1ZWE1BMGdHcldCeWNiQWtIa09VQXVRYVFnWGtBbUVFQXBZQnd2d3NNYjJ1ZzdNc0FxaU1LbndzQXJNLzlBTWpKd0phQkREb3VBRU41b0VraUFLUTR5MFJZeXFrakFLRnBwNmpzQ3RnTm9JbjlCd1M3QUVoOUFCbnpDNmsyQUtYOXAzRHZ1UWZLQUpnMFBBWHVuRnRqeEZZSnl4Qzl6QVNYQUlIOUFHWHVBRkhFQU5vL0FCUEJBSEZTQUJHWURDUEZBQjhsQUJEd0FManRrampVQ2tsT0FDenB3TUhFQUR0OUFBSEJBSGNiRE5GakFEQmdET0VvQUR3ZEFBV2ZETnlseklxMUM5d2tBTkVoRE95VEFHdDNESis2ckpGcUFFY2VBRnllQUZEZkJzUk9vSVE5RFBYbEFBU0hBSUtjbURER3R4cnJtNnJOdTZSWGZMRkkwckNieVJES3kyRGtDdDRTcUlpTmdQS1hBSVBkc1BOa0FKUDZLOE1VQUdubGdKSzNDdWo0QURDNEVORmtBRDEvOW9EOEd3Q3U5c3VPTVFpNWx3dnBRNnZKcHNDS013R1FxaERER2dxcDh3dFAxQUFWQ0FrbFBJZFEwZENXQ0ZvQ21tUkFqa05oTHR1aFc5MWF4eTBidXMwV3lidHdEd2xncGhEOENBQjl4bzBqNkMwbitiRUJMQTBndkJBWVFBMHdxUkFoYXd1T3Q0MHpndERLS0FyMFNMQTQ3ZzAwa2IxT3VyRU1TQUE1K2d0TkMybWJyWUFBNDhoYnMzQ1I3d1o0THdER0VxaHc4cGFVeFVlS3ZFMVo3ZDFibk1jV2ZMc1RBNmsrR3FBR1NkaUpBd2x3cGhBNVVBdFR2NXNweGdBZXdaQTJWd3JoM1EwZ3JCQWJKQTF3bGgxM2lkRUNhZzE0YmMxMEpadm9LZHZrdXJBa3VaMktuNkNRZmJBSitRQ1NrSkNRNndzS3BtdWo3M3NJS24yVnYvYkthZkhkNFJGZG9CTjlwTkNLTTZvQXN3QUJ4TllBc3VFQnMxZ0kweG9BTnYwQS9BSVFiVHphT0M2UWl5M2JjelN3ekF3UUdIUUFQcURSd1ZNQWtIMEE4MUVOOXJBQXRIQU4vWUdBeldLd3pNRUpRd29Bdm9VTDZZRE5TS1BhUk1xK0FLZmdETy9hZUhRQS8yclFzSndBWFRYZDJqVzE3bXRwRHVscFhENnQyRkI5N2lmZU9qa3NDSWNMWnZHZ09adHdxNGdBY2hRQU5qTUFvWWNBTUVjQVFFSUhzWW9BTWhnQWMzQUFYYXVaMS8rWTZ4TFpsQWtPUm54QVcvRUFFMDhPVXJ3QVczOEFNSHNBSXJrQUV6VUF3K1FBTWlmQUF6SUFyV2E3aGFNRlVyUUFOQWNBSDVyS3BENmdRZlFBTkg4T2NSQUF1cUNndFFRQUR6TUE5NC96QU5nRWtKL3FsMUNTZHg2MllMTWY2d1NHVFZ6RmZMT0o3cHVrTGUwQm9JUFE0SnRmQUlmR1VPZklBS0YwREZvWW1BVFBwNXBUd0oxYm1kOExqZmpSa0RBT2NIS0ZBTWhqQUx1bjRLdHlBS3hlQUlVL0N5RndBS0Z6QnpTNUFKMTJ1NGh3Qm9MQUFMMDVEbmVuNEJvY0FML3RRQnpxQ3FGNkJ0R0ljSWpPa0llQm1scXB5MWV5ZHdBSVZpbXIwTW5GM0Ftcjd1bFJLaFRiZHNGR3FoVGZnSUh3Q0RKdEFGTFJBS25QdXlVZnVPV1RxS28vaVhnVG1ZWUdBUFYwQUM5SEFBUGpxcDJacXprakFHSmtBUEtSQUExREFBNFBzQ2N4QUE5cEFDOWxBQ05zdmhlajdROHhBQUpMQUdKREFEbzZDcWpuQUVpZ0FDSUZBR1NLQ1QxUDh0aFpzSDFka05waU0yVU4xTmVPayswZXorODVQaTdqaEdjYnk1ZzJ5cEVFMkFDajU1Q3U4NENoMUE4MUNZZWFSWTVZRncyd3BoQUxPZ3g2aEtxZTM2NnpTdEVDVHdEU1c3QWNXdzAwV3RyNUp3QzNxdW5vZEExQW1oREtHQTFFR3BpMk53Q0ZHYnBUVy9zQjVnRGIvS2tEcXZmRHcvc1VBLytKQ0NtNE5RbG1lcGF2ZEFDQjVKaUlLd25lSGc5TmY1amJ5M2RUNHU5UjB3Q296UUFuRjlDSTc1d2ZZYStwcThDSTRRQXRmNHMraXIxTG1RQVljZ0NhQ2Nudkc3STUvUXRKbExDSkNKQXFXcGkxTGU3MC8vMUZqcmNCSzNrR0dxZ2ZSV3BqWk8rTWlQS0xpWmtZa3ZjNHd2alFxUkJvLy83MU1JbFp4WCtWam5oS0s0K1hILy9RbTB6WjZxcXNtU1VQcW5yNlNWcS9xczMrR3hML3UwbnhDYWUvdTV2OVM3VDZ2Yk9ZVllwL2RhYTZBYng5M0ZEeEIzOWd5MFU3RE9RWDhKRlM1azJORGhRNGdSSlU2a1dOSGlSWXdaTlc3azJOSGpSNUFoUlk0a3lWQ1JDazJBQUNYeUV3bVJJRWFCQWswQ3dLRGZ6WDVwSWgyQ0JBa0ZpaGd4UWhGNk5BblpKS1JKaVlhSzRmTVFnRGs0KzBtSXdja1JKeWVjTEd4dDlNdlExdzQzS09BME1XWFIyUXNYMGtnOUVPTnJJN2hiTFhDaTY4aFJqRmRzTWRIZDZrQU5UZ3BRRGpuS1JLbFNKWjVCQ1UzeUVJZ1JJMVNiUFBYeTh5ZFJJa0I4TlBlNTAxbGdRVHNIRVpZa1hkcjBhZFNwVmE5bTNkcjF4Sk9hK0FBYTlBZEwveVJCQUZBeGFnd3Z3NG9WQjhaZ2dpUjBLQ0ZDbUpRWDh0RGN1WWRDbUdRaFp6b3BRZ2JzT0xnY29rVHAxQ203VitrNmNkTElVWVFEeXBSUksvRXI3WHN3MUpTWlVRYkVFZHhHYyt1R3o1UXB5UTVsRGhBd2dzSENHMFVMWlhBd0lvTm9LQ0hNTU1TS2kyR3hTV1NDYkJOQkVLbk1zc3cyOCt3emcwUjdiVVFTU3pUeFJCUlRWQkdra3pTN3BMWS9jQU1BZ0poTVFZVVVsYlN4UlVMa2lKb0VrMEprRW5KSW1RcVo1SkZIQ0VFaGtELzhjREtRdzdxanBMLys3T0xMZ2t3YUM2U1FRNzd5MHBCUENoR3pFRWN1b01RbkZFSTVoWk5EaUVJeWlVd09jV0JPQitBTXp5NUNVRUZseGxIWUxNb1VVMkk0eEJRRkJQSEVqMEE4RVA4RXM4eGE4U09WUkFhNXhFUFA5Z0JOdERwVzFIUlRUanYxOUZOUUZWSUVBVlUwVzZsSlJCRFpaRGNGTUFEaFZXK09BSUNRbzhRY0VqSTljOTN0c1NLTlpHRUhWNTU0UW9VZUNJR2tBMlNSamRJNzhBN3g0WWtDVk9BQkQwY3N3TStDRlhoUW9RQUVkamhHaHl3UVFLQUFKQjY1b1FCWDB0MEJoY1BhclVUS0tUODVnbHR2bnNBQUJTM1FmWVdjSHhEcG9ZQlhYeW5tangxQUtPTmdKQWFwYkJDVitPQ3Mwa3RGREhWaWlpdTIrR0tNRnhxMTFOa1NRZldaVFdqMEF3eXBSdERFRkE5TXNkV3h4MlowZWNiSFl0cXlFRk1Tc1VLcU9BS0I1Qk9lZWE3bFozY2ZZV0NzbXd3QWh5dTYvc0lwQTFJa2tHb0dQK2lSU2hsVDNMWC91cEpNamdrQU1DNldVRUVxR3Z5SlF5b0k3SmdBN0RzV2J2amh6aXdOY2JTTTQ1WjdicnJyRG1salV3ZXB6UTlCK2tibEQzNUt2b05JeUZ6dSsvQytYZDVOU0VBZWtDb1pSb0w2NmFlZUlEbms4a0hCSUhxcUdLejhUdWwrU3FHaER3NmtHdU1QRXRnS0JIUFdNYTlraVJRQWc0Q0Z2RzdLaFFDeHliWWpLcHdJU0p2RHpOZ1dpS0RRNExiN2VPU1RWejdqVWUvUWpBKzltMnlwYjBDMEtMbUt4L1lFQVBGVXUvZitjTjEyNDRNT3FlUVI1RWdrayt3eGhzbFI2V0Z6RGg1NWx4SmtRU2VHaGp0S3h3a0tRT3hoaTRYSkJiQnlrRUNGMUc1Q0FRZ0lvbmI5eU1VOHBEQTJuSlNOZHpmeFhXVVk1YkFQRGNSdEVsdGVCejM0LzBFUXZxWjVmZWpEYkdnalBaZndvUVNoSzBVLzFHQ0hHV0VvUTVHZ29aTnNlRU1hdm1RVElldkVDVzdTUWk5RUFqcGpVZzRtSnRFalFteENDd2tJWFQvaXdDVmtBUU1ZUVdoaURRZ1FpN0dWb29WY3VJUUptcWlMREFnQ2lUMEtCVk5pQUFEL3RaQUNPMENFSXBvSWd4QklvUUlzN0FjRUxOR0NMeDdoZHhjVW5nYmZGa0pBQmxLUWc5VElDRXVva2tFa0loVk9Rb1E2cHFBRFNNNWpCMy9vbXljc3FTRWJwa0tUbTd3aElqeVJPRDlFQXcrNHdNTThmQUNBSVMxQmxUUURGS0NXc0lnYjBJQUFOTmdCRzNiV3N3YXNnQWF5eEVBZ2RwQ0JYV1pnQU51QWdDd0pRQUFNRkdLTVkzVEFNWXA1ekJzNFl4SS9XTUV4Q1FBS0JmOThZQVZIbUFjQm5JQUlKTndBRjdqUXdRVllBandNVm9wNEhDUmtPOTM1emhBcXdoV2Q2Y1R6YU1NTDZmbWhGYlRnSng5NGtTRk1PdWtQQTcxTVFRczZVT21scW05L01DRWdJcEdybU1XTVNFSHFuaWNZZ1J6Mi9XUWJMTWdWSVFiRkFwRE9LZ2FCTUpRbFo1SVVsQ1lGU1l4QW5DeGlVSWdaOWEwUURtQ0VKelQwaDkxNGdtR1ppWkhhZ3BmQmlCa1Bua01sYWxFeEprL1BsREJ2aVJ6b0RaMkswTXZvYmFjcW9ZM2VEdHBKaFNJT2NTK0RxTXlFRkIya3BHK1pQVXBmVXNaRUpMUU9TVXdvVFY5UnpCb2tDK2xwZXhuYVVLUW1sYzYyQlRWVFJ1VnJYLzJxS1hudUlhbEtSV1QwRUhwWXkraU5xcGU0Ni9NWWU0bkNIdGIvaGpTazdQY090OE9RaFU5UE1tc09FWXY0V1NDSnlUbENpcG11VExzcm1UeG5UTXdacllVS045ZERiWWhoeit1alh2OTZXOXptVmpXQkhheG1jQ1RWeXh6V29JcnQwUE9NYTZxcUJqZWZUdlZlOTdTcXVGMTV0VmVycFc1YVMzdmFya28zcllSN2JTVUYrb2ZaVWlxdmI5dXJiczE3WHZRVzBoV0MvWkJTa1Z0VnFSTDN1SnNob1h1TlMxV3JLbGV5VHNXcVZoT1hYZTJ5N0xxRzgyK0JaNlNyaUVZMFZ3QW9WTjhDYXBsSWRZaHRmdnhqZWkxOFlReWJSQkVhOUV3bjZtdmN4NGI0dVBYdHhJYzhYRi83UXJhdzhZVVJZaEdLaFpaUTFpVTYzQ0ZYVGZzeUFPendjTjJUc1l5ZEt3ak1aaGJIT2RZeFFCOGNLWWJkZGNKdS95dWVVRFA4WkNqL1ZSR0tzQU9IVGZ6aCtUcU14Q1grVUpmcGlXSVFQNWFxWTE2eFlmZmI0NWNVMk1HcGtyRU5zZUJpMjhEWVNXak82bzdaSEFrYklsUytXbTRiaFp0YzNpZ0hXdEJEblhLVkIrSmxMTThYeFY1bWRHZkFuR1V5a3ptK0IyMHFmeTI3WnUveGQwTndYaTVXbTV2cEcwSjR6eVRzTS9IK1BHaFVwM3FRaFM2SUJ0bUxhRkkzV3AxV1pqU0s3WnZsK2VKMzBoNURxS1pERGRYaHNsalkrdTAwZjRFdDFlTGk5UTdMWURLbVZQMXNhSHRRRVUrb0EyaGFUV3RaejlyVjI4WjJsenZ4YlZ1SGU3NGgxclhlV01FS1RwODd2bVFPY2J2SEhOOXpjL29QckVBMklCaEwzejV3V1NDeHNMYXpvLzF2Z00rTkREeW90disxWFozdExuTmI0WWRHZUxodGpXdDd3aGU0Qmkwb2l5TU44VWp2bXVKUkpXNnk2d3ZVZnJNejRDTW4rYWNrSUFNaUhNVGFodDYydGwyOWNwaXp2TnNJaC9XamNSMXBxa0pjNXpmSGVjNnpIR3VRMjdia1F5ZTZwa2JBalgybzNOcVdZRHJUR1M2UXBrYzk1aXVQdWlWZW5mQ0ROMW9WVytlNkpyeitkYThqTitJTjR3UFl2MDRMczRPZG4yWnZhTEpOOWJ5dmwzM3JucWxDWnl5eGNuOFhYZTk3ZDAwaW1BQ0RXZWdEVXpIbjl0UU5QL1dGdDV6bTdYMzR6clZzYTVvMzN2R1BCM3FwUTU1M3ZtZGU4NlRCQURmK1VRWU5ZRXJwaDhlNzZFMHZHdEliUFBGWlgvekhGLy82V2tNKzJ5L1hxOGczZjN2Y2MwUVovL2lIRFBiLzRZL1RELzd5d1NlKzZGTlArdFZmSGZiSjV6YnN2Vno0MmpzNTk5T24va1Q0d0FUZS80TUJ3QzkrNll2Ly9kRWZuL0NyZDc3TG1WLytERzU3K0xhdmZ2dmRyeEFhWlA4ZkFoaUZRc0FmL0luY1gvamlYem56Vjg5L21mTS82SXMrUUhzL0EzeS9USkFCK2ZzSE5kaURoZ0MvamRBL0NTdzRBS3hBQ3hRLy9EdEFEWHkvTzFDREJlUzlBaWhBMXBoQUVoUytFcHpBdzd1L0RWekI5dHNESHZqQTdDTUJWQmlSRXl4QjhxckI3ak04RldSQkhyeTlRUEJBR013K0ptaUFpOEZCSXp4Q3pPdEJKZVM3QmhDQUlGekFHZ2lDR1FnRTVaRStrU2krSmN6QzNBdUVHUWlDR25oQ0dJUUJBVENCRnRBQlBEaERORXhETlZ4RE5teERQRGQ4UXpoa3d4QUlnVGlzUXplY1F6ek1RenEwUXo3c1F6LzhRMEFNUkVFY1JFSXNSRU04UkVSTVJFVmNSRVpzUkQ3VWdSWXdBUUdBZ1NjTUNBQTciIHdpZHRoPSI2MDAiIGhlaWdodD0iMTA0IiAvPgoJCQkJCQkJPC90ZD4KCgkJCQkJCTwvdHI+CgkJCQkJPC90YWJsZT4KCQkJCTwvZGl2PgoJCQk8L3RkPgoJCTwvdHI+Cgk8L3RhYmxlPgo8L2JvZHk+";
			
			# get the template, assign the vars and output
			die(str_replace(array('{[IMAGE]}', '{[MESSAGE]}'), array($image, $message), base64_decode($template)) );
				
		}

	}

?>