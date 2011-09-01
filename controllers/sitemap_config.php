<?

 class Sitemap_Config extends Backend_Controller {
 	
 	protected $access_for_groups = array(Users_Groups::admin);
 	public $form_edit_title = 'Sitemap Configuration';
	public $form_model_class = 'Sitemap_Params';
	public $implement = 'Db_ListBehavior, Db_FormBehavior';
	public $form_redirect = null;
	public $form_edit_save_flash = 'Sitemap configuration has been saved.';
 
 	public function __construct() {
		parent::__construct();
			
		//set up the menu tabs
		$this->app_module = 'system';
		$this->app_tab = 'system';
		$this->app_page = 'settings';
			
		$this->app_module_name = 'System';		
		$this->app_page_title = 'Sitemap';
		}
		
		public function index() {
			try {
				$params = new Sitemap_Params();
				$this->viewData['form_model'] = $params->load();
				$this->viewData['pages'] = Cms_Page::create()->order('navigation_sort_order asc')->find_all();
			}
			catch(exception $ex) {
				$this->_controller->handlePageError($ex);
			}
		}
		
		public function index_onSave() {
			try {
				$config = new Sitemap_Params();
				$config = $config->load();

				$config->save(post($this->form_model_class, array()), $this->formGetEditSessionKey());

				echo Backend_Html::flash_message('Sitemap configuration has been successfully saved.');
			}
			catch(Exception $ex) {
				Phpr::$response->ajaxReportException($ex, true, true);
			}			
			
			//save pages
			$page_list = Cms_Page::create()->find_all(); 
			foreach($page_list as $id=>$page) {
				$visible = 0;
				if(isset($_POST['pages'][$page->id])) {
					$visible = 1;
				} 
				Db_DbHelper::query('update pages set sitemap_visible=:visible where id=:id', array('visible' => $visible, 'id' => $page->id));
			}
		}

 }