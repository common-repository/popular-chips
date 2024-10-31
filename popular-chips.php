<?php
/*
Plugin Name: Popular Chips
Description: 人気記事のランキング機能を提供します
Author: COLORCHIPS
Version: 1.2.1
*/

require_once( plugin_dir_path(__FILE__) . 'class/admin-page.php' );
require_once( plugin_dir_path(__FILE__) . 'class/setting-page.php' );

Popular_Chips::main();

class Popular_Chips {
	
	CONST META_EXCLUDE = 'popular_chips_exclude';
	CONST META_DISABLE = 'popular_chips_disable';
	CONST META_COUNT = 'popular_chips_count';
	
	CONST OPTION_KEY = 'popular_chips_options';
	CONST CACHE_KEY = 'popular_chips_cache';
	
	public static $count_super_admin = true;
	
	public static $limit_types = array(
		'all' => 'すべて表示',
		'day' => '日以内の記事のみ',
		'week' => '週間以内の記事のみ',
		'month' => 'ヶ月以内の記事のみ',
	);
	
	public static $default_options = array(
		'label' => 'ランキング',
		'post_type' => array( 'post' ),
		'limit_value' => 0,
		'limit_type' => 'all',
	);
	
	public static function get_rankings() {
		
		$option_data = get_option( self::OPTION_KEY );
		
		if ( empty( $option_data ) ) $option_data = array();
		
		return $option_data;
	}
	
	public static function get_ranking_options( $uid = false ) {
		if ( $uid ) {
			$option_data = get_option( self::OPTION_KEY );
			if ( isset( $option_data[ $uid ] ) )
				$options = wp_parse_args( $option_data[ $uid ], self::$default_options );
		}
		if ( empty( $options ) ) {
			$options = self::$default_options;
		}
		return $options;
	}
	
	public static function set_ranking_options( $uid, $newoptions ) {
		if ( $uid ) {
			$option_data = get_option( self::OPTION_KEY );
			if ( empty( $option_data ) ) $option_data = array();
			$options = array();
			$options['label'] = $newoptions['label'];
			$options['display_num'] = (int) $newoptions['display_num'];
			$options['post_type'] = (array) $newoptions['post_type'];
			$options['limit_value'] = (int) $newoptions['limit_value'];
			$options['limit_type'] = (string) $newoptions['limit_type'];
			$option_data[ $uid ] = wp_parse_args( $options, self::$default_options );
			return update_option( self::OPTION_KEY, $option_data );
		}
		return false;
	}
	
	public static function remove_ranking_options( $uid ) {
		if ( $uid ) {
			$option_data = get_option( self::OPTION_KEY );
			if ( isset( $option_data[ $uid ] ) ) unset( $option_data[ $uid ] );
			return update_option( self::OPTION_KEY, $option_data );
		}
		return false;
	}
	
	public static function clear_ranking_options() {
		
		delete_option( self::OPTION_KEY );
	}
	
	public static function get_cache_ranking( $name = 'global' ) {
		
		$cache_data = get_option( self::CACHE_KEY );
		
		if ( empty( $cache_data ) ) $cache_data[ $name ] = array();
		
		return $cache_data[ $name ];
	}
	
	public static function cache_ranking( $name = 'global' ) {
		
		$cache_data = get_option( self::CACHE_KEY );
		
		$options = self::get_ranking_options();
		
		$cache = array(
			'time' => time(),
			'posts' => array(),
		);
		
		$posts = self::get_ranking_posts( $options );
		$rank = 1;
		foreach ( $posts as $post ) {
			$cache[ 'posts' ][ $rank ] = $post->ID;
			$rank++;
		}
		
		$cache_data[ $name ] = $cache;
		
		update_option( self::CACHE_KEY, $cache_data );
	}
	
	public static function clear_cache_ranking( $name = 'global' ) {
		
		$cache_data = get_option( self::CACHE_KEY );
		
		$cache_data[ $name ] = array();
		
		update_option( self::CACHE_KEY, $cache_data );
	}
	
	public static function get_support_post_type() {
		return get_post_types( array( 'public' => true ) );
	}
	
	public static function main() {
		add_action( 'init', array( __CLASS__, 'init' ) );
		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
		add_action( 'widgets_init', array( __CLASS__, 'register_ranking_widget' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ), null, 2 );
		add_action( 'save_post', array( __CLASS__, 'save' ), null, 2 );
	}
	
	public static function init() {
		if ( defined( 'WP_ADMIN' ) ) {
			wp_enqueue_style( 'popular-chips-admin', plugin_dir_url(__FILE__) . 'admin.css' );
			wp_enqueue_script( 'popular-chips-admin', plugin_dir_url(__FILE__) . 'admin.js' );
			
			$settingpage = new Popular_Chips_Setting_Page( 'ランキング', 'ランキング', 'edit_posts', 'popular-chips' );
			$settingpageContext = new Popular_Chips_Admin_Page_Context( $settingpage );
			$settingpageContext->addTo( 'options' );
		} else {
			if ( isset( $_GET['__c_rk_i'] ) ) {
				self::count( $_GET['__c_rk_i'] );
				self::count_img();
				exit();
			} elseif ( isset( $_GET['__c_rk_j'] ) ) {
				self::count( $_GET['__c_rk_j'] );
				exit();
			} else {
				wp_enqueue_style( 'popular-chips', plugin_dir_url(__FILE__) . 'popular-chips.css' );
				wp_enqueue_script( 'jquery' );
				// 管理者を除く
				if ( self::$count_super_admin || !is_super_admin() ) {
					add_action( 'wp', array( __CLASS__, 'bind_count_function' ) );
				}
			}
		}
		
		add_shortcode( 'ranking', array( __CLASS__, 'register_ranking_shortcode' ) );
	}
	
	public static function bind_count_function( $query ) {
		// シングルページ
		if ( is_singular() ) {
			// カウント用タグの出力
			add_action( 'wp_head', array( __CLASS__, 'count_script_tag' ) );
		}
	}
	
	public static function count( $post_id ) {
		$disable = (bool) get_post_meta( $post_id, self::META_DISABLE, true);
		if ( !$disable ) {
			$count = (int) get_post_meta( $post_id, self::META_COUNT, true);
			if ( $count > 0 ) {
				update_post_meta( $post_id, self::META_COUNT, $count + 1 );
			} else {
				add_post_meta( $post_id , self::META_COUNT, 1, true );
			}
		}
	}
	
	public static function count_increment( $query ) {
		global $post;
		self::count( $post->ID );
		remove_action( 'loop_start', array( __CLASS__, 'count_increment' ) );
	}
	
	public static function count_script_tag( $query ) {
		global $post;
		wp_enqueue_script('jquery');
?>
<script type="text/javascript">
jQuery(function(){
	jQuery.get('<?php echo home_url('?__c_rk_j='.$post->ID); ?>');
});
</script>
<?php
	}
	
	public static function count_img_tag( $output ) {
		global $post;
		$tag = '<img src="'.home_url('?__c_rk_i='.$post->ID).'" />';
		return $tag.$output;
	}
	
	public static function count_img() {
		header('Content-Type: image/gif');
		echo base64_decode('R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==');
	}
	
	public static function admin_init() {
	}
	
	public static function add_meta_boxes( $post_type, $post ) {
		if ( in_array( $post_type, self::get_support_post_type() ) ) {
			add_meta_box( 'popular_chips', 'ランキング', array( __CLASS__, 'ranking_meta_box' ), $post_type, 'side' );
		}
	}
	
	public static function ranking_meta_box() {
		global $post;
		$post_id = $post->ID;
		if ( $the_post = wp_is_post_revision( $post_id ) ) $post_id = $the_post;
		
		$count = (int) get_post_meta( $post_id, self::META_COUNT, true );
		
		if ( in_array( get_post_status(), array_keys( get_post_statuses() ) ) ) {
			$disable = (bool) get_post_meta( $post_id, self::META_DISABLE, true );
		} else {
			$disable = false;
		}
		
		$rankings = self::get_rankings();
		foreach ( $rankings as $uid => $ranking ) :
			$rank = self::get_post_ranking( $post_id, $uid );
			$include = self::is_include_ranking( $post_id, $uid );
			if ( in_array( get_post_status(), array_keys( get_post_statuses() ) ) ) {
				$exclude = self::is_exclude_ranking( $post_id, $uid );
			} else {
				$exclude = false;
			}
?>
<div class="section">
	<?php echo $ranking['label']; ?>:
<?php if ( $include ) : ?>
	<span class="popular-chips-display" <?php echo $exclude ? 'style="display: none;"' : ''; ?>> <span class="number"><?php echo $count > 0 ? number_format( $rank ) : '---'; ?></span>位</span>
	<span class="popular-chips-exclude-display" class="note" <?php echo $exclude ? '' : 'style="display: none;"'; ?>>ランキング除外中</span>
	<a class="edit-popular-chips-exclude hide-if-no-js" href="#popular_chips_exclude">編集</a>
	<div class="popular-chips-exclude-select hide-if-js">
		<input type="hidden" class="hidden-popular-chips-exclude" name="hidden_popular_chips_exclude[<?php echo $uid ?>]" value="<?php echo $exclude ? '1' : '0'; ?>" />
		<label><input type="checkbox" class="popular-chips-exclude" name="popular_chips_exclude[<?php echo $uid ?>]" value="1" <?php echo $exclude ? 'checked="checked"' : ''; ?> /> この投稿をランキングから除外する</label><br />
		<a class="save-popular-chips-exclude hide-if-no-js button" href="#popular_chips_exclude">OK</a>
		<a class="cancel-popular-chips-exclude hide-if-no-js" href="#popular_chips_exclude">キャンセル</a>
	</div>
<?php else : ?>
	<span class="note">ランキング対象外</span>
<?php endif; ?>
</div>
<?php	endforeach; ?>
<div class="section-last">
	表示回数:
	<span id="popular-chips-count-display" class="number"><?php echo number_format( $count ); ?></span>回
	<span id="popular-chips-disable-display" class="note" <?php echo $disable ? '' : 'style="display: none;"'; ?>>集計停止中</span>
	<a class="edit-popular-chips-count hide-if-no-js" href="#popular_chips_count">編集</a>
	<div id="popular-chips-count-select" class="hide-if-js">
		<input type="hidden" id="original-popular-chips-count" name="original_popular_chips_count" value="<?php echo $count; ?>" />
		<input type="hidden" id="hidden-popular-chips-count" name="hidden_popular_chips_count" value="<?php echo $count; ?>" />
		<input type="hidden" id="hidden-popular-chips-disable" name="hidden_popular_chips_disable" value="<?php echo $disable ? '1' : '0'; ?>" />
		<label><input type="checkbox" id="popular-chips-disable" name="popular_chips_disable" value="1" <?php echo $disable ? 'checked="checked"' : ''; ?> /> この投稿の表示回数の集計を停止する</label><br />
		<input type="text" id="popular-chips-count" name="popular_chips_count" value="<?php echo $count; ?>" size="5" />回
		<a class="save-popular-chips-count hide-if-no-js button" href="#popular_chips_count">OK</a>
		<a class="cancel-popular-chips-count hide-if-no-js" href="#popular_chips_count">キャンセル</a>
	</div>
</div>
<?php
	}
	
	public static function save( $post_ID, $post ) {
		if ( in_array( get_post_status( $post ), array_keys( get_post_statuses() ) ) 
			&& in_array( get_post_type( $post ), self::get_support_post_type() ) ) {
			$count = isset( $_POST[ 'popular_chips_count' ] ) ? $_POST[ 'popular_chips_count' ] : false;
			$original = isset( $_POST[ 'original_popular_chips_count' ] ) ? $_POST[ 'original_popular_chips_count' ] : false;
			if ( $count !== false && $count != $original ) {
				$metadata = get_post_meta( $post_ID, self::META_COUNT, true );
				if ( $metadata == '' )
					add_post_meta( $post_ID, self::META_COUNT, $count, true );
				else
					update_post_meta( $post_ID, self::META_COUNT, $count, $original );
			}
			$rankings = self::get_rankings();
			$excludes = isset( $_POST[ 'popular_chips_exclude' ] ) ? $_POST[ 'popular_chips_exclude' ] : array();
			foreach ( $rankings as $uid => $options ) {
				$metakey = 'popular_chips_exclude_'.$uid;
				$metadata = get_post_meta( $post_ID, $metakey, true );
				if ( isset( $excludes[ $uid ] ) && $excludes[ $uid ] ) {
					if ( $metadata == '' )
						add_post_meta( $post_ID, $metakey, '1', true );
					else
						update_post_meta( $post_ID, $metakey, '1' );
				} else {
					if ( $metadata != '' )
						delete_post_meta( $post_ID, $metakey );
				}
			}
			foreach ( array( 'popular_chips_disable' ) as $name ) {
				$value = isset( $_POST[ $name ] ) ? (bool) $_POST[ $name ] : false;
				$metadata = get_post_meta( $post_ID, $name, true );
				if ( $value ) {
					if ( $metadata == '' )
						add_post_meta( $post_ID, $name, '1', true );
					else
						update_post_meta( $post_ID, $name, '1' );
				} else {
					if ( $metadata != '' )
						delete_post_meta( $post_ID, $name );
				}
			}
		}
	}
	
	public static function is_include_ranking( $post = '', $uid = false ) {
		global $wpdb;
		$post = get_post($post);
		
		if ( !$uid ) {
			$rankings = self::get_rankings();
			list( $uid ) = array_keys( $rankings );
		}
		
		$options = self::get_ranking_options( $uid );
		extract( $options );
		
		if ( !in_array( get_post_type( $post ), $options[ 'post_type' ] ) ) {
			return false;
		}
		
		if ( $limit_value > 0 && in_array( $limit_type, array( 'day', 'week', 'month' ) ) ) {
			if ( strtotime( date( 'Y-m-d H:i:s' ). ' -'. $limit_value . ' ' . $limit_type ) > get_post_time() )
				return false;
		}
		
		return true;
	}
	
	public static function is_exclude_ranking( $post = '', $uid = false ) {
		global $wpdb;
		$post = get_post($post);
		
		if ( !$uid ) {
			$rankings = self::get_rankings();
			list( $uid ) = array_keys( $rankings );
		}
		
		$exclude = (bool) get_post_meta( $post->ID, self::META_EXCLUDE.'_'.$uid, true);
		
		return $exclude;
	}
	
	public static function get_post_ranking( $post = '', $uid = false ) {
		global $wpdb;
		$post = get_post($post);
		
		if ( !$uid ) {
			$rankings = self::get_rankings();
			list( $uid ) = array_keys( $rankings );
		}
		
		extract( self::get_ranking_options( $uid ) );
		
		if ( !self::is_include_ranking( $post, $uid ) )
			return false;
		
		$cache = self::get_cache_ranking( $uid );
		if ( !empty( $cache ) ) {
			$rank = array_search( $post->ID, $cache[ 'posts' ] );
			return $rank ? $rank : count( $cachce[ 'posts' ] ) + 1;
		}
		
		$count = (int) get_post_meta( $post->ID, self::META_COUNT, true);
		
		$sql = "SELECT wp_posts.ID, count.meta_value 'count' FROM $wpdb->posts ";
		$val = array();
		$sql.= "LEFT JOIN $wpdb->postmeta AS count ON $wpdb->posts.ID = count.post_id AND count.meta_key = %s ";
		$val = array_merge( $val, array( self::META_COUNT ) );
		$sql.= "LEFT JOIN $wpdb->postmeta AS exclude ON $wpdb->posts.ID = exclude.post_id AND exclude.meta_key = %s ";
		$val = array_merge( $val, array( self::META_EXCLUDE.'_'.$uid ) );
		$sql.= "WHERE $wpdb->posts.post_status = 'publish' AND $wpdb->posts.post_type IN ( " . join( ', ', array_fill( 0, count( $post_type ), '%s' ) ) . " ) ";
		$val = array_merge( $val, $post_type );
		$sql.= "AND ( exclude.meta_value != '1' OR exclude.meta_value IS NULL ) ";
		
		if ( $limit_value > 0 && in_array( $limit_type, array( 'day', 'week', 'month' ) ) ) {
			$sql.= "AND $wpdb->posts.post_date > %s ";
			$val = array_merge( $val, array( date( 'Y-m-d H:i:s', strtotime( date( 'Y-m-d H:i:s' ) . ' -'. $limit_value . ' ' . $limit_type ) ) ) );
		}
		
		$sql.= "GROUP BY $wpdb->posts.ID ";
		$sql.= "HAVING count.meta_value > %d ";
		$val = array_merge( $val, array( $count ) );
		$sql = $wpdb->prepare( $sql, $val );
		
		return count( $wpdb->get_results( $sql ) ) + 1;
	}
	
	public static function get_post_by_rank( $rank = 1, $uid = false ) {
		global $wpdb;
		
		if ( $uid ) {
			$option = self::get_ranking_options( $uid );
		} else {
			$rankings = self::get_rankings();
			$option = array_shift( $rankings );
		}
		
		$option = wp_parse_args( $option, array(
			'offset' => $rank-1,
			'limit' => 1,
		) );
		
		list( $post ) = self::get_ranking_posts( $option );
		
		return $post;
	}
	
	public static function get_ranking_posts( $uid = false, $option = array() ) {
		global $wpdb;
		
		if ( is_array( $uid ) ) {
			$option = $uid;
		} elseif ( $uid ) {
			$option = wp_parse_args( $option, self::get_ranking_options( $uid ) );
		} elseif ( empty( $option ) ) {
			$rankings = self::get_rankings();
			$option = array_shift( $rankings );
		}
		
		extract( wp_parse_args( $option, array(
			'offset' => 0,
			'limit' => 10,
		) ) );
		
		$sql = "SELECT $wpdb->posts.*, count.meta_value+0 'popular_chips_count' FROM $wpdb->posts ";
		$val = array();
		$sql.= "LEFT JOIN $wpdb->postmeta AS count ON $wpdb->posts.ID = count.post_id AND count.meta_key = %s ";
		$val = array_merge( $val, array( self::META_COUNT ) );
		$sql.= "LEFT JOIN $wpdb->postmeta AS exclude ON $wpdb->posts.ID = exclude.post_id AND exclude.meta_key = %s ";
		$val = array_merge( $val, array( self::META_EXCLUDE.'_'.$uid ) );
		$sql.= "WHERE $wpdb->posts.post_status = 'publish' AND $wpdb->posts.post_type IN ( " . join( ', ', array_fill( 0, count( $post_type ), '%s' ) ) . " ) ";
		$val = array_merge( $val, $post_type );
		$sql.= "AND ( exclude.meta_value != '1' OR exclude.meta_value IS NULL ) ";
		
		if ( $limit_value > 0 && in_array( $limit_type, array( 'day', 'week', 'month' ) ) ) {
			$sql.= "AND $wpdb->posts.post_date > %s ";
			$val = array_merge( $val, array( date( 'Y-m-d H:i:s', strtotime( date( 'Y-m-d H:i:s' ) . ' -'. $limit_value . ' ' . $limit_type ) ) ) );
		}
		
		$sql.= "GROUP BY $wpdb->posts.ID ";
		$sql.= "ORDER BY count.meta_value+0 DESC, post_date DESC ";
		$sql.= "LIMIT %d, %d ";
		$val = array_merge( $val, array( $offset, $limit ) );
		$sql = $wpdb->prepare( $sql, $val );
		
		$posts = $wpdb->get_results( $sql );
		
		return $posts;
		
		/*
		WordPress 3.2 以降ならば、meta_queryとtax_queryを駆使してget_postsできるはず。
		$args = array(
			'numberposts' => $limit, 'offset' => $offset,
			'orderby' => 'meta_value_num', 'order' => 'DESC',
			'meta_key' => self::META_COUNT, 'meta_value' =>'',
			'post_type' => self::get_support_post_type(), 'suppress_filters' => true
		);
		
		return get_posts( $args );
		*/
	}
	
	public static function register_ranking_widget() {
		include_once plugin_dir_path( __FILE__ ) . 'popular-chips-widget.php';
		register_widget( 'Popular_Chips_Widget' );
	}
	
	public static function register_ranking_shortcode( $atts, $content, $code ) {
		extract( shortcode_atts( array(
			'no' => 1,
			'name' => 'title',
			'length' => 70,
			'link' => false,
			'thumb' => false,
			'class' => '',
			'uid' => false,
		), $atts ) );
		
		if ( !$uid ) {
			$rankings = array_keys( self::get_rankings() );
			$option = array_shift( $rankings );
		}
		
		$post = self::get_post_by_rank( $no, $uid );
		
		$ret = '';
		if ( $post ) {
			switch ( $name ) {
				case 'title' :
					$ret = $post->post_title;//.'('.$post->popular_chips_count.')';
					break;
				case 'class' :
					$ret = join( ' ', get_post_class( $class, $post->ID ) );
					break;
				case 'link' :
					$ret = get_permalink( $post->ID );
					break;
				case 'body' :
					$body = str_replace( array( "\n", "\r" ), '', strip_tags( strip_shortcodes( $post->post_content ) ) );
					if ( mb_strlen( $body ) > $length ) {
						$ret = mb_substr( $body, $start, $length ) . '…';
					} else {
						$ret = $body;
					}
					break;
				case 'series' :
					$series = mansionlab_current_category( $post->ID );
					if ( $series->parent > 0 ) {
						$posted_in = '%1$s＜%2$s＞';
						$category = mansionlab_parent_term( $post->ID, $series->taxonomy );
					} else {
						$posted_in = '%2$s';
					}
					$ret = sprintf( $posted_in, $category->name, $series->name );
					break;
				case 'date' :
					$ret = mysql2date( get_option('date_format'), $post->post_date );
					break;
				case 'image' :
					if ( $thumb == false ) {
						if ( $no > 1 ) {
							$thumb = 'post-category-thumbnail-small';
						} else {
							$thumb = 'post-category-thumbnail-large';
						}
					}
					$ret =  get_the_post_thumbnail( $post->ID, $thumb );
					break;
			}
		}
		
		if ( $link && $ret != '' ) {
			$ret = '<a href="'.get_permalink( $post->ID ).'">'.$ret.'</a>';
		}
		
		return $ret;
	}
}