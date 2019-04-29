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

//register
$app->post('/register', function (Request $request, Response $response, array $args) {
    $input = $request->getParsedBody();
	$sql = $this->db->prepare("insert into user(email,password,goal,gender,weight,height,bloodsugar,cholesterol) values(:email,:password,:goal,:gender,:weight,:height,:bloodsugar,:cholesterol)");
    $sql->bindParam("email",$input['email']);
	$sql->bindParam("password",$input['password']);
	$sql->bindParam("goal",$input['goal']);
	$sql->bindParam("gender",$input['gender']);
	$sql->bindParam("weight",$input['weight']);
    $sql->bindParam("height",$input['height']);
	$sql->bindParam("bloodsugar",$input['bloodsugar']);
    $sql->bindParam("cholesterol",$input['cholesterol']);
	$sql->execute();
	$status = $sql->rowCount();
    if(!$status){
        return $this->response->withJson(['error'=>true,'message'=>'Register Failed']);
    }else{
        return $this->response->withJson(['error'=>false,'message'=>'Register Success']);
    }
});

//get all foods
$app->get('/foods',function (Request $request, Response $response, array $args) {
		$sql = $this->db->prepare("select * from food");
		$sql->execute();
		$foods = $sql->fetchAll();
		return $this->response->withJson($foods);
});

//get food category
$app->get('/food',function (Request $request, Response $response, array $args) {
		$sql = $this->db->prepare("select distinct kategori from food");
		$sql->execute();
		$kategori = $sql->fetchAll();
		return $this->response->withJson($kategori);
});

//get list food / category
$app->get('/food/{category}',function (Request $request, Response $response, array $args) {
		$sql = $this->db->prepare("select * from food where kategori = :category");
		$sql->bindParam("category", $args['category']);
		$sql->execute();
		$listFood = $sql->fetchAll();
		return $this->response->withJson($listFood);
});


$app->get('/[{name}]', function (Request $request, Response $response, array $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/' route");

    // Render index view
    return $this->renderer->render($response, 'index.phtml', $args);
});

//php -S 192.168.0.26:8080 -t public index.php