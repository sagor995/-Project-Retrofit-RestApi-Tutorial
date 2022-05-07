<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require '../vendor/autoload.php';
require '../includes/DbOperations.php';


$app = AppFactory::create();
$app->setBasePath("/MyApi/public");
$app->addErrorMiddleware(true, true, true);
$app->addBodyParsingMiddleware();
/*
    endpoint: createuser
    parameters: email, password, name, school
    method: POST
*/

$app->post('/createuser', function(Request $request, Response $response){
    
    if(!haveEmptyParameters(array('email','password','name','school'), $request, $response)){

        $request_data = $request->getParsedBody();

        $email = $request_data['email'];
        $password = $request_data['password'];
        $name = $request_data['name'];
        $school = $request_data['school'];

        //Encrypt password
        $hash_password = password_hash($password, PASSWORD_DEFAULT);

        $db = new DbOperations;
        $result = $db->createUser($email, $hash_password, $name, $school);

        if($result == USER_CREATED){
            
            $message = array();
            $message['error'] = false;
            $message['message'] = 'User created Successfully';
            $response->getBody()->write(json_encode($message));
            
            return $response
                    ->withHeader('Content-type', 'application/json')
                    ->withStatus(201);

        }else if($result == USER_FAILURE){
            $message = array();
            $message['error'] = true;
            $message['message'] = 'Some error occured';
            $response->getBody()->write(json_encode($message));
            
            return $response
                    ->withHeader('Content-type', 'application/json')
                    ->withStatus(422);

        }else if($result == USER_EXISTS){
            $message = array();
            $message['error'] = true;
            $message['message'] = 'User Already exists';
            $response->getBody()->write(json_encode($message));
            
            return $response
                    ->withHeader('Content-type', 'application/json')
                    ->withStatus(422);
        }
    }
    return $response
                    ->withHeader('Content-type', 'application/json')
                    ->withStatus(422);
});

$app->post('/userlogin', function(Request $request, Response $response){
    if(!haveEmptyParameters(array('email', 'password'), $request, $response)){
        $request_data = $request->getParsedBody();

        $email = $request_data['email'];
        $password = $request_data['password'];

        $db = new DbOperations;
        $result = $db->userLogin($email, $password);

        if($result == USER_AUTHENTICATED){
            $user = $db->getUserByEmail($email);

            $response_data = array();
            $response_data['error'] = false;
            $response_data['message'] = 'Login Successful';
            $response_data['user'] = $user;

            $response->getBody()->write(json_encode($response_data));
            return $response
                    ->withHeader('Content-type', 'application/json')
                    ->withStatus(200);
        }else if($result == USER_NOT_FOUND){
            $response_data = array();
            $response_data['error'] = true;
            $response_data['message'] = 'User not exists';

            $response->getBody()->write(json_encode($response_data));
            return $response
                    ->withHeader('Content-type', 'application/json')
                    ->withStatus(200);
        }else if($result == PASSWORD_DO_NOT_MATCH){
            $response_data = array();
            $response_data['error'] = true;
            $response_data['message'] = 'Invalud credential';

            $response->getBody()->write(json_encode($response_data));
            return $response
                    ->withHeader('Content-type', 'application/json')
                    ->withStatus(404);
        }
    }
    return $response
                    ->withHeader('Content-type', 'application/json')
                    ->withStatus(422);
});

$app->get('/allusers', function(Request $request, Response $response){
    $db = new DBOperations;

    $users = $db->getAllUsers();

    $response_data = array();

    $response_data['error'] = false;
    $response_data['users'] = $users;
 
    $response->getBody()->write(json_encode($response_data));

    return $response
                    ->withHeader('Content-type', 'application/json')
                    ->withStatus(422);
        
});

$app->put('/updateuser/{id}', function(Request $request, Response $response, array $args){

    $id = $args['id'];
    if(!haveEmptyParameters(array('email','name','school','id'), $request, $response)){
        
        $request_data = $request->getParsedBody();
        $email = $request_data['email'];
        $name = $request_data['name'];
        $school = $request_data['school'];

        $db = new DbOperations;

        if($db->updateUser($email, $name, $school, $id)){
            $response_data = array();
            $response_data['error'] = false;
            $response_data['message'] = 'User updated Successful';
            $user = $db->getUserByEmail($email);
            $response_data['user'] = $user;

            $response->getBody()->write(json_encode($response_data));

            return $response
                    ->withHeader('Content-type', 'application/json')
                    ->withStatus(200);
        }else{
            $response_data = array();
            $response_data['error'] = true;
            $response_data['message'] = 'Please try again later';
            $user = $db->getUserByEmail($email);
            $response_data['user'] = $user;

            $response->getBody()->write(json_encode($response_data));

            return $response
                    ->withHeader('Content-type', 'application/json')
                    ->withStatus(200);
        }
    }
    return $response
                    ->withHeader('Content-type', 'application/json')
                    ->withStatus(200);

});

$app->put('/updatepassword', function(Request $request, Response $response){
    if(!haveEmptyParameters(array('current_password','new_password','email'), $request, $response)){
        $request_data = $request->getParsedBody();
        $current_password = $request_data['current_password'];
        $new_password = $request_data['new_password'];
        $email = $request_data['email'];

        $db = new DbOperations;
        $result = $db->updatePassword($current_password, $new_password, $email);

        if($result == PASSWORD_CHANGED){
            $response_data = array();
            $response_data['error'] = false;
            $response_data['message'] = 'Password Changed';

            $response->getBody()->write(json_encode($response_data));

            return $response
                    ->withHeader('Content-type', 'application/json')
                    ->withStatus(200);
        }else if($result == PASSWORD_DO_NOT_MATCH){
            $response_data = array();
            $response_data['error'] = true;
            $response_data['message'] = 'You habe given wrong password';

            $response->getBody()->write(json_encode($response_data));

            return $response
                    ->withHeader('Content-type', 'application/json')
                    ->withStatus(200);
        }else if($result == PASSWORD_NOT_CHANGED){
            $response_data = array();
            $response_data['error'] = true;
            $response_data['message'] = 'Some error occured';

            $response->getBody()->write(json_encode($response_data));

            return $response
                    ->withHeader('Content-type', 'application/json')
                    ->withStatus(200);
        }

    }
        return $response
                    ->withHeader('Content-type', 'application/json')
                    ->withStatus(422);

});

$app->delete('/deleteuser/{id}', function(Request $request, Response $response, array $args){
    $id = $args['id'];
    $db = new DbOperations;

    $response_data = array();
    if($db->deleteUser($id)){
        $response_data['error'] = false;
        $response_data['message'] = 'User has been deleted';
    }else{
        $response_data['error'] = true;
        $response_data['message'] = 'Please try again later';
    }
    $response->getBody()->write(json_encode($response_data));

    return $response
            ->withHeader('Content-type', 'application/json')
            ->withStatus(200);
});

function haveEmptyParameters($required_params, $request, $response){
    $error = false;
    //if one param is empty it will be defined in below var.
    $error_params = '';
    $request_params = $request->getParsedBody();

    foreach($required_params as $param){
        //it is empty or having 0 length
        if(!isset($request_params[$param]) || strlen($request_params[$param])<=0){
            $error = true;
            $error_params .= $param . ', ';
        }
    }
    if($error){
        $error_detail = array();
        $error_detail['error'] = true;
        $error_detail['message'] = 'Required parameters '. substr($error_params, 0, -2). ' are either missing or empty';
        $response->getBody()->write(json_encode($error_detail));
    }
    return $error;
}

$app->run();
?>