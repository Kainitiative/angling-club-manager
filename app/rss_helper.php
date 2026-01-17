<?php
declare(strict_types=1);

/**
 * Fetch and parse an RSS feed with caching
 * 
 * @param string $url The RSS feed URL
 * @param int $limit Number of items to return
 * @param int $cacheSeconds Cache duration in seconds
 * @return array
 */
function fetch_rss_feed(string $url, int $limit = 5, int $cacheSeconds = 3600): array {
    $cacheFile = sys_get_temp_dir() . '/rss_cache_' . md5($url) . '.json';
    
    // Check cache
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheSeconds)) {
        $cachedData = json_decode(file_get_contents($cacheFile), true);
        if ($cachedData) return array_slice($cachedData, 0, $limit);
    }
    
    try {
        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'user_agent' => 'AnglingClubManager/1.0'
            ]
        ]);
        
        $rssContent = @file_get_contents($url, false, $context);
        if (!$rssContent) return [];
        
        $xml = @simplexml_load_string($rssContent);
        if (!$xml) return [];
        
        $items = [];
        foreach ($xml->channel->item as $item) {
            $items[] = [
                'title' => (string)$item->title,
                'link' => (string)$item->link,
                'description' => strip_tags((string)$item->description),
                'pubDate' => (string)$item->pubDate,
                'timestamp' => strtotime((string)$item->pubDate)
            ];
        }
        
        // Save to cache
        file_put_contents($cacheFile, json_encode($items));
        
        return array_slice($items, 0, $limit);
    } catch (Exception $e) {
        return [];
    }
}
