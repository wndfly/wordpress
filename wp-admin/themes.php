<?php
/**
 * Themes administration panel.
 *
 * @package WordPress
 * @subpackage Administration
 */

/** WordPress Administration Bootstrap */
require_once('./admin.php');

require_once( './includes/default-list-tables.php' );

$wp_list_table = new WP_Themes_Table;
$wp_list_table->check_permissions();

if ( current_user_can('switch_themes') && isset($_GET['action']) ) {
	if ( 'activate' == $_GET['action'] ) {
		check_admin_referer('switch-theme_' . $_GET['template']);
		switch_theme($_GET['template'], $_GET['stylesheet']);
		wp_redirect('themes.php?activated=true');
		exit;
	} else if ( 'delete' == $_GET['action'] ) {
		check_admin_referer('delete-theme_' . $_GET['template']);
		if ( !current_user_can('delete_themes') )
			wp_die( __( 'Cheatin&#8217; uh?' ) );
		delete_theme($_GET['template']);
		wp_redirect('themes.php?deleted=true');
		exit;
	}
}

$wp_list_table->prepare_items();

$title = __('Manage Themes');
$parent_file = 'themes.php';

if ( current_user_can( 'switch_themes' ) ) :

$help = '<p>' . __('Aside from the default theme included with your WordPress installation, themes are designed and developed by third parties.') . '</p>';
$help .= '<p>' . __('You can see your active theme at the top of the screen. Below are the other themes you have installed that are not currently in use. You can see what your site would look like with one of these themes by clicking the Preview link. To change themes, click the Activate link.') . '</p>';
if ( current_user_can('install_themes') )
	$help .= '<p>' . sprintf(__('If you would like to see more themes to choose from, click on the &#8220;Install Themes&#8221; tab and you will be able to browse or search for additional themes from the <a href="%s" target="_blank">WordPress.org Theme Directory</a>. Themes in the WordPress.org Theme Directory are designed and developed by third parties, and are licensed under the GNU General Public License, version 2, just like WordPress. Oh, and they&#8217;re free!'), 'http://wordpress.org/extend/themes/') . '</p>';

$help .= '<p><strong>' . __('For more information:') . '</strong></p>';
$help .= '<p>' . __('<a href="http://codex.wordpress.org/Using_Themes" target="_blank">Documentation on Using Themes</a>') . '</p>';
$help .= '<p>' . __('<a href="http://wordpress.org/support/" target="_blank">Support Forums</a>') . '</p>';
add_contextual_help($current_screen, $help);

add_thickbox();
wp_enqueue_script( 'theme-preview' );

endif;

require_once('./admin-header.php');
if ( is_multisite() && current_user_can('edit_themes') ) {
	?><div id="message0" class="updated"><p><?php printf( __('Administrator: new themes must be activated in the <a href="%s">Network Themes</a> screen before they appear here.'), admin_url( 'ms-themes.php') ); ?></p></div><?php
}
?>

<?php if ( ! validate_current_theme() ) : ?>
<div id="message1" class="updated"><p><?php _e('The active theme is broken.  Reverting to the default theme.'); ?></p></div>
<?php elseif ( isset($_GET['activated']) ) :
		if ( isset($wp_registered_sidebars) && count( (array) $wp_registered_sidebars ) && current_user_can('edit_theme_options') ) { ?>
<div id="message2" class="updated"><p><?php printf( __('New theme activated. This theme supports widgets, please visit the <a href="%s">widgets settings</a> screen to configure them.'), admin_url( 'widgets.php' ) ); ?></p></div><?php
		} else { ?>
<div id="message2" class="updated"><p><?php printf( __( 'New theme activated. <a href="%s">Visit site</a>' ), home_url( '/' ) ); ?></p></div><?php
		}
	elseif ( isset($_GET['deleted']) ) : ?>
<div id="message3" class="updated"><p><?php _e('Theme deleted.') ?></p></div>
<?php endif; ?>

<div class="wrap">
<?php screen_icon(); ?>
<h2><a href="themes.php" class="nav-tab nav-tab-active"><?php echo esc_html( $title ); ?></a><?php if ( current_user_can('install_themes') ) { ?><a href="theme-install.php" class="nav-tab"><?php echo esc_html_x('Install Themes', 'theme'); ?></a><?php } ?></h2>

<h3><?php _e('Current Theme'); ?></h3>
<div id="current-theme">
<?php if ( $ct->screenshot ) : ?>
<img src="<?php echo $ct->theme_root_uri . '/' . $ct->stylesheet . '/' . $ct->screenshot; ?>" alt="<?php _e('Current theme preview'); ?>" />
<?php endif; ?>
<h4><?php
	/* translators: 1: theme title, 2: theme version, 3: theme author */
	printf(__('%1$s %2$s by %3$s'), $ct->title, $ct->version, $ct->author) ; ?></h4>
<p class="theme-description"><?php echo $ct->description; ?></p>
<?php if ( current_user_can('edit_themes') && $ct->parent_theme ) { ?>
	<p><?php printf(__('The template files are located in <code>%2$s</code>. The stylesheet files are located in <code>%3$s</code>. <strong>%4$s</strong> uses templates from <strong>%5$s</strong>. Changes made to the templates will affect both themes.'), $ct->title, str_replace( WP_CONTENT_DIR, '', $ct->template_dir ), str_replace( WP_CONTENT_DIR, '', $ct->stylesheet_dir ), $ct->title, $ct->parent_theme); ?></p>
<?php } else { ?>
	<p><?php printf(__('All of this theme&#8217;s files are located in <code>%2$s</code>.'), $ct->title, str_replace( WP_CONTENT_DIR, '', $ct->template_dir ), str_replace( WP_CONTENT_DIR, '', $ct->stylesheet_dir ) ); ?></p>
<?php } ?>
<?php if ( $ct->tags ) : ?>
<p><?php _e('Tags:'); ?> <?php echo join(', ', $ct->tags); ?></p>
<?php endif; ?>
<?php theme_update_available($ct); ?>

</div>

<br class="clear">
<?php
if ( ! current_user_can( 'switch_themes' ) ) {
	echo '</div>';
	require( './admin-footer.php' );
	exit;
}
?>
<h3><?php _e('Available Themes'); ?></h3>

<?php $wp_list_table->display(); ?>

<br class="clear" />

<?php
// List broken themes, if any.
$broken_themes = get_broken_themes();
if ( current_user_can('edit_themes') && count( $broken_themes ) ) {
?>

<h2><?php _e('Broken Themes'); ?> <?php if ( is_multisite() ) _e( '(Site admin only)' ); ?></h2>
<p><?php _e('The following themes are installed but incomplete. Themes must have a stylesheet and a template.'); ?></p>

<table id="broken-themes">
	<tr>
		<th><?php _e('Name'); ?></th>
		<th><?php _e('Description'); ?></th>
	</tr>
<?php
	$theme = '';

	$theme_names = array_keys($broken_themes);
	natcasesort($theme_names);

	foreach ($theme_names as $theme_name) {
		$title = $broken_themes[$theme_name]['Title'];
		$description = $broken_themes[$theme_name]['Description'];

		$theme = ('class="alternate"' == $theme) ? '' : 'class="alternate"';
		echo "
		<tr $theme>
			 <td>$title</td>
			 <td>$description</td>
		</tr>";
	}
?>
</table>
<?php
}
?>
</div>

<?php require('./admin-footer.php'); ?>
