<?php

return [
	'routes' => [
		// Original routes
		['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
		['name' => 'page#caladd', 'url' => '/caladd', 'verb' => 'POST'],
		['name' => 'page#formbase', 'url' => '/form', 'verb' => 'GET'],
		['name' => 'page#formbasepost', 'url' => '/form', 'verb' => 'POST'],

		['name' => 'page#form', 'url' => '/pub/{token}/form', 'verb' => 'GET'],
		['name' => 'page#formpost', 'url' => '/pub/{token}/form', 'verb' => 'POST'],
		['name' => 'page#cncf', 'url' => '/pub/{token}/cncf', 'verb' => 'GET'],

		['name' => 'page#formemb', 'url' => '/embed/{token}/form', 'verb' => 'GET'],
		['name' => 'page#formpostemb', 'url' => '/embed/{token}/form', 'verb' => 'POST'],
		['name' => 'page#cncfemb', 'url' => '/embed/{token}/cncf', 'verb' => 'GET'],

		['name' => 'state#index', 'url' => '/state', 'verb' => 'POST'],

		['name' => 'calendars#calgetweek', 'url' => '/calgetweek', 'verb' => 'POST'],
		['name' => 'calendars#callist', 'url' => '/callist', 'verb' => 'GET'],

        ['name' => 'dir#index', 'url' => '/dir/{token}', 'verb' => 'GET'],
		['name' => 'dir#indexbase', 'url' => '/dir', 'verb' => 'GET'],
        ['name' => 'dir#indexv1', 'url' => '/pub/{token}/dir', 'verb' => 'GET'], # v1 legacy

		['name' => 'debug#index', 'url' => '/debug', 'verb' => 'POST'],
		
		// Therapist routes
		['name' => 'therapist#getAll', 'url' => '/api/therapists', 'verb' => 'GET'],
		['name' => 'therapist#get', 'url' => '/api/therapists/{id}', 'verb' => 'GET'],
		['name' => 'therapist#getSchedule', 'url' => '/api/therapists/{id}/schedule', 'verb' => 'GET'],
		['name' => 'therapist#updateSchedule', 'url' => '/api/therapists/{id}/schedule', 'verb' => 'PUT'],
		['name' => 'therapist#becomeTherapist', 'url' => '/api/therapists/become', 'verb' => 'POST'],
		['name' => 'therapist#updateProfile', 'url' => '/api/therapists/profile', 'verb' => 'PUT'],
		
		// Appointment routes
		['name' => 'appointment#getAll', 'url' => '/api/appointments', 'verb' => 'GET'],
		['name' => 'appointment#get', 'url' => '/api/appointments/{id}', 'verb' => 'GET'],
		['name' => 'appointment#create', 'url' => '/api/appointments', 'verb' => 'POST'],
		['name' => 'appointment#update', 'url' => '/api/appointments/{id}', 'verb' => 'PUT'],
		['name' => 'appointment#delete', 'url' => '/api/appointments/{id}', 'verb' => 'DELETE'],
		['name' => 'appointment#getAnalytics', 'url' => '/api/appointments/analytics', 'verb' => 'GET'],
		['name' => 'appointment#getPracticeAnalytics', 'url' => '/api/appointments/analytics/practice', 'verb' => 'GET'],
		
		// Billing routes
		['name' => 'billing#createInvoice', 'url' => '/api/invoices', 'verb' => 'POST'],
		['name' => 'billing#getInvoice', 'url' => '/api/invoices/{id}', 'verb' => 'GET'],
		['name' => 'billing#createSuperbill', 'url' => '/api/superbills', 'verb' => 'POST'],
		['name' => 'billing#getSuperbill', 'url' => '/api/superbills/{id}', 'verb' => 'GET'],
		['name' => 'billing#processPayment', 'url' => '/api/payments', 'verb' => 'POST'],
		['name' => 'billing#getSquareCredentials', 'url' => '/api/square/credentials', 'verb' => 'GET'],
		['name' => 'billing#setSquareCredentials', 'url' => '/api/square/credentials', 'verb' => 'POST'],
	]
];
