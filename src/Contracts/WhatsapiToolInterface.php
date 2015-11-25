<?php 
namespace Xaamin\Whatsapi\Contracts;


interface WhatsapiToolInterface 
{
	/**
	 * Request code registration to Whatsapp server
	 * 
	 * @param  string $number
	 * @param  string $type    sms or voice
	 * @param  string $carrier 
	 * @return array
	 */
    public function requestCode($number, $type, $carrier);

    /**
     * Register code for account activation
     * 
     * @param  string $number 
     * @param  string $code   
     * @return array
     */
    public function registerCode($number, $code);
}