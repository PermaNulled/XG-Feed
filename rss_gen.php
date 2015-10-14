<?php
header ("Content-Type:text/xml");
?>
<?xml version="1.0" encoding="utf-8" ?> 
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:newznab="http://www.newznab.com/DTD/2010/feeds/attributes/">
<channel>
<?php
function get_data($url) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_COOKIEJAR, 'ixcookie.txt');
        curl_setopt($ch, CURLOPT_COOKIEFILE, 'ixcookie.txt');
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.2; rv:20.0) Gecko/20121202 Firefox/20.0');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_REFERER, $url);

        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
}


function shuffle_assoc(&$array) {
        $keys = array_keys($array);

        shuffle($keys);

        foreach($keys as $key) {
            $new[$key] = $array[$key];
        }

        $array = $new;

        return true;
    }

function clean($string) {
   $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.

   return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
}

//Banning bad files so sickbeard doesn't find them
//Needs to be automated some how.
//Should also be stored in a database
$bans = array( 
	array("server"=>"irc.abjects.net","bot"=>"[MG]-HDTV|AS|S|D","pid"=>"385"), 
	array("server"=>"irc.abandoned-irc.net","bot"=>"Zombie-WF-MayFaiR","pid"=>"509"),
	array("server"=>"irc.abandoned-irc.net","bot"=>"Zombie-WF-CrinKleyS","pid"=>"1125"),
	array("server"=>"irc.scenep2p.net","bot"=>"TS-TV|Helheim","pid"=>"509"),
	array("server"=>"irc.abjects.net","bot"=>"[MG]-HDTV|AS|S|D0","pid"=>"82"),
	array("server"=>"Irc.scenep2p.net","bot"=>"TS-HDTV|TW|A2","pid"=>"183")
	);

if(empty($_GET['rid']))
{
  $search = false;

}else{
//include("TVDB.php");

//$TV = new TVDb;

//$show = $TV->get_tv_show_by_id(295681);



     // TVRage data should be stored in a database IDs don't change so we should only need to get them once, this should reduce time to search from sickbeard.
    //   include('TVRage.php');
   //   $rid = $_GET['rid']; // TV Rage ID
  //   $show = array(); // create array for the show data
 //   $show = TV_Shows::findById($rid); // get the show data
	// TVRage went away and broke rss_gen/XG-Feed, sickbeard reverted to TVDB so we'll start using that.

	include("TVDB.php");
	$TV = new TVDb;
	$rid = $_GET['rid']; // TVDB Show ID
	$show = $TV->get_tv_show_by_id($rid);
	$search = true;

	$ep = sprintf("%02d",$_GET['ep']); // episode number - hack to make it store as 01 if digit isn't greater than 9
	$season = sprintf("%02d",$_GET['season']); // tv show season - hack to make it store as 01 if digit isn't greater than 9
	$limit = $_GET['limit']; //100 - not used
	$t = $_GET['t']; // tvsearch - not used
}


     //Function to build and print the rss items
     function list_item($title,$date,$size,$url)
     {
	$title = str_replace("[TV]-","",$title);
	echo "<item>\n\t";
		echo '<title>'.$title.'</title>'."\n\t";
		echo '<guid isPermaLink="true">'.$url.'</guid>'."\n\t";
		echo '<link>'.$url.'</link>'."\n\t";
		echo '<pubDate>'.$date.'</pubDate>'."\n\t";
		echo '<category>TV &gt; HD</category>'."\n\t";
		echo '<description>'.$title.'</description>'."\n\t";
		echo '<enclosure url="http://'.$url.'" length="'.$size.'" type="application/x-nzb" />'."\n\t";
		echo '<newznab:attr name="category" value="5000" />'."\n\t";
		echo '<newznab:attr name="category" value="5040" />'."\n\t";
		echo '<newznab:attr name="size" value="'.$size.'" />'."\n\t";
 	      	echo '<newznab:attr name="guid" value="'.md5($title).'" />'."\n\n\n";
	echo "</item>\n";
     }

?>
<?php
$url = "http://".$_SERVER[HTTP_HOST].urlencode($_SERVER[REQUEST_URI]);
echo '<atom:link href="'.$url.'" rel="self" type="application/rss+xml" />'."\n";
?>
<title>XDCC Listing RSS Hax</title>
<description>XDCC listing rss hax</description>
<<<<<<< HEAD
<link>https://www.influncethis.org/xdcc</link>
=======
<link>Static Link Replacement</link>
>>>>>>> origin/master
<language>en-gb</language>
<webMaster>tim@thedefaced.org (Timothy Lawrence)</webMaster>
<category></category>

<?php
$items = array();

if($search)
{

   $show_name = $show->name; // get the show's name from the returned TVRage object.
   $show_name = str_replace("-"," ",clean($show_name)); // Some shows like american dad include symbols in them which aren't indexed.
   if($ep !=0 && !empty($ep)) // Check if we're looking for an episode or season
   	$search_string = urlencode($show_name."."."S".$season."E".$ep); // if we're looking for an episode we want to add the S and E identifiers, we were only passed an TVRage ID
   else
	$search_string = urlencode($show_name."."."S".$season); // Same concept as above but without E because we're looking for seasons.
   echo "Searching for: ".$search_string."\n";
   $network_data = get_data("http://ixirc.com/api/?q=".$search_string); // Request the built search string on ixirc's API
   $output = json_decode($network_data); // decode the json return and store it.
   $results = $output->results; // grab the results object from the returned data we don't care about the rest of it yet.
}else{
   //If we weren't supplied a TVRage ID then it's trying to get all new stuff under the category of TV

   $search_terms = array("HDTV","LOL","DIMENSION","IMMERSE","ROVERS","C4TV","CROOKS","AFG","FiHTV","BATV","KILLERS","NTb"); // hack for no categories, we build a tv search by finding file names which have tags related to tv downloads.

   //We'll need to perform searches for each one of the above tags.
   foreach($search_terms as $search)
   {
	$network_data = file_get_contents("http://ixirc.com/api?q=".$search);
	$output = json_decode($network_data);
	$results = $output->results;

	foreach($results as $item)
	{
		array_push($items,$item); // we store all of the results in the items array.
  	}
  }


}

//If we're dealing with a single show search...
if($search)
{

   foreach($results as $item)
   {
	//Make sure we're geting what we asked for
	if( stristr($item->name,$search_string) || stristr( $item->name, str_replace("+",".",$search_string) ) )
	{
		array_push($items,$item);
	}
   }
}

   $num_gets = count($items);
   echo '<newznab:response offset="0" total="'.$num_gets.'" />'."\n";
   shuffle_assoc($items);
   //Process items array and list items in RSS format
   foreach($items as $item)
   {
      	$server = $item->naddr;
	$bot = $item->uname;
	$pid = $item->n;

    //Ban specific servers/bots/packetids...
    	$banned = false;
	foreach( $bans as $ban )
    	{
  		if($server == $ban["server"] && $bot == $ban["bot"] && $pid == $ban["pid"] || $server == "irc.abandoned-irc.net")
      			$banned = true;
    	}
	if($banned == false)
	{
	    if(!empty($item->uname) && !empty($item->naddr) && !empty($item->nport) && !empty($item->cname) && !empty($item->n) && !empty($item->name)){
		$item_url = $item->naddr."/".$item->nport."/".$item->cname."/".$item->uname."/".$item->n."/".$item->name;
		list_item($item->name,$item->agef,$item->sz,$item_url);
  	   }
	} 
  }

?>

</channel>
</rss>
