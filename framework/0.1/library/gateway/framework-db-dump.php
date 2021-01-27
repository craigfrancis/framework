<?php

//--------------------------------------------------
// Auth

	if (!gateway::framework_api_auth_check('framework-db-dump')) {
		exit('Invalid Auth' . "\n");
	}

//--------------------------------------------------
// Run

	// TODO [secrets] - Maybe?

?>