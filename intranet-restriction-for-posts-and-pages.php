<?php
/*
Plugin Name: Intranet Restriction for Posts and Pages
Description: Allows to restrict the access of specific posts and pages to intranet only.  Adds an extra option to pages and posts in the admin panel where contents may be marked as restricted.  Intranet can defined by domain names and IP ranges.
Version: 0.1
Author: Webdevil
Author URI: http://webdevil.hu/
*/

/**
 * @author slink
 */

register_activation_hook( __FILE__,'intranet_restriction_install' );
register_deactivation_hook( __FILE__, 'intranet_restriction_remove' );

add_filter( 'the_posts', 'intranet_restriction_filter_posts', 1 );
add_filter( 'get_pages', 'intranet_restriction_filter_posts', 1 );

add_action( 'edit_post', 'intranet_restriction_update' );
add_action( 'save_post', 'intranet_restriction_update' );
add_action( 'publish_post', 'intranet_restriction_update' );

add_action( 'admin_menu', 'wd_add_custom_box' );
add_action( 'admin_menu', 'intranet_restriction_admin_settings_menu' );


function intranet_restriction_install() {
    add_option( 'intranet_restriction_data', "192.168.0.0/255.255.0.0\n10.0.0.0/255.0.0.0", '', 'yes' );
}


function intranet_restriction_remove() {
    delete_option( 'intranet_restriction_data' );
}


function intranet_restriction_admin_settings_menu() {
    add_options_page(
        __('Intranet Restriction'),
        __('Intranet Restriction'),
        'administrator',
        'intranet-restriction',
        'intranet_restriction_admin_settings_page'
    );
}

function intranet_restriction_admin_settings_page() {
    ?>
    <div class="wrap">
        <h2><?php _e('Intranet Restriction Settings'); ?></h2>
        <p><?php _e('Specify domain names and IP ranges. Put each of them in separate line.'); ?> </p>
        <form method="post" action="options.php">
        <?php wp_nonce_field( 'update-options' ); ?>
        <textarea cols="50" rows="10" name="intranet_restriction_data"><?php echo get_option( 'intranet_restriction_data' ); ?></textarea>
        <input type="hidden" name="action" value="update" />
        <input type="hidden" name="page_options" value="intranet_restriction_data" />
        <br/><input type="submit" value="<?php _e('Save Changes') ?>" />
        </form>
   </div>
   <?php
}

/**
 * Filters posts
 */
function intranet_restriction_filter_posts( $posts ) {
    if ( is_admin() || intranet_restriction_is_intranet() ) {
        return $posts;
    }
    $filtered = array();
    foreach ( $posts as $post ) {
        $restrict_intranet = get_post_meta( $post->ID, 'wd_restrict_intranet', true );
        if  ( ! $restrict_intranet ) {
            $filtered[] = $post;
        }
    }
    return $filtered;
}


/**
 * Adds a custom section to admin
 */
function wd_add_custom_box() {
    if ( function_exists( 'add_meta_box' ) ) {
        add_meta_box( 'wd_intranet', __('Intranet Restriction'), 'wd_inner_custom_box', 'page', 'side', 'default' );
        add_meta_box( 'wd_intranet', __('Intranet Restriction'), 'wd_inner_custom_box', 'post', 'side', 'default' );
    }
}


/**
 * Prints the inner fields for the custom post/page section
 */
function wd_inner_custom_box() {
  echo '<input type="hidden" name="intranet_restriction_noncename" id="intranet_restriction_noncename" value="' . wp_create_nonce( plugin_basename(__FILE__) ) . '" />';

  global $post;
  $restrict_intranet = get_post_meta( $post->ID, 'wd_restrict_intranet', true );
?>
  <div class="inside">
       <?php if ( $restrict_intranet ): ?>
       <input type="checkbox" name="restrict_intranet" value="1" id="restrict_intranet" checked="checked" />
       <?php else: ?>
       <input type="checkbox" name="restrict_intranet" value="1" id="restrict_intranet" />
       <?php endif; ?>
       <label for="restrict_intranet"><?php _e('Restrict to Intranet'); ?></label>
  </div>
<?php
}


/**
 * Saves data
 */
function intranet_restriction_update( $id ) {
    // verify this came from the our screen and with proper authorization,
    // because save_post can be triggered at other times
    if ( ! wp_verify_nonce( $_POST['intranet_restriction_noncename'], plugin_basename(__FILE__) ) ) {
        return $post_id;
    }

    if ( 'page' == $_POST['post_type'] or 'post' == $_POST['post_type'] ) {
    	if ( ! current_user_can( 'edit_page', $post_id ) ) {
            return $post_id;
        }
    } else {
        return $post_id;
    }

    $value = (int)$_POST['restrict_intranet'];
    if ( ! $value ) {
        $value = 0;
    }
    update_post_meta( $id, 'wd_restrict_intranet', $value );

}


function intranet_restriction_match_network( $nets, $ip, $first=false ) {
   $return = false;
   if ( ! is_array ($nets) ) {
       $nets = array ($nets);
   }

   foreach ( $nets as $net ) {
       $rev = ( preg_match( '/^\!/', $net ) ) ? true : false;
       $net = preg_replace( '/^\!/', '', $net );

       $ip_arr  = explode( '/', $net );
       $net_long = ip2long( $ip_arr[0] );
       $x = ip2long( $ip_arr[1] );
       $mask = long2ip( $x ) == $ip_arr[1] ? $x : 0xffffffff << (32 - $ip_arr[1]);
       $ip_long = ip2long( $ip );

       if ( $rev ) {
           if ( ($ip_long & $mask) == ($net_long & $mask) ) return false;
       } else {
           if ( ($ip_long & $mask) == ($net_long & $mask) ) $return = true;
           if ( $first && $return ) return true;
       }
   }
   return $return;
}


/**
 * Returns true if client is from intranet
 */
function intranet_restriction_is_intranet() {
    $settings = intranet_restriction_parse_data();
    // check reverse names
    if ( count( $settings['reverse'] ) > 0 ) {
        $client_reverse = gethostbyaddr( $_SERVER['REMOTE_ADDR'] );
        $reverse_pattern = '/('.implode( ')|(', str_replace('.', '\.', $settings['reverse']) ).')$/i';
        if ( preg_match( $reverse_pattern, $client_reverse) ) {
            return TRUE;
        }
    }
    // check ip ranges
    if ( count( $settings['ipmask'] ) > 0 ) {
        foreach( $settings['ipmask'] as $mask ) {
            if ( intranet_restriction_match_network( $mask, $_SERVER['REMOTE_ADDR'] ) ) {
                return TRUE;
            }
        }
    }
    return FALSE;
}


/**
 * Parses settings data and returns IP mask and reverse list
 */
function intranet_restriction_parse_data() {
    $data = explode( "\n", get_option( 'intranet_restriction_data' ) );
    $ipmask_list = array();
    $reverse_list = array();
    foreach ( $data as $line ) {
        $line = trim( $line );
        if ( is_numeric( $line[0] ) ) {
            $ipmask_list[] = $line;
        } else {
            $reverse_list[] = $line;
        }
    }
    return array(
        'ipmask' => $ipmask_list,
        'reverse' => $reverse_list
    );
}
