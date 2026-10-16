<?php
    //Editor integrado en jugadores.php, clubes.php y sanciones.php (solo se muestra a administradores)
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
                    "nombre" => ["etiqueta" => "Nombre", "tipo" => "text", "max" => 60],
                    "ciudad" => ["etiqueta" => "Ciudad", "tipo" => "text", "max" => 60],
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
                    "ci" => ["etiqueta" => "Jugador", "tipo" => "select", "origen" => ["jugadores", "ci", "CONCAT(ci, ' - ', nombre, ' ', apellido)"]],
                    "tipo_sancion" => ["etiqueta" => "Tipo de Sanción", "tipo" => "text", "max" => 50],
                    "motivo" => ["etiqueta" => "Motivo", "tipo" => "textarea", "max" => 255],
                    "fecha_sancion" => ["etiqueta" => "Fecha de Suspensión", "tipo" => "date"],
                    "fecha_limite" => ["etiqueta" => "Fecha Límite", "tipo" => "date"]
                ]
            ]
        ];
    }

    //Opciones de un campo select: [["v" => valor, "t" => texto], ...]
    function opcionesDe($conn, $campo) {
        [$tabla, $valor, $texto] = $campo["origen"];
        return $conn->query("SELECT $valor AS v, $texto AS t FROM $tabla ORDER BY t")->fetch_all(MYSQLI_ASSOC);
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
                $stmt = $conn->prepare("DELETE FROM $tabla WHERE $pk = ?");
                $stmt->bind_param("s", $id);
                $stmt->execute();
                if ($stmt->affected_rows > 0) {
                    $volver(true, "Registro eliminado");
                }
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

            if ($accion === "crear") {
                $columnas = implode(", ", array_keys($valores));
                $marcas = implode(", ", array_fill(0, count($valores), "?"));
                $stmt = $conn->prepare("INSERT INTO $tabla ($columnas) VALUES ($marcas)");
                $stmt->bind_param(str_repeat("s", count($valores)), ...array_values($valores));
                $stmt->execute();
                $volver(true, "Registro creado", (string)($entidad["auto"] ? $conn->insert_id : $valores[$pk]));
            }

            $asignaciones = implode(", ", array_map(fn($columna) => "$columna = ?", array_keys($valores)));
            $parametros = [...array_values($valores), $id];
            $stmt = $conn->prepare("UPDATE $tabla SET $asignaciones WHERE $pk = ?");
            $stmt->bind_param(str_repeat("s", count($parametros)), ...$parametros);
            $stmt->execute();
            $volver(true, "Cambios guardados", $id);
        } catch (mysqli_sql_exception $e) {
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
	<style>
		#editor { background: #16202e; color: #dbe5f3; border: 1px solid #2c3a4f; border-radius: 10px; padding: 20px; max-width: 1100px; margin: 0 auto 24px; }
		#editor h3 { margin: 0 0 14px; font-weight: 600; }

		#editor .mensaje { margin: 0 0 14px; padding: 10px 14px; border-radius: 6px; }
		#editor .mensaje.ok { background: #1c3a2c; color: #9fe0bc; }
		#editor .mensaje.error { background: #3d2226; color: #f0a3a3; }

		.slider { display: flex; align-items: center; gap: 8px; }
		.slider-pista { display: flex; gap: 10px; overflow-x: auto; scroll-snap-type: x proximity; flex: 1; padding: 6px 2px 10px; scrollbar-width: thin; }
		.slider-flecha { flex: none; display: flex; align-items: center; justify-content: center; width: 38px; height: 38px; border-radius: 50%; border: 1px solid #2c3a4f; background: #1f2d42; color: #8BA3C5; font-size: 22px; line-height: 1; text-decoration: none; }
		a.slider-flecha:hover { background: #2a3d59; }
		.slider-flecha.inactiva { opacity: .35; }
		.slider-info { margin: 2px 0 20px; font-size: 13px; color: #8BA3C5; text-align: center; }

		.tarjeta { flex: 0 0 170px; scroll-snap-align: center; scroll-margin-inline: 40px; scroll-margin-block: 140px; display: flex; flex-direction: column; gap: 4px; padding: 12px; border: 1px solid #2c3a4f; border-radius: 8px; background: #1f2d42; color: #dbe5f3; text-decoration: none; }
		.tarjeta small { color: #8BA3C5; }
		.tarjeta:hover { background: #26374f; }
		.tarjeta.activa { border-color: #8BA3C5; background: #2a3d59; box-shadow: 0 0 0 2px #8BA3C5; }
		.slider-vacio { margin: 12px auto; color: #8BA3C5; }

		.editor-campos { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; }
		.editor-campos label { display: flex; flex-direction: column; gap: 4px; font-size: 14px; color: #8BA3C5; }
		.editor-campos input, .editor-campos select, .editor-campos textarea { padding: 8px 10px; border: 1px solid #2c3a4f; border-radius: 6px; background: #0f1824; color: #dbe5f3; font: inherit; }
		.editor-campos input[readonly] { opacity: .6; }

		.editor-acciones { display: flex; gap: 10px; margin-top: 18px; flex-wrap: wrap; }
		#editor button, #editor .btn-nuevo, #editor .btn-eliminar { padding: 9px 18px; border-radius: 6px; border: 1px solid #8BA3C5; background: transparent; color: #8BA3C5; cursor: pointer; font: inherit; text-decoration: none; }
		#editor .btn-guardar { background: #8BA3C5; color: #16202e; font-weight: 600; }
		#editor .btn-eliminar { border-color: #d96b6b; color: #d96b6b; }
		.editor-acciones .btn-eliminar { margin-left: auto; }

		.confirmar { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; padding: 14px; margin-bottom: 20px; border: 1px solid #d96b6b; border-radius: 8px; background: #2a1a1f; }
		.confirmar p { margin: 0; flex: 1 1 260px; }

		#editor a:focus-visible, #editor button:focus-visible, #editor input:focus-visible, #editor select:focus-visible, #editor textarea:focus-visible { outline: 2px solid #8BA3C5; outline-offset: 2px; }
	</style>
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
			<h3>Editor de <?= htmlspecialchars($entidad["titulo"]) ?></h3>
<?php if ($flash): ?>
			<p class="mensaje <?= $flash["ok"] ? "ok" : "error" ?>" role="status"><?= htmlspecialchars($flash["texto"]) ?></p>
<?php endif; ?>

			<div class="slider">
<?php if ($anterior !== null): ?>
				<a class="slider-flecha" href="<?= $url($anterior) ?>#sel" aria-label="Anterior">&#8249;</a>
<?php else: ?>
				<span class="slider-flecha inactiva" aria-hidden="true">&#8249;</span>
<?php endif; ?>

				<div class="slider-pista">
<?php foreach ($registros as $i => $registro): ?>
					<a class="tarjeta<?= $i === $indice ? ' activa' : '' ?>"<?= $i === $indice ? ' id="sel"' : '' ?> href="<?= $url($registro[$pk]) ?>#sel">
						<strong><?= htmlspecialchars($registro["_etiqueta"]) ?></strong>
						<small>#<?= htmlspecialchars($registro[$pk]) ?></small>
					</a>
<?php endforeach; ?>
<?php if (!$registros): ?>
					<p class="slider-vacio">No hay resultados. Probá con otra búsqueda o creá uno nuevo.</p>
<?php endif; ?>
				</div>

<?php if ($siguiente !== null): ?>
				<a class="slider-flecha" href="<?= $url($siguiente) ?>#sel" aria-label="Siguiente">&#8250;</a>
<?php else: ?>
				<span class="slider-flecha inactiva" aria-hidden="true">&#8250;</span>
<?php endif; ?>
			</div>
			<p class="slider-info"><?= count($registros) ?> resultado(s). La barra de búsqueda de abajo filtra el slider y la tabla.</p>

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
