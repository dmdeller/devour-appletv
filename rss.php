<?php

require_once('lib/HTTPCache.php');
require_once('lib/YouTube.php');
require_once('lib/Vimeo.php');

$cache = new HTTPCache('cache');

$feedText = $cache->get('http://feeds.feedburner.com/devourfeed');

$xml = new SimpleXMLElement($feedText);

foreach ($xml->channel->item as $item)
{
    $devourPage = $cache->get($item->link);
    
    // for some reason, this feed doesn't supply a normal description... let's fix that
    $item->description = (string)$item->children('http://purl.org/rss/1.0/modules/content/')->encoded;
    
    if (preg_match('#http://www\.youtube\.com/embed/([\w-_]+)\?#', $devourPage, $matches))
    {
        $youtubeID = $matches[1];
    
        $youtube = new YouTube($youtubeID, $cache);
        
        $videoURL = $youtube->getVideoURL();
        
        if (!empty($videoURL))
        {
            $videoSize = $cache->getSize($videoURL);
            
            $enclosure = $item->addChild('enclosure');
            
            //$enclosure->addAttribute('url', $videoURL);
            
            // We have to do this silly little dance because iTunes won't accept a URL whose name doesn't end in .mp4
            // HEY iTUNES, THERE'S THIS THING CALLED A MIME TYPE
            $enclosure->addAttribute('url', 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['REQUEST_URI']) . '/video.php/' . sha1($item->guid) . '.mp4');
            
            $enclosure->addAttribute('length', $videoSize);
            
            // SEE iTUNES? RIGHT HERE
            $enclosure->addAttribute('type', 'video/mp4');
        }
        else
        {
            $item->addChild('error', 'Could not get YouTube video URL');
            
            $debug = array(
                'videoInfo' => $youtube->getVideoInfo(),
                'streamData' => $youtube->getStreamData(),
            );
            $item->addChild('debug', htmlentities(print_r($debug, true)));
        }
    }
    else if (preg_match('#http://player.vimeo.com/video/([\d]+)?#', $devourPage, $matches))
    {
        $videoID = $matches[1];
        
        $vimeo = new Vimeo($videoID, $cache);
        
        $videoURL = $vimeo->getVideoURL();
        
        if (!empty($videoURL))
        {
            $videoSize = $cache->getSize($videoURL);
            
            $enclosure = $item->addChild('enclosure');
            
            $enclosure->addAttribute('url', 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['REQUEST_URI']) . '/video.php/' . sha1($item->guid) . '.mp4');
            $enclosure->addAttribute('length', $videoSize);
            $enclosure->addAttribute('type', 'video/mp4');
        }
    }
    else
    {
        $item->addChild('error', 'Doesn\'t appear to be a YouTube or Vimeo video');
    }
}

echo $xml->asXML();
