<?php defined('SYSPATH') or die('No direct script access.');
/**
 * OpenGeoSMS Controller
 *
 * PHP version 5
 * LICENSE: This source file is subject to LGPL license 
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/lesser.html
 * @author	   Harry C.W. Li <harryli@itri.org.tw> 
 * @package    OpenGeoSMS - http://www.facebook.com/OpenGeoSMS
 * @module	   OpenGeoSMS Controller	
 * @copyright  Industrial Technology Research Institute - http://www.itri.org.tw
 * @license    http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL) 
* 
*/


class OpenGeoSMS_Controller extends Template_Controller
{
	public $template = '';
	
	public function index()
	{
		url::redirect('reports');
	}
	
	public function send()
    {
		$post = new Validation($_POST);
		$post->pre_filter('trim', TRUE);

		$post->add_rules('incident_id', 'required');
		$post->add_rules('phone','required');
		$post->add_rules('text','required');
		
		$result_message = "";
		
		if( $post->validate() )
		{
			$incident_id =$post->incident_id;
			$phone = $post->phone;
			$text = $post->text;
			
			$incident = ORM::factory('incident')
						->where('id', $incident_id)
						->where('incident_active', 1)
						->find();
			
			if ( $incident->id != 0 )
			{
				//get incident location.
				$lat = round($incident->location->latitude, 6);
				$lon = round($incident->location->longitude, 6);
				
				//send the sms
				$sms_message = "http://maps.google.com.tw/?q=$lat,$lon&GeoSMS\n$text";
				$r = sms::send($phone, "", $sms_message);
				if($r == '1')
				{
					$result_message = "SMS sent success!";
				}
				else
				{
					$result_message = $r;
				}
			}
			else
			{
				$result_message = "Check input fields.";
			}
		}
		else
		{
			$result_message = "Check input fields.";
		}
		
		$this->template = new View('send_action');
		$this->template->result_message = $result_message;
	}
}
