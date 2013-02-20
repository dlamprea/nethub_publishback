<?php
require('client.php');
require('GrantType/IGrantType.php');
require('GrantType/ClientCredentials.php');

$hostname_conexion = "localhost";
$database_conexion = "nethub_history";
$username_conexion = "root";
$password_conexion = "";
$conexion = mysql_pconnect($hostname_conexion, $username_conexion, $password_conexion) or trigger_error(mysql_error(),E_USER_ERROR);

const CLIENT_ID     			= 'OfJNa844VDadIBqV5L6e';
const CLIENT_SECRET 			= '145585291457462786241361051250';
const REDIRECT_URI           	= 'http://url/of/this.php';
const AUTHORIZATION_ENDPOINT 	= 'https://api.nethub.co/oauth2/token/';
const TOKEN_ENDPOINT         	= 'https://api.nethub.co/oauth2/token/';

$query_datos = "SELECT * FROM nethub_history.data_nethub LIMIT 0,1";
mysql_select_db($database_conexion, $conexion);
$consulta_datos = mysql_query($query_datos, $conexion) or die(mysql_error());
print("<pre>");

$client = new OAuth2\Client(CLIENT_ID, CLIENT_SECRET);
$params = array('code' => '', 'redirect_uri' => REDIRECT_URI);
$response = $client->getAccessToken(TOKEN_ENDPOINT, 'ClientCredentials', array());
$client->setAccessToken($response['result']['access_token']);
$client->setAccessTokenType(1);

while($row_consulta_datos = mysql_fetch_assoc($consulta_datos)){
	print_r("----------------------------------------------------------------------------------------------------------------------<br />");
	$identifiers = array(
		"facebook_id"=>$row_consulta_datos['facebook_id'], 
		"email"=>$row_consulta_datos['email']
	);
	
	$social = array(
					array(
						"label"=>"facebook",
						"url"=>$row_consulta_datos['link'],
						"id"=>$row_consulta_datos['facebook_id'],
						"username"=> (empty($row_consulta_datos['username']))?$row_consulta_datos['facebook_id']:$row_consulta_datos['username']
					)
			
			);
			
	if(strlen($row_consulta_datos['twitter_id'])>2){
		$identifiers["twitter_id"] = $row_consulta_datos['twitter_id'];
		$social+array(
				"twitter_id" => $row_consulta_datos['twitter_id']
					);
		}	
	if(strlen($row_consulta_datos['phone_mobile'])>2){
		$contact["phone_mobile"] = $row_consulta_datos['phone_mobile'];
		}
	$contact = array(
		"email"=>array(
			array(
				"label" => "facebook",
				"email" => $row_consulta_datos['email']
			)
		),
		"social"=>$social
	);
	
	$data = array(
				"first_name"=>$row_consulta_datos['first_name'],
				"last_name"=>$row_consulta_datos['last_name'],
				"gender"=>$row_consulta_datos['gender'],
				"password"=>md5($row_consulta_datos['email']),
				"email"=>$row_consulta_datos['email'],
				"birthday"=>$row_consulta_datos['birth_date'],
				"created"=>$row_consulta_datos['create_time'],
				"identifiers" => $identifiers,
				"contact" => $contact,
				"characterisitics"=>array("Residencia"=>$row_consulta_datos['location_name'],"location_id"=>$row_consulta_datos['location_id'],"canal_captura"=>$row_consulta_datos['Canal_captura'])
			);
	print_r($data);
	print_r("<br />");
	$create_response = $client->fetch('https://api.nethub.co/consumer/',
					json_encode($data,JSON_UNESCAPED_SLASHES),
					'POST'
					);
	print_r($create_response);
	//actualiza el nethubid en la tabla
	try {
		$idNethub = $create_response['result']['id'];
		$idTabla = $row_consulta_datos['iddata_nethub'];
		$insertSQL_cliente = " UPDATE nethub_history.data_nethub SET nethub_id = '$idNethub' WHERE iddata_nethub = '$idTabla' ";
		mysql_select_db($database_conexion, $conexion);
		$resultado_cliente = mysql_query($insertSQL_cliente, $conexion);
		
		//Envía la información de consumo
$query_datos_con = "SELECT * FROM nethub_history.data_marks WHERE id_usuario = '$idTabla'";
mysql_select_db($database_conexion, $conexion);
$consulta_datos_con = mysql_query($query_datos_con, $conexion) or die(mysql_error());
while($row_consulta_datos_con = mysql_fetch_assoc($consulta_datos_con)){
	$data_mark = array('consume'=>array($row_consulta_datos_con['mark']=>$row_consulta_datos_con['val']));
	print_r($data_mark);
	$response = $client->fetch('https://api.nethub.co/consumer/'.$idNethub,
			json_encode($data_mark,JSON_UNESCAPED_SLASHES),
			'PUT'
			);
}
mysql_free_result($consulta_datos_con);
		//Envía la información de consumo
	} catch (Exception $e) {
		$id_registro_nethub = 0;
	}
	//actualiza el nethubid en la tabla	
	print_r("----------------------------------------------------------------------------------------------------------------------");
}
mysql_free_result($consulta_datos);




//$client = new OAuth2\Client(CLIENT_ID, CLIENT_SECRET);
//$params = array('code' => '', 'redirect_uri' => REDIRECT_URI);
//$response = $client->getAccessToken(TOKEN_ENDPOINT, 'ClientCredentials', array());
//var_dump( $response);
//parse_str($response['result'], $info);
//print("<pre>");
//print_r($response['result']);
//print "-------token------";
//$client->setAccessToken($response['result']['access_token']);
//$client->setAccessTokenType(1);

//$response = $client->fetch('https://api.nethub.co/consumer/', array('return'=>'first_name','email'=>'diegolamprea@gmail.com'));
//$response = $client->fetch('https://api.nethub.co/consumer/', array('email'=>'alvarogalvis@gmail.com'));
//print_r($response);
/*$response = $client->fetch('https://api.nethub.co/consumer/', array('return'=>'id','email'=>'gustavo1@gmail.com','limit'=>'1'));
if (sizeof($response['result']['data'])>0){
	$id_registro_nethub = $response['result']['data']['0']['id'];
	$data = array("first_name"=>"Diego Armando","last_name"=>"Lamprea Molina","gender"=>"male","birthday"=>"1989/9/2");
	$update_response = $client->fetch('https://api.nethub.co/consumer/'.$id_registro_nethub,
		json_encode($data,JSON_UNESCAPED_SLASHES),
		'PUT'
		);
	print_r($update_response);
}else{
	$id_registro_nethub = "0";
	try {
		$data = array("first_name"=>"Gustavo","last_name"=>"Lamprea","email"=>"gustavo1@gmail.com","password"=>md5("gustavo1@gmail.com"),"gender"=>"male","birthday"=>"1986/4/12");
		$create_response = $client->fetch('https://api.nethub.co/consumer/',
			json_encode($data,JSON_UNESCAPED_SLASHES),
			'POST'
			);
		$id_registro_nethub = $create_response['result']['id'];
		} catch (Exception $e) {
			$id_registro_nethub = 0;
		}
}*/

//print_r($response['result']['data']);
//print_r(sizeof($response['result']['data']));



//print_r($response['result']['data']['count']);


/*$response = $client->fetch('https://api.nethub.co/consumer/', array('email'=>'diegolamprea@gmail.com'));

//$response = $client->fetch('https://api.nethub.co/consumer/', array('return'=>'first_name','email'=>'diegolamprea@gmail.com'));
print_r($response);*/
/*
print "-------get user------";
$data = array('consume' => array('read' => 'vision', 'using-rules' => 'si'));
$response = $client->fetch('https://api.nethub.co/consumer/440be68d-fd2f-4639-b0ba-22b6079bf6fd',
json_encode($data),
'PUT'
);
*/
/*$response = $client->fetch('https://api.nethub.co/consumer/', array('order'=>'first_name,DESC'));*/
//$response = $client->fetch('https://api.nethub.co/stats/consumer/count');

//var_dump($response, $response['result']);
/*
print_r($response);
print "-------set user------";*/