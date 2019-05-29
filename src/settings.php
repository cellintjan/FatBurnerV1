<?php
return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header
				'upload_directory' => __DIR__ . '/../public/upload', //buat upload gambar
        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__ . '/../templates/',
        ],

        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
            'level' => \Monolog\Logger::DEBUG,
        ],
				
				// Database setting
				'db' => [
            'host' => 'localhost',
            'dbname' => 'u346426447_piku',
            'user' => 'u346426447_piku',
						'pass' => 'techno2019',
        ],
				
				// Add lib jwt auth
				'jwt' => [
            'secret' => 'topsecretagent',
        ],
    ],
];
