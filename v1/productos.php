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
            case "OPTIONS":
                header('Content-Type: application/json');
                break;
            case "GET":
                header('Content-Type: application/json');
                $resultado = array();
                if(isset($_GET["id"])) $id = trim($_GET["id"]);
                if(isset($id) and !empty($id)) 
                    $sql = "select p.Id, p.TipoDeProductoId, tp.Nombre TipoDeProducto, tp.VigenciaDesde TipoDeProductoVigenciaDesde, tp.VigenciaHasta TipoDeProductoVigenciaHasta, tp.Observaciones TipoDeProductoObservaciones, tp.Visible TipoDeProductoVisible, p.Nombre Producto, p.VigenciaDesde, p.VigenciaHasta, p.Visible, p.Disponible, p.Descripcion, p.AdjuntoId, p.ListaDePrecioId, 0 ListaDePrecio from proproducto p inner join protipodeproducto tp on p.TipoDeProductoId=tp.Id where p.Id=".$id.";";
                else
                    $sql = "select p.Id, p.TipoDeProductoId, tp.Nombre TipoDeProducto, tp.VigenciaDesde TipoDeProductoVigenciaDesde, tp.VigenciaHasta TipoDeProductoVigenciaHasta, tp.Observaciones TipoDeProductoObservaciones, tp.Visible TipoDeProductoVisible, p.Nombre Producto, p.VigenciaDesde, p.VigenciaHasta, p.Visible, p.Disponible, p.Descripcion, p.AdjuntoId, p.ListaDePrecioId, 0 ListaDePrecio from proproducto p inner join protipodeproducto tp on p.TipoDeProductoId=tp.Id;";
                $resultado = json_decode($bd->ejecutarConsultaJson($sql));
                foreach ($resultado as $index => $value) {
                    $value->TipoDeProductoVigenciaDesde = date("Y-m-d",$value->TipoDeProductoVigenciaDesde);
                    $value->TipoDeProductoVigenciaHasta = date("Y-m-d",$value->TipoDeProductoVigenciaHasta);
                    $value->VigenciaDesde = date("Y-m-d",$value->VigenciaDesde);
                    $value->VigenciaHasta = date("Y-m-d",$value->VigenciaHasta);
                    $value->ListaDePrecio = [];
                    $resultado2 = array();
                    $sql = "select pl.ListaDePrecioId Id, l.Nombre, pl.VigenciaDesde, pl.VigenciaHasta, pl.Precio, l.Visible, l.VigenciaDesde ListaDePrecioVigenciaDesde, l.VigenciaHasta ListaDePrecioVigenciaHasta from vtsproductodelistadeprecio pl inner join vtslistadeprecio l on pl.ListaDePrecioId=l.Id where pl.ProductoId=".$value->Id.";";
                    $resultado2 = json_decode($bd->ejecutarConsultaJson($sql));
                    foreach ($resultado2 as $index2 => $value2) {
                        $value2->VigenciaDesde = date("Y-m-d",$value2->VigenciaDesde);
                        $value2->VigenciaHasta = date("Y-m-d",$value2->VigenciaHasta);
                        $value2->ListaDePrecioVigenciaDesde = date("Y-m-d",$value2->ListaDePrecioVigenciaDesde);
                        $value2->ListaDePrecioVigenciaHasta = date("Y-m-d",$value2->ListaDePrecioVigenciaHasta);
                    }
                    $value->ListaDePrecio = $resultado2;
                }
                echo json_encode($resultado);
                return;
                break;
            case "POST":
                header('Content-Type: application/json');
                $resultado = array();
                if(isset($headers["Authorization"]))
                    $token = $headers["Authorization"];
                if(isset($token)) {
                    $token=trim(str_replace("Bearer"," ",$token));
                    if(@Auth::Check($token) !== null and @Auth::Check($token)) {
                        if(isset($_POST["tipoDeProductoId"])) $tipoDeProductoId = trim($_POST["tipoDeProductoId"]);
                        if(isset($_POST["nombre"])) $nombre = trim($_POST["nombre"]);
                        if(isset($_POST["vigenciaDesde"])) $vigenciaDesde = trim($_POST["vigenciaDesde"]);
                        if(isset($_POST["vigenciaHasta"])) $vigenciaHasta = trim($_POST["vigenciaHasta"]);
                        if(isset($_POST["visible"])) $visible = trim($_POST["visible"]);
                        if(isset($_POST["disponible"])) $disponible = trim($_POST["disponible"]);
                        if(isset($_POST["descripcion"])) $descripcion = trim($_POST["descripcion"]);
                        if(isset($_POST["adjuntoId"])) $adjuntoId = trim($_POST["adjuntoId"]);
                        if(isset($_POST["listaDePrecioId"])) $listaDePrecioId = trim($_POST["listaDePrecioId"]);
                        if(isset($_POST["precio"])) $precio = trim($_POST["precio"]);
                        if(isset($_POST["vigenciaDesdeListaDePrecio"])) $vigenciaDesdeListaDePrecio = trim($_POST["vigenciaDesdeListaDePrecio"]);
                        if(isset($_POST["vigenciaHastaListaDePrecio"])) $vigenciaHastaListaDePrecio = trim($_POST["vigenciaHastaListaDePrecio"]);
                        if(isset($tipoDeProductoId) and !empty($tipoDeProductoId) and isset($nombre) and !empty($nombre) and isset($vigenciaDesde) and !empty($vigenciaDesde) and isset($vigenciaHasta) and !empty($vigenciaHasta) and isset($visible) and !empty($visible) and isset($disponible) and !empty($disponible)) {
                            $sql = "insert into proproducto (TipoDeProductoId, Nombre, VigenciaDesde, VigenciaHasta, Visible, Disponible, Descripcion, AdjuntoId, ListaDePrecioId)";
                            $sql .= " values (".$tipoDeProductoId.", '".$nombre."', ".$vigenciaDesde.", ".$vigenciaHasta.", ".$visible.", ".$disponible;
                            if(isset($descripcion) and !empty($descripcion))
                                $sql .= ", '".$descripcion."'";
                            else
                                $sql .= ", NULL";
                            if(isset($adjuntoId) and !empty($adjuntoId))
                                $sql .= ", ".$adjuntoId;
                            else
                                $sql .= ", NULL";
                            if(isset($listaDePrecioId) and !empty($listaDePrecioId) and isset($precio) and !empty($precio))
                                $sql .= ", ".$listaDePrecioId.");";
                            else
                                $sql .= ", 0);";
                            if($bd->ejecutarConsulta($sql)) {
                                $id = $bd->ultimo_result;
                                $resultado["id"] = $id;
                                if(!empty($id) and isset($listaDePrecioId) and !empty($listaDePrecioId) and isset($precio) and !empty($precio) and isset($vigenciaDesdeListaDePrecio) and !empty($vigenciaDesdeListaDePrecio) and isset($vigenciaHastaListaDePrecio) and !empty($vigenciaHastaListaDePrecio)) {
                                    $sql = "insert into vtsproductodelistadeprecio (ListaDePrecioId, ProductoId, VigenciaDesde, VigenciaHasta, Precio) values (".$listaDePrecioId.", ".$id.", ".$vigenciaDesdeListaDePrecio.", ".$vigenciaHastaListaDePrecio.", '".$precio."');";
                                    $bd->ejecutarConsulta($sql);
                                } 
                            }
                        }
                    }
                }
                echo json_encode($resultado);
                return;
                break;
            case "PUT":
                header('Content-Type: application/json');
                $resultado = array();
                if(isset($headers["Authorization"]))
                    $token = $headers["Authorization"];
                if(isset($token)) {
                    $token=trim(str_replace("Bearer"," ",$token));
                    if(@Auth::Check($token) !== null and @Auth::Check($token)) {
                        parse_str(file_get_contents("php://input"), $datosPUT);
                        if(isset($datosPUT["tipoDeProductoId"])) $tipoDeProductoId = trim($datosPUT["tipoDeProductoId"]);
                        if(isset($datosPUT["nombre"])) $nombre = trim($datosPUT["nombre"]);
                        if(isset($datosPUT["vigenciaDesde"])) $vigenciaDesde = trim($datosPUT["vigenciaDesde"]);
                        if(isset($datosPUT["vigenciaHasta"])) $vigenciaHasta = trim($datosPUT["vigenciaHasta"]);
                        if(isset($datosPUT["visible"])) $visible = trim($datosPUT["visible"]);
                        if(isset($datosPUT["disponible"])) $disponible = trim($datosPUT["disponible"]);
                        if(isset($datosPUT["descripcion"])) $descripcion = trim($datosPUT["descripcion"]);
                        if(isset($datosPUT["adjuntoId"])) $adjuntoId = trim($datosPUT["adjuntoId"]);
                        if(isset($datosPUT["listaDePrecioId"])) $listaDePrecioId = trim($datosPUT["listaDePrecioId"]);
                        if(isset($datosPUT["precio"])) $precio = trim($datosPUT["precio"]);
                        if(isset($datosPUT["vigenciaDesdeListaDePrecio"])) $vigenciaDesdeListaDePrecio = trim($datosPUT["vigenciaDesdeListaDePrecio"]);
                        if(isset($datosPUT["vigenciaHastaListaDePrecio"])) $vigenciaHastaListaDePrecio = trim($datosPUT["vigenciaHastaListaDePrecio"]);
                        if(isset($_GET["id"])) $id = trim($_GET["id"]);
                        if(isset($id) and !empty($id) and (isset($tipoDeProductoId) or isset($nombre) or isset($vigenciaDesde) or isset($vigenciaHasta) or isset($visible) or isset($disponible) or isset($descripcion) or isset($adjuntoId) or isset($listaDePrecioId) or isset($precio))){
                            $sql = "update proproducto set ";
                            if(isset($tipoDeProductoId) and !empty($tipoDeProductoId)) $sql .= "TipoDeProductoId = '".$tipoDeProductoId."', "; else $sql .= "TipoDeProductoId = TipoDeProductoId, ";
                            if(isset($nombre) and !empty($nombre)) $sql .= "Nombre = '".$nombre."', "; else $sql .= "Nombre = Nombre, ";
                            if(isset($vigenciaDesde) and !empty($vigenciaDesde)) $sql .= "VigenciaDesde = '".$vigenciaDesde."', "; else $sql .= "VigenciaDesde = VigenciaDesde, ";
                            if(isset($vigenciaHasta) and !empty($vigenciaHasta)) $sql .= "VigenciaHasta = '".$vigenciaHasta."', "; else $sql .= "VigenciaHasta = VigenciaHasta, ";
                            if(isset($visible) and (!empty($visible) or $visible == 0)) $sql .= "Visible = '".$visible."', "; else $sql .= "Visible = Visible, ";
                            if(isset($disponible) and (!empty($disponible) or $disponible == 0)) $sql .= "Disponible = '".$disponible."', "; else $sql .= "Disponible = Disponible, ";
                            if(isset($descripcion) and !empty($descripcion)) $sql .= "Descripcion = '".$descripcion."', "; else $sql .= "Descripcion = Descripcion, ";
                            if(isset($adjuntoId) and !empty($adjuntoId)) $sql .= "AdjuntoId = '".$adjuntoId."', "; else $sql .= "AdjuntoId = AdjuntoId, ";
                            if(isset($listaDePrecioId) and (!empty($listaDePrecioId) or $listaDePrecioId == 0)) $sql .= "ListaDePrecioId = '".$listaDePrecioId."'"; else $sql .= "ListaDePrecioId = ListaDePrecioId";
                            $sql .= " where Id=".$id.";";
                            $bd->ejecutarConsultaUpdateDelete($sql);
                            if(isset($listaDePrecioId) and !empty($listaDePrecioId) and (isset($precio) or isset($vigenciaDesdeListaDePrecio) or isset($vigenciaHastaListaDePrecio))) {
                                $sql = "select * from vtsproductodelistadeprecio where ListaDePrecioId=".$listaDePrecioId." and ProductoId=".$id.";";
                                if($bd->ejecutarConsultaExiste($sql)) {
                                    $sql = "update vtsproductodelistadeprecio set ";
                                    if(isset($precio) and (!empty($precio) or $precio == 0)) $sql .= "Precio = '".$precio."',"; else $sql .= "Precio = Precio, ";
                                    if(isset($vigenciaDesdeListaDePrecio) and !empty($vigenciaDesdeListaDePrecio)) $sql .= "VigenciaDesde = '".$vigenciaDesdeListaDePrecio."', "; else $sql .= "VigenciaDesde = VigenciaDesde, ";
                                    if(isset($vigenciaHastaListaDePrecio) and !empty($vigenciaHastaListaDePrecio)) $sql .= "VigenciaHasta = '".$vigenciaHastaListaDePrecio."'"; else $sql .= "VigenciaHasta = VigenciaHasta";
                                    $sql .= "where ListaDePrecioId=".$listaDePrecioId." and ProductoId=".$id.";";
                                }
                                else
                                    $sql = "insert into vtsproductodelistadeprecio (ListaDePrecioId, ProductoId, VigenciaDesde, VigenciaHasta, Precio) values (".$listaDePrecioId.", ".$id.", ".$vigenciaDesdeListaDePrecio.", ".$vigenciaHastaListaDePrecio.", '".$precio."');";
                                $bd->ejecutarConsultaUpdateDelete($sql);
                            }
                        }
                        if(isset($id) and !empty($id)){
                            $sql = "select Id, TipoDeProductoId, Nombre, VigenciaDesde, VigenciaHasta, Visible, Disponible, Descripcion, AdjuntoId, ListaDePrecioId, 0 ListaDePrecio from proproducto where Id=".$id.";";
                            $resultado = json_decode($bd->ejecutarConsultaJson($sql));
                            foreach ($resultado as $index => $value) {
                                $value->VigenciaDesde = date("Y-m-d",$value->VigenciaDesde);
                                $value->VigenciaHasta = date("Y-m-d",$value->VigenciaHasta);
                            }
                            $resultado[0]->ListaDePrecio = [];
                            $resultado2 = Array();
                            $sql = "select pl.ListaDePrecioId Id, l.Nombre, pl.VigenciaDesde, pl.VigenciaHasta, pl.Precio from vtsproductodelistadeprecio pl inner join vtslistadeprecio l on pl.ListaDePrecioId=l.Id where pl.ProductoId=".$id.";";
                            $resultado2 = json_decode($bd->ejecutarConsultaJson($sql));
                            foreach ($resultado2 as $index => $value) {
                                $value->VigenciaDesde = date("Y-m-d",$value->VigenciaDesde);
                                $value->VigenciaHasta = date("Y-m-d",$value->VigenciaHasta);
                            }
                            $resultado[0]->ListaDePrecio = $resultado2;
                        }
                    }
                }
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