<?php defined('SYSPATH') or die('No direct script access.');
/**
 * OpenGeoSMS Hook
 *
 * PHP version 5
 * LICENSE: This source file is subject to LGPL license 
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/lesser.html
 * @author	   Harry C.W. Li <harryli@itri.org.tw> 
 * @package    OpenGeoSMS - http://www.facebook.com/OpenGeoSMS
 * @module	   OpenGeoSMS Hook	
 * @copyright  Industrial Technology Research Institute - http://www.itri.org.tw
 * @license    http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL) 
* 
*/


class opengeosms_events {
	
	/**
	 * Registers the main event add method
	 */
	public function __construct()
	{	
		// Hook into routing
		$this->db = Database::instance();
		Event::add('system.pre_controller', array($this, 'add'));
	}
	
	/**
	 * Adds all the events to the main Ushahidi application
	 */
	public function add()
	{
		Event::add('ushahidi_action.message_sms_add', array($this, 'process_sms'));
		Event::add('ushahidi_action.report_extra', array($this, 'send_opengeosms'));
	}
	
	public function send_opengeosms()
	{
		$incident_id = Event::$data;
		
		$incident = ORM::factory('incident')
					->where('id', $incident_id)
					->where('incident_active', 1)
					->find();
		
		$defeult_text = "";
		if ( $incident->id != 0 )
		{
			$defeult_text = $incident->incident_title;
		}
		
		$view = View::factory('send_opengeosms_form');
		$view->action_url = Kohana::config('core.site_domain') . "opengeosms/send";
		$view->incident_id = $incident_id;
		$view->text = $defeult_text;
		$view->render(TRUE);
	}
	
	public function process_sms()
	{
		$sms = Event::$data;
		
		//Kohana::log("error", "process_sms called from " . $sms->message_from . " and message = " . $sms->message);
		if(strlen($sms->message) > 0)
		{
			$sms_lines = explode(chr(0xA), $sms->message);
		}
		else
		{
			return;
		}
		
		if( count($sms_lines) < 5 )
		{
			return;
		}
		
		if( !strstr($sms_lines[0], "GeoSMS") )
		{
			return;
		}
		
		$query = $this->db->query(
				"DELETE FROM " . Kohana::config('database.default.table_prefix') . "message WHERE id = $sms->id;
			");
		
		$url_array = explode("?q=", $sms_lines[0]);
		if( count($url_array) >= 2)
		{
			$url_array2 = explode("&", $url_array[1]);
			if( count($url_array2) >= 1)
			{
				$latlon = $url_array2[0];
			}
			else
			{
				return;
			}
		}
		else
		{
			return;
		}
		
		$latlon_array =  explode(",", $latlon);
		if( count($latlon_array) >= 2)
		{
			$lat = $latlon_array[0];
			$lon = $latlon_array[1];
		}
		else
		{
			return;
		}
		
		$title = $sms_lines[1];
		$description = $sms_lines[2];
		$date_time = $sms_lines[3];
		$category = $sms_lines[4];
		
		$location_name = "";
		if( count($sms_lines) >= 6)
			$location_name = $sms_lines[5];
		
		if(strlen($location_name) < 3)
			$location_name .= "+++";	
			
		$person_first = "";
		if( count($sms_lines) >= 7)
			$person_first = $sms_lines[6];
		if(strlen($person_first) == 0)
			$person_first = $sms->message_from;
		
		$person_last = "";
		if( count($sms_lines) >= 8)
			$person_last = $sms_lines[7];
		
		$person_email = "";
		if( count($sms_lines) >= 9)
			$person_email = $sms_lines[8];
		
		$dd = "01/01/1971";
		$hh = "00";
		$mm = "00";
		$ampm = "am";
		$date_time_array = explode(" ", $date_time);
		
		if( count($date_time_array) >= 3 )
		{
			Kohana::log("error", "Start to post report : parse date_time_array");
		
			$dd = $date_time_array[0];
			$tt = $date_time_array[1];
			$ampm = strtolower($date_time_array[2]);
			
			$time_array = explode(":", $tt);
			if($time_array >= 2)
			{
				$hh = $time_array[0];
				$mm = $time_array[1];
			}
		}
		
		//Kohana::log("error", "Start to post report : $dd $hh $mm $ampm");
		
		if( $location_name == "" )
		{
			$address_xml = simplexml_load_file("http://maps.google.com/maps/api/geocode/xml?latlng=$lat,$lon&sensor=false");
			if($address_xml)
			{
				if(count($address_xml->result) > 0)
					$location_name = $address_xml->result->formatted_address;
			}
		}
		
		$postfields = array
		(
			'task' => 'report',
			'incident_title' => $title,
			'incident_description' => $description,
			'incident_date' => $dd,
			'incident_hour' => $hh,
			'incident_minute' => $mm,
			'incident_ampm' => $ampm,
			'incident_category' => $category,
			'latitude' => $lat,
			'longitude' => $lon,
			'location_name' => $location_name,
			'person_first' => $person_first,
			'person_last' => $person_last,
			'person_email' => $person_email
		);
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, url::site() . 'api');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		$r = curl_exec($ch);
		
		//Kohana::log("error", "Post result = $r");
	}
	
	
}

new opengeosms_events;