<?php
/**
 * An example Controller.  This shows the most basic usage of a Controller.
 */
class Controller_Errors extends Controller_Template {

	public function action_404()
	{
		$this->template->title = 'Page missing';
		$this->template->content = View::factory('errors/404');
	}
	
	public function action_twitter()
	{
		$this->template->title = 'Problems with Twitter';
		$this->template->content = View::factory('errors/twitter');
	}

}