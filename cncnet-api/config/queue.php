<?php

return [

	/*
	|--------------------------------------------------------------------------
	| Default Queue Driver
	|--------------------------------------------------------------------------
	|
	| The Laravel queue API supports a variety of back-ends via an unified
	| API, giving you convenient access to each back-end using the same
	| syntax for each one. Here you may set the default queue driver.
	|
	| Supported: "null", "sync", "database", "beanstalkd",
	|            "sqs", "iron", "redis"
	|
	*/

    'default' => env('QUEUE_DRIVER', 'redis'),

	/*
	|--------------------------------------------------------------------------
	| Queue Connections
	|--------------------------------------------------------------------------
	|
	| Here you may configure the connection information for each server that
	| is used by your application. A default configuration has been added
	| for each back-end shipped with Laravel. You are free to add more.
	|
	*/

	'connections' => [

		'sync' => [
			'driver' => 'sync',
		],

		'database' => [
			'driver' => 'database',
			'table' => 'jobs',
			'queue' => 'default',
			'retry_after' => 60,
		],

		'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_QUEUE_CONNECTION', 'default'),
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => (int) env('REDIS_QUEUE_RETRY_AFTER', 1800),
            'block_for' => null,
            'after_commit' => false,
		],

	],

	/*
	|--------------------------------------------------------------------------
	| Failed Queue Jobs
	|--------------------------------------------------------------------------
	|
	| These options configure the behavior of failed queue job logging so you
	| can control which database and table are used to store the jobs that
	| have failed. You may change them to any database / table you wish.
	|
	*/

	'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
		'database' => 'mysql',
        'table' => 'failed_jobs',
	],

];
