<?php
/*
Plugin Name: Intranet Restriction
Description: Allows to restrict posts and pages to intranet.  Adds an extra option to pages and posts in the admin to mark contents restricted.  The intranet can defined by reverse domains and IP ranges.
Version: 0.1
Author: Webdevil
Author URI: http://webdevil.hu/
License: BSD
*/

add_filter('the_posts', 'wd_intranet_filter_posts', 1);
add_filter('get_pages', 'wd_intranet_get_pages', 1);

add_action('edit_post', 'wd_intranet_update');
add_action('save_post', 'wd_intranet_update');
add_action('publish_post', 'wd_intranet_update');
add_action('admin_menu', 'wd_add_custom_box');


/**
 * Filters pages
 */
function wd_intranet_get_pages($pages) {
    if (is_admin() || wd_intranet_is_intranet()) {
        return $pages;
    }
    $filtered = array();
    foreach($pages as $page) {
        $restrict_intranet = get_post_meta($page->ID, 'wd_restrict_intranet', true);
        if (!$restrict_intranet) {
            $filtered[] = $page;
        }
    }
    return $filtered;
}

/**
 * Filters posts
 */
function wd_intranet_filter_posts($posts) {
    if (is_admin() || wd_intranet_is_intranet()) {
        return $posts;
    }
    $filtered = array();
    foreach($posts as $post) {
        $restrict_intranet = get_post_meta($post->ID, 'wd_restrict_intranet', true);
        if (!$restrict_intranet) {
            $filtered[] = $post;
        }
    }
    return $filtered;
}


/**
 * Adds a custom section to admin
 */
function wd_add_custom_box() {
    if (function_exists('add_meta_box')) {
        add_meta_box('wd_intranet', __('Intranet'), 'wd_inner_custom_box', 'page', 'normal', 'high');
        add_meta_box('wd_intranet', __('Intranet'), 'wd_inner_custom_box', 'post', 'normal', 'high');
    }
}


/**
 * Prints the inner fields for the custom post/page section
 */
function wd_inner_custom_box() {
  echo '<input type="hidden" name="wd_intranet_noncename" id="wd_intranet_noncename" value="' . wp_create_nonce( plugin_basename(__FILE__) ) . '" />';

  global $post;
  $restrict_intranet = get_post_meta($post->ID, 'wd_restrict_intranet', true);
?>
  <div class="inside">
       <?php if ($restrict_intranet): ?>
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
function wd_intranet_update($id) {
    // verify this came from the our screen and with proper authorization,
    // because save_post can be triggered at other times
    if (!wp_verify_nonce( $_POST['wd_intranet_noncename'], plugin_basename(__FILE__) )) {
        return $post_id;
    }

    if ('page' == $_POST['post_type'] or 'post' == $_POST['post_type']) {
    	if (!current_user_can( 'edit_page', $post_id )) {
            return $post_id;
        }
    } else {
        return $post_id;
    }

    $value = (int)$_POST['restrict_intranet'];
    if (!$value) {
        $value = 0;
    }
    update_post_meta($id, 'wd_restrict_intranet', $value);

}


function wd_intranet_match_network($nets, $ip, $first=false) {
   $return = false;
   if (!is_array ($nets)) $nets = array ($nets);

   foreach ($nets as $net) {
       $rev = (preg_match ("/^\!/", $net)) ? true : false;
       $net = preg_replace ("/^\!/", "", $net);

       $ip_arr  = explode('/', $net);
       $net_long = ip2long($ip_arr[0]);
       $x        = ip2long($ip_arr[1]);
       $mask    = long2ip($x) == $ip_arr[1] ? $x : 0xffffffff << (32 - $ip_arr[1]);
       $ip_long  = ip2long($ip);

       if ($rev) {
           if (($ip_long & $mask) == ($net_long & $mask)) return false;
       } else {
           if (($ip_long & $mask) == ($net_long & $mask)) $return = true;
           if ($first && $return) return true;
       }
   }
   return $return;
}


/**
 * Returns true if client is from intranet
 */
function wd_intranet_is_intranet() {
    $reverse = gethostbyaddr($_SERVER['REMOTE_ADDR']);
    if (preg_match('/(sote\.hu)|(hupe\.hu)|(usn\.hu)|(popbitch\.hu)$/i', $reverse)) {
        return TRUE;
    }
    $ip_etk = wd_intranet_match_network('195.111.73.0/255.255.255.224', $_SERVER['REMOTE_ADDR']);
    $ip_hupe = wd_intranet_match_network('193.6.214.0/255.255.255.0',$_SERVER['REMOTE_ADDR']);
    $ip_192 = wd_intranet_match_network('192.168.0.0/255.255.0.0',$_SERVER['REMOTE_ADDR']);
    $ip_10 = wd_intranet_match_network('10.0.0.0/255.0.0.0',$_SERVER['REMOTE_ADDR']);
    if ($ip_192 || $ip_10 || $ip_hupe || $ip_etk) {
        return TRUE;
    }
    return FALSE;
}
