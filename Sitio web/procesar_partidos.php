<?php
    //Logica de partidos: crear y concluir (solo admin) y el panel que se muestra en partidos.php
    //Uso en partidos.php:  procesarPartidos() antes del HTML  y  mostrarFormularioPartidos() dentro de <main>
    require_once "baseDeDatos.php";

    //Valida un entero dentro de un rango. Devuelve el entero o null
    function enteroEnRango($valor, $min, $max) {
        $n = filter_var($valor, FILTER_VALIDATE_INT);
        return ($n !== false && $n >= $min && $n <= $max) ? $n : null;
    }

    function existeClub($conn, $id) {
        $stmt = $conn->prepare("SELECT 1 FROM club WHERE id_club = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    //Crear y concluir partidos. Debe llamarse antes de generar HTML (termina con una redireccion)
    function procesarPartidos() {
        global $conn;

        //Los unicos POST de la pagina son los de este panel
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            return;
        }
        requerirAdmin();
        verificarCsrf();

        $accion = isset($_POST["accion"]) && is_string($_POST["accion"]) ? $_POST["accion"] : "";
        $campo = fn($nombre) => isset($_POST[$nombre]) && is_string($_POST[$nombre]) ? trim($_POST[$nombre]) : "";

        //Guarda el mensaje en la sesion y vuelve a la pagina (se muestra una sola vez)
        $volver = function ($ok, $texto, $datos = null, $concluir = "") {
            $_SESSION["flash"] = ["ok" => $ok, "texto" => $texto, "datos" => $datos];
            header("Location: partidos.php" . ($concluir !== "" ? "?concluir=" . urlencode($concluir) . "#sel" : ""));
            exit();
        };

        try {
            if ($accion === "crear") {
                $datos = ["id_club_local" => $campo("id_club_local"), "id_club_visitante" => $campo("id_club_visitante"),
                          "fecha" => $campo("fecha"), "estadio" => $campo("estadio")];

                $local = enteroEnRango($datos["id_club_local"], 1, PHP_INT_MAX);
                $visitante = enteroEnRango($datos["id_club_visitante"], 1, PHP_INT_MAX);
                if ($local === null || $visitante === null || !existeClub($conn, $local) || !existeClub($conn, $visitante)) {
                    $volver(false, "Elegí un club local y un club visitante válidos", $datos);
                }
                if ($local === $visitante) {
                    $volver(false, "Un club no puede jugar contra sí mismo", $datos);
                }

                $fecha = DateTime::createFromFormat("Y-m-d", $datos["fecha"]);
                if (!$fecha || $fecha->format("Y-m-d") !== $datos["fecha"]) {
                    $volver(false, "La fecha no es válida", $datos);
                }
                if ($datos["estadio"] === "" || mb_strlen($datos["estadio"]) > 50) {
                    $volver(false, "El estadio es obligatorio (hasta 50 caracteres)", $datos);
                }

                //Un partido sin goles cargados (NULL) esta programado; al concluirlo se cargan ambos goles
                $stmt = $conn->prepare("INSERT INTO partidos (id_club_local, id_club_visitante, fecha, estadio) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiss", $local, $visitante, $datos["fecha"], $datos["estadio"]);
                $stmt->execute();
                $volver(true, "Partido creado");
            }

            if ($accion === "concluir") {
                $id = enteroEnRango($campo("id"), 1, PHP_INT_MAX);
                $datos = ["goles_local" => $campo("goles_local"), "goles_visitante" => $campo("goles_visitante")];
                if ($id === null) {
                    $volver(false, "Partido no indicado");
                }
                $golesLocal = enteroEnRango($datos["goles_local"], 0, 99);
                $golesVisitante = enteroEnRango($datos["goles_visitante"], 0, 99);
                if ($golesLocal === null || $golesVisitante === null) {
                    $volver(false, "Los goles deben ser números enteros entre 0 y 99", $datos, (string)$id);
                }

                //Solo se concluye un partido que sigue programado (sin goles cargados)
                $stmt = $conn->prepare("UPDATE partidos SET goles_local = ?, goles_visitante = ? WHERE id_partido = ? AND goles_local IS NULL AND goles_visitante IS NULL");
                $stmt->bind_param("iii", $golesLocal, $golesVisitante, $id);
                $stmt->execute();
                if ($stmt->affected_rows > 0) {
                    $volver(true, "Resultado guardado");
                }
                $volver(false, "El partido no existe o ya fue concluido");
            }

            $volver(false, "Accion invalida");
        } catch (mysqli_sql_exception $e) {
            error_log("procesar_partidos.php: " . $e->getMessage());
            $volver(false, "No se pudo guardar el partido");
        }
    }

    //Panel de administracion: concluir el partido elegido y crear partidos nuevos (solo se dibuja para admin)
    function mostrarFormularioPartidos() {
        global $conn;

        if (!esAdmin()) {
            return;
        }

        $flash = $_SESSION["flash"] ?? null;
        unset($_SESSION["flash"]);
        $datos = $flash["datos"] ?? [];

        $clubes = $conn->query("SELECT id_club AS v, nombre AS t FROM club ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);

        //Partido que se esta concluyendo (?concluir=ID), solo si sigue programado
        $partido = null;
        $idConcluir = enteroEnRango($_GET["concluir"] ?? "", 1, PHP_INT_MAX);
        if ($idConcluir !== null) {
            $stmt = $conn->prepare("
                SELECT p.id_partido, cl.nombre AS local, cv.nombre AS visitante
                FROM partidos p
                INNER JOIN club cl ON p.id_club_local = cl.id_club
                INNER JOIN club cv ON p.id_club_visitante = cv.id_club
                WHERE p.id_partido = ? AND p.goles_local IS NULL AND p.goles_visitante IS NULL
            ");
            $stmt->bind_param("i", $idConcluir);
            $stmt->execute();
            $partido = $stmt->get_result()->fetch_assoc();
        }

        $ocultos = function ($accion) {
            echo "<input type='hidden' name='csrf' value='" . tokenCsrf() . "'>";
            echo "<input type='hidden' name='accion' value='" . $accion . "'>";
        };

        $opciones = function ($seleccionado) use ($clubes) {
            echo "<option value=''>Seleccionar...</option>";
            foreach ($clubes as $club) {
                $sel = (string)$club["v"] === (string)$seleccionado ? " selected" : "";
                echo "<option value='" . htmlspecialchars((string)$club["v"]) . "'$sel>" . htmlspecialchars($club["t"]) . "</option>";
            }
        };
?>
		<section id="editor">
			<h2>Administrar partidos</h2>
<?php if ($flash): ?>
			<p class="mensaje <?= $flash["ok"] ? "ok" : "error" ?>" role="status"><?= htmlspecialchars($flash["texto"]) ?></p>
<?php endif; ?>

<?php if ($partido): ?>
			<form action="partidos.php" method="POST" autocomplete="off" id="sel">
				<?php $ocultos("concluir"); ?>
				<input type="hidden" name="id" value="<?= (int)$partido["id_partido"] ?>">
				<h3>Concluir: <?= htmlspecialchars($partido["local"]) ?> vs <?= htmlspecialchars($partido["visitante"]) ?></h3>
				<div class="editor-campos">
					<label>
						<span>Goles de <?= htmlspecialchars($partido["local"]) ?> (local)</span>
						<input type="number" name="goles_local" min="0" max="99" step="1" value="<?= htmlspecialchars($datos["goles_local"] ?? "") ?>" required autofocus>
					</label>
					<label>
						<span>Goles de <?= htmlspecialchars($partido["visitante"]) ?> (visitante)</span>
						<input type="number" name="goles_visitante" min="0" max="99" step="1" value="<?= htmlspecialchars($datos["goles_visitante"] ?? "") ?>" required>
					</label>
				</div>
				<div class="editor-acciones">
					<button type="submit" class="btn-guardar">Guardar resultado</button>
					<a class="btn-nuevo" href="partidos.php">Cancelar</a>
				</div>
			</form>
			<hr>
<?php endif; ?>

			<form action="partidos.php" method="POST" autocomplete="off">
				<?php $ocultos("crear"); ?>
				<h3>Nuevo partido</h3>
				<div class="editor-campos">
					<label>
						<span>Club local</span>
						<select name="id_club_local" required><?php $opciones($datos["id_club_local"] ?? ""); ?></select>
					</label>
					<label>
						<span>Club visitante</span>
						<select name="id_club_visitante" required><?php $opciones($datos["id_club_visitante"] ?? ""); ?></select>
					</label>
					<label>
						<span>Fecha</span>
						<input type="date" name="fecha" value="<?= htmlspecialchars($datos["fecha"] ?? "") ?>" required>
					</label>
					<label>
						<span>Estadio</span>
						<input type="text" name="estadio" maxlength="50" value="<?= htmlspecialchars($datos["estadio"] ?? "") ?>" required>
					</label>
				</div>
				<div class="editor-acciones">
					<button type="submit" class="btn-guardar">Crear partido</button>
				</div>
			</form>
		</section>
<?php
    }
?>
