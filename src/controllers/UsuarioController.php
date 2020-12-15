<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use App\Models\Usuario;
use App\Components\Resultado;
use \Firebase\JWT\JWT;

$app = AppFactory::create();

class UsuarioController{

    public function getAll(Request $request, Response $response) 
    {
        $rta = Usuario::get();

        if(count($rta) == 0){
            $result = new Resultado(true, "SIN USUARIOS", 200);
            $response->getBody()->write(json_encode($result)); // save devuelve true o false
        }
        else{
            $result = new Resultado(true, $rta, 200);
            $response->getBody()->write(json_encode($result)); // save devuelve true o false
        }
        return $response;
    }
    public function getOne(Request $request, Response $response, $args) 
    {
        $rta = Usuario::where('id',$args['id_usuario'])->first();

        if(!$rta){
            $result = new Resultado(true, "NO EXISTE EL USUARII", 200);
            $response->getBody()->write(json_encode($result)); // save devuelve true o false
        }
        else{
            $result = new Resultado(true, $rta, 200);
            $response->getBody()->write(json_encode($result)); // save devuelve true o false
        }
        return $response;
    }
    public function addOne(Request $request, Response $response) 
    {
        $parserBody = $request->getParsedBody();
        $user = new Usuario;
        
        if( !isset($_POST['email']) || !isset($_POST['nombre']) || !isset($_POST['tipo']) || !isset($_POST['password']) ){
            $result = new Resultado(false,"ERROR: FALTAN DATOS", 500);
            $response->getBody()->write(json_encode($result));
        }else{

            if( empty($parserBody['email'])  || empty($parserBody['nombre']) || empty($parserBody['tipo']) || empty($_POST['password'])){

                $response->getBody()->write(json_encode("ERROR: DATOS INVALIDOS"));
                
            }else{
                $tipo = trim(strtolower($parserBody['tipo']));
                if(strlen($_POST['password']) >= 4){
                    if($tipo == "socio" || $tipo == "bartender" || $tipo == "cervecero" || $tipo == "cocinero" || $tipo == "mozo" ){

                        $existEmail =  Usuario::where('email', trim($parserBody['email']))->first();
    
                        if(empty($existEmail)){ // si no existe

                            switch($tipo){
                                case "mozo":
                                    $user->sector = "mesa";
                                break;
                                case "cocinero":
                                    $user->sector = "cocina";
                                break;
                                case "cervecero":
                                    $user->sector = "cerveza";
                                break;
                                case "bartender":
                                    $user->sector = "barra";
                                break;
                                case "socio":
                                    $user->sector = "admin";
                                break;
                                default:
                            break;
                            }

                            $user->email = trim(strtolower($parserBody['email']));
                            $user->nombre = trim(strtolower($parserBody['nombre']));
                            $user->tipo = $tipo;
                            $user->password = $parserBody['password'];
                            $user->estado = "libre";

                            try {
                                $user->save();
                                $result = new Resultado(true, $user, 201);
                                $response->getBody()->write(json_encode($result)); // save devuelve true o false
                            } catch (\Throwable $th) {
                                $result = new Resultado(false,"ERROR: NO SE PUDO GUARDAR", 500);
                                $response->getBody()->write(json_encode($result));
                            }
                        }else{
                            $result = new Resultado(false,"ERROR: EMAIL EXISTENTE ", 500);
                            $response->getBody()->write(json_encode($result));
                        } 
                    }else{
                        $result = new Resultado(false,"ERROR: TIPO DE USUARIO INVALIDO", 500);
                        $response->getBody()->write(json_encode($result));
                    }
                }else{
                    $result = new Resultado(false,"ERROR: CLAVE MUY CORTA", 500);
                    $response->getBody()->write(json_encode($result));
                }
                
            } 
        }
        return $response;
    }

    public function updateOne(Request $request, Response $response, $args)
    {     
     
        $parserBody = $request->getParsedBody();
        //var_dump($parserBody);
        //die();

        if( isset($_POST['sector'])){
            if( $parserBody['sector'] != ''){
                $sector = trim(strtolower( $parserBody['sector']));
                if( $sector == "cocina" || $sector == "barra" || $sector == "cerveza" || $sector == "mesa" ){
                          
                  $user =  Usuario::where('id', $args['id'])->first();
                      if($user){
                          $user->sector = $sector;
      
                          try {
                              $user->save();
                              $result = new Resultado(true, $user, 201);
                              $response->getBody()->write(json_encode($result)); // save devuelve true o false
                          } catch (\Throwable $th) {
                              $result = new Resultado(false,"ERROR: NO SE PUDO GUARDAR", 500);
                              $response->getBody()->write(json_encode($result));
                          }
                      }else{
                          $result = new Resultado(false,"ERROR: NO EXISTE EL USUARIO", 500);
                          $response->getBody()->write(json_encode($result));
                      }
                  }else{
                      $result = new Resultado(false,"ERROR: SECTOR INVALIDO", 500);
                      $response->getBody()->write(json_encode($result));
                  }

            }else{
                $result = new Resultado(false,"ERROR: SECTOR NO PUEDE ESTAR VACIO", 500);
                $response->getBody()->write(json_encode($result));
            }
        }else{
            $result = new Resultado(false,"ERROR: FALTAN DATOS", 500);
            $response->getBody()->write(json_encode($result));
        }

        
        return $response;
    }

    public function deleteOne(Request $request, Response $response, $args)
    {  
        $user =  Usuario::where('id', $args['id'])->first();

        if($user){
            $user->estado = "borrado";
            
            try {
                $user->save();
                $result = new Resultado(true, $user, 201);
                $response->getBody()->write(json_encode($result)); // save devuelve true o false
            } catch (\Throwable $th) {
                $result = new Resultado(false,"ERROR: NO SE PUDO GUARDAR", 500);
                $response->getBody()->write(json_encode($result));
            }
        }else{
            $result = new Resultado(false,"ERROR: NO EXISTE EL USUARIO", 500);
            $response->getBody()->write(json_encode($result));
        }
        
        return $response;
    }

}