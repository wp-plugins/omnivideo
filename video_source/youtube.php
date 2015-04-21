<?php
/**
 * YouTube omnivideo source
 */
class OmniVideo_YouTube {
  
  /**
   * Get Video Feed
   */
  function get_video_feed( $atts ) {
    extract( $atts );

    $feed_url = "http://gdata.youtube.com/feeds/api/users/$username/uploads/?start-index=1&max-results=$result";
    $cache_key = 'omnivideo_' . md5( $feed_url );

    // Check for cache
    $results = get_transient( $cache_key );

    // If cache is empty
    if( false === $results ) {
      $data = wp_remote_get( $feed_url );

      if( !is_wp_error( $data ) ) {
        $response =  new SimpleXMLElement( $data['body'] );
        $cache = set_transient( $cache_key, json_encode( $response ), HOUR_IN_SECONDS );
        return json_encode( $response );

      } else {
        return false;
      }
    } else {
      return $results;
    }
  }


  /**
   * Render Video
   */
  function render( $atts ) {
    $atts = shortcode_atts(array(
      'username' => '',
      'result' => '1',
      'type' => 'image',
      'description' => false, 
    ), $atts);

    extract( $atts );

    $output = '';

    if( empty( $username ) ) {
      $output = '<h3>'. __( 'Oopssss! You forgot to fill username', 'omnivideo' ) .'</h3>';
    } else {
      $feed = $this->get_video_feed( $atts );

      if( $feed ) {
        $feed_data = json_decode( $feed );
        $output .= $this->build_html( $feed_data, $atts );

      // Feed failed to be fetchec
      } else {
        $output = '<h3>'. __( 'Error fetching video, please try again a few minute later.', 'omnivideo' ) .'</h3>';
      }
    }

    return $output;
  }


  /**
   * Build HTML structure
   */
  function build_html( $data, $atts ) {
    if( !is_object( $data ) ) {
      return __('Error fetching data', 'omnivideo');
    }

    extract( $atts );
    $output = '';

    foreach ($data->entry as $entry) {
      $vid_id = '';
      if ( preg_match('#videos/([^/]+)$#', $entry->id, $matches) ) {
        $vid_id = $matches[1];
      }

      if( $vid_id ) {
        $output .= '<li class="gallery-item">';
        $output .= '<div class="omnivideo-thumb">';
          
          if( 'redirect' == $type ) {
            $output .= '<a href="http://youtube.com/watch?v='.$vid_id.'" target="_blank" title="'.esc_html($entry->title).'">';
            $output .= '<img src="http://i1.ytimg.com/vi/'.$vid_id.'/0.jpg" /></a>';
          }

          else {
            $output .= '<a href="#video-modal" data-iframe="//www.youtube.com/embed/'.$vid_id.'"  data-toggle="modal" title="'.esc_html($entry->title).'" class="test-pop"><img src="http://i1.ytimg.com/vi/'.$vid_id.'/0.jpg" /></a>';

            if ($description == true) {
              if( is_string( $entry->content ) ) {
                $output .= '<div class="omni-description">'.esc_html($entry->content).'</div>';
              }
            }

            $output .= '<div class="modal fade" id="video-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                  <h3 class="modal-title" id="myModalLabel">'.esc_html($entry->title).'</h3>
                  </div>

                  <div class="modal-body">
                  </div>
                    
                </div>
              </div>
            </div>';
          }

        $output .= '</div>';
        $output .= '<div class="gallery-caption">'.esc_html($entry->title).'</div>';  
        $output .= '</li>';
      }
    }

    return $output;
  }

}