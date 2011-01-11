<?php
/**
 * An example Controller.  This shows the most basic usage of a Controller.
 */
class Controller_Profiles extends Controller_Template {

	public function action_random()
	{
		if (mt_rand(1, 10) == 5)
		{
			Cache::delete('public');
		}
		
		if ( ! $tweets = Cache::call('public', array($this, 'fetch'), array('statuses/public_timeline.json'), 30))
		{
			$result = Request::factory('errors/twitter')->execute();
			exit($result);
		}

		$random_twit = $tweets[array_rand($tweets)]->user;

		// Call the view action so we can see this user
		$this->action_view($random_twit->screen_name);
	}


	public function action_view($screen_name = null)
	{
		$tweets = Cache::call($screen_name, array($this, 'fetch'), array('statuses/user_timeline.json?count=3&screen_name='.$screen_name), 10 * 60);

		// If the user does not exist or the request fails then just 404
		if ( ! $tweets or isset($tweets['error']))
		{
			Request::show_404();
		}

		// Get the user from the first tweet
		$twit = current($tweets)->user;

		// Get all votes for this twitter user
		$votes = Model_Vote::find_by_twit_id($twit->id);

		$agree = 0;
		$disagree = 0;

		foreach ($votes as $vote)
		{
			$vote->is_fucktard ? ++$agree : ++$disagree;
		}

		$rating = $agree > 0 ? $agree / count($votes) * 100 : null;

		$this->template->content = View::factory('profiles/view', array(
			'twit' => $twit,
			'tweets' => $tweets,
			'agree' => $agree,
			'disagree' => $disagree,
			'rating' => $rating,
			'votes' => count($votes),
			'profile_image' => $twit->profile_image_url,
			'is_random' => Request::active()->action == 'random'
		));
	}

	public function fetch($uri)
	{
		return json_decode(file_get_contents('http://api.twitter.com/1/'.$uri));
	}

	public function action_vote()
	{
		$vote = new Model_Vote(array(

			// Save the twitter id we are voting on
			'twit_id' => Input::post('id'),

			// If yes does not exist, then obviously default to no
			'is_fucktard' => (bool) Input::post('yes', false)
		));
 
		$vote->save();

		Session::set_flash('message', 'Your vote has been cast.');

		Output::redirect(Input::post('screen_name'));
	}

}