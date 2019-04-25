<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\UploadFile;

// Routes
$app->get('/users', function (Request $request, Response $response, array $args) {
		$sql = $this->db->prepare("select * from user");
		$sql->execute();
		$users = $sql->fetchAll();
		return $this->response->withJson($users);
});

$app->get('/[{name}]', function (Request $request, Response $response, array $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/' route");

    // Render index view
    return $this->renderer->render($response, 'index.phtml', $args);
});
