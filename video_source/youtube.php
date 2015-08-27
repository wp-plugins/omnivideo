<?php
/**
 * YouTube omnivideo source
 */
class OmniVideo_YouTube {
  
  protected $youtube_key; //pass in by constructor
  
  public function __construct()
  {
    $this->youtube_key = 'AIzaSyCnok8P1LcO4l2yDLijxr1HQHXe88_nPnA';  
  }   
  
  /**
   * Get Channnel ID
   */
  function get_channel_id( $username ) {
  
    $api_url = 'https://www.googleapis.com/youtube/v3/channels?part=id&forUsername='.$username.'&key='.$this->youtube_key;
    $data = wp_remote_get( $api_url );

    if( !is_wp_error( $data ) ) {
      $result = json_decode($data['body']);
      return $result->items[0]->id;
    }else{
      return;
    }  
  }
  
  /**
   * Get Video Feed
   */
  function get_video_feed( $atts ) {
    extract( $atts );
    
    $result = $result + 1;
    
    $channel_id = 0;
    $channel_id = $this->get_channel_id($username);
    
    $feed_url = 'https://www.googleapis.com/youtube/v3/search?part=snippet&channelId='.$channel_id.'&maxResults='.$result.'&key='.$this->youtube_key;
    $cache_key = 'omnivideo_' . md5( $feed_url );

    // Check for cache
    $results = get_transient( $cache_key );
    $results = false;

    // If cache is empty
    if( false === $results ) {
      $data = wp_remote_get( $feed_url );

      if( !is_wp_error( $data ) ) {
        $cache = set_transient( $cache_key, json_encode( $data['body'] ), HOUR_IN_SECONDS );
        return json_decode($data['body']);

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
    if( !is_object( $data ) ) {
      return __('Error fetching data', 'omnivideo');
    }
    
    extract( $atts );
    $output = '';

    foreach ($data->items as $entry) {
      $vid_id = '';
      
      if (isset($entry->id->videoId)) {
        $vid_id = $entry->id->videoId;
      }
      
      if( $vid_id ) {
      
        $img = '';
        $img = esc_url($entry->snippet->thumbnails->high->url);
        $output .= '<li class="gallery-item">';
        $output .= '<div class="omnivideo-thumb">';
          
          if( 'redirect' == $type ) {
            $output .= '<a href="http://youtube.com/watch?v='.$vid_id.'" target="_blank" title="'.esc_html($entry->snippet->title).'">';
            $output .= '<img src="'.$img.'" /></a>';
          }

          else {
            $output .= '<a href="#video-modal" data-iframe="//www.youtube.com/embed/'.$vid_id.'"  data-toggle="modal" title="'.esc_html($entry->snippet->title).'" class="test-pop"><img src="'.$img.'" /></a>';

            if ($description == true) {
              if( is_string( $entry->snippet->description ) ) {
                $output .= '<div class="omni-description">'.esc_html($entry->snippet->description).'</div>';
              }
            }

            $output .= '<div class="modal fade" id="video-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                  <h3 class="modal-title" id="myModalLabel">'.esc_html($entry->snippet->title).'</h3>
                  </div>

                  <div class="modal-body">
                  </div>
                    
                </div>
              </div>
            </div>';
          }

        $output .= '</div>';
        $output .= '<div class="gallery-caption">'.esc_html($entry->snippet->title).'</div>';  
        $output .= '</li>';
      }
    }

    return $output;
  }

}