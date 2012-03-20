<?php
/******************************************************************************

Plugin Name: Galleria Fullscreen
Plugin URI: http://torturedmind.org/
Description: Fullscreen gallery for Wordpress
Version: 0.4
Author: Petri Damstén
Author URI: http://torturedmind.org/
License: MIT

******************************************************************************/

$ver = '0.4';

class GFSPlugin {
  protected $photobox = "fsg_photobox = {\n";
  protected $json = "fsg_json = {\n";
  protected $photoboxid = 0;
  protected $groupid = 0;
  protected $used = Array();

  function startswith(&$str, &$starts)
  {
    return (strncmp($str, $starts, strlen($starts)) == 0);
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
      if (strrpos($bloginfo, "localhost") !== false) {
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

  public function __construct()
  {
    // run after gallery processed
    add_filter('the_content', array(&$this, 'content'), 99);
    add_action('wp_enqueue_scripts', array(&$this, 'enqueue_scripts'));
    add_action('wp_footer', array(&$this, 'footer'));
    add_filter('attachment_fields_to_edit', array(&$this, 'fields_to_edit'), 10, 2);
    add_filter('attachment_fields_to_save', array(&$this, 'fields_to_save'), 10, 2);
    add_shortcode('fsg_photobox', array(&$this, 'photobox_shortcode'));
    add_shortcode('fsg_link', array(&$this, 'link_shortcode'));
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
          array('post_id' => $val->ID, 'id' => 0, 'data' => $val);
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
          array('post_id' => $val->ID, 'id' => 0, 'data' => $val);
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
    if ($modified) {
      wp_update_attachment_metadata($post['ID'], $meta);
    }
    return $post;
  }

  function enqueue_scripts()
  {
    wp_enqueue_script('galleria', plugins_url('galleria-1.2.6.min.js', __FILE__), array('jquery'), '1.2.6');
    //wp_enqueue_script('galleria', plugins_url('galleria.js', __FILE__), array('jquery'), '1.2.6');
    wp_enqueue_script('galleria-fs', plugins_url('galleria-fs.js', __FILE__), array('jquery'), $ver);
    wp_register_style('galleria-fs', plugins_url('galleria-fs.css', __FILE__), array(), $ver);
    wp_enqueue_style('galleria-fs');
  }

  function footer()
  {
    if (!empty($this->json)) {
      $this->json .= "};";
      $this->photobox .= "};\n";
      $theme = plugins_url('galleria-fs-theme.js', __FILE__);
      echo "<div id=\"galleria\"></div><script>Galleria.loadTheme(\"".$theme."\");\n".
           $this->photobox.$this->json."</script>";
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
    $i = 0;
    $this->json .= $id.": [\n";
    foreach ($images as $key => $val) {
      if (!in_array($val['post_id'], $this->used)) {
        $meta = wp_get_attachment_metadata($val['post_id']);
        $thumb = wp_get_attachment_image_src($val['post_id'], 'thumbnail');
        $thumb = $thumb[0];
        $title = str_replace("'", "\\'", $val['data']->post_title);
        //var_dump($val['data']);
        $description = $val['data']->post_content;
        if (!empty($description)) {
          $description = str_replace("'", "\\'", $description);
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
        $info = (empty($meta['image_meta']['info'])) ? '' :
                "<p class=\"galleria-info-camera\">".$meta['image_meta']['info']."</p>";
        $this->json .= "{image: '".$key.
                      "', thumb: '".$thumb.
                      "', layer: '<div class=\"galleria-infolayer\">".
                          "<div class=\"galleria-layeritem\">".
                              "<h1>".$title."</h1>".$description.$info.
                          "</div class=\"galleria-layeritem\">".$link."</div>'";
        if ($extra) {
          foreach (array("thumbnail", "medium", "large", "full") as $size) {
            $img = wp_get_attachment_image_src($val['post_id'], $size);
            $this->json .= ", ".$size.": ['".$img[0]."', ".$img[1].", ".$img[2]."]";
          }
        }
        $this->json .= "},\n";
        $images[$key]['id'] = $i;
        ++$i;
      }
    }
    $this->json .= "],\n";
  }

  // Handle gallery
  function content($content)
  {
    global $post;
    // Get children (images) of the post
    $children = &get_children(array('post_parent' => $post->ID, 'post_status' => 'inherit',
        'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => 'ASC',
        'orderby' => 'menu_order ID'));
    if (empty($children)) {
      return $content;
    }
    $images = array();
    foreach ($children as $key => $val) {
      $images[$this->href(wp_get_attachment_link($key, 'full'))] =
          array('post_id' => $key, 'id' => 0, 'data' => $val);
    }

    // Get possible image groups
    $links = $this->links($content);
    $this->append_json('fsg_post_'.$post->ID, $images);

    // Add needed data to links
    foreach ($links as $link) {
      if (strpos($link, 'data-postid') === false) { // test if link already has the data
        $href = $this->href($link);
        if (array_key_exists($href, $images)) {
          $tmp = str_replace('<a ', '<a data-postid="fsg_post_'.$post->ID.
                            '" data-imgid="'.$images[$href]['id'].'" ', $link);
          $content = str_replace($link, $tmp, $content);
        }
      }
    }
    return $content;
  }
}

$gfsplugin = new GFSPlugin();

?>
