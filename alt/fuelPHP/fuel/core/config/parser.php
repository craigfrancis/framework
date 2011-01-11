<?php
/**
 * Fuel
 *
 * Fuel is a fast, lightweight, community driven PHP5 framework.
 *
 * @package		Fuel
 * @version		1.0
 * @author		Harro "WanWizard" Verton
 * @license		MIT License
 * @copyright	2010 - 2011 Fuel Development Team
 * @link		http://fuelphp.com
 */

namespace Fuel\Application;

return array(
	/**
	 * global configuration
	*/

	// if no parser type is requested, use the default
	'driver'			=> 'tags',

	/**
	 * specific driver configurations. to override a global setting, just add it to the driver config with a different value
	*/

	// special configuration settings for cookie based sessions
	'tags'			=> array(
		'l_delim'		=> '{',					// Character to start a tag
		'trigger'		=> 'fuel:',				// Gets added after the start tag to protect normal start tags {fuel:foo:bar}
		'r_delim'		=> '}',					// Character to start a tag
	)

);

/* End of file config/session.php */
