<?php
/* * * INFO * *
	
	___ Project: Customize-Images ___
		Name:		Customize-Images
		Task:		Image Crop & Cut with Caching
		Version:	1.2
		Date:		2013
		Author:		Dario D. Müller
		Mail:		mailme@dariodomi.de
		Homepage:	http://dariodomi.de
		GitHub:		https://github.com/DarioDomiDE
		Copyright:	(c) 2013 by Dario D. Müller
	
	___ Inspiration: Adaptive-Images ___
		Version:   1.5.2
		Homepage:  http://adaptive-images.com
		GitHub:    https://github.com/MattWilcox/Adaptive-Images
	
	___ How to use ___
		This Project make access to images with direct build-in croping and scaling of files
		It also represents a caching-functionality of the cropped & sliced images
		Access to an Image as usual "domain.com/files/[custom-path]/filename.jpg"
		All files (".htaccess" & php-files) can stored in directory "files/"
	
	___ Browse to an image ___
		- /path/imagename.jpg					(get image with original width & height)
		- /path/imagename_w400.jpg				(get image with reduced width=400px)
		- /path/imagename_h250.jpg				(get image with reduced height=250px)
		- /path/imagename_w400_h250.jpg			(get image with width=400px and height=250px)
		- /path/imagename_h250_w400_c.jpg		(select available mode = crop)
		- /path/imagename_h250_w500_d.jpg		(select available mode = distort)
		- /path/imagename_h250_w600_s.jpg		(select available mode = scale)
		- /path/imagename_w600.jpg?dev			(caching is disabled if $env is 'DEV')
	
	___ Mode ___
		One Param of Filename can be a one-char string who set on of these Modes
		* crop- & distort-Modes requires both params (width and height)!
			s -> scale (scaling: use width und height as max-values, maintain proportions, requires only one length-param)
			c -> crop (cropping: accept output data width and height, maintain proportions, cut too much of the scene)
			d -> distort (distortion: use exactly width and height values)
	
*/

final class CustomizeImages {
	
	/* CONFIG */
	public static $cache_path = 'files/cache/'; // where to store the generated re-sized images. Specify from your document root!
	public static $jpg_quality = 75; // the quality of any generated JPGs, 0 to 100
	public static $sharpen = true; // Shrinking images can blur details, perform a sharpen on re-scaled images?
	public static $mode = 'SCALE'; // Image Mode
	public static $env = 'LIVE'; // DEV or LIVE Mode, in DEV Mode Caching is disabled if ?dev is behind url
	public static $specialDir = ''; // This value can set via your custom PHP-Application is a value between filedir and filename, set e.g. "path" if your path is "path/", no slash at the end

	/* get all of the required data from the HTTP request */
	private static $document_root;
	private static $requested_uri;
	private static $extension;
	private static $source_file;
	private static $cache_file;
	private static $imgName;
	private static $imgExt;
	private static $dirPermission;
	private static $resolution;
	private static $browser_cache;
	
	public static function initImage() {	 

		/* init data and check Request-URI */
		self::$document_root  = $_SERVER['DOCUMENT_ROOT'];
		self::$requested_uri  = parse_url(urldecode($_SERVER['REQUEST_URI']), PHP_URL_PATH);
		self::$extension	  = strtolower(pathinfo(self::$requested_uri, PATHINFO_EXTENSION));
		self::$source_file    = '/';
		self::$cache_file     = '';
		self::$imgName		  = null;
		self::$imgExt		  = null;
		self::$dirPermission  = 0755;
		self::$resolution	  = array(null, null);
		self::$browser_cache  = 60*60*24*7; // How long the BROWSER cache should last (seconds, minutes, hours, days. 7days by default)
		
		
		$parts = explode('.', self::$requested_uri);
		self::$imgExt = end($parts);
		$parts = explode('/', self::$requested_uri);
		
		self::$requested_uri = '';
		foreach($parts as $key => $val) {
			if($key == count($parts)-1) {
				$tmp = explode('.', $val);
				$tmp2 = explode('_', $tmp[0]);
				//unset($tmp2[0]);
				foreach($tmp2 as $key2 => $val2) {
						
					if(substr($val2, 0,1) == 'w' && is_numeric(substr($val2, 1)))
						self::$resolution[0] = substr($val2, 1);
					elseif(substr($val2, 0,1) == 'h' && is_numeric(substr($val2, 1)))
						self::$resolution[1] = substr($val2, 1);
					elseif(count($val2) === 1) {
						switch($val2) {
							case 's':
								self::$mode = 'SCALE';
								break;
							case 'c':
								self::$mode = 'CROP';
								break;
							case 'd':
								self::$mode = 'DISTORT';
								break;
							default:
								if($key2 === 0)
									self::$imgName = $val2;
								else
									self::$imgName .= '_'.$val2;
						}
					} else {
						if($key2 === 0)
							self::$imgName = $val2;
						else
							self::$imgName .= '_'.$val2;
					}
				}
			} else {
				self::$requested_uri .= $val.'/';
				if($key == count($parts)-2 && $key != 1)
					self::$cache_path .= $val.'/';
			}
		}
		
		if(substr(self::$requested_uri, -1) !== '/')
			self::$requested_uri .= '/';
		
		
		self::$cache_file = self::$cache_path;
		if(!empty(self::$specialDir)) {
			self::$cache_file .= self::$specialDir;
			self::$specialDir .= '/';
		}
		self::$requested_uri = self::$requested_uri.self::$specialDir.self::$imgName.'.'.self::$imgExt;
		
		
		
		if(!self::$resolution[0] || !self::$resolution[1]) {
			self::$mode = 'SCALE';
		}
		
		/* Generate Name of Cache-File */
		$tmp = explode('/', self::$requested_uri);
		$tmp = explode('.', end($tmp));
		self::$cache_file .= $tmp[0];
		if(self::$resolution[0])
			self::$cache_file .= '_w'.self::$resolution[0];
		if(self::$resolution[1])
			self::$cache_file .= '_h'.self::$resolution[1];
		self::$cache_file .= '_'.strtolower(substr(self::$mode, 0,1)).'.'.$tmp[1];

		if(isset($_GET['dev']) && MODE === 'DEV')
			self::$env = 'DEV';

		if(substr(self::$document_root, -1) === '/')
			self::$document_root = substr(self::$document_root, 0, -1);

		self::$source_file = self::$document_root.self::$requested_uri;
		
		
		

		// does the self::$cache_path directory exist already?
		if (!is_dir(self::$cache_path)) { // no
		  if (!mkdir(self::$cache_path, self::$dirPermission, true)) { // so make it
			if (!is_dir(self::$cache_path)) { // check again to protect against race conditions
			  // uh-oh, failed to make that directory
			  self::sendErrorImage("Failed to create cache directory at: ".self::$cache_path);
			}
		  }
		}
		
		// check if the file exists at all
		if (!file_exists(self::$source_file)) {
			if(!file_exists(self::$cache_file)) {
				header("Status: 404 Not Found");
			}
			self::sendImage(self::$cache_file);
			exit();
		}

		/* check that PHP has the GD library available to use for image re-sizing */
		if (!extension_loaded('gd')) { // it's not loaded
			if (!function_exists('dl') || !dl('gd.so')) { // and we can't load it either
				// no GD available, so deliver the image straight up
				trigger_error('You must enable the GD extension to make use of Adaptive Images', E_USER_WARNING);
				self::sendImage(self::$source_file);
			}
		}

		/* Use the resolution value as a path variable and check to see if an image of the same name exists at that path */
		if (self::$env !== 'DEV' && file_exists(self::$cache_file)) { // it exists cached at that size
			// compare cache and source modified dates to ensure the cache isn't stale
			self::refreshCache();
			self::sendImage(self::$cache_file);
		}
		
		/* It exists as a source file, and it doesn't exist cached - lets make one: */
		self::generateImage();
		self::sendImage(self::$cache_file);
	}
	
	/* helper function: Send headers and returns an image. */
	private static function sendImage($filename) {
		if (in_array(self::$extension, array('png', 'gif', 'jpeg'))) {
			header("Content-Type: image/".self::$extension);
		} else {
			header("Content-Type: image/jpeg");
		}
		header("Cache-Control: private static, max-age=".self::$browser_cache);
		header('Expires: '.gmdate('D, d M Y H:i:s', time()+self::$browser_cache).' GMT');
		header('Content-Length: '.filesize($filename));
		readfile($filename);
		exit();
	}
	
	/* helper function: Create and send an image with an error message. */
	private static function sendErrorImage($message) {
		/* get all of the required data from the HTTP request */
		$requested_file		  = basename(self::$requested_uri);
		self::$source_file    = self::$document_root.self::$requested_uri;
		
		$im            = ImageCreateTrueColor(800, 300);
		$text_color    = ImageColorAllocate($im, 233, 14, 91);
		$message_color = ImageColorAllocate($im, 91, 112, 233);
		
		ImageString($im, 5, 5, 5, "Adaptive Images encountered a problem:", $text_color);
		ImageString($im, 3, 5, 25, $message, $message_color);
		
		ImageString($im, 5, 5, 85, "Potentially useful information:", $text_color);
		ImageString($im, 3, 5, 105, "DOCUMENT ROOT IS: ".self::$document_root, $text_color);
		ImageString($im, 3, 5, 125, "REQUESTED URI WAS: ".self::$requested_uri, $text_color);
		ImageString($im, 3, 5, 145, "REQUESTED FILE WAS: ".$requested_file, $text_color);
		ImageString($im, 3, 5, 165, "SOURCE FILE IS: ".self::$source_file, $text_color);
		
		header('Cache-Control: no-store');
		header('Expires: '.gmdate('D, d M Y H:i:s', time()-1000).' GMT');
		header('Content-Type: image/jpeg');
		ImageJpeg($im);
		ImageDestroy($im);
		exit();
	}

	/* sharpen images function */
	private static function findSharp($intOrig, $intFinal) {
		$intFinal = $intFinal * (750.0 / $intOrig);
		$intA     = 52;
		$intB     = -0.27810650887573124;
		$intC     = .00047337278106508946;
		$intRes   = $intA + $intB * $intFinal + $intC * $intFinal * $intFinal;
		return max(round($intRes), 0);
	}

	/* refreshes the cached image if it's outdated */
	private static function refreshCache() {
		if (file_exists(self::$cache_file)) {
			if (filemtime(self::$cache_file) >= filemtime(self::$source_file)) {
				return self::$cache_file;
			}
		}
		self::generateImage();
	}

	/* generates the given cache file for the given source file with the given resolution */
	private static function generateImage() {

		// Check the original image dimensions
		$dimensions   = GetImageSize(self::$source_file);
		$width        = $dimensions[0];
		$height       = $dimensions[1];
		
		$resolution = self::$resolution;
		// Do we need to downscale the image?
		// ... the width of the source image is already less than the client width?
		if (($width <= $resolution[0] && $resolution[0] != null) && ($height <= $resolution[1] && $resolution[1] != null)) {
			return self::$source_file;
		}
		
		// We need to resize the source image to the width of the resolution breakpoint we're working with
		if(self::$mode === 'DISTORT') { // distort
			list($new_width, $new_height) = $resolution;
		} elseif(self::$mode === 'SCALE') { // scale
			$scaleProportion = '';
			if($resolution[0] != null && $resolution[1] != null) {
				$scaleProportion = ($width / $height < $resolution[0] / $resolution[1]) ? 'resizeWidth' : 'resizeHeight';
			}
			if(($resolution[0] != null && $resolution[1] == null) || $scaleProportion === 'resizeHeight') {
				$ratio      = $height/$width;
				$new_width  = $resolution[0];
				$new_height = ceil($new_width * $ratio);
			} elseif(($resolution[0] == null && $resolution[1] != null) || $scaleProportion === 'resizeWidth') {
				$ratio      = $width/$height;
				$new_height  = $resolution[1];
				$new_width = ceil($new_height * $ratio);
			} else {
				$new_width = $width;
				$new_height = $height;
			}
		}
		if(self::$mode === 'CROP') { // crop
			$src_x = $src_y = 0;
			list($new_width, $new_height) = $resolution;
			$cropProportion = ($width / $height > $resolution[0] / $resolution[1]) ? 'cropWidth' : 'cropHeight';
			if($cropProportion === 'cropWidth') {
				$ratio = $resolution[0]/$resolution[1];
				$src_x = ceil($height * $ratio - $width) * -1;
			} else {
				$ratio = $resolution[1]/$resolution[0];
				$src_y = ceil($width * $ratio - $height) * -1;
			}
		}
		
		$dst = ImageCreateTrueColor($new_width, $new_height); // re-sized image
		
		switch (self::$extension) {
			case 'png':
			  $src = @ImageCreateFromPng(self::$source_file); // original image was png
			break;
			case 'gif':
			  $src = @ImageCreateFromGif(self::$source_file); // original image was gif
			break;
			default:
			  $src = @ImageCreateFromJpeg(self::$source_file); // original image was jpeg
			  ImageInterlace($dst, true); // Enable interlancing (progressive JPG, smaller file-size)
			break;
		}

		if(self::$extension=='png'){
			imagealphablending($dst, false);
			imagesavealpha($dst,true);
			$transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
			imagefilledrectangle($dst, 0, 0, $new_width, $new_height, $transparent);
		}

		// do the resize in memory
		if(self::$mode === 'CROP') {
			// scale and crop at the same time, this was freaky hard!
			ImageCopyResampled($dst, $src, 0, 0, $src_x/2, $src_y/2, $new_width, $new_height, $width-$src_x, $height-$src_y);
		} else {
			ImageCopyResampled($dst, $src, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
		}
		
		ImageDestroy($src);
		
		// sharpen the image?
		// NOTE: requires PHP compiled with the bundled version of GD (see http://php.net/manual/en/function.imageconvolution.php)
		if(self::$sharpen === true && function_exists('imageconvolution')) {
			$intSharpness = self::findSharp($width, $new_width);
			$arrMatrix = array(
			  array(-1, -2, -1),
			  array(-2, $intSharpness + 12, -2),
			  array(-1, -2, -1)
			);
			imageconvolution($dst, $arrMatrix, $intSharpness, 0);
		}
		
		if (!is_writable(self::$cache_path)) {
			self::sendErrorImage("The cache directory is not writable: ".self::$cache_path);
		}
		
		// save the new file in the appropriate path, and send a version to the browser
		switch (self::$extension) {
			case 'png':
				$gotSaved = ImagePng($dst, self::$cache_file);
				break;
			case 'gif':
				$gotSaved = ImageGif($dst, self::$cache_file);
				break;
			default:
				$gotSaved = ImageJpeg($dst, self::$cache_file, self::$jpg_quality);
				break;
		}
		ImageDestroy($dst);
		
		if (!$gotSaved && !file_exists(self::$cache_file)) {
			self::sendErrorImage('Failed to create image: '.self::$cache_file);
		}
	}


}