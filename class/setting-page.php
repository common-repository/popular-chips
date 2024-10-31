<?php

class Popular_Chips_Setting_Page extends Popular_Chips_Admin_Page_Abstract {
	
	public $uid = false;
	public $messages = array();
	
	public function initialize( $action ) {
		global $current_screen;
		
		//add_contextual_help($current_screen,'<p>Yes!</p>');
		
		//$this->add_message( 'ふっじっさーん！' );
	}
	
	public function get_messages( $message_id = false ) {
		if ( !$message_id && isset( $_REQUEST['message'] ) ) $message_id = $_REQUEST['message'];
		switch ( $message_id ) {
			case '1': $this->messages[] = array( 'updated', '保存しました。<a href="'.$this->link().'">リストに戻る</a>' ); break;
			case '30': $this->messages[] = array( 'updated', 'ランキング条件を設定してください。' ); break;
		}
		return empty( $this->messages ) ? false : $this->messages;
	}
	
	public function add_message( $message, $class = 'updated' ) {
		if ( $message ) {
			$this->messages[] = array( $class, $message );
		}
	}
	
	public function display_message() {
		$messages = $this->get_messages();
		if ( empty( $messages ) ) return false;
		foreach ( $messages as $message ) :
			list( $class, $text ) = $message;
?>
<div class="<?php echo $class; ?> below-h2" id="message"><p><?php echo $text; ?></p></div>
<?php
		endforeach;
	}
	
	public function execute( $action ) {
		switch ( $action ) {
			case 'save':
				$uid = $_POST[ 'uid' ] ? $_POST[ 'uid' ] : md5(uniqid(rand(),1));
				Popular_Chips::set_ranking_options( $uid, $_POST );
				wp_redirect( $this->link( 'edit', "uid=$uid&message=1" ) );
				break;
			default:
				wp_redirect( $this->link( 'default' ) );
				break;
		}
		exit();
	}
	
	public function header() {
?>
<div class="wrap">
<div class="icon32" id="icon-options-general"><br></div>
<h2><?php echo $this->getPageTitle(); ?><a class="button add-new-h2" href="<?php echo $this->link( 'new' ); ?>">新規追加</a></h2>
<?php $this->display_message(); ?>
<?php
	}
	
	public function footer() {
?>
<div id="copyright">Extended by <a href="http://www.colorchips.co.jp/" target="_blank">COLORCHIPS</a></div>
</div>
<?php
	}
	
	public function default_action() {
		$rankings = Popular_Chips::get_rankings();
		if ( empty( $rankings ) ) {
			wp_redirect( $this->link( 'new', 'message=30' ) );
			exit();
		}
		return 'default';
	}
	
	public function default_view() {
		global $current_screen, $wp_post_types;
		$rankings = Popular_Chips::get_rankings();
		$columns = array(
			'label' => 'ラベル',
			'post_type' => '投稿タイプ',
			'limit_type' => '期間限定',
			'_control' => '操作',
		);
		$this->header();
?>
<form action="" method="post">
	<div id="poststuff" class="">
		<p>現在登録されているランキング条件の一覧です。</p>
		<div id="post-body">
			<div id="post-body-content" class="">
				<table class="widefat">
					<thead>
						<tr>
<?php foreach ( $columns as $prop => $label ) : ?>
							<th class="<?php echo $prop; ?>"><?php echo $label ?></th>
<?php endforeach; ?>
						</tr>
					</thead>
					<tbody>
<?php if ( !empty( $rankings ) ) : ?>
<?php foreach ( $rankings as $uid => $ranking ) : ?>
						<tr>
<?php foreach ( $columns as $prop => $label ) : ?>
							<td class="<?php echo $prop; ?>">
<?php switch ( $prop ) : ?>
<?php case 'label' : ?>
								<a href="<?php echo $this->link( 'edit', "uid=$uid" ); ?>"><?php echo $ranking[ $prop ]; ?></a>
<?php break; ?>
<?php case 'post_type' : ?>
								<?php
									$tmp = array();
									foreach ( $ranking[ 'post_type' ] as $post_type ) {
										$tmp[] = $wp_post_types[ $post_type ]->label;
									}
									echo join( ', ', $tmp );
								?>
<?php break; ?>
<?php case 'limit_type' : ?>
								<?php
									echo $ranking[ 'limit_type' ] == 'all' ? '' : $ranking[ 'limit_value' ];
									echo Popular_Chips::$limit_types[ $ranking[ 'limit_type' ] ];
								?>
<?php break; ?>
<?php case '_control' : ?>
								<a href="<?php echo $this->link( 'edit', "uid=$uid" ) ?>">編集</a>
								<a href="<?php echo $this->link( 'delete', "uid=$uid" ) ?>">削除</a>
<?php break; ?>
<?php default : ?>
								<?php echo $ranking[ $prop ]; ?>
<?php break; ?>
<?php endswitch; ?>
							</td>
<?php endforeach; ?>
						</tr>
<?php endforeach; ?>
<?php else : ?>
						<tr>
							<td colspan="<?php echo count( $columns ); ?>">
								ランキング条件が設定されていません。新しいランキングを追加してください。
							</td>
						</tr>
<?php endif; ?>
					</tbody>
					<tfoot>
						<tr>
<?php foreach ( $columns as $prop => $label ) : ?>
							<th class="<?php echo $prop; ?>"><?php echo $label ?></th>
<?php endforeach; ?>
						</tr>
					</tfoot>
				</table>
			</div>
		</div>
	</div>
</form>
<?php
		$this->footer();
	}
	
	public function new_action() {
		return 'form';
	}
	
	public function edit_action() {
		if ( $_REQUEST[ 'uid' ] ) {
			$this->uid = $_REQUEST[ 'uid' ];
		}
		return 'form';
	}
	
	public function delete_action() {
		if ( $_REQUEST[ 'uid' ] ) {
			$this->uid = $_REQUEST[ 'uid' ];
			Popular_Chips::remove_ranking_options( $this->uid );
		}
		wp_redirect( $this->link() );
		exit();
	}
	
	public function form_view() {
		global $current_screen, $wp_post_types;
		
		$options = Popular_Chips::get_ranking_options( $this->uid );
		
		$this->header();
?>
<form action="" method="post">
	<table class="form-table">
		<tr valign="top">
			<th scope="row"><label>ラベル</label></th>
			<td>
				<input type="text" name="label" value="<?php echo esc_attr( $options[ 'label' ] ); ?>" />
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label>投稿タイプ</label></th>
			<td>
<?php foreach ( Popular_Chips::get_support_post_type() as $post_type ) : ?>
				<input id="<?php echo "post_type_$post_type"; ?>" name="post_type[]" type="checkbox" value="<?php echo $post_type; ?>" <?php echo in_array( $post_type, $options[ 'post_type' ] ) ? 'checked="checked"' : '' ?> />
				<label for="<?php echo "post_type_$post_type"; ?>"><?php echo $wp_post_types[ $post_type ]->labels->name; ?></label><br />
<?php endforeach; ?>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label>期間指定</label></th>
			<td>
				<input name="limit_value" type="text" size="4" value="<?php echo esc_attr( $options[ 'limit_value' ] ); ?>" />
				<select name="limit_type">
<?php foreach ( Popular_Chips::$limit_types as $limit_type => $label ) : ?>
					<option value="<?php echo $limit_type; ?>" <?php echo $limit_type == $options[ 'limit_type' ] ? 'selected="selected"' : '' ?>><?php echo $label; ?></option>
<?php endforeach ?>
				</select>
			</td>
		</tr>
	</table>
	<p class="submit">
		<a class="button-secondary" href="<?php echo self::link(); ?>">キャンセル</a>
		<input type="submit" value="<?php echo $this->uid ? '変更を保存' : '新規登録'; ?>" class="button-primary" name="submit">
	</p>
	<input type="hidden" name="action" value="save" />
	<input type="hidden" name="uid" value="<?php echo $this->uid; ?>" />
	<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'cc-ranking-setting' );?>" />
</form>
<?php
		$this->footer();
	}
}