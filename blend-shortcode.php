<?php
/*
Plugin Name: Blend Shortcode
Plugin URI: http://wordpress.org/extend/plugins/blend-shortcode/
Description: Converts Blend WordPress shortcodes to a Blend widget. Example: [blend]https://blend.io/project/54611406124aec59070070c4[/blend]
Version: 1.0.0
Author: Blend.io Inc.
Author URI: https://blend.io
License: GPLv2

Original version: Christian Montoya <christian@blend.io>
*/


/* Register oEmbed provider
   -------------------------------------------------------------------------- */

wp_oembed_add_provider('#https?://(?:www\.)?blend\.(?:io|com)/.*#i', 'https://blend.io/oembed', true);


/* Register Blend shortcode
   -------------------------------------------------------------------------- */

add_shortcode("blend", "blend_shortcode");


/**
 * Blend shortcode handler
 * @param  {string|array}  $atts     The attributes passed to the shortcode like [blend attr1="value" /].
 *                                   Is an empty string when no arguments are given.
 * @param  {string}        $content  The content between non-self closing [blend]...[/blend] tags.
 * @return {string}                  Widget embed code HTML
 */
function blend_shortcode($atts, $content = null) {
  
  // get URL 
  $url = trim($content); 
  
  // get default values from options 
  $height = false; 
  $width = false; 
  if(blend_url_is_playlist($url)) { 
    $height = blend_get_option('player_height_multi'); 
    $width = blend_get_option('player_width_multi'); 
  }
  else { 
    $height = blend_get_option('player_height'); 
    $width = blend_get_option('player_width'); 
  }
  $plugin_options = array(); 
  if($height !== false) { 
    $plugin_options['height'] = $height; 
  }
  if($width !== false) { 
    $plugin_options['width'] = $width; 
  }
  
  // get key/value options
  $shortcode_options = is_array($atts) ? $atts : array(); 
  
  if (isset($shortcode_options['params'])) { 
    $shortcode_params = array(); 
    parse_str( html_entity_decode($shortcode_options['params']), $shortcode_params ); 
    unset($shortcode_options['params']); 
    $shortcode_options = array_merge($shortcode_options, $shortcode_params); 
  }
  
  $options = array_merge($plugin_options, $shortcode_options);  

  // Both "width" and "height" need to be integers
  if (isset($options['width']) && !preg_match('/^\d+$/', $options['width'])) { unset($options['width']); }
  if (isset($options['height']) && !preg_match('/^\d+$/', $options['height'])) { unset($options['height']); }

  return wp_oembed_get( $url, $options ); 
}

/**
 * Plugin options getter
 * @param  {string|array}  $option   Option name
 * @param  {mixed}         $default  Default value
 * @return {mixed}                   Option value
 */
function blend_get_option($option, $default = false) {
  $value = get_option('blend_' . $option);
  return $value === '' ? $default : $value;
}

/**
 * Booleanize a value
 * @param  {boolean|string}  $value
 * @return {boolean}
 */
function blend_booleanize($value) {
  return is_bool($value) ? $value : $value === 'true' ? true : false;
}

/**
 * Decide if a url has a tracklist
 * @param  {string}   $url
 * @return {boolean}
 */
function blend_url_is_playlist($url) {
  return preg_match('/^(.+?)\/(set|playlist)\/(.+?)$/', $url);
}

/* Settings
   -------------------------------------------------------------------------- */

/* Add settings link on plugin page */
add_filter("plugin_action_links_" . plugin_basename(__FILE__), 'blend_settings_link');

function blend_settings_link($links) {
  $settings_link = '<a href="options-general.php?page=blend-shortcode">Settings</a>';
  array_unshift($links, $settings_link);
  return $links;
}

/* Add admin menu */
add_action('admin_menu', 'blend_shortcode_options_menu');
function blend_shortcode_options_menu() {
  add_options_page('Blend Options', 'Blend', 'manage_options', 'blend-shortcode', 'blend_shortcode_options');
  add_action('admin_init', 'register_blend_settings');
}

function register_blend_settings() {
  register_setting('blend-settings', 'blend_player_height');
  register_setting('blend-settings', 'blend_player_width ');
  register_setting('blend-settings', 'blend_player_height_multi');
  register_setting('blend-settings', 'blend_player_width_multi');
}

function blend_shortcode_options() {
  if (!current_user_can('manage_options')) {
    wp_die( __('You do not have sufficient permissions to access this page.') );
  }
?>
<div class="wrap">
  <h2>Blend Shortcode Default Settings</h2>
  <p>These settings will become the new defaults used by the Blend Shortcode throughout your blog.</p>
  <p>You can always override these settings on a per-shortcode basis. Setting the 'params' attribute in a shortcode overrides these defaults individually.</p>

  <form method="post" action="options.php">
    <?php settings_fields('blend-settings'); ?>
    <table class="form-table">

      <tr valign="top">
        <th scope="row">Player Height for Projects</th>
        <td>
          <input type="text" name="blend_player_height" value="<?php echo get_option('blend_player_height'); ?>" /> (no unit, or %)<br />
          Leave blank to use the default.
        </td>
      </tr>

      <tr valign="top">
        <th scope="row">Player Width for Projects</th>
        <td>
          <input type="text" name="blend_player_width" value="<?php echo get_option('blend_player_width'); ?>" /> (no unit, or %)<br />
          Leave blank to use the default.
        </td>
      </tr>
      
<!-- 
      <tr valign="top">
        <th scope="row">Player Height for Playlists</th>
        <td>
          <input type="text" name="blend_player_height_multi" value="<?php echo get_option('blend_player_height_multi'); ?>" /> (no unit, or %)<br />
          Leave blank to use the default.
        </td>
      </tr>
      
            <tr valign="top">
        <th scope="row">Player Width for Playlists</th>
        <td>
          <input type="text" name="blend_player_width_multi" value="<?php echo get_option('blend_player_width_multi'); ?>" /> (no unit, or %)<br />
          Leave blank to use the default.
        </td>
      </tr>
-->

    </table>

      <p class="submit">
        <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
      </p>

  </form>
</div>
<?php
}
?>
