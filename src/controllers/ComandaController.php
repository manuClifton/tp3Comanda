<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;


use Slim\Factory\AppFactory;
use App\Components\Resultado;
use App\Models\Comanda;
use App\Models\Pedido;
use App\Models\Cliente;
use App\Models\Menu;
use App\Models\Mesa;

use App\Models\Encuesta;
use DateTime;

$app = AppFactory::create();

class ComandaController{

    public function getAll(Request $request, Response $response) 
    {
        $rta = Comanda::get();

        if(count($rta) == 0){
            $result = new Resultado(true, "SIN COMANDAS", 200);
            $response->getBody()->write(json_encode($result)); 
        }
        else{
            $result = new Resultado(true, $rta, 200);
            $response->getBody()->write(json_encode($result));
        }
        return $response;
    }

    public function getOne(Request $request, Response $response, $args) 
    {
        $rta = Comanda::where('codigo_pedido',$args['codigo_pedido'])->first();

        if(!$rta){
            $result = new Resultado(true, "NO EXISTE LA COMANDA", 200);
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
        
      //resibe id_mesa, nombre, genera codigo comanda, id_pedido, precio = suma de valores menu, tiempo
        $parserBody = $request->getParsedBody();
        $codigo_mesa = $parserBody['codigo_mesa'];
        $nombre_cliente = trim(strtolower($parserBody['nombre_cliente']));
        $codigo_pedido = $parserBody['codigo_pedido'];

        $comanda = new Comanda;

        $cliente = new Cliente;

        if( !isset($parserBody['codigo_mesa']) || $parserBody['codigo_mesa'] == '' || !isset($parserBody['nombre_cliente']) || $parserBody['nombre_cliente'] == '' || !isset($parserBody['codigo_pedido']) || $parserBody['codigo_pedido'] == '' ){
            $result = new Resultado(false, "ERROR: DATOS INVALIDOS", 500);
            $response->getBody()->write(json_encode($result)); 
        }else{
            $existeMesa = Mesa::where('codigo', $codigo_mesa)->first();
            //echo $existeMesa;
            //die;
            if(!$existeMesa){
                $result = new Resultado(false, "ERROR: NO EXISTE LA MESA", 500);
                $response->getBody()->write(json_encode($result)); 
            }else{

                if($existeMesa->estado == "activo"){
                    $comanda->estado = "abierta";
    
                    $comanda->codigo_mesa = $codigo_mesa;
                    
                    $pedidosExiste =  Pedido::where('codigo', $codigo_pedido)->get();
                    //echo var_dump(json_decode($pedisosExiste));
                    //die;
                    if(count($pedidosExiste) == 0){
                        $result = new Resultado(false, "ERROR: NO EXISTE EL PEDIDO", 500);
                        $response->getBody()->write(json_encode($result)); 
                    }else{
                        $comanda->codigo_pedido = $codigo_pedido;
        
                        $pedidos = json_decode($pedidosExiste);
                        //echo var_dump($pedidos);
                        //echo  count($pedidos);
                        //die;
                        $contador = 0;
    
                        //modificar mesa
                        $mesa = Mesa::where('codigo', $codigo_mesa)->first();
    

                        for ($i=0; $i < count($pedidos); $i++) { 
                            $pedidos[$i]->id_menu;
                            $menuExiste =  Menu::where('id',  $pedidos[$i]->id_menu)->first();
                            //var_dump(json_decode($menuExiste));
                            //die;
                            $filaMenu = json_decode($menuExiste);
                            $contador += $filaMenu->precio * $pedidos[$i]->cantidad;

                        }

                        try {
                            //de cliente
                            $cliente->nombre = $nombre_cliente;
                            $cliente->save();
                            //guardo estado de mesa
                            $mesa->estado = "con cliente esperando pedido";
                            $mesa->save();
                            //de comanda
                            $comanda->id_cliente = $cliente->id; 
                            $comanda->precio = $contador;
                            $comanda->tiempo_estimado = new DateTime();
                            $comanda->tiempo_estimado->setTime(00,rand(10, 25),00);
     
                            $result = new Resultado(true, $comanda, 200);
                            $response->getBody()->write(json_encode($result)); 
                        } catch (\Throwable $th) {
                            $result = new Resultado(false, "ERROR: NO SE PUDO CREAR COMANDA", 500);
                            $response->getBody()->write(json_encode($th)); 
                        }
    
                    }  
                }else{
                    $result = new Resultado(false, "ERROR: LA MESA NO ESTA HABILITADA", 500);
                    $response->getBody()->write(json_encode($result)); 
                }
            } 
        }
        return $response;
    }


    public function updateOne(Request $request, Response $response, $args)
    {
        $parserBody = $request->getParsedBody();
        $codigo = $args['codigo_pedido'];
        
        if( $codigo == ''  || !isset($parserBody['estado']) || $parserBody['estado'] == '' ){
            $result = new Resultado(false, "ERROR: FALTAN DATOS", 500);
            $response->getBody()->write(json_encode($result)); 
        }else{
            $existeComanda = Comanda::where('codigo_pedido', $codigo)->first();

            if($existeComanda->estado == 'cerrado'){
                $result = new Resultado(false, "ERROR: COMANDA CERRADA", 500);
                $response->getBody()->write(json_encode($result)); 
            }else{
                $pedidos = Pedido::where('codigo', $args['codigo_pedido'])->get();

                $contador = 0;
                for ($i=0; $i < count($pedidos); $i++) { 
                    if($pedidos[$i]->estado == "listo para servir"){
                        $contador++;
                    }
                }
                if($contador == count($pedidos)){
                    $existeComanda->estado = $parserBody['estado'];
                    try {
                        $existeComanda->save();
                        $result = new Resultado(true, $existeComanda, 200);
                        $response->getBody()->write(json_encode($result)); 
                    } catch (\Throwable $th) {
                        $result = new Resultado(false, "ERROR: NO SE PUDO GUARDAR", 500);
                        $response->getBody()->write(json_encode($result)); 
                    }

                }else{
                    $result = new Resultado(false, "ERROR: FALTAN PEDIDOS SIN TERMINAR", 500);
                    $response->getBody()->write(json_encode($result)); 
                }
            }
        }
        return $response;
    }


    public function tiempoEspera(Request $request, Response $response, $args)
    {
        $mesa =  $args['codigo_mesa']?? '';
        $pedido =  $args['codigo_pedido']?? '';

            $comandaExiste =  Comanda::where('codigo_pedido', $pedido)->first();
            
            if(!$comandaExiste){
                $result = new Resultado(false, "ERROR: NO EXISTE COMANDA", 500);
                $response->getBody()->write(json_encode($result)); 
            }else{

                $result = new Resultado(true, "Faltan ".rand(1,15)." Minutos" , 200);
                $response->getBody()->write(json_encode($result)); 
            }      

        return $response;
    }

    public function addEncuesta(Request $request, Response $response)
    {
        $parserBody = $request->getParsedBody();

        if( !isset($parserBody['codigo_pedido']) || !isset($parserBody['punto_mesa']) || !isset($parserBody['punto_resto']) || !isset($parserBody['punto_mozo']) || !isset($parserBody['punto_cocinero']) ){
            $result = new Resultado(false, "ERROR: FALTAN DAT0S", 500);
            $response->getBody()->write(json_encode($result));
        }else{
            
            if( !is_string($parserBody['codigo_pedido']) || !is_numeric($parserBody['punto_mesa']) || !is_numeric($parserBody['punto_resto']) || !is_numeric($parserBody['punto_mozo']) || !is_numeric($parserBody['punto_cocinero']) ){
                $result = new Resultado(false, "ERROR: DATOS INVALIDOS", 500);
                $response->getBody()->write(json_encode($result));
            }else{
                $pedido = $parserBody['codigo_pedido'];
                $comandaExiste =  Comanda::where('codigo_pedido', $pedido)->first();
               // var_dump($comandaExiste);
               // die;
                if(!$comandaExiste){
                    $result = new Resultado(false, "ERROR: NO EXISTE PEDIDO", 500);
                    $response->getBody()->write(json_encode($result)); 
                }else{

                    $encuesta = new Encuesta;

                    $encuesta->codigo_pedido = $parserBody['codigo_pedido'];
                    $encuesta->punto_mesa = $parserBody['punto_mesa'];
                    $encuesta->punto_resto = $parserBody['punto_resto'];
                    $encuesta->punto_mozo = $parserBody['punto_mozo'];
                    $encuesta->punto_cocinero = $parserBody['punto_cocinero'];
                    
                    if(!isset($parserBody['descripcion']) || empty($parserBody['descripcion']) ){
                        $encuesta->descripcion = 'SIN COMENTARIOS';
                    }else{
                        $encuesta->descripcion = $parserBody['descripcion'];
                    }
                    

                    try {
                        $encuesta->save();
                        $result = new Resultado(true, $encuesta, 200);
                        $response->getBody()->write(json_encode($result)); 
                    } catch (\Throwable $th) {
                        $result = new Resultado(false, "ERROR: NO SE GUARDO ENCUESTA: ".$th, 500);
                        $response->getBody()->write(json_encode($result)); 
                    }
                } 
            }     
        }

      

        return $response;
    }




}