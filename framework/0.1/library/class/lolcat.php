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

		public function image_url_get($credits = NULL) {

			$id = $this->image_id_get($credits);
			$site = config::get('lolcat.site');
			$pass = hash('sha256', $id . $site . config::get('lolcat.pass'));

			return url('https://www.devcf.com/a/api/lolcat/', array(
					'id' => $id,
					'site' => $site,
					'pass' => $pass,
				));

		}

		public function image_id_get($credits = NULL) {

			if ($credits === NULL) {
				$credits = $this->credit_get();
			}

			$credits = floor($credits); // 4.9 is not enough for image 5.

			if ($credits < 0) {
				$credits = 0;
			}

			return ($credits + 1); // No credits still gets image 1

		}

		public function credit_get() {
			return config::get('lolcat.credits', 0); // Project should override with db a query.
		}

		public function credit_add($credits) {
		}

	}

?>