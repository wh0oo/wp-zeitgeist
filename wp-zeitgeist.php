<?php
/*
	Plugin Name: WP-Zeitgeist
	Plugin URI: http://www.village-idiot.org/archives/2007/04/15/wp-zeitgeist/
	Description: WP-Zeitgeist provides a visual representation of search trends and patterns. 
	Version: 1.0
	Author: whoo
	Author URI: http://www.village-idiot.org
	Usage: <?php zeitgeist_display(1, 100, '', ''); ?>
*/


/*  
	This program is free software; you can redistribute it and/or modify
    	it under the terms of the GNU General Public License as published by
    	the Free Software Foundation; either version 2 of the License, or
    	(at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
    	but WITHOUT ANY WARRANTY; without even the implied warranty of
    	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
    	along with this program; if not, write to the Free Software
    	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

	Some code respectfully borrowed from Shaun Inman (http://www.shauninman.com/)
	Any redistribution of the code inside this plugin requires that Shaun Inman
	and myself, whoo, be mentioned. 

*/


// Install the necessary tables.

	function zeitgeist_install() {
		global $wpdb;
		$site_root = get_settings('siteurl');
		$zeitgeist_table_name = $wpdb->prefix . "zeitgeist";
		if($wpdb->get_var("show tables like '$zeitgeist_table_name'") != $zeitgeist_table_name) {
		

		$sql = "CREATE TABLE " . $zeitgeist_table_name . " (
			id int(11) unsigned NOT NULL AUTO_INCREMENT,
			searchterms varchar(255) NOT NULL DEFAULT '',
			count int(10) unsigned NOT NULL DEFAULT '0',
			hit varchar(255) NOT NULL DEFAULT '',
			PRIMARY KEY (id)
			);";
		require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
		dbDelta($sql);
		$firstentry = "INSERT INTO $zeitgeist_table_name (searchterms,count,hit) VALUES ('hello',1,'$site_root')";
		$wpdb->query($firstentry);
			
		}
}

// Do some work
	
	function zeitgeist($zurl) { // $url should be an array created by parse_url($ref)
			global $wpdb;
			global $table_prefix;
			$table_zeit = $table_prefix . "zeitgeist";
			$zref	= $_SERVER['HTTP_REFERER'];
			$zurl = parse_url($zref);
			$zres = $_SERVER['REQUEST_URI'];
// Check for google first
		if (preg_match("/google\./i", $zurl['host'])) {
			parse_str($zurl['query'],$q);
			// Googles search terms are in "q"
			$searchterms = $q['q'];
			}
		else if (preg_match("/yahoo\./i", $zurl['host'])) {
			parse_str($zurl['query'],$q);
			// Yahoo search terms are in "p"
			$searchterms = $q['p'];
			}
		else if (preg_match("/search\.live\./i", $zurl['host'])) {
			parse_str($zurl['query'],$q);
			// MSN search terms are in "q"
			$searchterms = $q['q'];
			}
		else if (preg_match("/search\.aol\./i", $zurl['host'])) {
			parse_str($zurl['query'],$q);
			// AOL search terms are in "query"
			$searchterms = $q['query'];
			}
		else if (preg_match("/web\.ask\./i", $zurl['host'])) {
			parse_str($zurl['query'],$q);
			// Ask Jeeves search terms are in "q"
			$searchterms = $q['q'];
			}
		else if (preg_match("/search\.looksmart\./i", $zurl['host'])) {
			parse_str($zurl['query'],$q);
			// LookSmart search terms are in "p"
			$searchterms = $q['p'];
			}
		else if (preg_match("/alltheweb\./i", $zurl['host'])) {
			parse_str($zurl['query'],$q);
			// All the Web search terms are in "q"
			$searchterms = $q['q'];
			}
		else if (preg_match("/a9\./i", $zurl['host'])) {
			parse_str($zurl['query'],$q);
			// A9 search terms are in "q"
			$searchterms = $q['q'];
			}
		else if (preg_match("/gigablast\./i", $zurl['host'])) {
			parse_str($zurl['query'],$q);
			// Gigablast search terms are in "q"
			$searchterms = $q['q'];
			}
		else if (preg_match("/s\.teoma\./i", $zurl['host'])) {
			parse_str($zurl['query'],$q);
			// Teoma search terms are in "q"
			$searchterms = $q['q'];
			}
		else if (preg_match("/clusty\./i", $zurl['host'])) {
			parse_str($zurl['query'],$q);
			// Clusty search terms are in "query"
			$searchterms = $q['query'];
			}
		
		if (isset($searchterms) && !empty($searchterms)) {
		// Remove BINARY from the SELECT statement for a case-insensitive comparison
		$exists_query = "SELECT id FROM $table_zeit WHERE searchterms = BINARY '$searchterms'";
		$search_term_id = $wpdb->get_var($exists_query);
		
		if( $search_term_id ) {
				$query = "UPDATE $table_zeit SET count = (count+1) WHERE id = $search_term_id";
			} else {
				$query = "INSERT INTO $table_zeit (searchterms,count,hit) VALUES ('$searchterms',1,'$zres')";
			}
			
			$wpdb->query($query);		
}
		}
	
// Display the Cloud

	function zeitgeist_display($smallest=10, $largest=36, $before='', $after='&nbsp;') {
		global $wpdb;
		global $table_prefix;
		$table_zeit = $table_prefix . "zeitgeist";
		$results = $wpdb->get_results("SELECT $table_zeit.searchterms AS 'keywords', $table_zeit.count AS 'count', $table_zeit.hit AS 'hit' FROM $table_zeit GROUP BY keywords ORDER BY RAND() DESC LIMIT 0 , 30");

	foreach ($results as $result) {
		$hit  = $result->hit;
		$counts[] = $result->count;
		}
		$min = min($counts);
		$max = max($counts);
		$spread = $max - $min;
		if ($largest != $smallest) {
		$fontspread = $largest - $smallest;
		if ($spread != 0) {
			$fontstep = $fontspread / $spread;
		} else {
			$fontstep = 0;
		}
	}
	
	foreach ($results as $result) {
		$url  = $home_root . $result->hit;
		$text = $result->keywords;
		$fraction = ($result->count - $min);
		$size = $smallest + ($fontstep * $fraction);
		$style = "class=\"";
		if ($largest != $smallest) {
		$style .= "cloud_".round($size/10). "";
		}
		$style .= "\"";
		$the_results[] = $before."<a href=\"".$url."\" title=\"".str_replace('"', '&quot;', $result->keywords)."\" ".$style.">".$text."</a>".$after."\n";
	}
		srand ((double)microtime()*1000000);
		shuffle ($the_results);
		foreach( $the_results as $result ) echo $result;
	}

// Here's the hooks

	add_action('activate_wp-zeitgeist.php', 'zeitgeist_install');
	add_action('shutdown', 'zeitgeist');

?>