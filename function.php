<?php

function up_coming_events( $atts )
{
    global $post;
    global $wpdb;

	$atts = shortcode_atts(
		array(
			'per_page' => '-1',
			'cat_id' => '0',
            'order_by' => 'start_date',
            'view' => 'table',

		), $atts, 'up_coming_events' );


	ob_start();





    /** Event SQL */
    $eventSQL = 'SELECT * FROM '.$wpdb->prefix.'posts p
                LEFT JOIN `'.$wpdb->prefix.'esp_datetime` esp_date ON `esp_date`.`EVT_ID` = `p`.`ID`
                LEFT JOIN `'.$wpdb->term_relationships.'` tr ON (p.ID = tr.object_id)
                LEFT JOIN `'.$wpdb->term_taxonomy.'` tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)
                LEFT JOIN `'.$wpdb->terms.'` t ON (tt.term_id = t.term_id)
                WHERE
                    `post_type` = \'espresso_events\'
                    AND `post_status` = \'publish\'
                    AND `tt`.`taxonomy` = \'espresso_event_categories\'
                    ';

    /**
     * @todo Conditional Where Clauses
     */
    if($atts['cat_id'] > 0)
    {
        $eventSQL .= ' AND `t`.`term_id` = '.$atts['cat_id'];
    }

    /**
     * @todo Conditional Order by clauses
     */
    if($atts['order_by'] == 'start_date')
    {
        $eventSQL .= ' ORDER BY DTT_EVT_start ASC';
    }
    else
    {
        $eventSQL .= ' ORDER BY DTT_EVT_end DESC';
    }


    /**
     * @todo Conditional LIMIT
     */
     if($atts['per_page'])
     {
         $eventSQL .= ' LIMIT '.$atts['per_page'];
     }



    /** Fetch event records from database */
    $allEventData = $wpdb->get_results($eventSQL);

    if($allEventData)
    {
        if($atts['view'] == 'table')
        {
            ?>
            <div class="wpb_column vc_column_container col-sm-7">';
            <h3>Upcoming Classes</h3>

            <table id="ee_filter_table" class="espresso-table footable table" data-page-size="10" data-filter="#filter">
            	<thead class="espresso-table-header-row">
            		<tr>
                    	<th class="th-group"><?php _e('Classes','event_espresso'); ?></th>
            			<th class="th-group" colspan="2"><?php _e('Event Date','event_espresso'); ?></th>
                        <th class="th-group"><?php _e('Location','event_espresso'); ?></th>
                        <th class="th-group"><?php _e('Price','event_espresso'); ?></th>
            		</tr>
            	</thead>
                <tbody>
                    <?php
                    /**
                     * @todo loop through the events
                     */
                     foreach($allEventData as $eachEvent)
                     {
                         /** Get Venue data */
                         $espVenue = $wpdb->get_row('SELECT * FROM `sd321_esp_event_venue`
                                                     LEFT JOIN `sd321_esp_venue_meta` ON `sd321_esp_venue_meta`.`VNU_ID` = `sd321_esp_event_venue`.`VNU_ID`
                                                     WHERE `EVT_ID` = '.$eachEvent->ID
                                                 );

                         $startTimeStamp = strtotime($eachEvent->DTT_EVT_start);
                         $endTimeStamp = strtotime($eachEvent->DTT_EVT_end);

                         $startDate = date('M d, Y', $startTimeStamp);
                         $endDate = date('M d, Y', $endTimeStamp);

                         $startTime = date('h:i a', $startTimeStamp);
                         $endTime = date('h:i a', $endTimeStamp);

                         $ticket_price = 100;
                         $eventID = $eachEvent->ID;

                         $time = microtime(true);
                         $token = EE_Encryption::instance()->encrypt( $time );
                         $referer = get_the_permalink();
                         $nonce = wp_create_nonce($referer);

                         $tickets = espresso_event_tickets_available( $eventID, FALSE, FALSE );
                         $ticket = reset( $tickets );

                         //check if the ticket is free, if set the ticket price to 'Free'
                         if($ticket instanceof EE_Ticket )
                         {
                             $ticket_price = $ticket->pretty_price();
                             $ticket_price_data_value = $ticket->price();
                             $ticket_price = $ticket_price_data_value == 0 ? __( 'Free', 'event_espresso' ) : $ticket_price;
                         }
                         ?>
                             <tr class="espresso-table-row footable-even">
                                 <td class="event_title"><?php echo $eachEvent->post_title ?></td>
                                 <td class="start_date" colspan="2"><?php echo $startDate.' - '.$endDate; ?><br /> <?php echo $startTime.' - '.$endTime; ?></td>
                                 <td class="venue_title"><?php echo $espVenue->VNU_city; ?></td>
                                 <td class="ticket_price">
                                     <?php echo $ticket_price;?>

                                     <form method="post" action="<?php echo get_the_permalink($eventID); ?>">
                                         <?php echo wp_nonce_field('process_ticket_selections', 'process_ticket_selections_nonce_' . $eventID, true, false); ?>
                                         <input type="hidden" name="ee" value="process_ticket_selections" />
                                         <input type="hidden" name="tkt-slctr-qty-<?php echo $eventID; ?>[]" value="1" />
                                         <input type="hidden" name="tkt-slctr-ticket-id-<?php echo $eventID; ?>[]" value="<?php echo $ticket->ID(); ?>" />
                                         <input type="hidden" name="noheader" value="true" />
                                         <input type="hidden" name="tkt-slctr-return-url-<?php echo $eventID; ?>" value="<?php echo get_the_permalink().'#tkt-slctr-tbl-'.$eventID; ?>" />
                                         <input type="hidden" name="tkt-slctr-rows-<?php echo $eventID; ?>" value="1" />
                                         <input type="hidden" name="tkt-slctr-max-atndz-<?php echo $eventID; ?>" value="10" />
                                         <input type="hidden" name="tkt-slctr-event-id" value="<?php echo $eventID; ?>" />
                                         <input type="hidden" name="tkt-slctr-request-processor-email" value="" />
                                         <input type="hidden" name="tkt-slctr-request-processor-token" value="<?php echo $token; ?>" />
                                         <input type="submit"  value="Register Now" />

                                     </form>
                                 </td>

                         	</tr>
                         <?php
                     }
                     ?>
                     </tbody>
                </table>
                </div>
            <?php
        }
        else
        {
            ?>
            <aside id="ee-upcoming-events-widget-2" class="widget widget_ee-upcoming-events-widget">
                <h3 class="widget-title">
                    <span><a href="http://localhost/projects/cms/wp/vikesh/classes/">Upcoming Classes</a></span>
                </h3>

                <ul class="ee-upcoming-events-widget-ul">
                    <?php
                    /**
                     * @todo loop through the events
                     */
                     foreach($allEventData as $eachEvent)
                     {
                         /** Get Venue data */
                         $espVenue = $wpdb->get_row('SELECT * FROM `sd321_esp_event_venue`
                                                     LEFT JOIN `sd321_esp_venue_meta` ON `sd321_esp_venue_meta`.`VNU_ID` = `sd321_esp_event_venue`.`VNU_ID`
                                                     WHERE `EVT_ID` = '.$eachEvent->ID
                                                 );

                         $startTimeStamp = strtotime($eachEvent->DTT_EVT_start);
                         $endTimeStamp = strtotime($eachEvent->DTT_EVT_end);

                         $startDate = date('M d, Y', $startTimeStamp);
                         $endDate = date('M d, Y', $endTimeStamp);

                         $startTime = date('h:i a', $startTimeStamp);
                         $endTime = date('h:i a', $endTimeStamp);

                         $ticket_price = 100;
                         $eventID = $eachEvent->ID;

                         $time = microtime(true);
                         $token = EE_Encryption::instance()->encrypt( $time );
                         $referer = get_the_permalink();
                         $nonce = wp_create_nonce($referer);

                         $tickets = espresso_event_tickets_available( $eventID, FALSE, FALSE );
                         $ticket = reset( $tickets );

                         //check if the ticket is free, if set the ticket price to 'Free'
                         if($ticket instanceof EE_Ticket )
                         {
                             $ticket_price = $ticket->pretty_price();
                             $ticket_price_data_value = $ticket->price();
                             $ticket_price = $ticket_price_data_value == 0 ? __( 'Free', 'event_espresso' ) : $ticket_price;
                         }
                         ?>
                         <li class="ee-upcoming-events-widget-li">
                             <h5 class="ee-upcoming-events-widget-title-h5">
                                 <a class="ee-widget-event-name-a one-line" href="<?php echo get_the_permalink($eachEvent->ID); ?>"><?php echo $eachEvent->post_title; ?></a>
                             </h5>
                             <ul id="ee-event-datetimes-ul-647" class="ee-event-datetimes-ul ee-clearfix">
                                 <li id="ee-event-datetimes-li-1" class="ee-event-datetimes-li ee-event-datetimes-li-DTU">
                                     <strong><?php echo $eachEvent->DTT_name; ?></strong>
                                     <br>
                                     <span class="dashicons dashicons-calendar"></span>
                                     <?php echo $startDate . ' - '.$endDate; ?><br>
                                     <span class="dashicons dashicons-clock"></span>
                                     <?php echo $startTime . ' - '.$endTime; ?>
                                 </li>
                             </ul>
                             <form method="post" action="<?php echo get_the_permalink($eventID); ?>">
                                 <?php echo wp_nonce_field('process_ticket_selections', 'process_ticket_selections_nonce_' . $eventID, true, false); ?>
                                 <input type="hidden" name="ee" value="process_ticket_selections" />
                                 <input type="hidden" name="tkt-slctr-qty-<?php echo $eventID; ?>[]" value="1" />
                                 <input type="hidden" name="tkt-slctr-ticket-id-<?php echo $eventID; ?>[]" value="<?php echo $ticket->ID(); ?>" />
                                 <input type="hidden" name="noheader" value="true" />
                                 <input type="hidden" name="tkt-slctr-return-url-<?php echo $eventID; ?>" value="<?php echo get_the_permalink().'#tkt-slctr-tbl-'.$eventID; ?>" />
                                 <input type="hidden" name="tkt-slctr-rows-<?php echo $eventID; ?>" value="1" />
                                 <input type="hidden" name="tkt-slctr-max-atndz-<?php echo $eventID; ?>" value="10" />
                                 <input type="hidden" name="tkt-slctr-event-id" value="<?php echo $eventID; ?>" />
                                 <input type="hidden" name="tkt-slctr-request-processor-email" value="" />
                                 <input type="hidden" name="tkt-slctr-request-processor-token" value="<?php echo $token; ?>" />
                                 <input type="submit"  value="Register Now" />

                             </form>
                         </li>
                         <?php
                     }
                     ?>

                </ul>
            </aside>
            <?php
        }

    }

return ob_get_clean();
}


add_shortcode( 'up_coming_events', 'up_coming_events' );
