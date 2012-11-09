/******************************************************************************
 Galleria Fullscreen Theme
 Copyright (c) 2012, Petri Damstén
 Licensed under the MIT license.
******************************************************************************/

(function($) {

var fsg_last_post_id = "";

$(document).ready(function() { // DOM ready
  $("[data-imgid]", this).each(function() {
    $(this).click(show_galleria);
  });
  if($(".galleria-photobox").length != 0) {
    randomize_photos();
  }

  var hash = window.location.hash;
  if (hash.length > 0) {
    var postid = 'fsg_post_' + fullscreen_galleria_postid;
    var imgid = hash.substring(1);
    $('[data-imgid="' + imgid + '"][data-postid="' + postid + '"]', this).first().click();
  }
});

$(window).resize(function() { // window resized
  var galleria = $("#galleria").data('galleria');
  if (galleria != undefined) {
    galleria.resize();
  }
});

set_keyboard = function(event) {
  var galleria = $("#galleria").data('galleria');
  galleria.attachKeyboard({
    escape: function() {
      if ($('#galleria-map').is(":visible")) {
        $('.galleria-map-close').click();
      } else {
        $('.galleria-close').click();
      }
    },
    left: galleria.prev,
    80: galleria.prev, // P = Previous
    right: galleria.next,
    space: galleria.next,
    78: galleria.next, // N = Next
    83: function() { // S = Slideshow
        galleria.setPlaytime(1500);
        galleria.playToggle();
    },
    77: function() { // M = Open map
      $('#fsg_map_btn').click();
    },
    70: function() { // M = Open map
      galleria.toggleFullscreen();
      set_keyboard();
    }
  });
}

show_galleria = function(event) {
  event.preventDefault();
  var elem = $("#galleria");
  var close = $("#close-galleria");
  elem.toggle();
  close.toggle();
  var postid = $(this).attr("data-postid");
  var imgid = $(this).attr("data-imgid");
  id = 0;
  for (var i = 0; i < fsg_json[postid].length; ++i) {
    if (fsg_json[postid][i]['id'] == imgid) {
      id = i;
      break;
    }
  }
  if (postid != fsg_last_post_id) {
    if (elem.data('galleria')) {
      // Set new data
      // Bit of a hack, but load does not have show param and show function
      // works purely after load
      elem.data('galleria')._options.show = id;
      elem.data('galleria').load(fsg_json[postid]);
      set_keyboard();
    } else {
      // Init galleria
      elem.galleria({
        dataSource: fsg_json[postid],
        show: id,
        showCounter: false,
        fullscreenDoubleTap: false,
        imageCrop: false,
        fullscreenCrop: false,
        maxScaleRatio: 1.0,
        idleTime: 2000,
        extend: function() {
          set_keyboard();
        }
      });
    }
    fsg_last_post_id = postid;
  } else {
    elem.data('galleria').show(id);
    set_keyboard();
  }
}

open_map = function(lat, long)
{
  $('#galleria-map').show();
  if (typeof open_map.map == 'undefined') {
    open_map.proj = new OpenLayers.Projection('EPSG:4326');
    OpenLayers.ImgPath = fullscreen_galleria_url;
    open_map.map = new OpenLayers.Map('galleria-map', {
        controls:[
            new OpenLayers.Control.Navigation(),
            new OpenLayers.Control.PanZoomBar(),
            new OpenLayers.Control.Attribution()]
    });
    open_map.map.addLayer(new OpenLayers.Layer.OSM());
  }

  var lonLat = new OpenLayers.LonLat(long, lat).transform(
      open_map.proj, open_map.map.getProjectionObject());
  open_map.map.setCenter(lonLat, 16);

  if (typeof open_map.marker == 'undefined') {
    var markers = new OpenLayers.Layer.Markers('Markers');
    open_map.map.addLayer(markers);
    var size = new OpenLayers.Size(35, 52);
    var offset = new OpenLayers.Pixel(-(size.w / 2), -size.h);
    var icon = new OpenLayers.Icon(fullscreen_galleria_url + 'marker.png', size, offset);
    open_map.marker = new OpenLayers.Marker(lonLat, icon);
    markers.addMarker(open_map.marker);
  } else {
    var px = open_map.map.getLayerPxFromLonLat(lonLat);
    open_map.marker.moveTo(px);
  }
}

randomize_photos = function()
{
  $(".galleria-photobox").each(function(index) {
    var ID = 'fsg_photobox_' + index;
    var BORDER = fsg_photobox[ID]['border'];
    var COLS = fsg_photobox[ID]['cols'];
    var ROWS = fsg_photobox[ID]['rows'];
    var BOX = $(this).width() / COLS + 1;
    $(this).width($(this).parent().width() + 2 * BORDER);
    $(this).height(($(this).parent().width() / COLS * ROWS) + 2 * BORDER);
    $(this).css('top', -BORDER);
    $(this).css('left', -BORDER);
    $(this).html('');
    //console.log(index, ID, BORDER, 'x', COLS, ROWS, BOX);

    // init array
    var array = new Array(COLS);
    for (var i = 0; i < COLS; i++) {
      array[i] = new Array(ROWS);
      for (var j = 0; j < ROWS; j++) {
        array[i][j] = 0;
      }
    }
    var x = 0;
    var y = 0;
    var d = 1;
    while (1) {
      // next free cell
      stop = false;
      while (array[x][y] != 0) {
        ++x;
        if (x >= COLS) {
          x = 0;
          ++y;
          if (y >= ROWS) {
            stop = true;
            break;
          }
        }
      }
      if (stop) {
        break;
      }
      // find max size
      var mx = 0;
      while ((x + mx) < COLS && array[x + mx][y] == 0) {
        ++mx;
      }
      var my = 0;
      while ((y + my) < ROWS && array[x][y + my] == 0) {
        ++my;
      }
      // mark array
      var max = Math.min(mx, my);
      var box = Math.floor(Math.random() * max) + 1;
      for (var i = 0; i < box; i++) {
        for (var j = 0; j < box; j++) {
          array[x + i][y + j] = d;
        }
      }
      // Get next random photo
      var all = true;
      var photo = Math.floor(Math.random() * fsg_json[ID].length);
      for (i = 0; i < fsg_json[ID].length; ++i) {
        if (fsg_json[ID][photo]['used'] != true) {
          fsg_json[ID][photo]['used'] = true;
          all = false;
          break;
        }
        ++photo;
        if (photo >= fsg_json[ID].length) {
          photo = 0;
        }
      }
      if (all) {
        for (i = 0; i < fsg_json[ID].length; ++i) {
          fsg_json[ID][i]['used'] = (i == photo);
        }
      }
      // Add photo div
      var size = (box * BOX - 2 * BORDER);
      var img = fsg_json[ID][photo]['image'];
      var imgid = fsg_json[ID][photo]['id'];
      var w = fsg_json[ID][photo]['full'][1];
      var h = fsg_json[ID][photo]['full'][2];
      //console.log(size, y, x, y * BOX, x * BOX);
      var $div = $('<div style="width: ' + size + 'px; height: ' + size + 'px; top: ' + y * BOX +
                  'px; left: ' + x * BOX + 'px; margin: ' + BORDER + 'px;">');
      var $a = $('<a data-postid="' + ID + '" data-imgid="' + imgid + '" href="' + img + '">');
      $($a).click(show_galleria);
      // - Find best img
      var a = ["thumbnail", "medium", "large", "full"];
      for (var s in a) {
        min = Math.min(fsg_json[ID][photo][a[s]][1],
                      fsg_json[ID][photo][a[s]][2]);
        if (min > size) {
          img = fsg_json[ID][photo][a[s]][0];
          w = fsg_json[ID][photo][a[s]][1];
          h = fsg_json[ID][photo][a[s]][2];
          break;
        }
      }
      var min = Math.min(w, h);
      var m = size / min;
      w = w * m;
      h = h * m;
      var imgx = -Math.floor((w - size) / 2.0);
      var imgy = -Math.floor((h - size) / 2.0);
      var $img = $('<img style="left: ' + imgx + 'px; top: ' + imgy + 'px;" width="' + w +
                  '" height="' + h + '" src="' + img + '">');
      $a.append($img);
      $div.append($a);
      $(this).append($div);
      ++d;
    }
  });
  //$(window).resize();
}

}(jQuery)); 
