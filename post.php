<?php
	require 'inc/functions.php';
	require 'inc/display.php';
	require 'inc/template.php';
	require 'inc/config.php';
	require 'inc/user.php';
	
	$board = Array(
		'url' => '/b/',
		'name' => 'Beta',
		'title' => 'In devleopment.');
	
	$body = '';
	
	// Fix for magic quotes
	if (get_magic_quotes_gpc()) {
		function strip_array(&$var) {
			return is_array($var) ? array_map("strip_array", $var) : stripslashes($var);
		}
		
		$_SESSION = strip_array($_SESSION);
		$_GET = strip_array($_GET);
		$_POST = strip_array($_POST);
	}
	
	if(isset($_POST['post'])) {
		if(	!isset($_POST['name']) ||
			!isset($_POST['email']) ||
			!isset($_POST['subject']) ||
			!isset($_POST['body']) ||
			!isset($_POST['password'])
			) error(ERROR_BOT);
		
		$post = Array();
		
		if(isset($_POST['thread'])) {
			$OP = false;
			$post['thread'] = round($_POST['thread']);
		} else $OP = true;
		
		if(!(($OP && $_POST['post'] == BUTTON_NEWTOPIC) ||
		    (!$OP && $_POST['post'] == BUTTON_REPLY)))
			error(ERROR_BOT);
		
		// Check the referrer
		if($OP) {
			if(!isset($_SERVER['HTTP_REFERER']) || !preg_match(URL_MATCH, $_SERVER['HTTP_REFERER'])) error(ERROR_BOT);
		}
		
		
		
		// TODO: Since we're now using static HTML files, we can't give them cookies on their first page view
		// Find another anti-spam method.
		
		/*
		// Check if he has a valid cookie.
		if(!$user['valid']) error(ERROR_BOT);
		
		// Check how long he has been here.
		if(time()-$user['appeared']<LURKTIME) error(ERROR_LURK);
		*/
		
		// Check for a file
		if($OP) {
			if(!isset($_FILES['file']['tmp_name']) || empty($_FILES['file']['tmp_name']))
				error(ERROR_NOIMAGE);
		}
		
		$post['name'] = (!empty($_POST['name'])?$_POST['name']:'Anonymous');
		$post['subject'] = $_POST['subject'];
		$post['email'] = utf8tohtml($_POST['email']);
		$post['body'] = $_POST['body'];
		$post['password'] = $_POST['password'];
		$post['filename'] = $_FILES['file']['name'];
		$post['has_file'] = $OP || !empty($_FILES['file']['tmp_name']);
		
		if($post['has_file'] && $_FILES['file']['size'] > MAX_FILESIZE)
			error(ERR_FILSIZE);
		
		$trip = generate_tripcode($post['name']);
		$post['name'] = $trip[0];
		$post['trip'] = (isset($trip[1])?$trip[1]:'');
		
		if($post['email'] == 'noko') {
			$noko = true;
			$post['email'] = '';
		} else $noko = false;
		
		if($post['has_file']) {
			$post['extension'] = strtolower(substr($post['filename'], strrpos($post['filename'], '.') + 1));
			$post['file_id'] = rand(0, 1000000000);
			$post['file'] = DIR_IMG . $post['file_id'] . '.' . $post['extension'];
			$post['thumb'] = DIR_THUMB . $post['file_id'] . '.png';
			if(!in_array($post['extension'], $allowed_ext)) error(ERROR_FILEEXT);
		}
		
		// Check string lengths
		if(strlen($post['name']) > 25) error(sprintf(ERROR_TOOLONG, 'name'));
		if(strlen($post['email']) > 30) error(sprintf(ERROR_TOOLONG, 'email'));
		if(strlen($post['subject']) > 40) error(sprintf(ERROR_TOOLONG, 'subject'));
		if(strlen($post['body']) > MAX_BODY) error(ERROR_TOOLONGBODY);
		if(!(!$OP && $post['has_file']) && strlen($post['body']) < 1) error(ERROR_TOOSHORTBODY);
		if(strlen($post['password']) > 20) error(sprintf(ERROR_TOOLONG, 'password'));
		
		
		
		markup($post['body']);
		
		if($post['has_file']) {
			// Just trim the filename if it's too long
			if(strlen($post['filename']) > 30) $post['filename'] = substr($post['filename'], 0, 27).'…';
			// Move the uploaded file
			if(!@move_uploaded_file($_FILES['file']['tmp_name'], $post['file'])) error(ERROR_NOMOVE);
			
			$size = @getimagesize($post['file']);
			$post['width'] = $size[0];
			$post['height'] = $size[1];
			
			// Check if the image is valid
			if($post['width'] < 1 || $post['height'] < 1) {
				unlink($post['file']);
				error(ERR_INVALIDIMG);
			}
			
			if($post['width'] > MAX_WIDTH || $post['height'] > MAX_HEIGHT) {
				unlink($post['file']);
				error(ERR_MAXSIZE);
			}
			
			$post['filehash'] = md5_file($post['file']);
			$post['filesize'] = filesize($post['file']);
			
			$image = createimage($post['extension'], $post['file']);
			
			if(REDRAW_IMAGE) {
				switch($post['extension']) {
					case 'jpg':
					case 'jpeg':
						imagejpeg($image, $post['file'], JPEG_QUALITY);
						break;
					case 'png':
						imagepng($image, $post['file'], 7);
						break;
					case 'gif':
						if(REDRAW_GIF)
							imagegif($image, $post['file']);
						break;
					case 'bmp':
						imagebmp($image, $post['file']);
						break;
					default:
						error('Unknwon file extension.');
				}
			}
			
			// Create a thumbnail
			$thumb = resize($image, $post['width'], $post['height'], $post['thumb'], THUMB_WIDTH, THUMB_HEIGHT);
			
			
			$post['thumbwidth'] = $thumb['width'];
			$post['thumbheight'] = $thumb['height'];
		}
		
		// Todo: Validate some more, remove messy code, allow more specific configuration
		
		// MySQLify
		sql_open();
		mysql_safe_array($post);
		
		if($OP) {
			mysql_query(
				sprintf("INSERT INTO `posts` VALUES ( NULL, NULL, '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%d', '%d', '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s' )",
					$post['subject'],
					$post['email'],
					$post['name'],
					$post['trip'],
					$post['body'],
					time(),
					time(),
					$post['thumb'],
					$post['thumbwidth'],
					$post['thumbheight'],
					$post['file'],
					$post['width'],
					$post['height'],
					$post['filesize'],
					$post['filename'],
					$post['filehash'],
					$post['password'],
					mysql_real_escape_string($_SERVER['REMOTE_ADDR'])
				), $sql) or error(mysql_error($sql));
		} else {
			mysql_query(
				sprintf("INSERT INTO `posts` VALUES ( NULL, '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%d', '%d', '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s' )",
					$post['thread'],
					$post['subject'],
					$post['email'],
					$post['name'],
					$post['trip'],
					$post['body'],
					time(),
					time(),
					$post['has_file']?$post['thumb']:null,
					$post['has_file']?$post['thumbwidth']:null,
					$post['has_file']?$post['thumbheight']:null,
					$post['has_file']?$post['file']:null,
					$post['has_file']?$post['width']:null,
					$post['has_file']?$post['height']:null,
					$post['has_file']?$post['filesize']:null,
					$post['has_file']?$post['filename']:null,
					$post['has_file']?$post['filehash']:null,
					$post['password'],
					mysql_real_escape_string($_SERVER['REMOTE_ADDR'])
				), $sql) or error(mysql_error($sql));
		}
		
		$id = mysql_insert_id($sql);
		buildThread(($OP?$id:$post['thread']));
		
		if(!$OP) {
			mysql_query(
				sprintf("UPDATE `posts` SET `bump` = '%d' WHERE `id` = '%s' AND `thread` IS NULL",
					time(),
					$post['thread']
				), $sql) or error(mysql_error($sql));
		}
		
		buildIndex();
		sql_close();
		
		if(ALWAYS_NOKO || $noko) {
			header('Location: ' . DIR_RES . ($OP?$id:$post['thread']) . '.html' . (!$OP?'#'.$id:''), true, 302);
		} else {
			header('Location: ' . ROOT . FILE_INDEX, true, 302);
		}
		
		exit;
	} else {
		if(!file_exists(FILE_INDEX)) {
			buildIndex();
			sql_close();
		}
		
		header('Location: ' . ROOT . FILE_INDEX, true, 302);
	}
?>
