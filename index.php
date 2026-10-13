<!DOCTYPE HTML PUBLIC  "-//W3C//DTD HTML 4.0//EN">
<html>

<head>
<meta http-equiv=Content-Type content="text/html; charset=iso-8859-1">
<link rel="stylesheet" href="css/default.css?v=1">
<link rel="icon" type="image/x-icon" href="images/favicon.ico?v=1">
<title>Pictures</title>
</head>

<body>

<?php
	//************************************************************************
	//
	// SimplePhpGallery - Displays and navigates through a photo/video gallery.
	// Copyright (C) 2005-2026 Shawn Baker
	// https://www.frozen.ca
	//
	//************************************************************************

	// image paths
	const FFMPEG_PATH = "ffmpeg";
	const FFMPEG_SECONDS = 5;
	const ROOT_DIR = "pictures";
	const THUMBS_SUBDIR = "thumbs";

	const IMAGE_EMPTY_FOLDER = "images/empty_folder.svg";
	const IMAGE_IMAGE = "images/image.svg";
	const IMAGE_NAV_FIRST = "images/first.svg";
	const IMAGE_NAV_PREV = "images/prev.svg";
	const IMAGE_NAV_NEXT = "images/next.svg";
	const IMAGE_NAV_LAST = "images/last.svg";
	const IMAGE_NAV_UP = "images/up.svg";
	const IMAGE_NAV_FIRST_DISABLED = "images/first_disabled.svg";
	const IMAGE_NAV_PREV_DISABLED = "images/prev_disabled.svg";
	const IMAGE_NAV_NEXT_DISABLED = "images/next_disabled.svg";
	const IMAGE_NAV_LAST_DISABLED = "images/last_disabled.svg";
	const IMAGE_NAV_UP_DISABLED = "images/up_disabled.svg";
	const IMAGE_PLAY = "images/play.svg";
	const IMAGE_VIDEO = "images/video.svg";

	// allowable file name extensions
	$image_extensions = array("jpg", "jpeg", "png", "gif");
	$video_extensions = array("mp4");
	$extensions = array_merge($image_extensions, $video_extensions);

	// gallery settings
	$thumb_size = 240;
	$copyright = "";

	// item class
	class Item
	{
		var $file_name;
		var $title;
		var $description;
		var $copyright;
		var $youtube_id;
		var $is_video;
		var $has_thumb;
		var $thumb_file_name;
	}
	
	//************************************************************************
	//
	// get_files - Gets the subdirectories and files from a directory.
	//
	//************************************************************************
	function get_files($dir, &$dirs, &$items)
	{
		global $extensions, $video_extensions, $copyright;

		// open the directory
		$handle = opendir($dir);

		// create the directory and item arrays
		$dirs = array();
		$items = array();
		while (($file = readdir($handle)) !== false)
		{
			// skip the special directories
			if ($file != "." && $file != "..")
			{
				// if it's a directory then add it to the directories array
				if (is_dir("$dir/$file"))
				{
					if ($file != THUMBS_SUBDIR)
					{
						$dirs[] = $file;
					}
				}
				
				// otherwise, if it's a file then create a
				// new item and add it to the items array
				else
				{
					$file_array = explode(".", $file);
					$ext = strtolower(array_pop($file_array));
					if (array_search($ext, $extensions) !== false)
					{
						$item = new Item;
						$item->file_name = $file;
						$item->copyright = $copyright;
						$item->is_video = array_search($ext, $video_extensions) !== false;
						$item->has_thumb = false;
						$items[] = $item;
					}
				}
			}
		}

		// close the directory
		closedir($handle);
		
		// sort the directory and item arrays
		sort($dirs);
		sort($items);
	}

	//************************************************************************
	//
	// create_image_thumb - Creates a thumbnail for an image file.
	//
	//************************************************************************
	function create_image_thumb($dir, $item)
	{
		global $thumb_size;

		// load the image file
		$thumbs_dir = "$dir/" . THUMBS_SUBDIR;
		$ext = strtolower(pathinfo($item->file_name, PATHINFO_EXTENSION));
		$path = "$dir/" . $item->file_name;
		if ($ext == "jpg" || $ext == "jpeg")
		{
			$image = imagecreatefromjpeg($path);
		}
		elseif ($ext == "png")
		{
			$image = imagecreatefrompng($path);
		}
		elseif ($ext == "gif")
		{
			$image = imagecreatefromgif($path);
		}

		// get the thumb dimensions (maintain the aspect ratio)
		$iw = imageSX($image);
		$ih = imageSY($image);
		if ($iw > $ih)
		{
			$tw = $thumb_size;
			$th = $thumb_size * $ih / $iw;
		}
		elseif ($iw < $ih)
		{
			$tw = $thumb_size * $iw / $ih;
			$th = $thumb_size;
		}
		else
		{
			$tw = $thumb_size;
			$th = $thumb_size;
		}

		// create the thumbnail
		$thumb = ImageCreateTrueColor($tw, $th);
		imagecopyresampled($thumb, $image, 0, 0, 0, 0, $tw, $th, $iw, $ih);

		// save the thumbnail
		$thumb_path = "$thumbs_dir/" . $item->file_name;
		if ($ext == "jpg" || $ext == "jpeg")
		{
			imagejpeg($thumb, $thumb_path);
		}
		elseif ($ext == "png")
		{
			imagepng($thumb, $thumb_path);
		}
		elseif ($ext == "gif")
		{
			imagegif($thumb, $thumb_path);
		}

		// destroy the images
		imagedestroy($image);
		imagedestroy($thumb);

		// set the thumb file name
		$item->has_thumb = file_exists($thumb_path);
		if ($item->has_thumb)
		{
			$item->thumb_file_name = $thumb_path;
		}
	}

	//************************************************************************
	//
	// create_video_thumb - Creates a thumbnail for a video file.
	//
	//************************************************************************
	function create_video_thumb($dir, $item)
	{
		global $thumb_size;

		// create the FFMPEG command
		$thumbs_dir = "$dir/" . THUMBS_SUBDIR;
		$file_path = "$dir/" . $item->file_name;
		$thumb_path = "$thumbs_dir/" . pathinfo($item->file_name, PATHINFO_FILENAME) . ".png";
		$cmd = sprintf("%s -ss %d -i %s -vf \"scale=$thumb_size:-1\" -vframes 1 -y %s 2>&1",
						FFMPEG_PATH, FFMPEG_SECONDS, escapeshellarg($file_path),
    					escapeshellarg($thumb_path));

		// create the thumbnail by executing the FFMPEG command
		$output = [];
		$result = null;
		exec($cmd, $output, $result);

		// set the thumb file name
		$item->has_thumb = file_exists($thumb_path);
		if ($item->has_thumb)
		{
			$item->thumb_file_name = $thumb_path;
		}
	}

	//************************************************************************
	//
	// find_basename - Searchs for a file's basename in an array of items.
	//
	//************************************************************************
	function find_basename($file, $items)
	{
		$file_base = pathinfo($file, PATHINFO_FILENAME);
		$low = 0;
		$high = count($items) - 1;
		while ($low <= $high)
		{
			$mid = floor(($low + $high) / 2);
			$mid_base = pathinfo($items[$mid]->file_name, PATHINFO_FILENAME);
			if ($file_base == $mid_base)
			{
				return $mid;
			}
			elseif ($file_base < $mid_base)
			{
				$high = $mid - 1;
			}
			else
			{
				$low = $mid + 1;
			}
		}
		return false;
	}

	//************************************************************************
	//
	// get_gallery_info - Gets the gallery information from the gallery.xml file.
	//
	//************************************************************************
	function get_gallery_info($dir, &$items)
	{
		global $thumb_size, $copyright;
		
		// make sure there's a gallery.xml file
		if (!file_exists("$dir/gallery.xml"))
		{
			return;
		}

		// read the gallery.xml file
		$xml = simplexml_load_file("$dir/gallery.xml");
		if ($xml === false || strlen($xml) == 0)
		{
			return;
		}
		
		// read the thumb size
		if (isset($xml->thumb_size))
		{
			$thumb_size = (int)$xml->thumb_size;
		}

		// read the copyright message
		if (isset($xml->copyright))
		{
			$copyright = $xml->copyright;
			foreach ($items as $item)
			{
				$item->copyright = $copyright;
			}
		}

		// read the items
		if (isset($xml->item))
		{
			foreach ($xml->item as $item)
			{
				$i = array_search($item->file, array_column($items, 'file_name'));
				if ($i !== false)
				{
					if (isset($item->title))
					{
						$items[$i]->title = $item->title;
					}
					if (isset($item->description))
					{
						$items[$i]->description = $item->description;
					}
					if (isset($item->copyright))
					{
						$items[$i]->copyright = $item->copyright;
					}
					if (isset($item->youtube_id))
					{
						$items[$i]->youtube_id = $item->youtube_id;
					}
				}
			}
		}
	}

	//************************************************************************
	//
	// get_thumbs - Gets/creates/deletes thumbnails for a list of items.
	//
	//************************************************************************
	function get_thumbs($dir, &$items)
	{
		// create the thumbs directory if necessary
		$thumbs_dir ="$dir/" . THUMBS_SUBDIR;
		if (!is_dir($thumbs_dir))
		{
			mkdir($thumbs_dir, 0777);
		}

		// figure out which thumbs exist, delete those that don't correspond to an item
		$handle = opendir($thumbs_dir);
		while (($file = readdir($handle)) !== false)
		{
			if ($file != "." && $file != "..")
			{
				$index = find_basename($file, $items);
				if ($index === false)
				{
					unlink("$thumbs_dir/$file");
				}
				elseif (filemtime("$dir/" . $items[$index]->file_name) < filemtime("$thumbs_dir/$file"))
				{
					$items[$index]->has_thumb = true;
					$items[$index]->thumb_file_name = "$thumbs_dir/$file";
				}
			}
		}
		closedir($handle);

		// create any thumbs that don't exist
		for ($i = 0; $i < count($items); $i++)
		{
			if (!$items[$i]->has_thumb)
			{
				if ($items[$i]->is_video)
				{
					create_video_thumb($dir, $items[$i]);
				}
				else
				{
					create_image_thumb($dir, $items[$i]);
				}
			}
		}
	}

	//************************************************************************
	//
	// get_gallery_thumb - Gets the thumbnail for a gallery.
	//
	//************************************************************************
	function get_gallery_thumb($dir)
	{
		// get the directory and file names
		get_files($dir, $dirs, $items);

		// get/create/delete the thumbnails
		get_thumbs($dir, $items);

		// search for a thumbnail in this directory
		foreach ($items as $item)
		{
			if ($item->has_thumb)
			{
				return  $item->thumb_file_name;
			}
		}

		// search through the subdirectories
		foreach ($dirs as $subdir)
		{
			$thumb = get_gallery_thumb("$dir/$subdir");
			if ($thumb != IMAGE_EMPTY_FOLDER)
			{
				return $thumb;
			}
		}

		return IMAGE_EMPTY_FOLDER;
	}

	//************************************************************************
	//
	// get_gallery - Gets a gallery from a directory.
	//
	//************************************************************************
	function get_gallery($dir, &$dirs, &$items)
	{
		// get the directory and file names
		get_files($dir, $dirs, $items);

		// get/create/delete the thumbnails
		get_thumbs($dir, $items);

		// get the gallery.txt file information
		get_gallery_info($dir, $items);
		
		// remove the file extensions
		for ($i = 0; $i < count($items); $i++)
		{
			$file_name = $items[$i]->file_name;
			$path_parts = pathinfo($file_name);
			if ($path_parts['extension'] != '')
			{
				$n = strlen($items[$i]->file_name) - strlen($path_parts['extension']) - 1;
				$file_name = substr($items[$i]->file_name, 0, $n);
			}
			if ($items[$i]->title == $items[$i]->file_name)
			{
				$items[$i]->title = $file_name;
			}
			if ($items[$i]->description == $items[$i]->file_name)
			{
				$items[$i]->description = $file_name;
			}
		}
	}

	//************************************************************************
	//
	// show_path - Shows the gallery path as a set of links.
	//
	//************************************************************************
	function show_path($dir, $showing_item)
	{
		global $PHP_SELF;

		// if it's the root directory then just show the name
		echo "\t<div class='header-path'>\n";
		if ($dir == ROOT_DIR)
		{
			echo "\t\t<span>Home</span>";
		}

		// otherwise show links for the directories above this directory
		else
		{
			// show the home link
			$gallery = "$PHP_SELF?dir=" . urlencode(ROOT_DIR);
			echo "\t\t<a href=$gallery&page=1>Home</a>";
			$parts = explode("/", $dir);

			// show the sub links
			for ($i = 1; $i < count($parts) - 1; $i++)
			{
				$gallery .= "/" . urlencode($parts[$i]);
				echo "<span> &raquo; </span>\n";
				echo "\t\t<a href=$gallery>$parts[$i]</a>";
			}

			// show the current directory
			echo "<span> &raquo; </span>\n";
			if ($showing_item)
			{
				$gallery .= "/" . urlencode($parts[$i]);
				echo "\t\t<a href=$gallery>$parts[$i]</a>\n";
			}
			else
			{
				echo "\t\t<span>" . $parts[count($parts) - 1] . "</span>\n";
			}
		}
		echo "\t</div>\n";
	}

	//************************************************************************
	//
	// show_dir_nav - Shows the navigation buttons for a directory.
	//
	//************************************************************************
	function show_dir_nav($dir)
	{
		global $PHP_SELF;

		if ($dir != ROOT_DIR)
		{
			$parent = dirname($dir);
			get_files($parent, $dirs, $items);
			$i = array_search(basename($dir), $dirs);
			if ($i > 0)
			{
				$first = "$parent/$dirs[0]";
				$prev = "$parent/" . $dirs[$i - 1];
			}
			if ($i < count($dirs) - 1)
			{
				$next = "$parent/" . $dirs[$i + 1];
				$last = "$parent/" . end($dirs);
			}
		}

		// start the navigation section
		echo "\t<div class='header-nav'>\n";

		// show the first and prev buttons
		if (empty($first))
		{
			echo "\t\t<img src='" . IMAGE_NAV_FIRST_DISABLED . "'>\n";
			echo "\t\t<img src='" . IMAGE_NAV_PREV_DISABLED . "'>\n";
		}
		else
		{
			echo "\t\t<a href=$PHP_SELF?dir=" . urlencode($first) . "><img src='" . IMAGE_NAV_FIRST . "'></a>\n";
			echo "\t\t<a href=$PHP_SELF?dir=" . urlencode($prev) . "><img src='" . IMAGE_NAV_PREV . "'></a>\n";
		}

		// show the up button
		if (empty($parent))
		{
			echo "\t\t<img src='" . IMAGE_NAV_UP_DISABLED . "'>\n";
		}
		else
		{
			echo "\t\t<a href=$PHP_SELF?dir=" . urlencode($parent) . "><img src='" . IMAGE_NAV_UP . "'></a>\n";
		}

		// show the next and last buttons
		if (empty($last))
		{
			echo "\t\t<img src='" . IMAGE_NAV_NEXT_DISABLED . "'>\n";
			echo "\t\t<img src='" . IMAGE_NAV_LAST_DISABLED . "'>\n";
		}
		else
		{
			echo "\t\t<a href=$PHP_SELF?dir=" . urlencode($next) . "><img src='" . IMAGE_NAV_NEXT . "'></a>\n";
			echo "\t\t<a href=$PHP_SELF?dir=" . urlencode($last) . "><img src='" . IMAGE_NAV_LAST . "'></a>\n";
		}

		// end the navigation section
		echo "\t</div>\n";
	}

	//************************************************************************
	//
	// show_item_nav - Shows the navigation buttons for an item.
	//
	//************************************************************************
	function show_item_nav($dir, $index, $count)
	{
		global $PHP_SELF;

		// start the navigation section
		echo "\t<div class='header-nav'>\n";

		// show the first and prev buttons
		$self_dir = "$PHP_SELF?dir=" . urlencode($dir);
		if ($index > 1)
		{
			echo "\t\t<a href='$self_dir&item=1'><img src='" . IMAGE_NAV_FIRST . "'></a>\n";
			echo "\t\t<a href='$self_dir&item=" . ($index - 1) . "'><img src='" . IMAGE_NAV_PREV . "'></a>\n";
		}
		else
		{
			echo "\t\t<img src='" . IMAGE_NAV_FIRST_DISABLED . "'>\n";
			echo "\t\t<img src='" . IMAGE_NAV_PREV_DISABLED . "'>\n";
		}

		// show the up button
		echo "\t\t<a href='$self_dir'><img src='" . IMAGE_NAV_UP . "'></a>\n";

		// show the next and last buttons
		if ($index < $count)
		{
			echo "\t\t<a href='$self_dir&item=" . ($index + 1) . "'><img src='" . IMAGE_NAV_NEXT . "'></a>\n";
			echo "\t\t<a href='$self_dir&item=$count'><img src='" . IMAGE_NAV_LAST . "'></a>\n";
		}
		else
		{
			echo "\t\t<img src='" . IMAGE_NAV_NEXT_DISABLED . "'>\n";
			echo "\t\t<img src='" . IMAGE_NAV_LAST_DISABLED . "'>\n";
		}

		// end the navigation section
		echo "\t</div>\n";
	}

	//************************************************************************
	//
	// show_thumbs - Shows the thumbnails for a directory.
	//
	//************************************************************************
	function show_thumbs($dir)
	{
		global $PHP_SELF;

		// get the gallery
		get_gallery($dir, $dirs, $items);
		$self_dir = "$PHP_SELF?dir=$dir/";

		// start the page, show the header and start the content
		echo "<div class='page'>\n\n";
		echo "<div class='header'>\n";
		show_path($dir, false);
		show_dir_nav($dir);
		echo "</div>\n";
		echo "\n<div class='content'>\n";

		// show the subdirectories
		if (count($dirs) > 0)
		{
			echo "\t<div class='thumbs-container'>\n";
			$sub_dir = "$PHP_SELF?dir=" . urlencode($dir . "/");
			for ($i = 0; $i < count($dirs); $i += 1)
			{
				echo "\t\t<div class='thumb'>";
				echo "<a href='$sub_dir" . urlencode($dirs[$i]) . "'>";
				$thumb = get_gallery_thumb("$dir/$dirs[$i]");
				if ($thumb == IMAGE_EMPTY_FOLDER)
				{
					echo "<img class='item' src='$thumb' width='240' height='120'>";
				}
				else
				{
					echo "<img class='item' src='$thumb'>";
				}
				echo "</a>";
				echo "<div>$dirs[$i]</div>";
				echo "</div>\n";
			}
			echo "\t</div>\n";
		}

		// show the thumbnails
		if (count($items) != 0)
		{
			echo "\t<div class='thumbs-container'>\n";
			$self_dir_item = "$PHP_SELF?dir=" . urlencode($dir) . "&item=";
			for ($i = 0; $i < count($items); $i += 1)
			{
				echo "\t\t<div class='thumb'>";
				echo "<a href='$self_dir_item" . ($i + 1) . "'>";
				if ($items[$i]->has_thumb)
				{
					if ($items[$i]->is_video)
					{
						echo "<div class='video-thumb-container'>";
						echo "<img class='video-thumb-image' src='" . $items[$i]->thumb_file_name . "'>";
						echo "<div class='video-thumb-overlay'>";
						echo "<img src='" . IMAGE_PLAY . "'>";
						echo "</div>";
						echo "</div>";
					}
					else
					{
						echo "<img class='item' src='" . $items[$i]->thumb_file_name ."'>";
					}
				}
				elseif ($items[$i]->is_video)
				{
					echo "<img class='item' src='" . IMAGE_VIDEO . "' width='240' height='120'>";
				}
				else
				{
					echo "<img class='item' src='" . IMAGE_EMPTY_FOLDER . "' width='240' height='120'>";
				}
				echo "</a>";
				if (!empty($items[$i]->title))
				{
					echo "<div>" . $items[$i]->title . "</div>";
				}
				echo "</div>\n";
			}
			echo "\t</div>\n";
		}

		echo "</div>\n\n";
		echo "</div>\n\n";
	}

	//************************************************************************
	//
	// show_item - Shows a specific item from a directory.
	//
	//************************************************************************
	function show_item($dir, $item)
	{
		// get the gallery
		get_gallery($dir, $dirs, $items);

		// start the page, show the header and start the content
		echo "<div class='page'>\n\n";
		echo "<div class='header'>\n";
		show_path($dir, true);
		show_item_nav($dir, $item, count($items));
		echo "</div>\n";
		echo "<div class='content'>\n";

		// start the item container
		echo "\t<div class='item-container'>\n";

		// show the item
		$item--;
		if ($items[$item]->is_video)
		{
			if (!empty($items[$item]->youtube_id))
			{
				echo "\t\t<div class='item-video'>";
				echo "<iframe src='https://youtube.com/embed/" . $items[$item]->youtube_id . "?autoplay=1&mute=1' frameborder='0' allowfullscreen></iframe>";
				echo "</div>\n";
			}
			else
			{
				echo "\t\t<video controls autoplay class='item' src='$dir/" . $items[$item]->file_name ."'>";
				echo "</video>\n";
			}
		}
		else
		{
			echo "\t\t<img class='item' src='$dir/" . $items[$item]->file_name ."'>\n";
		}

		// show the description
		if (!empty($items[$item]->title))
		{
			echo "\t\t<div class='item-title'>" . $items[$item]->title . "</div>\n";
		}
		if (!empty($items[$item]->description))
		{
			echo "\t\t<div class='item-description'>" . $items[$item]->description . "</div>\n";
		}

		// show the copyright message
		if (!empty($items[$item]->copyright))
		{
			echo "\t\t<div class='item-copyright'>" . $items[$item]->copyright . "</div>\n";
		}

		echo "\t</div>\n";
		echo "</div>\n\n";
		echo "</div>\n\n";
	}

	//************************************************************************
	//
	// mainline - Shows a directory of thumbnails or a specific item.
	//
	//************************************************************************

	// if there's no dir then show the root directory's thumbnails
	if (!isset($_GET['dir']))
	{
		show_thumbs(ROOT_DIR);
	}

	// if there's an item number then show that item
	elseif (isset($_GET['item']))
	{
		show_item($_GET['dir'], $_GET['item']);
	}

	// otherwise show the thumbnails
	else
	{
		show_thumbs($_GET['dir']);
	}
?>
</body>
</html>
