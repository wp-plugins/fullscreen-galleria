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
});

show_galleria = function(event) {
  event.preventDefault();
  var elem = $("#galleria");
  var close = $("#close-galleria");
  //var showimg = false;
  elem.toggle();
  close.toggle();
  var postid = $(this).attr("data-postid");
  var imgid = $(this).attr("data-imgid");
  if (postid != fsg_last_post_id) {
    if (elem.data('galleria')) {
      // Set new data
      // Bit of a hack, but load does not have show param and show function
      // works purely after load
      elem.data('galleria')._options.show = parseInt(imgid);
      elem.data('galleria').load(fsg_json[postid]);
      elem.data('galleria').enterFullscreen();
    } else {
      // Init galleria
      elem.galleria({
        dataSource: fsg_json[postid],
        show: imgid,
        showCounter: false,
        fullscreenDoubleTap: false,
        imageCrop: false,
        fullscreenCrop: false,
        //imageCrop: 'fit',
        //fullscreenCrop: 'fit',
        idleTime: 2000,
        extend: function() {
          this.enterFullscreen();
        }
      });
    }
    fsg_last_post_id = postid;
  } else {
    elem.data('galleria').show(imgid);
    elem.data('galleria').enterFullscreen();
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
      var w = fsg_json[ID][photo]['full'][1];
      var h = fsg_json[ID][photo]['full'][2];
      //console.log(size, y, x, y * BOX, x * BOX);
      var $div = $('<div style="width: ' + size + 'px; height: ' + size + 'px; top: ' + y * BOX +
                  'px; left: ' + x * BOX + 'px; margin: ' + BORDER + 'px;">');
      var $a = $('<a data-postid="' + ID + '" data-imgid="' + photo + '" href="' + img + '">');
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
