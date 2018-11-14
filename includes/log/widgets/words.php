<?php
function wp_statistics_generate_words_postbox_content( $ISOCountryCode, $count = 10 ) {

	global $wpdb, $WP_Statistics;

	// Retrieve MySQL data for the search words.
	$search_query = wp_statistics_searchword_query( 'all' );

	// Determine if we're using the old or new method of storing search engine info and build the appropriate table name.
	$tablename = $wpdb->prefix . 'statistics_';

	if ( $WP_Statistics->get_option( 'search_converted' ) ) {
		$tabletwo  = $tablename . 'visitor';
		$tablename .= 'search';
		$result    = $wpdb->get_results(
			"SELECT * FROM `{$tablename}` INNER JOIN `{$tabletwo}` on {$tablename}.`visitor` = {$tabletwo}.`ID` WHERE {$search_query} ORDER BY `{$tablename}`.`ID` DESC  LIMIT 0, {$count}"
		);
	} else {
		$tablename .= 'visitor';
		$result    = $wpdb->get_results(
			"SELECT * FROM `{$tablename}` WHERE {$search_query} ORDER BY `{$tablename}`.`ID` DESC  LIMIT 0, {$count}"
		);
	}

	if ( sizeof( $result ) > 0 ) {
		echo "<table width=\"100%\" class=\"widefat table-stats\" id=\"last-referrer\">
		  <tr>";
		echo "<td>Word</td>";
		echo "<td>Browser</td>";
		if ( $WP_Statistics->get_option( 'geoip' ) ) {
			echo "<td>Country</td>";
		}
		if ( $WP_Statistics->get_option( 'geoip_city' ) ) {
			echo "<td>City</td>";
		}
		echo "<td>Date</td>";
		echo "<td>IP</td>";
		echo "<td>Referrer</td>";
		echo "</tr>";

		//Load city Name
		if ( $WP_Statistics->get_option( 'geoip_city' ) ) {
			$geoip_reader = $WP_Statistics::geoip_loader( 'city' );
		}

		foreach ( $result as $items ) {


			if ( ! $WP_Statistics->Search_Engine_QueryString( $items->referred ) ) {
				continue;
			}

			if ( $WP_Statistics->get_option( 'search_converted' ) ) {
				$this_search_engine = $WP_Statistics->Search_Engine_Info_By_Engine( $items->engine );
				$words              = $items->words;
			} else {
				$this_search_engine = $WP_Statistics->Search_Engine_Info( $items->referred );
				$words              = $WP_Statistics->Search_Engine_QueryString( $items->referred );
			}

			echo "<tr>";
			echo "<td style=\"text-align: left\">";
			echo $words;
			echo "</td>";
			echo "<td style=\"text-align: left\">";
			if ( array_search(
				     strtolower( $items->agent ),
				     array(
					     "chrome",
					     "firefox",
					     "msie",
					     "opera",
					     "safari",
				     )
			     ) !== false
			) {
				$agent = "<img src='" .
				         plugins_url( 'wp-statistics/assets/images/' ) .
				         $items->agent .
				         ".png' class='log-tools' title='{$items->agent}'/>";
			} else {
				$agent = wp_statistics_icons( 'dashicons-editor-help', 'unknown' );
			}
			echo "<a href='?page=" .
			     WP_Statistics::$page['overview'] .
			     "&type=last-all-visitor&agent={$items->agent}'>{$agent}</a>";
			echo "</td>";
			$city = '';
			if ( $WP_Statistics->get_option( 'geoip_city' ) ) {
				if ( $geoip_reader != false ) {
					try {
						$reader = $geoip_reader->city( $items->ip );
						$city   = $reader->city->name;
					} catch ( Exception $e ) {
						$city = __( 'Unknown', 'wp-statistics' );
					}
				}
			}

			if ( $WP_Statistics->get_option( 'geoip' ) ) {
				echo "<td style=\"text-align: left\">";
				echo "<img src='" .
				     plugins_url( 'wp-statistics/assets/images/flags/' . $items->location . '.png' ) .
				     "' title='{$ISOCountryCode[$items->location]}{$city}' class='log-tools'/>";
				echo "</td>";
			}

			if ( $WP_Statistics->get_option( 'geoip_city' ) ) {
				echo "<td style=\"text-align: left\">";
				echo $city;
				echo "</td>";
			}


			echo "<td style=\"text-align: left\">";
			echo date( get_option( 'date_format' ), strtotime( $items->last_counter ) );
			echo "</td>";

			echo "<td style=\"text-align: left\">";
			if ( substr( $items->ip, 0, 6 ) == '#hash#' ) {
				$ip_string = __( '#hash#', 'wp-statistics' );
			} else {
				$ip_string = "<a href='admin.php?page=" .
				             WP_Statistics::$page['visitors'] .
				             "&type=last-all-visitor&ip={$items->ip}'>{$items->ip}</a>";
			}
			echo $ip_string;
			echo "</td>";

			$referrer_html = $WP_Statistics->html_sanitize_referrer( $items->referred );
			$base_url = parse_url($referrer_html);
			$base_url = $base_url['host'];
			echo "<td style=\"text-align: left\">";
			echo "<a href='" .
			     $referrer_html .
			     "' title='" .
			     $referrer_html .
			     "'>" .
			     $base_url .
			     "</a></div>";
			echo "</div>";
			echo "</td>";

			echo "</tr>";
		}

		echo "</table>";
	}
}

