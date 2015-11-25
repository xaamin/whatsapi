<?php 
namespace Xaamin\Whatsapi\Sessions\Laravel;

use Illuminate\Session\Store as SessionStore;
use Xaamin\Whatsapi\Sessions\SessionInterface;

class Session implements SessionInterface {
	
	/**
	 * The key used in the Session.
	 *
	 * @var string
	 */
	protected $key = 'itnovado_whatsapi';

	/**
	 * Session store object.
	 *
	 * @var \Illuminate\Session\Store
	 */	
	protected $session;

	/**
	 * Creates a new Illuminate based Session driver.
	 *
	 * @param  \Illuminate\Session\Store  $session
	 * @param  string  $key
	 * @return void
	 */
	public function __construct(SessionStore $session, $key = null)
	{
		$this->session = $session;

		if ($key) $this->key = $key;
	}

	/**
     * {@inheritdoc}
     */
	public function getKey()
	{
		return $this->key;
	}

	/**
     * {@inheritdoc}
     */
	public function put($value)
	{
		$this->session->put($this->getKey(), $value);
	}
	
	/**
     * {@inheritdoc}
     */
	public function pull()
	{
		return $this->session->pull($this->getKey());
	}
}