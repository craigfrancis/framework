<?php

namespace Fuel\Core;

class Migration_Create_votes extends Migration {

	function up()
	{
		DBUtil::create_table('votes', array(
			'id' => array('type' => 'int', 'constraint' => 11, 'auto_increment' => true),
			'twit_id' => array('type' => 'int', 'constraint' => 11),
			'is_fucktard' => array('type' => 'int', 'constraint' => 11),

		), array('id'));
	}

	function down()
	{
		DBUtil::drop_table('votes');
	}
}