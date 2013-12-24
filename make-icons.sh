#!/bin/bash

IMAGES="./"
INKSCAPE="/Applications/Inkscape.app/Contents/Resources/bin/inkscape"

$INKSCAPE --export-png=$IMAGES"fs-map.png" --export-area=0:0:264:24 --export-width=264 --export-height=24 --export-background=#000000 --export-background-opacity=0.0 "galleria-fs-icons.svg"

$INKSCAPE --export-png=$IMAGES"blank.gif" --export-area=0:0:1:1 --export-width=1 --export-height=1 --export-background=#000000 --export-background-opacity=0.0 "galleria-fs-icons.svg"

$INKSCAPE --export-png=$IMAGES"north-mini.png" --export-area=0:24:18:42 --export-width=18 --export-height=18 --export-background=#000000 --export-background-opacity=0.0 "galleria-fs-icons.svg"
$INKSCAPE --export-png=$IMAGES"west-mini.png" --export-area=18:24:36:42 --export-width=18 --export-height=18 --export-background=#000000 --export-background-opacity=0.0 "galleria-fs-icons.svg"
$INKSCAPE --export-png=$IMAGES"south-mini.png" --export-area=36:24:54:42 --export-width=18 --export-height=18 --export-background=#000000 --export-background-opacity=0.0 "galleria-fs-icons.svg"
$INKSCAPE --export-png=$IMAGES"east-mini.png" --export-area=54:24:72:42 --export-width=18 --export-height=18 --export-background=#000000 --export-background-opacity=0.0 "galleria-fs-icons.svg"

$INKSCAPE --export-png=$IMAGES"close-mini.png" --export-area=72:24:90:42 --export-width=18 --export-height=18 --export-background=#000000 --export-background-opacity=0.0 "galleria-fs-icons.svg"
$INKSCAPE --export-png=$IMAGES"zoom-plus-mini.png" --export-area=90:24:108:42 --export-width=18 --export-height=18 --export-background=#000000 --export-background-opacity=0.0 "galleria-fs-icons.svg"
$INKSCAPE --export-png=$IMAGES"zoom-minus-mini.png" --export-area=108:24:126:42 --export-width=18 --export-height=18 --export-background=#000000 --export-background-opacity=0.0 "galleria-fs-icons.svg"

$INKSCAPE --export-png=$IMAGES"slider.png" --export-area=126:24:146:33 --export-width=20 --export-height=9 --export-background=#000000 --export-background-opacity=0.0 "galleria-fs-icons.svg"
$INKSCAPE --export-png=$IMAGES"zoombar.png" --export-area=146:24:164:288 --export-width=18 --export-height=264 --export-background=#000000 --export-background-opacity=0.0 "galleria-fs-icons.svg"

$INKSCAPE --export-png=$IMAGES"marker.png" --export-area=164:24:199:76 --export-width=35 --export-height=52 --export-background=#000000 --export-background-opacity=0.0 "galleria-fs-icons.svg"
