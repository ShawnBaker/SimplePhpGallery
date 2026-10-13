# SimplePhpGallery

A very simple PHP photo/video gallery consisting of:
- a single PHP file (**index.php**)
- a single style sheet (**css/default.css**)
- a folder of images for the navigation buttons (**images**)
- a folder of photos and videos to be displayed (**pictures**)

## Installation

Installation is extremely simple, just upload the files listed above to your website.

## How It Works

The files and folders in the **pictures** folder are the contents of the gallery. The **pictures**folder is referred to as the Home folder. Each folder within **pictures** creates a sub-gallery using the name of that folder (which can contain spaces).

When a folder is first viewed, it is scanned for image and video files. For each image file, a thumbnail is created in the **thumbs** sub-folder. For each video file, if ffmpeg is installed, a thumbnail is created in the **thumbs** sub-folder. If ffmpeg is not install then a placeholder image is used as the video thumbnail. To get around this limitation, you can use ffmpeg to generate the video thumbnails yourself and then upload them to the appropriate **thumbs** sub-folder. A typical ffmpeg command looks like this:

```
ffmpeg -ss 5 -i filename.mp4 -vf "scale=240:-1" -vframes 1 -y thumbs/filename.png
```

When an image thumbnail is selected, the full image is displayed. When a video thumbnail is selected, the video is played.

## Configuration

The gallery can be configured in three ways:

- the PHP constants and variables at the top of the **index.php** file,
- the variables at the top of the **css/default.css** file,
- the **gallery.xml** files that can be placed in any folder within the **pictures** folder.

If nothing is changed and no configuration files are added (see below), then thumnails are 240 pixels wide and the only text ever displayed is the names of the sub galleries.

## Configuration Files

Each folder within (and including) the **pictures** folder may contain an optional configuration file named **gallery.xml**. This is an XML file that specifies a top level `<gallery>` element containing any or all of the following values:

```
<copyright>Copyright © ACME Industries</copyright>
<thumb_size>300</thumb_size>
```

and one or more `<item>` elements containing a `<file_name>` element and any or all of the following values:

```
<title>Some Image</title>
<description>A longer description of the image or video.</description>
<copyright>Copyright © John Smith</copyright>
<youtube_id>123456789</youtube_id>
```

The first three values apply to all items (images and videos). The `</youtube_id>` value only applies to videos.

An example can be found in the **pictures/Sub Gallery** folder.

## Playing Video Files Locally

This is usually a problem for small websites using basic web hosting plans. An alternative is to upload the videos to YouTube and then link them to the thumbnails within the gallery. This is done using configuration files named **gallery.xml** that can be placed in each of the folders within the pictures folder.
