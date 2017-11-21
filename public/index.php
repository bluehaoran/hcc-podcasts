<?php
require "../vendor/autoload.php";

use Goutte\Client;
use GuzzleHttp\Client as GuzzleClient;
use Symfony\Component\DomCrawler\Crawler as Crawler;
use HccPodcast\SimpleXmlExtended;

function retrieveDocument($url) {
    // Avoid SSL warnings
    $guzzleClient = new GuzzleClient(['curl' => [CURLOPT_SSL_VERIFYPEER => false] ]);

    $client = new Client();
    $client->setClient($guzzleClient);

    // $client->getClient()->setDefaultOption('config/curl/' . CURLOPT_SSL_VERIFYPEER, false);
    $crawler = $client->request('GET', $url);

    return $crawler;
}

class Podcast {
    public $title;
    public $publishedTimestamp;
    public $author;
    public $audioUrl;
    public $durationInMs;
    public $content;
}

/*
This article is organised thus:
body

 > section#page (data-content-field="main-content")
    > [article#article-<id>.hentry.post-type-text.article-index-<enum>]
        > h1.entry-title (data-content-field="title")
        > time.published(datetime="date")
        > .... > div.sqs-audio-embed (data-url="....mp3" data-title="" data-author="<name>" data-duration-in-ms="")
        > .... > div.sqs-block-content > (HTML)

 */
function extractPodcastEntries(Crawler $htmlCrawler) {
    return $htmlCrawler
        ->filter('section#page > article.hentry')
        ->each(Closure::fromCallable('extractPodcastEntry'));
}

function extractPodcastEntry($podcastSection) {

    $audio = $podcastSection->filter('div.sqs-audio-embed');

    $podcast                     = new Podcast();
    $podcast->title              = $podcastSection->filter('h1.entry-title')->text();
    $podcast->publishedTimestamp = $podcastSection->filter('time.published')->attr('datetime');
    $podcast->content            = $podcastSection->filter('div.sqs-block-content')->html(); // wrap in CData?
    $podcast->author             = $audio->attr('data-author');
    $podcast->audioUrl           = $audio->attr('data-url');
    $podcast->durationInMs       = $audio->attr('data-duration-in-ms');

    return $podcast;
}

function createRSSFeed($podcastEntries) {

    // Use the extended SimpleXML class (SimpleXmlExtended - defined below), to ensure CData can be added within being escaped.
    $rss = new SimpleXmlExtended('<?xml version="1.0" encoding="utf-8"?><rss/>');
    $rss->addAttribute('version', '2.0');
    $rss->addAttribute('xmlns:xmlns:atom', 'http://www.w3.org/2005/Atom');
    $rss->addAttribute('xmlns:xmlns:dc', 'http://purl.org/dc/elements/1.1/');
    $rss->addAttribute('xmlns:xmlns:media', 'http://search.yahoo.com/mrss/');

    // Add the site detail node.
    $channel = $rss->addChild('channel');
    $channel->addChild('title', 'HCC Sermons');
    $channel->addChild('link', 'https://www.harbourcitychurch.com/sermons');
    $channel->addChild('description')->addCData('HCC Sermons - RSS Feed');
    $channel->addChild('language', 'en-AU');

    foreach ($podcastEntries as $podcastEntry) {
        $item = $channel->addChild('item');

        $item->addChild('title')->addCData($podcastEntry->title);
        $item->addChild('link', $podcastEntry->audioUrl);
        $item->addChild('description')->addCData($podcastEntry->content);
        $item->addChild('dc:dc:creator', $podcastEntry->author);
        // $item->addChild('media:media:thumbnail')->addAttribute('url', $thumbnail_image_url);
        $item->addChild('guid', $podcastEntry->audioUrl)->addAttribute('isPermaLink', 'false');
        $item->addChild('pubDate', $podcastEntry->publishedTimestamp);
    }

    return $rss->asXML();
}

function outputRssFeed($rssFeed) {
    header('Content-type: application/rss+xml'); // confirm
    echo($rssFeed);
    exit();
}

function debugDocument(Crawler $htmlCrawler) {
    $fh = fopen(__DIR__ .'/dump','w');
    $body = $htmlCrawler->filter('body')->each(function($node) use ($fh) {
        $html = print_r($node,true);
        fwrite($fh, $html);
        fwrite($fh, "\n<!----------------------\n");
    });
}



$url = 'https://www.harbourcitychurch.com/sermons';
$document = retrieveDocument($url);
$podcastEntries = extractPodcastEntries($document);
$rssFeed = createRSSFeed($podcastEntries);
return outputRssFeed($rssFeed);

