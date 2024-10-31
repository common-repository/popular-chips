<?php

class Popular_Chips_Widget extends WP_Widget {
	
	public function Popular_Chips_Widget() {
		$widget_ops = array('classname' => 'widget_popular_chips', 'description' => '投稿のランキングを表示' );
		$this->WP_Widget( 'popular_chips', 'ランキング', $widget_ops );
	}
	
	public function widget( $args, $instance ) {
		extract( $args );
		$title = apply_filters( 'widget_title', $instance[ 'title' ] );
		
		$options = array();
		$options[ 'limit' ] = (int) $instance[ 'limit' ];
		
		$posts = Popular_Chips::get_ranking_posts( $instance[ 'uid' ], $options );
		if ( !$posts ) return;
		
		echo $before_widget;
		if ( $title )
			echo $before_title . $title . $after_title;
?>
		<ol>
<?php $rank = 1; foreach ( $posts as $post ) : ?>
			<li class="<?php echo "rank-$rank"; ?>">
				<a href="<?php echo get_permalink( $post ); ?>"><?php echo get_the_title( $post ); ?></a>
			</li>
<?php $rank++; endforeach; ?>
		</ol>
<?php
		echo $after_widget;
		
		return;
	}
	
	public function form( $instance ) {
		global $wp_post_types;
		$instance = wp_parse_args( (array) $instance, array( 'title' => '', 'limit' => 5 ) );
		$title = strip_tags( $instance[ 'title' ] );
		$limit = intval( $instance[ 'limit' ] );
		$rankings = Popular_Chips::get_rankings();
?>
<div id="popular_chips-widget">
	<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" /></p>
	<p><label for="<?php echo $this->get_field_id( 'uid' ); ?>">ランキング:</label>
		<select class="widefat" id="<?php echo $this->get_field_id( 'uid' ); ?>" name="<?php echo $this->get_field_name( 'uid' ); ?>">
<?php foreach ( $rankings as $uid => $ranking ) : ?>
			<option value="<?php echo $uid; ?>" <?php echo isset( $instance[ 'uid' ] ) && $uid == $instance[ 'uid' ] ? 'selected="selected"' : '' ?>><?php echo $ranking[ 'label' ]; ?></option>
<?php endforeach ?>
		</select></p>
	<p><label for="<?php echo $this->get_field_id( 'limit' ); ?>">件数:</label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'limit' ); ?>" name="<?php echo $this->get_field_name( 'limit' ); ?>" type="text" value="<?php echo esc_attr( $limit ); ?>" /></p>
</div>
<?php
	}
}