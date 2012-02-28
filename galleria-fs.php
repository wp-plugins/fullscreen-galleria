<?php
/******************************************************************************

Plugin Name: Galleria Fullscreen
Plugin URI: http://torturedmind.org/
Description: Fullscreen gallery for Wordpress
Version: 0.1
Author: Petri Damstén
Author URI: http://torturedmind.org/
License: MIT

******************************************************************************/

class GFSPlugin {
  protected $json = "galleria_json = {\n";

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
    $ver = '0.1';
    wp_enqueue_script('galleria', plugins_url('galleria-1.2.6.min.js', __FILE__), array('jquery'), '1.2.6');
    //wp_enqueue_script('galleria', plugins_url('galleria-1.2.6.js', __FILE__), array('jquery'), '1.2.6');
    wp_enqueue_script('galleria-fs', plugins_url('galleria-fs.js', __FILE__), array('jquery'), $ver);
    wp_register_style('galleria-fs', plugins_url('galleria-fs.css', __FILE__), array(), $ver);
    wp_enqueue_style('galleria-fs');
  }

  function footer()
  {
    if (!empty($this->json)) {
      $this->json .= '};';
      $theme = plugins_url('galleria-fs-theme.js', __FILE__);
      echo "<div id=\"galleria\"></div><script>Galleria.loadTheme(\"".$theme."\");\n".$this->json."</script>";
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
          array('used' => false, 'post_id' => $key, 'id' => 0,
                'group' => '', 'data' => $val);
    }

    // Get possible image groups
    $links = $this->links($content);
    $groups = array();
    foreach ($links as $link) {
      $group = $this->tagarg($link, 'data-filter');
      if ($group != '') {
        array_push($groups, $group);
      }
    }
    array_push($groups, '');

    // Write json data for galleria
    foreach ($groups as $group) {
      $i = 0;
      $this->json .= "g".$post->ID.$group.": [\n";
      foreach ($images as $key => $val) {
        $meta = wp_get_attachment_metadata($val['post_id']);
        //var_dump($val['used'], $group, $meta['file']);
        if ($val['used'] == false && ($group != '' &&
            $this->startswith($meta['file'], $group) || $group == '')) {
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
                            "</div class=\"galleria-layeritem\">".$link."</div>".
                        "'},\n";
          $images[$key]['used'] = true;
          $images[$key]['id'] = $i;
          $images[$key]['group'] = $group;
        }
        ++$i;
      }
      $this->json .= "],\n";
    }

    // Add needed data to links
    foreach ($links as $link) {
      $href = $this->href($link);
      if (array_key_exists($href, $images)) {
        $tmp = str_replace('<a ', '<a data-postid="g'.$post->ID.$images[$href]['group'].
                           '" data-imgid="'.$images[$href]['id'].'" ', $link);
        $content = str_replace($link, $tmp, $content);
      }
    }
    return $content;
  }
}

$gfsplugin = new GFSPlugin();

?>
