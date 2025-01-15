<?php
$tiempo = time();
//Guardamos la fecha actual de la sesión con hora, minutos y segundos también incluidos.
$fechaUltimaConexion = date('Y-m-d  H:i:s', $tiempo);
$contadorVisitas = 0;
if (filter_has_var(INPUT_COOKIE, "usuario")) {
    foreach ($_COOKIE["usuario"] as $claveCookie => $valorCookie) {
        $clavesValidadas = htmlspecialchars($claveCookie);
        $valoresValidados = htmlspecialchars($valorCookie);
        echo "Clave: " . $claveCookie . ", valor: " . $valorCookie . ".";
    }
} else {
    setcookie("usuario['nombre']", filter_input(INPUT_POST, "usuarioExistente"), time() + 604800);
    setcookie("usuario['nVisitas']", $contadorVisitas, time() + 604800);
    setcookie("usuario['fConn']", $fechaUltimaConexion, time() + 604800);
}
?>
<?php
if (filter_has_var(INPUT_POST, "Registrarse")) {
    header("Location: registro.html");
    die();
} else {
    ?>

    <html>
        <head>
            <meta charset="UTF-8">
            <title></title>
        </head>
        <body>
            <?php
            require_once './funcionesValidacion.php';
            require_once './funcionesBaseDeDatos.php';
            //creación de la conexión
            $conexionBD = new mysqli();
            $mensajeError = "";
            $mensajeExito = "";

            try {
                $conexionBD->connect("localhost", "root", "", "espectaculos");
            } catch (Exception $ex) {
                $mensajeError .= "ERROR: " . $ex->getMessage();
            }
            if (filter_has_var(INPUT_POST, "Entrar")) {
                try {
                    $usuarioLogin = validarUsuarioExistente(filter_input(INPUT_POST, "usuarioExistente"), $conexionBD);
//                    echo var_dump($usuarioLogin);
                    if ($usuarioLogin) {
                        $conexionBD->autocommit(false);
                        $consultaSesiones = $conexionBD->query("SELECT contraseña FROM usuarios WHERE login='$usuarioLogin'");
                        if ($consultaSesiones->num_rows > 0) {
                            $contraseña = $consultaSesiones->fetch_all(MYSQLI_ASSOC);
                            foreach ($contraseña as $contraseñaExistente) {
//                                echo var_dump(filter_input(INPUT_POST, "contraseñaExistente"), $contraseñaExistente);
                                $contraseñaEncriptada = hash("sha512", filter_input(INPUT_POST, "contraseñaExistente"));
                                $esValida = $contraseñaEncriptada === $contraseñaExistente['contraseña'];
                                if ($esValida) {
                                    $mensajeExito .= "Inicio de Sesión realizado con éxito";
                                    $contadorVisitas++;
                                } else {
                                    $mensajeError .= "No se ha podido iniciar sesión, la contraseña o el usuario no son correctos.";
                                }
                            }
                        } else {
                            $mensajeError .= "La consulta no se ha podido realizar.";
                        }
                    } else {
                        $mensajeError .= "Los datos son inválidos o incorrectos.";
                    }
                } catch (Exception $ex) {
                    $mensajeError .= "ERROR: " . $ex->getMessage();
                }
                ?>
                <h2>LISTA DE MENSAJES: </h2>
                <h2>Mensajes de error: </h2>
                <ul>
                    <li><?php
                        if (isset($mensajeError)) {
                            echo $mensajeError;
                        }
                        ?></li>
                </ul>
                <h2>Mensajes de exito: </h2>
                <ul>
                    <li><?php
                        if (isset($mensajeExito)) {
                            echo $mensajeExito;
                        }
                        ?></li>
                </ul>
                <h2>Nombre de usuario, visitas y ultima conexión: </h2>
                <ul>
                    <li><?php
                        if (filter_has_var(INPUT_COOKIE, "usuario")) {
                            echo $claveCookie;
                        }
                        ?></li>
                </ul>
                <?php
            }
            ?>
            <form action="" method="post">
                <button type="submit" name="reiniciarVisitas">Reiniciar Visitas</button>
                <button type="submit" name="eliminarCookie">Borrar Cookie</button>
            </form>
            <?php
            if (filter_has_var(INPUT_POST, "reiniciarVisitas")) {
                $contadorVisitas = 0;
                setcookie("usuario['nVisitas']", $contadorVisitas, time() + 604800);
            } else if (filter_has_var(INPUT_POST, "eliminarCookie")) {
                setcookie("usuario['nombre']", filter_input(INPUT_POST, "usuarioExistente"), time() - 604800);
                setcookie("usuario['nVisitas']", $contadorVisitas, time() - 604800);
                setcookie("usuario['fConn']", $fechaUltimaConexion, time() - 604800);
            }
            ?>
        </body>
    </html>
    <?php
}    