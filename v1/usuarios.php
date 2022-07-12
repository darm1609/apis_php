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
                        if($_GET["id"])
                            $sql = "select u.Id, u.Login, p.Nombres, p.Apellidos, p.Email, pu.administrador, pu.usuarios, pu.productos, pu.pedidos from segusuario u inner join segpermisosdeusuario pu on u.Id=pu.usuarioId inner join perpersona p on u.PersonaId=p.Id and p.ClienteId=".$clienteId." and u.Id=".$_GET["id"].";";
                        else
                            $sql = "select u.Id, u.Login, p.Nombres, p.Apellidos, p.Email, pu.administrador, pu.usuarios, pu.productos, pu.pedidos from segusuario u inner join segpermisosdeusuario pu on u.Id=pu.usuarioId inner join perpersona p on u.PersonaId=p.Id and p.ClienteId=".$clienteId.";";
                        $resultado = $bd->ejecutarConsultaJson($sql);
                        echo $resultado;
                        return;
                        break;
                    case "POST":
                        header('Content-Type: application/json');
                        $resultado = array();
                        if(isset($_POST["nombres"])) $nombres = trim($_POST["nombres"]);
                        if(isset($_POST["apellidos"])) $apellidos = trim($_POST["apellidos"]);
                        if(isset($_POST["email"])) $email = trim($_POST["email"]);
                        if(isset($_POST["login"])) $login = trim($_POST["login"]);
                        if(isset($_POST["password"])) $password = trim($_POST["password"]);
                        if(isset($_POST["administrador"])) $administrador = trim($_POST["administrador"]);
                        if(isset($_POST["usuarios"])) $usuarios = trim($_POST["usuarios"]);
                        if(isset($_POST["productos"])) $productos = trim($_POST["productos"]);
                        if(isset($_POST["pedidos"])) $pedidos = trim($_POST["pedidos"]);
                        if(isset($login) and !empty($login) and isset($email) and !empty($email) and isset($nombres) and !empty($nombres) and isset($apellidos) and !empty($apellidos) and isset($password) and !empty($password)) {
                            $sql = "select u.Login from segusuario u inner join perpersona p on u.PersonaId=p.Id where p.ClienteId='".$clienteId."' and u.Login='".$login."';";
                            $resultado = json_decode($bd->ejecutarConsultaJson($sql));
                            if(empty(count($resultado))) {
                                $sql = "select p.Email from perpersona where ClienteId='".$clienteId."' and Email='".$email."';";
                                $resultado = json_decode($bd->ejecutarConsultaJson($sql));
                                if(empty(count($resultado))) {
                                    $sql="insert into perpersona (ClienteId, Email, Nombres, Apellidos) values (".$clienteId.", '".$email."', '".$nombres."', '".$apellidos."');";
                                    if($bd->ejecutarConsulta($sql)) {
                                        $id =  $bd->ultimo_result;
                                        $sql = "insert into segusuario (PersonaId, Login, Password) values (".$id.", '".$login."', '".$password."');";
                                        if($bd->ejecutarConsulta($sql)) {
                                            $usuarioId = $bd->ultimo_result;
                                            $resultado["id"] = $id;

                                            $sql = "insert into segpermisosdeusuario (usuarioId, administrador, usuarios, productos, pedidos) values (".$usuarioId.", ".$administrador.", ".$usuarios.", ".$productos.", ".$pedidos.");";
                                            $bd->ejecutarConsulta($sql);

                                            echo json_encode($resultado);
                                            return;
                                        }
                                    }
                                }
                            }
                        }
                        echo json_encode($resultado);
                        return;
                        break;
                    case "OPTIONS":
                        header('Content-Type: application/json');
                        break;
                    case "PUT":
                        header('Content-Type: application/json');
                        parse_str(file_get_contents("php://input"), $datosPUT);
                        if(isset($datosPUT["login"])) $login = trim($datosPUT["login"]);
                        if(isset($datosPUT["password"])) $password = trim($datosPUT["password"]);
                        if(isset($datosPUT["email"])) $email = trim($datosPUT["email"]);
                        if(isset($datosPUT["nombres"])) $nombres = trim($datosPUT["nombres"]);
                        if(isset($datosPUT["apellidos"])) $apellidos = trim($datosPUT["apellidos"]);
                        if(isset($datosPUT["administrador"])) $administrador = trim($datosPUT["administrador"]);
                        if(isset($datosPUT["usuarios"])) $usuarios = trim($datosPUT["usuarios"]);
                        if(isset($datosPUT["productos"])) $productos = trim($datosPUT["productos"]);
                        if(isset($datosPUT["pedidos"])) $pedidos = trim($datosPUT["pedidos"]);
                        $resultado = array();
                        $resultado = Auth::GetData($token);
                        if($_GET["id"] > 0)
                            $id = $_GET["id"];
                        else
                            $id = $resultado->id;
                        if(isset($id) and (isset($email) or isset($nombres) or isset($apellidos))) {
                            $sql = "update perpersona set ";
                            if(isset($email) and !empty($email)) $sql .= "Email = '".$email."', "; else $sql .= "Email = Email, ";
                            if(isset($nombres) and !empty($nombres)) $sql .= "Nombres = '".$nombres."', "; else $sql .= "Nombres = Nombres, ";
                            if(isset($apellidos) and !empty($apellidos)) $sql .= "Apellidos='".$apellidos."'"; else $sql .= "Apellidos = Apellidos";
                            $sql .= " where ClienteId = ".$clienteId." and id = (select PersonaId from segusuario where Id=".$id.");";
                            $bd->ejecutarConsultaUpdateDelete($sql);
                        }
                        if(isset($id) and (isset($login) or isset($password))) {
                            $sql = "update segusuario set ";
                            if(isset($login) and !empty($login)) $sql .= "Login = '".$login."', "; else $sql .= "Login = Login, "; 
                            if(isset($password) and !empty($password)) $sql .= "Password = '".$password."' "; else $sql .= "Password = Password"; 
                            $sql .= " where id = ".$id.";";
                            $bd->ejecutarConsultaUpdateDelete($sql);
                        }
                        if(isset($administrador) and (!empty($administrador) or $administrador == 0)) {
                            $sql = "update segpermisosdeusuario set administrador=".$administrador." where usuarioId = ".$id.";";
                            $bd->ejecutarConsultaUpdateDelete($sql);
                        }
                        if(isset($usuarios) and (!empty($usuarios) or $usuarios == 0)) {
                            $sql = "update segpermisosdeusuario set usuarios=".$usuarios." where usuarioId = ".$id.";";
                            $bd->ejecutarConsultaUpdateDelete($sql);
                        }
                        if(isset($productos) and (!empty($productos) or $productos == 0)) {
                            $sql = "update segpermisosdeusuario set productos=".$productos." where usuarioId = ".$id.";";
                            $bd->ejecutarConsultaUpdateDelete($sql);
                        }
                        if(isset($pedidos) and (!empty($pedidos) or $pedidos == 0)) {
                            $sql = "update segpermisosdeusuario set pedidos=".$pedidos." where usuarioId = ".$id.";";
                            $bd->ejecutarConsultaUpdateDelete($sql);
                        }
                        $sql = "select u.Id, u.Login, p.Nombres, p.Apellidos, p.Email, pu.administrador, pu.usuarios, pu.productos, pu.pedidos from segusuario u inner join segpermisosdeusuario pu on u.Id=pu.usuarioId inner join perpersona p on u.PersonaId=p.Id and p.ClienteId=".$clienteId." and u.Id=".$id.";";
                        $resultado = $bd->ejecutarConsultaJson($sql);
                        echo $resultado;
                        return;
                        break;
                    case "DELETE":
                        header('Content-Type: application/json');
                        $resultado = array();
                        if(isset($_GET["id"])) $id = $_GET["id"];
                        if(isset($id) and !empty($id)) {
                            $sql = "select PersonaId from segusuario where Id=".$id.";";
                            $resultado = json_decode($bd->ejecutarConsultaJson($sql));
                            if(count($resultado)) {
                                $personaId = $resultado[0]->PersonaId;
                                if(isset($personaId)) {
                                    $sql = "delete from segusuario where Id=".$id.";";  
                                    $bd->ejecutarConsultaUpdateDelete($sql);
                                    $sql = "delete from perpersona where Id=".$personaId.";";
                                    $bd->ejecutarConsultaUpdateDelete($sql);
                                    $resultado[0] = true;
                                    echo json_encode($resultado);
                                    return;
                                }
                            }
                            echo json_encode($resultado);
                        }
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