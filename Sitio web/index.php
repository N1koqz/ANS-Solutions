<?php
	require_once __DIR__ . "/aplicacion/inicio.php";

	//Si ya hay sesion se va directo al menu
	if (estaLogueado()) {
		header("Location: menuInicio.php");
		exit();
	}
?>
<html>
<head>
	<meta charset="UTF-8">
	<title> MG Fut </title>
	<link href="recursos/css/CSSindex.css" type="text/css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>

<body>

    <div class="contenedor-general">
        <div class="contenedor-lateral">
            <div class="inicio-container">
                <div class="logo-area"></div>
            

                <div class="contenido-inicio">

                    <h1>Inicio de sesión</h1>

                </div>

            </div>

            <form action="login.php" method="POST">
                <?php if (isset($_GET["error"])): ?>
                <p style="color: #d96b6b">
                    <?php
                        $errores = ["2" => "Demasiados intentos, esperá un minuto"];
                        echo is_string($_GET["error"]) ? ($errores[$_GET["error"]] ?? "Correo o contraseña incorrectos") : "Correo o contraseña incorrectos";
                    ?>
                </p>
                <?php endif; ?>

                <input class="input-email"
                    type="text"
                    name="email"
                    autocomplete="username"
                    placeholder="Usuario o correo electrónico"
                    required
                    autofocus
                >

                <div class="campo-password">
                    <input class="input-password"
                        id="passwordLogin"
                        type="password"
                        name="password"
                        placeholder="Contraseña"
                        required
                    >
                    <button class="boton-ver-password" type="button" id="alternarPassword" aria-label="Mantener presionado para ver la contraseña" aria-pressed="false">
                        <img src="recursos/imagenes/eye.svg" alt="">
                    </button>
                </div>
                <div class="botonIniciarSesion">
                    <button type="submit" class="btnIniciarSesion" style="color: #ffffff">
                        Iniciar Sesión
                    </button>
                </div> 
            </form>

            <div class="botonIniciarSesion">
                <a class="btnIniciarSesion enlaceBoletines" href="prensa.php">Boletines de prensa</a>
            </div>

        </div>

        <div class="img-panel"></div>
    </div>

        <script>
            const password = document.getElementById("passwordLogin");
            const boton = document.getElementById("alternarPassword");
            const icono = boton.querySelector("img");
            const mostrar = (visible) => {
                password.type = visible ? "text" : "password";
                boton.setAttribute("aria-pressed", visible);
                boton.setAttribute("aria-label", visible ? "Soltar para ocultar" : "Mantener presionado para ver");
                icono.src = `recursos/imagenes/eye${visible ? "-off" : ""}.svg`;
            };
            const tecla = (event) => ["Space", "Enter"].includes(event.code);

            boton.addEventListener("pointerdown", (event) => {
                if (event.button === 0) { event.preventDefault(); mostrar(true); }
            });
            ["pointerup", "pointercancel"].forEach((tipo) => document.addEventListener(tipo, () => mostrar(false)));
            boton.addEventListener("keydown", (event) => { if (tecla(event)) { event.preventDefault(); mostrar(true); } });
            boton.addEventListener("keyup", (event) => { if (tecla(event)) mostrar(false); });
            boton.addEventListener("blur", () => mostrar(false));
            window.addEventListener("blur", () => mostrar(false));
        </script>

</body>
</html>
