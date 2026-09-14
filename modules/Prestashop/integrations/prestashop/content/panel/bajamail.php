<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ERROR);

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
require _PS_ADMIN_DIR_.'/../config/config.panel.inc.php';
include dirname(__FILE__).'/init.php';

$datos = 'SELECT
            aabm.id_log,
            ae.firstname,
            aabm.email,
            aabm.description,
            aabm.date_add,
            aabm.processed
        FROM
            '._DB_PREFIX_.'alsernet_baja_mail aabm
            LEFT JOIN '._DB_PREFIX_.'employee ae ON ae.id_employee = aabm.id_user
        ORDER BY `date_add` DESC';

// Mensaje de resultado
$mensaje = '';

// Eliminar si viene delete_id por GET
if (isset($_GET['delete_id'])) {
    $id = (int) $_GET['delete_id'];
    $sql_eliminar = 'DELETE FROM `'._DB_PREFIX_."alsernet_baja_mail` WHERE id_log = $id";
    if (Db::getInstance()->execute($sql_eliminar)) {
        $mensaje = "<div class='alert alert-warning'>Registro eliminado correctamente.</div>";
    } else {
        $mensaje = "<div class='alert alert-danger'>Error al eliminar: ".$conn->error.'</div>';
    }
}

// Insertar si hay POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id_user = (int) Context::getContext()->employee->id;
    $sql = 'INSERT INTO `'._DB_PREFIX_.'alsernet_baja_mail` (`id_user`, `email`, `description`, date_add)
                    VALUES ('.(int) $id_user.", '".pSQL($_POST['email'])."', '".pSQL($_POST['descripcion'])."', NOW())";

    if (Db::getInstance()->execute($sql)) {
        $mensaje = "<div class='alert alert-success'>Datos insertados correctamente.</div>";
    } else {
        $mensaje = "<div class='alert alert-danger'>Error al insertar: ".$conn->error.'</div>';
    }
}
$logs = Db::getInstance()->executeS($datos);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Formulario de Baja</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
    <div class="container">
        <h2>Formulario de Baja</h2>

        <?= $mensaje ?>

        <form method="POST" action="" class="mb-4">
            <div class="mb-3">
                <label for="email" class="form-label">Correo electrónico</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>

            <div class="mb-3">
                <label for="descripcion" class="form-label">Descripción</label>
                <textarea class="form-control" id="descripcion" name="descripcion" rows="3" required></textarea>
            </div>

            <button type="submit" class="btn btn-danger">Baja</button>
        </form>

        <h4>Tabla de datos cargados</h4>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID Log</th>
                    <th>ID Usuario</th>
                    <th>Email</th>
                    <th>Descripción</th>
                    <th>Fecha de Creación</th>
                    <th>Procesado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($logs as $value) {
                    ?>
                    <tr>
                        <td><?php echo $value['id_log']; ?></td>
                        <td><?php echo $value['firstname']; ?></td>
                        <td><?php echo $value['email']; ?></td>
                        <td><?php echo $value['description']; ?></td>
                        <td><?php echo $value['date_add']; ?></td>
                        <td>
                            <?php if ($value['processed'] == 1) {?>
                                <span class="label label-success">Procesado</span>
                            <?php } else { ?>
                                <span class="label label-warning">Pendiente</span>
                            <?php } ?>
                        </td>
                        <?php if ($value['processed'] == 0) { ?>
                        <td>
                        <a href="?delete_id=<?= $value['id_log'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Seguro que deseas eliminar este registro?');">
                            Eliminar
                        </a>
                        </td>
                        <?php } ?>
                    </tr>
               <?php } ?>
            </tbody>
        </table>
    </div>
</body>
</html>