<?php

return [
	'default' => env('LOG_CHANNEL', 'stack'),
	'channels' => [
		'runtime' => [
            'driver' => 'single',
            'formatter' => Monolog\Formatter\LineFormatter::class,
		    'formatter_with' => [
		        'format' => "[%datetime%] ::".getenv('APP_NAME').".".getenv('APP_ENV').".%level_name%:: %message%\n",
		    ],
            'path' => storage_path('logs/runtime.log'),
            'level' => 'debug'
        ],
		'activity' => [
			'driver' => 'single',
			'formatter' => Monolog\Formatter\LineFormatter::class,
		    'formatter_with' => [
		        'format' => "[%datetime%] ::".getenv('APP_NAME').".".getenv('APP_ENV').".%level_name%:: %message%\n",
		    ],
            'path' => storage_path('logs/activity.log'),
            'level' => 'debug'
		],
		'worker' => [
			'driver' => 'single',
			'formatter' => Monolog\Formatter\LineFormatter::class,
		    'formatter_with' => [
		        'format' => "[%datetime%] ::".getenv('APP_NAME').".".getenv('APP_ENV').".%level_name%:: %message%\n",
		    ],
            'path' => storage_path('logs/worker.log'),
            'level' => 'debug'
		]
	]
];