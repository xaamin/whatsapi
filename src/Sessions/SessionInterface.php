<?php 
namespace Xaamin\Whatsapi\Sessions;

interface SessionInterface {
	
	/**
	 * Returns the session key.
	 *
	 * @return string
	 */
	public function getKey();

	/**
	 * Put a value in the session.
	 *
	 * @param  mixed   $value
	 * @return void
	 */
	public function put($value);

	/**
	 * Get the session value and remove it from store.
	 *
	 * @return mixed
	 */
	public function pull();
}