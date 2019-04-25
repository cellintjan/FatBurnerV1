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
            'dbname' => 'fatburner',
            'user' => 'root',
						'pass' => '',
        ],
				
				// Add lib jwt auth
				'jwt' => [
            'secret' => 'supersecretkeyforsoaclass',
        ],
    ],
];
