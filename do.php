<?php
	//
	function validate_date($date_text){
		//
		$got_format = 'd-m-Y';
		$result_format = 'Y-m-d H:i:s';
		//
		//
		$date = DateTime::createFromFormat($got_format, $date_text);
		//
		//echo gettype ( $date );
		//	
		if ( gettype ( $date ) == 'object' ){
			//
			$date_formated = date_format($date, $result_format);
			//	
			//echo $date_formated;
			return $date_formated;
			//
		}else{
			//
			return false;
			//
		}
			
	}
	//
	function isValidTime(DateTime $date)
	{
		$ts = $date->getTimestamp();
		$transitions = $this->getTransitions(
			$ts - self::MAX_DST_SHIFT,
			$ts + self::MAX_DST_SHIFT
		);

		if (count($transitions) == 1) {
			// No DST changes around here, so obviously $date is valid
			return true;
		}

		$shift = $transitions[1]['offset'] - $transitions[0]['offset'];

		if ($shift < 0) {
			// The clock moved backward, so obviously $date is valid
			// (although it might be ambiguous)
			return true;
		}

		$compare = new DateTime($date->format('Y-m-d H:i:s'), $this);

		return $compare->modify("$shift seconds")->getTimestamp() != $ts;
	}
	//
	function t_c($table){
		//
		switch ($table) {
			case 'id_akcii':
				return 'INT(10)';
				break;
			case 'nazvanie_akcii':
				return 'VARCHAR(100)';
				break;
			case 'data_nacala_akcii':
				return 'DATETIME';
				break;
			case 'data_okoncania':
				return 'DATETIME';
				break;
			case 'status':
				return 'VARCHAR(20)';
				break;
			default:
				return 'VARCHAR(100)';
		}
		//
	}
	//
	function run_db($arg, $db_name, $in_data){
		//
		$connection = new mysqli('localhost', 'root', 'password');
		//
		$sql_create = 'CREATE DATABASE '.$db_name.';';
		$sql_use = 'USE '.$db_name.';';
		//
		if ($arg == 'create_database'){
			//
			if ( mysqli_query($connection, $sql_create) ) {
				//
				//
			} else {
				echo 'CANT CREATE EXISTS: '.$db_name.' ERROR: '.$connection->error;
			}
			//
		}else if ($arg == 'create_tables'){
			//
			mysqli_query($connection, $sql_use);
			//
			$create_table = mysqli_query( $connection, "CREATE TABLE akcii (".
														$in_data[0]." ".t_c($in_data[0]).",".
														$in_data[1]." ".t_c($in_data[1]).",".
														$in_data[2]." ".t_c($in_data[2]).",".
														$in_data[3]." ".t_c($in_data[3]).",".
														$in_data[4]." ".t_c($in_data[4])."".
													") DEFAULT CHARSET=utf8;" );
			//
			if ( $create_table ) {
				//
				//
			} else {
				//
				echo 'CANT CREATE TABLES: '.$db_name.' ERROR: '.$connection->error;
				//
			}
			//
		}else if ($arg == 'insert'){
			//
			mysqli_query($connection, $sql_use);
			//
			//
			$prep = array();
			foreach($in_data as $k => $v ) {
				$prep[':'.$k] = $v;
			}
			//
			echo "\n";
			echo "\n";
			//echo $in_data;
			echo implode("','", $in_data );
			echo "\n";
			echo "\n";
			//
			$values = "'".implode("','", $in_data )."'";
			//
			$insert_into = mysqli_query( $connection, "INSERT INTO akcii VALUES (".$values.");" );
			//
			if ( $insert_into ) {
				//
				//
			} else {
				//
				echo 'CANT INSERT DATA: '.$db_name.' ERROR: '.$connection->error;
				//
			}
			//
		}
		$connection->close();
	}
	//
	function transliterate($word, $translit_table){
		//
		$cyr = $translit_table[0];
		$lat = $translit_table[1];
		//
		//
		$textlat = str_replace($cyr, $lat, $word);
		//
		return $textlat;
	}
	//
	function import_transliteration($transliteration){
		//
		$dirname = __DIR__;
		//
		$json_file = file_get_contents($dirname.'/translit.json');
		$json = json_decode( json_encode( json_decode( $json_file ), JSON_UNESCAPED_UNICODE ) );
		//
		$t_a=array();
		//
		$a_cyr = array();
		$a_lat = array();
		//
		$selected_translit = $json->{$transliteration};
		//
		foreach ($selected_translit as $key => $val) {
			//
			$vars = get_object_vars($val);
			foreach ($vars as $cyr => $lat) {
				//
				array_push($a_cyr, $cyr);
				array_push($a_lat, $lat);
			}
			//
		}

		return array( $a_cyr, $a_lat);

	}
	//
	function init(){
		//
		$dirname = __DIR__;
		//
		$data_read = file_get_contents($dirname.'/data.csv');
		//
		run_db('create_database', 'php_test', '' );
		//
		$data_rows = explode("\n", $data_read);
		//
		$row = 0;
		//
		foreach ($data_rows as $data_row) {
			//
			$clean_set = [];
			//
			$dirty_set = explode(";", $data_row);
			//
			foreach ($dirty_set as $str_token) {
				//
				$valid_date = validate_date($str_token);
				//
				if ( $valid_date != false ){
					//
					array_push($clean_set, $valid_date);
					//
				}else{
					//
					$exclude = preg_replace('/[!?\'+]/', '', $str_token);
					//
					if( $row == 0 ){
						//
						$res_clean = transliterate($exclude, import_transliteration('cyrillic_translit_iso_atone'));
						$res_clean = str_replace(' ', '_', strtolower($res_clean) );
						$res_clean = str_replace('"', '', $res_clean );
						array_push($clean_set, $res_clean);
						//
					}else{
						//
						$res_clean = transliterate($exclude, import_transliteration('cyrillic_translit_iso_atone'));
						$res_clean = str_replace(' ', '-', $res_clean );
						$res_clean = str_replace('"', '', $res_clean );
						//
						$res_clean = preg_replace('/[^A-z0-9%]+/', '-', $res_clean); // allows and percent symbol
						//
						array_push($clean_set, $res_clean);
						//
					}
					//
				}
				//
			}
			//
			
			//
			if( $row == 0 ){
				//
				run_db('create_tables', 'php_test', $clean_set );
				//
			}else{
				//
				run_db('insert', 'php_test', $clean_set );
				//
			}
			//
			$row = $row + 1;
			//
		}

	}

	init()

?>