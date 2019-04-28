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

$app->post('/login', function (Request $request, Response $response, array $args) {
    $input = $request->getParsedBody();
    $sql = $this->db->prepare("select * from user where email=:email AND password=:password");
    $sql->bindParam("email",$input['email']);
    $sql->bindParam("password",$input['password']);
    $sql->execute();
    $user = $sql->fetchObject();
    if(!$user){
        return $this->response->withJson(['error'=>true,'message'=>'Invalid Email/Password']);
    }else{
        return $this->response->withJson(['error'=>false,'message'=>'Success']);
    }
});


$app->get('/[{name}]', function (Request $request, Response $response, array $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/' route");

    // Render index view
    return $this->renderer->render($response, 'index.phtml', $args);
});
