<?php
/**
 * Fuel
 *
 * Fuel is a fast, lightweight, community driven PHP5 framework.
 *
 * @package		Fuel
 * @version		1.0
 * @license		MIT License
 * @copyright	2010 - 2011 Fuel Development Team
 * @link		http://fuelphp.com
 */

namespace Fuel;

// --------------------------------------------------------------------

/**
 * parser Class
 *
 * @package		Fuel
 * @subpackage	Core
 * @category	Core
 * @author		Harro "WanWizard" Verton
 */
class Parser
{
	/**
	 * loaded parser driver instance
	 */
	protected static $_instance = null;

	/**
	 * Initialize by loading config & starting default parser
	 */
	public static function _init()
	{
		Config::load('parser', true);

		$defaults = Config::get('parser', array());

		// When a string was entered it's just the driver type
		if ( ! empty($config) && ! is_array($config))
		{
			$config = array('driver' => $config);
		}

		// Overwrite default values with given config
		$config = array_merge($defaults, $config);

		if (empty($config['driver']))
		{
			throw new Exception('No parser driver given or no default parser driver set.');
		}

		// Instantiate the driver
		$class = 'Parser_'.ucfirst($config['driver']);
		$driver = new $class;

		// And configure it, specific driver config first
		if (isset($config[$config['driver']]))
		{
			$driver->set_config('config', $config[$config['driver']]);
		}

		// if the driver has an init method, call it
		if (method_exists($driver, 'init'))
		{
			$driver->init();
		}
	}

	/**
	 * Factory
	 *
	 * Produces fully configured parser driver instances
	 *
	 * @param	array|string	full driver config or just driver type
	 */
	public static function factory($view = array())
	{

		return $driver;
	}

	/**
	 * class constructor
	 *
	 * @param	void
	 * @access	private
	 * @return	void
	 */
	private function __construct()
	{
		$defaults = Config::get('parser', array());

		// When a string was entered it's just the driver type
		if ( ! empty($config) && ! is_array($config))
		{
			$config = array('driver' => $config);
		}

		// Overwrite default values with given config
		$config = array_merge($defaults, $config);

		if (empty($config['driver']))
		{
			throw new Exception('No parser driver given or no default parser driver set.');
		}

		// Instantiate the driver
		$class = 'Parser_'.ucfirst($config['driver']);
		$driver = new $class;

		// And configure it, specific driver config first
		if (isset($config[$config['driver']]))
		{
			$driver->set_config('config', $config[$config['driver']]);
		}

		// if the driver has an init method, call it
		if (method_exists($driver, 'init'))
		{
			$driver->init();
		}
	}

	/**
	 * create or return the driver instance
	 *
	 * @param	void
	 * @access	public
	 * @return	parser_Driver object
	 */
	public static function factory($view, $data)
	{
		return new View($view);
	}

	/**
	 * set parser variables
	 *
	 * @param	string	name of the variable to set
	 * @param	mixed	value
	 * @access	public
	 * @return	void
	 */
	public static function parse($name, $data)
	{
		return static::instance()->parse($name, $data);
	}

	/**
	 * set parser variables
	 *
	 * @param	string	name of the variable to set
	 * @param	mixed	value
	 * @access	public
	 * @return	void
	 */
	public static function parse_string($string, $data)
	{
		return static::instance()->parse_string($string, $data);
	}

}

/* End of file parser.php */