<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Usuario;
use \Firebase\JWT\JWT;
use Slim\Factory\AppFactory;
use App\Components\Resultado;
use App\Models\Login;

$app = AppFactory::create();

class LoginController{

    public function getAll(Request $request, Response $response) 
    {
        $rta = Login::get();

        if(count($rta) == 0){
            $result = new Resultado(true, "SIN CONECCIONES", 200);
            $response->getBody()->write(json_encode($result)); // save devuelve true o false
        }
        else{
            $result = new Resultado(true, $rta, 200);
            $response->getBody()->write(json_encode($result)); // save devuelve true o false
        }
        return $response;
    }

    public function login(Request $request, Response $response) 
    {

        $body = $request->getParsedBody();
        $email = $body['email'];
        $password = $body['password'];

        $exist =  Usuario::where('email', $email)->first();

        if(!empty($exist)){

           $usuario = json_decode($exist);
          // var_dump($usuario);
          // die();
           if($exist->password == $password){

            $login = new Login;
            
            $login->id_usuario = $usuario->id;

            try {
                $login->save();

                $Key = "tpcomanda";
                $payload = array(   
                    "id" => $usuario->id,
                    "email" => $usuario->email,
                    "nombre" => $usuario->nombre,
                    "tipo" => $usuario->tipo,
                    "estado" => $usuario->estado,
                    "sector" => $usuario->sector
                );
                $jwt = JWT::encode($payload,$Key);

                $result = new Resultado(true,"TOKEN: ". $jwt, 200);
                $response->getBody()->write(json_encode($result));


            } catch (\Throwable $th) {
                $result = new Resultado(false,"ERROR: NO SE PUDO CONECTAR", 500);
                $response->getBody()->write(json_encode($th));
            }
           }else{
            $result = new Resultado(false,"ERROR: PASSWORD INCORRECTO", 500);
            $response->getBody()->write(json_encode($result));
           }
        }else{
            $result = new Resultado(false,"ERROR: EMAIL NO EXISTE", 500);
            $response->getBody()->write(json_encode($result));
        } 
        
        return $response;
    }

}