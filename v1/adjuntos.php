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
    
    global $clienteId, $servidor, $puerto, $usuario, $pass, $basedatos, $rutaDeAdjuntos;

    $bd=new BaseDatos($servidor,$puerto,$usuario,$pass,$basedatos);
	if($bd->conectado)
	{
        switch($requestMethod) {
            case "OPTIONS":
                header('Content-Type: application/json');
                break;
            case "GET":
                if($_GET["id"]) {
                    $sql = "select Ruta from genadjunto where ClienteId=".$clienteId." and Id=".$_GET["id"].";";
                    $resultado = json_decode($bd->ejecutarConsultaJson($sql));
                    if(count($resultado)) {
                        $ruta = $resultado[0]->Ruta;
                        $mimeType = image_type_to_mime_type(exif_imagetype($ruta));
                        header( 'Content-Type: ' . $mimeType );
                        if($mimeType == "image/jpeg") {
                            $image = imagecreatefromjpeg($ruta);
                            imagejpeg($image);
                        }
                        if($mimeType == "image/png") {
                            $image = imagecreatefrompng($ruta);
                            imagepng($image);
                        }
                    }
                }
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
                        if(isset($_FILES["file"]["tmp_name"])) {
                            $nombreDeArchivo = md5($_FILES["file"]["name"].time());
                            $tipoDeArchivo = $_FILES["file"]["type"];
                            $tamanoDeArchivo = $_FILES["file"]["size"];
                            if($tipoDeArchivo == "image/jpeg" or $tipoDeArchivo == "image/png") {
                                if($tipoDeArchivo == "image/jpeg")
                                    $nombreDeArchivo = $nombreDeArchivo.".jpg";
                                if($tipoDeArchivo == "image/png")
                                    $nombreDeArchivo = $nombreDeArchivo.".png";
                                $nombreDeArchivoRuta = $rutaDeAdjuntos.$nombreDeArchivo;
                                if(move_uploaded_file($_FILES["file"]["tmp_name"],$nombreDeArchivoRuta)) {
                                    $sql = "insert into genadjunto (ClienteId, Nombre, Ruta) values (".$clienteId.", '".$nombreDeArchivo."', '".$nombreDeArchivoRuta."');";
                                    if($bd->ejecutarConsulta($sql)) {
                                        $resultado["id"] = $bd->ultimo_result;
                                        $x = json_encode($resultado);
                                        echo $x;
                                        return;
                                    }
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
                break;
        }
        $resultado = array();
        echo json_encode($resultado);
    }
    else
        header("HTTP/1.1 404 Not Found");
?>