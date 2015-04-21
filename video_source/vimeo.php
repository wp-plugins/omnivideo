<?php
/**
 * Vimeo omnivideo source
 */
class OmniVideo_Vimeo {

  /**
   * Get Video Feed
   */
  function get_video_feed( $atts ) {
    extract( $atts );

    $feed_url = "http://vimeo.com/api/v2/$username/videos.json";
    $cache_key = 'omnivideo_' . md5( $feed_url );

    // Check for cache
    $results = get_transient( $cache_key );

    // If cache is empty
    if( false === $results ) {
      $data = wp_remote_get( $feed_url );

      if( !is_wp_error( $data ) ) {
        $response = json_decode( $data['body'] );
        $cache = set_transient( $cache_key, $response, HOUR_IN_SECONDS );
        return $response;

      } else {
        return false;
      }
    } else {
      return $results;
    }
  }


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
        $output .= $this->build_html( $feed, $atts );

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
    if( !is_array( $data ) ) {
      return __('Error fetching data', 'omnivideo');
    }
    
    extract( $atts );
    $output = '';
    $counter = 1;

    foreach ($data as $entry) {

      $output .= '<li class="gallery-item">';
      $output .= '<div class="omnivideo-thumb">';
            
      if ($type =='popup') {
            
        $output .= '<a href="#video-modal" data-iframe="//player.vimeo.com/video/'.esc_html($entry->id).'" data-toggle="modal" class="test-pop" ><img src="'. esc_html($entry->thumbnail_medium).'" /></a>';
        
        if ($description == true) { 
          $output .= '<div class="omni-description">'.esc_html($entry->description).'</div>';
        }
              
        $output .= '<div class="modal fade" id="video-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
              <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h3 class="modal-title"></h3>
              
              </div>
              <div class="modal-body">
              
              </div>              
            </div><!-- /.modal-content -->
            </div><!-- /.modal-dialog -->
          </div>';
      } else { 
        $output .= '<a href="'.esc_html($entry->url).'" target="_blank" title="'.esc_html($entry->title).'"><img src="'.esc_html($entry->thumbnail_medium).'" /></a>';
      }

      $output .= '</div>';
      $output .= '<div class="gallery-caption">'.esc_html($entry->title).'</div>';  
      $output .= '</li>';
        
      if($counter==$result) break;
      $counter++;

    }

    return $output;
  }

}