=== Fullscreen Galleria ===
Contributors: pdamsten
Donate link: http://torturedmind.org/misc-media/
Author URI: http://torturedmind.org/
Plugin URI: http://torturedmind.org/misc-media/
Tags: galleria, gallery, photography, images
Requires at least: 3.3
Tested up to: 3.3.1
Stable tag: trunk
License: MIT

A simple fullscreen gallery to Wordpress

== Description ==

Fullscreen gallery for Wordpress. Based on [Galleria](http://galleria.io/) JavaScript image gallery framework.

#### Features

* Clean fullscreen interface. Only image and carousel is shown when idle.
* Custom link support for media eg. link to Flickr page that is shown for the image.

#### Usage

1. Use Wordpress Gallery feature and media as usual. Images are handled automatically and shown in fullscreen viewer.

== Installation ==

1. Install and activate Fullscreen Galleria using normal install. [More info](http://codex.wordpress.org/Managing_Plugins)

== Upgrade Notice ==

None

== Frequently Asked Questions ==

= fsg_photobox keyword =

Adds random photobox to the page. eg. [fsg_photobox include="244, 243,242,241,208,207,206,205,204" rows="6" cols="4"] See live example [here](http://torturedmind.org/photos/).

* **include** - specify list of images (default is all images attached to post/page)
* **rows** - maximum number of rows in the grid (default is 2)
* **cols** - maximum number of columns in the grid (default is 2)
* **border** - border around the pictures in pixels (default is 2)

= fsg_link keyword =

Adds link to group of images. eg. [fsg_link class="btn" include="112,113,114,115"]View[/fsg_link] See live example [here](http://torturedmind.org/misc-media/) (View Online -button).

* **include** - specify list of images (default is all images attached to post/page)
* **class** - class for a tag (default is none)

== Screenshots ==

1. Fullscreen Galleria in action.
2. Random photobox using fsg_photobox keyword
3. Showing map for photos that have gps coordinates

== Changelog ==

= 0.5.2 =
* Update [Galleria](http://galleria.io/) to 1.2.7
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
