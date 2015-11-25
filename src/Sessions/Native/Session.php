<?php 
namespace Xaamin\Whatsapi\Sessions\Native;

use Xaamin\Whatsapi\Sessions\SessionInterface;

class Session implements SessionInterface {

	/**
	 * The key used in the Session.
	 *
	 * @var string
	 */
	protected $key = 'itnovado_whatsapi';

	/**
	 * Creates a new native session driver.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public function __construct($key = null)
	{
		if (isset($key)) $this->key = $key;

		$this->startSession();
	}

	/**
	 * Called upon destruction of the native session handler.
	 *
	 * @return void
	 */
	public function __destruct()
	{
		$this->writeSession();
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
		$this->set($value);
	}
	
	/**
     * {@inheritdoc}
     */
	public function pull()
	{
		$session = $this->getSession();

		$this->$this->forgetSession();

		return $session;
	}

	/**
	 * Starts the session if it does not exist.
	 *
	 * @return void
	 */
	public function startSession()
	{
		// Let's start the session
		if (session_id() == '')
		{
			session_start();
		}
	}

	/**
	 * Writes the session.
	 *
	 * @return void
	 */
	public function writeSession()
	{
		session_write_close();
	}

	/**
	 * Interacts with the $_SESSION global to set a property on it.
	 * The property is serialized initially.
	 *
	 * @param  mixed  $value
	 * @return void
	 */
	public function setSession($value)
	{
		$_SESSION[$this->getKey()] = serialize($value);
	}

	/**
	 * Unserializes a value from the session and returns it.
	 *
	 * @return mixed.
	 */
	public function getSession()
	{
		if (isset($_SESSION[$this->getKey()]))
		{
			return unserialize($_SESSION[$this->getKey()]);
		}
	}

	/**
	 * Forgets the Itnovado session from the global $_SESSION.
	 *
	 * @return void
	 */
	public function forgetSession()
	{
		if (isset($_SESSION[$this->getKey()]))
		{
			unset($_SESSION[$this->getKey()]);
		}
	}

}
