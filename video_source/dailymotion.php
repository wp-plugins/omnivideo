<?php
/**
 * DailyMotion omnivideo source
 */
class OmniVideo_DailyMotion {
  

  /**
   * Get Video Feed
   */
  function get_video_feed( $atts ) {
    extract( $atts );

    $feed_url = "https://api.dailymotion.com/user/$username/videos?fields=url,id,description,embed_url%2Cid%2Cthumbnail_url%2Ctitle%2Curl&limit=$result";
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


  /**
   * Render video list
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
        $output .= $this->build_html( $feed, $atts );

      // Feed failed to be fetched
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

    foreach ($data->list as $entry) {
      $output .=  '<li class="gallery-item">';
      $output .=  '<div class="omnivideo-thumb">';

      if ($type =='redirect') {
        $output .= '<a href="'.esc_html($entry->url).'" target="_blank" title="'.esc_html($entry->title).'"><img src="'.esc_html($entry->thumbnail_url).'" /></a>';
      } else {
        $output .= '<a href="#video-modal" data-iframe="http://www.dailymotion.com/embed/video/'.esc_html($entry->id).'" data-toggle="modal" title="'.esc_html($entry->title).'" class="test-pop"><img src="'.esc_html($entry->thumbnail_url).'" /></a>';
              
        if ($description == 'true') { 
          $output .= '<div class="omni-description">'.esc_html($entry->description).'</div>';
        }
            
        $output .= '<div class="modal fade" id="video-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                      <div class="modal-dialog">
                        <div class="modal-content">
                          <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                            <h3 class="modal-title" id="myModalLabel">
                              <?php echo esc_html($entry->title); ?>
                            </h3>
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

    return $output;     
  }

}