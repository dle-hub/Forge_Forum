<?php
/*
=====================================================
 DataLife Engine Forum - Independent Upload Handler
=====================================================
*/

if( !defined( 'DATALIFEENGINE' ) ) {
	header( "HTTP/1.1 403 Forbidden" );
	header ( 'Location: ../../' );
	die( "Hacking attempt!" );
}

class ForumUploadFileViaFTP {

	private $path_file = "";
	private $file_name = "";
	
	public $error_code = false;
	public $force_replace = false;
	public $md5 = null;
	
	function __construct() {
		
	}

    function saveFile($path, $filename, $prefix=true, $force_prefix = false) {

        if( !DLEFiles::FileExists( "forum/" . $this->path_file . $filename ) ){
            return false;
        }

        return $this->path_file . $filename;
    }

    function getFileName() {
	
		$path = trim(str_replace(chr(0), '', (string)$_POST['ftpurl']));
		$path = str_replace(array('/', '\\'), '/', $path);

		if( !$path ) return '';
		
		if (preg_match('#\p{C}+#u', $path)) {
			return '';
		}
	
		$path_parts = pathinfo( $path );

		$this->file_name = $path_parts['basename'];
		
		$parts = array_filter(explode('/', $path_parts['dirname']), 'strlen');
		
		$absolutes = array();
		
		foreach ($parts as $part) {
			$part = trim($part);
			
			if ('.' == $part OR '..' == $part OR !$part) continue;
			
			$absolutes[] = $part;
		}
	
		$path = implode('/', $absolutes);
	
		if ( $path ) {
			$this->path_file = $path.'/';
		}

		return $this->file_name;
	
    }


    function getFileSize() {

		return DLEFiles::Size( "forum/" . $this->path_file . $this->file_name );

    }
	
    function getImage() {
        return ROOT_DIR . "/uploads/forum/" . $this->path_file . $this->file_name;
    }
	
}

class ForumUploadFileViaURL {  

	private $from = "";
	
	public $error_code = false;
	public $force_replace = false;
	public $md5 = null;
	
	function __construct() {
		
	}
	
    function saveFile($path, $filename, $auto_prefix = true, $force_prefix = false) {

		$file_prefix = "";
	
		if ( ($auto_prefix AND DLEFiles::FileExists( $path.$filename ) ) OR $force_prefix ) {

			$file_prefix = UniqIDReal()."_";

		}

		$filename = totranslit( $file_prefix.$filename );

		if( !DLEFiles::$error ) {
			
			$stream = @fopen( $this->from , 'rb');
			
			if (is_resource($stream)) {
				
				DLEFiles::WriteStream( $path.$filename, $stream);
				
			} else {
				
				DLEFiles::$error = 'PHP Error: Unable to open the stream with uploaded file';
				return false;
			
			}
			
			if (is_resource($stream)) {
				fclose($stream);
			}
			
			if( DLEFiles::$error ) return false;

		} else return false;

        return $filename;
    }
	
    function getFileName() {
		
		$img_extensions = array("gif", "jpg", "png", "jpeg", "webp", "bmp", "avif", "heic");

		$imageurl = trim( strip_tags((string)($_POST['imageurl'] ?? '') ) );
		$imageurl = str_replace(chr(0), '', $imageurl);
		$imageurl = str_replace( "\\", "/", $imageurl );
		
		if(!$imageurl) return '';
		
		$url = @parse_url ( $imageurl );
		
        if (!is_array($url) OR !isset($url['host']) OR !$url['host'] OR !isset($url['scheme']) OR !$url['scheme']) {
            return '';
        }

		if($url['scheme'] != 'http' AND $url['scheme'] != 'https') {

            return '';
		}

		if($url['host'] == 'localhost' OR $url['host'] == '127.0.0.1') {

            return '';
		}

		if( stripos ( $url['host'], $_SERVER['HTTP_HOST'] ) !== false ) {

			return '';

		}
		
		$name = basename((string)($url['path'] ?? ''));
		$extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
		
		if( !in_array( $extension, $img_extensions ) ) {

			$image = new thumbnail($imageurl);

			if (!$image->error) {
				$name = 'image_'.UniqIDReal().'.'.$image->format;
			} else {
				if (stripos($imageurl, ".php") !== false) return '';
				if (stripos($imageurl, ".phtm") !== false) return '';
			}
			
			unset($image);
		}

		$this->from = $imageurl;

		return $name;
    }
	
    function getFileSize() {

		$url = @parse_url( $this->from );

		if ( $url ) {
			
			if($url['scheme'] == "https" ) $port = 443; else $port = 80;

			$fp = @fsockopen( $url['host'], $port, $errno, $errstr, 10);

			if ($fp) {
				$x='';
	
				fputs($fp,"HEAD {$url['path']} HTTP/1.0\nHOST: {$url['host']}\n\n");
				while(!feof($fp)) $x.=fgets($fp,128);
				fclose($fp);

				if ( preg_match("#Content-Length: ([0-9]+)#i",$x,$size) ) {
					return intval($size[1]);
				} else {
					return strlen(@file_get_contents($this->from));
				}

			}

		}
		
		return 0;

    }
	
    function getImage() {
        return $this->from;
    }
	
}

class ForumUploadFileViaForm {
	
	public $error_code = false;
	public $force_replace = false;
	
	private $name;
	private $tmp_name;
	private $size;
	private $max_file_size;
	
	private $chunk;
	private $chunks;
	public  $chunk_tmp_name;
	public $md5 = null;
	private $field_name;
	
	function __construct() {
		global $config, $member_id, $user_group, $lang;

		// Hem plupload (qqfile) hem de TinyMCE/Froala (file) field adını destekle
		$field_name = 'qqfile';
		if (!isset($_FILES['qqfile']) && isset($_FILES['file'])) {
			$field_name = 'file';
		}

		$_FILES[$field_name]['name'] = isset($_FILES[$field_name]['name']) ? $_FILES[$field_name]['name'] : '';

		$this->chunk = isset($_REQUEST['chunk']) ? intval($_REQUEST['chunk']) : 0;
		$this->chunks = isset($_REQUEST['chunks']) ? intval($_REQUEST['chunks']) : 0;

		$this->name = isset($_REQUEST['name']) ? $_REQUEST['name'] : $_FILES[$field_name]['name'];
		$this->name = $this->getFileName();
		
		$this->tmp_name = isset($_FILES[$field_name]['tmp_name']) ? $_FILES[$field_name]['tmp_name'] : false;
		$this->size = isset($_FILES[$field_name]['size']) ? $_FILES[$field_name]['size'] : 0;
		
		if ( !$this->name ){
			die( json_encode(array('error' => $lang['file_def_1'] ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
        }

		if( $this->chunks > 1 ) {
			
			$this->chunk_tmp_name = ROOT_DIR . "/uploads/forum/".md5($this->name.$member_id['name'].SECURE_AUTH_KEY).'.tmp';
			
			$max_file_size = intval($config['max_up_size']);
			
			if( $user_group[$member_id['user_group']]['allow_file_upload'] ) {
	
				if( !intval($user_group[$member_id['user_group']]['max_file_size']) ) $max_file_size = 0;
				elseif( intval($user_group[$member_id['user_group']]['max_file_size']) > $max_file_size ) $max_file_size = intval($user_group[$member_id['user_group']]['max_file_size']);
	
			} elseif( !$max_file_size ) {
				$max_file_size = 20 * 1024 * 1024;
			}
	
			$this->max_file_size = $max_file_size * 1024;
			
			if( !$this->max_file_size ) $this->max_file_size = 1024 * 1024 * 1024;
		
		}
		
		if( $this->getErrorCode() ) {
			header( "HTTP/1.1 403 Forbidden" );
			die( json_encode(array('error' => $this->getErrorCode() ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );	
		}
		
		if (!$this->tmp_name || !is_uploaded_file($this->tmp_name) ) {
			die( json_encode(array('error' => $lang['file_def_1'] ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
		}

		// field_name'yi dışarı da sakla (getErrorCode için)
		$this->field_name = $field_name;
		
		if( $this->chunks > 1 ) {
			$this->uploadchunk();
		}
		
	}
	
    function saveFile($path, $filename, $auto_prefix = true, $force_prefix = false) {
		
		$file_prefix = "";
	
		if ( ($auto_prefix AND DLEFiles::FileExists( $path.$filename ) ) OR $force_prefix ) {

			$file_prefix = UniqIDReal()."_";

		}
		
		$filename = $file_prefix . $filename;

		if( !DLEFiles:: $error ) {
			
			$stream = @fopen( $this->tmp_name , 'rb');
			
			if (is_resource($stream)) {
				
				DLEFiles::WriteStream( $path.$filename, $stream);
				
			} else {
				
				DLEFiles::$error = 'PHP Error: Unable to open the stream with uploaded file';
				return false;
			
			}
			
			if (is_resource($stream)) {
				fclose($stream);
			}
			
			if( DLEFiles::$error ) return false;

		} else return false;

		$this->md5 = md5_file($this->tmp_name);

		if( $this->chunks > 1 ) {
			@unlink( $this->chunk_tmp_name );
			$this->chunk_tmp_name = '';
		}
		
		$this->cleanup_old_tmp();
		
        return $filename;
    }
	
    function cleanup_old_tmp(){
		
		$files = glob(ROOT_DIR . '/uploads/forum/*.tmp');

		foreach ($files as $tmpFile) {
			
			if (is_file($tmpFile)) {
				
				if (time() - filemtime($tmpFile) < (5 * 3600) ) {
					continue;
				}
				
				@unlink($tmpFile);
			
			}

		}
    }
	
	function uploadchunk() {
		global $lang;

		if (!$in = @fopen($this->tmp_name, "rb")) {
			header( "HTTP/1.1 403 Forbidden" );
			die( json_encode(array('error' => 'PHP Error: Unable to open the stream with uploaded file'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
		}
		
        if ( !$out = @fopen($this->chunk_tmp_name, $this->chunk ? "ab" : "wb" ) ) {
			header( "HTTP/1.1 403 Forbidden" );
            die( json_encode(array('error' => 'PHP Error: Unable to write uploaded file, check CHMOD for folder /uploads/forum/'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
        }
		
		while ($buff = fread($in, 4096)) {
			fwrite($out, $buff);
		}
		
		fflush($out);
		
        @fclose($in);	
        @fclose($out);
		
		clearstatcache(true, $this->chunk_tmp_name);
		$this->size = filesize( $this->chunk_tmp_name );
		
		if( $this->max_file_size AND $this->size > $this->max_file_size) {
			
			@unlink( $this->chunk_tmp_name );
			header( "HTTP/1.1 403 Forbidden" );
			die( json_encode(array('error' => $lang['files_too_big'] ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
			
		}
		
		if ($this->chunks == $this->chunk + 1) {
			
			$this->tmp_name = $this->chunk_tmp_name;
			
		} else {
			
			die( json_encode(array('result' => 'chunk uploaded'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
			
		}

	}
	
    function getFileName() {

		$path_parts = @pathinfo($this->name);

        return $path_parts['basename'];

    }
	
    function getFileSize() {
        return $this->size;
    }
	
    function getImage() {
        return array( 'tmp_name' => $this->tmp_name,  'name' => $this->getFileName() );
    }
	
    function getErrorCode() {

		$fn = isset($this->field_name) ? $this->field_name : 'qqfile';
		$error_code = isset($_FILES[$fn]['error']) ? $_FILES[$fn]['error'] : UPLOAD_ERR_NO_FILE;

		if ($error_code !== UPLOAD_ERR_OK) {

		    switch ($error_code) { 
		        case UPLOAD_ERR_INI_SIZE: 
		            $error_code = 'PHP Error: The uploaded file exceeds the upload_max_filesize directive in php.ini'; break;
		        case UPLOAD_ERR_FORM_SIZE: 
		            $error_code = 'PHP Error: The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form'; break;
		        case UPLOAD_ERR_PARTIAL: 
		            $error_code = 'PHP Error: The uploaded file was only partially uploaded'; break;
		        case UPLOAD_ERR_NO_FILE: 
		            $error_code = 'PHP Error: No file was uploaded'; break;
		        case UPLOAD_ERR_NO_TMP_DIR: 
		            $error_code = 'PHP Error: Missing a PHP temporary folder'; break;
		        case UPLOAD_ERR_CANT_WRITE: 
		            $error_code = 'PHP Error: Failed to write file to disk'; break;
		        case UPLOAD_ERR_EXTENSION: 
		            $error_code = 'PHP Error: File upload stopped by extension'; break;
		        default: 
		            $error_code = 'Unknown upload error';  break;
		    } 

		} else return false;

        return $error_code;
    }
}

class ForumFileUploader {

	private $allowed_extensions = array ("gif", "jpg", "jpeg", "png", "webp", "bmp", "avif", "heic");
	private $allowed_video = array("mp4", "mp3", "m4v", "m4a", "mov", "webm", "m3u8", "mkv", "flac", "aac", "ogg");
	private $allowed_files = array();
	private $area = "";
	private $author = "";
	private $news_id = "";
	private $t_size = "";
	private $t_seite = 0;
	private $make_thumb = true;
	private $m_size = "";
	private $m_seite = 0;
	private $make_medium = false;
	private $hidpi = 0;
	private $make_watermark = true;
	private $upload_path = "forum/";
	private $file = null;

    function __construct($area, $news_id, $author, $t_size, $t_seite, $make_thumb = true, $make_watermark = true, $m_size = 0, $m_seite = 0, $make_medium = false, $hidpi = false){        
		global $config, $db, $member_id, $user_group;

        $this->area = totranslit($area);

		// Forum ayarlarından dosya türlerini çek
		$forum_cfg = array();
		$db->query("SELECT name, value FROM " . PREFIX . "_forum_settings");
		while ($row = $db->get_row()) {
			$forum_cfg[$row["name"]] = stripslashes($row["value"]);
		}
		$forum_allowed_types = isset($forum_cfg['allowed_filetypes']) ? $forum_cfg['allowed_filetypes'] : "jpg,jpeg,png,gif,webp,pdf,zip,rar";
		$this->allowed_files = explode( ',', strtolower( $forum_allowed_types ) );

        $this->author = $db->safesql( $author );
        $this->news_id = intval($news_id);
        $this->t_size = $t_size;
        $this->t_seite = $t_seite;
        $this->make_thumb = $make_thumb;
        $this->m_size = $m_size;
        $this->m_seite = $m_seite;
        $this->make_medium = $make_medium;
        $this->make_watermark = $make_watermark;

		if( $hidpi ) $this->hidpi = 1; else $this->hidpi = 0;

		$ftp_upload_flag = false;
      
		if ( isset($_POST['imageurl']) AND $_POST['imageurl'] ) {

            $this->file = new ForumUploadFileViaURL();

        } elseif ( $member_id['user_group'] == 1 AND isset($_POST['ftpurl']) AND $_POST['ftpurl'] ) {

            $this->file = new ForumUploadFileViaFTP();
			$ftp_upload_flag = true;
			
        } else {

            $this->file = new ForumUploadFileViaForm();

        }

		define( 'FOLDER_PREFIX', date( "Y-m" )."/" );

		// Klasörlerin var olduğundan emin ol
		if( !is_dir(ROOT_DIR . '/uploads/forum/') ) { @mkdir(ROOT_DIR . '/uploads/forum/', 0777, true); }
		if( !is_dir(ROOT_DIR . '/uploads/forum/thumbs/') ) { @mkdir(ROOT_DIR . '/uploads/forum/thumbs/', 0777, true); }
		if( !is_dir(ROOT_DIR . '/uploads/forum/medium/') ) { @mkdir(ROOT_DIR . '/uploads/forum/medium/', 0777, true); }
		if( !is_dir(ROOT_DIR . '/uploads/forum/' . FOLDER_PREFIX) ) { @mkdir(ROOT_DIR . '/uploads/forum/' . FOLDER_PREFIX, 0777, true); }
		if( !is_dir(ROOT_DIR . '/uploads/forum/' . FOLDER_PREFIX . "thumbs/") ) { @mkdir(ROOT_DIR . '/uploads/forum/' . FOLDER_PREFIX . "thumbs/", 0777, true); }
		if( !is_dir(ROOT_DIR . '/uploads/forum/' . FOLDER_PREFIX . "medium/") ) { @mkdir(ROOT_DIR . '/uploads/forum/' . FOLDER_PREFIX . "medium/", 0777, true); }
    }

	private function check_filename( $filename ) {
		
		$filename = (string)$filename;
		
		if( !$filename ) return false;
			
		$filename = str_replace(chr(0), '', $filename);
		$filename = str_replace( "\\", "/", $filename );
		$filename = preg_replace( '#[.]+#i', '.', $filename );
		$filename = str_replace( "/", "", $filename );
		$filename = str_ireplace( "php", "", $filename );

		$filename_arr = explode( ".", $filename );
		
		if(count($filename_arr) < 2) {
			return false;
		}
		
		$type = totranslit( end( $filename_arr ) );
		
		if(!$type) return false;
		
		$curr_key = key( $filename_arr );
		
		unset( $filename_arr[$curr_key] );

		$filename = totranslit( implode( "_", $filename_arr ) );

		if( dle_strlen($filename ) > 200 ) {

			$filename = dle_substr( $filename, 0, 200 );
			
			if( ($temp_max = dle_strrpos( $filename, '-' )) ) $filename = dle_substr( $filename, 0, $temp_max );
		
		}

		if( !$filename ) {
			$filename = time() + rand( 1, 100 );
		}
		
		$filename = $filename . "." . $type;

		$filename = preg_replace( '#[.]+#i', '.', $filename );

		if( stripos ( $filename, ".php" ) !== false ) return false;
		if( stripos ( $filename, ".phtm" ) !== false ) return false;
		if( stripos ( $filename, ".shtm" ) !== false ) return false;
		if( stripos ( $filename, ".htaccess" ) !== false ) return false;
		if( stripos ( $filename, ".cgi" ) !== false ) return false;
		if( stripos ( $filename, ".htm" ) !== false ) return false;
		if( stripos ( $filename, ".ini" ) !== false ) return false;

		if( stripos ( $filename, "." ) === 0 ) return false;
		if( stripos ( $filename, "." ) === false ) return false;

		return $filename;

	}

	private function resolve_upload_driver($config_remote) {
		if (isset($_REQUEST['upload_driver']) && $_REQUEST['upload_driver'] !== '') {
			$driver = intval($_REQUEST['upload_driver']);
			return ($driver > -1) ? $driver : null;
		}
		$config_remote = intval($config_remote);
		return ($config_remote > -1) ? $config_remote : null;
	}

	private function sanitize_db_filename($raw_name, $fallback = '') {
		global $db;

		$bads = array('<', '>', ':', '«', '|', '?', '*', '[', ']', ';', '|', '=', '\\', ',', '/', '#', '%', '"', '\'');
		$db_file_name = str_replace(chr(0), '', strip_tags(html_entity_decode((string)$raw_name, ENT_QUOTES | ENT_HTML5, 'utf-8')));
		$db_file_name = str_ireplace($bads, '', $db_file_name);
		$db_file_name = preg_replace("/\s+/u", " ", $db_file_name);
		$db_file_name = trim($db_file_name);

		if (!$db_file_name && $fallback) {
			$db_file_name = $fallback;
		}

		return $db->safesql($db_file_name);
	}

	private function msg_error($message, $code = 500) {
		
		if( isset( $this->file->chunk_tmp_name ) AND $this->file->chunk_tmp_name ) {
			
			@unlink($this->file->chunk_tmp_name);
			$this->file->chunk_tmp_name = '';
			
		}
		
		return json_encode(array('error' => $message ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	
	}
	
	function FileUpload() {
		
		global $config, $db, $lang, $member_id, $user_group;
		
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
		
		$_IP = get_ip();
		$added_time = time();
		$xfvalue = "";
		$driver = null;
		$tinypng_error = false;
		$flink = false;
		$link = false;
		$commentsfileid = false;
		$uid = intval( $member_id['user_id'] );
		$now = date('Y-m-d H:i:s', $added_time);
		
		if (!$this->file){
			return $this->msg_error( $lang['upload_error_3'] );
        }

		$filename = $this->check_filename( $this->file->getFileName() );

		if ( !$filename ){
			return $this->msg_error( $lang['upload_error_4'] );
        }

		$filename_arr = explode( ".", $filename );
		$type = $file_type = end( $filename_arr );

		if ( !$type ){
			return $this->msg_error( $lang['upload_error_4'] );
        }
		
		$size = $this->file->getFileSize();
	
        if (!$size) {
            return $this->msg_error( $lang['upload_error_5'] );
        }

		// Forum ayarlarını kontrol et
		$forum_cfg = array();
		$db->query("SELECT name, value FROM " . PREFIX . "_forum_settings");
		while ($row = $db->get_row()) {
			$forum_cfg[$row["name"]] = stripslashes($row["value"]);
		}
		$max_attachment_mb = isset($forum_cfg['max_attachment_mb']) ? intval($forum_cfg['max_attachment_mb']) : 5;
			
		// Görsel uzantıları önce resim olarak işlenir (forum allowed_filetypes içinde png/jpg de olsa)
		if( in_array($type, $this->allowed_files ) && !in_array($type, $this->allowed_extensions) ) {

			if( $max_attachment_mb AND $size > ($max_attachment_mb * 1024 * 1024) ) {
				return $this->msg_error( $lang['files_too_big'] );
			}

			if( $user_group[$member_id['user_group']]['max_files'] ) {
				$row = $db->super_query( "SELECT COUNT(*) as count FROM " . PREFIX . "_forum_uploads WHERE user_id = '{$uid}' AND post_id = '{$this->news_id}'" );
				$count_files = $row['count'];
				if ($count_files AND $count_files >= $user_group[$member_id['user_group']]['max_files'] ) return $this->msg_error( $lang['error_max_files'] );
			}
			
			$this->upload_path = "forum/";
			$is_public = 1;
			$auto_prefix = false;
			$force_prefix = true;
			
			$driver = $this->resolve_upload_driver($config['files_remote']);
			DLEFiles::init( $driver, $config['local_on_fail'] );
			
			$uploaded_filename = $this->file->saveFile($this->upload_path . FOLDER_PREFIX, $filename, $auto_prefix, $force_prefix);

			if ( DLEFiles::$error ){
				return $this->msg_error( DLEFiles::$error );
			}
			
			if ( !$uploaded_filename ){
				return $this->msg_error( $lang['images_uperr_3'] );
			}
			
			$db_file_name = $this->sanitize_db_filename($this->file->getFileName(), $uploaded_filename);
			$base_name = pathinfo(str_replace(['\\', '/'], '', $this->file->getFileName()), PATHINFO_FILENAME);

			$size = DLEFiles::Size( $this->upload_path . FOLDER_PREFIX . $uploaded_filename );
			$driver = DLEFiles::$driver;

			if( !$this->file->md5 ) {
				$md5 = DLEFiles::Checksum($this->upload_path . FOLDER_PREFIX . $uploaded_filename);
			} else $md5 = $this->file->md5;

			$http_url = DLEFiles::GetBaseURL();

			if ($user_group[$member_id['user_group']]['allow_admin']) $db->query( "INSERT INTO " . USERPREFIX . "_admin_logs (name, date, ip, action, extras) values ('".$db->safesql($member_id['name'])."', '{$added_time}', '{$_IP}', '36', '{$uploaded_filename}')" );

			$file_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 56.054 66.35" width="66" height="76" class="file-icon file-ext-' . $file_type . '"><g transform="translate(-43.755 -32.246)"><path d="M82.585,33.746H53.6a8.342,8.342,0,0,0-8.342,8.342V88.754A8.342,8.342,0,0,0,53.6,97.1H89.966a8.342,8.342,0,0,0,8.342-8.342V49.469Z" fill="#f9f9f9" stroke="#cecece" stroke-miterlimit="10" stroke-width="3"></path><path d="M204.77,33.746v9.866a7.156,7.156,0,0,0,7.156,7.156h9.866Z" transform="translate(-123.189)" fill="#cecece"></path></g></svg>';
			$b_color = 'transparent';
			
			// Uzantı SVG icon seçimleri
			if (in_array($file_type, array('doc', 'docx'))) {
				$file_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 56.054 66.35" width="66" height="76" class="file-icon file-ext-' . $file_type . '"><g transform="translate(-588.829 -297.644)"><g transform="translate(545.074 265.397)"><path d="M82.585,33.746H53.6a8.342,8.342,0,0,0-8.342,8.342V88.754A8.342,8.342,0,0,0,53.6,97.1H89.966a8.342,8.342,0,0,0,8.342-8.342V49.469Z" fill="#fff" stroke="#2a60ae" stroke-miterlimit="10" stroke-width="3"></path><path d="M204.77,33.746v9.866a7.156,7.156,0,0,0,7.156,7.156h9.866Z" transform="translate(-123.189)" fill="#2a60ae"></path></g><g transform="translate(596.025 337.278)"><rect width="17.063" height="3.707" rx="1.853" transform="translate(0 5.926)" fill="#2a60ae"></rect><rect width="11.25" height="3.707" rx="1.853" transform="translate(0 11.851)" fill="#2a60ae"></rect><rect width="30.474" height="3.707" rx="1.853" fill="#2a60ae"></rect></g><path d="M3.42,0A.749.749,0,0,1,2.9-.181.723.723,0,0,1,2.66-.627L.627-12.787A.265.265,0,0,1,.608-12.9a.381.381,0,0,1,.124-.276.381.381,0,0,1,.275-.124H3.5q.551,0,.608.437L5.263-5.681,6.574-9.842a.62.62,0,0,1,.627-.513H8.626a.62.62,0,0,1,.627.513L10.564-5.7l1.178-7.163a.51.51,0,0,1,.171-.332.676.676,0,0,1,.418-.1H14.82a.372.372,0,0,1,.285.124.4.4,0,0,1,.114.276v.114L13.186-.627a.705.705,0,0,1-.247.446A.757.757,0,0,1,12.426,0H10.507a.69.69,0,0,1-.475-.152A.742.742,0,0,1,9.8-.494L7.923-5.757,6.042-.494A.908.908,0,0,1,5.8-.152.69.69,0,0,1,5.32,0Z" transform="translate(597 323)" fill="#2a60ae"></path></g></svg>';
				$b_color = '#e9eff7';
			}
			if (in_array($file_type, array('ppt', 'pptx'))) {
				$file_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 56.054 66.35" width="66" height="76" class="file-icon file-ext-' . $file_type . '"><g transform="translate(-236.224 -502.325)"><g transform="translate(192.469 470.079)"><path d="M82.585,33.746H53.6a8.342,8.342,0,0,0-8.342,8.342V88.754A8.342,8.342,0,0,0,53.6,97.1H89.966a8.342,8.342,0,0,0,8.342-8.342V49.469Z" fill="#fff" stroke="#c64122" stroke-miterlimit="10" stroke-width="3"></path><path d="M204.77,33.746v9.866a7.156,7.156,0,0,0,7.156,7.156h9.866Z" transform="translate(-123.189)" fill="#c64122"></path></g><path d="M1.767,0a.456.456,0,0,1-.332-.143.456.456,0,0,1-.143-.332V-12.806a.5.5,0,0,1,.133-.352.447.447,0,0,1,.342-.142H7.144a6.059,6.059,0,0,1,3.876,1.121,3.953,3.953,0,0,1,1.406,3.287,3.818,3.818,0,0,1-1.406,3.24A6.25,6.25,0,0,1,7.144-4.579H4.978v4.1a.472.472,0,0,1-.133.332A.447.447,0,0,1,4.5,0ZM7.049-7.3A1.747,1.747,0,0,0,8.275-7.7a1.554,1.554,0,0,0,.446-1.206,1.726,1.726,0,0,0-.408-1.2,1.613,1.613,0,0,0-1.264-.456H4.921V-7.3Z" transform="translate(245 527)" fill="#c64122"></path><g transform="translate(4 9)"><rect width="21.546" height="4.463" rx="2.232" transform="translate(249.483 542.098)" fill="#c64122"></rect><path d="M0,10V.03C.245.01.491,0,.74,0A9.443,9.443,0,0,1,10,9.615q0,.193-.008.385Z" transform="translate(261.791 518.347)" fill="#c64122" opacity="0.42"></path><path d="M10.5,21A10.519,10.519,0,0,1,2.8,3.33,10.461,10.461,0,0,1,9.664,0V10.9H21A10.51,10.51,0,0,1,10.5,21Z" transform="translate(250 519.053)" fill="#c64122"></path></g></g></svg>';
				$b_color = '#f9ebe8';
			}
			if (in_array($file_type, array('xls', 'xlsx'))) {
				$file_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 56.054 66.35" width="66" height="76" class="file-icon file-ext-' . $file_type . '"><g transform="translate(-410.326 -502.325)"><g transform="translate(366.571 470.079)"><path d="M82.585,33.746H53.6a8.342,8.342,0,0,0-8.342,8.342V88.754A8.342,8.342,0,0,0,53.6,97.1H89.966a8.342,8.342,0,0,0,8.342-8.342V49.469Z" fill="#fff" stroke="#209c61" stroke-miterlimit="10" stroke-width="3"></path><path d="M204.77,33.746v9.866a7.156,7.156,0,0,0,7.156,7.156h9.866Z" transform="translate(-123.189)" fill="#209c61"></path></g><path d="M.589,0A.381.381,0,0,1,.313-.124.381.381,0,0,1,.19-.4.506.506,0,0,1,.247-.627L4.389-6.783.57-12.673A.506.506,0,0,1,.513-12.9a.381.381,0,0,1,.123-.276A.381.381,0,0,1,.912-13.3H3.819a.79.79,0,0,1,.684.418L6.65-9.576l2.223-3.306a.776.776,0,0,1,.665-.418h2.774a.382.382,0,0,1,.276.124.381.381,0,0,1,.123.276.506.506,0,0,1-.057.228L8.8-6.821l4.18,6.194a.506.506,0,0,1,.057.228.382.382,0,0,1-.124.276A.381.381,0,0,1,12.635,0h-3a.759.759,0,0,1-.665-.38L6.536-3.914,4.161-.38A.759.759,0,0,1,3.5,0Z" transform="translate(419 527)" fill="#209c61"></path><g transform="translate(-2.695 2.152)"><g transform="translate(421.695 536.07)"><rect width="6.546" height="4.463" rx="2" fill="#209c61"></rect><rect width="6.546" height="4.463" rx="2" transform="translate(7.851)" fill="#209c61"></rect><rect width="6.546" height="4.463" rx="2" transform="translate(15.701)" fill="#209c61"></rect><rect width="6.546" height="4.463" rx="2" transform="translate(15.701 6)" fill="#209c61"></rect><rect width="6.546" height="4.463" rx="2" transform="translate(15.701 12)" fill="#209c61"></rect><rect width="6.546" height="4.463" rx="2" transform="translate(7.851 6)" fill="#209c61"></rect><rect width="6.546" height="4.463" rx="2" transform="translate(7.851 12)" fill="#209c61"></rect><rect width="6.546" height="4.463" rx="2" transform="translate(0 6)" fill="#209c61"></rect><rect width="6.546" height="4.463" rx="2" transform="translate(0 12)" fill="#209c61"></rect></g></g></g></svg>';
				$b_color = '#e8f5ef';
			}
			if (in_array($file_type, array('txt'))) {
				$file_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 56.054 66.35" width="66" height="76" class="file-icon file-ext-' . $file_type . '"><g transform="translate(-934.326 -297.644)"><g transform="translate(890.571 265.397)"><path d="M82.585,33.746H53.6a8.342,8.342,0,0,0-8.342,8.342V88.754A8.342,8.342,0,0,0,53.6,97.1H89.966a8.342,8.342,0,0,0,8.342-8.342V49.469Z" fill="#fff" stroke="#c6c8db" stroke-miterlimit="10" stroke-width="3"></path><path d="M204.77,33.746v9.866a7.156,7.156,0,0,0,7.156,7.156h9.866Z" transform="translate(-123.189)" fill="#c6c8db"></path></g><g transform="translate(945.655 324.912)"><rect width="32.783" height="4.463" rx="2.232" transform="translate(0 14.27)" fill="#c6c8db"></rect><rect width="32.783" height="4.463" rx="2.232" transform="translate(0 7.135)" fill="#c6c8db"></rect><rect width="32.783" height="4.463" rx="2.232" fill="#c6c8db"></rect></g></g></svg>';
				$b_color = '#f8f8fb';
			}
			if (in_array($file_type, array('pdf'))) {
				$file_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 56.054 66.35" width="66" height="76" class="file-icon file-ext-' . $file_type . '"><g transform="translate(-760.79 -297.644)"><g transform="translate(717.035 265.397)"><path d="M82.585,33.746H53.6a8.342,8.342,0,0,0-8.342,8.342V88.754A8.342,8.342,0,0,0,53.6,97.1H89.966a8.342,8.342,0,0,0,8.342-8.342V49.469Z" fill="#fff" stroke="#fa3225" stroke-miterlimit="10" stroke-width="3"></path><path d="M204.77,33.746v9.866a7.156,7.156,0,0,0,7.156,7.156h9.866Z" transform="translate(-123.189)" fill="#fa3225"></path></g><g transform="translate(768.517 337.278)"><rect width="17.063" height="3.707" rx="1.853" transform="translate(0 5.926)" fill="#fa3225"></rect><rect width="11.25" height="3.707" rx="1.853" transform="translate(0 11.851)" fill="#fa3225"></rect><rect width="30.474" height="3.707" rx="1.853" fill="#fa3225"></rect></g><g transform="translate(762.773 294.187)"><path d="M49.9-138.9a7.264,7.264,0,0,1-3.09-3.893c.326-1.339.84-3.372.449-4.646a1.812,1.812,0,0,0-3.459-.492c-.362,1.324-.029,3.191.586,5.572a67.964,67.964,0,0,1-2.953,6.209c-.007,0-.007.007-.014.007-1.961,1.006-5.326,3.22-3.944,4.921a2.249,2.249,0,0,0,1.556.724c1.3,0,2.584-1.3,4.422-4.472a41.242,41.242,0,0,1,5.717-1.679,10.968,10.968,0,0,0,4.632,1.411,1.873,1.873,0,0,0,1.426-3.141C54.216-139.367,51.292-139.085,49.9-138.9Zm-11.137,6.969a10,10,0,0,1,2.526-2.909C39.713-132.326,38.758-131.877,38.758-131.935Zm6.788-15.841c.608,0,.55,2.67.145,3.394C45.329-145.54,45.336-147.776,45.546-147.776Zm-2.034,11.347a33.39,33.39,0,0,0,2.055-4.537,9.373,9.373,0,0,0,2.5,2.953A26.647,26.647,0,0,0,43.513-136.429Zm10.935-.413s-.413.492-3.1-.651C54.266-137.7,54.744-137.037,54.447-136.842Z" transform="translate(-29.503 163.391)" fill="#fa3225"></path></g></g></svg>';
				$b_color = '#ffeae8';
			}
			if (in_array($file_type, array('apk'))) {
				$file_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="85" class="file-icon file-ext-' . $file_type . '"><g id="file__x2C__apk__x2C__android__x2C_"><g id="Layer_36"><g><g><polygon points="107.07,25.535 349.078,25.535 420.6,97.777 420.6,447.809 107.07,447.809" style="fill-rule:evenodd;clip-rule:evenodd;fill:#FFFFFF;"/><path d="M420.6,454.668H107.07c-3.79,0-6.859-3.07-6.859-6.859V25.535c0-3.791,3.069-6.86,6.859-6.86 h242.008c1.831,0,3.583,0.729,4.877,2.032l71.522,72.243c1.271,1.285,1.986,3.019,1.986,4.827v350.031 C427.464,451.598,424.39,454.668,420.6,454.668z M113.931,440.949h299.813v-340.35l-67.525-68.204H113.931V440.949z" style="fill:#4C8CF9;"/></g><g><rect height="105.598" style="fill-rule:evenodd;clip-rule:evenodd;fill:#4C8CF9;" width="313.531" x="129.979" y="177.687"/></g><g><path d="M213.491,258.4h-11.189v-9.82h11.189V258.4z M254.513,246.423h-19.446l-3.861,11.977h-11.189 l19.047-55.963h11.456L269.43,258.4h-11.187L254.513,246.423z M237.729,237.682h13.986l-6.927-22.062h-0.133L237.729,237.682z M285.815,238.086V258.4h-11.191v-55.963h22.112c6.392,0,11.319,1.615,15.05,4.974c3.598,3.23,5.461,7.534,5.461,12.917 s-1.863,9.687-5.461,12.917c-3.73,3.226-8.658,4.841-15.05,4.841H285.815z M285.815,229.473h10.921 c3.061,0,5.46-0.803,7.057-2.551c1.597-1.752,2.396-3.905,2.396-6.46c0-2.689-0.799-4.974-2.396-6.727 c-1.597-1.748-3.996-2.689-7.057-2.689h-10.921V229.473z M342.686,235.126h-6.13V258.4h-11.187v-55.963h11.187v22.87h4.8 l14.784-22.87h13.582l-18.776,26.1L371.19,258.4h-13.587L342.686,235.126z" style="fill:#FEFEFE;"/></g><g><path d="M263.497,146.43c-14.012,0-26.189-5.08-32.584-13.591c-2.275-3.028-1.665-7.328,1.365-9.604      c3.028-2.28,7.328-1.666,9.606,1.363c3.761,5.006,12.038,8.113,21.612,8.113c0.004,0,0.009,0,0.018,0 c9.691-0.005,18.639-3.263,22.269-8.108c2.276-3.038,6.575-3.648,9.604-1.372c3.032,2.271,3.647,6.571,1.376,9.604 c-6.286,8.379-19.02,13.592-33.239,13.596C263.515,146.43,263.506,146.43,263.497,146.43z" style="fill:#4C8CF9;"/></g><g><path d="M143.968,233.241c-0.025,0-0.051,0-0.078,0c-41.683-0.455-66.408-11.325-73.488-32.308 c-9.214-27.302,17.032-62.657,32.452-74.669c2.99-2.322,7.303-1.785,9.63,1.197c2.328,2.992,1.791,7.3-1.195,9.631 c-13.252,10.32-34.507,39.833-27.888,59.454c6.248,18.51,36.424,22.708,60.641,22.975c3.787,0.041,6.825,3.147,6.784,6.933 C150.783,230.216,147.721,233.241,143.968,233.241z" style="fill:#4C8CF9;"/></g><g><polygon points="349.078,97.777 420.6,97.777 349.078,25.535" style="fill-rule:evenodd;clip-rule:evenodd;fill:#D4E4FF;"/><path d="M420.6,104.637h-71.521c-3.79,0-6.86-3.07-6.86-6.86V25.535c0-2.781,1.68-5.286,4.249-6.346 c2.579-1.055,5.534-0.454,7.488,1.519l71.522,72.243c1.95,1.968,2.519,4.914,1.454,7.47 C425.867,102.976,423.371,104.637,420.6,104.637z M355.938,90.917h48.217l-48.217-48.703V90.917z" style="fill:#4C8CF9;"/></g><g><path d="M239.338,493.326c-0.364,0-0.731-0.027-1.104-0.088l-22.509-3.633      c-3.322-0.537-5.766-3.406-5.766-6.773v-35.023c0-3.791,3.069-6.859,6.859-6.859c3.791,0,6.86,3.068,6.86,6.859v29.182      l16.743,2.703c3.74,0.605,6.282,4.125,5.679,7.865C245.558,490.932,242.645,493.326,239.338,493.326z" style="fill:#4C8CF9;"/></g><g><path d="M325.249,493.326c-0.367,0-0.733-0.027-1.105-0.088l-22.507-3.633      c-3.322-0.537-5.768-3.406-5.768-6.773v-35.023c0-3.791,3.069-6.859,6.859-6.859s6.86,3.068,6.86,6.859v29.182l16.743,2.703      c3.74,0.605,6.282,4.125,5.681,7.865C331.467,490.932,328.554,493.326,325.249,493.326z" style="fill:#4C8CF9;"/></g><g><path d="M290.872,65.221c6.259-1.748,13.188,0.941,16.519,6.86      c4.13,7.13,1.73,16.142-5.332,20.18c-7.057,4.171-15.978,1.749-19.979-5.382c-3.193-5.516-2.396-12.375,1.469-16.95      c1.331,1.748,3.726,2.285,5.727,1.211C291.275,69.929,292.074,67.24,290.872,65.221L290.872,65.221z" style="fill-rule:evenodd;clip-rule:evenodd;fill:#4C8CF9;"/></g><g><path d="M228.808,65.221c6.259-1.748,13.185,0.941,16.649,6.86      c3.994,7.13,1.597,16.142-5.463,20.18c-6.924,4.171-15.981,1.749-19.979-5.382c-3.196-5.516-2.397-12.375,1.466-16.95      c1.331,1.748,3.861,2.285,5.727,1.211C229.34,69.929,230.005,67.24,228.808,65.221L228.808,65.221z" style="fill-rule:evenodd;clip-rule:evenodd;fill:#4C8CF9;"/></g><g><path d="M301.793,328.887l13.985-23.539c1.598-2.693,0.67-6.189-1.862-7.805      c-2.8-1.611-6.126-0.809-7.728,1.885l-14.784,24.889c-8.787-3.098-18.244-4.979-28.101-4.979c-9.855,0-19.18,1.881-28.104,4.979      l-14.648-24.889c-1.732-2.693-5.063-3.496-7.728-1.885c-2.664,1.615-3.595,5.111-1.996,7.805l13.983,23.539      c-27.038,14.123-45.551,42.508-45.551,75.334c0,3.23,2.531,5.648,5.594,5.648H341.75c3.203,0,5.598-2.418,5.598-5.648      C347.348,371.395,328.833,343.01,301.793,328.887L301.793,328.887z M190.716,398.572c2.797-37.938,34.361-67.938,72.588-67.938      c38.36,0,69.792,30,72.72,67.938H190.716z" style="fill:#4C8CF9;"/></g><g><path d="M341.75,415.656H184.854c-3.063,0-5.594,2.557-5.594,5.648v26.504h11.188v-20.852h145.714v20.852      h11.187v-26.504C347.348,418.213,344.953,415.656,341.75,415.656L341.75,415.656z" style="fill:#4C8CF9;"/></g><g><path d="M373.585,315.836c-10.787,0-19.579,8.885-19.579,19.777v107.758c0,1.479,0.133,3.092,0.532,4.438      h11.99c-0.799-1.211-1.331-2.826-1.331-4.438V335.613c0-4.574,3.859-8.475,8.521-8.475c4.528,0,8.392,3.9,8.392,8.475v107.758      c0,1.611-0.532,3.227-1.33,4.438h11.985c0.399-1.346,0.532-2.959,0.532-4.438V335.613      C393.298,324.721,384.506,315.836,373.585,315.836L373.585,315.836z" style="fill:#4C8CF9;"/></g><g><path d="M153.954,415.656c-10.79,0-19.579,8.879-19.579,19.777v12.375h11.187v-12.375      c0-4.709,3.864-8.477,8.525-8.477c4.529,0,8.391,3.768,8.391,8.477v12.375h11.189v-12.375      C173.667,424.535,164.875,415.656,153.954,415.656L153.954,415.656z" style="fill:#4C8CF9;"/></g><g><path d="M233.868,356.197c4.396,0,7.991,3.631,7.991,8.072      c0,4.438-3.595,8.07-7.991,8.07c-4.263,0-7.857-3.633-7.857-8.07C226.011,359.828,229.605,356.197,233.868,356.197      L233.868,356.197z" style="fill-rule:evenodd;clip-rule:evenodd;fill:#4C8CF9;"/></g><g><path d="M292.739,356.197c4.392,0,7.86,3.631,7.86,8.072      c0,4.438-3.469,8.07-7.86,8.07c-4.396,0-7.993-3.633-7.993-8.07C284.746,359.828,288.344,356.197,292.739,356.197      L292.739,356.197z" style="fill-rule:evenodd;clip-rule:evenodd;fill:#4C8CF9;"/></g></g></g></g><g /></svg>';
			$b_color = '#e9eff7';
			}
			if (in_array($file_type, array('7z', 'rar', 'zip', 'gz'))) {
				$file_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 56.054 66.35" width="66" height="76" class="file-icon file-ext-' . $file_type . '"><g transform="translate(-412.5 -297.644)"><g transform="translate(368.745 265.397)"><path d="M82.585,33.746H53.6a8.342,8.342,0,0,0-8.342,8.342V88.754A8.342,8.342,0,0,0,53.6,97.1H89.966a8.342,8.342,0,0,0,8.342-8.342V49.469Z" fill="#fff" stroke="#891fa8" stroke-miterlimit="10" stroke-width="3"></path><path d="M204.77,33.746v9.866a7.156,7.156,0,0,0,7.156,7.156h9.866Z" transform="translate(-123.189)" fill="#891fa8"></path></g><g transform="translate(435.02 313.808)"><g transform="translate(3.765)"><rect width="5.04" height="3.26" rx="1.63" transform="translate(3.26) rotate(90)" fill="#891fa8"></rect><rect width="5.04" height="3.26" rx="1.63" transform="translate(3.26 6.155) rotate(90)" fill="#891fa8"></rect><rect width="5.04" height="3.26" rx="1.63" transform="translate(3.26 12.31) rotate(90)" fill="#891fa8"></rect></g><path d="M0,9.67A6.031,6.031,0,0,1,2.219,4.906L3.406,0H6.984L8.238,5.015A6.057,6.057,0,0,1,10.316,9.67c0,3.207-2.307,5.8-5.156,5.8S0,12.877,0,9.67Z" transform="translate(0 18.548)" fill="#891fa8"></path><ellipse cx="2.5" cy="2" rx="2.5" ry="2" transform="translate(2.658 26.285)" fill="#fff"></ellipse></g></g></svg>';
				$b_color = '#f4e9f5';
			}
			
			if( in_array( $type, $this->allowed_video ) ) {
				if( in_array($file_type, array('mp3', 'flac', 'aac', 'ogg'))  ) {
					$file_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 56.054 66.35" width="66" height="76" class="file-icon file-ext-' . $file_type . '"><g transform="translate(-240.5 -297.644)"><g transform="translate(196.745 265.397)"><path d="M82.585,33.746H53.6a8.342,8.342,0,0,0-8.342,8.342V88.754A8.342,8.342,0,0,0,53.6,97.1H89.966a8.342,8.342,0,0,0,8.342-8.342V49.469Z" fill="#fff" stroke="#ffa734" stroke-miterlimit="10" stroke-width="3"></path><path d="M204.77,33.746v9.866a7.156,7.156,0,0,0,7.156,7.156h9.866Z" transform="translate(-123.189)" fill="#ffa734"></path></g><path d="M23.3-6.68H21.372l-.759-3.432a.778.778,0,0,0-.723-.574.778.778,0,0,0-.722.571l-1.179,5.2-1.225-8.7a.765.765,0,0,0-.735-.636.761.761,0,0,0-.737.653L14.2-4.319,12.61-15.984a.764.764,0,0,0-.735-.64.764.764,0,0,0-.735.64L9.551-4.318,8.456-13.6a.761.761,0,0,0-.737-.654.764.764,0,0,0-.735.638L5.76-4.908,4.582-10.114a.778.778,0,0,0-.722-.572.778.778,0,0,0-.723.573L2.378-6.68H.445A.445.445,0,0,0,0-6.234v.594A.445.445,0,0,0,.445-5.2H2.972a.772.772,0,0,0,.719-.575l.173-.74L5.215-.573A.719.719,0,0,0,5.966,0h.008a.769.769,0,0,0,.7-.637l.983-7.027L8.762,1.721a.742.742,0,0,0,1.473.013L11.875-10.3l1.64,12.037a.742.742,0,0,0,1.473-.013L16.1-7.664l.983,7.026a.771.771,0,0,0,.7.638.717.717,0,0,0,.755-.573L19.886-6.51l.173.739a.772.772,0,0,0,.72.576H23.3a.445.445,0,0,0,.445-.445v-.594A.445.445,0,0,0,23.3-6.68Z" transform="translate(256.344 339.5)" fill="#ffa734"></path></g></svg>';
					$b_color = '#fff6ea';
					$file_play = "audio";
				} else {
					$file_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 56.054 66.35" width="66" height="76" class="file-icon file-ext-' . $file_type . '"><g transform="translate(-586.74 -502.325)"><g transform="translate(542.985 470.079)"><path d="M82.585,33.746H53.6a8.342,8.342,0,0,0-8.342,8.342V88.754A8.342,8.342,0,0,0,53.6,97.1H89.966a8.342,8.342,0,0,0,8.342-8.342V49.469Z" fill="#fff" stroke="#04a0b2" stroke-miterlimit="10" stroke-width="3"></path><path d="M204.77,33.746v9.866a7.156,7.156,0,0,0,7.156,7.156h9.866Z" transform="translate(-123.189)" fill="#04a0b2"></path></g><g transform="translate(0.887 3.384)"><g transform="translate(603.613 524.116)"><path d="M3,16a3,3,0,0,1-3-3V3A3,3,0,0,1,3,0h8.3a3,3,0,0,1,3,3V5.943L20.471,2.1A1,1,0,0,1,22,2.944V13.055a1,1,0,0,1-1.529.849L14.3,10.057V13a3,3,0,0,1-3,3Z" fill="#04a0b2"></path></g></g></g></svg>';
					$b_color = '#e5f5f7';
					$file_play = "video";
				}
				$data_url = $http_url . $this->upload_path . FOLDER_PREFIX . $uploaded_filename;
			}
			
			// Forum Veritabanına Ekle
			$db->query( "INSERT INTO " . PREFIX . "_forum_uploads (user_id, post_id, filename, filepath, filesize, date, driver) values ('{$uid}', '{$this->news_id}', '{$db_file_name}', '/uploads/forum/". FOLDER_PREFIX ."{$uploaded_filename}', '{$size}', '{$now}', '{$driver}')" );
			$id = $db->insert_id();
			$del_name = "files";
			
			$size = formatsize($size);
			$data_url = $download_url = $http_url . $this->upload_path . FOLDER_PREFIX . $uploaded_filename;
			$file_path = "/uploads/forum/" . FOLDER_PREFIX . $uploaded_filename;

$return_box = <<<HTML
<div class="file-preview-card" data-type="file" data-area="{$del_name}" data-deleteid="{$id}" data-url="{$data_url}" data-path="{$file_path}" data-play="{$file_play}" data-public="{$is_public}">
	<div class="active-ribbon"><span><i class="mediaupload-icon mediaupload-icon-ok"></i></span></div>
	<div class="file-content" style="background-color: {$b_color};">
		<div class="file-ext">{$file_type}</div>{$file_icon}
	</div>
	<div class="file-footer">
		<div class="file-footer-caption">
			<div class="file-caption-info" rel="tooltip" title="ID: {$id}, {$db_file_name}">{$base_name}</div>
			<div class="file-size-info">({$size})</div>
		</div>
		<div class="file-footer-bottom">
			<div class="file-preview"><a href="{$download_url}" class="position-left" rel="tooltip" title="{$lang['plugins_a_3']}" target="_blank"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M7.646 10.854a.5.5 0 0 0 .708 0l2-2a.5.5 0 0 0-.708-.708L8.5 9.293V5.5a.5.5 0 0 0-1 0v3.793L6.354 8.146a.5.5 0 1 0-.708.708z"/><path d="M4.406 3.342A5.53 5.53 0 0 1 8 2c2.69 0 4.923 2 5.166 4.579C14.758 6.804 16 8.137 16 9.773 16 11.569 14.502 13 12.687 13H3.781C1.708 13 0 11.366 0 9.318c0-1.763 1.266-3.223 2.942-3.593.143-.863.698-1.723 1.464-2.383m.653.757c-.757.653-1.153 1.44-1.153 2.056v.448l-.445.049C2.064 6.805 1 7.952 1 9.318 1 10.785 2.23 12 3.781 12h8.906C13.98 12 15 10.988 15 9.773c0-1.216-1.02-2.228-2.313-2.228h-.5v-.5C12.188 4.825 10.328 3 8 3a4.53 4.53 0 0 0-2.941 1.1z"/></svg></a><a class="clipboard-copy-link" href="#" rel="tooltip" title="{$lang['up_im_copy']}"><i class="mediaupload-icon mediaupload-icon-copy"></i></a></div>
			<div class="file-delete"><a class="file-delete-link" href="#"><i class="mediaupload-icon mediaupload-icon-trash"></i></a></div>
		</div>
	</div>
</div>
HTML;

		} elseif ( in_array( $type, $this->allowed_extensions ) AND $user_group[$member_id['user_group']]['allow_image_upload'] ) {

			$min_size_upload = true;
			$hidpi_name ='';

			$driver = $this->resolve_upload_driver($config['image_remote']);
			DLEFiles::init( $driver, $config['local_on_fail'] );
			
			if( intval( $config['max_up_size'] ) AND $size > ((int)$config['max_up_size'] * 1024) ) {
				return $this->msg_error( $lang['images_big'] );
			}

			if( $user_group[$member_id['user_group']]['max_images'] ) {
				$row = $db->super_query( "SELECT COUNT(*) as count FROM " . PREFIX . "_forum_uploads WHERE user_id = '{$uid}' AND post_id = '{$this->news_id}'" );
				if( $row['count'] AND $row['count'] >= $user_group[$member_id['user_group']]['max_images'] ) return $this->msg_error( $lang['error_max_images'] );
			}
			
			include_once (ENGINE_DIR . '/classes/thumb.class.php');
			$image = new thumbnail( $this->file->getImage(), true, $min_size_upload );
			
			if ( $image->error ){
				return $this->msg_error( $image->error );
			}

			if ($this->hidpi) {
				$image->re_save = true;
				$image->size_auto(intval($image->width / 2), 1);
			}

			if (isset($_POST['imageurl']) && $_POST['imageurl']) {
				$image->re_save = true;
			}

			if ($config['max_up_side']) $image->size_auto($config['max_up_side'], $config['o_seite']);

			$dimension = $image->width . "x" . $image->height;

			if ($this->make_watermark) $image->insert_watermark($config['max_watermark']);

			if ($member_id['user_group'] != 1 OR $image->re_save) {
				$uploaded_filename = $image->save($this->upload_path . FOLDER_PREFIX . $filename, true);
			} else {
				if( $config['images_uniqid'] ) {
					$force_prefix = true;
				} else {
					$force_prefix = false;
				}
				$uploaded_filename = $this->file->saveFile($this->upload_path . FOLDER_PREFIX, $filename, true, $force_prefix);
			}

			if ($image->error) {
				return $this->msg_error($image->error);
			}

			if (DLEFiles::$error) {
				return $this->msg_error(DLEFiles::$error);
			}

			if (!$uploaded_filename) {
				return $this->msg_error($lang['images_uperr_3']);
			}

			if ($this->hidpi) {
				$hidpi_name = pathinfo($uploaded_filename, PATHINFO_FILENAME) . '@x2.' . pathinfo($uploaded_filename, PATHINFO_EXTENSION);
				if ($config['max_up_side']) $image->size_auto($config['max_up_side'], $config['o_seite'], $this->hidpi);
				if ($this->make_watermark) $image->insert_watermark($config['max_watermark'], $this->hidpi );
				$image->save($this->upload_path . FOLDER_PREFIX . $hidpi_name, false);
			}
			
			$raw_size = DLEFiles::Size( $this->upload_path . FOLDER_PREFIX . $uploaded_filename );
			$size = formatsize( $raw_size );
			$thumb_data = 0;
			$added_time = time();
		
			if( $this->make_thumb ) {
				if( $image->size_auto( $this->t_size, $this->t_seite, $this->hidpi ) ) {
					if( $this->make_watermark ) $image->insert_watermark( $config['max_watermark'], $this->hidpi );
					if( $this->hidpi ) {
						$image->save($this->upload_path . FOLDER_PREFIX . "thumbs/" . $hidpi_name, false);
						$image->size_auto($this->t_size, $this->t_seite);
						if ($this->make_watermark) $image->insert_watermark($config['max_watermark']);
						$image->save($this->upload_path . FOLDER_PREFIX . "thumbs/" . $uploaded_filename, false);
					} else {
						$image->save($this->upload_path . FOLDER_PREFIX . "thumbs/" . $uploaded_filename, false);
					}
					$thumb_data = 1;
				}
				if ( $image->error ){
					return $this->msg_error( $image->error );
				}
			}

			$medium_data = 0;
			if( $this->make_medium ) {
				if( $image->size_auto( $this->m_size, $this->m_seite, $this->hidpi ) ) {
					if( $this->make_watermark ) $image->insert_watermark( $config['max_watermark'], $this->hidpi );
					if ($this->hidpi) {
						$image->save($this->upload_path . FOLDER_PREFIX . "medium/" . $hidpi_name, false);
						$image->size_auto( $this->m_size, $this->m_seite);
						if ($this->make_watermark) $image->insert_watermark($config['max_watermark']);
						$image->save($this->upload_path . FOLDER_PREFIX . "medium/" . $uploaded_filename, false);
					} else {
						$image->save($this->upload_path . FOLDER_PREFIX . "medium/" . $uploaded_filename, false);
					}
					$medium_data = 1;
				}
				if ( $image->error ){
					return $this->msg_error( $image->error );
				}
			}
			
			if( $image->tinypng_error ) $tinypng_error = $image->tinypng_error;
			
			$http_url = DLEFiles::GetBaseURL();
			$driver = DLEFiles::$driver;

			$db_file_name = $this->sanitize_db_filename($this->file->getFileName(), $uploaded_filename);
			
			// Veritabanına Ekle
			$db->query( "INSERT INTO " . PREFIX . "_forum_uploads (user_id, post_id, filename, filepath, filesize, date, driver) values ('{$uid}', '{$this->news_id}', '{$db_file_name}', '/uploads/forum/". FOLDER_PREFIX ."{$uploaded_filename}', '{$raw_size}', '{$now}', '{$driver}')" );
			$id = $db->insert_id();
			$del_name = "images";
			
			if ($user_group[$member_id['user_group']]['allow_admin']) $db->query( "INSERT INTO " . USERPREFIX . "_admin_logs (name, date, ip, action, extras) values ('".$db->safesql($member_id['name'])."', '{$added_time}', '{$_IP}', '36', '{$uploaded_filename}')" );
			
			$img_url = $data_url = $link = $flink = $http_url . $this->upload_path . FOLDER_PREFIX . $uploaded_filename;
			$image_path = "/uploads/forum/" . FOLDER_PREFIX . $uploaded_filename;

			if( $medium_data ) {
				$img_url = 	$http_url . $this->upload_path . FOLDER_PREFIX . "medium/" . $uploaded_filename;
				$medium_data = "yes";
				$tm_url = $img_url;
			} else $medium_data = "no";

			if( $thumb_data ) {
				$img_url = 	$http_url . $this->upload_path . FOLDER_PREFIX . "thumbs/" . $uploaded_filename;
				$thumb_data = "yes";
				$th_url = $img_url;
			} else $thumb_data = "no";
			
			if ($this->hidpi) {
				$hidpi_data = " data-hidpi=\"{$hidpi_name}\"";
				$hidpi_url = str_replace($uploaded_filename, $hidpi_name, $data_url);
				$hidpi_url = " data-srcset=\"{$hidpi_url} 2x\"";
			} else { $hidpi_data = ''; $hidpi_url = '';}

			if($medium_data == "yes" ) $link = $tm_url;
			elseif( $thumb_data == "yes" ) $link = $th_url;
			else $flink = false;
			
			$display_name = explode("_", $uploaded_filename);
			if (count($display_name) > 1 AND strlen($display_name[0]) == 10) unset($display_name[0]);
			$display_name = implode("_", $display_name);
			
			$base_name = pathinfo($display_name, PATHINFO_FILENAME);
			$file_type = explode(".", $display_name);
			$file_type = totranslit(end($file_type));

$return_box = <<<HTML
<div class="file-preview-card" data-type="image" data-area="{$del_name}" data-deleteid="{$id}" data-url="{$data_url}" data-path="{$image_path}" data-thumb="{$thumb_data}" data-medium="{$medium_data}"{$hidpi_data}>
	<div class="active-ribbon"><span><i class="mediaupload-icon mediaupload-icon-ok"></i></span></div>
	<div class="file-content">
		<div class="file-ext">{$file_type}</div>
		<img src="{$img_url}" class="file-preview-image">
	</div>
	<div class="file-footer">
		<div class="file-footer-caption">
			<div class="file-caption-info" rel="tooltip" title="{$uploaded_filename}">{$base_name}</div>
			<div class="file-size-info">{$dimension} ({$size})</div>
		</div>
		<div class="file-footer-bottom">
			<div class="file-preview">
				<a href="{$data_url}"{$hidpi_url} data-highslide="single" target="_blank" rel="tooltip" title="{$lang['up_im_expand']}"><i class="mediaupload-icon mediaupload-icon-zoom"></i></a>
				<a class="clipboard-copy-link" href="#" rel="tooltip" title="{$lang['up_im_copy']}"><i class="mediaupload-icon mediaupload-icon-copy"></i></a>	
			</div>
			<div class="file-delete"><a class="file-delete-link" href="#"><i class="mediaupload-icon mediaupload-icon-trash"></i></a></div>
		</div>
	</div>
</div>
HTML;

			if( isset( $this->file->chunk_tmp_name ) AND $this->file->chunk_tmp_name ) {
				@unlink($this->file->chunk_tmp_name);
				$this->file->chunk_tmp_name = '';
			}

		} else return $this->msg_error( $lang['images_uperr_2'] );
		
		$return_url = isset($data_url) ? $data_url : (isset($link) && $link ? $link : '');
		$return_is_image = isset($del_name) && $del_name === 'images';

		$return_array = array (
			'success' => true,
			'returnbox' => $return_box,
			'uploaded_filename' => $uploaded_filename,
			'xfvalue' => $xfvalue,
			'link' => $link,
			'url' => $return_url,
			'is_image' => $return_is_image,
			'display_name' => isset($base_name) ? $base_name : '',
			'flink' => $flink,
			'commentsfileid' => $commentsfileid,
			'remote_error' => DLEFiles::$remote_error,
			'tinypng_error' => $tinypng_error
		);
		
		return json_encode($return_array, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

	}

}
?>
