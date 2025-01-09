<?php 
	add_action( 'wp_enqueue_scripts', 'grandtour_child_enqueue_styles' );
	function grandtour_child_enqueue_styles() {
		wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' ); 
	} 
?>


<?php
	/** login logo image **/
	add_action( 'login_enqueue_scripts', 'my_login_logo' );
	function my_login_logo() { 
?>
	<style type="text/css">
		#login h1 a, .login h1 a {
			background-image:  url(<?php echo get_stylesheet_directory_uri(); ?>/images/site-login-logo.png); //cargar url logo
			background-repeat: no-repeat;
			background-size: contain;
			width: auto;
		}
		body.login {
			background: #f5f5f5;
		}
	</style>
<?php 
	} 
?>

<?php
	/** login url **/
	add_filter( 'login_headerurl', 'my_login_logo_url' );
	function my_login_logo_url() {
		return home_url();
	}

	/** login title **/
	add_filter( 'login_headertext', 'my_login_logo_title' ); 
	function my_login_logo_title( $headertext ) {
		$headertext = esc_html__( 'WebSite Title', 'text-domain' );
		return $headertext;
	}

	/** Footer in dashboard **/
	add_filter('admin_footer_text', 'remove_footer_admin');
	function remove_footer_admin() {
		echo 'Contact by <a href="mailto:cronoscopios@gmail.com?Subject=Hola%20desde%20adventurestoperu.com" target="_blank">Developer</a> | Documentation: <a href="https://www.youtube.com" target="_blank">video</a></p>';
	}

add_action('admin_footer', 'my_admin_footer_function');
function my_admin_footer_function() {
    echo '<p>' . __( 'This will be inserted at the bottom of admin page', 'textdomain' ) . '</p>';
}


?>

<?php
	/**  Deshabilita fuentes RSS **/
	add_action('do_feed', 'fb_disable_feed', 1);
	add_action('do_feed_rdf', 'fb_disable_feed', 1);
	add_action('do_feed_rss', 'fb_disable_feed', 1);
	add_action('do_feed_rss2', 'fb_disable_feed', 1);
	add_action('do_feed_atom', 'fb_disable_feed', 1);

	function fb_disable_feed() {
		wp_die( __('No feed available, please visit our <a href="'. get_bloginfo('url') .'">homepage</a>!') );
	}
?>

<?php 
    /** Deshabilitar XML-RPC **/
    add_filter('xmlrpc_enabled', '__return_false');


/*
 * Function for post duplication. Dups appear as drafts. User is redirected to the edit screen
 */
function rd_duplicate_post_as_draft(){
  global $wpdb;
  if (! ( isset( $_GET['post']) || isset( $_POST['post'])  || ( isset($_REQUEST['action']) && 'rd_duplicate_post_as_draft' == $_REQUEST['action'] ) ) ) {
    wp_die('No post to duplicate has been supplied!');
  }
 
  /*
   * Nonce verification
   */
  if ( !isset( $_GET['duplicate_nonce'] ) || !wp_verify_nonce( $_GET['duplicate_nonce'], basename( __FILE__ ) ) )
    return;
 
  /*
   * get the original post id
   */
  $post_id = (isset($_GET['post']) ? absint( $_GET['post'] ) : absint( $_POST['post'] ) );
  /*
   * and all the original post data then
   */
  $post = get_post( $post_id );
 
  /*
   * if you don't want current user to be the new post author,
   * then change next couple of lines to this: $new_post_author = $post->post_author;
   */
  $current_user = wp_get_current_user();
  $new_post_author = $current_user->ID;
 
  /*
   * if post data exists, create the post duplicate
   */
  if (isset( $post ) && $post != null) {
 
    /*
     * new post data array
     */
    $args = array(
      'comment_status' => $post->comment_status,
      'ping_status'    => $post->ping_status,
      'post_author'    => $new_post_author,
      'post_content'   => $post->post_content,
      'post_excerpt'   => $post->post_excerpt,
      'post_name'      => $post->post_name,
      'post_parent'    => $post->post_parent,
      'post_password'  => $post->post_password,
      'post_status'    => 'draft',
      'post_title'     => $post->post_title,
      'post_type'      => $post->post_type,
      'to_ping'        => $post->to_ping,
      'menu_order'     => $post->menu_order
    );
 
    /*
     * insert the post by wp_insert_post() function
     */
    $new_post_id = wp_insert_post( $args );
 
    /*
     * get all current post terms ad set them to the new post draft
     */
    $taxonomies = get_object_taxonomies($post->post_type); // returns array of taxonomy names for post type, ex array("category", "post_tag");
    foreach ($taxonomies as $taxonomy) {
      $post_terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'slugs'));
      wp_set_object_terms($new_post_id, $post_terms, $taxonomy, false);
    }
 
    /*
     * duplicate all post meta just in two SQL queries
     */
    $post_meta_infos = $wpdb->get_results("SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=$post_id");
    if (count($post_meta_infos)!=0) {
      $sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
      foreach ($post_meta_infos as $meta_info) {
        $meta_key = $meta_info->meta_key;
        if( $meta_key == '_wp_old_slug' ) continue;
        $meta_value = addslashes($meta_info->meta_value);
        $sql_query_sel[]= "SELECT $new_post_id, '$meta_key', '$meta_value'";
      }
      $sql_query.= implode(" UNION ALL ", $sql_query_sel);
      $wpdb->query($sql_query);
    }
 
 
    /*
     * finally, redirect to the edit post screen for the new draft
     */
    wp_redirect( admin_url( 'post.php?action=edit&post=' . $new_post_id ) );
    exit;
  } else {
    wp_die('Post creation failed, could not find original post: ' . $post_id);
  }
}
add_action( 'admin_action_rd_duplicate_post_as_draft', 'rd_duplicate_post_as_draft' );
 
/*
 * Add the duplicate link to action list for post_row_actions
 */
function rd_duplicate_post_link( $actions, $post ) {
  if (current_user_can('edit_posts')) {
    $actions['duplicate'] = '<a href="' . wp_nonce_url('admin.php?action=rd_duplicate_post_as_draft&post=' . $post->ID, basename(__FILE__), 'duplicate_nonce' ) . '" title="Duplicate this item" rel="permalink">Duplicate</a>';
  }
  return $actions;
}
 
add_filter( 'post_row_actions', 'rd_duplicate_post_link', 10, 2 );


/*
* @snippet       Quitar la opción Márketing de WooCommerce del menú de administración de WordPress
* @author        Oscar Abad Folgueira
*/
add_filter('woocommerce_marketing_menu_items', 'ocultar_menu_marketing_admin');

function ocultar_menu_marketing_admin($marketing_pages){
    return array();
}

/*
* @snippet       Upload SVG
* @author        
*/
function cc_mime_types($mimes) {
	$mimes['svg'] = 'image/svg+xml';
	return $mimes;
}	
add_filter('upload_mimes', 'cc_mime_types');

// Activate WordPress Maintenance Mode
function wp_maintenance_mode() {
if (!current_user_can('edit_themes') || !is_user_logged_in()) {
wp_die('<h1>Under Maintenance</h1><br />We’re hard at work improving our site for you. We’ll be back online shortly. Thanks for bearing with us!');
}
}
add_action('get_header', 'wp_maintenance_mode');

//Custom admin logo
function custom_admin_logo() {
    echo '
    <style type="text/css">
        #wpadminbar #wp-admin-bar-wp-logo > .ab-item .ab-icon {
            background-image: url(' . get_stylesheet_directory_uri() . '/images/custom-admin-logo.png) !important;
            background-size: cover;
        }
    </style>
    ';
}
add_action('admin_head', 'custom_admin_logo');

// submenu remove
function remove_wp_logo_menu() {
    global $wp_admin_bar;
    $wp_admin_bar->remove_menu('about');          // Elimina el enlace 'Acerca de WordPress'
    $wp_admin_bar->remove_menu('wporg');          // Elimina el enlace 'WordPress.org'
    $wp_admin_bar->remove_menu('documentation');  // Elimina el enlace 'Documentación'
    $wp_admin_bar->remove_menu('support-forums'); // Elimina el enlace 'Foros de soporte'
    $wp_admin_bar->remove_menu('feedback');       // Elimina el enlace 'Enviar comentarios'
}
add_action('wp_before_admin_bar_render', 'remove_wp_logo_menu', 0)



?>
