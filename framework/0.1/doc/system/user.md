# User helper

Intention is to overcome some of the issues with [login and passwords](../../doc/security/logins.md).

You can view the source on [GitHub](https://github.com/craigfrancis/framework/blob/master/framework/0.1/library/class/user/user.php).

Example setup

	class user extends user_base {

		//--------------------------------------------------
		// Setup

			// public function __construct() {
			//
			// 	$this->db_table_main = DB_PREFIX . 'user';
			// 	$this->db_table_session = DB_PREFIX . 'user_session';
			// 	$this->db_table_reset = DB_PREFIX . 'user_new_password';
			//
			// 	$this->setup();
			//
			// 	$this->session->length_set(60*30);
			// 	$this->session->history_length_set(60*60*24*30);
			// 	$this->session->allow_concurrent_set(false);
			//
			// 	$this->session_start();
			//
			// }

		//--------------------------------------------------
		// Custom fields

			// function field_name_get($form) {
			// 	$field_name = new form_field_text($form, 'Name');
			// 	$field_name->db_field_set('name');
			// 	$field_name->min_length_set('Your name is required.');
			// 	$field_name->max_length_set('Your name cannot be longer than XXX characters.');
			// 	return $field_name;
			// }

	}
