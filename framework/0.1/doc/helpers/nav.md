
# Navigation helper

You can view the source on [GitHub](https://github.com/craigfrancis/framework/blob/master/framework/0.1/library/class/nav.php).

	//--------------------------------------------------
	// Example setup

		$nav = new nav();
		$nav->link_add('/', 'Home');
		$nav->link_add('/contact/', 'Contact us');
		$nav->link_add('/help/', 'Help');

	//--------------------------------------------------
	// Example setup - with groups

		$nav = new nav();

		$nav->group_add('Group A');
		$nav->link_add('/a1/', 'A1');
		$nav->link_add('/a2/', 'A2');
		$nav->link_add('/a3/', 'A3');

		$nav->group_add('Group B');
		$nav->link_add('/b1/', 'B1');
		$nav->link_add('/b2/', 'B2');
		$nav->link_add('/b3/', 'B3');

	//--------------------------------------------------
	// Example setup - with sub navigation

		$config = array(
			'item_class' => 'xxx',
			'link_class' => 'xxx',
			'link_title' => 'xxx',
			'selected' => true,
			'child' => $sub_nav,
			'open' => true,    // Only used with the sub_nav
		);

		$sub_nav = new nav();
		$sub_nav->link_add('/about/1/', 'Page 1');
		$sub_nav->link_add('/about/2/', 'Page 2');
		$sub_nav->link_add('/about/3/', 'Page 3');

		$nav = new nav();
		$nav->link_add('/', 'Home');
		$nav->link_add('/about/', 'About', array('child' => $sub_nav));
