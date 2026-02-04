<?php
/**
 * Plugin Name: ProjektO
 * Description: Projekte inkl. Status & Zuständigkeiten verwalten und per Gutenberg-Block als Badges/Accordion anzeigen.
 * Version: 1.0.4
 * Author: hubertoink
 * Text Domain: projekto
 */

if (!defined('ABSPATH')) {
	exit;
}

/* ========================================================================
   THEME-KOMPATIBILITÄT (Dark Mode)
   ======================================================================== */

add_action('admin_menu', 'projekto_register_settings_page');
add_action('admin_init', 'projekto_register_settings');
add_action('wp_enqueue_scripts', 'projekto_enqueue_theme_compat_styles', 20);
add_action('enqueue_block_editor_assets', 'projekto_enqueue_theme_compat_styles', 20);

function projekto_register_settings_page(): void {
	add_options_page(
		'ProjektO',
		'ProjektO',
		'manage_options',
		'projekto-settings',
		'projekto_render_settings_page'
	);
}

function projekto_sanitize_mode(string $mode): string {
	$mode = strtolower(trim($mode));
	return in_array($mode, ['auto', 'light', 'dark'], true) ? $mode : 'auto';
}

function projekto_sanitize_selector(string $selector): string {
	$selector = trim((string) sanitize_text_field($selector));
	$selector = str_replace(['{', '}', '<', '>'], '', $selector);
	return $selector;
}

function projekto_register_settings(): void {
	register_setting('projekto_settings', 'projekto_dark_mode', [
		'type'              => 'string',
		'sanitize_callback' => 'projekto_sanitize_mode',
		'default'           => 'auto',
	]);
	register_setting('projekto_settings', 'projekto_dark_selector', [
		'type'              => 'string',
		'sanitize_callback' => 'projekto_sanitize_selector',
		'default'           => 'html[data-neve-theme="dark"]',
	]);
	register_setting('projekto_settings', 'projekto_light_selector', [
		'type'              => 'string',
		'sanitize_callback' => 'projekto_sanitize_selector',
		'default'           => 'html[data-neve-theme="light"]',
	]);
}

function projekto_render_settings_page(): void {
	if (!current_user_can('manage_options')) return;
	$mode = get_option('projekto_dark_mode', 'auto');
	$dark_sel = get_option('projekto_dark_selector', 'html[data-neve-theme="dark"]');
	$light_sel = get_option('projekto_light_selector', 'html[data-neve-theme="light"]');
	?>
	<div class="wrap">
		<h1>ProjektO – Theme-Kompatibilität</h1>
		<form method="post" action="options.php">
			<?php settings_fields('projekto_settings'); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="projekto_dark_mode">Dark Mode</label></th>
					<td>
						<select name="projekto_dark_mode" id="projekto_dark_mode">
							<option value="auto" <?php selected($mode, 'auto'); ?>>Automatisch (Theme-Selektor)</option>
							<option value="light" <?php selected($mode, 'light'); ?>>Hell (immer)</option>
							<option value="dark" <?php selected($mode, 'dark'); ?>>Dunkel (immer)</option>
						</select>
						<p class="description">Standardmäßig auf Neve ausgelegt. Bei <em>Automatisch</em> wird die dunkle Variante nur unter dem Dark-Selektor angewendet.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="projekto_dark_selector">Dark Mode Selektor</label></th>
					<td>
						<input type="text" class="regular-text" name="projekto_dark_selector" id="projekto_dark_selector" value="<?php echo esc_attr($dark_sel); ?>" />
						<p class="description">Neve Default: <code>html[data-neve-theme="dark"]</code></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="projekto_light_selector">Light Mode Selektor</label></th>
					<td>
						<input type="text" class="regular-text" name="projekto_light_selector" id="projekto_light_selector" value="<?php echo esc_attr($light_sel); ?>" />
						<p class="description">Neve Default: <code>html[data-neve-theme="light"]</code></p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

function projekto_get_theme_compat_css(): string {
	$mode = get_option('projekto_dark_mode', 'auto');
	$mode = projekto_sanitize_mode((string) $mode);
	if ($mode === 'light') return '';

	$dark_selector = get_option('projekto_dark_selector', 'html[data-neve-theme="dark"]');
	$dark_selector = projekto_sanitize_selector((string) $dark_selector);
	if ($mode === 'auto' && $dark_selector === '') return '';

	$scope = ($mode === 'dark') ? '' : ($dark_selector . ' ');

	// Only scope our overrides to ProjektO blocks, never to the whole theme.
	$base = $scope . '.wp-block-projekto-projects';
	$modal = $scope; // modal is appended to body (outside the block), so don't depend on block ancestry

	return "\n" . $base . "{\n" .
		"  --projekto-border:#253046;\n" .
		"  --projekto-bg:#0b1220;\n" .
		"  --projekto-chip-bg:#111827;\n" .
		"  --projekto-text:#f9fafb;\n" .
		"  --projekto-muted:#94a3b8;\n" .
	"}\n" .
	$base . " .projekto-filter:hover{background:#1f2937}\n" .
	$base . " button.projekto-badge," . $base . " a.projekto-badge{box-shadow:0 2px 10px rgba(0,0,0,.35)}\n" .
	$modal . ".projekto-modal-inner{background:#111827;color:#f9fafb}\n" .
	$modal . ".projekto-modal-body{background:#111827;color:#f9fafb}\n" .
	$modal . ".projekto-modal-body{scrollbar-color:#475569 #0b1220;scrollbar-width:thin}\n" .
	$modal . ".projekto-modal-body::-webkit-scrollbar{width:10px}\n" .
	$modal . ".projekto-modal-body::-webkit-scrollbar-track{background:#0b1220}\n" .
	$modal . ".projekto-modal-body::-webkit-scrollbar-thumb{background:#475569;border-radius:999px;border:2px solid #0b1220}\n" .
	$modal . ".projekto-modal-body::-webkit-scrollbar-thumb:hover{background:#64748b}\n" .
	$modal . ".projekto-content--modal{color:#f9fafb}\n" .
	$modal . ".projekto-more{color:#f9fafb}\n" .
	$modal . ".projekto-modal-close{background:rgba(255,255,255,.12)}\n";
}

function projekto_enqueue_theme_compat_styles(): void {
	$css = projekto_get_theme_compat_css();
	if (!$css) return;
	if (!wp_style_is('projekto-theme-compat', 'registered')) {
		wp_register_style('projekto-theme-compat', false, [], '1.0.0');
	}
	wp_enqueue_style('projekto-theme-compat');
	wp_add_inline_style('projekto-theme-compat', $css);
}

/* ========================================================================
   CUSTOM POST TYPE + TAXONOMIEN
   ======================================================================== */

add_action('init', 'projekto_register_content');

function projekto_register_content(): void {
	// CPT: Projekt
	register_post_type('projekto_projekt', [
		'labels' => [
			'name'               => 'Projekte',
			'singular_name'      => 'Projekt',
			'add_new'            => 'Neu hinzufügen',
			'add_new_item'       => 'Neues Projekt hinzufügen',
			'edit_item'          => 'Projekt bearbeiten',
			'new_item'           => 'Neues Projekt',
			'view_item'          => 'Projekt ansehen',
			'search_items'       => 'Projekte suchen',
			'not_found'          => 'Keine Projekte gefunden',
			'not_found_in_trash' => 'Keine Projekte im Papierkorb',
			'all_items'          => 'Alle Projekte',
			'menu_name'          => 'Projekte',
		],
		'public'       => true,
		'show_in_rest' => true,
		'menu_icon'    => 'dashicons-clipboard',
		'supports'     => ['title', 'editor', 'excerpt', 'thumbnail', 'revisions'],
		'has_archive'  => true,
		'rewrite'      => ['slug' => 'projekte'],
	]);

	// Taxonomie: Status
	register_taxonomy('projekto_status', ['projekto_projekt'], [
		'labels' => [
			'name'          => 'Projektstatus',
			'singular_name' => 'Status',
			'add_new_item'  => 'Neuen Status hinzufügen',
			'edit_item'     => 'Status bearbeiten',
			'all_items'     => 'Alle Status',
		],
		'public'            => true,
		'show_in_rest'      => true,
		'hierarchical'      => true, // Checkbox-Auswahl statt Textfeld
		'show_admin_column' => true,
		'rewrite'           => ['slug' => 'projekt-status'],
	]);

	// Taxonomie: Zuständigkeit
	register_taxonomy('projekto_zustaendigkeit', ['projekto_projekt'], [
		'labels' => [
			'name'          => 'Zuständigkeiten',
			'singular_name' => 'Zuständigkeit',
			'add_new_item'  => 'Neue Zuständigkeit hinzufügen',
			'edit_item'     => 'Zuständigkeit bearbeiten',
			'all_items'     => 'Alle Zuständigkeiten',
		],
		'public'            => true,
		'show_in_rest'      => true,
		'hierarchical'      => true, // Checkbox-Auswahl statt Textfeld
		'show_admin_column' => true,
		'rewrite'           => ['slug' => 'projekt-zustaendigkeit'],
	]);

	// Taxonomie: Arbeitsbereich (optional)
	register_taxonomy('projekto_arbeitsbereich', ['projekto_projekt'], [
		'labels' => [
			'name'          => 'Arbeitsbereiche',
			'singular_name' => 'Arbeitsbereich',
			'add_new_item'  => 'Neuen Arbeitsbereich hinzufügen',
			'edit_item'     => 'Arbeitsbereich bearbeiten',
			'all_items'     => 'Alle Arbeitsbereiche',
		],
		'public'            => true,
		'show_in_rest'      => true,
		'hierarchical'      => true, // Checkbox-Auswahl statt Textfeld
		'show_admin_column' => true,
		'rewrite'           => ['slug' => 'projekt-arbeitsbereich'],
	]);

	// Term-Meta für Status-Farbe
	register_term_meta('projekto_status', 'projekto_status_color', [
		'type'              => 'string',
		'single'            => true,
		'sanitize_callback' => 'sanitize_hex_color',
		'show_in_rest'      => true,
	]);
}

/* ========================================================================
   STATUS-FARBE (Term-Meta UI)
   ======================================================================== */

add_action('projekto_status_add_form_fields', 'projekto_status_color_add_field');
add_action('projekto_status_edit_form_fields', 'projekto_status_color_edit_field');
add_action('created_projekto_status', 'projekto_save_status_color');
add_action('edited_projekto_status', 'projekto_save_status_color');

function projekto_status_color_add_field(): void {
	wp_nonce_field('projekto_status_color', 'projekto_status_color_nonce');
	?>
	<div class="form-field">
		<label for="projekto_status_color">Farbe</label>
		<input type="text" name="projekto_status_color" id="projekto_status_color" value="" placeholder="#2ecc71" />
		<p>Optional: HEX-Farbe für Badge (z.B. #2ecc71 für Grün, #e74c3c für Rot).</p>
	</div>
	<?php
}

function projekto_status_color_edit_field($term): void {
	$color = get_term_meta($term->term_id, 'projekto_status_color', true);
	wp_nonce_field('projekto_status_color', 'projekto_status_color_nonce');
	?>
	<tr class="form-field">
		<th><label for="projekto_status_color">Farbe</label></th>
		<td>
			<input type="text" name="projekto_status_color" id="projekto_status_color" value="<?php echo esc_attr($color); ?>" placeholder="#2ecc71" />
			<p class="description">Optional: HEX-Farbe für Badge (z.B. #2ecc71 für Grün, #e74c3c für Rot).</p>
		</td>
	</tr>
	<?php
}

function projekto_save_status_color(int $term_id): void {
	if (!isset($_POST['projekto_status_color_nonce']) || !wp_verify_nonce($_POST['projekto_status_color_nonce'], 'projekto_status_color')) {
		return;
	}
	$color = isset($_POST['projekto_status_color']) ? sanitize_hex_color($_POST['projekto_status_color']) : '';
	if ($color) {
		update_term_meta($term_id, 'projekto_status_color', $color);
	} else {
		delete_term_meta($term_id, 'projekto_status_color');
	}
}

function projekto_get_status_color(?int $term_id): string {
	if (!$term_id) return '';
	return (string) get_term_meta($term_id, 'projekto_status_color', true);
}

/* ========================================================================
   ZUSTÄNDIGKEIT – BILD (Term-Meta UI)
   ======================================================================== */

add_action('init', function() {
	register_term_meta('projekto_zustaendigkeit', 'projekto_zustaendigkeit_image', [
		'type'              => 'integer',
		'single'            => true,
		'sanitize_callback' => 'absint',
		'show_in_rest'      => true,
	]);
	register_term_meta('projekto_zustaendigkeit', 'projekto_zustaendigkeit_color', [
		'type'              => 'string',
		'single'            => true,
		'sanitize_callback' => 'sanitize_hex_color',
		'show_in_rest'      => true,
	]);
});

add_action('projekto_zustaendigkeit_add_form_fields', 'projekto_zustaendigkeit_image_add_field');
add_action('projekto_zustaendigkeit_edit_form_fields', 'projekto_zustaendigkeit_image_edit_field');
add_action('created_projekto_zustaendigkeit', 'projekto_save_zustaendigkeit_image');
add_action('edited_projekto_zustaendigkeit', 'projekto_save_zustaendigkeit_image');

function projekto_zustaendigkeit_image_add_field(): void {
	wp_nonce_field('projekto_zustaendigkeit_image', 'projekto_zustaendigkeit_image_nonce');
	?>
	<div class="form-field">
		<label for="projekto_zustaendigkeit_color">Farbe</label>
		<input type="text" name="projekto_zustaendigkeit_color" id="projekto_zustaendigkeit_color" value="" placeholder="#9333ea" class="projekto-color-picker" />
		<p>Optional: Badge-Farbe für diese Zuständigkeit.</p>
	</div>
	<div class="form-field">
		<label for="projekto_zustaendigkeit_image">Bild</label>
		<input type="hidden" name="projekto_zustaendigkeit_image" id="projekto_zustaendigkeit_image" value="" />
		<div id="projekto-image-preview" style="margin-bottom:8px;"></div>
		<button type="button" class="button projekto-upload-image">Bild auswählen</button>
		<button type="button" class="button projekto-remove-image" style="display:none;">Entfernen</button>
		<p>Optional: Profilbild für diese Zuständigkeit (z.B. Foto der Person).</p>
	</div>
	<?php
}

function projekto_zustaendigkeit_image_edit_field($term): void {
	$image_id = (int) get_term_meta($term->term_id, 'projekto_zustaendigkeit_image', true);
	$image_url = $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : '';
	$zust_color = get_term_meta($term->term_id, 'projekto_zustaendigkeit_color', true);
	wp_nonce_field('projekto_zustaendigkeit_image', 'projekto_zustaendigkeit_image_nonce');
	?>
	<tr class="form-field">
		<th><label for="projekto_zustaendigkeit_color">Farbe</label></th>
		<td>
			<input type="text" name="projekto_zustaendigkeit_color" id="projekto_zustaendigkeit_color" value="<?php echo esc_attr($zust_color); ?>" placeholder="#9333ea" class="projekto-color-picker" />
			<p class="description">Optional: Badge-Farbe für diese Zuständigkeit.</p>
		</td>
	</tr>
	<tr class="form-field">
		<th><label for="projekto_zustaendigkeit_image">Bild</label></th>
		<td>
			<input type="hidden" name="projekto_zustaendigkeit_image" id="projekto_zustaendigkeit_image" value="<?php echo esc_attr($image_id); ?>" />
			<div id="projekto-image-preview" style="margin-bottom:8px;">
				<?php if ($image_url): ?>
					<img src="<?php echo esc_url($image_url); ?>" style="max-width:100px;max-height:100px;border-radius:50%;" />
				<?php endif; ?>
			</div>
			<button type="button" class="button projekto-upload-image">Bild auswählen</button>
			<button type="button" class="button projekto-remove-image" <?php echo $image_id ? '' : 'style="display:none;"'; ?>>Entfernen</button>
			<p class="description">Optional: Profilbild für diese Zuständigkeit (z.B. Foto der Person).</p>
		</td>
	</tr>
	<?php
}

function projekto_save_zustaendigkeit_image(int $term_id): void {
	if (!isset($_POST['projekto_zustaendigkeit_image_nonce']) || !wp_verify_nonce($_POST['projekto_zustaendigkeit_image_nonce'], 'projekto_zustaendigkeit_image')) {
		return;
	}
	$image_id = isset($_POST['projekto_zustaendigkeit_image']) ? absint($_POST['projekto_zustaendigkeit_image']) : 0;
	if ($image_id) {
		update_term_meta($term_id, 'projekto_zustaendigkeit_image', $image_id);
	} else {
		delete_term_meta($term_id, 'projekto_zustaendigkeit_image');
	}
	$zust_color = isset($_POST['projekto_zustaendigkeit_color']) ? sanitize_hex_color($_POST['projekto_zustaendigkeit_color']) : '';
	if ($zust_color) {
		update_term_meta($term_id, 'projekto_zustaendigkeit_color', $zust_color);
	} else {
		delete_term_meta($term_id, 'projekto_zustaendigkeit_color');
	}
}

function projekto_get_zustaendigkeit_image(?int $term_id, string $size = 'thumbnail'): string {
	if (!$term_id) return '';
	$image_id = (int) get_term_meta($term_id, 'projekto_zustaendigkeit_image', true);
	return $image_id ? (string) wp_get_attachment_image_url($image_id, $size) : '';
}

function projekto_get_zustaendigkeit_color(?int $term_id): string {
	if (!$term_id) return '';
	return (string) get_term_meta($term_id, 'projekto_zustaendigkeit_color', true);
}

function projekto_contrast_text_color(string $hex_color, string $light = '#ffffff', string $dark = '#111827'): string {
	$hex = ltrim(trim($hex_color), '#');
	if (strlen($hex) === 3) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	if (strlen($hex) !== 6) return $dark;

	$r = hexdec(substr($hex, 0, 2)) / 255;
	$g = hexdec(substr($hex, 2, 2)) / 255;
	$b = hexdec(substr($hex, 4, 2)) / 255;

	// Relative luminance (sRGB)
	$to_linear = static function (float $c): float {
		return ($c <= 0.04045) ? ($c / 12.92) : pow(($c + 0.055) / 1.055, 2.4);
	};
	$rl = $to_linear($r);
	$gl = $to_linear($g);
	$bl = $to_linear($b);
	$luminance = 0.2126 * $rl + 0.7152 * $gl + 0.0722 * $bl;

	return ($luminance < 0.5) ? $light : $dark;
}

/* ========================================================================
   PROJEKT – HINTERGRUNDFARBE (Post-Meta)
   ======================================================================== */

add_action('init', function() {
	register_post_meta('projekto_projekt', 'projekto_projekt_color', [
		'type'              => 'string',
		'single'            => true,
		'sanitize_callback' => 'sanitize_hex_color',
		'show_in_rest'      => true,
	]);

	register_post_meta('projekto_projekt', 'projekto_projekt_gradient_from', [
		'type'              => 'string',
		'single'            => true,
		'sanitize_callback' => 'sanitize_hex_color',
		'show_in_rest'      => true,
	]);
	register_post_meta('projekto_projekt', 'projekto_projekt_gradient_to', [
		'type'              => 'string',
		'single'            => true,
		'sanitize_callback' => 'sanitize_hex_color',
		'show_in_rest'      => true,
	]);
	
	register_post_meta('projekto_projekt', 'projekto_projekt_progress', [
		'type'              => 'integer',
		'single'            => true,
		'sanitize_callback' => function($val) {
			$val = absint($val);
			return min(100, max(0, $val));
		},
		'show_in_rest'      => true,
		'default'           => 0,
	]);
});

add_action('add_meta_boxes', function() {
	add_meta_box(
		'projekto_projekt_color_box',
		'Projekt-Farbe',
		'projekto_projekt_color_meta_box',
		'projekto_projekt',
		'side',
		'default'
	);
	
	add_meta_box(
		'projekto_projekt_progress_box',
		'Projektfortschritt',
		'projekto_projekt_progress_meta_box',
		'projekto_projekt',
		'side',
		'default'
	);
});

function projekto_projekt_color_meta_box($post): void {
	$color = get_post_meta($post->ID, 'projekto_projekt_color', true);
	$grad_from = get_post_meta($post->ID, 'projekto_projekt_gradient_from', true);
	$grad_to = get_post_meta($post->ID, 'projekto_projekt_gradient_to', true);
	wp_nonce_field('projekto_projekt_color', 'projekto_projekt_color_nonce');
	?>
	<p>
		<label for="projekto_projekt_color">Hintergrundfarbe:</label><br>
		<input type="text" name="projekto_projekt_color" id="projekto_projekt_color" value="<?php echo esc_attr($color); ?>" class="projekto-color-picker" data-default-color="" />
	</p>
	<p style="margin-top:12px">
		<strong>oder Verlauf (Gradient):</strong>
	</p>
	<p style="display:flex;gap:12px;align-items:center">
		<span>
			<label for="projekto_projekt_gradient_from" style="display:block">Von</label>
			<input type="text" name="projekto_projekt_gradient_from" id="projekto_projekt_gradient_from" value="<?php echo esc_attr($grad_from); ?>" class="projekto-color-picker" data-default-color="" />
		</span>
		<span>
			<label for="projekto_projekt_gradient_to" style="display:block">Bis</label>
			<input type="text" name="projekto_projekt_gradient_to" id="projekto_projekt_gradient_to" value="<?php echo esc_attr($grad_to); ?>" class="projekto-color-picker" data-default-color="" />
		</span>
	</p>
	<p class="description">Optional: Hintergrundfarbe für dieses Projekt im Backend.</p>
	<p class="description">Wenn beide Gradient-Farben gesetzt sind, wird ein Verlauf statt der Einzelfarbe verwendet.</p>
	<?php
}

function projekto_projekt_progress_meta_box($post): void {
	$progress = (int) get_post_meta($post->ID, 'projekto_projekt_progress', true);
	wp_nonce_field('projekto_projekt_progress', 'projekto_projekt_progress_nonce');
	?>
	<div style="display:flex;align-items:center;gap:12px;margin-bottom:8px">
		<input type="range" name="projekto_projekt_progress" id="projekto_projekt_progress" 
		       value="<?php echo esc_attr($progress); ?>" min="0" max="100" step="5" 
		       style="flex:1" oninput="document.getElementById('projekto_progress_val').textContent=this.value+'%'" />
		<span id="projekto_progress_val" style="min-width:45px;font-weight:600;text-align:right"><?php echo esc_html($progress); ?>%</span>
	</div>
	<div style="background:#e5e7eb;border-radius:6px;height:8px;overflow:hidden">
		<div id="projekto_progress_preview" style="height:100%;background:linear-gradient(90deg,#10b981,#34d399);width:<?php echo esc_attr($progress); ?>%;transition:width .2s"></div>
	</div>
	<p class="description" style="margin-top:8px">Fortschritt des Projekts (0% – 100%).</p>
	<script>
		document.getElementById('projekto_projekt_progress').addEventListener('input', function(){
			document.getElementById('projekto_progress_preview').style.width = this.value + '%';
		});
	</script>
	<?php
}

add_action('save_post_projekto_projekt', function($post_id) {
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
	if (!current_user_can('edit_post', $post_id)) return;
	
	// Farbe speichern
	if (isset($_POST['projekto_projekt_color_nonce']) && wp_verify_nonce($_POST['projekto_projekt_color_nonce'], 'projekto_projekt_color')) {
		$color = isset($_POST['projekto_projekt_color']) ? sanitize_hex_color($_POST['projekto_projekt_color']) : '';
		if ($color) {
			update_post_meta($post_id, 'projekto_projekt_color', $color);
		} else {
			delete_post_meta($post_id, 'projekto_projekt_color');
		}
		$gf = isset($_POST['projekto_projekt_gradient_from']) ? sanitize_hex_color($_POST['projekto_projekt_gradient_from']) : '';
		$gt = isset($_POST['projekto_projekt_gradient_to']) ? sanitize_hex_color($_POST['projekto_projekt_gradient_to']) : '';
		if ($gf) {
			update_post_meta($post_id, 'projekto_projekt_gradient_from', $gf);
		} else {
			delete_post_meta($post_id, 'projekto_projekt_gradient_from');
		}
		if ($gt) {
			update_post_meta($post_id, 'projekto_projekt_gradient_to', $gt);
		} else {
			delete_post_meta($post_id, 'projekto_projekt_gradient_to');
		}
	}
	
	// Fortschritt speichern
	if (isset($_POST['projekto_projekt_progress_nonce']) && wp_verify_nonce($_POST['projekto_projekt_progress_nonce'], 'projekto_projekt_progress')) {
		$progress = isset($_POST['projekto_projekt_progress']) ? absint($_POST['projekto_projekt_progress']) : 0;
		$progress = min(100, max(0, $progress));
		update_post_meta($post_id, 'projekto_projekt_progress', $progress);
	}
});

/* ========================================================================
   ADMIN SCRIPTS (Colorpicker + Media Upload)
   ======================================================================== */

add_action('admin_enqueue_scripts', 'projekto_admin_scripts');

function projekto_admin_scripts($hook): void {
	global $typenow, $taxonomy;
	
	// Colorpicker für Status-Taxonomie und Projekt-Editor
	$load_colorpicker = false;
	$load_media = false;
	
	if (in_array($hook, ['term.php', 'edit-tags.php']) && $taxonomy === 'projekto_status') {
		$load_colorpicker = true;
	}
	if (in_array($hook, ['term.php', 'edit-tags.php']) && $taxonomy === 'projekto_zustaendigkeit') {
		$load_media = true;
		$load_colorpicker = true;
	}
	if (in_array($hook, ['post.php', 'post-new.php']) && $typenow === 'projekto_projekt') {
		$load_colorpicker = true;
	}
	
	if ($load_colorpicker) {
		wp_enqueue_style('wp-color-picker');
		wp_enqueue_script('wp-color-picker');
		wp_add_inline_script('wp-color-picker', "
			jQuery(document).ready(function($){
				$('.projekto-color-picker, #projekto_status_color').wpColorPicker();
			});
		");
	}
	
	if ($load_media) {
		wp_enqueue_media();
		wp_add_inline_script('jquery', "
			jQuery(document).ready(function($){
				var frame;
				$('.projekto-upload-image').on('click', function(e){
					e.preventDefault();
					if (frame) { frame.open(); return; }
					frame = wp.media({
						title: 'Bild auswählen',
						button: { text: 'Auswählen' },
						multiple: false,
						library: { type: 'image' }
					});
					frame.on('select', function(){
						var attachment = frame.state().get('selection').first().toJSON();
						$('#projekto_zustaendigkeit_image').val(attachment.id);
						$('#projekto-image-preview').html('<img src=\"' + attachment.sizes.thumbnail.url + '\" style=\"max-width:100px;max-height:100px;border-radius:50%;\" />');
						$('.projekto-remove-image').show();
					});
					frame.open();
				});
				$('.projekto-remove-image').on('click', function(e){
					e.preventDefault();
					$('#projekto_zustaendigkeit_image').val('');
					$('#projekto-image-preview').html('');
					$(this).hide();
				});
			});
		");
	}
}

/* ========================================================================
   ADMIN PROJEKTLISTE – HINTERGRUNDFARBE
   ======================================================================== */

add_action('admin_head', 'projekto_admin_row_colors');

function projekto_admin_row_colors(): void {
	global $typenow, $pagenow;
	if ($pagenow !== 'edit.php' || $typenow !== 'projekto_projekt') return;
	
	$posts = get_posts([
		'post_type'   => 'projekto_projekt',
		'numberposts' => -1,
		'fields'      => 'ids',
	]);
	
	if (!$posts) return;
	
	echo '<style>';
	foreach ($posts as $pid) {
		$color = get_post_meta($pid, 'projekto_projekt_color', true);
		if ($color) {
			echo '#post-' . intval($pid) . ' { background-color: ' . esc_attr($color) . '22 !important; }';
			echo '#post-' . intval($pid) . ':hover { background-color: ' . esc_attr($color) . '44 !important; }';
		}
	}
	echo '</style>';
}

/* ========================================================================
   ADMIN FILTER (Projektliste)
   ======================================================================== */

add_action('restrict_manage_posts', 'projekto_admin_filters');

function projekto_admin_filters(): void {
	global $typenow;
	if ($typenow !== 'projekto_projekt') return;

	$taxonomies = [
		'projekto_status'        => 'Status',
		'projekto_arbeitsbereich' => 'Arbeitsbereich',
	];

	foreach ($taxonomies as $tax => $label) {
		$selected = isset($_GET[$tax]) ? sanitize_text_field($_GET[$tax]) : '';
		wp_dropdown_categories([
			'show_option_all' => "Alle $label",
			'taxonomy'        => $tax,
			'name'            => $tax,
			'orderby'         => 'name',
			'value_field'     => 'slug',
			'selected'        => $selected,
			'hide_empty'      => false,
		]);
	}
}

/* ========================================================================
   GUTENBERG BLOCK
   ======================================================================== */

add_action('init', 'projekto_register_block');

function projekto_register_block(): void {
	register_block_type(__DIR__ . '/blocks/projekto-projects', [
		'render_callback' => 'projekto_render_block',
	]);
}

function projekto_render_block(array $attr): string {
	$statuses        = !empty($attr['statuses']) ? array_map('intval', $attr['statuses']) : [];
	$responsibles    = !empty($attr['responsibles']) ? array_map('intval', $attr['responsibles']) : [];
	$arbeitsbereiche = !empty($attr['arbeitsbereiche']) ? array_map('intval', $attr['arbeitsbereiche']) : [];
	$show_legend     = $attr['showLegend'] ?? true;
	$show_details    = $attr['showDetails'] ?? true;
	$show_status_badge_collapsed = (bool) ($attr['showStatusBadgeCollapsed'] ?? false);
	$default_status  = isset($attr['defaultStatus']) ? (int)$attr['defaultStatus'] : 0;
	$square_badges   = (bool) ($attr['squareBadges'] ?? false);
	$effective_default_status = $default_status;
	if (!empty($statuses) && $default_status && !in_array($default_status, $statuses, true)) {
		$effective_default_status = 0;
	}
	$link_to_single  = $attr['linkToSingle'] ?? false;
	$show_excerpt    = $attr['showExcerpt'] ?? true;
	$show_resp_photo = $attr['showResponsiblePhoto'] ?? true;
	$limit           = intval($attr['limit'] ?? 0);
	$order_by        = in_array($attr['orderBy'] ?? '', ['title', 'date']) ? $attr['orderBy'] : 'title';
	$order           = in_array($attr['order'] ?? '', ['ASC', 'DESC']) ? $attr['order'] : 'ASC';

	// Tax Query bauen
	$tax_query = [];
	if ($statuses)        $tax_query[] = ['taxonomy' => 'projekto_status', 'field' => 'term_id', 'terms' => $statuses];
	if ($responsibles)    $tax_query[] = ['taxonomy' => 'projekto_zustaendigkeit', 'field' => 'term_id', 'terms' => $responsibles];
	if ($arbeitsbereiche) $tax_query[] = ['taxonomy' => 'projekto_arbeitsbereich', 'field' => 'term_id', 'terms' => $arbeitsbereiche];
	if (count($tax_query) > 1) $tax_query['relation'] = 'AND';

	$query = new WP_Query([
		'post_type'      => 'projekto_projekt',
		'posts_per_page' => $limit > 0 ? $limit : -1,
		'orderby'        => $order_by,
		'order'          => $order,
		'tax_query'      => $tax_query,
		'no_found_rows'  => true,
	]);

	// Unique ID für diese Block-Instanz
	$block_id = 'projekto-' . wp_unique_id();

	ob_start();
	?>
	<div class="wp-block-projekto-projects<?php echo $square_badges ? ' projekto-square-badges' : ''; ?>" id="<?php echo esc_attr($block_id); ?>" data-default-status="<?php echo esc_attr($effective_default_status); ?>">
		<?php if ($show_legend): ?>
			<?php
			$legend_terms = $statuses
				? get_terms(['taxonomy' => 'projekto_status', 'include' => $statuses, 'hide_empty' => false])
				: get_terms(['taxonomy' => 'projekto_status', 'hide_empty' => false]);
			?>
			<?php if (!is_wp_error($legend_terms) && $legend_terms): ?>
				<ul class="projekto-legend">
					<li class="projekto-legend-item projekto-filter<?php echo $effective_default_status ? '' : ' is-active'; ?>" data-filter="all">
						<span class="projekto-dot" style="background:#6b7280"></span>
						<span>Alle</span>
					</li>
					<?php foreach ($legend_terms as $t): $c = projekto_get_status_color($t->term_id); ?>
						<li class="projekto-legend-item projekto-filter<?php echo ((int) $t->term_id === (int) $effective_default_status) ? ' is-active' : ''; ?>" data-filter="<?php echo esc_attr($t->term_id); ?>" <?php echo $c ? 'style="--projekto-color:' . esc_attr($c) . '"' : ''; ?>>
							<span class="projekto-dot"></span>
							<span><?php echo esc_html($t->name); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		<?php endif; ?>

		<?php if (!$query->have_posts()): ?>
			<p class="projekto-empty">Aktuell sind keine Projekte vorhanden.</p>
		<?php else: ?>
			<div class="projekto-badges">
				<?php while ($query->have_posts()): $query->the_post();
					$pid = get_the_ID();
					$title = get_the_title();
					$link = get_permalink();
					$content = apply_filters('the_content', get_the_content());
					$excerpt = has_excerpt($pid) ? get_the_excerpt($pid) : '';
					$status_terms = get_the_terms($pid, 'projekto_status');
					$status = (!is_wp_error($status_terms) && $status_terms) ? $status_terms[0] : null;
					$status_id = $status ? $status->term_id : 0;
					$item_hidden = ($effective_default_status && ((int) $status_id !== (int) $effective_default_status));
					$status_name = $status ? $status->name : '';
					$status_color = $status ? projekto_get_status_color($status->term_id) : '';
					$projekt_color = (string) get_post_meta($pid, 'projekto_projekt_color', true);
					$projekt_color = $projekt_color ? (string) sanitize_hex_color($projekt_color) : '';
					$grad_from = (string) get_post_meta($pid, 'projekto_projekt_gradient_from', true);
					$grad_to = (string) get_post_meta($pid, 'projekto_projekt_gradient_to', true);
					$grad_from = $grad_from ? (string) sanitize_hex_color($grad_from) : '';
					$grad_to = $grad_to ? (string) sanitize_hex_color($grad_to) : '';
					$projekt_bg = $projekt_color;
					$contrast_base = $projekt_color;
					if ($grad_from && $grad_to) {
						$projekt_bg = 'linear-gradient(135deg, ' . $grad_from . ', ' . $grad_to . ')';
						$contrast_base = $grad_from;
					} elseif ($grad_from || $grad_to) {
						// If only one color is set, behave like solid color
						$contrast_base = $grad_from ?: $grad_to;
						$projekt_bg = $contrast_base;
					}
					$item_text_color = $contrast_base ? projekto_contrast_text_color($contrast_base) : '';
					$item_divider_color = '';
					if ($item_text_color) {
						$item_divider_color = (strtolower($item_text_color) === '#ffffff') ? 'rgba(255,255,255,.22)' : 'rgba(17,24,39,.12)';
					}
					$updated_label = get_the_modified_date(get_option('date_format'), $pid);
					$progress = (int) get_post_meta($pid, 'projekto_projekt_progress', true);
					
					// Style zusammenbauen
					$styles = [];
					if ($status_color) $styles[] = '--projekto-color:' . esc_attr($status_color);
					if ($projekt_bg) $styles[] = '--projekto-bg:' . esc_attr($projekt_bg);
					if ($item_text_color) $styles[] = '--projekto-item-text:' . esc_attr($item_text_color);
					if ($item_divider_color) $styles[] = '--projekto-divider:' . esc_attr($item_divider_color);
					$style_attr = $styles ? 'style="' . implode(';', $styles) . '"' : '';
					
					// Zuständigkeiten sammeln
					$zustaendig = [];
					$res = get_the_terms($pid, 'projekto_zustaendigkeit');
					if (!is_wp_error($res) && $res) {
						foreach ($res as $r) {
							$zustaendig[] = [
								'name'  => $r->name,
								'image' => projekto_get_zustaendigkeit_image($r->term_id),
								'color' => projekto_get_zustaendigkeit_color($r->term_id),
							];
						}
					}
					$primary_responsible = $zustaendig ? $zustaendig[0] : null;
					$responsible_label = '';
					if ($primary_responsible && !empty($primary_responsible['name'])) {
						$responsible_label = $primary_responsible['name'];
						if (count($zustaendig) > 1) {
							$responsible_label .= ' +' . (count($zustaendig) - 1);
						}
					}
				?>
					<?php if ($show_details): ?>
						<div class="projekto-item<?php echo $item_hidden ? ' is-hidden' : ''; ?>" data-status="<?php echo esc_attr($status_id); ?>" <?php echo $style_attr; ?>>
							<?php if ($show_status_badge_collapsed): ?>
								<span class="projekto-pin" aria-hidden="true"></span>
							<?php endif; ?>
							<button type="button" class="projekto-badge projekto-toggle" aria-haspopup="dialog" aria-label="Details öffnen: <?php echo esc_attr($title); ?>">
								<span class="projekto-title"><?php echo esc_html($title); ?></span>
							</button>
							<template class="projekto-modal-template">
								<div class="projekto-modal" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr($title); ?>">
									<div class="projekto-modal-inner">
										<div class="projekto-modal-header" <?php echo $style_attr; ?>>
											<button type="button" class="projekto-modal-close" aria-label="Schließen"></button>
											<h3 class="projekto-modal-title"><?php echo esc_html($title); ?></h3>
											<div class="projekto-modal-sub">
												<?php if (!empty($updated_label)): ?>
													<span class="projekto-modal-updated">Aktualisiert am <?php echo esc_html($updated_label); ?></span>
												<?php endif; ?>
												<?php if (!empty($status_name)): ?>
													<span class="projekto-modal-status-badge" <?php echo $status_color ? 'style="--badge-color:' . esc_attr($status_color) . '"' : ''; ?>><?php echo esc_html($status_name); ?></span>
												<?php endif; ?>
											</div>
										</div>
										<div class="projekto-modal-body">
											<?php if ($progress > 0): ?>
												<div class="projekto-progress projekto-progress--modal">
													<div class="projekto-progress-header">
														<span class="projekto-progress-label">Fortschritt</span>
														<span class="projekto-progress-value"><?php echo esc_html($progress); ?>%</span>
													</div>
													<div class="projekto-progress-bar">
														<div class="projekto-progress-fill" style="width:<?php echo esc_attr($progress); ?>%"></div>
													</div>
												</div>
											<?php endif; ?>
											<?php if ($content): ?>
												<div class="projekto-content projekto-content--modal"><?php echo $content; ?></div>
											<?php elseif ($show_excerpt && $excerpt): ?>
												<div class="projekto-content projekto-content--modal"><?php echo wpautop($excerpt); ?></div>
											<?php endif; ?>
											<?php if ($link_to_single): ?>
												<a class="projekto-more" href="<?php echo esc_url($link); ?>">Mehr erfahren →</a>
											<?php endif; ?>
											<?php if ($zustaendig): ?>
												<div class="projekto-modal-responsibles">
													<?php foreach ($zustaendig as $z): 
														$z_color = !empty($z['color']) ? $z['color'] : '#6b7280';
														$z_text  = projekto_contrast_text_color($z_color);
													?>
														<?php if ($show_resp_photo && !empty($z['image'])): ?>
															<div class="projekto-modal-resp projekto-modal-resp--photo" style="--resp-bg:<?php echo esc_attr($z_color); ?>;--resp-text:<?php echo esc_attr($z_text); ?>">
																<img src="<?php echo esc_url($z['image']); ?>" alt="" class="projekto-modal-resp-avatar" />
																<span class="projekto-modal-resp-name"><?php echo esc_html($z['name']); ?></span>
															</div>
														<?php else: ?>
															<span class="projekto-modal-resp projekto-modal-resp--badge" style="--resp-bg:<?php echo esc_attr($z_color); ?>;--resp-text:<?php echo esc_attr($z_text); ?>"><?php echo esc_html($z['name']); ?></span>
														<?php endif; ?>
													<?php endforeach; ?>
												</div>
											<?php endif; ?>
										</div>
									</div>
								</div>
							</template>
						</div>
					<?php else: ?>
						<div class="projekto-item<?php echo $item_hidden ? ' is-hidden' : ''; ?>" data-status="<?php echo esc_attr($status_id); ?>" <?php echo $style_attr; ?>>
							<?php if ($show_status_badge_collapsed): ?>
								<span class="projekto-pin" aria-hidden="true"></span>
							<?php endif; ?>
							<a class="projekto-badge" href="<?php echo esc_url($link); ?>">
								<span class="projekto-title"><?php echo esc_html($title); ?></span>
							</a>
						</div>
					<?php endif; ?>
				<?php endwhile; ?>
			</div>
		<?php endif; ?>
	</div>
	<?php
	wp_reset_postdata();
	return ob_get_clean();
}

/* ========================================================================
   FRONTEND SCRIPTS
   ======================================================================== */

add_action('wp_footer', 'projekto_frontend_scripts');

function projekto_frontend_scripts(): void {
	if (!has_block('projekto/projects')) return;
	?>
	<script>
	document.addEventListener('DOMContentLoaded', function(){
		var isCoarsePointer = (window.matchMedia && window.matchMedia('(pointer: coarse)').matches) ? true : false;
		var supportsScrollbarGutter = (window.CSS && typeof window.CSS.supports === 'function' && window.CSS.supports('scrollbar-gutter: stable')) ? true : false;
		var previousBodyPaddingRight = '';
		var didCompensateScrollbar = false;
		var getScrollbarWidth = function(){
			// width of the scrollbar that would disappear when we lock scrolling
			var width = window.innerWidth - document.documentElement.clientWidth;
			return width > 0 ? width : 0;
		};
		var backdrop = document.querySelector('.projekto-modal-backdrop');
		var root = document.querySelector('.projekto-modal-root');
		if (!backdrop) {
			backdrop = document.createElement('div');
			backdrop.className = 'projekto-modal-backdrop';
			document.body.appendChild(backdrop);
		}
		if (!root) {
			root = document.createElement('div');
			root.className = 'projekto-modal-root';
			document.body.appendChild(root);
		}
		var ensureModalBaseStyles = function(){
			// In case theme/CSS caching prevents our stylesheet from applying,
			// enforce the overlay positioning with inline styles.
			root.style.position = 'fixed';
			root.style.inset = '0';
			root.style.zIndex = '999999';
			root.style.pointerEvents = 'none';

			backdrop.style.position = 'fixed';
			backdrop.style.inset = '0';
			backdrop.style.zIndex = '999998';
			backdrop.style.background = 'rgba(0,0,0,.5)';
			backdrop.style.backdropFilter = 'blur(8px)';
		};
		var activeModal = null;
		var lastTrigger = null;
		var isClosing = false;
		var closeModal = function(){
			if (isClosing) return;
			if (activeModal) {
				isClosing = true;
				activeModal.classList.add('is-closing');
				backdrop.style.transition = 'opacity .25s ease';
				backdrop.style.opacity = '0';
				setTimeout(function(){
					if (activeModal) {
						activeModal.remove();
						activeModal = null;
					}
					backdrop.classList.remove('is-active');
					backdrop.style.opacity = '';
					backdrop.style.visibility = '';
					backdrop.style.transition = '';
					document.documentElement.classList.remove('projekto-modal-open');
					if (didCompensateScrollbar) {
						document.body.style.paddingRight = previousBodyPaddingRight;
						didCompensateScrollbar = false;
					}
					if (!isCoarsePointer && lastTrigger && typeof lastTrigger.focus === 'function') {
						lastTrigger.focus();
					}
					lastTrigger = null;
					isClosing = false;
				}, 280);
			} else {
				backdrop.classList.remove('is-active');
				backdrop.style.opacity = '';
				backdrop.style.visibility = '';
				document.documentElement.classList.remove('projekto-modal-open');
				if (didCompensateScrollbar) {
					document.body.style.paddingRight = previousBodyPaddingRight;
					didCompensateScrollbar = false;
				}
				lastTrigger = null;
			}
		};
		var openModalFromItem = function(item, trigger){
			var tpl = item ? item.querySelector('template.projekto-modal-template') : null;
			if (!tpl || !tpl.content) return;
			closeModal();
			ensureModalBaseStyles();
			var sbw = getScrollbarWidth();
			if (!supportsScrollbarGutter && sbw > 0) {
				previousBodyPaddingRight = document.body.style.paddingRight || '';
				document.body.style.paddingRight = sbw + 'px';
				didCompensateScrollbar = true;
			}
			lastTrigger = trigger || null;
			var frag = tpl.content.cloneNode(true);
			root.appendChild(frag);
			var modals = root.querySelectorAll('.projekto-modal');
			activeModal = modals.length ? modals[modals.length - 1] : null;
			backdrop.classList.add('is-active');
			backdrop.style.opacity = '1';
			backdrop.style.visibility = 'visible';
			document.documentElement.classList.add('projekto-modal-open');
			var closeBtn = activeModal ? activeModal.querySelector('.projekto-modal-close') : null;
			if (!isCoarsePointer && closeBtn) closeBtn.focus();
		};

		if (!backdrop.dataset.bound) {
			backdrop.dataset.bound = '1';
			backdrop.addEventListener('click', function(){ closeModal(); });
			document.addEventListener('keydown', function(e){
				if (e.key === 'Escape') closeModal();
			});
			document.addEventListener('click', function(e){
				var btn = e.target && e.target.closest ? e.target.closest('.projekto-modal-close') : null;
				if (btn) {
					e.preventDefault();
					closeModal();
				}
			});
		}

		// Close when clicking outside the modal card (overlay area).
		if (!root.dataset.boundOutside) {
			root.dataset.boundOutside = '1';
			root.addEventListener('click', function(e){
				if (!activeModal) return;
				// If the click hit the overlay itself (padding area), close.
				if (e.target === activeModal) {
					e.preventDefault();
					closeModal();
				}
			});
		}

		document.querySelectorAll('.wp-block-projekto-projects').forEach(function(block){
			var items = block.querySelectorAll('.projekto-item');
			var filters = block.querySelectorAll('.projekto-filter');
			var applyFilterInstant = function(f){
				items.forEach(function(it){
					var status = it.dataset.status;
					var shouldShow = (f === 'all') || (String(status) === String(f));
					if (shouldShow) {
						it.classList.remove('is-hidden');
						it.classList.remove('is-fading-out');
						it.classList.remove('is-fading-in');
					} else {
						it.classList.add('is-hidden');
						it.classList.remove('is-fading-out');
						it.classList.remove('is-fading-in');
					}
				});
			};
			
			// Filter-Klicks mit Animation
			filters.forEach(function(filter){
				filter.addEventListener('click', function(){
					closeModal();
					var f = this.dataset.filter;
					filters.forEach(function(fl){ fl.classList.remove('is-active'); });
					this.classList.add('is-active');
					
					// Animation: erst alle ausblenden die nicht passen
					var toHide = [];
					var toShow = [];
					
					items.forEach(function(it){
						var status = it.dataset.status;
						var shouldShow = (f === 'all') || (String(status) === String(f));
						
						if (shouldShow && it.classList.contains('is-hidden')) {
							toShow.push(it);
						} else if (!shouldShow && !it.classList.contains('is-hidden')) {
							toHide.push(it);
						}
					});
					
					// Ausblenden mit Animation
					toHide.forEach(function(it, index){
						it.classList.add('is-fading-out');
						setTimeout(function(){
							it.classList.add('is-hidden');
							it.classList.remove('is-fading-out');
						}, 250);
					});
					
					// Einblenden mit verzögerter Animation
					setTimeout(function(){
						toShow.forEach(function(it, index){
							it.classList.remove('is-hidden');
							it.classList.add('is-fading-in');
							setTimeout(function(){
								it.classList.remove('is-fading-in');
							}, 350);
						});
					}, toHide.length > 0 ? 200 : 0);
				});
			});

			// Badge-Click: Modal öffnen
			block.querySelectorAll('.projekto-toggle').forEach(function(btn){
				btn.addEventListener('click', function(e){
					e.preventDefault();
					var item = this.closest('.projekto-item');
					openModalFromItem(item, this);
				});
			});

			// Default-Status beim Laden anwenden (auch wenn Legende ausgeblendet ist)
			var dsRaw = block.getAttribute('data-default-status');
			var ds = parseInt(dsRaw || '0', 10) || 0;
			if (ds > 0) {
				applyFilterInstant(String(ds));
				if (filters && filters.length) {
					filters.forEach(function(fl){ fl.classList.remove('is-active'); });
					var defBtn = block.querySelector('.projekto-filter[data-filter="' + ds + '"]');
					if (defBtn) defBtn.classList.add('is-active');
				}
			} else {
				applyFilterInstant('all');
				if (filters && filters.length) {
					// Ensure at least "Alle" is active
					var anyActive = false;
					filters.forEach(function(fl){ if (fl.classList.contains('is-active')) anyActive = true; });
					if (!anyActive) {
						var allBtn = block.querySelector('.projekto-filter[data-filter="all"]');
						if (allBtn) allBtn.classList.add('is-active');
					}
				}
			}
		});
	});
	</script>
	<?php
}

/* ========================================================================
   REWRITE FLUSH
   ======================================================================== */

register_activation_hook(__FILE__, function () {
	projekto_register_content();
	flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function () {
	flush_rewrite_rules();
});
