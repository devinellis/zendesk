<?php
/**
 * Zendesk plugin for Craft CMS 3.x
 *
 * Creates a new support ticket in Zendesk using the JSON API
 *
 * @link      https://adigital.agency
 * @copyright Copyright (c) 2018 Matt Shearing
 */

namespace adigital\zendesk\services;

use adigital\zendesk\Zendesk;

use Craft;
use craft\base\Component;

/**
 * ZendeskService Service
 *
 * All of your plugin’s business logic should go in services, including saving data,
 * retrieving data, etc. They provide APIs that your controllers, template variables,
 * and other plugins can interact with.
 *
 * https://craftcms.com/docs/plugins/services
 *
 * @author    Matt Shearing
 * @package   Zendesk
 * @since     1.0.0
 */
class ZendeskService extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * This function can literally be anything you want, and you can have as many service
     * functions as you want
     *
     * From any other plugin file, call it like this:
     *
     *     Zendesk::$plugin->zendeskService->submitTicket()
     *
     * @return mixed
     */
    public function submitTicket($data)
    {
		//build up the json array
		$create = json_encode(
			[
				'ticket' => [
					'subject' => $data['subject'],
					'priority' => $data['priority'],
					'status' => 'new',
					'type' => $data['type'],
					'comment' => [
						'body' => $data['body']
					],
					'custom_fields' => $data["customFields"],
					'requester' => [
						'name' => $data['name'],
						'email' => $data['email']
					]
				]
			]
		);

		//send all this to zendesk using our curl wrapper
		$output = self::curlWrap("/tickets.json", $create);
		
		//get the ticket ID - also checks the new ticket was created successfully
		$ticketId = $output->ticket->id;
		
		//if return exists and we've a ticket ID - it must have been created successfully :-)
		if ($output && $ticketId) {
			return $ticketId;
		}
		return false;
    }
    
    public function curlWrap($url, $json)
	{
		$settings = Zendesk::$plugin->getSettings();
	    $zdApiKey = $settings->api_key;
	    $zdUser = $settings->user;
	    $zdUrl = $settings->url;
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt($ch, CURLOPT_MAXREDIRS, 10 );
		curl_setopt($ch, CURLOPT_URL, $zdUrl.$url);
		curl_setopt($ch, CURLOPT_USERPWD, $zdUser."/token:".$zdApiKey);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
		curl_setopt($ch, CURLOPT_USERAGENT, "MozillaXYZ/1.0");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		$output = curl_exec($ch);
		curl_close($ch);
		
		$decoded = json_decode($output);
		return $decoded;
	}
}
