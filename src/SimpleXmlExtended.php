<?php
namespace HccPodcast;

// Extend the Simple XML class to allow CData 
// http://stackoverflow.com/questions/7146141/simplexmlelementaddchild-doesnt-seem-to-work-with-certain-strings
class SimpleXmlExtended extends \SimpleXMLElement
{   
	public function addCData($cdata_text)
	{
		$node = dom_import_simplexml($this);   
		$no = $node->ownerDocument;   
		$node->appendChild($no->createCDATASection($cdata_text));   
	}
}


// $testXML = '<item><title>5 Questions to Ask Yourself When Picking an Airport Outfit</title><description>&lt;![CDATA[&lt;img src="http://cliqueimg.com/posts/img/uploads/current/images/0/254/921/promo.original.jpg" alt=""/&gt;&lt;br/&gt;Never wear the wrong thing again!]]&gt;</description><pubDate>Mon, 14 Sep 2015 04:00:00 +0000</pubDate><link>http://www.whowhatwear.com.au/airport-outfits-comfortable</link><guid>http://www.whowhatwear.com.au/airport-outfits-comfortable</guid><enclosure type="image/jpeg" length="354481" url="http://cliqueimg.com/posts/img/uploads/current/images/0/254/921/promo.original.jpg"/></item>';