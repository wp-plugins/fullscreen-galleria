<?php
/******************************************************************************

Plugin Name: Fullscreen Galleria
Plugin URI: http://torturedmind.org/
Description: Fullscreen gallery for Wordpress
Version: 0.6.3
Author: Petri Damstén
Author URI: http://torturedmind.org/
License: MIT

******************************************************************************/

$fsg_ver = '0.6.3';

class FSGPlugin {
  protected $photobox = "fsg_photobox = {\n";
  protected $json = "fsg_json = {\n";
  protected $gps = FALSE;
  protected $photoboxid = 0;
  protected $groupid = 0;
  protected $firstpostid = -1;
  protected $used = Array();

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

  public function __construct()
  {
    // run after gallery processed
    add_filter('the_content', array(&$this, 'content'), 99);
    add_action('wp_enqueue_scripts', array(&$this, 'enqueue_scripts'));
    add_action('wp_footer', array(&$this, 'footer'));
    add_filter('attachment_fields_to_edit', array(&$this, 'fields_to_edit'), 10, 2);
    add_filter('attachment_fields_to_save', array(&$this, 'fields_to_save'), 10, 2);
    add_filter('wp_read_image_metadata', array(&$this, 'add_additional_metadata'), '', 3);
    add_shortcode('fsg_photobox', array(&$this, 'photobox_shortcode'));
    add_shortcode('fsg_link', array(&$this, 'link_shortcode'));
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
    $this->photobox .= $id.": {rows: ".$rows.", cols: ".$cols.", border: ".$border."},\n";
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
    wp_enqueue_script('galleria', plugins_url('galleria-1.2.8.min.js', __FILE__), array('jquery'), '1.2.8', true);
    //wp_enqueue_script('galleria', plugins_url('galleria-1.2.8.js', __FILE__), array('jquery'), '1.2.8', true);
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
      $this->json .= "\n};";
      $this->photobox = rtrim($this->photobox, ",\n");
      $this->photobox .= "\n};\n";
      $theme = plugins_url('galleria-fs-theme.js', __FILE__);
      $url = "fullscreen_galleria_url='".plugin_dir_url(__FILE__)."';\n";
      $postid = "fullscreen_galleria_postid=".$this->firstpostid.";\n";
      echo "<div id=\"galleria\"></div><script>Galleria.loadTheme(\"".$theme."\");\n".
           $url.$postid.$this->photobox.$this->json."</script>";
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
        $title = addslashes($val['data']->post_title);
        //var_dump($val['data']);
        $description = $val['data']->post_content;
        if (!empty($description)) {
          $description = addslashes($description);
          $description = str_replace("\n", "<br/>", $description);
          $description = str_replace("\r", "", $description);
          $description = "<p class=\"galleria-info-description\">".$description."</p>";
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
        if (!empty($permalink)) {
          $bookmark = "<div class=\"galleria-layeritem\">".
                      "<a title=\"Permalink\" href=\"".$permalink."\">".
                      "<div class=\"galleria-link-bookmark\"></div></a>".
                  "</div>";
        } else {
          $bookmark = '';
        }
        $info = (empty($meta['image_meta']['info'])) ? '' :
                "<p class=\"galleria-info-camera\">".$meta['image_meta']['info']."</p>";
        $info = addslashes($info);
        $this->json .= "{id: ".$val['post_id'].
                      ", image: '".$key.
                      "', thumb: '".$thumb.
                      "', permalink: '".$permalink.
                      "', layer: '<div class=\"galleria-infolayer\">".
                          "<div class=\"galleria-layeritem\">".
                              "<h1>".$title."</h1>".$description.$info.
                          "</div>".$link.$map.$bookmark."</div>'";
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

    // Add needed data to links
    $upload_dir = wp_upload_dir();
    $media = $upload_dir['baseurl'];
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
                $images[$href] = array('post_id' => $id, 'id' => $id, 'data' => $photos[0],
                                       'permalink' => get_permalink($id).'#0');
            }
          }
        }
        if (array_key_exists($href, $images)) {
          $tmp = str_replace('<a ', '<a data-postid="fsg_post_'.$post->ID.
                             '" data-imgid="'.$images[$href]['id'].'" ', $link);
          $content = str_replace($link, $tmp, $content);
        }
      }
    }
    $this->append_json('fsg_post_'.$post->ID, $images);
    return $content;
  }
}

$fsgplugin = new FSGPlugin();

?>
