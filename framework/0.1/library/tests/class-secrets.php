<?php


		//--------------------------------------------------
		// Testing

// 			private $test_mode = false;
//
// [...]
//
// 			public static function _test_mode_enable() {
// 				if (!str_starts_with(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file'], FRAMEWORK_ROOT)) {
// 					trigger_error('Only the framework can enable the secrets test mode', E_USER_ERROR);
// 					exit();
// 				}
// 				$obj = secrets::instance_get();
// 				$obj->test_mode = true;
// 			}
//
// 			public static function _test_file_path() {
// 				$obj = secrets::instance_get();
// print_r($obj->file_path);
// 				if ($obj->test_mode === true) {
// 					return $obj->file_path;
// 				}
// 			}
//
// 			public static function _test_reset() {
// 				$obj = secrets::instance_get();
// 				if ($obj->test_mode === true) {
// 				}
// 			}


//--------------------------------------------------
// Setup

	$secrets = [];
	$secrets['test_str_1']          = ['type' => 'str'];
	$secrets['test_str_2']          = ['type' => 'str'];
	$secrets['test_str_3']          = ['type' => 'str'];
	$secrets['test_key_symmetric_1']  = ['type' => 'key'];
	$secrets['test_key_symmetric_2']  = ['type' => 'key'];
	$secrets['test_key_symmetric_3']  = ['type' => 'key', 'key_type' => 'symmetric'];
	$secrets['test_key_asymmetric_1'] = ['type' => 'key', 'key_type' => 'asymmetric'];
	$secrets['test_key_asymmetric_2'] = ['type' => 'key', 'key_type' => 'asymmetric'];
	$secrets['test_key_asymmetric_3'] = ['type' => 'key', 'key_type' => 'asymmetric'];

	// config::set('secrets.prefix', 'TEST');

	secrets::_test_mode_enable();

	secrets::setup($secrets);

//--------------------------------------------------
//

	print_r(secrets::_test_file_path());


	// print_r(secrets::get('test_str_1'));





	exit('DONE?');

?>