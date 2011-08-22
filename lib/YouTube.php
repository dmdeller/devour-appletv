<?php

require_once(__DIR__ . '/HTTPCache.php');

class YouTube
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
        $infoText = $this->cache->get('https://www.youtube.com/get_video_info?&video_id=' . $this->videoID . '&el=embedded&ps=default&eurl=http%3A%2F%2Fwww%2Egoogle%2Ecom%2F&hl=en_US');
        $info = static::parseUrlQuery($infoText);
        
        return $info;
    }
    
    public function getStreamData()
    {
        $info = $this->getVideoInfo();
        $streamData = static::parseStreamData($info['url_encoded_fmt_stream_map']);
        
        return $streamData;
    }
    
    public function getVideo()
    {
        $streamData = $this->getStreamData();
        $bestVideo = static::findBestVideo($streamData);
        
        return $bestVideo;
    }
    
    public function getVideoUrl()
    {
        $video = $this->getVideo();
        
        return $video['url'];
    }
    
    public static function findBestVideo($streamData)
    {
        $formatPriorities = array_flip(static::$usableFormats);
        
        $candidate = null;
        foreach ($streamData as $video)
        {
            if (in_array($video['itag'], static::$usableFormats))
            {
                if ($candidate == null)
                {
                    $candidate = $video;
                }
                else if ($formatPriorities[$video['itag']] > $formatPriorities[$candidate['itag']])
                {
                    $candidate = $video;
                }
            }
        }
        
        return $candidate;
    }
    
    public static function parseUrlQuery($query)
    {
        $mess = explode('&', $query);
        
        $data = array();
        foreach ($mess as $pair)
        {
            @list($key, $val) = explode('=', $pair);
            
            $key = urldecode($key);
            $val = urldecode($val);
            
            $data[$key] = $val;
        }
        
        return $data;
    }
    
    public static function parseStreamData($inData)
    {
        $mess = explode(',', $inData);
        
        $data = array();
        foreach ($mess as $pair)
        {
            $dataText = static::parseUrlQuery($pair);
            
            $data[] = $dataText;
        }
        
        return $data;
    }
}
