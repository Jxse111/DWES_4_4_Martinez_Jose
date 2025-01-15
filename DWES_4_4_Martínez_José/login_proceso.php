<?php
$mensajeError = "Mensajes de error : ";
$mensajeExito = "Mensajes de éxito: ";
$tiempo = time();
// Guardamos la fecha actual de la sesión con hora, minutos y segundos también incluidos.
$fechaUltimaConexion = date('Y-m-d H:i:s', $tiempo);
if (filter_has_var(INPUT_COOKIE, "usuario")) {
    $contadorVisitas = intval(filter_input(INPUT_COOKIE, "usuario[nVisitas]"));
    //Una vez creada la cookie recojo los datos y actualizo el contador de visitas y la fecha de la ultima conexión.
    foreach ($_COOKIE["usuario"] as $clave => $cookieUsuario) {
        $datosCookieUsuario[$clave] = $cookieUsuario;
    }
    setcookie("usuario[nVisitas]", $contadorVisitas + 1, time() + 604800);
    setcookie("usuario[fConn]", $fechaUltimaConexion, time() + 604800);

    if (filter_has_var(INPUT_POST, "reiniciarVisitas")) {
        setcookie("usuario[nVisitas]", '0', time() + 604800);
    } elseif (filter_has_var(INPUT_POST, "eliminarCookie")) {
        setcookie("usuario[nombre]", "", time() - 604800);
        setcookie("usuario[nVisitas]", "", time() - 604800);
        setcookie("usuario[fConn]", "", time() - 604800);
        header("Location: login.html"); // Realizamos la redirección al formulario de inicio de sesión
        exit(); // Aseguramos que no siga ejecutando código después de la redirección
    }
    //Sino esta creada la cookie, se crea y se guardan los datos correspondientes.
} else {
    setcookie("usuario[nombre]", filter_input(INPUT_POST, "usuarioExistente"), time() + 604800);
    setcookie("usuario[nVisitas]", '0', time() + 604800);
    setcookie("usuario[fConn]", $fechaUltimaConexion, time() + 604800);
}
?>
<?php
/* En el caso de que se pulse el boton Crear cuenta del formulario de inicio de sesión
 *  serás redirigido al formulario de registro
 */
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

            // Creación de la conexión
            $conexionBD = new mysqli();

            //Intento de conexión a la base de datos
            try {
                $conexionBD->connect("localhost", "root", "", "espectaculos");
            } catch (Exception $ex) {
                $mensajeError .= "ERROR: " . $ex->getMessage();
            }
            //Si el boton entrar  del formulario de inicio de sesión se pulsa, realiza las siguientes operaciones.
            if (filter_has_var(INPUT_POST, "Entrar")) {
                try {
                    //Validamos el usuario con los datos de la base de datos
                    $usuarioLogin = validarUsuarioExistente(filter_input(INPUT_POST, "usuarioExistente"), $conexionBD);
                    if ($usuarioLogin) {
                        //Extraemos la contraseña del usuario ya registrado
                        $conexionBD->autocommit(false);
                        $consultaSesiones = $conexionBD->query("SELECT contraseña FROM usuarios WHERE login='$usuarioLogin'");
                        if ($consultaSesiones->num_rows > 0) {
                            $contraseña = $consultaSesiones->fetch_all(MYSQLI_ASSOC);
                            foreach ($contraseña as $contraseñaExistente) {
                                //Si las dos contraseñas cifradas son exactas, el inico de sesión se realiza con exito.
                                $contraseñaEncriptada = hash("sha512", filter_input(INPUT_POST, "contraseñaExistente"));
                                $esValida = $contraseñaEncriptada === $contraseñaExistente['contraseña'];
                                if ($esValida) {
                                    $mensajeExito .= "Inicio de Sesión realizado con éxito";
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
                <h2>Mensajes de éxito: </h2>
                <ul>
                    <li><?php
                        if (isset($mensajeExito)) {
                            echo $mensajeExito;
                        }
                        ?></li>
                </ul>
                <h2>Elementos de la cookie usuario : </h2>
                <ul>
                    <li><?php
                        if (filter_has_var(INPUT_COOKIE, "usuario")) {
                            foreach ($datosCookieUsuario as $claveCookie => $valorCookie) {
                                echo nl2br("Valor $claveCookie: $valorCookie\n\n");
                            }
                        }
                        ?></li>
                </ul>
                <?php
            }
            ?>
            <!-- FORMULARIO DE BOTONES DE COOKIES DE REINICIO Y BORRADO -->
            <form action="" method="post">
                <button type="submit" name="reiniciarVisitas">Reiniciar Visitas</button>
                <button type="submit" name="eliminarCookie">Borrar Cookie</button>
            </form>
        </body>
    </html>
    <?php
}