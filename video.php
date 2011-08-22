<?php

require_once('lib/HTTPCache.php');
require_once('lib/YouTube.php');
require_once('lib/Vimeo.php');

/**
 * Simple script to redirect requests to YouTube or Vimeo's OWN SERVERS.
 * ABSOLULTELY NO VIDEO CONTENT IS HOSTED ON THIS SERVER.
 *
 * This is only necessary because iTunes requires URLs to look a certain way, and doesn't play nice with Google/Vimeo's URLs.
 */

$guidHash = basename($_SERVER['REQUEST_URI'], '.mp4');

$cache = new HTTPCache('cache');

$feedText = $cache->get('http://feeds.feedburner.com/devourfeed');

$xml = new SimpleXMLElement($feedText);

foreach ($xml->channel->item as $item)
{
    $devourPage = $cache->get($item->link);
    
    if (sha1($item->guid) == $guidHash)
    {
        if (preg_match('#http://www\.youtube\.com/embed/([\w-_]+)\?#', $devourPage, $matches))
        {
            $youtubeID = $matches[1];
    
            $youtube = new YouTube($youtubeID, $cache);
            
            $videoURL = $youtube->getVideoURL();
    
            if (!empty($videoURL))
            {
                header('Location: ' . $videoURL);
                exit();
            }
        }
        else if (preg_match('#http://player.vimeo.com/video/([\d]+)?#', $devourPage, $matches))
        {
            $videoID = $matches[1];
            
            $vimeo = new Vimeo($videoID, $cache);
            
            $videoURL = $vimeo->getVideoURL();
            
            if (!empty($videoURL))
            {
                header('Location: ' . $videoURL);
                exit();
            }
        }
    }
}

header($_SERVER['REQUEST_METHOD'] . ' 404 Not Found');
exit('<h1>Not Found</h1>');
