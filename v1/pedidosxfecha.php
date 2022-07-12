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
                        if($_GET["fecha"]) $fecha = trim($_GET["fecha"]);
                        date_default_timezone_set("UTC");
                        $timeUnixIni = strtotime($fecha[6].$fecha[7].$fecha[8].$fecha[9]."-".$fecha[3].$fecha[4]."-".$fecha[0].$fecha[1]." 00:00:00");
                        $timeUnixFin = strtotime($fecha[6].$fecha[7].$fecha[8].$fecha[9]."-".$fecha[3].$fecha[4]."-".$fecha[0].$fecha[1]." 23:59:00");
                        if(isset($timeUnixIni) and isset($timeUnixFin) and !empty($timeUnixIni) and !empty($timeUnixFin)) {
                            $sql="select p.Id, p.Numero, p.EstadoId, e.Nombre Estado, p.FechaHora, p.Total, p.Nombre, p.Telefono, p.Direccion, p.Email, p.ObservacionesCliente, p.Observaciones, 0 Detalle from pedido p inner join genestados e on p.EstadoId=e.Id where p.ClienteId=".$clienteId." and p.FechaHora <= ".$timeUnixFin." and p.FechaHora >= ".$timeUnixIni.";";
                            $resultado = json_decode($bd->ejecutarConsultaJson($sql));
                            if(count($resultado)) {
                                foreach ($resultado as $value) {
                                    $value->FechaHora = date("d-m-Y h:i:s a", $value->FechaHora);
                                    $value->Detalle = [];
                                    $resultado2 = array();
                                    $sql = "select pd.ProductoId, p.Nombre Producto, pd.Cantidad, pd.TotalUnitario, pd.Total, p.AdjuntoId from pedidodetalle pd inner join proproducto p on pd.ProductoId=p.Id where pd.PedidoId=".$value->Id.";";
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