<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\UploadedFile;
use Firebase\JWT\JWT;

header('Access-Control-Allow-Origin: *');

header('Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE');

header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

//register
$app->post('/register', function (Request $request, Response $response, array $args) {
    $input = $request->getParsedBody();
    $apikey = rand(10000,99999);
	$sql = $this->db->prepare("insert into user(email,password,name,goal,gender,weight,height,bloodsugar,cholesterol,tipe,apikey,calorie) values(:email,:password,:name,:goal,:gender,:weight,:height,:bloodsugar,:cholesterol,:tipe,:apikey,:calorie)");
    $sql->bindParam("email",$input['email']);
	$sql->bindParam("password",$input['password']);
	$sql->bindParam("name",$input['name']);
	$sql->bindParam("goal",$input['goal']);
	$sql->bindParam("gender",$input['gender']);
	$sql->bindParam("weight",$input['weight']);
    $sql->bindParam("height",$input['height']);
	$sql->bindParam("bloodsugar",$input['bloodsugar']);
    $sql->bindParam("cholesterol",$input['cholesterol']);
    $sql->bindParam("apikey",$apikey);
    $sql->bindParam("calorie",$input['calorie']);
    if(isset($input['tipe'])) $sql->bindParam("tipe",$input['tipe']);
    else {
        $temp = "Free";
        $sql->bindParam("tipe",$temp);
    }
	$sql->execute();
	$status = $sql->rowCount();
    if(!$status){
        return $this->response->withJson(['error'=>true,'message'=>'Register Failed']);
    }else{
        return $this->response->withJson(['error'=>false,'message'=>'Register Success']);
    }
});

//login
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
		// Buat JWT
		$setting = $this->get('settings');
		// Membuat token baru
		$token = JWT::encode(['id' => $user->user_id, 'email' => $user->email], $setting['jwt']['secret'], "HS256");
		return $this->response->withJson(['error'=>false,'message'=>["token"=>$token,"apikey"=>$user->apikey,"userid"=>$user->user_id,"weight"=>$user->weight,"name"=>$user->name,"goal"=>$user->goal]]);
	}
});

function updateRequest($apikey,$default,$app){
    $sql = $app->db->prepare("select request,tipe from user where apikey = :apikey");
    $sql->bindParam("apikey",$apikey);
    $sql->execute();   
    $hasil = $sql->fetchObject();
    
    if($hasil){
        $req = intval($hasil->request) + 1;
        if($hasil->tipe=="Free" && $req>50){
            $result = ['error'=>true, 'message'=>'Request exceeded maximum limit. Try again tomorrow!'];
        }else{  
            $sql = $app->db->prepare("update user set request=:req where apikey = :apikey");
            $sql->bindParam("apikey",$apikey);
            $sql->bindParam("req",$req);
            $sql->execute(); 
            $result = $default;
        }
    }else{
        $result = ['error'=>true,'message'=>'Apikey does not match'];
    }
    return $result;
}

//get notification
$app->get('/notification', function (Request $request, Response $response, array $args) {
	$sql = $this->db->prepare("SELECT s.id_log,f.nama,s.tipe FROM schedule s,food f WHERE s.id_food = f.id AND s.time < CURRENT_TIMESTAMP() AND s.isdone = false");
	
	$sql->bindParam("id",$args['id']);
	$sql->execute();
	$listLog = $sql->fetchAll();
	
	if(isset($args['apikey'])){
       $listLog = updateRequest($args['apikey'],$listLog,$this);
    }
	
	return $this->response->withJson($listLog);
});

//update notification
$app->post('/notification/update/{id}', function (Request $request, Response $response, array $args) {
	$input = $request->getParsedBody();
	$status = "1";
	
	if($input['apikey']!=""){
       $status = updateRequest($input['apikey'],"1",$this);
    }
	
	if($status!="1"){
		return $this->response->withJson($status);
	}else{
	    $sql = $this->db->prepare("update schedule set isdone=true where id_log=:id");
		$sql->bindParam("id",$args['id']);
		$sql->execute();
		$status = $sql->rowCount();
		
		if(!$status){
			return $this->response->withJson(['error'=>true,'message'=>'Update Failed']);
		}else{
			return $this->response->withJson(['error'=>false,'message'=>'Update Success']);
		}
	}
});
	
$app->group('/api', function (\Slim\App $app) {
    
    //update firebase token
	$app->post('/firebase/{id}', function (Request $request, Response $response, array $args) {
		$input = $request->getParsedBody();
		$status = "1";
		
		if($input['apikey']!=""){
	       $status = updateRequest($input['apikey'],"1",$this);
	    }
		
		if($status!="1"){
			return $this->response->withJson($status);
		}else{
		    $sql = $this->db->prepare("update user set firebase_key=:firebase_key
															where user_id=:id");
    		$sql->bindParam("id",$args['id']);
    		$sql->bindParam("firebase_key",$input['firebase_key']);
    		$sql->execute();
    		$status = $sql->rowCount();
    		
    		if(!$status){
    			return $this->response->withJson(['error'=>true,'message'=>'Update Failed']);
    		}else{
    			return $this->response->withJson(['error'=>false,'message'=>'Update Success']);
    		}
		}
	});
	
	//get schedule
	$app->get('/schedule/log/{id}/{filter}/[{apikey}]', function (Request $request, Response $response, array $args) {
		$sql = $this->db->prepare("SELECT f.nama,s.id_log,s.tipe FROM schedule s,food f WHERE s.id_food = f.id AND STR_TO_DATE(s.time,'%d-%m-%Y') = CURDATE() AND s.isdone = false AND s.id_user=:id");
		
		
		$sql->bindParam("id",$args['id']);
		$sql->execute();
		$listLog = $sql->fetchAll();
		
		if(isset($args['apikey'])){
	       $listLog = updateRequest($args['apikey'],$listLog,$this);
	    }
		
		return $this->response->withJson($listLog);
	});
	
	//insert schedule
	$app->post('/schedule/insert', function (Request $request, Response $response, array $args) {
		$input = $request->getParsedBody();
		$status = "1";
		
		if($input['apikey']!=""){
	       $status = updateRequest($input['apikey'],"1",$this);
	    }
		
		if($status!="1"){
			return $this->response->withJson($status);
		}else{
		     $sql = $this->db->prepare("insert into schedule(id_user, id_food, time, isdone,tipe)
															 values(:id_user, :id_food, :time,false,:tipe)");
    		$sql->bindParam("id_user",$input['id_user']);
    		$sql->bindParam("id_food",$input['id_food']);
    		$sql->bindParam("time",$input['time']);
    		$sql->bindParam("tipe",$input['tipe']);
    		$sql->execute();
    		$status = $sql->rowCount();
    		
    		if(!$status){
    			return $this->response->withJson(['error'=>true,'message'=>'Insert Failed']);
    		}else{
    			return $this->response->withJson(['error'=>false,'message'=>'Insert Success']);
    		}
		}
	});
	
	//delete log makan
	$app->delete('/schedule/delete/{id}/[{apikey}]', function (Request $request, Response $response, array $args) {
		$status = "1";
		if(isset($args['apikey'])){
		    $status = updateRequest($args['apikey'],"1",$this);
		}
		
		if($status!="1"){
			return $this->response->withJson($status);
		}else{
		    $sql = $this->db->prepare("delete from schedule where id_log=:id");
			$sql->bindParam("id", $args['id']);
			$sql->execute();
			$status = $sql->rowCount();
			
			if(!$status){
				return $this->response->withJson(['error'=>true,'message'=>'Delete Failed']);
			}else{
				return $this->response->withJson(['error'=>false,'message'=>'Delete Success']);
			}
		}
	});
	
	//get all articles
	$app->get('/articles/[{apikey}]',function (Request $request, Response $response, array $args) {
	    $sql = $this->db->prepare("select a.artikel_id as artikelid, a.judul as judul, u.name as nama, a.imageurl, a.datecreated, a.isi from artikel a, user u where u.user_id = a.user_id ORDER BY artikelid DESC");
		$sql->execute();
		$foods = $sql->fetchAll();  
		
	    if(isset($args['apikey'])){
	       $foods = updateRequest($args['apikey'],$foods,$this);
	    }
        return $this->response->withJson($foods);
	});
	
	//get article by id
	$app->get('/article/{id}/[{apikey}]',function (Request $request, Response $response, array $args) {
	    $sql = $this->db->prepare("select * from artikel where artikel_id=:id");
	    $sql->bindParam("id",$args['id']);
		$sql->execute();
		$foods = $sql->fetchAll();  
		
	    if(isset($args['apikey'])){
	       $foods = updateRequest($args['apikey'],$foods,$this);
	    }
        return $this->response->withJson($foods);
	});
	
	//insert article
	$app->post('/article/insert', function (Request $request, Response $response, array $args) {
		$input = $request->getParsedBody();
		$uploadedFiles = $request->getUploadedFiles();
		$status = "1";
		
		if($input['apikey']!=""){
	       $status = updateRequest($input['apikey'],"1",$this);
	    }
		
		if($status!="1"){
			return $this->response->withJson($status);
		}else{
            $sql = $this->db->prepare("select AUTO_INCREMENT FROM information_schema.TABLES
                                       where table_schema = 'u346426447_piku' and table_name='artikel'");
            $sql->execute();
            $id = $sql->fetchObject()->AUTO_INCREMENT;
		    
		    $sql = $this->db->prepare("insert into artikel(user_id, judul, isi)
															 values(:user_id, :judul, :isi)");
    		$sql->bindParam("user_id",$input['user_id']);
    		$sql->bindParam("judul",$input['judul']);
    		$sql->bindParam("isi",$input['isi']);
    		$sql->execute();
    		$status = $sql->rowCount();
    		
    		if(!$status){
			    return $this->response->withJson(['error'=>true,'message'=>'Insert Failed']);
    		}else{
    		    $uploadedFile = $uploadedFiles['imageurl'];
        		if (!empty($uploadedFile)){
                    $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
                    $filename = sprintf('%s.%0.8s', $id, $extension);
                    
                    $directory = $this->get('settings')['upload_directory'];
                    $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);
                }
			    return $this->response->withJson(['error'=>false,'message'=>'Insert Success']);
    		}
		}
	});
	
	//update article
	$app->post('/article/update/{id}', function (Request $request, Response $response, array $args) {
		$input = $request->getParsedBody();
		$status = "1";
		
		if($input['apikey']!=""){
	       $status = updateRequest($input['apikey'],"1",$this);
	    }
		
		if($status!="1"){
			return $this->response->withJson($status);
		}else{
		    $sql = $this->db->prepare("update artikel set judul=:judul,
		                               isi=:isi where artikel_id=:id");
    		$sql->bindParam("id",$args['id']);
    		$sql->bindParam("judul",$input['judul']);
    		$sql->bindParam("isi",$input['isi']);
    		$sql->execute();
    		$status = $sql->rowCount();
    		
    		if(!$status){
    			return $this->response->withJson(['error'=>true,'message'=>'Update Failed']);
    		}else{
    			return $this->response->withJson(['error'=>false,'message'=>'Update Success']);
    		}
		}
	});
	
	//delete article
	$app->delete('/article/delete/{id}/[{apikey}]', function (Request $request, Response $response, array $args) {
		$status = "1";
		if(isset($args['apikey'])){
		    $status = updateRequest($args['apikey'],"1",$this);
		}
		
		if($status!="1"){
			return $this->response->withJson($status);
		}else{
		    $sql = $this->db->prepare("delete from artikel where artikel_id=:id");
			$sql->bindParam("id", $args['id']);
			$sql->execute();
			$status = $sql->rowCount();
			
			if(!$status){
				return $this->response->withJson(['error'=>true,'message'=>'Delete Failed']);
			}else{
				return $this->response->withJson(['error'=>false,'message'=>'Delete Success']);
			}
		}
	});
	
	//get all foods
	$app->get('/foods/[{apikey}]',function (Request $request, Response $response, array $args) {
			$sql = $this->db->prepare("select * from food");
			$sql->execute();
			$foods = $sql->fetchAll();
			
			if(isset($args['apikey'])){
    	       $foods = updateRequest($args['apikey'],$foods,$this);
    	    }
			return $this->response->withJson($foods);
	});
	
	//get food category
	$app->get('/food/[{apikey}]',function (Request $request, Response $response, array $args) {
			$sql = $this->db->prepare("select distinct kategori from food");
			$sql->execute();
			$kategori = $sql->fetchAll();
			
			if(isset($args['apikey'])){
    	       $kategori = updateRequest($args['apikey'],$kategori,$this);
    	    }
			return $this->response->withJson($kategori);
	});
	
	//get list food / category
	$app->get('/food/{category}/[{apikey}]',function (Request $request, Response $response, array $args) {
			$sql = $this->db->prepare("select * from food where kategori = :category");
			$sql->bindParam("category", $args['category']);
			$sql->execute();
			$listFood = $sql->fetchAll();
			
			if(isset($args['apikey'])){
    	       $listFood = updateRequest($args['apikey'],$listFood,$this);
    	    }
			return $this->response->withJson($listFood);
	});
	
	//get log makan
	$app->get('/food/log/{id}/{filter}/[{apikey}]', function (Request $request, Response $response, array $args) {
		$fil = $args['filter'] ."(tanggal) = " .$args['filter'] ."(CURRENT_DATE) and year(tanggal)=year(CURRENT_DATE)";
		$sql = $this->db->prepare("select lm.id_log, f.nama, lm.tipe, lm.tanggal, lm.satuan as jumlah, f.satuan, f.kalori, f.berat
															 from logmakan lm, food f
															 where lm.id_food = f.id and lm.id_user=:id
															 and " .$fil);
		$sql->bindParam("id",$args['id']);
		$sql->execute();
		$listLog = $sql->fetchAll();
		
		if(isset($args['apikey'])){
	       $listLog = updateRequest($args['apikey'],$listLog,$this);
	    }
		
		return $this->response->withJson($listLog);
	});
	
	//insert log makan
	$app->post('/food/log/insert', function (Request $request, Response $response, array $args) {
		$input = $request->getParsedBody();
		$status = "1";
		
		if($input['apikey']!=""){
	       $status = updateRequest($input['apikey'],"1",$this);
	    }
		
		if($status!="1"){
			return $this->response->withJson($status);
		}else{
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
		}
	});
	
	//update log makan
	$app->post('/food/log/update/{id}', function (Request $request, Response $response, array $args) {
		$input = $request->getParsedBody();
		$status = "1";
		
		if($input['apikey']!=""){
	       $status = updateRequest($input['apikey'],"1",$this);
	    }
		
		if($status!="1"){
			return $this->response->withJson($status);
		}else{
		    $sql = $this->db->prepare("update logmakan set satuan=:satuan
															where id_log=:id");
    		$sql->bindParam("id",$args['id']);
    		$sql->bindParam("satuan",$input['satuan']);
    		$sql->execute();
    		$status = $sql->rowCount();
    		
    		if(!$status){
    			return $this->response->withJson(['error'=>true,'message'=>'Update Failed']);
    		}else{
    			return $this->response->withJson(['error'=>false,'message'=>'Update Success']);
    		}
		}
	});
	
	//delete log makan
	$app->delete('/food/log/delete/{id}/[{apikey}]', function (Request $request, Response $response, array $args) {
		$status = "1";
		if(isset($args['apikey'])){
		    $status = updateRequest($args['apikey'],"1",$this);
		}
		
		if($status!="1"){
			return $this->response->withJson($status);
		}else{
		    $sql = $this->db->prepare("delete from logmakan where id_log=:id");
			$sql->bindParam("id", $args['id']);
			$sql->execute();
			$status = $sql->rowCount();
			
			if(!$status){
				return $this->response->withJson(['error'=>true,'message'=>'Delete Failed']);
			}else{
				return $this->response->withJson(['error'=>false,'message'=>'Delete Success']);
			}
		}
	});
	
	//get all workout
	$app->get('/workouts/[{apikey}]',function (Request $request, Response $response, array $args) {
		$sql = $this->db->prepare("select * from workout");
		$sql->execute();
		$foods = $sql->fetchAll();
		
		if(isset($args['apikey'])){
	       $foods = updateRequest($args['apikey'],$foods,$this);
	    }
		
		return $this->response->withJson($foods);
	});
	
	//get log workout
	$app->get('/workout/log/{id}/{filter}/[{apikey}]', function (Request $request, Response $response, array $args) {
		$fil = $args['filter'] ."(tanggal) = " .$args['filter'] ."(CURRENT_DATE) and year(tanggal)=year(CURRENT_DATE)";
		$sql = $this->db->prepare("select lw.id_log, w.nama, lw.tanggal, lw.waktu as waktu_workout, w.waktu as waktu_def, w.kalori
															 from logworkout lw, workout w
															 where lw.id_workout = w.id and lw.id_user=:id
															 and " .$fil);
		$sql->bindParam("id",$args['id']);
		$sql->execute();
		$listLog = $sql->fetchAll();
		
		if(isset($args['apikey'])){
	       $listLog = updateRequest($args['apikey'],$listLog,$this);
	    }
		return $this->response->withJson($listLog);
	});
	
	//insert log workout
	$app->post('/workout/log/insert', function (Request $request, Response $response, array $args) {
		$input = $request->getParsedBody();
		$status = "1";
		
		if($input['apikey']!=""){
	       $status = updateRequest($input['apikey'],"1",$this);
	    }
		
		if($status!="1"){
			return $this->response->withJson($status);
		}else{
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
		}
	});
	
	//update log workout
	$app->post('/workout/log/update/{id}', function (Request $request, Response $response, array $args) {
		$input = $request->getParsedBody();
		$status = "1";
		
		if($input['apikey']!=""){
	       $status = updateRequest($input['apikey'],"1",$this);
	    }
		
		if($status!="1"){
			return $this->response->withJson($status);
		}else{
		    $sql = $this->db->prepare("update logworkout 
															set waktu=:waktu
															where id_log=:id");
    		$sql->bindParam("id",$args['id']);
    		$sql->bindParam("waktu",$input['waktu']);
    		$sql->execute();
    		$status = $sql->rowCount();
    		
    		if(!$status){
    			return $this->response->withJson(['error'=>true,'message'=>'Update Failed']);
    		}else{
    			return $this->response->withJson(['error'=>false,'message'=>'Update Success']);
    		}
		}
	});
	
	//delete log workout
	$app->delete('/workout/log/delete/{id}/[{apikey}]', function (Request $request, Response $response, array $args) {
		$status = "1";
		if(isset($args['apikey'])){
		    $status = updateRequest($args['apikey'],"1",$this);
		}
		
		if($status!="1"){
			return $this->response->withJson($status);
		}else{
		   $sql = $this->db->prepare("delete from logworkout where id_log=:id");
			$sql->bindParam("id", $args['id']);
			$sql->execute();
			$status = $sql->rowCount();
			
			if(!$status){
				return $this->response->withJson(['error'=>true,'message'=>'Delete Failed']);
			}else{
				return $this->response->withJson(['error'=>false,'message'=>'Delete Success']);
			}
		}
	});
	
	//get calories goal
	$app->get('/calorie/{id}', function (Request $request, Response $response, array $args) {
		$sql = $this->db->prepare("select calorie,weight,height,bloodsugar,cholesterol,goal from user where user_id = :userid");
		$sql->bindParam("userid",$args['id']);
		$sql->execute();
		$calorie = $sql->fetchObject();
		return $this->response->withJson($calorie);
	});
	
	//update calorie
	$app->post('/calorie/update/{userid}', function (Request $request, Response $response, array $args) {
		$input = $request->getParsedBody();
		$sql = $this->db->prepare("update user 
															set calorie=:calorie,
															weight=:weight,
															height=:height,
															bloodsugar=:bloodsugar,
															cholesterol=:cholesterol,
															goal=:goal
															where user_id=:userid");
		$sql->bindParam("userid",$args['userid']);
		$sql->bindParam("calorie",$input['calorie']);
		$sql->bindParam("weight",$input['weight']);
		$sql->bindParam("height",$input['height']);
		$sql->bindParam("bloodsugar",$input['bloodsugar']);
		$sql->bindParam("cholesterol",$input['cholesterol']);
		$sql->bindParam("goal",$input['goal']);
		$sql->execute();
		$status = $sql->rowCount();
		if(!$status){
			return $this->response->withJson(['error'=>true,'message'=>'Update Failed']);
		}else{
			return $this->response->withJson(['error'=>false,'message'=>'Update Success']);
		}
	});
	
	$app->post('/password/update/{userid}', function (Request $request, Response $response, array $args) {
		$input = $request->getParsedBody();
		$sql = $this->db->prepare("update user 
															set password=:password
															where user_id=:userid");
		$sql->bindParam("userid",$args['userid']);
		$sql->bindParam("password",$input['password']);
		$sql->execute();
		$status = $sql->rowCount();
		if(!$status){
			return $this->response->withJson(['error'=>true,'message'=>'Update Failed']);
		}else{
			return $this->response->withJson(['error'=>false,'message'=>'Update Success']);
		}
	});
});



$app->get('/[{name}]', function (Request $request, Response $response, array $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/' route");

    // Render index view
    return $this->renderer->render($response, 'index.phtml', $args);
});

//php -S 192.168.0.26:8080 -t public index.php