<?php
namespace App\Middlewares;


use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use \Firebase\JWT\JWT;
use App\Components\Resultado;

class AuthMiddleware {

    public $roles;

    public function __construct(string $role1, string $role2 = '', string $role3='')
    {
        $this->roles = array();
        array_push($this->roles, $role1,$role2,$role3);
    }

        /**
     * Example middleware invokable class
     *
     * @param  ServerRequest  $request PSR-7 request
     * @param  RequestHandler $handler PSR-15 request handler
     *
     * @return Response
     */

    public function __invoke(Request $request, RequestHandler $handler)
    {
     
        $valido = false;
        //$token = $request->getHeader('token')[0] ?? '';
        $token = $_SERVER['HTTP_TOKEN']?? '';
        //var_dump($token);
        //die();

        if(!empty($token)){//si hay token
           // echo "tengo TOKEN";
            //die();

            $Key = "tpcomanda";

            try {
                $decode = JWT::decode($token,$Key,array('HS256'));

                $valido = in_array($decode->tipo, $this->roles);

             } catch (\Throwable $th) {
                //$result = new Resultado(false, "TOKEN INVALIDO", 500);
                //echo json_encode($result); 
                //die();
             }
        }else{
           // $result = new Resultado(false, "NO TIENE TOKEN", 500);
            //echo json_encode($result); 
            //die();
        }

        
        if (!$valido) {
            $result = new Resultado(false,"ERROR: Prohibido pasar", 403);
            
            
            $response = new Response();
            $response->getBody()->write(json_encode($result));
           
            return $response->withStatus(403);
        } else {
            $response = $handler->handle($request);
            //var_dump(json_encode($response));
            //die();
            $existingContent = (string) $response->getBody();
            //var_dump(json_encode($existingContent));
            //die();
            $resp = new Response();
            $resp->getBody()->write($existingContent);
            return $resp;
        }
        
    }
}