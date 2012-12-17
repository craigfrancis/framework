<?php

/***************************************************

	//--------------------------------------------------
	// Example setup

		<?= cms_admin_html('content'); ?>

	//--------------------------------------------------
	// Example where the heading is separate... this
	// might be useful to re-use the heading in the
	// site navigation, or on the page <title>

		<?= cms_admin_html(array('section' => 'heading', 'wrapper_tag' => 'h1')); ?>
		<?= cms_admin_html(array('section' => 'content')); ?>

	//--------------------------------------------------
	// To make editable

		config::set('cms.default.editable', ADMIN_LOGGED_IN);

	//--------------------------------------------------
	// Full object version

		$cms_admin = new cms_admin(array(
				'mode' => 'markdown',
				'editable' => false,
				'limits' => array(), // See below
			));

		echo $cms_admin->html('section');

		echo $cms_admin->html(array(
				'section'     => 'section',
				'page'        => '/',
				'marker'      => 'marker',
				'editable'    => true, // Defaults to true
				'wrapper_tag' => 'div',
				'default'     => 'Lorem ipsum dolor sit amet...',
				'variables'   => array('count' => 5), // e.g. "You have [COUNT] messages" - note the issue with 1 message (singular) in English
			));

	//--------------------------------------------------
	// Limit support

		cms.default.limits = array('lang' => 'en'); // Try English, then move onto the default

		cms.default.limits = array(
				array('lang' => 'es'),
				array('lang' => 'en'),
			); // Try Spanish first, then English, then the default

		cms.default.limits = array(
				array('lang' => 'fr', 'country' => 'ca'),
				array('lang' => 'fr'),
				array('lang' => 'en'),
			); // Try Canadian French first, any French, any English, then the default

***************************************************/

	class cms_admin_base extends check {

		//--------------------------------------------------
		// Variables

			protected $config = array();
			protected $content = array();

		//--------------------------------------------------
		// Setup

			public function __construct($config = NULL) {
				$this->setup($config);
			}

			protected function setup($config) {

				//--------------------------------------------------
				// Profile

					if (is_string($config)) {
						$profile = $config;
					} else if (isset($config['profile'])) {
						$profile = $config['profile'];
					} else {
						$profile = NULL;
					}

				//--------------------------------------------------
				// Default config

					$default_config = array(
							'mode' => 'markdown',
							'limits' => array(),
						);

					$default_config = array_merge($default_config, config::get_all('cms.default'));

				//--------------------------------------------------
				// Set config

					if (!is_array($config)) {
						$config = array();
					}

					if ($profile !== NULL) {
						$config = array_merge(config::get_all('cms.' . $profile), $config);
						$config['profile'] = $profile;
					}

					$this->config = array_merge($default_config, $config);

				//--------------------------------------------------
				// Tables

					if (config::get('debug.level') > 0) {

						debug_require_db_table(DB_PREFIX . 'cms_text', '
								CREATE TABLE [TABLE] (
									path varchar(100) NOT NULL,
									section varchar(100) NOT NULL,
									marker tinytext NOT NULL,
									created datetime NOT NULL,
									version int(11) NOT NULL,
									content text NOT NULL,
									PRIMARY KEY (path, section, version)
								);');

					}

				//--------------------------------------------------
				// Return content for this page

					$where_sql = array();
					$where_sql[] = '';

					$this->content = array();

			}

		//--------------------------------------------------
		// Standard file support

			public function html($config) {

				//--------------------------------------------------
				// Config

					$defaults = array(
							'section'     => 'section',
							'page'        => '/',
							'marker'      => NULL,
							'wrapper_tag' => NULL,
							'default'     => 'Lorem ipsum dolor sit amet...',
							'editable'    => true,
						);

					if (is_string($config)) {

						$config = array('section' => $config);

					} else if (!is_array($config)) {

						$config = array();

					}

					$config = array_merge($defaults, $config);

				//--------------------------------------------------
				// Return

					return 'Hello';

			}

	}

?>