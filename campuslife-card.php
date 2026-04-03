<?php

if (!defined('ABSPATH')) {
    exit;
}

function campuslife_load_textdomain() {
    load_plugin_textdomain('campus-life-id', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'campuslife_load_textdomain');

function campuslife_enqueue_assets() {
    global $post;
    if (is_page() && $post && (has_shortcode($post->post_content, 'campuslife_show_id') || has_shortcode($post->post_content, 'campuslife_edit_id') || has_shortcode($post->post_content, 'campuslife_validate_id') || has_shortcode($post->post_content, 'campuslife_create_id') || has_shortcode($post->post_content, 'campuslife_id_notice'))) {
        wp_enqueue_script('qrcodejs', 'https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js', [], '1.0.0', true);
        wp_enqueue_style('campuslife-style', plugin_dir_url(__FILE__) . 'assets/style.css', [], '2.9.3');
    }

    if (is_admin() && get_current_screen()->id === 'toplevel_page_campuslife-id') {
        wp_enqueue_script('campuslife-admin', plugin_dir_url(__FILE__) . 'assets/admin.js', ['jquery'], '2.9.3', true);
        wp_localize_script('campuslife-admin', 'campuslifeAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('campuslife_admin_action')
        ]);
    }
}
add_action('wp_enqueue_scripts', 'campuslife_enqueue_assets', 20);
add_action('admin_enqueue_scripts', 'campuslife_enqueue_assets');

function campuslife_admin_menu() {
    add_menu_page('Campus Life ID', 'Campus Life ID', 'manage_options', 'campuslife-id', 'campuslife_admin_page', 'dashicons-id');
}
add_action('admin_menu', 'campuslife_admin_menu');

function campuslife_admin_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('No tienes permiso para acceder a esta página.', 'campus-life-id'));
    }
    $users = get_users(['meta_key' => 'campuslife_serial_number', 'meta_compare' => 'EXISTS']);
    $search_name = isset($_GET['search_name']) ? sanitize_text_field($_GET['search_name']) : '';
    $search_cl_code = isset($_GET['search_cl_code']) ? sanitize_text_field($_GET['search_cl_code']) : '';

    $filtered_users = [];
    foreach ($users as $user) {
        $user_id = $user->ID;
        $full_name = get_user_meta($user_id, 'campuslife_full_name', true) ?: $user->display_name;
        $serial_number = get_user_meta($user_id, 'campuslife_serial_number', true);
        $university = get_user_meta($user_id, 'campuslife_university', true) ?: __('No especificado', 'campus-life-id');

        if (($search_name && stripos($full_name, $search_name) === false) || ($search_cl_code && stripos($serial_number, $search_cl_code) === false)) {
            continue;
        }
        $filtered_users[] = $user;
    }

    echo '<div class="wrap"><h1>' . esc_html__('Campus Life ID Management', 'campus-life-id') . '</h1>';
    echo '<div class="filter-section" style="margin-bottom: 20px;">';
    echo '<input type="text" id="search_name" name="search_name" placeholder="' . esc_attr__('Buscar por nombre...', 'campus-life-id') . '" value="' . esc_attr($search_name) . '" style="margin-right: 10px; padding: 5px;">';
    echo '<input type="text" id="search_cl_code" name="search_cl_code" placeholder="' . esc_attr__('Buscar por CL code...', 'campus-life-id') . '" value="' . esc_attr($search_cl_code) . '" style="margin-right: 10px; padding: 5px;">';
    echo '<button id="filter-button" style="padding: 5px 10px;">' . esc_html__('Filtrar', 'campus-life-id') . '</button>';
    echo '</div>';
    if ($filtered_users) {
        echo '<table class="wp-list-table widefat fixed striped" id="campuslife-id-table">';
        echo '<thead><tr><th>' . esc_html__('Nombre', 'campus-life-id') . '</th><th>' . esc_html__('Universidad', 'campus-life-id') . '</th><th>' . esc_html__('Número de serie', 'campus-life-id') . '</th><th>' . esc_html__('Acciones', 'campus-life-id') . '</th></tr></thead><tbody>';
        foreach ($filtered_users as $user) {
            $user_id = $user->ID;
            $full_name = get_user_meta($user_id, 'campuslife_full_name', true) ?: $user->display_name;
            $university = get_user_meta($user_id, 'campuslife_university', true) ?: __('No especificado', 'campus-life-id');
            $serial_number = get_user_meta($user_id, 'campuslife_serial_number', true);
            echo '<tr data-user-id="' . esc_attr($user_id) . '">';
            echo '<td>' . esc_html($full_name) . '</td>';
            echo '<td>' . esc_html($university) . '</td>';
            echo '<td>' . esc_html($serial_number) . '</td>';
            echo '<td class="actions"><button class="edit-button" data-user-id="' . esc_attr($user_id) . '">' . esc_html__('Editar', 'campus-life-id') . '</button> <button class="delete-button" data-user-id="' . esc_attr($user_id) . '">' . esc_html__('Eliminar', 'campus-life-id') . '</button></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>' . esc_html__('No hay IDs creados aún o no coinciden con los filtros.', 'campus-life-id') . '</p>';
    }
    echo '</div>';
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#filter-button').on('click', function() {
                var searchName = $('#search_name').val();
                var searchClCode = $('#search_cl_code').val();
                window.location.href = '<?php echo admin_url('admin.php?page=campuslife-id'); ?>&search_name=' + encodeURIComponent(searchName) + '&search_cl_code=' + encodeURIComponent(searchClCode);
            });
        });
    </script>
    <?php
}

function campuslife_admin_edit() {
    check_ajax_referer('campuslife_admin_action', 'nonce');
    $user_id = intval($_POST['user_id']);
    if (!current_user_can('manage_options') || !$user_id) {
        wp_send_json_error(__('No tienes permiso o el ID es inválido.', 'campus-life-id'));
        wp_die();
    }

    $full_name = get_user_meta($user_id, 'campuslife_full_name', true) ?: '';
    $university = get_user_meta($user_id, 'campuslife_university', true) ?: '';
    $field_of_study = get_user_meta($user_id, 'campuslife_field_of_study', true) ?: '';
    $birth_date = get_user_meta($user_id, 'campuslife_birth_date', true) ?: '';
    $photo_url = get_user_meta($user_id, 'campuslife_photo_url', true) ?: '';
    $serial_number = get_user_meta($user_id, 'campuslife_serial_number', true);

    ob_start();
    ?>
    <div class="edit-modal">
        <h2><?php _e('Editar ID', 'campus-life-id'); ?></h2>
        <form id="edit-form-<?php echo $user_id; ?>" enctype="multipart/form-data">
            <input type="hidden" name="user_id" value="<?php echo esc_attr($user_id); ?>">
            <div><label><?php _e('Nombre completo:', 'campus-life-id'); ?></label><input type="text" name="campuslife_full_name" value="<?php echo esc_attr($full_name); ?>" required></div>
            <div><label><?php _e('Universidad:', 'campus-life-id'); ?></label><input type="text" name="campuslife_university" value="<?php echo esc_attr($university); ?>" required></div>
            <div><label><?php _e('Programa:', 'campus-life-id'); ?></label><input type="text" name="campuslife_field_of_study" value="<?php echo esc_attr($field_of_study); ?>" required></div>
            <div><label><?php _e('Fecha de nacimiento (YYYY-MM-DD):', 'campus-life-id'); ?></label><input type="date" name="campuslife_birth_date" value="<?php echo esc_attr($birth_date); ?>" required></div>
            <div><label><?php _e('Número de serie:', 'campus-life-id'); ?></label><input type="text" name="campuslife_serial_number" value="<?php echo esc_attr($serial_number); ?>" readonly></div>
            <div><label><?php _e('Foto de perfil:', 'campus-life-id'); ?></label><img src="<?php echo esc_url($photo_url); ?>" style="width: 100px; height: 100px; object-fit: cover;"><input type="file" name="campuslife_photo" accept="image/*"></div>
            <button type="submit"><?php _e('Guardar', 'campus-life-id'); ?></button>
            <button type="button" class="close-modal"><?php _e('Cancelar', 'campus-life-id'); ?></button>
        </form>
    </div>
    <?php
    wp_send_json_success(ob_get_clean());
    wp_die();
}
add_action('wp_ajax_campuslife_admin_edit', 'campuslife_admin_edit');

function campuslife_admin_update() {
    check_ajax_referer('campuslife_admin_action', 'nonce');
    $user_id = intval($_POST['user_id']);
    if (!current_user_can('manage_options') || !$user_id) {
        wp_send_json_error(__('No tienes permiso o el ID es inválido.', 'campus-life-id'));
        wp_die();
    }

    $full_name = sanitize_text_field($_POST['campuslife_full_name']);
    $university = sanitize_text_field($_POST['campuslife_university']);
    $field_of_study = sanitize_text_field($_POST['campuslife_field_of_study']);
    $birth_date = sanitize_text_field($_POST['campuslife_birth_date']);

    update_user_meta($user_id, 'campuslife_full_name', $full_name);
    update_user_meta($user_id, 'campuslife_university', $university);
    update_user_meta($user_id, 'campuslife_field_of_study', $field_of_study);
    update_user_meta($user_id, 'campuslife_birth_date', $birth_date);

    if (!empty($_FILES['campuslife_photo']['name'])) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/bmp', 'image/webp'];
        $file_type = mime_content_type($_FILES['campuslife_photo']['tmp_name']);
        if (in_array($file_type, $allowed_types)) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            $uploaded = media_handle_upload('campuslife_photo', 0);
            if (!is_wp_error($uploaded)) {
                update_user_meta($user_id, 'campuslife_photo_url', wp_get_attachment_url($uploaded));
                wp_send_json_success(__('ID actualizado exitosamente.', 'campus-life-id'));
            } else {
                wp_send_json_error(sprintf(__('Subida fallida: %s', 'campus-life-id'), $uploaded->get_error_message()));
            }
        } else {
            wp_send_json_error(__('Tipo de imagen no soportado. Usa JPEG, PNG, GIF, BMP o WebP.', 'campus-life-id'));
        }
    }
    wp_send_json_success(__('ID actualizado exitosamente.', 'campus-life-id'));
    wp_die();
}
add_action('wp_ajax_campuslife_admin_update', 'campuslife_admin_update');

function campuslife_admin_delete() {
    check_ajax_referer('campuslife_admin_action', 'nonce');
    $user_id = intval($_POST['user_id']);
    if (!current_user_can('manage_options') || !$user_id) {
        wp_send_json_error(__('No tienes permiso o el ID es inválido.', 'campus-life-id'));
        wp_die();
    }

    delete_user_meta($user_id, 'campuslife_serial_number');
    delete_user_meta($user_id, 'campuslife_full_name');
    delete_user_meta($user_id, 'campuslife_university');
    delete_user_meta($user_id, 'campuslife_field_of_study');
    delete_user_meta($user_id, 'campuslife_birth_date');
    delete_user_meta($user_id, 'campuslife_photo_url');
    wp_send_json_success(__('ID eliminado exitosamente.', 'campus-life-id'));
    wp_die();
}
add_action('wp_ajax_campuslife_admin_delete', 'campuslife_admin_delete');

function campuslife_generate_serial_number($user_id) {
    $serial = get_user_meta($user_id, 'campuslife_serial_number', true);
    if (!$serial) {
        $serial = 'CL-' . strtoupper(wp_generate_password(6, false, false));
        update_user_meta($user_id, 'campuslife_serial_number', $serial);
    }
    return $serial;
}

function campuslife_has_id($user_id) {
    return get_user_meta($user_id, 'campuslife_serial_number', true) ? true : false;
}

function campuslife_show_id_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>' . __('Por favor, inicia sesión para ver tu carnet.', 'campus-life-id') . '</p>';
    }

    $user_id = get_current_user_id();
    if (!campuslife_has_id($user_id)) {
        return '<p>' . __('No has creado un carnet aún. Usa el formulario de creación para crearlo.', 'campus-life-id') . '</p>';
    }

    $user = wp_get_current_user();
    $full_name = get_user_meta($user_id, 'campuslife_full_name', true) ?: $user->display_name;
    $photo_url = get_user_meta($user_id, 'campuslife_photo_url', true) ?: plugin_dir_url(__FILE__) . 'assets/default-photo.jpg';
    $university = get_user_meta($user_id, 'campuslife_university', true) ?: __('No especificado', 'campus-life-id');
    $field_of_study = get_user_meta($user_id, 'campuslife_field_of_study', true) ?: __('No especificado', 'campus-life-id');
    $birth_date = get_user_meta($user_id, 'campuslife_birth_date', true) ?: __('No especificado', 'campus-life-id');
    $serial_number = campuslife_generate_serial_number($user_id);
    $validation_url = add_query_arg('serial', $serial_number, home_url('/validate-id/'));

    ob_start();
    ?>
    <div id="campuslife-id-card" class="campuslife-card">
        <div class="card-header">
            <img src="https://campuslifepa.com/wp-content/uploads/2025/07/campuslife-website-logo-horizontal.png" alt="Logo" class="card-logo">
            <h2><?php _e('Carnet Campus', 'campus-life-id'); ?></h2>
        </div>
        <div class="card-body">
            <?php if ($photo_url && strpos($photo_url, 'default-photo.jpg') === false): ?>
                <img src="<?php echo esc_url($photo_url); ?>" alt="<?php _e('Foto de perfil', 'campus-life-id'); ?>" class="photo">
            <?php else: ?>
                <p><?php _e('No photo uploaded. Please edit your ID to add a photo.', 'campus-life-id'); ?></p>
            <?php endif; ?>
            <p><strong><?php _e('Nombre completo:', 'campus-life-id'); ?></strong> <span><?php echo esc_html($full_name); ?></span></p>
            <p><strong><?php _e('Universidad:', 'campus-life-id'); ?></strong> <span><?php echo esc_html($university); ?></span></p>
            <p><strong><?php _e('Programa:', 'campus-life-id'); ?></strong> <span><?php echo esc_html($field_of_study); ?></span></p>
            <p><strong><?php _e('Fecha de nacimiento:', 'campus-life-id'); ?></strong> <span><?php echo esc_html($birth_date); ?></span></p>
            <p><strong><?php _e('Número de serie:', 'campus-life-id'); ?></strong> <span class="serial-number"><?php echo esc_html($serial_number); ?></span></p>
            <div id="qrcode-<?php echo $user_id; ?>" class="qrcode"></div>
            <div class="watermark"></div>
        </div>
    </div>
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById("qrcode-<?php echo $user_id; ?>")) {
                new QRCode(document.getElementById("qrcode-<?php echo $user_id; ?>"), {
                    text: "<?php echo esc_url($validation_url); ?>",
                    width: 100,
                    height: 100
                });
            }
        });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('campuslife_show_id', 'campuslife_show_id_shortcode');

function campuslife_create_id_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>' . __('Por favor, inicia sesión para crear tu ID universitario.', 'campus-life-id') . '</p>';
    }

    $user_id = get_current_user_id();
    if (campuslife_has_id($user_id)) {
        return ''; // Hide form if ID exists
    }

    $full_name = '';
    $university = '';
    $field_of_study = '';
    $birth_date = '';
    $photo_url = '';

    if (isset($_POST['campuslife_create_submit'])) {
        $full_name = sanitize_text_field($_POST['campuslife_full_name']);
        $university = sanitize_text_field($_POST['campuslife_university']);
        $field_of_study = sanitize_text_field($_POST['campuslife_field_of_study']);
        $birth_date = sanitize_text_field($_POST['campuslife_birth_date']);

        update_user_meta($user_id, 'campuslife_full_name', $full_name);
        update_user_meta($user_id, 'campuslife_university', $university);
        update_user_meta($user_id, 'campuslife_field_of_study', $field_of_study);
        update_user_meta($user_id, 'campuslife_birth_date', $birth_date);

        if (!empty($_FILES['campuslife_photo']['name'])) {
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/bmp', 'image/webp'];
            $file_type = mime_content_type($_FILES['campuslife_photo']['tmp_name']);
            if (in_array($file_type, $allowed_types)) {
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');
                $uploaded = media_handle_upload('campuslife_photo', 0);
                if (!is_wp_error($uploaded)) {
                    $photo_url = wp_get_attachment_url($uploaded);
                    update_user_meta($user_id, 'campuslife_photo_url', $photo_url);
                } else {
                    echo '<p style="color: red;">' . sprintf(__('Upload failed: %s', 'campus-life-id'), $uploaded->get_error_message()) . '</p>';
                }
            } else {
                echo '<p style="color: red;">' . __('Unsupported image type. Please use JPEG, PNG, GIF, BMP, or WebP.', 'campus-life-id') . '</p>';
            }
        }
        if (empty($photo_url)) {
            update_user_meta($user_id, 'campuslife_photo_url', plugin_dir_url(__FILE__) . 'assets/default-photo.jpg');
        }
        campuslife_generate_serial_number($user_id); 
        echo '<p style="color: green;">' . __('ID creado exitosamente.', 'campus-life-id') . '</p>';
    }

    ob_start();
    ?>
    <div class="campuslife-create-form">
        <h2><?php _e('Crear tu ID Universitario', 'campus-life-id'); ?></h2>
        <form method="post" enctype="multipart/form-data">
            <div>
                <label for="campuslife_full_name"><?php _e('Nombre completo:', 'campus-life-id'); ?></label>
                <input type="text" name="campuslife_full_name" value="<?php echo esc_attr($full_name); ?>" required>
            </div>
            <div>
                <label for="campuslife_university"><?php _e('Universidad:', 'campus-life-id'); ?></label>
                <input type="text" name="campuslife_university" value="<?php echo esc_attr($university); ?>" required>
            </div>
            <div>
                <label for="campuslife_field_of_study"><?php _e('Programa:', 'campus-life-id'); ?></label>
                <input type="text" name="campuslife_field_of_study" value="<?php echo esc_attr($field_of_study); ?>" required>
            </div>
            <div>
                <label for="campuslife_birth_date"><?php _e('Fecha de nacimiento (YYYY-MM-DD):', 'campus-life-id'); ?></label>
                <input type="date" name="campuslife_birth_date" value="<?php echo esc_attr($birth_date); ?>" required>
            </div>
            <div>
                <label for="campuslife_photo"><?php _e('Foto de perfil:', 'campus-life-id'); ?></label>
                <input type="file" name="campuslife_photo" accept="image/*">
            </div>
            <input type="submit" name="campuslife_create_submit" value="<?php _e('Crear ID', 'campus-life-id'); ?>">
        </form>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('campuslife_create_id', 'campuslife_create_id_shortcode');

function campuslife_edit_id_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>' . __('Por favor, inicia sesión para editar tu carnet.', 'campus-life-id') . '</p>';
    }

    $user_id = get_current_user_id();
    if (!campuslife_has_id($user_id)) {
        return ''; 
    }

    $full_name = get_user_meta($user_id, 'campuslife_full_name', true);
    $university = get_user_meta($user_id, 'campuslife_university', true);
    $field_of_study = get_user_meta($user_id, 'campuslife_field_of_study', true);
    $birth_date = get_user_meta($user_id, 'campuslife_birth_date', true);
    $photo_url = get_user_meta($user_id, 'campuslife_photo_url', true) ?: '';

    if (isset($_POST['campuslife_edit_submit'])) {
        $full_name = sanitize_text_field($_POST['campuslife_full_name']);
        $university = sanitize_text_field($_POST['campuslife_university']);
        $field_of_study = sanitize_text_field($_POST['campuslife_field_of_study']);
        $birth_date = sanitize_text_field($_POST['campuslife_birth_date']);

        update_user_meta($user_id, 'campuslife_full_name', $full_name);
        update_user_meta($user_id, 'campuslife_university', $university);
        update_user_meta($user_id, 'campuslife_field_of_study', $field_of_study);
        update_user_meta($user_id, 'campuslife_birth_date', $birth_date);

        if (!empty($_FILES['campuslife_photo']['name'])) {
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/bmp', 'image/webp'];
            $file_type = mime_content_type($_FILES['campuslife_photo']['tmp_name']);
            if (in_array($file_type, $allowed_types)) {
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');
                $uploaded = media_handle_upload('campuslife_photo', 0);
                if (!is_wp_error($uploaded)) {
                    $photo_url = wp_get_attachment_url($uploaded);
                    update_user_meta($user_id, 'campuslife_photo_url', $photo_url);
                } else {
                    echo '<p style="color: red;">' . sprintf(__('Upload failed: %s', 'campus-life-id'), $uploaded->get_error_message()) . '</p>';
                }
            } else {
                echo '<p style="color: red;">' . __('Unsupported image type. Please use JPEG, PNG, GIF, BMP, or WebP.', 'campus-life-id') . '</p>';
            }
        }
        echo '<p style="color: green;">' . __('ID actualizado exitosamente.', 'campus-life-id') . '</p>';
    }

    ob_start();
    ?>
    <div class="campuslife-edit-form">
        <h2><?php _e('Editar tu ID Universitario', 'campus-life-id'); ?></h2>
        <form method="post" enctype="multipart/form-data">
            <div>
                <label for="campuslife_full_name"><?php _e('Nombre completo:', 'campus-life-id'); ?></label>
                <input type="text" name="campuslife_full_name" value="<?php echo esc_attr($full_name); ?>" required>
            </div>
            <div>
                <label for="campuslife_university"><?php _e('Universidad:', 'campus-life-id'); ?></label>
                <input type="text" name="campuslife_university" value="<?php echo esc_attr($university); ?>" required>
            </div>
            <div>
                <label for="campuslife_field_of_study"><?php _e('Programa:', 'campus-life-id'); ?></label>
                <input type="text" name="campuslife_field_of_study" value="<?php echo esc_attr($field_of_study); ?>" required>
            </div>
            <div>
                <label for="campuslife_birth_date"><?php _e('Fecha de nacimiento (YYYY-MM-DD):', 'campus-life-id'); ?></label>
                <input type="date" name="campuslife_birth_date" value="<?php echo esc_attr($birth_date); ?>" required>
            </div>
            <div>
                <label for="campuslife_photo"><?php _e('Foto de perfil:', 'campus-life-id'); ?></label>
                <img id="photo-preview" src="<?php echo esc_url($photo_url); ?>" alt="<?php _e('Vista previa de foto', 'campus-life-id'); ?>">
                <input type="file" name="campuslife_photo" accept="image/*">
            </div>
            <input type="submit" name="campuslife_edit_submit" value="<?php _e('Actualizar ID', 'campus-life-id'); ?>">
        </form>
    </div>
    <script>
        document.getElementById('campuslife_photo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('photo-preview').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('campuslife_edit_id', 'campuslife_edit_id_shortcode');

function campuslife_validate_id_shortcode() {
    $result = '';
    if (isset($_POST['campuslife_validate']) || isset($_GET['serial'])) {
        $serial_number = isset($_POST['campuslife_serial']) ? sanitize_text_field($_POST['campuslife_serial']) : (isset($_GET['serial']) ? sanitize_text_field($_GET['serial']) : '');
        $user = get_users([
            'meta_key' => 'campuslife_serial_number',
            'meta_value' => $serial_number,
            'number' => 1
        ]);

        if ($user) {
            $user_id = $user[0]->ID;
            $full_name = get_user_meta($user_id, 'campuslife_full_name', true) ?: $user[0]->display_name;
            $university = get_user_meta($user_id, 'campuslife_university', true) ?: __('No especificado', 'campus-life-id');
            $field_of_study = get_user_meta($user_id, 'campuslife_field_of_study', true) ?: __('No especificado', 'campus-life-id');
            $birth_date = get_user_meta($user_id, 'campuslife_birth_date', true) ?: __('No especificado', 'campus-life-id');
            $result = '<div class="validation-result valid"><span>✓</span> <strong style="font-size: 1.5em; color: #28a745;">' . __('CARNET VALIDO', 'campus-life-id') . '</strong></div>';
            $result .= '<p><strong>' . __('Nombre completo:', 'campus-life-id') . '</strong> ' . esc_html($full_name) . '</p>';
            $result .= '<p><strong>' . __('Universidad:', 'campus-life-id') . '</strong> ' . esc_html($university) . '</p>';
            $result .= '<p><strong>' . __('Programa:', 'campus-life-id') . '</strong> ' . esc_html($field_of_study) . '</p>';
            $result .= '<p><strong>' . __('Fecha de nacimiento:', 'campus-life-id') . '</strong> ' . esc_html($birth_date) . '</p>';
        } else {
            $result = '<div class="validation-result invalid"><span>✗</span> <strong style="font-size: 1.5em; color: #dc3545;">' . __('CARNET INVÁLIDO', 'campus-life-id') . '</strong></div>';
            $result .= '<p><strong>' . __('Nombre completo:', 'campus-life-id') . '</strong> ' . __('No disponible', 'campus-life-id') . '</p>';
            $result .= '<p><strong>' . __('Universidad:', 'campus-life-id') . '</strong> ' . __('No disponible', 'campus-life-id') . '</p>';
            $result .= '<p><strong>' . __('Programa:', 'campus-life-id') . '</strong> ' . __('No disponible', 'campus-life-id') . '</p>';
            $result .= '<p><strong>' . __('Fecha de nacimiento:', 'campus-life-id') . '</strong> ' . __('No disponible', 'campus-life-id') . '</p>';
        }
    }

    ob_start();
    ?>
    <form method="post" class="campuslife-validate-form">
        <label for="campuslife_serial"><?php _e('Número de serie:', 'campus-life-id'); ?></label>
        <input type="text" name="campuslife_serial" required>
        <input type="submit" name="campuslife_validate" value="<?php _e('Validar carnet', 'campus-life-id'); ?>">
    </form>
    <div class="validation-container">
        <?php echo $result; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('campuslife_validate_id', 'campuslife_validate_id_shortcode');

function campuslife_id_notice_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>' . __('Por favor, inicia sesión para ver el aviso.', 'campus-life-id') . '</p>';
    }

    $user_id = get_current_user_id();
    if (campuslife_has_id($user_id)) {
        return '';
    }

    ob_start();
    ?>
    <p>
        <?php _e('No tienes un ID universitario. Haz clic ', 'campus-life-id'); ?>
        <a href="https://www.campuslifepa.com/id" target="_blank"><?php _e('AQUI', 'campus-life-id'); ?></a>
        <?php _e(' para enviar una solicitud.', 'campus-life-id'); ?>
    </p>
    <?php
    return ob_get_clean();
}
add_shortcode('campuslife_id_notice', 'campuslife_id_notice_shortcode');
?>