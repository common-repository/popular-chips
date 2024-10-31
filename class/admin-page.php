<?php
/**
 * 管理画面ページ抽象クラス
 */
abstract class Popular_Chips_Admin_Page_Abstract {
	
	private $page_title = '';
	private $menu_title = '';
	private $capability = '';
	private $menu_slug = '';
	private $hookname;
	private $parent_page;
	private $section;
	private $position;
	
	private $action = 'default';
	private $view;
	
	/**
	 * コンストラクタ
	 */
	public function __construct($page_title,$menu_title,$capability,$menu_slug) {
		$this->page_title = $page_title;
		$this->menu_title = $menu_title;
		$this->capability = $capability;
		$this->menu_slug = $menu_slug;
	}
	
	/**
	 * ページ名を返す
	 * @return $page_title
	 */
	public function getPageTitle() {
		return $this->page_title;
	}
	
	/**
	 * メニュータイトルを返す
	 * @return $menu_title
	 */
	public function getMenuTitle() {
		return $this->menu_title;
	}
	
	/**
	 * ページ権限を返す
	 * @return $capability
	 */
	public function getCapability() {
		return $this->capability;
	}
	
	/**
	 * メニュースラッグを返す
	 * @return $menu_slug
	 */
	public function getMenuSlug() {
		return $this->menu_slug;
	}
	
	/**
	 * フック名を設定する
	 * @param $hookname
	 */
	public function setHookName($hookname) {
		$this->hookname = $hookname;
	}
	
	/**
	 * フック名を設定する
	 * @param $hookname
	 */
	public function getHookName() {
		return $this->hookname;
	}
	
	/**
	 * 親ページを設定する
	 * @param Popular_Chips_Admin_Page_Abstract $page
	 */
	public function setParentPage($page) {
		$this->parent_page = $page;
	}
	
	/**
	 * 親ページを返す
	 * @param $position
	 */
	public function getParentPage() {
		return $this->parent_page;
	}
	
	/**
	 * ページセクションを設定する
	 * @param $position
	 */
	public function setSection($section) {
		$this->section = $section;
	}
	
	/**
	 * ページセクションを設定する
	 * @param $position
	 */
	public function getSection() {
		return $this->section;
	}
	
	/**
	 * ページ位置を設定する
	 * @param $position
	 */
	public function setPosition($position) {
		$this->position = $position;
	}
	
	/**
	 * ページ位置を設定する
	 * @param $position
	 */
	public function getPosition() {
		return $this->position;
	}
	
	/**
	 * ページを追加し、各アクションにフックする
	 * @param String ページの追加場所
	 */
	public function addPage() {
		
		$parent_page = $this->getParentPage();
		$parent_slug = false;
		if ( is_a( $parent_page, 'Popular_Chips_Admin_Page_Abstract' ) ) {
			$parent_slug = $parent_page->getMenuSlug();
		}
		
		$section = $this->getSection();
		$position = $this->getPosition();
		$page_title = $this->getPageTitle();
		$menu_title = $this->getMenuTitle();
		$capability = $this->getCapability();
		$menu_slug = $this->getMenuSlug();
		$function = array( $this, 'hook_view' );
		
		if ( $parent_slug ) {
			$hookname = add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
		} else {
			switch ( $section ) {
				case 'management':
					$hookname = add_management_page( $page_title, $menu_title, $capability, $menu_slug, $function );
					break;
				case 'options':
					$hookname = add_options_page( $page_title, $menu_title, $capability, $menu_slug, $function );
					break;
				case 'theme':
					$hookname = add_theme_page( $page_title, $menu_title, $capability, $menu_slug, $function );
					break;
				case 'plugins':
					$hookname = add_plugins_page( $page_title, $menu_title, $capability, $menu_slug, $function );
					break;
				case 'users':
					$hookname = add_users_page( $page_title, $menu_title, $capability, $menu_slug, $function );
					break;
				case 'dashboard':
					$hookname = add_dashboard_page( $page_title, $menu_title, $capability, $menu_slug, $function );
					break;
				case 'posts':
					$hookname = add_posts_page( $page_title, $menu_title, $capability, $menu_slug, $function );
					break;
				case 'media':
					$hookname = add_media_page( $page_title, $menu_title, $capability, $menu_slug, $function );
					break;
				case 'links':
					$hookname = add_links_page( $page_title, $menu_title, $capability, $menu_slug, $function );
					break;
				case 'pages':
					$hookname = add_pages_page( $page_title, $menu_title, $capability, $menu_slug, $function );
					break;
				case 'comments':
					$hookname = add_comments_page( $page_title, $menu_title, $capability, $menu_slug, $function );
					break;
				case 'object':
					$hookname = add_object_page( $page_title, $menu_title, $capability, $menu_slug, $function );
					break;
				case 'utility':
					$hookname = add_utility_page( $page_title, $menu_title, $capability, $menu_slug, $function );
					break;
				default:
					$hookname = add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function );
					break;
			}
		}
		
		$this->setHookName( $hookname );
		
		add_action( 'load-'.$hookname, array( $this, 'load' ) );
	}
	
	/**
	 * 処理全般
	 */
	public function load() {
		$this->action = empty( $_REQUEST[ 'action' ] ) ? '' : $_REQUEST[ 'action' ];
		
		add_filter( 'favorite_actions', array( $this, 'favorite_actions' ) );
		
		// 初期処理
		$initialize_result = $this->initialize( $this->action );
		if ( is_wp_error( $initialize_result ) ) wp_die( $initialize_result->get_error_message() );
		elseif ( $initialize_result === false ) wp_die('初期処理に失敗しました。');
		
		// POST処理
		if ( isset( $_POST[ 'action' ] ) && $_POST[ 'action' ] == $this->action ) {
			$view = $this->execute( $this->action );
		}
		// GET処理
		else {
			$action_function = array( $this, $this->action.'_action' );
			if ( !is_callable( $action_function ) ) $action_function = array( $this, 'default_action' );
			$view = call_user_func( $action_function );
		}
		
		// 表示処理を行うファンクション名
		if ( !empty( $view ) ) {
			$this->view = $view.'_view';
		}
	}
	protected abstract function initialize( $action );
	protected abstract function execute( $action );
	protected abstract function default_action();
	
	/**
	 * 表示処理
	 */
	public function hook_view() {
		// 表示処理
		$view_function = array( $this, $this->view );
		if ( is_callable( $view_function ) ) call_user_func( $view_function );
	}
	protected abstract function default_view();
	
	/**
	 * アクションリンクを取得
	 * @param $action アクション名
	 * @param $params パラメータ
	 */
	public function link( $action = 'default', $params = array() ){
		global $_parent_pages;
		$query = array_filter( wp_parse_args( $params, array(
			'page' => $this->getMenuSlug(),
			'action' => $action == 'default' ? '' : $action,
		) ) );
		$parent_file = $_parent_pages[ $this->getMenuSlug() ];
		$link = $parent_file ? $parent_file : 'admin.php';
		$link.= '?'.http_build_query( $query );
		return admin_url( $link );
	}
	
	/**
	 * favorite_actionsにリンクを追加するフィルター
	 */
	public function favorite_actions( $actions ){
		return $actions;
	}
}

class Popular_Chips_Admin_Page_Context {
	
	private $page;
	
	/**
	 * コンストラクタ
	 * @param Popular_Chips_Admin_Page_Abstract Popular_Chips_Admin_Page_Abstractオブジェクト
	 */
	public function __construct(Popular_Chips_Admin_Page_Abstract $page) {
		$this->page = $page;
	}
	
	/**
	 * admin_menuアクションにトップレベルページの追加処理をフックする
	 * $param $section
	 */
	public function addTo( $section ) {
		
		if ( is_a( $section, 'Popular_Chips_Admin_Page_Abstract' ) ) {
			$this->page->setParentPage( $section );
		} else {
			$this->page->setSection( $section );
		}
		
		add_action( 'admin_menu', array( $this->page, 'addPage' ) );
	}
}