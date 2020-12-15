<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
//use App\Models\Usuario;
use \Firebase\JWT\JWT;
use Slim\Factory\AppFactory;
use App\Components\Resultado;
use App\Models\Comanda;
use App\Models\Pedido;
use App\Models\Menu;

$app = AppFactory::create();

class PedidoController{

    public function getAll(Request $request, Response $response) 
    {
        $rta = Pedido::get();

        if(count($rta) == 0){
            $result = new Resultado(true, "SIN PEDIDOS", 200);
            $response->getBody()->write(json_encode($result)); 
        }
        else{
            $result = new Resultado(true, $rta, 200);
            $response->getBody()->write(json_encode($result));
        }
        return $response;
    }

    //Trae el primer pedido con ese codigo
    public function getOne(Request $request, Response $response, $args) 
    {
        $rta = Pedido::where('codigo',$args['codigo_pedido'])->get();

        if(count($rta) == 0){
            $result = new Resultado(true, "NO EXISTE EL PEDIDO", 200);
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
       // var_dump($parserBody);
        //die;

        if( !isset($_POST['codigo']) ){
            $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            // Output: 54esmdr0qf
            //echo substr(str_shuffle($permitted_chars), 0, 5);
            //die;
            $codigo = substr(str_shuffle($permitted_chars), 0, 5);
            $pedidoExiste =  Pedido::where('codigo', $codigo)->first();

            while($pedidoExiste != null){
                $codigo = substr(str_shuffle($permitted_chars), 0, 5);
                $pedidoExiste =  Pedido::where('codigo', $codigo)->first();
            }

            $pedido = new Pedido;

            $pedido->codigo = $codigo;
            $arrayMenu = ['1','2','3','4','5','6'];

                if( in_array($parserBody['id_menu'], $arrayMenu) ){
                    $pedido->id_menu = $parserBody['id_menu'];
                    if(is_numeric($parserBody['cantidad']) && $parserBody['cantidad'] > 0 ){
                        $pedido->cantidad = $parserBody['cantidad'];
                 
                        try {
                            $pedido->estado = "activo";
                            $pedido->save();
                            $result = new Resultado(true, $pedido, 200);
                            $response->getBody()->write(json_encode($result)); 
                        } catch (\Throwable $th) {
                            $result = new Resultado(false, "ERROR: NO SE AGREGO PEDIDO", 500);
                            $response->getBody()->write(json_encode($result)); 
                        }
                    }else{
                        $result = new Resultado(false, "ERROR: CANTIDAD INVALIDA", 500);
                        $response->getBody()->write(json_encode($result)); 
                    }
                 
                }else{
                    $result = new Resultado(false, "ERROR: NO EXISTE EL ELEMENTO EN EL MENU", 500);
                    $response->getBody()->write(json_encode($result)); 
                }
            
           
        }else{

            $pedidoExiste =  Pedido::where('codigo', $_POST['codigo'])->first();

            if($pedidoExiste){

                $pedido = new Pedido;
                $pedido->codigo = $pedidoExiste->codigo;
                $arrayMenu = ['1','2','3','4','5','6'];

                if( in_array($parserBody['id_menu'], $arrayMenu) ){
                    $pedido->id_menu = $parserBody['id_menu'];
                    if(is_numeric($parserBody['cantidad']) && $parserBody['cantidad'] > 0 ){
                        $pedido->cantidad = $parserBody['cantidad'];
                 
                        try {
                            $pedido->estado = "activo";
                            $pedido->save();
                            $result = new Resultado(true, $pedido, 200);
                            $response->getBody()->write(json_encode($result)); 
                        } catch (\Throwable $th) {
                            $result = new Resultado(false, "ERROR: NO SE AGREGO PEDIDO", 500);
                            $response->getBody()->write(json_encode($result)); 
                        }
                    }else{
                        $result = new Resultado(false, "ERROR: CANTIDAD INVALIDA", 500);
                        $response->getBody()->write(json_encode($result)); 
                    }
                 
                }else{
                    $result = new Resultado(false, "ERROR: NO EXISTE EL ELEMENTO EN EL MENU", 500);
                    $response->getBody()->write(json_encode($result)); 
                }
               

            }else{
                $result = new Resultado(false, "NO EXISTE", 500);
                $response->getBody()->write(json_encode($result)); 
            }

        }
        
        return $response;
    }

    public function updateOne(Request $request, Response $response, $args)
    {
        $Key = "tpcomanda";
        $token = $_SERVER['HTTP_TOKEN'];
        $decode = JWT::decode($token,$Key,array('HS256'));   
        
        
        $parserBody = $request->getParsedBody();

        if(  !isset($parserBody['estado']) || $parserBody['estado'] == ''){
            $result = new Resultado(false, "ERROR: FALTA EL ESTADO", 500);
            $response->getBody()->write(json_encode($result)); 
        }else{
            $estado = trim(strtolower($parserBody['estado']));
            if($estado == 'en preparacion' || $estado == 'listo para servir'){
                $pedidos = Pedido::where('codigo', $args['codigo_pedido'])->get();

                if(count($pedidos) == 0){
                    $result = new Resultado(false, "NO EXISTE PEDIDO", 500);
                    $response->getBody()->write(json_encode($result)); 
                }else{
        
                    $flag = 0;
                    for ($i=0; $i < count($pedidos); $i++) {
                        $filaMennu = Menu::where('id', $pedidos[$i]->id_menu)->first();
        
                        if($filaMennu->sector == $decode->sector){
                            $pedidos[$i]->estado = $estado;
                            try {
                                $pedidos[$i]->save();
                                $flag = 1;
                            } catch (\Throwable $th) {
                                $result = new Resultado(false, "ERROR: NO SE GUARDARON LOS CAMBIOS", 500);
                                $response->getBody()->write(json_encode($result)); 
                            }
                    
                        }
                    }
                    if($flag){
                        $result = new Resultado(true, $pedidos, 200);
                        $response->getBody()->write(json_encode($result)); 
                    }else{
                        $result = new Resultado(false, "NO TIENE ACCESO", 500);
                        $response->getBody()->write(json_encode($result)); 
                    }
                }
            }else{
                $result = new Resultado(false, "ERROR: ESTADO INCORRECTO", 500);
                $response->getBody()->write(json_encode($result)); 
            }

        }

        return $response;
    }

}