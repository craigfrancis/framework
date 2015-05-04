<?php

	/*--------------------------------------------------
	/* Extend with...

		class lolcat extends lolcat_base {

			public function credit_get() {
			}

			public function credit_add($credits) {
			}

		}

	/*--------------------------------------------------*/

	class lolcat_base extends check {

		public function image_get_url($credits = NULL) {

			$id = $this->image_get_id($credits);
			$site = config::get('lolcat.site');
			$pass = hash('sha256', $id . $site . config::get('lolcat.pass'));

			return url('https://www.devcf.com/a/api/lolcat/', array(
					'id' => $id,
					'site' => $site,
					'pass' => $pass,
				));

		}

		public function image_get_id($credits = NULL) {

			if ($credits === NULL) {
				$credits = intval($this->credit_get());
			}

			if ($credits < 1) {
				$credits = 1;
			}

			return ceil($credits / 100);

		}

		public function credit_get() {
			return config::get('lolcat.credits', 0); // Project should override with db a query.
		}

		public function credit_add($credits) {
		}

	}

?>