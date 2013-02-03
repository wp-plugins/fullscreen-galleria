<?php
/******************************************************************************

Plugin Name: Fullscreen Galleria
Plugin URI: http://torturedmind.org/
Description: Fullscreen gallery for Wordpress
Version: 1.1
Author: Petri Damstén
Author URI: http://torturedmind.org/
License: MIT

******************************************************************************/

$fsg_ver = '1.1';

function fsg_remove_settings() 
{
	delete_option($this->db_key);
}

class FSGPlugin {
  protected $photobox = "fsg_photobox = {\n";
  protected $json = "fsg_json = {\n";
  protected $options = array();
  protected $gps = FALSE;
  protected $photoboxid = 0;
  protected $groupid = 0;
  protected $firstpostid = -1;
  protected $used = array();

  // Helper functions

  function startswith(&$str, &$starts)
  {
    return (strncmp($str, $starts, strlen($starts)) == 0);
  }
  
  function endswith(&$str, &$ends)
  {
    return (substr($str, -strlen($ends)) === $ends);
  }
  
  function get_attachment_id_from_src($src) 
  {
		global $wpdb;
    if (WP_DEBUG) {
      $src = str_replace(".localhost", ".org", $src);
    }
		return $wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE guid='$src'");
  }

  function tagarg(&$tag, $arg)
  {
    $i = stripos($tag, $arg);
    if ($i != FALSE) {
      $len = strlen($tag);
      while ($i < $len && $tag[$i] != "'" && $tag[$i] != '"') {
        ++$i;
      }
      ++$i;
      $j = stripos($tag, $tag[$i - 1], $i);
      if ($j != FALSE) {
        return substr($tag, $i, $j - $i);
      }
    }
    return '';
  }

  function href($str)
  {
    $href = $this->tagarg($str, 'href');
    if (WP_DEBUG) {
      // Make localhost copy work in DEBUG mode
      $bloginfo = get_bloginfo('url');
      if (strrpos($bloginfo, "localhost") !== FALSE) {
        $href = str_replace(array(".org", ".net", ".com"), ".localhost", $href);
      }
    }
    return $href;
  }

  function links($content)
  {
    $links = array();
    $re = "/\<a[^\>]+\>/mi";
    preg_match_all($re, $content, $links);
    $links = $links[0];
    return $links;
  }
  
  function gps_to_float($value)
  {
    $a = explode('/', $value);
    if (count($a) < 1) {
      return 0.0;
    }
    if (count($a) < 2) {
      return floatval($a[0]);
    }
    return floatval($a[0]) / floatval($a[1]);
  }

  function gps_to_degrees($value)
  {
    if (count($value) > 0) {
      $d = $this->gps_to_float($value[0]);
    }
    if (count($value) > 1) {
      $m = $this->gps_to_float($value[1]);
    }
    if (count($value) > 2) {
      $s = $this->gps_to_float($value[2]);
    }
    return $d + ($m / 60.0) + ($s / 3600.0);
  }

  function ob_log($ob)
  {
    ob_start();
    var_dump($ob);
    $contents = ob_get_contents();
    ob_end_clean();
    error_log($contents);
  }

  // Plugin construct

  public function __construct()
  {
    // run after gallery processed
    add_filter('the_content', array(&$this, 'content'), 99);
    add_action('wp_enqueue_scripts', array(&$this, 'enqueue_scripts'));
    add_action('wp_footer', array(&$this, 'footer'));
    add_filter('attachment_fields_to_edit', array(&$this, 'fields_to_edit'), 10, 2);
    add_filter('attachment_fields_to_save', array(&$this, 'fields_to_save'), 10, 2);
    add_filter('wp_read_image_metadata', array(&$this, 'add_additional_metadata'), '', 5);
    add_shortcode('fsg_photobox', array(&$this, 'photobox_shortcode'));
    add_shortcode('fsg_link', array(&$this, 'link_shortcode'));
    add_action('admin_init', array(&$this, 'admin_init'));
    add_action('admin_menu', array(&$this, 'admin_menu'));
    register_uninstall_hook(__FILE__, 'fsg_remove_settings');
    $plugin = plugin_basename(__FILE__); 
    add_filter('plugin_action_links_'.$plugin, array(&$this, 'settings_link'));
    
    $this->defaults = array(); 
    foreach ($this->settings as $key => $setting) {
      $this->defaults[$key] = $this->settings[$key]['default'];
    }
    $this->options = get_option($this->db_key, $this->defaults);
    foreach ($this->settings as $key => $setting) {
      if (!array_key_exists($key, $this->options)) {
        $this->options[$key] = $this->settings[$key]['default'];
      }
      if ($setting['type'] == 'checkbox') {
        $this->options[$key] = ($this->options[$key] == 'on') ? true : false;
      }
    }
  }

  // Settings
  protected $db_key = 'fsg_plugin_settings';
  protected $settings = array(
  	'transition' => array(
      'title' => 'Transition Type', 
      'type' => 'combobox',
      'default' => 'slide',
    	'items' => array('Slide' => 'slide', 'Fade' => 'fade', 'Flash' => 'flash', 'Pulse' => 'pulse', 
                       'Fade and Slide' => 'fadeslide')
    ),
  	'overlay_time' => array(
      'title' => 'Show Overlay', 
      'type' => 'combobox',
      'default' => 2000,
    	'items' => array('Newer' => 0, '1s' => 1000, '2s' => 2000, '4s' => 4000, '8s' => 8000, 
                       'Allways' => 1000000)
    ),
  	'show_camera_info' => array(
      'title' => 'Show Camera Info', 
      'type' => 'checkbox',
      'default' => 'on'
    ),
  	'show_description' => array(
      'title' => 'Show Description', 
      'type' => 'checkbox',
      'default' => 'on'
    ),
  	'show_thumbnails' => array(
      'title' => 'Show Thumbnails', 
      'type' => 'checkbox',
      'default' => 'on'
    ),
  	'show_permalink' => array(
      'title' => 'Show Permalink', 
      'type' => 'checkbox',
      'default' => 'on'
    ),
  	'auto_start_slideshow' => array(
      'title' => 'Autostart slideshow', 
      'type' => 'checkbox',
      'default' => ''
    )
  );
      
  function admin_init()
  {
  	register_setting($this->db_key, $this->db_key, 
                     array(&$this, 'plugin_settings_validate'));
  	add_settings_section('main_section', 'Main Settings', NULL, __FILE__);
    foreach ($this->settings as $key => $setting) {
      add_settings_field($key, $setting['title'], array(&$this, $setting['type']), 
                         __FILE__, 'main_section', $key);
    }
  }

  function admin_menu() 
  {
  	add_options_page('Fullscreen Galleria Settings', 'Fullscreen Galleria', 
                     'administrator', __FILE__, array(&$this, 'settings_page'));
  }
  
  function settings_page() 
  {
    global $fsg_ver;
  ?>
  	<div class="wrap">
  		<div class="icon32" id="icon-options-general"><br></div>
  		<h2>Fullscreen Galleria Settings</h2>
  		<form action="options.php" method="post">
  		<?php settings_fields($this->db_key); ?>
  		<?php do_settings_sections(__FILE__); ?>
  		<p class="submit">
  			<input name="submit" type="submit" id="submit" class="button-primary" 
               value="<?php esc_attr_e('Save Changes'); ?>" />
  		</p>
  		</form>
      <div style="text-align: center; width: 256px; line-height: 175%;">
        
      <img width=256 height=28 src="<?php echo plugins_url('hr.png', __FILE__); ?>"><br>
      Version <?php echo $fsg_ver; ?><br>
      <div style="font-size: 12pt;">
      <a href="mailto:petri.damsten@torturedmind.org">Petri Damstén</a><br>
      <a href="http://torturedmind.org/">Tortured Mind Photography</a>
    	</div>
    	</div>
  	</div>
  <?php
  }

  function plugin_settings_validate($input) 
  {
  	return $input;
  }

  function combobox($key) 
  {
  	$options = get_option($this->db_key, $this->defaults);
  	$items = $this->settings[$key]['items'];
  	echo '<select id="'.$key.'" name="'.$this->db_key.'['.$key.']">';
  	foreach ($items as $k => $item) {
  		$selected = ($options[$key] == $item) ? 'selected="selected"' : '';
  		echo '<option value="'.$item.'" '.$selected.'>'.$k.'</option>';
  	}
  	echo '</select>';
  }

  function checkbox($key) 
  {
  	$options = get_option($this->db_key, $this->defaults);
  	if ($options[$key] == 'on') { 
      $checked = ' checked="checked" '; 
    } else {
      $checked = '';
    }
  	echo '<input '.$checked.' id="'.$key.'" name="'.$this->db_key.'['.$key.']" type="checkbox" />';
  }

  function settings_link($links) 
  { 
    $settings_link = '<a href="options-general.php?page=fullscreen-galleria/galleria-fs.php">'.
                     'Settings</a>'; 
    array_unshift($links, $settings_link); 
    return $links; 
  }

  // Rest of the plugin

  function exifv($s)
  {
    $e = explode('/', $s);
    if (count($e) < 2 ) {
      $b = 1;
    } else {
      $b = intval($e[1]);
    }
    $a = intval($e[0]);
    $a = ($a == 0) ? 1: $a;
    $b = ($b == 0) ? 1: $b;
    return array($a, $b);
  }
  
  function camera_info($exif)
  {
    $camera = '';
    $lens = '';
    $focal = '';
    $f = '';
    $s = '';
    $iso = '';
    if (!empty($exif['Model'])) {
      $camera = $exif['Model'];
    }
    if (!empty($exif['Make'])) {
      $make = $exif['Make'];
    }
    if (substr($camera, 0, 4) == substr($make, 0, 4)) {
      $camera = $camera;
    } else {
      $camera = $make.' '.$camera;
    }
    #if (!empty($exif['LensModel'])) {
    #  $lens = $exif['LensModel'];
    #} else if (!empty($exif['LensInfo'])) {
    #  $lens = $exif['LensInfo'];
    #}
    #if ($lens != '') {
    #    $lens = ' with '.$lens;
    #}
    if (!empty($exif['FNumber'])) {
      $f = $this->exifv($exif['FNumber']);
      $f = $f[0] / $f[1];
      $f = ' and f/'.$f;
    }
    if (!empty($exif['ExposureTime'])) {
      $s = $this->exifv($exif['ExposureTime']);
      if ($s[0] > $s[1]) {
        $s = $s[0] / $s[1];
      } else {
        $s = $s[1] / $s[0];
        $s = '1/'.$s;
      }
      $s = ', '.$s.'sec';
    }
    if (!empty($exif['FocalLength'])) {
      $focal = $this->exifv($exif['FocalLength']);
      $focal = round($focal[0] / $focal[1]);
      $focal = ' at '.$focal.'mm';
    }
    if (!empty($exif['ISOSpeedRatings'])) {
      $iso = $exif['ISOSpeedRatings'];
      if ($iso != '') {
        $iso = ', ISO '.$iso;
      }
    }
    #error_log($camera.$lens.$focal.$f.$s.$iso);
    return $camera.$lens.$focal.$f.$s.$iso;
  }

  function add_additional_metadata($meta, $file, $sourceImageType)
  {
    if (is_callable('exif_read_data')) {
      $exif = @exif_read_data($file);
      if (!empty($exif['GPSLatitude'])) {
        $lat = $this->gps_to_degrees($exif['GPSLatitude']);
      }
      if (!empty($exif['GPSLongitude'])) {
        $long = $this->gps_to_degrees($exif['GPSLongitude']);
      }
      if (!empty($exif['GPSLatitudeRef'])) {
        if ($exif['GPSLatitudeRef'] == 'S') {
          $lat *= -1;
        }
      }
      if (!empty($exif['GPSLongitudeRef'])) {
        if ($exif['GPSLongitudeRef'] == 'W') {
          $long *= -1;
        }
      }
      if (isset($long)) {
        $meta['longitude'] = $long;
      }
      if (isset($lat)) {
        $meta['latitude'] = $lat;
      }
      if (empty($meta['info'])) {
        $meta['info'] = $this->camera_info($exif);
      }
    } else {
      error_log('Cannot read exif. exif_read_data not callable.');
    }
    return $meta;
  }

  function photobox_shortcode($attr, $content = null)
  {
    global $post;

    extract(shortcode_atts(array(
      'rows'       => 2,
      'cols'       => 3,
      'border'     => 2,
      'maxtiles'   => 20,
      'tile'       => 0,
      'include'    => '',
    ), $attr));

    if (!empty($include)) {
      $photos = &get_posts(array('post_type' => 'attachment',
          'post_mime_type' => 'image', 'order' => 'ASC', 'orderby' => 'menu_order ID',
          'include' => $include));
    } else {
      $photos = &get_children(array('post_parent' => $post->ID, 'post_status' => 'inherit',
          'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => 'ASC',
          'orderby' => 'menu_order ID'));
    }
    $images = array();
    foreach ($photos as $key => $val) {
      $images[$this->href(wp_get_attachment_link($val->ID, 'full'))] =
          array('post_id' => $val->ID, 'id' => 0, 'data' => $val,
                'permalink' => get_permalink($val->ID).'#0');
    }
    $id = 'fsg_photobox_'.$this->photoboxid;
    $this->photobox .= $id.": {rows: ".$rows.", cols: ".$cols.", border: ".
                       $border.", maxtiles: ".$maxtiles.", tile: ".$tile."},\n";
    $this->append_json($id, $images, true);
    ++$this->photoboxid;
    return "<div id='".$id."' class='galleria-photobox'></div>";
  }

  function link_shortcode($attr, $content = null)
  {
    global $post;

    extract(shortcode_atts(array(
      'include'    => '',
      'class'      => '',
    ), $attr));

    if (!empty($include)) {
      $photos = &get_posts(array('post_type' => 'attachment',
          'post_mime_type' => 'image', 'order' => 'ASC', 'orderby' => 'menu_order ID',
          'include' => $include));
      $id = "fsg_group_".$this->groupid;
      ++$this->groupid;
    } else {
      $photos = &get_children(array('post_parent' => $post->ID, 'post_status' => 'inherit',
          'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => 'ASC',
          'orderby' => 'menu_order ID'));
      $id = "fsg_post_".$post->ID;
    }
    $images = array();
    foreach ($photos as $key => $val) {
      $images[$this->href(wp_get_attachment_link($val->ID, 'full'))] =
          array('post_id' => $val->ID, 'id' => 0, 'data' => $val,
                'permalink' => get_permalink($val->ID).'#0');
    }
    if (!empty($include)) {
      $this->append_json($id, $images);
      $this->used = array_merge($this->used, wp_parse_id_list($include));
    }
    reset($images);
    $first = key($images);
    if (!empty($class)) {
      $class = " class='".$class."'";
    }
    return "<a data-postid='".$id."' data-imgid='0' href='".$first."'".$class.">".$content."</a>";
  }

  function fields_to_edit($form_fields, $post)
  {
    $meta = wp_get_attachment_metadata($post->ID);
    $form_fields["camera-info"] = array(
        "label" => __("Camera Info"),
        "input" => "text",
        "value" => empty($meta['image_meta']['info']) ? '' : $meta['image_meta']['info'],
        "helps" => __('Camera info for Fullscreen Galleria plugin.')
    );
    $form_fields["custom-link"] = array(
        "label" => __("Custom Link"),
        "input" => "text",
        "value" => empty($meta['image_meta']['link']) ? '' : $meta['image_meta']['link'],
        "helps" => __('Custom link for Fullscreen Galleria plugin.')
    );
    $form_fields["latitude"] = array(
        "label" => __("Latitude"),
        "input" => "text",
        "value" => empty($meta['image_meta']['latitude']) ? '' : $meta['image_meta']['latitude'],
        "helps" => __('Latitude for Fullscreen Galleria plugin.')
    );
    $form_fields["longitude"] = array(
        "label" => __("Longitude"),
        "input" => "text",
        "value" => empty($meta['image_meta']['longitude']) ? '' : $meta['image_meta']['longitude'],
        "helps" => __('Longitude for Fullscreen Galleria plugin.')
    );
    return $form_fields;
  }

  function fields_to_save($post, $attachment)
  {
    $meta = wp_get_attachment_metadata($post['ID']);
    $modified = false;
    if (isset($attachment['camera-info'])) {
      $meta['image_meta']['info'] = $attachment['camera-info'];
      $modified = true;
    }
    if (isset($attachment['custom-link'])) {
      $meta['image_meta']['link'] = $attachment['custom-link'];
      $modified = true;
    }
    if (isset($attachment['latitude'])) {
      $meta['image_meta']['latitude'] = $attachment['latitude'];
      $modified = true;
    }
    if (isset($attachment['longitude'])) {
      $meta['image_meta']['longitude'] = $attachment['longitude'];
      $modified = true;
    }
    if ($modified) {
      wp_update_attachment_metadata($post['ID'], $meta);
    }
    return $post;
  }

  function enqueue_scripts()
  {
    global $fsg_ver;
    wp_enqueue_script('galleria', plugins_url('galleria-1.2.9.min.js', __FILE__), array('jquery'), '1.2.9', true);
    //wp_enqueue_script('galleria', plugins_url('galleria-1.2.9.js', __FILE__), array('jquery'), '1.2.9', true);
    wp_enqueue_script('galleria-fs', plugins_url('galleria-fs.js', __FILE__), array('galleria'), $fsg_ver, true);
    // register here and print conditionally in footer
    wp_register_script('open-layers', plugins_url('OpenLayers.js', __FILE__), array('galleria-fs'), '2.11', true);
    wp_register_style('galleria-fs', plugins_url('galleria-fs.css', __FILE__), array(), $fsg_ver);
    wp_enqueue_style('galleria-fs');
  }

  function footer()
  {
    // We call wp_print_scripts here also when gps is false so scripts get printed before
    // json/galleria loading code
    wp_print_scripts(($this->gps) ? 'open-layers' : '');
    if (!empty($this->json)) {
      $this->json = rtrim($this->json, ",\n");
      $this->json .= "\n};\n";
      $this->photobox = rtrim($this->photobox, ",\n");
      $this->photobox .= "\n};\n";
      $theme = plugins_url('galleria-fs-theme.js', __FILE__);
      $url = "fullscreen_galleria_url='".plugin_dir_url(__FILE__)."';\n";
    	$options = 'fsg_settings = '.json_encode($this->options).";\n";
      $postid = "fullscreen_galleria_postid=".$this->firstpostid.";\n";
      echo "<div id=\"galleria\"></div><script>Galleria.loadTheme(\"".$theme."\");\n".
           $url.$postid.$options.$this->photobox.$this->json."</script>";
    }
  }

  function imginfo(&$meta)
  {
    // TODO Generate info from exif if there is a way to read lens id from makernote
    if (!empty($meta['image_meta']['info'])) {
      return $meta['image_meta']['info'];
    }
    return '';
  }

  function append_json($id, &$images, $extra = false)
  {
    // Write json data for galleria
    $this->json .= $id.": [\n";
    foreach ($images as $key => $val) {
      if (!in_array($val['post_id'], $this->used)) {
        $meta = wp_get_attachment_metadata($val['post_id']);
        $thumb = wp_get_attachment_image_src($val['post_id'], 'thumbnail');
        $thumb = $thumb[0];
        $bookmark = '';
        $layer = '';
        if ($this->options['overlay_time'] != 0) {
          $title = addslashes($val['data']->post_title);
          //var_dump($val['data']);
          $desc = $val['data']->post_content;
          if ($this->options['show_description'] && !empty($desc)) {
            $description = addslashes($desc);
            $description = str_replace("\n", "<br/>", $description);
            $description = str_replace("\r", "", $description);
            $description = "<p class=\"galleria-info-description\">".$description."</p>";
          } else {
            $description = '';
          }
          if (!empty($meta['image_meta']['link'])) {
            $link = $meta['image_meta']['link'];
            if (strpos($link, 'flickr.com') != FALSE) {
              $t = 'Show in Flickr';
              $c = 'galleria-link-flickr';
            } else {
              $t = $link;
              $c = 'galleria-link';
            }
            $link = "<div class=\"galleria-layeritem\">".
                        "<a target=\"_blank\" title=\"".$t."\" href=\"".$link."\">".
                        "<div class=\"".$c."\"></div></a>".
                    "</div>";
          } else {
            $link = '';
          }
          if (!empty($meta['image_meta']['longitude'])) {
            $c = $meta['image_meta']['latitude'].",".$meta['image_meta']['longitude'];
            $map = "<div class=\"galleria-layeritem\">".
                        "<a id=\"fsg_map_btn\" title=\"Open Map\"".
                        " href=\"#\" onclick=\"open_map(".$c.");\">".
                        "<div class=\"galleria-link-map\"></div></a>".
                    "</div>";
            $this->gps = TRUE;
          } else {
            $map = '';
          }
          $permalink = $val['permalink'];
          if ($this->options['show_permalink'] && !empty($permalink)) {
            $bookmark = "<div class=\"galleria-layeritem\">".
                        "<a title=\"Permalink\" href=\"".$permalink."\">".
                        "<div class=\"galleria-link-bookmark\"></div></a>".
                    "</div>";
          } else {
            $bookmark = '';
          }
          if ($this->options['show_camera_info'] && !empty($meta['image_meta']['info'])) {
            $info = "<p class=\"galleria-info-camera\">".$meta['image_meta']['info']."</p>";
          } else {
            $info = '';
          }
          $info = addslashes($info);
          $layer = '<div class="galleria-infolayer"><div class="galleria-layeritem">'.
                   '<h1>'.$title.'</h1>'.$description.$info.
                   '</div>'.$link.$map.$bookmark.'</div>';
        }
        $this->json .= "{id: ".$val['post_id'].
                       ", image: '".$key.
                       "', thumb: '".$thumb.
                       "', permalink: '".$bookmark.
                       "', layer: '".$layer."'";
        if ($extra) {
          foreach (array("thumbnail", "medium", "large", "full") as $size) {
            $img = wp_get_attachment_image_src($val['post_id'], $size);
            $this->json .= ", ".$size.": ['".$img[0]."', ".$img[1].", ".$img[2]."]";
          }
        }
        $this->json .= "},\n";
        $images[$key]['id'] = $val['post_id'];
      }
    }
    $this->json = rtrim($this->json, ",\n");
    $this->json .= "\n],\n";
  }

  // Handle gallery
  function content($content)
  {
    global $post;
    if ($this->firstpostid == -1) {
      $this->firstpostid = $post->ID;
    }
    // Get children (images) of the post
    $children = &get_children(array('post_parent' => $post->ID, 'post_status' => 'inherit',
        'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => 'ASC',
        'orderby' => 'menu_order ID'));
    if (empty($children)) {
      if (substr(get_post_mime_type($post->ID), 0, 5) == 'image') {
        $children = &get_posts(array('post_type' => 'attachment',
            'post_mime_type' => 'image', 'order' => 'ASC', 'orderby' => 'menu_order ID',
            'include' => $post->ID));
      }
    }
    $images = array();
    foreach ($children as $key => $val) {
      $images[$this->href(wp_get_attachment_link($key, 'full'))] =
          array('post_id' => $key, 'id' => $key, 'data' => $val,
                'permalink' => get_permalink($post->ID).'#'.$key);
    }

    $links = $this->links($content);
    #$this->ob_log($links);

    // Add needed data to links
    $upload_dir = wp_upload_dir();
    $media = $upload_dir['baseurl'];
    $fsg_post = array();
    foreach ($links as $link) {
      if (strpos($link, 'data-postid') === false) { // test if link already has the data
        $href = $this->href($link);
        if (!array_key_exists($href, $images) && $this->startswith($href, $media)) {
          // We have images from other posts (include)
          $id = $this->get_attachment_id_from_src($href);
          if ($id != NULL) {
            $photos = &get_posts(array('post_type' => 'attachment',
                'post_mime_type' => 'image', 'order' => 'ASC', 'orderby' => 'menu_order ID',
                'include' => $id));
            if (count($photos) > 0) {
                $fsg_post[$href] = array('post_id' => $id, 'id' => $id, 'data' => $photos[0],
                                         'permalink' => get_permalink($id).'#0');
            }
          }
        } else if (array_key_exists($href, $images)) {
          $fsg_post[$href] = $images[$href];
        }
        if (array_key_exists($href, $fsg_post)) {
          $tmp = str_replace('<a ', '<a data-postid="fsg_post_'.$post->ID.
                             '" data-imgid="'.$fsg_post[$href]['id'].'" ', $link);
          $content = str_replace($link, $tmp, $content);
        }
      }
    }
    $this->append_json('fsg_post_'.$post->ID, $fsg_post);
    return $content;
  }
}

$fsgplugin = new FSGPlugin();

?>
