<?php
/*
Plugin Name: Recipe Schema Markup
Plugin URI: https://wordpress.org/plugins/recipe-schema-markup/
Description: Create good looking printable recipes. With Recipe Schema Markup your recipes are optimized for search engines.
Version: 1.0
Author: Buymeapie
Author URI: http://buymeapie.com
License: GPLv2 or later
*/

/*
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if (!function_exists('add_action')) {
	echo "This is a plugin and is not meant to be invoked directly.";
	exit;
}

if (!defined('BUYMEAPIE_VERSION_KEY'))
    define('BUYMEAPIE_VERSION_KEY', 'buymeapie_version');

if (!defined('BUYMEAPIE_VERSION_NUM'))
    define('BUYMEAPIE_VERSION_NUM', '1.0.0');

$buymeapie_db_version = "1.0.0";

add_option(BUYMEAPIE_VERSION_KEY, BUYMEAPIE_VERSION_NUM);

register_activation_hook(__FILE__, 'buymeapie_recipe_install');
add_action('plugins_loaded', 'buymeapie_recipe_install');

add_action('init', 'add_buymeapie_recipe_button');
add_action('admin_menu', 'buymeapie_recipe_admin_menu');
add_action('admin_init', 'buymeapie_recipe_admin_init');

$buymeapie_directory = get_option('siteurl') . '/wp-content/plugins/' . dirname(plugin_basename(__FILE__));

add_action("plugins_loaded", "buymeapie_recipe_load_translation");

function buymeapie_recipe_load_translation() {
	global $buymeapie_directory;
	load_plugin_textdomain("buymeapie-recipe", false, dirname(plugin_basename(__FILE__)) . "/languages");
}

function buymeapie_recipe_admin_init() {
	global $buymeapie_directory;
	wp_register_style('buymeapie-recipe-theme-style', $buymeapie_directory . "/css/wordpress-theme.css");
	wp_register_script('buymeapie-recipe-theme-script', $buymeapie_directory . "/js/wordpress-theme.js");
}

function add_buymeapie_recipe_button() {
	if (!current_user_can('edit_posts') && !current_user_can('edit_pages')) {
		return;
	}

	if (get_user_option('rich_editing') == 'true') {
		add_filter('mce_external_plugins', 'add_buymeapie_recipe_tinymce_plugin');
		add_filter('mce_buttons', 'add_buymeapie_recipe_tinymce_button');
		add_action('admin_print_scripts', 'buymeapie_recipe_admin_scripts');
		add_action('admin_print_styles', 'buymeapie_recipe_admin_styles');
	}
}

function buymeapie_recipe_mce_css($mce_css) {
	if (!empty($mce_css)) {
		$mce_css .= ",";
	}

	$mce_css .= plugins_url('css/editor.css', __FILE__ );

	return $mce_css;
}

add_filter('mce_css', 'buymeapie_recipe_mce_css');

function add_buymeapie_recipe_tinymce_button($buttons) {
	$buttons[] = 'separator';
	$buttons[] = 'buymeapieRecipe';
	return $buttons;
}

function add_buymeapie_recipe_tinymce_plugin($plugins) {
	global $buymeapie_directory;
	$plugins['buymeapieRecipe'] = $buymeapie_directory . '/js/editor.js';
	$plugins['noneditable'] = $buymeapie_directory . '/js/noneditable.js';
	return $plugins;
}

function buymeapie_recipe_admin_menu() {
	global $buymeapie_directory;

	$page = add_menu_page('Recipe Schema Markup Themes', 'Recipe Schema Markup', 'edit_others_posts', 'buymeapie_recipe_themes', 'buymeapie_recipe_themes', $buymeapie_directory . '/images/menu-icon.png');

	add_action('admin_print_styles-' . $page, 'buymeapie_recipe_admin_styles');
	add_action('admin_print_scripts-' . $page, 'buymeapie_recipe_admin_settings_scripts');
}

function buymeapie_recipe_admin_settings_scripts() {
	wp_enqueue_script('buymeapie-recipe-theme-script');
}

function buymeapie_recipe_admin_styles() {
	wp_enqueue_style('buymeapie-recipe-theme-style');
	wp_enqueue_style('thickbox');
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'buymeapie_recipe_add_plugin_action_links' );

function buymeapie_recipe_add_plugin_action_links($links) {
    $blog_name = get_bloginfo("name");

	return array_merge(
		array(
			'settings' => '<a href="' . get_bloginfo( 'wpurl' ) . '/wp-admin/admin.php?page=buymeapie_recipe_themes">Settings</a>',
			'feedback' => '<a href="mailto:support@buymeapie.com?subject=Feedback from ' . $blog_name . '" target="_blank">Feedback</a>'
		),
		$links
	);
}

add_action('wp_enqueue_scripts', 'buymeapie_recipe_custom_scripts');

function buymeapie_recipe_custom_scripts() {
	global $buymeapie_directory;

	wp_register_style('buymeapie-recipe-theme-layout', $buymeapie_directory . "/css/layout.css");
	wp_enqueue_style('buymeapie-recipe-theme-layout');

	wp_enqueue_script('jquery');

	wp_register_script('buymeapie-recipe-post', $buymeapie_directory . "/js/post.js");
	wp_enqueue_script('buymeapie-recipe-post');
}

function buymeapie_recipe_themes() {
	global $wpdb;
	global $buymeapie_directory;

	$table_name = $wpdb->prefix . "buymeapie_recipe_theme";
	$themes = $wpdb->get_col("SELECT theme FROM $table_name WHERE name != 'Current' ORDER BY id DESC");
	$saved_themes = implode(",", $themes);

	$theme = $wpdb->get_row("SELECT theme FROM $table_name WHERE name = 'Current'");
	if (empty($theme)) {
		$applied_theme = "null";
	} else {
		$applied_theme = $theme->theme;
	}
?>
	<div class="wrap buymeapie_recipe_theme">
		<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
		<script type="text/javascript">window.buymeapieRecipeAppliedTheme = <?php echo $applied_theme ?>; window.buymeapieRecipeSavedThemes = [<?php echo $saved_themes ?>];</script>
		<script>
		    jQuery(function(){
		        jQuery('#buymeapie-recipe-themes').height(window.innerHeight - jQuery('#buymeapie-recipe-themes').offset().top - 45);
		    });
		</script>
		<iframe id="buymeapie-recipe-themes" src="<?php echo $buymeapie_directory ?>/html/theme.html"></iframe>
	</div>
<?php
}

function buymeapie_recipe_admin_scripts() {
	wp_enqueue_script('media-upload');
	wp_enqueue_script('thickbox');
	wp_enqueue_script('my-upload');
	wp_enqueue_script('jquery');
}

$buymeapie_recipe_chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_";
$buymeapie_recipe_char_map = array();

function buymeapie_recipe_create_map() {
	global $buymeapie_recipe_chars;
	global $buymeapie_recipe_char_map;

	for ($i = 0; $i < strlen($buymeapie_recipe_chars); $i++) {
		$ch = substr($buymeapie_recipe_chars, $i, 1);
		$buymeapie_recipe_char_map[$ch] = $i;
	}
}

buymeapie_recipe_create_map();

function buymeapie_recipe_to_id($n) {
	global $buymeapie_recipe_chars;

	if ($n === 0) {
		return $buymeapie_recipe_chars[0];
	}

	$digits = "";
	while ($n > 0) {
		$digits .= $buymeapie_recipe_chars[$n % 64];
		$n = floor($n / 64);
	}

	return strrev($digits);
}

function buymeapie_recipe_from_id($id) {
	global $buymeapie_recipe_char_map;

	$id = strrev($id);

	$sum = 0;
	for ($i = 0; $i < strlen($id); $i++) {
		$ch = substr($id, $i, 1);
		$sum += ($buymeapie_recipe_char_map[$ch] * pow(64, $i));
	}

	return $sum;
}

function buymeapie_recipe_install() {
	global $wpdb;
	global $buymeapie_db_version;

	$installed_version = get_option("buymeapie_db_version");

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

	if ($installed_version != $buymeapie_db_version) {
		$table_name = $wpdb->prefix . "buymeapie_recipe_recipe";
		$sql = "CREATE TABLE $table_name (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			recipe TEXT NOT NULL,
			created TIMESTAMP DEFAULT NOW() NOT NULL,
			post_id BIGINT(20) UNSIGNED
			) CHARACTER SET UTF8;";

		dbDelta($sql);

		$theme_table_name = $wpdb->prefix . "buymeapie_recipe_theme";
		$theme_sql = "CREATE TABLE $theme_table_name (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			name TEXT NOT NULL,
			theme TEXT NOT NULL
			) CHARACTER SET UTF8;";

		dbDelta($theme_sql);

		$theme_row = $wpdb->get_row("SELECT * FROM $theme_table_name WHERE name='Current'");
		if (empty($theme_row)) {
			$default_theme = '{"name":"Current","description":"Live on blog","color":{"name":"Old Movie","title":"#414141","subheader":"#414141","save":"#666666","stat":"#808080","text":"#414141","print":"#bfbfbf","import":"#bfbfbf","saveHighlight":"#808080","printHighlight":"#d9d9d9","importHighlight":"#d9d9d9","titleHighlight":"#5a5a5a","subheaderHighlight":"#5a5a5a","statHighlight":"#9a9a9a","textHighlight":"#5a5a5a","saveText":"#ffffff","printText":"#ffffff","importText":"#ffffff"},"background":{"name":"No Border","background":"white","box":"white","border":{"style":"none","width":1,"corner":0,"color":"rgb(220, 220, 220)"},"innerBorder":{"style":"solid","width":1,"corner":0,"color":"rgb(220, 220, 220)"},"boxBorder":{"style":"solid","width":1,"corner":0,"color":"rgb(220, 220, 220)"}},"font":{"name":"Sans Basic","header":{"name":"Helvetica Neue","size":22,"transform":"none","bold":false,"italic":false,"underline":false,"family":"Helvetica Neue,Helvetica,Arial,sans-serif","websafe":true},"subheader":{"name":"Helvetica Neue","size":18,"transform":"none","bold":false,"italic":false,"underline":false,"family":"Helvetica Neue,Helvetica,Arial,sans-serif","websafe":true},"body":{"name":"Helvetica Neue","size":14,"transform":"none","bold":false,"italic":false,"underline":false,"family":"Helvetica Neue,Helvetica,Arial,sans-serif","websafe":true},"info":{"name":"Helvetica Neue","size":14,"transform":"none","bold":false,"italic":false,"underline":false,"family":"Helvetica Neue,Helvetica,Arial,sans-serif","websafe":true},"button":{"name":"Helvetica Neue","size":13,"transform":"none","bold":false,"italic":false,"underline":false,"family":"Helvetica Neue,Helvetica,Arial,sans-serif","websafe":true}},"layout":{"name":"Standard","style":"blog-buymeapie-standard","picture":true,"description":true,"stats":true,"nutrition":true,"reviews":true,"print":true,"import":true,"sectionHeaders":true,"brand":false, "condensed":false}}';

			$theme_data = array(
				"name" => "Current",
				"theme" => $default_theme
				);

			$wpdb->insert($theme_table_name, $theme_data);
		}

		add_option("buymeapie_db_version", $buymeapie_db_version);
	}
}

function buymeapie_recipe_save_recipe() {
	global $wpdb;

	$x = $_POST['data'];

	$recipe = $x["recipe"];
	$recipe_id = $x["recipeId"];
	$post_id = $x["post"];

	$json_recipe =json_encode($recipe);

	// BEGIN VERY BIZARRE JSON ENCODE FIX
	$json_recipe = str_replace("\\\'", "'", $json_recipe);
	$json_recipe = str_replace("\\\"", "\"", $json_recipe);
	$json_recipe = str_replace("\\\\", "\\", $json_recipe);
	// END VERY BIZARRE JSON ENCODE FIX

	$data = array(
		"recipe" => $json_recipe,
		"post_id" => $post_id
		);
	$table_name = $wpdb->prefix . "buymeapie_recipe_recipe";

	if (!empty($recipe_id)) {
		$wpdb->update($table_name, $data, array('id' => $recipe_id));
	} else {
		$wpdb->insert($table_name, $data);
		$recipe_id = $wpdb->insert_id;
	}

	echo json_encode(array("recipeId" => $recipe_id));
	die();
}

function buymeapie_recipe_load_recipe() {
	global $wpdb;

	$recipeId = intval($_POST['data']);

	$table_name = $wpdb->prefix . "buymeapie_recipe_recipe";
	$recipe_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id=%d", $recipeId));
	if (empty($recipe_row)) {
		return json_encode(FALSE);
	}

	$recipe = json_decode($recipe_row->recipe);

	echo json_encode(array("recipe" => $recipe));
	die();
}

function buymeapie_recipe_delete_recipe() {
	global $wpdb;

	$recipeId = intval($_POST['data']);

	$table_name = $wpdb->prefix . "buymeapie_recipe_recipe";
	$wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE id=%d", $recipeId));

	echo json_encode(TRUE);
	die();
}

function buymeapie_recipe_title_recipe() {
	global $wpdb;

	$recipeId = intval($_POST['data']);

	$table_name = $wpdb->prefix . "buymeapie_recipe_recipe";
	$recipe_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id=%d", $recipeId));
	if (empty($recipe_row)) {
		return json_encode(FALSE);
	}

	$recipe = json_decode($recipe_row->recipe);
	$title = $recipe->title;

	echo json_encode($title);
	die();
}

function buymeapie_recipe_save_theme() {
	global $wpdb;

	$theme = $_POST['data'];
	$theme_name = $theme["name"];

	$theme = buymeapie_recipe_load_theme($theme);

	$data = array(
		"name" => $theme_name,
		"theme" => json_encode($theme)
		);
	$table_name = $wpdb->prefix . "buymeapie_recipe_theme";

	$theme_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE name=%s", $theme_name));
	if (empty($theme_row)) {
		$wpdb->insert($table_name, $data);
	} else {
		$wpdb->update($table_name, $data, array('id' => $theme_row->id));
	}

	echo json_encode(TRUE);
	die();
}

function buymeapie_recipe_remove_theme() {
	global $wpdb;

	$theme_name = $_POST['data'];

	$table_name = $wpdb->prefix . "buymeapie_recipe_theme";
	$wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE name=%s", $theme_name));

	echo json_encode(TRUE);
	die();
}

function buymeapie_recipe_load_font($font) {
	$font["size"] = intval($font["size"]);
	$font["bold"] = $font["bold"] === "true";
	$font["italic"] = $font["italic"] === "true";
	$font["underline"] = $font["underline"] === "true";
	$font["websafe"] = $font["websafe"] === "true";

	return $font;
}

function buymeapie_recipe_load_theme($theme) {
	$theme["background"]["border"]["width"] = intval($theme["background"]["border"]["width"]);
	$theme["background"]["border"]["corner"] = intval($theme["background"]["border"]["corner"]);
	$theme["background"]["innerBorder"]["width"] = intval($theme["background"]["innerBorder"]["width"]);
	$theme["background"]["innerBorder"]["corner"] = intval($theme["background"]["innerBorder"]["corner"]);
	$theme["background"]["boxBorder"]["width"] = intval($theme["background"]["boxBorder"]["width"]);
	$theme["background"]["boxBorder"]["corner"] = intval($theme["background"]["boxBorder"]["corner"]);

	$theme["font"]["header"] = buymeapie_recipe_load_font($theme["font"]["header"]);
	$theme["font"]["subheader"] = buymeapie_recipe_load_font($theme["font"]["subheader"]);
	$theme["font"]["body"] = buymeapie_recipe_load_font($theme["font"]["body"]);
	$theme["font"]["info"] = buymeapie_recipe_load_font($theme["font"]["info"]);
	$theme["font"]["button"] = buymeapie_recipe_load_font($theme["font"]["button"]);

	$theme["layout"]["picture"] = $theme["layout"]["picture"] !== "false";
	$theme["layout"]["description"] = $theme["layout"]["description"] !== "false";
	$theme["layout"]["stats"] = $theme["layout"]["stats"] !== "false";
	$theme["layout"]["nutrition"] = $theme["layout"]["nutrition"] !== "false";
	$theme["layout"]["print"] = $theme["layout"]["print"] !== "false";
	$theme["layout"]["import"] = $theme["layout"]["import"] !== "false";
    $theme["layout"]["sectionHeaders"] = $theme["layout"]["sectionHeaders"] !== "false";
    $theme["layout"]["brand"] = $theme["layout"]["brand"] === "true";
    $theme["layout"]["condensed"] = $theme["layout"]["condensed"] === "true";
    $theme["layout"]["numberedIngredients"] = $theme["layout"]["numberedIngredients"] === "true";
    $theme["layout"]["numberedMethods"] = $theme["layout"]["numberedMethods"] === "true";
    $theme["layout"]["numberedNotes"] = $theme["layout"]["numberedNotes"] === "true";

	return $theme;
}

add_action('wp_ajax_buymeapie_recipe_prompt', 'buymeapie_recipe_prompt');

add_action('wp_ajax_buymeapie_recipe_save_recipe', 'buymeapie_recipe_save_recipe');

add_action('wp_ajax_buymeapie_recipe_load_recipe', 'buymeapie_recipe_load_recipe');

add_action('wp_ajax_buymeapie_recipe_delete_recipe', 'buymeapie_recipe_delete_recipe');

add_action('wp_ajax_buymeapie_recipe_title_recipe', 'buymeapie_recipe_title_recipe');

add_action('wp_ajax_buymeapie_recipe_save_theme', 'buymeapie_recipe_save_theme');

add_action('wp_ajax_buymeapie_recipe_remove_theme', 'buymeapie_recipe_remove_theme');

function buymeapie_recipe_prompt() {
	$type = $_POST['data'];

	update_option("buymeapie_recipe_prompt_" . $type, "true");
}

function buymeapie_recipe_time($time) {
	if (empty($time)) {
		return null;
	}
	
	$label_hr_text = __("hr", "buymeapie-recipe");
	$label_min_text = __("min", "buymeapie-recipe");
	
	$time = intval($time);

	$minutes = $time % 60;
	$hours = floor(($time - $minutes) / 60);

	if ($hours > 0) {
		$result = $hours . " " . $label_hr_text;
	} else {
		$result = "";
	}

	if ($minutes > 0) {
		$result = $result . " " . $minutes . " " . $label_min_text;
	}

	return trim($result);
}

function buymeapie_recipe_create_font($font) {
	return (object) array(
		"family" => $font->family,
		"size" => $font->size . "px",
		"transform" => $font->transform,
		"decoration" => $font->underline ? "underline" : "none",
		"weight" => $font->bold ? "bold" : "normal",
		"style" => $font->italic ? "italic" : "normal",
		"websafe" => $font->websafe
	);
}

function buymeapie_recipe_get_style() {
	global $wpdb;

	$theme_table_name = $wpdb->prefix . "buymeapie_recipe_theme";
	$theme_row = $wpdb->get_row("SELECT * FROM $theme_table_name WHERE name='Current'");
	if (empty($theme_row)) {
		$theme = null;
	} else {
		$theme = json_decode($theme_row->theme);
	}
	return $theme;
}

// create ISO formatted times (see http://en.wikipedia.org/wiki/ISO_8601#Durations)

function buymeapie_format_ISO($time) {
    $isoTime = str_replace(" hr", "H", $time);
    $isoTime = str_replace(" min", "M", $isoTime);
    $isoTime = str_replace(" ", "", $isoTime);
    $isoTime = "PT" . $isoTime;
    
    return $isoTime;
}

// renders HTML for the recipe
//   includes hRecipe (see http://microformats.org/wiki/hrecipe)
//   includes recipe microdata (see http://schema.org/Recipe)
function buymeapie_recipe_render_recipe($recipeId) {
	global $wpdb;
	global $buymeapie_host;

	// get recipe
	$recipe_table_name = $wpdb->prefix . "buymeapie_recipe_recipe";
	$recipe_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $recipe_table_name WHERE id=%d", intval($recipeId)));
	if (empty($recipe_row)) {
		return "";
	}

	$recipe = json_decode($recipe_row->recipe);
	$created = $recipe_row->created;

	$title = $recipe->title;
	$summary = $recipe->summary;
	$prep_time = buymeapie_recipe_time($recipe->prepTime);
	$cook_time = buymeapie_recipe_time($recipe->cookTime);
	$total_time = buymeapie_recipe_time($recipe->totalTime);

	$calories = $recipe->calories;
	$total_fat = $recipe->fat;
	$protein = $recipe->protein;
	$total_carbohydrates = $recipe->carbs;

	$serves = $recipe->servings;
	$yields = $recipe->yields;
	$adapted = $recipe->adapted;
	$adapted_link = $recipe->adaptedLink;
	$author = $recipe->author;
	$image = $recipe->image;
    
    $prep_time_standard = buymeapie_format_ISO($prep_time);
    $cook_time_standard = buymeapie_format_ISO($cook_time);
    $total_time_standard = buymeapie_format_ISO($total_time);

	// get styles
	$theme = buymeapie_recipe_get_style();

	$showSpacer = $theme->background->innerBorder->style !== "none" || ((!empty($prep_time) || !empty($cook_time) || !empty($total_time)) && $theme->layout->stats !== FALSE && $theme->layout->style !== "blog-buymeapie-stat-focus");
	$showPicture = $theme->layout->picture !== FALSE;
	$showSummary = $theme->layout->description !== FALSE;
	$showStats = $theme->layout->stats !== FALSE;
	$showNutrition = $theme->layout->nutrition !== FALSE;
	$showImportButton = true;
	$showPrint = $theme->layout->print !== FALSE;
    $showSectionHeaders = $theme->layout->sectionHeaders !== FALSE;
    $showBrand = isset($theme->layout->brand) && $theme->layout->brand !== FALSE;
    $showCondensed = isset($theme->layout->condensed) && $theme->layout->condensed !== FALSE;
    $showNumberedIngredients = isset($theme->layout->numberedIngredients) && $theme->layout->numberedIngredients !== FALSE;
    $showNumberedMethods = isset($theme->layout->numberedMethods) && $theme->layout->numberedMethods !== FALSE;
    $showNumberedNotes = isset($theme->layout->numberedNotes) && $theme->layout->numberedNotes !== FALSE;
    
    $condensed = $showCondensed ? "blog-buymeapie-condensed" : "";
    $numberedIngredients = $showNumberedIngredients ? "blog-buymeapie-numbered-ingredients" : "";
    $numberedMethods = $showNumberedMethods ? "blog-buymeapie-numbered-methods" : "";
    $numberedNotes = $showNumberedNotes ? "blog-buymeapie-numbered-notes" : "";

	ob_start();

echo <<<HTML
    <div class="blog-buymeapie-recipe {$theme->layout->style} {$condensed} {$numberedIngredients} {$numberedMethods} {$numberedNotes}" itemscope itemtype="http://schema.org/Recipe">
HTML;

    if (!empty($image)) {
echo <<<HTML
    <img class="blog-buymeapie-google-image" src="$image" style="display:block;position:absolute;left:-10000px;top:-10000px;" itemprop="image" />
HTML;
    }
    
	if (!empty($image) && $showPicture) {
echo <<<HTML
		<div class="blog-buymeapie-photo-top" style="background-image: url($image)"></div>
HTML;
	}

echo <<<HTML
	<div class="blog-buymeapie-recipe-title" itemprop="name">$title</div>
HTML;

	if (!empty($created)) {
echo <<<HTML
	<div class="blog-buymeapie-recipe-published" itemprop="datePublished">$created</div>
HTML;
	}

	if (!empty($image) && $showPicture) {
echo <<<HTML
		<img class="blog-buymeapie-photo-top-large" src="$image" />
HTML;
	}

    $serves_text = __("Serves", "buymeapie-recipe");
    $yields_text = __("Yields", "buymeapie-recipe");

    if ($showStats && !empty($serves)) {
echo <<<HTML
    <div class="blog-buymeapie-serves">$serves_text $serves</div>
HTML;
    } else if ($showStats && !empty($yields)) {
echo <<<HTML
    <div class="blog-buymeapie-serves">$yields_text <span itemprop="recipeYield">$yields</span></div>
HTML;
    }
        
    if (!empty($summary) && $showSummary) {
echo <<<HTML
    <div class="blog-buymeapie-recipe-summary" itemprop="description">$summary</div>
HTML;
	}

    echo <<<HTML
	<div class="blog-buymeapie-header">
HTML;

echo <<<HTML
	</div>
HTML;

	if ($showSpacer) {
echo <<<HTML
	<div class="blog-buymeapie-spacer"></div>
HTML;
	}

	$prep_time_text = __("Prep Time", "buymeapie-recipe");
	$cook_time_text = __("Cook Time", "buymeapie-recipe");
	$total_time_text = __("Total Time", "buymeapie-recipe");

	if ($showStats && (!empty($prep_time) || !empty($cook_time) || !empty($total_time))) {
echo <<<HTML
	<div class="blog-buymeapie-info-bar">
HTML;

	if (!empty($prep_time)) {
echo <<<HTML
		<div class="blog-buymeapie-infobar-section">
			<div class="blog-buymeapie-infobar-section-title">$prep_time_text</div>
			<div class="blog-buymeapie-infobar-section-data" itemprop="prepTime" content="$prep_time_standard">$prep_time <span class="value-title" title="$prep_time_standard"></span></div>
		</div>
HTML;
	}

	if (!empty($cook_time)) {
echo <<<HTML
		<div class="blog-buymeapie-infobar-section">
			<div class="blog-buymeapie-infobar-section-title">$cook_time_text</div>
			<div class="blog-buymeapie-infobar-section-data" itemprop="cookTime" content="$cook_time_standard">$cook_time <span class="value-title" title="$cook_time_standard"></span></div>
		</div>
HTML;
	}

	if (!empty($total_time)) {
echo <<<HTML
		<div class="blog-buymeapie-infobar-section">
			<div class="blog-buymeapie-infobar-section-title">$total_time_text</div>
			<div class="blog-buymeapie-infobar-section-data" itemprop="totalTime" content="$total_time_standard">$total_time <span class="value-title" title="$total_time_standard"></span></div>
		</div>
HTML;
	}

echo <<<HTML
	</div>
HTML;

	}


echo <<<HTML
	<div class="blog-buymeapie-recipe-contents">
HTML;

	if (!empty($image) && $showPicture) {
echo <<<HTML
		<div class="blog-buymeapie-photo-middle" style="background-image: url($image)"></div>
HTML;
	}

	if ($showStats && (!empty($prep_time) || !empty($cook_time) || !empty($total_time))) {
echo <<<HTML
		<div class="blog-buymeapie-info-box">
HTML;

	if (!empty($prep_time)) {
echo <<<HTML
		<div class="blog-buymeapie-infobox-section">
			<div class="blog-buymeapie-infobox-section-title">$prep_time_text</div>
			<div class="duration blog-buymeapie-infobox-section-data" itemprop="prepTime" content="$prep_time_standard">$prep_time <span class="value-title" title="$prep_time_standard"></span></div>
		</div>
HTML;
	}

	if (!empty($cook_time)) {
echo <<<HTML
		<div class="blog-buymeapie-infobox-section">
			<div class="blog-buymeapie-infobox-section-title">$cook_time_text</div>
			<div class="duration blog-buymeapie-infobox-section-data" itemprop="cookTime" content="$cook_time_standard">$cook_time <span class="value-title" title="$cook_time_standard"></span></div>
		</div>
HTML;
	}

	if (!empty($total_time)) {
echo <<<HTML
		<div class="blog-buymeapie-infobox-section">
			<div class="blog-buymeapie-infobox-section-title">$total_time_text</div>
			<div class="duration blog-buymeapie-infobox-section-data" itemprop="totalTime" content="$total_time_standard">$total_time <span class="value-title" title="$total_time_standard"></span></div>
		</div>
HTML;
	}

echo <<<HTML
	</div>
HTML;
	}

	if ($showNutrition) {

		if ($type === "Servings" || $type === "Yields") {
echo <<<HTML
			<div class='blog-buymeapie-nutrition-item blog-buymeapie-nutrition-servings'>
				<div class='blog-buymeapie-nutrition-left'>$type</div>
				<div class='blog-buymeapie-nutrition-right'>$unit</div>
			</div>
HTML;
		}
	}

	$label_ingredient_text = __("Ingredients", "buymeapie-recipe");
	$label_instruction_text = __("Instructions", "buymeapie-recipe");
	$label_note_text = __("Notes", "buymeapie-recipe");

	$section_index = 0;
	$first = TRUE;
	if (!empty($recipe->ingredients)) {
		foreach ($recipe->ingredients as $section) {
echo <<<HTML
		<div class="blog-buymeapie-ingredient-section" buymeapiesection="$section_index">
HTML;
		if (!empty($section->title) && $showSectionHeaders) {
echo <<<HTML
			<div class="blog-buymeapie-subheader">$section->title</div>
HTML;
		} else if ($first && $showSectionHeaders) {
echo <<<HTML
                <div class="blog-buymeapie-subheader">$label_ingredient_text</div>
HTML;
		}
            
echo <<<HTML
			<ol class='blog-buymeapie-ingredients'>
HTML;

		$line_index = 0;
		if (!empty($section->lines)) {
			foreach ($section->lines as $line) {
				$line = str_replace('|', ' ', $line);
echo <<<HTML
				<li class="blog-buymeapie-ingredient-item" buymeapieitem="$line_index" itemprop="ingredients">$line</li>
HTML;
			$line_index++;
			}
		}

echo <<<HTML
			</ol>
HTML;
		$section_index++;
		$first = FALSE;
		}
	}



if ($showImportButton && !empty($recipe->ingredients)) {
	$import_recipe_button_text = __("Add ingredients to shopping list", "buymeapie-recipe");
	$import_recipe_text = __("If you don’t have Buy Me a Pie! app installed you’ll see the list with ingredients right after downloading it", "buymeapie-recipe");
?>	

	<div style="clear:both"></div>
	<div class="blog-buymeapie-import blog-buymeapie-action" id="buymeapie_recipe_import_<?php echo $recipeId ?>"><div></div></div>
	<div class="blog-buymeapie-import-text"><?php echo $import_recipe_text ?></div>
<?php

	foreach ($recipe->ingredients as &$section) {
		if (!empty($section->lines)) {
			foreach ($section->lines as &$line) {
				$_item = explode('|', $line);
				$items[] = array('name' => addcslashes($_item[0], '"'), 'amount' => isset($_item[1]) ? addcslashes($_item[1], '"') : '');
			}
		}
	}

	$export_recipe_data .= '{"lists":[{"name": "' . trim(html_entity_decode($title, ENT_QUOTES, "windows-1251")) . '","active":true,"items":[';
	foreach ($items as $item) {
		$item_format[] = '{"name":"'.trim(html_entity_decode($item['name'], ENT_QUOTES, "windows-1251")).'","amount":"'.trim(html_entity_decode($item['amount'], ENT_QUOTES, "windows-1251")).'","group":0}';
	}
	$export_recipe_data .= join(',', $item_format);
	$export_recipe_data .= ']}]}';
	$branch_key = 'key_live_blf8cbCOBIE1a7eFuCgqFfhdvDpODoZa';
	$type = is_single() ? 'post' : 'reel';
?>
<script type="text/javascript">
	var buymeapie_export_recipe_link = '';
    (function(b,r,a,n,c,h,_,s,d,k){if(!b[n]||!b[n]._q){for(;s<_.length;)c(h,_[s++]);d=r.createElement(a);d.async=1;d.src="https://cdn.branch.io/branch-latest.min.js";k=r.getElementsByTagName(a)[0];k.parentNode.insertBefore(d,k);b[n]=h}})(window,document,"script","branch",function(b,r){b[r]=function(){b._q.push([r,arguments])}},{_q:[],_v:1},"addListener applyCode banner closeBanner creditHistory credits data deepview deepviewCta first getCode init link logout redeem referrals removeListener sendSMS setIdentity track validateCode".split(" "), 0);
    branch.init('<?php echo $branch_key ?>');
    branch.link({
	    campaign: 'WP plugin',
	    channel: 'plugin',
	    tags: ['import', 'plugin', '<?php echo $_SERVER['HTTP_HOST'] ?>'],
	    feature: 'import',
        stage: '',
        data: <?php echo $export_recipe_data ?>
    }, function(err, link) {
   		jQuery('#buymeapie_recipe_import_<?php echo $recipeId ?>')
   			.html('<'+'a href="'+(jQuery.isMobile ? link : '')+'"><div><?php echo $import_recipe_button_text ?>'+'</'+'div></a>')
   			.data('buymeapie_export_recipe_link',link).attr('data-buymeapie_export_recipe_link',link)
   			.css('display','block');
  	});
</script>
</div>
<?php
}

	$first = TRUE;
	if (!empty($recipe->directions)) {
		foreach ($recipe->directions as $section) {
echo <<<HTML
		<div class="blog-buymeapie-method-section" buymeapiesection="$section_index">
HTML;
		if (!empty($section->title) && $showSectionHeaders) {
echo <<<HTML
			<div class="blog-buymeapie-subheader">$section->title</div>
HTML;
		} else if ($first && $showSectionHeaders) {
echo <<<HTML
			<div class="blog-buymeapie-subheader">$label_instruction_text</div>
HTML;
		}
            
echo <<<HTML
			<ol class="blog-buymeapie-methods" itemprop="recipeInstructions">
HTML;

		$line_index = 0;
		if (!empty($section->lines)) {
			foreach ($section->lines as $line) {
echo <<<HTML
				<li class="blog-buymeapie-method-item" buymeapieitem="$line_index">$line</li>
HTML;
			$line_index++;
			}
		}

echo <<<HTML
			</ol>
		</div>
HTML;
		$section_index++;
		$first = FALSE;
		}
	}

	$first = TRUE;
	if (!empty($recipe->notes)) {
		foreach ($recipe->notes as $section) {
echo <<<HTML
		<div class="blog-buymeapie-note-section" buymeapiesection="$section_index">
HTML;
		if (!empty($section->title) && $showSectionHeaders) {
echo <<<HTML
			<div class="blog-buymeapie-subheader">$section->title</div>
HTML;
		} else if ($first && $showSectionHeaders) {
echo <<<HTML
			<div class="blog-buymeapie-subheader">$label_note_text</div>
HTML;
		}

echo <<<HTML
			<ol class='blog-buymeapie-notes'>
HTML;

		$line_index = 0;
		if (!empty($section->lines)) {
			foreach ($section->lines as $line) {
echo <<<HTML
				<li class="blog-buymeapie-note-item" buymeapieitem="$line_index">$line</li>
HTML;
			$line_index++;
			}
		}

echo <<<HTML
			</ol>
		</div>
HTML;
		$section_index++;
		$first = FALSE;
		}
	}

if ($showNutrition && $calories && $total_fat && $protein && $total_carbohydrates) {
	$label_macro_calories_text = __("Calories", "buymeapie-recipe");
    $label_macro_cal_text = __("cal", "buymeapie-recipe");
    $label_macro_fat_text = __("Fat", "buymeapie-recipe");
    $label_macro_protein_text = __("Protein", "buymeapie-recipe");
    $label_macro_carbs_text = __("Carbs", "buymeapie-recipe");
    $nutrition_fact_text = __("Nutrition Facts", "buymeapie-recipe");
    $label_grams_text = __("g", "buymeapie-recipe");

echo <<<HTML
		<!--div class="blog-buymeapie-subheader">$nutrition_fact_text</div-->
		<div class="blog-buymeapie-nutrition-bar" itemprop="nutrition" itemscope itemtype="http://schema.org/NutritionInformation">
            <div class="blog-buymeapie-nutrition-section">
                <div class="blog-buymeapie-nutrition-section-title">$label_macro_calories_text</div>
                <div class="blog-buymeapie-nutrition-section-data" itemprop="calories">{$calories} {$label_macro_cal_text}</div>
            </div>
            <div class="blog-buymeapie-nutrition-section">
                <div class="blog-buymeapie-nutrition-section-title">$label_macro_fat_text</div>
                <div class="blog-buymeapie-nutrition-section-data" itemprop="fatContent">{$total_fat} {$label_grams_text}</div>
            </div>
            <div class="blog-buymeapie-nutrition-section">
                <div class="blog-buymeapie-nutrition-section-title">$label_macro_protein_text</div>
                <div class="blog-buymeapie-nutrition-section-data" itemprop="proteinContent">{$protein} {$label_grams_text}</div>
            </div>
            <div class="blog-buymeapie-nutrition-section">
                <div class="blog-buymeapie-nutrition-section-title">$label_macro_carbs_text</div>
                <div class="blog-buymeapie-nutrition-section-data" itemprop="carbohydrateContent">{$total_carbohydrates} {$label_grams_text}</div>
            </div>
        </div>
        <div class="blog-buymeapie-nutrition-border"></div>
HTML;
	}


	$print_text = __("Print", "buymeapie-recipe");

	if ($showPrint) {
echo <<<HTML
		<div class="blog-buymeapie-print blog-buymeapie-action">$print_text</div>
HTML;
	}



	$label_by_text = __("By", "buymeapie-recipe");

    if (!empty($author)) {
echo <<<HTML
    <div class="author blog-buymeapie-author" itemprop="author">$label_by_text $author</div>
HTML;
	}

    $adapted_text = __("Adapted from", "buymeapie-recipe");

	if (!empty($adapted)) {
echo <<<HTML
    <div class="blog-buymeapie-adapted">
    $adapted_text
HTML;

    if (!empty($adapted_link)) {
echo <<<HTML
    <a class="blog-buymeapie-adapted-link" href="$adapted_link">
HTML;
    }

echo <<<HTML
    $adapted
HTML;

    if (!empty($adapted_link)) {
echo <<<HTML
    </a>
HTML;
    }
echo <<<HTML
    </div>
HTML;
    }


	$label_adapted_text = __("Adapted from", "buymeapie-recipe");

    if (!empty($adapted)) {
echo <<<HTML
        <div class="blog-buymeapie-adapted-print">
        $label_adapted_text $adapted
        </div>
HTML;
    }
    
    $blog_name = get_bloginfo("name");
    $blog_url = network_site_url("/");
echo <<<HTML
    <div class="blog-buymeapie-recipe-source">{$blog_name} {$blog_url}</div>
HTML;

	$label_wordpress_text = __("Wordpress Recipe Plugin", "buymeapie-recipe");
	$label_by_small_text = __("by", "buymeapie-recipe");

    if ($showBrand) {
echo <<<HTML
	<div class="blog-buymeapie-brand"><a href="https://wordpress.org/plugins/recipe-schema-markup/">$label_wordpress_text</a> $label_by_small_text <a href="{$buymeapie_host}">Buy Me a Pie!</a></div>
HTML;
	}

echo <<<HTML
		</div>
	</div>
HTML;

	$result = ob_get_contents();
	ob_end_clean();

	return $result;
}

add_action('wp_head', 'buymeapie_recipe_wp_head');

function buymeapie_recipe_wp_head() {
	global $buymeapie_directory;

	$theme = buymeapie_recipe_get_style();

	$header_font = buymeapie_recipe_create_font($theme->font->header);
	$subheader_font = buymeapie_recipe_create_font($theme->font->subheader);
	$body_font = buymeapie_recipe_create_font($theme->font->body);
	$info_font = buymeapie_recipe_create_font($theme->font->info);
	$button_font = buymeapie_recipe_create_font($theme->font->button);

	$showReviews = $theme->layout->reviews !== FALSE;

	$fonts = array();

	if (!$header_font->websafe) {
		$fonts[] = $header_font->family;
	}
	if (!$subheader_font->websafe) {
		$fonts[] = $subheader_font->family;
	}
	if (!$body_font->websafe) {
		$fonts[] = $body_font->family;
	}
	if (!$info_font->websafe) {
		$fonts[] = $info_font->family;
	}
	if (!$button_font->websafe) {
		$fonts[] = $button_font->family;
	}

	$families = str_replace(" ", "+", join("|", array_unique($fonts)));

	$ajaxurl = admin_url('admin-ajax.php');
	$blogurl = network_site_url("/");

echo <<<HTML
<script type="text/javascript">
	window.buymeapieRecipePlugin = "{$buymeapie_directory}";
	window.buymeapieRecipeAjaxUrl = "{$ajaxurl}";
	window.buymeapieRecipeUrl = "{$blogurl}";
</script>
HTML;
    
echo <<<HTML
<!--[if lte IE 8]>
<script type="text/javascript">
    window.buymeapieRecipeDisabled = true;
</script>
<![endif]-->
<style type="text/css">
HTML;

	if (!empty($families)) {
echo <<<HTML
	@import url(http://fonts.googleapis.com/css?family={$families});
HTML;
	}

	$contentsBorderColor = $theme->background->innerBorder->color;
	$contentsBorderStyle = $theme->background->innerBorder->style;
	$contentsBorderWidth = $theme->background->innerBorder->width;
	if ($contentsBorderStyle === "none") {
		$contentsBorderStyle = "solid";
		$contentsBorderColor = "transparent";
		$contentsBorderWidth = 1;
	}

echo <<<HTML
    .blog-buymeapie-recipe .blog-buymeapie-recipe-title {
    	color: {$theme->color->title};
    }
    .blog-buymeapie-recipe .blog-buymeapie-subheader, .blog-buymeapie-recipe .blog-buymeapie-infobar-section-title, .blog-buymeapie-recipe .blog-buymeapie-infobox-section-title, .blog-buymeapie-nutrition-section-title {
        color: {$theme->color->subheader};
    }

    .blog-buymeapie-recipe .blog-buymeapie-infobar-section-data, .blog-buymeapie-recipe .blog-buymeapie-infobox-section-data, .blog-buymeapie-recipe .blog-buymeapie-adapted, .blog-buymeapie-recipe .blog-buymeapie-import-text, .blog-buymeapie-recipe .blog-buymeapie-author, .blog-buymeapie-recipe .blog-buymeapie-serves, .blog-buymeapie-nutrition-section-data {
        color: {$theme->color->stat};
    }
    .blog-buymeapie-recipe .blog-buymeapie-recipe-summary, .blog-buymeapie-recipe .blog-buymeapie-ingredient-item, .blog-buymeapie-recipe .blog-buymeapie-method-item, .blog-buymeapie-recipe .blog-buymeapie-note-item, .blog-buymeapie-write-review, .blog-buymeapie-nutrition-box {
        color: {$theme->color->text};
    }

 	.blog-buymeapie-recipe .blog-buymeapie-adapted-link {
        color: {$theme->color->save};
    }

    .blog-buymeapie-recipe .blog-buymeapie-adapted-link:hover {
        color: {$theme->color->saveHighlight};
    }

    .blog-buymeapie-recipe .blog-buymeapie-nutrition-bar:hover .blog-buymeapie-nutrition-section-title {
        color: {$theme->color->subheaderHighlight};
    }
   
    .blog-buymeapie-recipe .blog-buymeapie-print {
    	background-color: {$theme->color->print};
    	color: {$theme->color->printText};
    }
    .blog-buymeapie-recipe .blog-buymeapie-print:hover {
    	background-color: {$theme->color->printHighlight};
    }

    .blog-buymeapie-recipe .blog-buymeapie-import {
    	background-color: {$theme->color->import};
    	color: {$theme->color->importText};
    }
    .blog-buymeapie-recipe .blog-buymeapie-import:hover {
    	background-color: {$theme->color->importHighlight};
    }

    .blog-buymeapie-recipe {
    	background-color: {$theme->background->background};
    	border-color: {$theme->background->border->color};
    	border-style: {$theme->background->border->style};
    	border-width: {$theme->background->border->width}px;
    	border-radius: {$theme->background->border->corner}px;
    }
    .blog-buymeapie-recipe .blog-buymeapie-recipe-contents {
    	border-top-color: {$contentsBorderColor};
    	border-top-width: {$contentsBorderWidth}px;
    	border-top-style: {$contentsBorderStyle};
    }
    .blog-buymeapie-recipe .blog-buymeapie-info-bar, .blog-buymeapie-recipe .blog-buymeapie-nutrition-bar, .blog-buymeapie-nutrition-border {
    	border-top-color: {$theme->background->innerBorder->color};
    	border-top-width: {$theme->background->innerBorder->width}px;
    	border-top-style: {$theme->background->innerBorder->style};
    }
    .blog-buymeapie-nutrition-line, .blog-buymeapie-nutrition-thick-line, .blog-buymeapie-nutrition-very-thick-line {
    	border-top-color: {$theme->background->innerBorder->color};
    }
    .blog-buymeapie-recipe .blog-buymeapie-info-box, .blog-buymeapie-nutrition-box {
    	background-color: {$theme->background->box};
    	border-color: {$theme->background->boxBorder->color};
    	border-style: {$theme->background->boxBorder->style};
    	border-width: {$theme->background->boxBorder->width}px;
    	border-radius: {$theme->background->boxBorder->corner}px;
    }
    .blog-buymeapie-recipe .blog-buymeapie-recipe-title {
		font-family: {$header_font->family}, Helvetica Neue, Helvetica, Tahoma, Sans Serif, Sans;
		font-size: {$header_font->size};
		font-weight: {$header_font->weight};
		font-style: {$header_font->style};
		text-transform: {$header_font->transform};
		text-decoration: {$header_font->decoration};
    }
    .blog-buymeapie-recipe .blog-buymeapie-subheader {
		font-family: {$subheader_font->family}, Helvetica Neue, Helvetica, Tahoma, Sans Serif, Sans;
		font-size: {$subheader_font->size};
		font-weight: {$subheader_font->weight};
		font-style: {$subheader_font->style};
		text-transform: {$subheader_font->transform};
		text-decoration: {$subheader_font->decoration};
    }
    .blog-buymeapie-recipe .blog-buymeapie-recipe-summary, .blog-buymeapie-recipe .blog-buymeapie-ingredients, .blog-buymeapie-recipe .blog-buymeapie-methods, .blog-buymeapie-recipe .blog-buymeapie-notes, .blog-buymeapie-write-review, .blog-buymeapie-nutrition-box {
		font-family: {$body_font->family}, Helvetica Neue, Helvetica, Tahoma, Sans Serif, Sans;
		font-size: {$body_font->size};
		font-weight: {$body_font->weight};
		font-style: {$body_font->style};
		text-transform: {$body_font->transform};
		text-decoration: {$body_font->decoration};
    }
    .blog-buymeapie-recipe .blog-buymeapie-info-bar, .blog-buymeapie-recipe .blog-buymeapie-info-box, .blog-buymeapie-recipe .blog-buymeapie-adapted, .blog-buymeapie-recipe .blog-buymeapie-import-text, .blog-buymeapie-recipe .blog-buymeapie-author, .blog-buymeapie-recipe .blog-buymeapie-serves, .blog-buymeapie-recipe .blog-buymeapie-infobar-section-title, .blog-buymeapie-recipe .blog-buymeapie-infobox-section-title,.blog-buymeapie-recipe .blog-buymeapie-nutrition-bar, .blog-buymeapie-nutrition-section-title, .blog-buymeapie-nutrition-more {
		font-family: {$info_font->family}, Helvetica Neue, Helvetica, Tahoma, Sans Serif, Sans;
		font-size: {$info_font->size};
		font-weight: {$info_font->weight};
		font-style: {$info_font->style};
		text-transform: {$info_font->transform};
		text-decoration: {$info_font->decoration};
    }
    .blog-buymeapie-recipe .blog-buymeapie-action {
		font-family: {$button_font->family}, Helvetica Neue, Helvetica, Tahoma, Sans Serif, Sans;
		font-size: {$button_font->size};
		font-weight: {$button_font->weight};
		font-style: {$button_font->style};
		text-transform: {$button_font->transform};
		text-decoration: {$button_font->decoration};
    }
HTML;
         
echo <<<HTML
    </style>
HTML;
                
}
                
                
function buymeapie_recipe_shortcode($atts, $content = null) {
	extract(shortcode_atts(array(
		"id" => "-1"
	), $atts));

	return buymeapie_recipe_render_recipe($id);
}

add_shortcode('buymeapie-recipe', 'buymeapie_recipe_shortcode');
add_shortcode('yumprint-recipe',  'buymeapie_recipe_shortcode');

function buymeapie_recipe_add_admin_scripts($hook) {
	wp_enqueue_script('jquery');
	wp_enqueue_style('wp-pointer');
	wp_enqueue_script('wp-pointer');
}

function buymeapie_recipe_print_footer_scripts() {
	$show_editor_prompt = get_option('buymeapie_recipe_prompt_editor');
	$show_post_prompt = get_option('buymeapie_recipe_prompt_post');
	$show_theme_prompt = get_option('buymeapie_recipe_prompt_theme');

echo <<<HTML
	<script type='text/javascript'>
		jQuery(function () {
			if (!jQuery("body").pointer) {
				return;
			}
			if ("{$show_editor_prompt}" !== "true" && (/post\.php/.test(window.location.pathname) || /post-new\.php/.test(window.location.pathname))) {
				var f = function () {
					if (!jQuery("#content_buymeapieRecipe").length) {
						setTimeout(f, 500);
					} else {
						jQuery('#content_buymeapieRecipe').pointer({
							content: "<h3>Recipe Schema Markup</h3><p>Click the Recipe Schema Markup icon to insert a recipe</p>",
							position: {
								edge: 'bottom',
								align: 'left',
								offset: '-50 -10'
							}
						}).pointer('open');
						jQuery.post(ajaxurl, { action: 'buymeapie_recipe_prompt', data: 'editor' });
					}
				};
				f();
			} else if ("{$show_post_prompt}" !== "true" && /buymeapie_recipe_themes/.test(window.location.search)) {
				jQuery("#menu-posts").pointer({
					content: "<h3>Recipe Schema Markup</h3><p>Click here to create a recipe once you have chosen a template</p>",
					position: 'top'
				}).pointer('open');
				jQuery.post(ajaxurl, { action: 'buymeapie_recipe_prompt', data: 'post' });
			} else if ("{$show_theme_prompt}" !== "true") {
				jQuery("#toplevel_page_buymeapie_recipe_themes").pointer({
					content: "<h3>Recipe Schema Markup</h3><p>Click here to create your recipe template</p>",
					position: 'top'
				}).pointer('open');
				jQuery.post(ajaxurl, { action: 'buymeapie_recipe_prompt', data: 'theme' });
			}
		});
	</script>
HTML;
}

add_action('admin_enqueue_scripts', 'buymeapie_recipe_add_admin_scripts');
add_action('admin_print_footer_scripts', 'buymeapie_recipe_print_footer_scripts');

?>