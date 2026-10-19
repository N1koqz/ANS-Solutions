<?php
    //Editor integrado en jugadores.php, clubes.php, sanciones.php y estadisticas.php (solo se muestra a administradores)
    //Uso en cada pagina:
    //  arriba de la pagina:  require_once "editor.php" y luego procesarEditor("jugadores")
    //  dentro del head:  estilosEditor()
    //  sobre la barra de busqueda:  mostrarEditor("jugadores")
    require_once "baseDeDatos.php";

    //Lista blanca de tablas y columnas editables. Nada que venga del cliente decide nombres de tablas ni columnas
    function entidadesEditor() {
        return [
            "jugadores" => [
                "pagina" => "jugadores.php",
                "titulo" => "jugadores",
                "tabla" => "jugadores",
                "pk" => "ci",
                "auto" => false,
                "etiqueta" => "CONCAT(nombre, ' ', apellido)",
                "busqueda" => "CONCAT_WS(' ', ci, nombre, apellido, posicion)",
                "campos" => [
                    "ci" => ["etiqueta" => "Cédula", "tipo" => "text", "max" => 15],
                    "nombre" => ["etiqueta" => "Nombre", "tipo" => "text", "max" => 50],
                    "apellido" => ["etiqueta" => "Apellido", "tipo" => "text", "max" => 50],
                    "fecha_nacimiento" => ["etiqueta" => "Fecha de Nacimiento", "tipo" => "date"],
                    "posicion" => ["etiqueta" => "Posición", "tipo" => "text", "max" => 30],
                    "dorsal" => ["etiqueta" => "N° de camiseta", "tipo" => "number", "min" => 1, "max" => 99, "paso" => 1, "entero" => true],
                    "id_club" => ["etiqueta" => "Club", "tipo" => "select", "origen" => ["club", "id_club", "nombre"]],
                    "id_categoria" => ["etiqueta" => "Categoría", "tipo" => "select", "origen" => ["categorias", "id_categoria", "nombre"]],
                    "masa" => ["etiqueta" => "Masa", "tipo" => "number", "min" => 1, "max" => 300],
                    "altura" => ["etiqueta" => "Altura", "tipo" => "number", "min" => 0.5, "max" => 250]
                ]
            ],
            "clubes" => [
                "pagina" => "clubes.php",
                "titulo" => "clubes",
                "tabla" => "club",
                "pk" => "id_club",
                "auto" => true,
                "etiqueta" => "nombre",
                "busqueda" => "CONCAT_WS(' ', nombre, ciudad, fundacion)",
                "campos" => [
                    "nombre" => ["etiqueta" => "Nombre", "tipo" => "text", "max" => 50],
                    "ciudad" => ["etiqueta" => "Ciudad", "tipo" => "text", "max" => 50],
                    "fundacion" => ["etiqueta" => "Año de Fundación", "tipo" => "number", "min" => 1800, "max" => 2100, "paso" => 1, "entero" => true]
                ]
            ],
            "sanciones" => [
                "pagina" => "sanciones.php",
                "titulo" => "sanciones",
                "tabla" => "sanciones",
                "pk" => "id_sancion",
                "auto" => true,
                "etiqueta" => "CONCAT(tipo_sancion, ' (', ci, ')')",
                "busqueda" => "CONCAT_WS(' ', id_sancion, ci, tipo_sancion, motivo)",
                "campos" => [
                    "ci" => ["etiqueta" => "Cédula del jugador", "tipo" => "number", "min" => 1, "max" => 2147483647, "paso" => 1, "entero" => true, "existe" => ["jugadores", "ci", "No existe ningún jugador con esa cédula"]],
                    "tipo_sancion" => ["etiqueta" => "Tipo de Sanción", "tipo" => "text", "max" => 50],
                    "motivo" => ["etiqueta" => "Motivo", "tipo" => "textarea", "max" => 255],
                    "fecha_sancion" => ["etiqueta" => "Fecha de Suspensión", "tipo" => "date"],
                    "fecha_limite" => ["etiqueta" => "Fecha Límite", "tipo" => "date"]
                ]
            ],
            "estadisticas" => [
                "pagina" => "estadisticas.php",
                "titulo" => "estadísticas",
                "tabla" => "estadisticas",
                "pk" => "id_estadistica",
                "auto" => true,
                "etiqueta" => "CONCAT('Partido ', id_partido, ' - CI ', ci_jugador)",
                "busqueda" => "CONCAT_WS(' ', id_partido, ci_jugador)",
                "regla" => "validarEstadistica",
                "campos" => [
                    "id_partido" => ["etiqueta" => "Partido", "tipo" => "select", "orden" => "t DESC", "origen" => ["partidos", "id_partido",
                        "CONCAT(fecha, ' - ', (SELECT nombre FROM club WHERE club.id_club = partidos.id_club_local), ' vs ', (SELECT nombre FROM club WHERE club.id_club = partidos.id_club_visitante))"]],
                    "ci_jugador" => ["etiqueta" => "Cédula del jugador", "tipo" => "number", "min" => 1, "max" => 2147483647, "paso" => 1, "entero" => true, "existe" => ["jugadores", "ci", "No existe ningún jugador con esa cédula"]],
                    "goles" => ["etiqueta" => "Goles", "tipo" => "number", "min" => 0, "max" => 30, "paso" => 1, "entero" => true],
                    "asistencias" => ["etiqueta" => "Asistencias", "tipo" => "number", "min" => 0, "max" => 30, "paso" => 1, "entero" => true],
                    "amarillas" => ["etiqueta" => "Tarjetas amarillas", "tipo" => "number", "min" => 0, "max" => 2, "paso" => 1, "entero" => true],
                    "rojas" => ["etiqueta" => "Tarjetas rojas", "tipo" => "number", "min" => 0, "max" => 1, "paso" => 1, "entero" => true]
                ]
            ]
        ];
    }

    //Opciones de un campo select: [["v" => valor, "t" => texto], ...]
    function opcionesDe($conn, $campo) {
        [$tabla, $valor, $texto] = $campo["origen"];
        $orden = $campo["orden"] ?? "t";
        return $conn->query("SELECT $valor AS v, $texto AS t FROM $tabla ORDER BY $orden")->fetch_all(MYSQLI_ASSOC);
    }

    //Reglas de estadisticas que involucran varios campos a la vez. Devuelve el mensaje de error o null
    function validarEstadistica($conn, $v, $idActual) {
        //El partido y su resultado
        $stmt = $conn->prepare("SELECT id_club_local, id_club_visitante, goles_local, goles_visitante FROM partidos WHERE id_partido = ?");
        $stmt->bind_param("s", $v["id_partido"]);
        $stmt->execute();
        $partido = $stmt->get_result()->fetch_assoc();

        //El club del jugador
        $stmt = $conn->prepare("SELECT id_club FROM jugadores WHERE ci = ?");
        $stmt->bind_param("s", $v["ci_jugador"]);
        $stmt->execute();
        $jugador = $stmt->get_result()->fetch_assoc();

        if (!$partido || !$jugador) {
            return "Partido o jugador no válido";
        }
        $club = (int)$jugador["id_club"];
        if ($club !== (int)$partido["id_club_local"] && $club !== (int)$partido["id_club_visitante"]) {
            return "El jugador no pertenece a ninguno de los clubes de ese partido";
        }

        //Un jugador solo puede tener una fila por partido
        $actual = (int)$idActual;
        $stmt = $conn->prepare("SELECT 1 FROM estadisticas WHERE id_partido = ? AND ci_jugador = ? AND id_estadistica <> ?");
        $stmt->bind_param("ssi", $v["id_partido"], $v["ci_jugador"], $actual);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            return "Ese jugador ya tiene estadísticas cargadas en ese partido (editá el registro existente)";
        }

        //Si el partido ya tiene resultado, el jugador no puede haber marcado mas goles que su club
        $golesClub = $club === (int)$partido["id_club_local"] ? $partido["goles_local"] : $partido["goles_visitante"];
        if ($golesClub !== null && (int)$v["goles"] > (int)$golesClub) {
            return "El jugador no puede tener más goles (" . (int)$v["goles"] . ") que su club en ese partido (" . (int)$golesClub . ")";
        }
        return null;
    }

    //Valida un valor segun la definicion del campo. Devuelve el mensaje de error o null si es valido
    function validarCampo($conn, $campo, $valor) {
        if (!is_string($valor) || trim($valor) === "") {
            return $campo["etiqueta"] . " es obligatorio";
        }
        $valor = trim($valor);

        switch ($campo["tipo"]) {
            case "number":
                if (!is_numeric($valor) || $valor < $campo["min"] || $valor > $campo["max"]
                    || (!empty($campo["entero"]) && filter_var($valor, FILTER_VALIDATE_INT) === false)) {
                    return $campo["etiqueta"] . " debe ser un número entre " . $campo["min"] . " y " . $campo["max"];
                }
                //Si el campo debe corresponder a un registro existente (por ejemplo la cédula de un jugador)
                if (isset($campo["existe"])) {
                    [$tablaRef, $columnaRef, $mensajeRef] = $campo["existe"];
                    $stmt = $conn->prepare("SELECT 1 FROM $tablaRef WHERE $columnaRef = ?");
                    $stmt->bind_param("s", $valor);
                    $stmt->execute();
                    if ($stmt->get_result()->num_rows === 0) {
                        return $mensajeRef;
                    }
                }
                break;
            case "date":
                $fecha = DateTime::createFromFormat("Y-m-d", $valor);
                if (!$fecha || $fecha->format("Y-m-d") !== $valor) {
                    return $campo["etiqueta"] . " no es una fecha válida";
                }
                break;
            case "select":
                [$tabla, $columna] = $campo["origen"];
                $stmt = $conn->prepare("SELECT 1 FROM $tabla WHERE $columna = ?");
                $stmt->bind_param("s", $valor);
                $stmt->execute();
                if ($stmt->get_result()->num_rows === 0) {
                    return $campo["etiqueta"] . " no es válido";
                }
                break;
            default:
                if (mb_strlen($valor) > $campo["max"]) {
                    return $campo["etiqueta"] . " admite hasta " . $campo["max"] . " caracteres";
                }
        }
        return null;
    }

    //Crear, editar y eliminar. Debe llamarse antes de generar cualquier HTML (termina con una redireccion)
    function procesarEditor($tipo) {
        global $conn;

        //Los unicos POST que recibe cada pagina son los del editor
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            return;
        }
        requerirAdmin();
        verificarCsrf();

        $entidad = entidadesEditor()[$tipo];
        $tabla = $entidad["tabla"];
        $pk = $entidad["pk"];
        $campos = $entidad["campos"];
        $editables = array_diff_key($campos, [$pk => true]);
        $accion = $_POST["accion"] ?? "";
        $id = isset($_POST["id"]) && is_string($_POST["id"]) ? $_POST["id"] : "";
        $busqueda = isset($_POST["busqueda"]) && is_string($_POST["busqueda"]) ? trim($_POST["busqueda"]) : "";

        //Lo escrito en el formulario, para no perderlo si hay un error
        $datos = [];
        foreach ($campos as $nombre => $campo) {
            $datos[$nombre] = isset($_POST[$nombre]) && is_string($_POST[$nombre]) ? $_POST[$nombre] : "";
        }

        //Guarda el mensaje en la sesion y vuelve a la pagina (el mensaje se muestra una sola vez)
        $volver = function ($ok, $texto, $idRegistro = "", $datosFormulario = null) use ($entidad, $busqueda) {
            $_SESSION["flash"] = ["ok" => $ok, "texto" => $texto, "datos" => $datosFormulario];
            $parametros = [];
            if ($busqueda !== "") {
                $parametros["busqueda"] = $busqueda;
            }
            if ($idRegistro !== "") {
                $parametros["id"] = $idRegistro;
            }
            header("Location: " . $entidad["pagina"] . ($parametros ? "?" . http_build_query($parametros) : "") . ($idRegistro !== "" ? "#sel" : ""));
            exit();
        };

        try {
            if ($accion === "eliminar") {
                if ($id === "") {
                    $volver(false, "Registro no indicado");
                }
                if ($tipo === "clubes") {
                    $conn->begin_transaction();
                }
                $stmt = $conn->prepare("DELETE FROM $tabla WHERE $pk = ?");
                $stmt->bind_param("s", $id);
                $stmt->execute();
                if ($stmt->affected_rows > 0) {
                    //Al eliminar un club tambien se elimina su usuario de acceso
                    if ($tipo === "clubes") {
                        $stmt = $conn->prepare("DELETE FROM usuario WHERE club = ? AND rol <> 'admin'");
                        $stmt->bind_param("s", $id);
                        $stmt->execute();
                        $conn->commit();
                    }
                    $volver(true, "Registro eliminado");
                }
                $conn->rollback();
                $volver(false, "El registro no existe");
            }

            if ($accion !== "crear" && $accion !== "editar") {
                $volver(false, "Accion invalida");
            }
            if ($accion === "editar" && $id === "") {
                $volver(false, "Registro no indicado");
            }

            //Al crear se validan todos los campos, al editar no se toca la clave
            $valores = [];
            foreach ($accion === "crear" ? $campos : $editables as $nombre => $campo) {
                $error = validarCampo($conn, $campo, $datos[$nombre]);
                if ($error) {
                    $volver(false, $error, $accion === "editar" ? $id : "", $datos);
                }
                $valores[$nombre] = trim($datos[$nombre]);
            }

            //Regla entre campos propia de la entidad (por ejemplo las estadisticas)
            if (!empty($entidad["regla"])) {
                $error = $entidad["regla"]($conn, $valores, $accion === "editar" ? $id : "");
                if ($error) {
                    $volver(false, $error, $accion === "editar" ? $id : "", $datos);
                }
            }

            if ($accion === "crear") {
                //Los clubes se crean junto con su usuario de acceso. La contraseña nunca se devuelve al formulario
                $clave = "";
                if ($tipo === "clubes") {
                    $clave = isset($_POST["clave"]) && is_string($_POST["clave"]) ? $_POST["clave"] : "";
                    if (strlen($clave) < 8 || strlen($clave) > 72) {
                        $volver(false, "La contraseña debe tener entre 8 y 72 caracteres", "", $datos);
                    }
                    $stmt = $conn->prepare("SELECT 1 FROM usuario WHERE email = ?");
                    $stmt->bind_param("s", $valores["nombre"]);
                    $stmt->execute();
                    if ($stmt->get_result()->num_rows > 0) {
                        $volver(false, "Ya existe un usuario con ese nombre", "", $datos);
                    }
                    $conn->begin_transaction();
                }

                $columnas = implode(", ", array_keys($valores));
                $marcas = implode(", ", array_fill(0, count($valores), "?"));
                $stmt = $conn->prepare("INSERT INTO $tabla ($columnas) VALUES ($marcas)");
                $stmt->bind_param(str_repeat("s", count($valores)), ...array_values($valores));
                $stmt->execute();
                $idNuevo = (string)($entidad["auto"] ? $conn->insert_id : $valores[$pk]);

                if ($tipo === "clubes") {
                    $hash = password_hash($clave, PASSWORD_DEFAULT);
                    $rol = "club";
                    $stmt = $conn->prepare("INSERT INTO usuario (nombre, email, `contraseña`, rol, club) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssss", $valores["nombre"], $valores["nombre"], $hash, $rol, $idNuevo);
                    $stmt->execute();
                    $conn->commit();
                }
                $volver(true, "Registro creado", $idNuevo);
            }

            $asignaciones = implode(", ", array_map(fn($columna) => "$columna = ?", array_keys($valores)));
            $parametros = [...array_values($valores), $id];
            $stmt = $conn->prepare("UPDATE $tabla SET $asignaciones WHERE $pk = ?");
            $stmt->bind_param(str_repeat("s", count($parametros)), ...$parametros);
            $stmt->execute();
            $volver(true, "Cambios guardados", $id);
        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            error_log("editor.php: " . $e->getMessage());
            $mensajes = [
                1062 => "Ya existe un registro con esos datos",
                1451 => "No se puede eliminar: tiene registros relacionados",
                1452 => "Una de las referencias no existe"
            ];
            $volver(false, $mensajes[$e->getCode()] ?? "No se pudo guardar el registro", $id, $accion === "eliminar" ? null : $datos);
        }
    }

    //Estilos del editor, para el <head> (solo se imprimen si el usuario es admin)
    function estilosEditor() {
        if (!esAdmin()) {
            return;
        }
?>
	<link rel="stylesheet" href="CSSeditor.css">
<?php
    }

    //Panel del editor (slider + formulario). Usa la misma barra de busqueda de la pagina para filtrar el slider
    function mostrarEditor($tipo) {
        global $conn;

        //Los controles administrativos solo se dibujan para el rol admin
        if (!esAdmin()) {
            return;
        }

        $entidad = entidadesEditor()[$tipo];
        $pagina = $entidad["pagina"];
        $pk = $entidad["pk"];
        $campos = $entidad["campos"];
        $busqueda = textoBusqueda();
        $id = isset($_GET["id"]) && is_string($_GET["id"]) ? $_GET["id"] : "";

        //Mensaje de la ultima operacion (se muestra una sola vez)
        $flash = $_SESSION["flash"] ?? null;
        unset($_SESSION["flash"]);

        //Registros que alimentan el slider
        $columnas = implode(", ", array_unique(array_merge([$pk], array_keys($campos))));
        $consulta = "SELECT $columnas, " . $entidad["etiqueta"] . " AS _etiqueta FROM " . $entidad["tabla"];

        $patron = patronBusqueda();
        $stmt = $conn->prepare("$consulta WHERE " . $entidad["busqueda"] . " LIKE ? ORDER BY _etiqueta LIMIT 300");
        $stmt->bind_param("s", $patron);
        $stmt->execute();
        $registros = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        //Registro seleccionado (si no esta en los resultados de la busqueda se consulta aparte)
        $indice = -1;
        foreach ($registros as $i => $registro) {
            if ((string)$registro[$pk] === $id) {
                $indice = $i;
                break;
            }
        }
        $seleccionado = $indice >= 0 ? $registros[$indice] : null;
        if ($seleccionado === null && $id !== "") {
            $stmt = $conn->prepare("$consulta WHERE $pk = ?");
            $stmt->bind_param("s", $id);
            $stmt->execute();
            $seleccionado = $stmt->get_result()->fetch_assoc();
        }

        //Flechas del slider: anterior y siguiente segun la posicion en la lista
        $anterior = $indice > 0 ? $registros[$indice - 1][$pk] : null;
        $siguiente = isset($registros[$indice + 1]) ? $registros[$indice + 1][$pk] : null;

        //Valores del formulario: lo ultimo escrito (si hubo error), el registro elegido o vacio
        $valores = $flash["datos"] ?? $seleccionado ?? [];
        $confirmando = $seleccionado !== null && ($_GET["confirmar"] ?? "") === "1";

        //Enlace a esta misma pagina conservando la busqueda
        $url = function ($idRegistro = "", $confirmar = false) use ($pagina, $busqueda) {
            $parametros = [];
            if ($busqueda !== "") {
                $parametros["busqueda"] = $busqueda;
            }
            if ($idRegistro !== "") {
                $parametros["id"] = $idRegistro;
            }
            if ($confirmar) {
                $parametros["confirmar"] = 1;
            }
            return htmlspecialchars($pagina . ($parametros ? "?" . http_build_query($parametros) : ""));
        };

        //Campos ocultos comunes a los formularios del editor
        $ocultos = function ($accion, $idRegistro = "") use ($busqueda) {
            echo "<input type='hidden' name='csrf' value='" . tokenCsrf() . "'>";
            echo "<input type='hidden' name='accion' value='" . $accion . "'>";
            echo "<input type='hidden' name='busqueda' value='" . htmlspecialchars($busqueda) . "'>";
            if ($idRegistro !== "") {
                echo "<input type='hidden' name='id' value='" . htmlspecialchars($idRegistro) . "'>";
            }
        };
?>
		<section id="editor">
			<h2>Editor de <?= htmlspecialchars($entidad["titulo"]) ?></h2>
<?php if ($flash): ?>
			<p class="mensaje <?= $flash["ok"] ? "ok" : "error" ?>" role="status"><?= htmlspecialchars($flash["texto"]) ?></p>
<?php endif; ?>

<?php if ($confirmando): ?>
			<form action="<?= htmlspecialchars($pagina) ?>" method="POST" class="confirmar">
				<?php $ocultos("eliminar", (string)$seleccionado[$pk]); ?>
				<p>¿Eliminar <strong><?= htmlspecialchars($seleccionado["_etiqueta"]) ?></strong>? Esta acción no se puede deshacer.</p>
				<button type="submit" class="btn-eliminar">Sí, eliminar</button>
				<a class="btn-nuevo" href="<?= $url((string)$seleccionado[$pk]) ?>#sel">Cancelar</a>
			</form>
<?php endif; ?>

			<form action="<?= htmlspecialchars($pagina) ?>" method="POST" autocomplete="off">
				<?php $ocultos($seleccionado ? "editar" : "crear", $seleccionado ? (string)$seleccionado[$pk] : ""); ?>
				<h3><?= $seleccionado ? "Editando: " . htmlspecialchars($seleccionado["_etiqueta"]) : "Nuevo registro" ?></h3>
				<div class="editor-campos">
<?php foreach ($campos as $nombre => $campo):
	$actual = (string)($valores[$nombre] ?? "");
?>
					<label>
						<span><?= htmlspecialchars($campo["etiqueta"]) ?></span>
<?php if ($campo["tipo"] === "select"): ?>
						<select name="<?= $nombre ?>" required>
							<option value="">Seleccionar...</option>
<?php foreach (opcionesDe($conn, $campo) as $opcion): ?>
							<option value="<?= htmlspecialchars($opcion["v"]) ?>"<?= (string)$opcion["v"] === $actual ? " selected" : "" ?>><?= htmlspecialchars($opcion["t"]) ?></option>
<?php endforeach; ?>
						</select>
<?php elseif ($campo["tipo"] === "textarea"): ?>
						<textarea name="<?= $nombre ?>" maxlength="<?= $campo["max"] ?>" rows="3" required><?= htmlspecialchars($actual) ?></textarea>
<?php else:
	$atributos = "";
	if ($campo["tipo"] === "text") {
		$atributos = "maxlength='" . $campo["max"] . "'";
	} elseif ($campo["tipo"] === "number") {
		$atributos = "min='" . $campo["min"] . "' max='" . $campo["max"] . "' step='" . ($campo["paso"] ?? "any") . "'";
	}
	//La clave (por ejemplo la cedula) no se cambia una vez creado el registro
	if ($nombre === $pk && $seleccionado) {
		$atributos .= " readonly";
	}
?>
						<input type="<?= $campo["tipo"] ?>" name="<?= $nombre ?>" value="<?= htmlspecialchars($actual) ?>" <?= $atributos ?> required>
<?php endif; ?>
					</label>
<?php endforeach; ?>
<?php if ($tipo === "clubes" && !$seleccionado): ?>
					<label>
						<span>Contraseña de acceso del club</span>
						<input type="password" name="clave" minlength="8" maxlength="72" autocomplete="new-password" required>
					</label>
<?php endif; ?>
				</div>

				<div class="editor-acciones">
					<button type="submit" class="btn-guardar">Guardar cambios</button>
					<a class="btn-nuevo" href="<?= $url() ?>">Nuevo registro</a>
<?php if ($seleccionado): ?>
					<a class="btn-eliminar" href="<?= $url((string)$seleccionado[$pk], true) ?>#sel">Eliminar</a>
<?php endif; ?>
				</div>
			</form>
		</section>
<?php
    }
?>
