<?php

require_once(__DIR__ . '/HTTPCache.php');

class Vimeo
{
    /**
     * Formats we can use (based on 'itag'), ordered from most preferred to least preferred.
     * Currently, Apple TV only does 720p, so that's the one we want most.
     */
    public static $usableFormats = array(
        22, // 720p h.264
        37, // 1080p h.264
        18, // 360p h.264
        //5, // 240p FLV
    );
    
    protected $videoID, $cache;
    
    public function __construct($videoID, HTTPCache $cache)
    {
        $this->videoID = $videoID;
        $this->cache = $cache;
    }
    
    public function getVideoInfo()
    {
        // We can't cache Vimeo because of their tricksy timestamp tricks.
        $infoText = file_get_contents('http://vimeo.com/moogaloop/load/clip:' . $this->videoID . '/local/');
        $infoXML = new SimpleXMLElement($infoText);
        
        $info = array(
            'timestamp' => (string)$infoXML->timestamp,
            'signature' => (string)$infoXML->request_signature,
        );
        
        return $info;
    }
    
    public function getVideoURL()
    {
        $info = $this->getVideoInfo();
        
        if (empty($info['timestamp']) || empty($info['signature']))
        {
            return null;
        }
        
        $url = 'http://player.vimeo.com/play_redirect?clip_id=' . $this->videoID . '&quality=hd&codecs=h264,vp6&type=html5_desktop_local&time=' . $info['timestamp'] . '&sig=' . $info['signature'];
        
        return $url;
    }
}
