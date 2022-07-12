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

    function productosVigentes($detalles,$bd) {
        $vigentes = true;
        foreach($detalles as $value) {
            $sql = "select * from proproducto p
            where p.VigenciaDesde <= ".time()." and p.VigenciaHasta >= ".time()." and p.Id = ".$value["ProductoId"];
            $resultado=json_decode($bd->ejecutarConsultaJson($sql));
            if(!count($resultado))
                $vigentes = false;
        }
        return $vigentes;
    }

    function buscarTotal($detalle,$bd) {
        $suma = 0;
        foreach($detalle as $value) {
            $sql = "select Precio from proproducto p
            inner join vtsproductodelistadeprecio plp on p.Id=plp.ProductoId 
            and p.ListaDePrecioId=plp.ListaDePrecioId 
            and p.VigenciaDesde <= ".time()." and p.VigenciaHasta >= ".time().
            " and plp.VigenciaDesde <= ".time()." and plp.VigenciaHasta >= ".time().
            " and p.Id = ".$value["ProductoId"];
            $resultado=json_decode($bd->ejecutarConsultaJson($sql));
            if(count($resultado))
                if($resultado[0]->Precio)
                    $suma += $resultado[0]->Precio * $value["Cantidad"];
        }
        return $suma;
    }

    function buscarTotalUnitario($productoId,$bd) {
        $sql = "select Precio from proproducto p
                inner join vtsproductodelistadeprecio plp on p.Id=plp.ProductoId 
                and p.ListaDePrecioId=plp.ListaDePrecioId 
                and p.VigenciaDesde <= ".time()." and p.VigenciaHasta >= ".time().
                " and plp.VigenciaDesde <= ".time()." and plp.VigenciaHasta >= ".time().
                " and p.Id = ".$productoId;
        $resultado=json_decode($bd->ejecutarConsultaJson($sql));
        if(count($resultado))
            if($resultado[0]->Precio)
                return $resultado[0]->Precio;
        return 0;
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
                if(isset($headers["Authorization"]))
                    $token = $headers["Authorization"];
                if(isset($token)) {
                    $token=trim(str_replace("Bearer"," ",$token));
                    if(@Auth::Check($token) !== null and @Auth::Check($token)) {
                        if($_GET["id"]) $id = trim($_GET["id"]);
                        if(isset($id) and !empty($id))
                            $sql="select p.Id, p.Numero, p.EstadoId, e.Nombre Estado, p.FechaHora, p.Total, p.Nombre, p.Telefono, p.Direccion, p.Email, p.ObservacionesCliente, p.Observaciones, 0 Detalle from pedido p inner join genestados e on p.EstadoId=e.Id where p.ClienteId=".$clienteId." and p.Id=".$id.";";
                        else
                            $sql="select p.Id, p.Numero, p.EstadoId, e.Nombre Estado, p.FechaHora, p.Total, p.Nombre, p.Telefono, p.Direccion, p.Email, p.ObservacionesCliente, p.Observaciones, 0 Detalle from pedido p inner join genestados e on p.EstadoId=e.Id where p.ClienteId=".$clienteId.";";
                        $resultado = json_decode($bd->ejecutarConsultaJson($sql));
                        if(count($resultado)) {
                            foreach ($resultado as $value) {
                                $value->FechaHora = date("d-m-Y h:i:s a", $value->FechaHora);
                                $value->Detalle = [];
                                $resultado2 = array();
                                $sql = "select pd.ProductoId, p.Nombre Producto, pd.Cantidad, pd.TotalUnitario, pd.Total from pedidodetalle pd inner join proproducto p on pd.ProductoId=p.Id where pd.PedidoId=".$value->Id.";";
                                $resultado2 = json_decode($bd->ejecutarConsultaJson($sql));
                                $value->Detalle = $resultado2;
                            }
                        }
                    }
                }
                echo json_encode($resultado);
                return;
                break;
            case "POST":
                header('Content-Type: application/json');
                $resultado = array();
                $datos = json_decode(file_get_contents('php://input'), true);
                if(isset($datos["Nombre"])) $nombre = trim($datos["Nombre"]);
                if(isset($datos["Telefono"])) $telefono = trim($datos["Telefono"]);
                if(isset($datos["Direccion"])) $direccion = trim($datos["Direccion"]);
                if(isset($datos["Email"])) $email = trim($datos["Email"]);
                if(isset($datos["ObservacionesCliente"])) $observacionesCliente = trim($datos["ObservacionesCliente"]);
                $sql = "select Numero from pedido where ClienteId=".$clienteId." order by Id desc Limit 1;";
                $resultado = json_decode($bd->ejecutarConsultaJson($sql));
                if(count($resultado)) {
                    $numero = $resultado[0]->Numero;
                    $numero = trim(str_replace("P-"," ",$numero));
                    $numero += 1;
                    $numero = "P-".$numero;
                    $resultado = array();
                }
                else
                    $numero = "P-1";
                if(isset($datos["Detalle"]) and count($datos["Detalle"])) {
                    if(isset($nombre) and !empty($nombre) and isset($telefono) and !empty($telefono) and isset($direccion) and !empty($direccion)) {
                        $total=buscarTotal($datos["Detalle"],$bd);
                        if(productosVigentes($datos["Detalle"],$bd)) {
                            $sql = "insert into pedido (ClienteId, Numero, EstadoId, FechaHora, Total, Nombre, Telefono, Direccion, Email, ObservacionesCliente) values (";
                            $sql .= $clienteId.", ";
                            $sql .= "'".$numero."', ";
                            $sql .= "1, ";
                            date_default_timezone_set("UTC");
                            $sql .= (time() - 18000).", ";
                            $sql .= "'".$total."', ";
                            $sql .= "'".$nombre."', ";
                            $sql .= "'".$telefono."', ";
                            $sql .= "'".$direccion."', ";
                            if(isset($email) and !empty($email))
                                $sql .= "'".$email."', ";
                            else
                                $sql .= "NULL, ";
                            if(isset($observacionesCliente) and !empty($observacionesCliente))
                                $sql .= "'".$observacionesCliente."');";
                            else
                                $sql .= "NULL);";
                            if($bd->ejecutarConsulta($sql)) {
                                $id = $bd->ultimo_result;
                                $resultado["id"] = $id;
                                $sql = "insert into pedidodetalle (PedidoId, ProductoId, Cantidad, TotalUnitario, Total) values ";
                                foreach ($datos["Detalle"] as $value) {
                                    $totalUnitario = buscarTotalUnitario($value["ProductoId"],$bd);
                                    $sql .= "(".$id.", ".$value["ProductoId"].", ".$value["Cantidad"].", ".$totalUnitario.", ".$totalUnitario*$value["Cantidad"]."),";
                                }
                                $sql[strlen($sql)-1] = ";";
                                if(!$bd->ejecutarConsulta($sql)){
                                    $sql = "delete from pedido where Id=".$id.";";
                                    $bd->ejecutarConsultaUpdateDelete($sql);
                                    $resultado = array();
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
                        if($_GET["id"]) $id = trim($_GET["id"]);
                        if(isset($datosPUT["EstadoId"]) and !empty($datosPUT["EstadoId"])) $estadoId = trim($datosPUT["EstadoId"]);
                        if(isset($datosPUT["Observaciones"]) and !empty($datosPUT["Observaciones"])) $observaciones = trim($datosPUT["Observaciones"]);
                        if(isset($id) and !empty($id) and (isset($estadoId) or isset($observaciones))) {
                            $sql = "update pedido set"; 
                            if (isset($estadoId) and !empty($estadoId)) $sql .= " EstadoId=".$estadoId.","; else "EstadoId = EstadoId, ";
                            if (isset($observaciones) and (!empty($observaciones) or $observaciones == 0)) $sql .= " Observaciones='".$observaciones."'"; else $sql .= " Observaciones=Observaciones";
                            $sql .= " where Id=".$id.";";
                            $bd->ejecutarConsultaUpdateDelete($sql);
                            $sql="select p.Id, p.Numero, p.EstadoId, e.Nombre Estado, p.FechaHora, p.Total, p.Nombre, p.Telefono, p.Direccion, p.Email, p.ObservacionesCliente, p.Observaciones, 0 Detalle from pedido p inner join genestados e on p.EstadoId=e.Id where p.ClienteId=".$clienteId." and p.Id=".$id.";";
                            $resultado = json_decode($bd->ejecutarConsultaJson($sql));
                            if(count($resultado)) {
                                foreach ($resultado as $value) {
                                    $value->FechaHora = date("d-m-Y h:i:s a", $value->FechaHora);
                                    $value->Detalle = [];
                                    $resultado2 = array();
                                    $sql = "select pd.ProductoId, p.Nombre Producto, pd.Cantidad, pd.TotalUnitario, pd.Total from pedidodetalle pd inner join proproducto p on pd.ProductoId=p.Id where pd.PedidoId=".$value->Id.";";
                                    $resultado2 = json_decode($bd->ejecutarConsultaJson($sql));
                                    $value->Detalle = $resultado2;
                                }
                            }
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