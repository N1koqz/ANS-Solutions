<?php require_once "baseDeDatos.php"; requerirLogin(true); ?>
<html>
<head>
	<meta charset="UTF-8">
	<title> MG Fut </title>
	<link href="CSSmenuInicio.css" type="text/css" rel="stylesheet">
</head>

<body>
<?php menu(); ?>

	<main>
		<div>
			<table>
				<thead>
					<tr>
						<th></th>
						<th></th>
						<th></th>
						<th></th>
						<th></th>
						<th></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
<?php for ($i = 0; $i < 3; $i++): ?>
					<tr>
						<td>X</td>
						<td>X</td>
						<td>x</td>
						<td>X</td>
						<td>X</td>
						<td>X</td>
						<td>X</td>
					</tr>
<?php endfor; ?>
				</tbody>
			</table>
		</div>		

	</main>

<?php pie(); ?>

</body>

</html>
