<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use App\Models\Mesa;
use App\Components\Resultado;
use \Firebase\JWT\JWT;

$app = AppFactory::create();

class MesaController{

    public function getAll(Request $request, Response $response) 
    {
        $rta = Mesa::get();

        if(count($rta) == 0){
            $result = new Resultado(true, "SIN MESAS", 200);
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
        $rta = Mesa::where('codigo',$args['codigo_mesa'])->first();

        if(!$rta){
            $result = new Resultado(true, "NO EXISTE LA MESA", 200);
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
        $mesa = new Mesa;

        if( !isset($_POST['codigo']) ){
            $result = new Resultado(false,"ERROR: FALTAN DATOS", 500);
            $response->getBody()->write(json_encode($result));
        }else{
            if( empty($parserBody['codigo']) ){

                $response->getBody()->write(json_encode("ERROR: DATOS INVALIDOS"));

            }else{
                $existMesa =  Mesa::where('codigo', trim(strtolower($parserBody['codigo'])))->first();
                if(empty($existMesa)){ // si no existe
                    $mesa->codigo = trim(strtolower($parserBody['codigo']));
                    $mesa->estado = "activo";
                    try {
                        $mesa->save();
                        $result = new Resultado(true, $mesa, 201);
                        $response->getBody()->write(json_encode($result)); // save devuelve true o false
                    } catch (\Throwable $th) {
                        $result = new Resultado(false,"ERROR: NO SE PUDO GUARDAR", 500);
                        $response->getBody()->write(json_encode($result));
                    }
                }else{
                    $result = new Resultado(false,"ERROR: MESA EXISTENTE ", 500);
                    $response->getBody()->write(json_encode($result));
                } 
            } 
        }
        return $response;
    }

    public function updateOne(Request $request, Response $response, $args)
    {
        $Key = "tpcomanda";
        $token = $_SERVER['HTTP_TOKEN'];
        $decode = JWT::decode($token,$Key,array('HS256'));     

        $mesa =  Mesa::where('id', $args['id'])->first();
        $parserBody = $request->getParsedBody();
        //var_dump($parserBody);
        //die();
        if($mesa){

            $estado = trim(strtolower($parserBody['estado']));
            
            if( isset($_PUT['estado']) || $parserBody['estado'] != ''){

                if( $estado == "activo" || $estado == "cerrado" || $estado == "con cliente esperando pedido" || $estado == "con clientes comiendo"  || $estado == "con clientes pagando" ){
                    
                    switch($estado){
                        case "cerrado":
                            if($decode->tipo != "socio"){
                                $result = new Resultado(false,"ERROR: SIN ACCESO", 500);
                                $response->getBody()->write(json_encode($result));
                            }else{
                                $mesa->estado = $estado;
                                try {
                                    $mesa->save();
                                    $result = new Resultado(true, $mesa, 201);
                                    $response->getBody()->write(json_encode($result));
                                } catch (\Throwable $th) {
                                    $result = new Resultado(false,"ERROR: NO SE PUDO GUARDAR", 500);
                                    $response->getBody()->write(json_encode($result));
                                }
                            }
                        break;
                        case "activo":
                        case "con cliente esperando pedido":
                        case "con clientes comiendo":
                        case "con clientes pagando":
                                $mesa->estado = $estado;
                                try {
                                    $mesa->save();
                                    $result = new Resultado(true, $mesa, 201);
                                    $response->getBody()->write(json_encode($result)); // save devuelve true o false
                                } catch (\Throwable $th) {
                                    $result = new Resultado(false,"ERROR: NO SE PUDO GUARDAR", 500);
                                    $response->getBody()->write(json_encode($result));
                                }
                        break;
                    }
                }else{
                    $result = new Resultado(false,"ERROR: TIPO DE ESTADO INVALIDO", 500);
                    $response->getBody()->write(json_encode($result));
                }

            }else{
                $result = new Resultado(false,"ERROR: FALTAN DATOS", 500);
                $response->getBody()->write(json_encode($result));
            }
        }else{
            $result = new Resultado(false,"ERROR: NO EXISTE EL USUARIO", 500);
            $response->getBody()->write(json_encode($result));
        }
        
        return $response;
    }

}