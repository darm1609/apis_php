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
                header('Content-Type: application/json');
                switch($requestMethod) {
                case "GET":
                    $resultado = array();
                    $resultado["nombre"] = false;
                    if(isset($_GET["nombre"])) $nombre = trim($_GET["nombre"]);
                    if(isset($nombre) and !empty($nombre)) {
                        $sql = "select * from vtslistadeprecio where ClienteId=".$clienteId." and Nombre='".$nombre."';";
                        if($bd->ejecutarConsultaExiste($sql))
                            $resultado["nombre"] = true;
                        else
                            $resultado["nombre"] = false;
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