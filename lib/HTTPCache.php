<?php

class HTTPCache
{
    protected $dir, $sizesOnly;
    
    public function __construct($dir)
    {
        $this->dir = $dir;
    }
    
    public function get($url)
    {
        $filePath = $this->dir . '/' . sha1($url);
        
        if (file_exists($filePath))
        {
            $data = file_get_contents($filePath);
        }
        else
        {
            $data = file_get_contents($url);
            file_put_contents($filePath, $data);
        }
        
        return $data;
    }
    
    public function getSize($url)
    {
        $filePath = $this->dir . '/_size_' . sha1($url);
        
        if (file_exists($filePath))
        {
            $data = file_get_contents($filePath);
        }
        else
        {
            $data = static::remoteFilesize($url);
            file_put_contents($filePath, $data);
        }
        
        return $data;
    }
    
    /**
     * filesize() doesn't seem to work on URLs, even though it's supposed to
     *
     * http://icfun.blogspot.com/2008/11/php-get-remote-file-size.html
     */
    function remoteFilesize($inURL)
    {
        $url = preg_replace('/http:\/\//', '', $inURL);
        if (preg_match('/(.*?)(\/.*)/', $url, $match))
        {
            $domain = $match[1];
            $portno = 80;
            $method = "HEAD";
            $url = $match[2];
            
            $http_response = "";
            $http_request .= $method." ".$url ." HTTP/1.0\r\n";
            $http_request .= "\r\n";
    
            $fp = fsockopen($domain, $portno, $errno, $errstr);
            if ($fp)
            {
                fputs($fp, $http_request);
                while (!feof($fp)) $http_response .= fgets($fp, 128);
                fclose($fp);
            }
    
            $header = "Content-Length";
            $ret_str = "";
            if (preg_match("/Content\-Length: (\d+)/i", $http_response, $match))
            {
                return $match[1];
            }
            else
            {
                return 0;
            }
        }
        else
        {
            throw new InvalidArgumentException('Invalid URL: ' . $inURL);
        }
    }
}
