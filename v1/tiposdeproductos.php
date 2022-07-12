<?php
    require_once("vendor/autoload.php");
    require_once("auth.php");
    require_once("config.php");
    require_once("librerias/basedatos.php");
    header('Content-Type: application/json');
    
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
            case "OPTIONS":
                header('Content-Type: application/json');
                break;
            case "GET":
                if(isset($_GET["id"])) $id = trim($_GET["id"]);
                if(isset($id) and !empty($id))
                    $sql = "select Id, Nombre, VigenciaDesde, VigenciaHasta, Visible, Observaciones from protipodeproducto where ClienteId=".$clienteId." and Id=".$id.";";
                else
                    $sql = "select Id, Nombre, VigenciaDesde, VigenciaHasta, Visible, Observaciones from protipodeproducto where ClienteId=".$clienteId.";";
                $resultado = json_decode($bd->ejecutarConsultaJson($sql));
                foreach ($resultado as $index => $value) {
                    $value->VigenciaDesde = date("Y-m-d",$value->VigenciaDesde);
                    $value->VigenciaHasta = date("Y-m-d",$value->VigenciaHasta);
                }
                echo json_encode($resultado);
                return;
                break;
            case "POST":
                $resultado = array();
                if(isset($headers["Authorization"]))
                    $token = $headers["Authorization"];
                if(isset($token)) {
                    $token=trim(str_replace("Bearer"," ",$token));
                    if(@Auth::Check($token) !== null and @Auth::Check($token)) {
                        if(isset($_POST["nombre"])) $nombre = trim($_POST["nombre"]);
                        if(isset($_POST["vigenciaDesde"])) $vigenciaDesde = trim($_POST["vigenciaDesde"]);
                        if(isset($_POST["vigenciaHasta"])) $vigenciaHasta = trim($_POST["vigenciaHasta"]);
                        if(isset($_POST["visible"])) $visible = trim($_POST["visible"]);
                        if(isset($_POST["observaciones"]) and !empty($_POST["observaciones"])) $observaciones = trim($_POST["observaciones"]);
                        if(isset($nombre) and !empty($nombre) and isset($vigenciaDesde) and !empty($vigenciaDesde) and isset($vigenciaHasta) and !empty($vigenciaHasta) and isset($visible) and (!empty($visible) or $visible == 0)) {
                            $sql = "insert into protipodeproducto (ClienteId, Nombre, VigenciaDesde, VigenciaHasta, Visible, Observaciones) values (".$clienteId.", '".$nombre."', ".$vigenciaDesde.", ".$vigenciaHasta.", ".$visible.", ";
                            if(isset($observaciones))
                                $sql .= "'".$observaciones."'";
                            else
                                $sql .= "null";
                            $sql .= ");";
                            if($bd->ejecutarConsulta($sql)) {
                                $id =  $bd->ultimo_result;
                                $resultado["id"] = $id;
                                echo json_encode($resultado);
                            }
                        }
                        return;
                    }
                }
                echo json_encode($resultado);
                return;
                break;
            case "PUT":
                $resultado = array();
                if(isset($headers["Authorization"]))
                    $token = $headers["Authorization"];
                if(isset($token)) {
                    $token=trim(str_replace("Bearer"," ",$token));
                    if(@Auth::Check($token) !== null and @Auth::Check($token)) {
                        parse_str(file_get_contents("php://input"), $datosPUT);
                        if(isset($datosPUT["nombre"])) $nombre = trim($datosPUT["nombre"]);
                        if(isset($datosPUT["vigenciaDesde"])) $vigenciaDesde = trim($datosPUT["vigenciaDesde"]);
                        if(isset($datosPUT["vigenciaHasta"])) $vigenciaHasta = trim($datosPUT["vigenciaHasta"]);
                        if(isset($datosPUT["visible"])) $visible = trim($datosPUT["visible"]);
                        if(isset($datosPUT["observaciones"])) $observaciones = trim($datosPUT["observaciones"]);
                        if(isset($_GET["id"])) $id = trim($_GET["id"]);
                        if(!empty($id) and (isset($nombre) or isset($vigenciaDesde) or isset($vigenciaHasta) or isset($observaciones))) {
                            $sql = "update protipodeproducto set ";
                            if(isset($nombre) and !empty($nombre)) $sql .= "Nombre = '".$nombre."', "; else $sql .= "Nombre = Nombre, ";
                            if(isset($vigenciaDesde) and !empty($vigenciaDesde)) $sql .= "VigenciaDesde = '".$vigenciaDesde."', "; else $sql .= "VigenciaDesde = VigenciaDesde, ";
                            if(isset($vigenciaHasta) and !empty($vigenciaHasta)) $sql .= "VigenciaHasta = '".$vigenciaHasta."', "; else $sql .= "VigenciaHasta = VigenciaHasta, ";
                            if(isset($observaciones) and !empty($observaciones)) $sql .= "Observaciones = '".$observaciones."', "; else $sql .= "Observaciones = Observaciones, ";
                            if(isset($visible) and (!empty($visible) or $visible == 0)) $sql .= "Visible = '".$visible."'"; else $sql .= "Visible = Visible";
                            $sql .= " where ClienteId=".$clienteId." and Id=".$id.";";
                            $bd->ejecutarConsultaUpdateDelete($sql);
                        }
                        if(!empty($id)) {
                            $sql = "select Id, Nombre, VigenciaDesde, VigenciaHasta, Visible, Observaciones from protipodeproducto where ClienteId=".$clienteId." and Id=".$id.";";
                            $resultado = json_decode($bd->ejecutarConsultaJson($sql));
                            foreach ($resultado as $index => $value) {
                                $value->VigenciaDesde = date("Y-m-d",$value->VigenciaDesde);
                                $value->VigenciaHasta = date("Y-m-d",$value->VigenciaHasta);
                            }
                        }
                    }
                }
                echo json_encode($resultado);
                return;
                break;
            case "DELETE":
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