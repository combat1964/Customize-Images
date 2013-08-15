Customize-Images
======

Info
======

* Name 	    Customize-Images
* Task 	    Image Crop & Cut with Caching
* Version 	1.2
* Date 	    2013
* Author 	Dario D. Müller
* Mail 	    mailme@dariodomi.de
* Homepage 	http://dariodomi.de
* GitHub 	https://github.com/DarioDomiDE
* Copyright (c) 2013 by Dario D. Müller

Installation
======

1. All files: .htaccess, index.php, class.customize-images.php and files-Folder
2. Copy the whole folder including all files to your webserver or localhost.
3. Visit in your browser: http://localhost/Customize-Images/files/testimage_w450_h250_c.jpg

How to use
===

1. This Project make access to images with direct build-in croping and scaling of files
2. It also represents a caching-functionality of the cropped & sliced images
3. Access to an Image as usual "domain.com/files/[custom-path]/filename.jpg"
4. All files (".htaccess" & php-files) can stored in directory "files/"

Browse to an image
===

* /path/imagename.jpg 	(get image with original width & height)
* /path/imagename_w400.jpg 	(get image with reduced width=400px)
* /path/imagename_h250.jpg 	(get image with reduced height=250px)
* /path/imagename_w400_h250.jpg 	(get image with width=400px and height=250px)
* /path/imagename_h250_w400_c.jpg 	(select available mode = crop)
* /path/imagename_h250_w500_d.jpg 	(select available mode = distort)
* /path/imagename_h250_w600_s.jpg 	(select available mode = scale)
* /path/imagename_w600.jpg?dev (caching is disabled if $env is 'DEV')

Mode
===

One Param of Filename can be a one-char string who set on of these Modes (crop- & distort-Modes requires both params (width and height)!)
* s -> scale (scaling: use width und height as max-values, maintain proportions, requires only one length-param)
* c -> crop (cropping: accept output data width and height, maintain proportions, cut too much of the scene)
* d -> distort (distortion: use exactly width and height values)

Inspiration: Adaptive-Images
===

* Version 	1.5.2
* Homepage 	http://adaptive-images.com
* GitHub 	https://github.com/MattWilcox/Adaptive-Images
