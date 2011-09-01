<?

	class Sitemap_Params extends Core_Configuration_Model {
		public $record_code = 'sitemap_params';
		
		private $changefreq_options = array (
			'always'=>'always',
			'hourly' => 'hourly',
			'daily' => 'daily',
			'weekly' => 'weekly',
			'monthly' => 'monthly',
			'yearly' => 'yearly',
			'never' => 'never'
		);
	
		public static function create() {
			$config = new self();

			return $config->load();
		}
		
		protected function build_form() {
			$this->add_field('include_categories', 'Generate individual category pages', 'full', db_varchar)->tab('Categories')->renderAs(frm_checkbox);
			$this->add_field('categories_path', 'Category Root Path', 'full', db_varchar)->tab('Categories');
			$this->add_field('categories_changefreq', 'Category changefreq', 'full', db_varchar)->tab('Categories')->renderAs(frm_dropdown);
			$this->add_field('categories_priority', 'Category priority', 'full', db_varchar)->tab('Categories')->validation('The category priority field should contain a number between 0 and 1')->method('priority_validation');
			
			$this->add_field('include_products', 'Generate individual product pages', 'full', db_varchar)->tab('Products')->renderAs(frm_checkbox);
			$this->add_field('products_path', 'Product Root Path', 'full', db_varchar)->tab('Products');
			$this->add_field('products_changefreq', 'Product changefreq', 'full', db_varchar)->tab('Products')->renderAs(frm_dropdown);
			$this->add_field('products_priority', 'Product priority', 'full', db_varchar)->tab('Products')->validation('The products priority field should contain a number between 0 and 1')->method('priority_validation');
			
			$this->add_field('include_blogposts', 'Generate individual blog post pages', 'full', db_varchar)->tab('Blog Posts')->renderAs(frm_checkbox);
			$this->add_field('blogposts_path', 'Blog posts Root Path', 'full', db_varchar)->tab('Blog Posts');
			$this->add_field('blogposts_changefreq', 'Blog posts changefreq', 'full', db_varchar)->tab('Blog Posts')->renderAs(frm_dropdown);
			$this->add_field('blogposts_priority', 'Blog posts priority', 'full', db_varchar)->tab('Blog Posts')->validation('The blog posts priority field should contain a number between 0 and 1')->method('priority_validation');
			
			$wiki_installed = Core_ModuleManager::findById('wiki');
			if($wiki_installed) {
				$this->add_field('include_wiki', 'Generate individual wiki pages', 'full', db_varchar)->tab('Wiki Pages')->renderAs(frm_checkbox);
				$this->add_field('wiki_path', 'Wiki pages Root Path', 'full', db_varchar)->tab('Wiki Pages');
				$this->add_field('wiki_changefreq', 'Wiki pages changefreq', 'full', db_varchar)->tab('Wiki Pages')->renderAs(frm_dropdown);
				$this->add_field('wiki_priority', 'Blog posts priority', 'full', db_varchar)->tab('Wiki Pages')->validation('The wiki pages priority field should contain a number between 0 and 1')->method('priority_validation');
			}
			
			$this->add_form_custom_area('pages')->tab('CMS Pages');
		}
		
		protected function init_config_data() {
			//default values
			$this->include_categories = 0;
			$this->categories_path = '/store/category';
			$this->categories_changefreq = 'monthly';
			$this->categories_priority = 0.5;
			
			$this->include_products = 0;
			$this->products_path = '/store/product';
			$this->products_changefreq = 'monthly';
			$this->products_priority = 0.3;
			
			$this->include_blogposts = 0;			
			$this->blogposts_path = '/blog/post';			
			$this->blogposts_changefreq = 'monthly';			
			$this->blogposts_priority = 0.2;
			
			$wiki_installed = Core_ModuleManager::findById('wiki');
			if($wiki_installed) {
				$this->include_wiki = 1;			
				$this->wiki_path = '/docs';			
				$this->wiki_changefreq = 'monthly';			
				$this->wiki_priority = 0.2;
			}
		}
			
		public function get_products_changefreq_options($key_index = -1)		{
				return $this->changefreq_options;
			}
		
			public function get_categories_changefreq_options($key_index = -1)		{
			return $this->changefreq_options;
		}
		
		public function get_blogposts_changefreq_options($key_index = -1)		{
			return $this->changefreq_options;
		}
		
		public function get_wiki_changefreq_options($key_index = -1)		{
			return $this->changefreq_options;
		}
		
		public function priority_validation($name, $value) {
			if ($value > 1 || $value < 0)
				$this->validation->setError('Priority should be between 0 and 1', $name, true);
				
			return true;
		}
	}