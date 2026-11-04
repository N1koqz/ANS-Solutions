<?php
//Constructor de la pagina: HTML compartido (menu, pie, celdas de tabla).
//No toca la base de datos; la conexion, sesion y permisos viven en baseDeDatos.php
//La conexion, sesion y permisos los carga aplicacion/inicio.php (esta plantilla se carga desde alli)

//Devuelve una celda de tabla con el valor escapado
function celda($valor) {
    return "<td>" . htmlspecialchars((string)$valor) . "</td>";
}

//Columna de acciones, solo se imprime si el usuario es admin
function botonesAdmin($pagina, $id) {
    if (esAdmin()) {
        echo "<td><a class='botonEditar' href='" . $pagina . "?id=" . urlencode($id) . "#sel'><img src='recursos/imagenes/edit_icon.svg' alt='Editar'></a></td>";
    }
}

//Cabecera y menu compartidos por todas las paginas
function menu() {
    $botones = [
        ["btnClubes", "clubes.php", "Clubes"],
        ["btnJugadores", "jugadores.php", "Jugadores"],
        ["btnPartidos", "partidos.php", "Partidos"],
        ["btnEstadisticas", "estadisticas.php", "Estadísticas"],
        ["btnSanciones", "sanciones.php", "Sanciones"],
        ["btnCarnets", "carnets.php", "Carnets"]
    ];
?>
	<header>
        <hr>
		<div class="MGfut">
			<a href="menuInicio.php" class="logo" aria-label="MG FUT">
				<span class="red-container" aria-hidden="true">
					<span class="pelota">
						<img src="recursos/imagenes/soccerball.svg" alt="">
					</span>
					<img class="red" src="recursos/imagenes/net.png" alt="">
				</span>
				<span class="logoTexto">MG FUT</span>
			</a>
			
		<div class="botonesMenu" align="right">
    		<?php foreach ($botones as [$clase, $archivo, $texto]): ?>
				<a href="<?= htmlspecialchars($archivo) ?>"class="<?= htmlspecialchars($clase) ?>"><?= htmlspecialchars($texto) ?>
				</a>
			<?php endforeach; ?>
			</div>
		</div>
        <hr>
		<div class="usuarioIniciado"> 
			<form action="login.php" method="POST" style="margin:0">
                    <input type="hidden" name="csrf" value="<?= tokenCsrf() ?>">
                    <input type="hidden" name="accion" value="salir">
                    <button class="btnCerrarSesion" type="submit" style="color: #e6e6e6">
                        Cerrar sesión
                        <img src="recursos/imagenes/log_off_icon.svg" alt="" width="20" height="20">
                    </button>
                </form>
				<span class="usuarioActual">
					<img src="recursos/imagenes/person-circle.svg" alt="" width="20" height="20">
					Usuario: <?= htmlspecialchars((string)($_SESSION["email"] ?? "")) ?>
				</span>
		</div>
	</header>
<?php
}

//Pie de pagina compartido
function pie() {
?>
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
			<div class="bloques_footer" align="center">
                
				<h3>Contactos:</h3>
				<p><img src="recursos/imagenes/envelope-fill.svg" style="filter: invert(1) brightness(0.9);width: 17px;height: 17px;">
                    Email: contacto@ANSsolutions.com
                </p>
				<p><img src="recursos/imagenes/telephone-fill.svg" style="filter: invert(1) brightness(0.9);width: 17px;height: 17px;">
                    Teléfono: +598 091-981-722
                </p>
			</div>
			<div class="bloques_footer">
				<p>© Copyright 2026 ANS Solutions. Todos los derechos reservados. 
					Ninguna parte de este sistema o sitio web será reproducido, de ninguna forma, 
					incluyendo pero no limitando al host de un sitio web no autorizado por ANS Solutions.
				</p>
			</div>
		</div>
        <hr>
        <div class="franja_logo">
            <img src="recursos/imagenes/LOGO ANS SMALL.svg" width="100px" alt="ANS Solutions">
        </div>
		<br>
        <hr>
		<br>
	</footer>
<?php
}
?>
