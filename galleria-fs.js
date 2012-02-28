/******************************************************************************
 Galleria Fullscreen Theme
 Copyright (c) 2012, Petri Damstén
 Licensed under the MIT license.
******************************************************************************/

(function($) {

var galleria_last_post_id = "";

$(window).resize(function() { // window resized
  var elem = $("#galleria");
  //console.log(elem.data('galleria'));
  //elem.data('galleria')._fullscreen.scale();
});

$(document).ready(function() { // DOM ready
  $("[data-imgid]", this).each(function() {
    $(this).click(function(event) {
      event.preventDefault();
      var elem = $("#galleria");
      var close = $("#close-galleria");
      //var showimg = false;
      elem.toggle();
      close.toggle();
      var postid = $(this).attr("data-postid");
      var imgid = $(this).attr("data-imgid");
      if (postid != galleria_last_post_id) {
        if (elem.data('galleria')) {
          // Set new data
          // Bit of a hack, but load does not have show param and show function
          // works purely after load
          elem.data('galleria')._options.show = parseInt(imgid);
          elem.data('galleria').load(galleria_json[postid]);
          elem.data('galleria').enterFullscreen();
        } else {
          // Init galleria
          elem.galleria({
            dataSource: galleria_json[postid],
            show: imgid,
            showCounter: false,
            fullscreenDoubleTap: false,
            imageCrop: false,
            fullscreenCrop: false,
            idleTime: 2000,
            extend: function() {
              this.enterFullscreen();
            }
          });
        }
        galleria_last_post_id = postid;
      } else {
        elem.data('galleria').show(imgid);
        elem.data('galleria').enterFullscreen();
      }
    });
  });
});

}(jQuery)); 
