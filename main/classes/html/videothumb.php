<?php
/* 
 * $Id: videothumb.php
*/
 
class HelperClassVideoThumb
{
	static function auto( $url )
	{
		if( preg_match('/http\:\/\/www.youtube.com/', $url) || preg_match('/youtube.com/', $url)  || preg_match('/youtu.be/', $url) ) {
			return self::_getYoutubeVideoThumb($url);
		}
		else if( preg_match('/facebook.com/', $url) ) {
			return self::_getFacebookVideoThumb($url);
		}
		else if( preg_match('/vimeo.com/', $url) ) {
			return self::_getVimeoVideoThumb($url);
		}
	}
	
	static function getembedurl( $url )
	{
		if( preg_match('/http\:\/\/www.youtube.com/', $url) || preg_match('/youtube.com/', $url)  || preg_match('/youtu.be/', $url) ) {
			$id	= '';
			
			if( preg_match('%(?:youtube\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match) ) {
				$id	= $match[1];
			}
		
			if( $id ) {
				//$url	= 'http://www.youtube.com/embed/' . $id;
				$url	= 'http://www.youtube.com/embed/' . $id . '?wmode=transparent';
			}
			
			return $url;	
		}
		else if( preg_match('/facebook.com/', $url) ) {
			return $url;
		}
		else if( preg_match('/vimeo.com/', $url) ) {
			if ( preg_match('/(\d+)/', $url, $matches) ) {
				$video_id	= $matches[1];
			}
			
			$url	= "http://player.vimeo.com/video/{$video_id}";
			
			return $url;
		}
	}
	
	private function _getYoutubeVideoThumb( $src )
	{
		// SRC: http://www.youtube.com/v/owwb9K8YB8g
		// IMG: http://img.youtube.com/vi/VIDEO_ID/default.jpg
		
		$thumb	= false;
		if( preg_match('%(?:youtube\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $src, $match) ) {
			$thumb	= $match[1];
		}
		
		// validate if exists
		$thumb	= "http://img.youtube.com/vi/{$thumb}/default.jpg";
		if( @getimagesize($thumb) === false ) {
			return false;
		}
		
		return $thumb;
	}
	
	private function _getFacebookVideoThumb( $src )
	{
		// https://graph.facebook.com/VIDEO_ID/picture
		// http://www.facebook.com/v/1861584611679
		
		$thumb	= false;
		if( preg_match("/http:\\/\\/www\.facebook\.com\\/v\\/(.*?)/siU", $src, $match) ) {
			if( isset($match[1]) ) {
				$thumb	= $match[1];
			}
		}
		
		// validate if exists
		$thumb	= "https://graph.facebook.com/{$thumb}/picture";
		if( @getimagesize($thumb) === false ) {
			return false;
		}
		
		return $thumb;
	}
	
	private function _getGoogleVideoThumb( $src )
	{
		/*
		// http://video.google.com/videofeed?docid=VIDEO_ID

		// SRC: http://www.youtube.com/v/owwb9K8YB8g
		// IMG: http://img.youtube.com/vi/VIDEO_ID/default.jpg
		//$thumb	= preg_replace('/http\:\/\/www.youtube.com\/v\/(.*)\//', 'http://img.youtube.com/vi/$1/default.jpg', $src);
		
		if( preg_match("/http:\\/\\/video\.google\.com\\/googleplayer\\.swf\\?docid=([^\"][a-zA-Z0-9-_]+)[&\"]/siU", $src, $match) ) {
			if( isset($match[1]) ) {
				$thumb	= "http://img.youtube.com/vi/{$match[1]}/default.jpg";
				
				return $thumb;
			}
		}
		*/
		
		return false;
	}
	
	private function _getVimeoVideoThumb( $src )
	{
		/*
		* http://vimeo.com/api/v2/video/<VIDEO_ID>.<OUTPUT>
		* <VIDEO_ID>	Video id
		* <OUTPUT>		Result output in JSON, XML, PHP
		*/
		
		$video_id	= false;
		if ( preg_match('/(\d+)/', $src, $matches) ) {
			$video_id	= $matches[1];
		}
		
		if( $video_id ) {
			// fetch details
			$url	= "http://vimeo.com/api/v2/video/{$video_id}.json";
			
			// set max timeout to none
			if( !ini_get('safe_mode') ){
				set_time_limit(0);
			}
		
			// init cURL
			$ch		= curl_init($url);

			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			
			$json_data	= curl_exec($ch);

			curl_close($ch);
			
			// parse
			$data		= json_decode($json_data);
			if( $data ) {
				// validate if exists
				if( @getimagesize($data{0}->thumbnail_small) === false ) {
					return false;
				}
				
				return $data{0}->thumbnail_small;
			}
		}
		
		return false;
	}
}
