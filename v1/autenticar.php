<?php
    require_once("vendor/autoload.php");
    require_once("config.php");
    require_once("auth.php");
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
            case "POST":
                header('Content-Type: application/json');
                if(isset($_POST['login']) and isset($_POST['password'])) {
                    if(!empty($_POST['login']) and !empty($_POST['password'])) {
                        $sql="select u.Id, u.Login, p.Nombres, p.Apellidos from segusuario u inner join perpersona p on u.PersonaId=p.Id where p.ClienteId=".$clienteId." and u.Login='".$_POST['login']."' and u.Password='".$_POST['password']."';";
                        $resultadoSql=json_decode($bd->ejecutarConsultaJson($sql));
                        if(count($resultadoSql)) {
                            $resultado = array();
                            $resultado["tokenKey"] = Auth::SignIn([ 'id' => $resultadoSql[0]->Id,
                                                                    'nombres' => $resultadoSql[0]->Nombres,
                                                                    'apellidos' => $resultadoSql[0]->Apellidos]);
                            $resultado["login"] = $resultadoSql[0]->Login;                                        
                            $resultado["nombres"] = $resultadoSql[0]->Nombres;
                            $resultado["apellidos"] = $resultadoSql[0]->Apellidos;
                            echo json_encode($resultado);
                            return;
                        }
                    }
                }
                $resultado = array();
                echo json_encode($resultado);
                break;
            case "OPTIONS":
                header('Content-Type: application/json');
                break;
            case "GET":
                header('Access-Control-Allow-Origin: *');
                header('Content-Type: application/json');
                if(isset($headers["Authorization"]))
                    $token = $headers["Authorization"];
                if(isset($token)) {
                    $resultado = array();
                    $token=trim(str_replace("Bearer"," ",$token));
                    if(@Auth::Check($token) !== null and @Auth::Check($token)) {
                        $resultado = Auth::GetData($token);
                        $sql = "select u.Id, u.Login, p.Nombres, p.Apellidos, p.Email, pu.administrador, pu.usuarios, pu.productos, pu.pedidos from segusuario u inner join segpermisosdeusuario pu on u.Id=pu.usuarioId inner join perpersona p on u.PersonaId=p.Id and p.ClienteId=".$clienteId." and u.Id=".$resultado->id.";";
                        $resultado = $bd->ejecutarConsultaJson($sql);
                        echo $resultado;
                    }
                    else
                        echo json_encode($resultado);
                    return;
                }
                $resultado = array();
                echo json_encode($resultado);
                break;
            default:
                header("HTTP/1.0 405 Method Not Allowed");
                break;
        }
    }
    else
        header("HTTP/1.1 404 Not Found");
?>