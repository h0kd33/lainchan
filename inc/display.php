<?php
	/*
		Stuff to help with the display.
	*/
	
	
	/* 
		joaoptm78@gmail.com
		http://www.php.net/manual/en/function.filesize.php#100097
	*/
	function format_bytes($size) {
		$units = array(' B', ' KB', ' MB', ' GB', ' TB');
		for ($i = 0; $size >= 1024 && $i < 4; $i++) $size /= 1024;
		return round($size, 2).$units[$i];
	}
	
	function error($message) {
		die(Element('page.html', Array('index' => ROOT, 'title'=>'Error', 'subtitle'=>'An error has occured.', 'body'=>"<h1>$message</h1><p style=\"text-align:center;\"><a href=\"" . ROOT . FILE_INDEX . "\">Go back</a>.</p>")));
	}
	
	class Post {
		public function __construct($id, $thread, $subject, $email, $name, $trip, $body, $time, $thumb, $thumbx, $thumby, $file, $filex, $filey, $filesize, $filename) {
			$this->id = $id;
			$this->thread = $thread;
			$this->subject = utf8tohtml($subject);
			$this->email = $email;
			$this->name = utf8tohtml($name);
			$this->trip = $trip;
			$this->body = $body;
			$this->time = $time;
			$this->thumb = $thumb;
			$this->thumbx = $thumbx;
			$this->thumby = $thumby;
			$this->file = $file;
			$this->filex = $filex;
			$this->filey = $filey;
			$this->filesize = $filesize;
			$this->filename = $filename;
		}
		public function build($index=false) {
			$built =	'<div class="post reply"' . (!$index?' id="reply_' . $this->id . '"':'') . '>' . 
						'<p class="intro"' . (!$index?' id="' . $this->id . '"':'') . '>';
			
			// Subject
			$built .= '<span class="subject">' . $this->subject . '</span>';
			// Email
			if(!empty($this->email))
				$built .=  '<a class="email" href="mailto:' . $this->email . '">';
			// Name
			$built .= '<span class="name">' . $this->name . '</span>'
			// Trip
			. (!empty($this->trip) ? ' <span class="trip">'.$this->trip.'</span>':'');
			
			// End email
			if(!empty($this->email))
				$built .= '</a>';
			
			// Date/time
			$built .= ' ' . date('m/d/y (D) H:i:s', $this->time);
			
			$built .= ' <a class="post_no"' . 
			// JavaScript highlight
				($index?'':' onclick="highlightReply(' . $this->id . ');"') .
				' href="' . ROOT . DIR_RES . $this->thread . '.html' . '#' . $this->id . '">No.</a>' . 
			// JavaScript cite
				'<a class="post_no"' . ($index?'':'onclick="citeReply(' . $this->id . ');"') . 'href="' . ($index?ROOT . DIR_RES . $this->thread . '.html' . '#q' . $this->id:'javascript:void(0);') . '">'.$this->id.'</a>' . 
			'</p>';
		
			// File info
			if(!empty($this->file)) {
				$built .= '<p class="fileinfo">File: <a href="'	. ROOT . $this->file .'">' . basename($this->file) . '</a> <span class="unimportant">(' . 
			// Filesize
					format_bytes($this->filesize) . ', ' . 
			// File dimensions
					$this->filex . 'x' . $this->filey;
			// Aspect Ratio
				if(SHOW_RATIO) {
					$fraction = fraction($this->filex, $this->filey, ':');
					$built .= ', ' . $fraction;
				}
			// Filename
				$built .= ', ' . $this->filename . ')</span></p>' . 
			// Thumbnail
					'<a href="' . ROOT . $this->file.'"><img src="' . ROOT . $this->thumb.'" style="width:'.$this->thumbx.'px;height:'.$this->thumby.'px;" /></a>';
			}
			
			// Body
			$built .= '<p class="body">' . $this->body . '</p></div><br class="clear"/>';
			
			return $built;
		}
	};
	
	class Thread {
		public $omitted = 0;
		public function __construct($id, $subject, $email, $name, $trip, $body, $time, $thumb, $thumbx, $thumby, $file, $filex, $filey, $filesize, $filename) {
			$this->id = $id;
			$this->subject = utf8tohtml($subject);
			$this->email = $email;
			$this->name = utf8tohtml($name);
			$this->trip = $trip;
			$this->body = $body;
			$this->time = $time;
			$this->thumb = $thumb;
			$this->thumbx = $thumbx;
			$this->thumby = $thumby;
			$this->file = $file;
			$this->filex = $filex;
			$this->filey = $filey;
			$this->filesize = $filesize;
			$this->filename = $filename;
			$this->omitted = 0;
			$this->posts = Array();
		}
		public function add(Post $post) {
			$this->posts[] = $post;
		}
		
		
		public function build($index=false) {
			$built = '<p class="fileinfo">
		File: <a href="' . ROOT . $this->file.'">'.basename($this->file).'</a> <span class="unimportant">('.format_bytes($this->filesize).', '.$this->filex.'x'.$this->filey.', '.$this->filename.')</span>
	</p>
	<a href="' . ROOT . $this->file.'"><img src="' . ROOT . $this->thumb.'" style="width:'.$this->thumbx.'px;height:'.$this->thumby.'px;" /></a>
	<div class="post op">
		<p class="intro">
			<span class="subject">
				'.$this->subject.'
			</span>
			' . ( !empty($this->email) ? '<a class="email" href="mailto:' . $this->email . '">':'') .
			'<span class="name">'
				. $this->name . 
			'</span>' . (!empty($this->trip) ? ' <span class="trip">'.$this->trip.'</span>':'')
			. ( !empty($this->email) ? '</a>':'')
			. ' ' . date('m/d/y (D) H:i:s', $this->time). '
			<a class="post_no"' . ($index?'':' onclick="highlightReply(' . $this->id . ');"') . ' href="' . ROOT . DIR_RES . $this->id . '.html' . '#' . $this->id . '">No.</a><a class="post_no"' . ($index?'':'onclick="citeReply(' . $this->id . ');"') . 'href="' . ($index?ROOT . DIR_RES . $this->id . '.html' . '#q' . $this->id:'javascript:void(0);') . '">'.$this->id.'</a>' . ($index ? '<a href="' . ROOT . DIR_RES . $this->id . '.html">[Reply]</a>' : '') . 
		'</p>'
		.$this->body.'
		' . ($this->omitted ? '<span class="omitted">' . $this->omitted . ' post' . ($this->omitted==1?'':'s') . ' omitted. Click reply to view.</span>':'') . '
	</div>';
			foreach($this->posts as &$post) {
				$built .= $post->build($index);
			}
			$built .= '<br class="clear"/><hr/>';
			return $built;
		}
	};
?>