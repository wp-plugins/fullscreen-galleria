=== Fullscreen Galleria ===
Contributors: pdamsten
Donate link: http://torturedmind.org/misc-media/
Author URI: http://torturedmind.org/
Plugin URI: http://torturedmind.org/misc-media/
Tags: galleria, gallery, photography, images
Requires at least: 3.3
Tested up to: 3.5.1
Stable tag: trunk
License: MIT

A simple fullscreen gallery to Wordpress

== Description ==

Fullscreen gallery for Wordpress. Based on [Galleria](http://galleria.io/) JavaScript image gallery framework.

#### Features

* Clean fullscreen interface. Only image and carousel is shown when idle.
* Custom link support for media eg. link to Flickr page that is shown for the image.
* If image has gps coordinates it can be shown on map.

#### Usage

1. Use Wordpress Gallery feature and media as usual. Images are handled automatically and shown in fullscreen viewer.

== Installation ==

1. Install and activate Fullscreen Galleria using normal install. [More info](http://codex.wordpress.org/Managing_Plugins)

== Upgrade Notice ==

None

== Frequently Asked Questions ==

= Clicking image shows attachment page =

Gallery settings should have thumbnails linked to image file:

* Link thumbnails to: [x] Image File  [ ] Attachment Page

or in HTML mode:

* [gallery link="file"]

= fsg_photobox keyword =

Adds random photobox to the page. eg. [fsg_photobox include="244, 243,242,241,208,207,206,205,204" rows="6" cols="4"] See live example [here](http://torturedmind.org/photos/).

* **include** - specify list of images (default is all images attached to post/page)
* **rows** - maximum number of rows in the grid (default is 2)
* **cols** - maximum number of columns in the grid (default is 3)
* **border** - border around the pictures in pixels (default is 2)
* **maxtiles** - biggest allowed picture in tiles (default is 20)
* **tile** - fill available space with x px tiles. rows and cols are ignored. (no default)

= fsg_link keyword =

Adds link to group of images. eg. [fsg_link class="btn" include="112,113,114,115"]View[/fsg_link] See live example [here](http://torturedmind.org/misc-media/) (View Online -button).

* **include** - specify list of images (default is all images attached to post/page)
* **class** - class for a tag (default is none)
* **order** - ASC or DESC (default is ASC)
* **orderby** - See wordpress doc for all the options (default is post__in)

= keyboard shortcuts =

* esc - closes map/gallery
* left, P - Previous picture
* right, space, N - Next picture
* S - Start/stop slideshow
* M - Show map
* F - Fullscreen mode

== Screenshots ==

1. Fullscreen Galleria in action.
2. Random photobox using fsg_photobox keyword
3. Showing map for photos that have gps coordinates

== Changelog ==

= 1.3.4 =
* Don't show info box if empty.

= 1.3.3 =
* Update openlayers to 2.12

= 1.3.2 =
* Add order and orderby parameters in fsg_link
* Disable image navigation option

= 1.3.1 =
* Open attachment pages in FSG option

= 1.3 =
* Photo sharing buttons. Needs Jetpack to work.

= 1.2.4 =
* Work with W3 Total Cache.

= 1.2.3 =
* Show info also when navigating with keys.
* Parse string more carefully to avoid errors.

= 1.2.2 =
* Another try for the same bug.

= 1.2.1 =
* Trying to fix error where some images show in FSG and some not.

= 1.2 =
* enable/disable title and caption options
* True fullscreen option (experimental)

= 1.1.4 =
* fixing fsg_link again.

= 1.1.3 =
* fix fsg_link when there is no include.

= 1.1.2 =
* fix uninstall.

= 1.1.1 =
* fix settings again.

= 1.1 =
* maxtiles and tile options added to photobox.
* Fix settings interface

= 1.0 =
* Settings page added.
* Fix exposure time.

= 0.6.6 =
* Fill camera info from exif on upload.
* Only show images that are linked to image file in carousel.

= 0.6.5 =
* Updated [Galleria](http://galleria.io/) to 1.2.9

= 0.6.4 =
* Fix ESC key. Patch from Vala.

= 0.6.3 =
* Check that javascript value is valid. Removes error in console.

= 0.6.2 =
* Fixing PDF loading, again.

= 0.6.1 =
* Check if external attachment really exists. Fixes PDF attachments.
* Check if exif support for PHP installed. Fixes media upload errors.

= 0.6.0 =
* Support for include option in gallery.
* Better keyboard support (see FAQ)
* Fullscreen mode (Press F). This needs some further testing.

= 0.5.7 =
* Handle quotes in camera info too.

= 0.5.6 =
* Updated [Galleria](http://galleria.io/) to 1.2.8. Swiping should work better.

= 0.5.5 =
* Fix permalink to use wordpress ids. Fixes ids when more pictures are added to existing post. Sadly breaks old permalinks.

= 0.5.4 =
* Make better json

= 0.5.3 =
* Show permalink/bookmark icon in image info

= 0.5.2 =
* Updated [Galleria](http://galleria.io/) to 1.2.7
* Permalink support (http://site/post/#0 opens first image to galleria)

= 0.5.1 =
* Fix gps coordinate uploading

= 0.5 =
* Add map for images that have gps coordinates

= 0.4 =
* Only scale images if they are larger than canvas

= 0.3 =
* Esc closes the fullscreen gallery

= 0.2 =
* Arrows and close buttons fade out (not move) so they work also when hidden
* fsg_link keyword added
* fsg_photobox keyword added

= 0.1 =
* Initial release
