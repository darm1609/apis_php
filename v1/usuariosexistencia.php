<?php
    require_once("vendor/autoload.php");
    require_once("auth.php");
    require_once("config.php");
    require_once("librerias/basedatos.php");

    $requestMethod = $_SERVER["REQUEST_METHOD"];
    $headers=array();
    foreach (getallheaders() as $name => $value) {
        $headers[$name] = $value;
    }
    
    global $clienteId, $servidor, $puerto, $usuario, $pass, $basedatos;

    $bd=new BaseDatos($servidor,$puerto,$usuario,$pass,$basedatos);
	if($bd->conectado)
	{
        if(isset($headers["Authorization"]))
            $token = $headers["Authorization"];
        if(isset($token)) {
            $token=trim(str_replace("Bearer"," ",$token));
            if(@Auth::Check($token) !== null and @Auth::Check($token)) {
                switch($requestMethod) {
                    case "GET":
                        header('Content-Type: application/json');
                        if(isset($_GET["login"])) $login = $_GET["login"];
                        if(isset($_GET["email"])) $email = $_GET["email"];
                        $resultado = array();
                        $resultado["login"] = false;
                        $resultado["email"] = false;
                        if(isset($login)) {
                            $sql = "select * from segusuario u inner join perpersona p on u.PersonaId=p.Id where p.ClienteId=".$clienteId." and u.login='".$login."';";
                            if($bd->ejecutarConsultaExiste($sql))
                                $resultado["login"] = true;
                            else
                                $resultado["login"] = false;
                        }
                        if(isset($email)) {
                            $sql = "select * from perpersona p where p.ClienteId=".$clienteId." and p.email='".$email."';";
                            if($bd->ejecutarConsultaExiste($sql))
                                $resultado["email"] = true;
                            else
                                $resultado["email"] = false;
                        }
                        echo json_encode($resultado);
                        return;
                        break;
                    default:
                        header("HTTP/1.0 405 Method Not Allowed");
                        return;
                        break;
                }
            }
        }
        $resultado = array();
        echo json_encode($resultado);
    }
    else
        header("HTTP/1.1 404 Not Found");
?>