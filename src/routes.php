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
        return $this->response->withJson(['error'=>false,'message'=>$user->user_id]);
    }
});

//get all articles
$app->get('/articles',function (Request $request, Response $response, array $args) {
	$sql = $this->db->prepare("select a.artikel_id as artikelid, u.nama as nama, a.imageurl, a.datecreated, a.isi from artikel a, user u where u.user_id = a.user_id");
	$sql->execute();
	$foods = $sql->fetchAll();
	return $this->response->withJson($foods);
});

//insert article
$app->post('/article/insert', function (Request $request, Response $response, array $args) {
	$input = $request->getParsedBody();
	$sql = $this->db->prepare("insert into artikel(user_id, judul, imageurl, isi)
														 values(:user_id, :judul, :imageurl, :isi)");
	$sql->bindParam("user_id",$input['user_id']);
	$sql->bindParam("judul",$input['judul']);
	$sql->bindParam("imageurl",$input['imageurl']);
	$sql->bindParam("isi",$input['isi']);
	$sql->execute();
	$status = $sql->rowCount();
	if(!$status){
		return $this->response->withJson(['error'=>true,'message'=>'Insert Failed']);
	}else{
		return $this->response->withJson(['error'=>false,'message'=>'Insert Success']);
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

//get log makan
$app->get('/food/log/{id}/{filter}', function (Request $request, Response $response, array $args) {
	$fil = $args['filter'] ."(tanggal) = " .$args['filter'] ."(CURRENT_DATE) and year(tanggal)=year(CURRENT_DATE)";
	$sql = $this->db->prepare("select lm.id_log, f.nama, lm.tipe, lm.tanggal, lm.satuan, f.kalori, f.berat
														 from logmakan lm, food f
														 where lm.id_food = f.id and lm.id_user=:id
														 and " .$fil);
	$sql->bindParam("id",$args['id']);
	$sql->execute();
	$listLog = $sql->fetchAll();
	return $this->response->withJson($listLog);
});

//insert log makan
$app->post('/food/log/insert', function (Request $request, Response $response, array $args) {
	$input = $request->getParsedBody();
	$sql = $this->db->prepare("insert into logmakan(id_user, id_food, tipe, satuan)
														 values(:id_user, :id_food, :tipe, :satuan)");
	$sql->bindParam("id_user",$input['id_user']);
	$sql->bindParam("id_food",$input['id_food']);
	$sql->bindParam("tipe",$input['tipe']);
	$sql->bindParam("satuan",$input['satuan']);
	$sql->execute();
	$status = $sql->rowCount();
	if(!$status){
		return $this->response->withJson(['error'=>true,'message'=>'Insert Failed']);
	}else{
		return $this->response->withJson(['error'=>false,'message'=>'Insert Success']);
	}
});

//update log makan
$app->post('/food/log/update/{id}', function (Request $request, Response $response, array $args) {
	$input = $request->getParsedBody();
	$sql = $this->db->prepare("update logmakan set id_food=:id_food,
														tipe=:tipe, satuan=:satuan
														where id_log=:id");
	$sql->bindParam("id",$args['id']);
	$sql->bindParam("id_food",$input['id_food']);
	$sql->bindParam("tipe",$input['tipe']);
	$sql->bindParam("satuan",$input['satuan']);
	$sql->execute();
	$status = $sql->rowCount();
	if(!$status){
		return $this->response->withJson(['error'=>true,'message'=>'Update Failed']);
	}else{
		return $this->response->withJson(['error'=>false,'message'=>'Update Success']);
	}
});

//delete log makan
$app->delete('/food/log/delete/{id}', function (Request $request, Response $response, array $args) {
		$sql = $this->db->prepare("delete from logmakan where id_log=:id");
		$sql->bindParam("id", $args['id']);
		$sql->execute();
		$status = $sql->rowCount();
		if(!$status){
			return $this->response->withJson(['error'=>true,'message'=>'Delete Failed']);
		}else{
			return $this->response->withJson(['error'=>false,'message'=>'Delete Success']);
		}
});

//get log workout
$app->get('/workout/log/{id}/{filter}', function (Request $request, Response $response, array $args) {
	$fil = $args['filter'] ."(tanggal) = " .$args['filter'] ."(CURRENT_DATE) and year(tanggal)=year(CURRENT_DATE)";
	$sql = $this->db->prepare("select lw.id_log, w.nama, lw.tanggal, lw.waktu as waktu_workout, w.waktu as waktu_def, w.kalori
														 from logworkout lw, workout w
														 where lw.id_workout = w.id and lw.id_user=:id
														 and " .$fil);
	$sql->bindParam("id",$args['id']);
	$sql->execute();
	$listLog = $sql->fetchAll();
	return $this->response->withJson($listLog);
});

//insert log workout
$app->post('/workout/log/insert', function (Request $request, Response $response, array $args) {
	$input = $request->getParsedBody();
	$sql = $this->db->prepare("insert into logworkout(id_user, id_workout, waktu)
														 values(:id_user, :id_workout, :waktu)");
	$sql->bindParam("id_user",$input['id_user']);
	$sql->bindParam("id_workout",$input['id_workout']);
	$sql->bindParam("waktu",$input['waktu']);
	$sql->execute();
	$status = $sql->rowCount();
	if(!$status){
		return $this->response->withJson(['error'=>true,'message'=>'Insert Failed']);
	}else{
		return $this->response->withJson(['error'=>false,'message'=>'Insert Success']);
	}
});

//update log workout
$app->post('/workout/log/update/{id}', function (Request $request, Response $response, array $args) {
	$input = $request->getParsedBody();
	$sql = $this->db->prepare("update logworkout 
														set id_workout=:id_workout, waktu=:waktu
														where id_log=:id");
	$sql->bindParam("id",$args['id']);
	$sql->bindParam("id_workout",$input['id_workout']);
	$sql->bindParam("waktu",$input['waktu']);
	$sql->execute();
	$status = $sql->rowCount();
	if(!$status){
		return $this->response->withJson(['error'=>true,'message'=>'Update Failed']);
	}else{
		return $this->response->withJson(['error'=>false,'message'=>'Update Success']);
	}
});

//delete log workout
$app->delete('/workout/log/delete/{id}', function (Request $request, Response $response, array $args) {
		$sql = $this->db->prepare("delete from logworkout where id_log=:id");
		$sql->bindParam("id", $args['id']);
		$sql->execute();
		$status = $sql->rowCount();
		if(!$status){
			return $this->response->withJson(['error'=>true,'message'=>'Delete Failed']);
		}else{
			return $this->response->withJson(['error'=>false,'message'=>'Delete Success']);
		}
});

$app->get('/[{name}]', function (Request $request, Response $response, array $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/' route");

    // Render index view
    return $this->renderer->render($response, 'index.phtml', $args);
});

//php -S 192.168.0.26:8080 -t public index.php