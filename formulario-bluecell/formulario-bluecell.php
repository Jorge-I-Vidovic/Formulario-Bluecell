<?php

/**
 * Plugin Name: Formulario Bluecell
 * Plugin URI: https://github.com/Jorge-I-Vidovic/Formulario-Bluecell
 * Description: This plugin injects a form in 'single.php' after the method 'the_content()' and creates and table in administration panel.
 * Version: 1.0.0
 * Author: Jorge I. Vidovic
 * Author URI: https://github.com/Jorge-I-Vidovic
 * License: MIT
 */

// Función para agregar el formulario después del contenido en single.php
function bluecell_formulario_after_content($content)
{
    if (is_single()) {
        $formulario = '<form id="bluecell-formulario" method="post">
			<p>
				<label for="bluecell-nombre">Nombre:</label>
				<input type="text" id="bluecell-nombre" name="bluecell-nombre" required>
			</p>
			<p>
				<label for="bluecell-email">Email:</label>
				<input type="email" id="bluecell-email" name="bluecell-email" required>
			</p>
			<p>
				<label for="bluecell-telefono">Teléfono:</label>
				<input type="tel" id="bluecell-telefono" name="bluecell-telefono" required>
			</p>
			<p>
				<label for="bluecell-mensaje">Mensaje:</label>
				<textarea id="bluecell-mensaje" name="bluecell-mensaje" required></textarea>
			</p>
			<p>
				<label for="bluecell-asunto">Asunto:</label>
				<input type="text" id="bluecell-asunto" name="bluecell-asunto" required>
			</p>
			<p>
				<input type="checkbox" id="bluecell-aceptacion" name="bluecell-aceptacion" required>
				<label for="bluecell-aceptacion">Acepto las políticas de privacidad</label>
			</p>
			<input type="submit" value="Enviar">
		</form>';

        $content .= $formulario;
    }

    return $content;
}
add_filter('the_content', 'bluecell_formulario_after_content');

// Función para crear la tabla en la base de datos
function bluecell_crear_tabla()
{
    global $wpdb;
    $tabla = $wpdb->prefix . 'bluecell_datos';

    if ($wpdb->get_var("SHOW TABLES LIKE '$tabla'") != $tabla) {
        $sql = "CREATE TABLE $tabla (
			id INT NOT NULL AUTO_INCREMENT,
			nombre VARCHAR(100) NOT NULL,
			email VARCHAR(100) NOT NULL,
			telefono VARCHAR(20) NOT NULL,
			mensaje TEXT NOT NULL,
			asunto VARCHAR(100) NOT NULL,
			aceptacion TINYINT(1) NOT NULL,
			fecha DATETIME NOT NULL,
			PRIMARY KEY (id)
		) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
register_activation_hook(__FILE__, 'bluecell_crear_tabla');

// Función para validar y enviar el formulario por Ajax
function bluecell_enviar_formulario()
{
    if (isset($_POST['bluecell-nombre']) && isset($_POST['bluecell-email']) && isset($_POST['bluecell-telefono']) && isset($_POST['bluecell-mensaje']) && isset($_POST['bluecell-asunto']) && isset($_POST['bluecell-aceptacion'])) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'bluecell_datos';

        $nombre = $_POST['bluecell-nombre'];
        $email = $_POST['bluecell-email'];
        $telefono = $_POST['bluecell-telefono'];
        $mensaje = $_POST['bluecell-mensaje'];
        $asunto = $_POST['bluecell-asunto'];
        $aceptacion = $_POST['bluecell-aceptacion'];

        $wpdb->insert(
            $tabla,
            array(
                'nombre' => $nombre,
                'email' => $email,
                'telefono' => $telefono,
                'mensaje' => $mensaje,
                'asunto' => $asunto,
                'aceptacion' => $aceptacion,
                'fecha' => current_time('mysql')
            ),
            array(
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%d',
                '%s'
            )
        );

        $response = array(
            'success' => true,
            'message' => 'Formulario enviado correctamente.'
        );
    } else {
        $response = array(
            'success' => false,
            'message' => 'Faltan campos obligatorios.'
        );
    }

    wp_send_json($response);
}
add_action('wp_ajax_bluecell_enviar_formulario', 'bluecell_enviar_formulario');
add_action('wp_ajax_nopriv_bluecell_enviar_formulario', 'bluecell_enviar_formulario');

// Función para mostrar los datos enviados en el panel de administración
function bluecell_datos_enviados() {
	global $wpdb;
	$tabla = $wpdb->prefix . 'bluecell_datos';

	$datos = $wpdb->get_results( "SELECT * FROM $tabla" );

	echo '<h2>Datos Enviados</h2>';

	if ( count( $datos ) > 0 ) {
		echo '<table class="widefat">
			<thead>
				<tr>
					<th>Nombre</th>
					<th>Email</th>
					<th>Teléfono</th>
					<th>Mensaje</th>
					<th>Asunto</th>
					<th>Aceptación</th>
					<th>Fecha</th>
				</tr>
			</thead>
			<tbody>';

		foreach ( $datos as $dato ) {
			echo '<tr>
				<td>' . $dato->nombre . '</td>
				<td>' . $dato->email . '</td>
				<td>' . $dato->telefono . '</td>
				<td>' . $dato->mensaje . '</td>
				<td>' . $dato->asunto . '</td>
				<td>' . ( $dato->aceptacion == 1 ? 'Sí' : 'No' ) . '</td>
				<td>' . $dato->fecha . '</td>
			</tr>';
		}
	
		echo '</tbody>
		</table>';
	} else {
		echo '<p>No se han enviado datos todavía.</p>';
	}
}

// Función para agregar la pantalla de datos enviados en el panel de administración
function bluecell_agregar_pantalla() {
	add_menu_page('Datos Enviados', 'Datos Enviados', 'manage_options', 'bluecell-datos-enviados', 'bluecell_datos_enviados');
}
add_action('admin_menu', 'bluecell_agregar_pantalla');

// Función para eliminar la tabla al desinstalar el complemento
function bluecell_eliminar_tabla() {
	global $wpdb;
	$tabla = $wpdb->prefix . 'bluecell_datos';
	$wpdb->query("DROP TABLE IF EXISTS $tabla");
}
register_uninstall_hook(__FILE__, 'bluecell_eliminar_tabla');
?>
