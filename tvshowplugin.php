<?php
/*
Plugin Name: BHW Tv And Movie Site Plugin
Description: Update site with latest streaming TV shows and movies
Author: infTee
Version: 2.5
Author URI: https://www.blackhatworld.com/members/inftee.99316/
*/

register_activation_hook(__FILE__, 'initialise_tv_site_plugin');

function initialise_tv_site_plugin(){
	add_option('tvsitepoststatus', 'publish');
	add_option('tvsiteposttemplate', '<h3>Description</h3>\n###|DESCRIPTION|###\n<center>###|EMBEDVIDEO|###</center>\n###|EXTERNALLINKS|###');

	add_option('tvsiteexternallinkstemplate', '<h3>External Links</h3>\n###|EACHEXTERNALSTART|###<p><a href="###|THEEXTERNALLINK|###">###|THEEXTERNALLINKDOMAIN|###</a></p>###|EACHEXTERNALEND|###');
}

function tv_site_plugin_download_via_curl($url){
	set_time_limit(30);
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.1.16) Gecko/20121207 Iceweasel/3.5.16 (like Firefox/3.5.16)');
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,200); 
	curl_setopt($ch, CURLOPT_TIMEOUT, 200);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	
	$info = curl_getinfo($ch);

	$try = curl_exec($ch);
	curl_close($ch);
	if($try){
		return $try;
	}
	
	return false;
}

function tv_site_plugin_download_image_via_curl($url, $destination){
	$url = str_ireplace("http:http", "http", $url); 
	$url = str_ireplace("http:https:", "https:", $url);
	$url = str_ireplace("&amp;", "&", $url);
	$url = str_ireplace('\"', "", $url);
	$url = str_ireplace(" ", "%20", $url);
	
	set_time_limit(30);
	$fp = fopen($destination, 'w+');
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.1.16) Gecko/20121207 Iceweasel/3.5.16 (like Firefox/3.5.16)');
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 100); 
	curl_setopt($ch, CURLOPT_TIMEOUT, 200);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	
	$try = curl_exec($ch);
	curl_close($ch);
	if ($try) {
		$try = fwrite($fp, $try);
	}
	fclose($fp);
	
	if ($try) {
		return true;
	} else {
		return false;
	}
}

add_filter('cron_schedules','tv_site_plugin_schedules');

function tv_site_plugin_schedules($schedules){
	if(!isset($schedules["15min"])){
		$schedules["15min"] = array(
			'interval' => 15*60,
			'display' => __('Once every 15 minutes'));
	}

	if(!isset($schedules["20min"])){
		$schedules["20min"] = array(
			'interval' => 20*60,
			'display' => __('Once every 20 minutes'));
	}
	return $schedules;
}

register_deactivation_hook( __FILE__, 'tv_site_plugin_clear_schedule');

function tv_site_plugin_clear_schedule(){
	wp_clear_scheduled_hook('tv_site_plugin_series_hook');
	wp_clear_scheduled_hook('tv_site_plugin_movie_hook');
	wp_clear_scheduled_hook('tv_site_plugin_task_hook');
}


if (!wp_next_scheduled('tv_site_plugin_series_hook')) {
	wp_schedule_event(time(), '15min', 'tv_site_plugin_series_hook');
}

add_action('tv_site_plugin_series_hook', 'tv_site_update_series');

if (!wp_next_scheduled('tv_site_plugin_movie_hook')) {
	wp_schedule_event(time(), '20min', 'tv_site_plugin_movie_hook');
}

add_action('tv_site_plugin_movie_hook', 'tv_site_update_movies');

function tv_site_update_series(){
	tv_site_get_new_episodes();
}

function tv_site_update_movies(){
	tv_site_get_new_films();
}


function tv_site_get_new_episodes() {
	if(!get_option('tvsitecronenabled')){
		return;
	}

	$rawPage = tv_site_plugin_download_via_curl("http://itswatchseries.to/latest");
	if($rawPage){
		$listings = strstr($rawPage, '<ul class="listings">');
		$listings = strstr($listings, '</ul>', true);
		$episodes = array();

		preg_match_all('~<li>(.*?)</li>~', $listings, $episodeChunks);
		if($episodeChunks){
			$episodeChunks = array_reverse($episodeChunks[1]);
			foreach ($episodeChunks as $episodeData) {
				$originalUrl = strstr($episodeData, '<a href="');
				$originalUrl = str_ireplace('<a href="', '', strstr($originalUrl, '" title="', true));

				$meta_args = array(
					'meta_query' => array(
						array(
							'key' => 'originalUrl',
							'value' => $originalUrl
						)
					),
					'post_status' => 'any'
				);
		 
				$existingQuery = new WP_Query($meta_args);
				if(count($existingQuery->posts) >= 1){
					continue;
				}

				$title = strstr($episodeData, 'title="');
				$title = str_ireplace('title="', '', strstr($title, '"><span style="display: inline-block;', true));

				

				$rawEpisodePage = tv_site_plugin_download_via_curl($originalUrl);
				if($rawEpisodePage){
					$seriesName = strstr($rawEpisodePage, 'itemprop="url"><span itemprop="name">');
					$seriesName = str_ireplace('itemprop="url"><span itemprop="name">', '', strstr($seriesName, '</span></a>', true));

					preg_match_all('~href="http://itswatchseries.to/cale.html(.*?)" class="watchlink" title="~', $rawEpisodePage, $encodedLinks);
					if($encodedLinks){
						$image = strstr($rawEpisodePage, 'meta property="og:image" content="');
						$image = str_ireplace('meta property="og:image" content="', '', strstr($image, '" />', true));
						
						$description = strstr($rawEpisodePage, '<span id="short-desc" itemprop="description">');
						$description = str_ireplace('<span id="short-desc" itemprop="description">', '', strstr($description, '</span></p>', true));

						$links = array();
						foreach ($encodedLinks[1] as $encodedLink) {
							$realLink = base64_decode(str_ireplace('?r=', '', $encodedLink));
							$links[] = $realLink;
						}

						$episodes[] = array('title' => $title, 'image' => $image, 'description' => $description, 'links' => $links, 'originalUrl' => $originalUrl, 'seriesName' => $seriesName);
					}
				}

			}
		}
	}


	foreach ($episodes as $episode) {
		$postContent = "";
		$postTitle = $episode['title'];
		$embedCode = "";

		foreach ($episode['links'] as $link) {
			if(stripos($link, 'gorillavid.in') !== false){
				$urlInfo = parse_url($link);
				$id = str_ireplace('/', '', $urlInfo['path']);
				$embedCode = '<br><center><IFRAME SRC="http://gorillavid.in/embed-'.$id.'-960x480.html" FRAMEBORDER=0 MARGINWIDTH=0 MARGINHEIGHT=0 SCROLLING=NO WIDTH=960 HEIGHT=480></IFRAME></center>';
				break;
			} elseif (stripos($link, 'openload.co') !== false) {
				$link = str_ireplace('/f/', '/embed/', $link);
				$embedCode = '<br><center><iframe src="'.$link.'" scrolling="no" frameborder="0" width="700" height="430" allowfullscreen="true" webkitallowfullscreen="true" mozallowfullscreen="true"></iframe>';
				break;
			} elseif (stripos($link, 'vidzi.tv') !== false) {
				$link = str_ireplace('vidzi.tv/', 'vidzi.tv/embed-', $link);
				$embedCode = '<iframe src="'.$link.'" frameborder=0 marginwidth=0 marginheight=0 scrolling=no width=100% height=100% allowfullscreen></iframe>';
				break;
			}

		}

		$categoryId = 0;
		$term = term_exists($episode['seriesName'], 'category');
		if ($term === 0 || $term === null) {
			$categoryId = wp_create_category($episode['seriesName']);
		} else {
			$categoryId = $term['term_id'];
		}

		$status = get_option('tvsitepoststatus');

		remove_filter('content_save_pre', 'wp_filter_post_kses');
		remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');
		
		$try = wp_insert_post(array('post_content' => $postContent, 'post_title' => $postTitle, 'post_excerpt' => $episode['description'], 'post_author' => 1, 'post_status' => $status, 'post_category' => array((int)$categoryId)));
		
		add_filter('content_save_pre', 'wp_filter_post_kses');
		add_filter('content_filtered_save_pre', 'wp_filter_post_kses');

		if($try){
			add_post_meta($try, 'originalUrl', $episode['originalUrl'], true);
			add_post_meta($try, 'description', $episode['description'], true);
			add_post_meta($try, 'embedCode', $embedCode, true);
			add_post_meta($try, 'externalLinks', serialize($episode['links']), true);

			$wpUploadDir = wp_upload_dir();
			$wpUploadSource = $wpUploadDir['url'];
			$wpUploadDir = $wpUploadDir['path'];
			$newFilename = uniqid();

			$extension = pathinfo($episode['image'], PATHINFO_EXTENSION);
			if (stripos($extension, '?') !== false) {
				$extension = substr($extension, 0, stripos($extension, '?'));
			}

			$newFilename .= $try.".".$extension;

			$tryDownload = tv_site_plugin_download_image_via_curl($episode['image'], $wpUploadDir.'/'.$newFilename);
			$fileType = wp_check_filetype(basename($wpUploadDir.'/'.$newFilename), null);

			$attachment = array(
				'guid'				=> $wpUploadSource.'/'.basename($newFilename), 
				'post_mime_type'	=> $fileType['type'],
				'post_title'		=> preg_replace('/\.[^.]+$/', '', basename($newFilename)),
				'post_content'		=> '',
				'post_status'		=> 'inherit'
			);
			$attach_id = wp_insert_attachment($attachment, $wpUploadDir.'/'.$newFilename, $try);
			include_once ABSPATH . 'wp-admin/includes/image.php';

			$attach_data = wp_generate_attachment_metadata($attach_id, $wpUploadDir.'/'.$newFilename);
			wp_update_attachment_metadata($attach_id, $attach_data);

			$trySetThumb = set_post_thumbnail($try, $attach_id);
			$trySetMeta = update_post_meta($try, '_thumbnail_id', $attach_id);
		}
	}
}

function tv_site_get_new_films() {
	if(!get_option('tvsitefilmcronenabled')){
		return;
	}
	$rawPage = tv_site_plugin_download_via_curl("http://www.primewire.ch/");
	if($rawPage){
		preg_match_all('~<div class="index_item index_item_ie"><a href="(.*?)" title="~is', $rawPage, $filmLinks);
		if($filmLinks){
			$films = array();
			foreach ($filmLinks[1] as $filmLink) {
				$originalUrl = "http://www.primewire.ch/".$filmLink;
				
				$meta_args = array(
					'meta_query' => array(
						array(
							'key' => 'originalUrl',
							'value' => $originalUrl
						)
					),
					'post_status' => 'any'
				);
		 

				$existingQuery = new WP_Query($meta_args);
				if(count($existingQuery->posts) >= 1){
					continue;
				}

				$rawFilmPage = tv_site_plugin_download_via_curl($originalUrl);
				if($rawFilmPage){
					$title = strstr($rawFilmPage, '<title>');
					$title = substr(strstr($title, '</title>', true), 13);
					$title = str_ireplace(' Online - PrimeWire | LetMeWatchThis | 1Channel', '', $title);
					$image = strstr($rawFilmPage, 'meta property="og:image" content="');
					$image = str_ireplace('meta property="og:image" content="', '', strstr($image, '"/>', true));
					
					$description = strstr($rawFilmPage, '<p style="width:460px; display:block;">');
					$description = str_ireplace('<p style="width:460px; display:block;">', '', strstr($description, '</p>', true));

					preg_match_all('~link"> <a href="http://www.primewire.ch/stream/(.*?)" title="~is', $rawFilmPage, $exitPathLinks);
					
					if(!$exitPathLinks){
						continue;
					}
					$links = array();

					$maxMultiCurlConnect = 10;
					$curlsToDo = array();

					foreach ($exitPathLinks[1] as $exitPathLink) {
						${$exitPathLink} = curl_init();
						curl_setopt(${$exitPathLink}, CURLOPT_URL, "http://www.primewire.ch/stream/".$exitPathLink);
  						curl_setopt(${$exitPathLink}, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.1.16) Gecko/20121207 Iceweasel/3.5.16 (like Firefox/3.5.16)');
						curl_setopt(${$exitPathLink}, CURLOPT_FOLLOWLOCATION, true);
						curl_setopt(${$exitPathLink}, CURLOPT_RETURNTRANSFER, true);
						curl_setopt(${$exitPathLink}, CURLOPT_CONNECTTIMEOUT, 105);
						
						$curlsToDo[] = ${$exitPathLink};
					}

					$mh = curl_multi_init();

					$chunksToDo = array_chunk($curlsToDo, $maxMultiCurlConnect);
					foreach ($chunksToDo as $chunk) {
						foreach ($chunk as $curlHandle) {
							curl_multi_add_handle($mh, $curlHandle);
						}
						$running = null;
						do {
							sleep(1);
							curl_multi_exec($mh, $running);
						} while ($running);
					}

					foreach ($exitPathLinks[1] as $exitPathLink) {
						$rawExitPage = curl_multi_getcontent(${$exitPathLink});
						if(!$rawExitPage){
							continue;
						}

						$encodedLink = strstr($rawExitPage, '&url=');
						$encodedLink = str_ireplace('&url=', '', strstr($encodedLink, '&domain=', true));
						$realLink = base64_decode($encodedLink);
						$links[] = $realLink;
					}

					$films[] = array('title' => $title, 'image' => $image, 'description' => $description, 'links' => $links, 'originalUrl' => $originalUrl);
				}

			}
		}
	}




	foreach ($films as $film) {
		$postContent = "";
		$postTitle = $film['title'];
		$postContent .= "<h3>Description</h3><p>".$film['description']."<p>";
		$embedCode = "";

		foreach ($film['links'] as $link) {
			if(stripos($link, 'openload.co') !== false){
				$embedLink = str_ireplace('.co/f/', '.co/embed/', $link);
				$embedCode = '<iframe src="'.$embedLink.'" scrolling="no" frameborder="0" width="700" height="430" allowfullscreen="true" webkitallowfullscreen="true" mozallowfullscreen="true"></iframe>';
				break;
			}

		}

		$postContent .= $embedCode;

		if(count($film['links']) >= 1){
			$postContent .= "<h3>External Links</h3>";
		}
		
		foreach ($film['links'] as $link) {
			$postContent .= "<p><a href='".$link."'>".$link."</a></p>";
		}

		$status = get_option('tvsitepoststatus');

		remove_filter('content_save_pre', 'wp_filter_post_kses');
		remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');        
		
		$try = wp_insert_post(array('post_content' => $postContent, 'post_title' => $postTitle, 'post_excerpt' => $film['description'], 'post_author' => 1, 'post_status' => $status));
		
		add_filter('content_save_pre', 'wp_filter_post_kses');
		add_filter('content_filtered_save_pre', 'wp_filter_post_kses');

		if($try){
			add_post_meta($try, 'originalUrl', $film['originalUrl']);
			add_post_meta($try, 'description', $film['description'], true);
			add_post_meta($try, 'embedCode', $embedCode, true);
			add_post_meta($try, 'externalLinks', serialize($film['links']), true);

			$wpUploadDir = wp_upload_dir();
			$wpUploadSource = $wpUploadDir['url'];
			$wpUploadDir = $wpUploadDir['path'];
			$newFilename = uniqid();

			$extension = pathinfo($film['image'], PATHINFO_EXTENSION);
			if (stripos($extension, '?') !== false) {
			    $extension = substr($extension, 0, stripos($extension, '?'));
			}

			$newFilename .= $try.".".$extension;

			$tryDownload = tv_site_plugin_download_image_via_curl($film['image'], $wpUploadDir.'/'.$newFilename);
			$fileType = wp_check_filetype(basename($wpUploadDir.'/'.$newFilename), null);

			$attachment = array(
				'guid'				=> $wpUploadSource.'/'.basename($newFilename), 
				'post_mime_type'	=> $fileType['type'],
				'post_title'		=> preg_replace('/\.[^.]+$/', '', basename($newFilename)),
				'post_content'		=> '',
				'post_status'		=> 'inherit'
			);
			$attach_id = wp_insert_attachment($attachment, $wpUploadDir.'/'.$newFilename, $try);
			include_once ABSPATH . 'wp-admin/includes/image.php';

			$attach_data = wp_generate_attachment_metadata($attach_id, $wpUploadDir.'/'.$newFilename);
			wp_update_attachment_metadata($attach_id, $attach_data);

			$trySetThumb = set_post_thumbnail($try, $attach_id);
			$trySetMeta = update_post_meta($try, '_thumbnail_id', $attach_id);
		}
	}
}

function tv_site_template_filter($content) { 
	if(!metadata_exists('post', get_the_ID(), 'originalUrl')){
		return $content;
	}
	if (is_singular('post')) {
		$template = get_option('tvsiteposttemplate');
		$description = get_post_meta(get_the_ID(), 'description', true);
		if(stripos($template,'###|DESCRIPTION|###' ) !== false){
			$template = str_ireplace('###|DESCRIPTION|###', $description, $template);
		}
		$embedCode = get_post_meta(get_the_ID(), 'embedCode', true);
		if(stripos($template,'###|EMBEDVIDEO|###' ) !== false){
			$template = str_ireplace('###|EMBEDVIDEO|###', $embedCode, $template);
		}
		$externalLinks = unserialize(get_post_meta(get_the_ID(), 'externalLinks', true));
		
		if(stripos($template,'###|EXTERNALLINKS|###' ) !== false){
		
			$externalLinksTemplate = get_option('tvsiteexternallinkstemplate');
			

			$eachExternalLink = strstr($externalLinksTemplate, '###|EACHEXTERNALSTART|###');
			$eachExternalLink = str_ireplace('###|EACHEXTERNALSTART|###', '', strstr($eachExternalLink, '###|EACHEXTERNALEND|###', true));
			$externalLinkCode = "";

			foreach ($externalLinks as $link) {
				$domain = parse_url($link)['host'];
				$templatePart = str_ireplace('###|THEEXTERNALLINK|###', $link, $eachExternalLink);
				$templatePart = str_ireplace('###|THEEXTERNALLINKDOMAIN|###', $domain, $templatePart);
				$externalLinkCode .= $templatePart;
			}

			$externalLinkBeginning = strstr($externalLinksTemplate, '###|EACHEXTERNALSTART|###', true);
			$externalLinkEnd = substr(strstr($externalLinksTemplate, '###|EACHEXTERNALEND|###'), 23);
			$fullExternalLinkCode = $externalLinkBeginning.$externalLinkCode.$externalLinkEnd;
			$template = str_ireplace('###|EXTERNALLINKS|###', $fullExternalLinkCode, $template);
		}

		return $template;
	}

	return $content;
}

add_filter('the_content', 'tv_site_template_filter'); 


function set_up_tv_site_menus(){
	add_menu_page('TV Site Plugin', 'TV Site Plugin', "add_users", __FILE__."tvsiteplugin", 'tv_site_admin', plugins_url('tvlogo.png', __FILE__));
}

add_action('admin_menu', 'set_up_tv_site_menus');

function tv_site_admin(){
	echo '<div class="wrap">';
	if(!function_exists('curl_init')){
		echo '<div class="error notice"><p>Your web hosting provider has not enabled the curl extension, tv series episodes cannot be grabbed without it.</p></div>';
	}
	if(!function_exists('curl_multi_init')){
		echo '<div class="error notice"><p>Your web hosting provider has not enabled the multi curl extension, movies cannot be grabbed without it.</p></div>';
	}
	echo '<form method="post" action="options.php">';
	wp_nonce_field('update-options');
	echo '<input type="hidden" name="action" value="update" /><input type="hidden" name="page_options" value="tvsitecronenabled,tvsitefilmcronenabled,tvsitepoststatus,tvsiteposttemplate,tvsiteexternallinkstemplate" />';

	echo '<h1>TV Site Plugin Settings</h1><hr>';
	echo "<h3>Basic Settings</h3>";
	echo "<label>Enable auto posting of new episodes:</label>";
	tv_site_checkbox("tvsitecronenabled");
	echo "<br /><br />";
	echo "<label>Enable auto posting of new movies:</label>";
	tv_site_checkbox("tvsitefilmcronenabled");
	echo "<br /><br />";
	
	echo "<label>Post status for new content:</label>";
	$choices = array(
		"draft" => "Draft",
		"publish" => "Published"
	);

	tv_site_dropdown("tvsitepoststatus", $choices, "1");
  
	echo "<br/>";
	echo "<hr>";
	echo "<h3>Post Template</h3>";
	echo "<label>Main Post Template:</label><br />";
	tv_site_textarea("tvsiteposttemplate");
	echo "<br />";

	echo "<label>External Links Template:</label><br />";
	tv_site_textarea("tvsiteexternallinkstemplate");
	echo "<br />";

	echo "<p><input type='submit' class='button' value='Save Changes' /></p><hr>";

	echo "</div>";

}

if (!function_exists('tv_site_textarea')) {
	function tv_site_textarea($name, $value="") {
		if (get_option($name)) { 
			$value = get_option($name); 
		}
		echo '<textarea name="'.$name.'" cols="100" rows="14">'.$value.'</textarea>';
	
	}
}

if (!function_exists('tv_site_checkbox')) {
	function tv_site_checkbox($name) {
		?>
		<?php if (get_option($name)): ?>
		<input type="checkbox" name="<?php echo $name ?>" checked="checked" />
		<?php else: ?>
		<input type="checkbox" name="<?php echo $name ?>" />
		<?php endif; ?>
		<?php
	}
}

if (!function_exists('tv_site_is_vector')) {
   function tv_site_is_vector( &$array ) {
	  if ( !is_array($array) || empty($array) ) {
		 return -1;
	  }
	  $next = 0;
	  foreach ( $array as $k => $v ) {
		 if ( $k !== $next ) return false;
		 $next++;
	  }
	  return true;
   }
}

if(!function_exists('tv_site_dropdown')){
	function tv_site_dropdown($name, $data, $option="") {
	   if (get_option($name)) { $option = get_option($name); }

	   ?>
	   <select name="<?php echo $name ?>">
	   <?php

	   if (tv_site_is_vector($data)) {
		  foreach ($data as $item) {
			 if ($item == $option) {
				echo '<option selected="selected">' . $item . "</option>\n";
			 }
			 else {
				echo "<option>$item</option>\n";
			 }
		  }
	   }

	   else {
		  foreach ($data as $value => $text) {
			 if ($value == $option) {
				echo '<option value="'.$value.'" selected="selected">'.$text."</option>\n";
			 }
			 else {
				echo '<option value="'.$value .'">'."$text</option>\n";
			 }
		  }
	   }

	   ?>
	   </select>
	   <?php
	}
}

?>