
<?php
session_start();
require 'baseDeDatos.php';
requireAuthentication();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title> MG Fut </title>
    <link href="CSSmenuInicio.css" type="text/css" rel="stylesheet">
    <style>
        .btn-modo {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 8px 15px;
            background: #2c3e50;
            color: #8BA3C5;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        body.modo-oscuro {
            background-color: #121212;
            color: #e0e0e0;
        }
        body.modo-oscuro header, body.modo-oscuro footer {
            background-color: #1e1e1e;
        }
    </style>
</head>

<body>
    <button class="btn-modo" onclick="alternarModo()">Cambiar Modo</button>

    <header>
        <div class="MGfut">
            <h2> MG Fut</h2>
        </div>
    </header>

    <main>
        <div class="botonesMenu">
            <button class="btnClubes">
                <a href="clubes.php" style="color: #8BA3C5">Clubes</a>
            </button>

            <button class="btnJugadores">
                <a href="jugadores.php" style="color: #8BA3C5">Jugadores</a>
            </button>

            <button class="btnFixture">
                <a href="fixture.php" style="color: #8BA3C5">Fixture</a>
            </button>

            <button class="btnEstadisticas">
                <a href="estadisticas.php" style="color: #8BA3C5">Estadísticas</a>
            </button>
            <button><a href="partidos.php" style="color: #8BA3C5">Partidos</a></button>
            <button><a href="carnets.php" style="color: #8BA3C5">Carnets</a></button>
            <button><a href="sanciones.php" style="color: #8BA3C5">Sanciones</a></button>
            <button><a href="clasificacion.php" style="color: #8BA3C5">Clasificación</a></button>
        </div>
    </main>

    <footer>
        <br>
        <hr>
        <h2 align="center"> ¿Quiénes somos?</h2>
        <div class="grupo_bloques_footer">
            <div class="bloques_footer">
                <p> Somos ANS Solutions, un grupo de desarrollo de software independiente localizado en Rivera, Uruguay.
                    Estamos especializados en la creacion de sitios webs y bases de datos para negocios y empresas grandes.
                </p>
            </div>
            <div class="bloques_footer">
                <h3 align="center">Contactos: </h3>
                <ul>
                    <li>Email: contacto@ANSsolutions.com</li>
                    <li>Teléfono: +598 091-981-722</li>
                </ul>
            </div>
            <div class="bloques_footer">
                <p>© Copyright 2026 ANS Solutions. Todos los derechos reservados. 
                    Ninguna parte de este sistema o sitio web será reproducido, de ninguna forma, 
                    incluyendo pero no limitando al host de un sitio web no autorizado por ANS Solutions.
                </p>
            </div>
        </div>
        <br>
        <br>
    </footer>

    <script>
        function alternarModo() {
            document.body.classList.toggle('modo-oscuro');
            if (document.body.classList.contains('modo-oscuro')) {
                localStorage.setItem('modo', 'oscuro');
            } else {
                localStorage.setItem('modo', 'claro');
            }
        }

        window.onload = function() {
            if (localStorage.getItem('modo') === 'oscuro') {
                document.body.classList.add('modo-oscuro');
            }
        }
    </script>
</body>

</html>
