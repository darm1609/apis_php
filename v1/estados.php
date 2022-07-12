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
        switch($requestMethod) {
            case "GET":
                header('Content-Type: application/json');
                $resultado = array();
                if(isset($_GET["Categoria"]) and !empty($_GET["Categoria"])) $categoria = trim($_GET["Categoria"]);
                if(isset($categoria))
                    $sql="select e.Id, e.Nombre from genestados e where Categoria=".$categoria.";";
                $resultado=json_decode($bd->ejecutarConsultaJson($sql));
                echo json_encode($resultado);
                return;
                break;
            default:
                header("HTTP/1.0 405 Method Not Allowed");
                return;
                break;
        }
        $resultado = array();
        echo json_encode($resultado);
    }
    else
        header("HTTP/1.1 404 Not Found");
?>