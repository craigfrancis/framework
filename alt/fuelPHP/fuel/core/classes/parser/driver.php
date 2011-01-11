<?php
/**
 * Fuel
 *
 * Fuel is a fast, lightweight, community driven PHP5 framework.
 *
 * @package		Fuel
 * @version		1.0
 * @author		Phil Sturgeon
 * @license		MIT License
 * @copyright	2010 - 2011 Fuel Development Team
 * @link		http://fuelphp.com
 */

namespace Fuel;

// --------------------------------------------------------------------

abstract class Parser_Driver extends View {

	/**
	 * parse a view file
	 *
	 * @access	public
	 * @return	void
	 */
	abstract function parse();

	/**
	 * parse a string
	 *
	 * @access	public
	 * @return	void
	 */
	abstract function parse_string();

}

/* End of file driver.php */
