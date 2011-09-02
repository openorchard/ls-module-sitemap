<?

	class SiteMap_Module extends Core_ModuleBase {
	
		private $url_count = 0; //count number of urls added on sitemap
		const max_urls = 50000; //max urls on site map (sitemap protocol limit is 50,000)
		const max_generated = 10000; //max of each of categories, products and blog posts
	
		protected function createModuleInfo()		{
			return new Core_ModuleInfo(
				"Site Map",
				"Adds a sitemap to your store",
				"Limewheel Creative Inc." );				
		}
		
		public function subscribeEvents() {
			Backend::$events->addEvent('cms:onExtendPageModel', $this, 'extend_page_model');
			Backend::$events->addEvent('cms:onExtendPageForm', $this, 'extend_page_form');
		}
		
		public function extend_page_model($page, $context) {
			$page->define_column('sitemap_visible', 'Appears on site map')->defaultInvisible()->listTitle('Sitemap Visible');
		}
		
		public function extend_page_form($page, $context) {
			if ($context != 'content')
				$page->add_form_field('sitemap_visible', 'Appears on site map')->tab('Visibility')->renderAs(frm_checkbox);
		}
		
		public function listSettingsItems()	{
			$result = array(
				array(
					'icon'=>'/modules/sitemap/resources/images/sitemap.png', 
					'title'=>'Sitemap Configuration', 
					'url'=>'/sitemap/config/', 
					'description'=>'Setup the sitemap',
					'sort_id'=>300
				)
			);
			
			return $result;
		}
		
		public function register_access_points()	{
				return array(
					'sitemap.xml' =>'generate_sitemap'
				);
		}
		
		public function generate_sitemap() {		
			$params = Sitemap_Params::create();			
			
			header("Content-Type: application/xml");
			$xml = new DOMDocument();
			$xml->encoding = 'UTF-8';
			
			$urlset = $xml->createElement('urlset'); 		
			$urlset->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
			$urlset->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
			$urlset->setAttribute('xsi:schemaLocation', 'http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd');
			
			$page_list = Cms_Page::create()->where('is_published=1')->where('sitemap_visible=1')->where('navigation_visible=1')->where('security_mode_id <> "customers"')->find_all(); 		
			if(count($page_list)) {
				foreach($page_list as $page) {
					if($url = $this->prepare_url_element($xml, site_url($page->url),  date('c', strtotime($page->updated_at)), 'weekly', '0.7'))
						$urlset->appendChild($url);
				}
			}

			if($params->include_categories) {
				$category_list = Shop_Category::create()->limit(self::max_generated)->order('shop_categories.updated_at desc')->find_all();
				foreach($category_list as $category) {
					if($url = $this->prepare_url_element($xml, site_url($params->categories_path.'/'.$category->url_name),  date('c', strtotime($category->updated_at)), $params->categories_changefreq, $params->categories_priority))
						$urlset->appendChild($url);
				}
			}

			if($params->include_products) {				 
				$root_url = Phpr::$request->getRootUrl();
				$lssalestracking_installed = Core_ModuleManager::findById('lssalestracking');
				//$lssalestracking_installed = Db_DbHelper::scalar('select count(*) from core_install_history where moduleId = ?', 'lssalestracking');
				
				if($lssalestracking_installed && class_exists('LsSalesTracking_ProductManager')) {
					$product_list = new Shop_Product(null, array('no_column_init' => true, 'no_validation' => true)); 
	 				$product_list = $product_list->apply_filters()->where('enabled=1')->limit(self::max_generated)->order('shop_products.updated_at desc')->find_all();
	 			} 
	 			else {
	 				$product_list = Db_DbHelper::objectArray('select url_name, updated_at from shop_products where enabled is true and (grouped is null or grouped = 0) order by updated_at limit :?', self::max_generated);
	 			}
	 			foreach($product_list as $product) {
	 				if($lssalestracking_installed && class_exists('LsSalesTracking_ProductManager')) {
	 					$product_url = site_url($params->products_path.LsSalesTracking_ProductManager::get_marketplace_product_url($product));
	 				}
	 				else {
	 					 $product_url = $root_url.$product->page_url($params->products_path);
	 					 //$product_url = site_url($params->products_path.'/'.$product->url_name);
	 					}		
	 				if($url = $this->prepare_url_element($xml, $product_url,  date('c', strtotime($product->updated_at)), $params->products_changefreq, $params->products_priority))
	 					$urlset->appendChild($url);
	 			}
 			}
 			if($params->include_blogposts) {
	 			$blog_post_list = Blog_Post::create()->limit(self::max_generated)->order('blog_posts.updated_at desc')->find_all();
	 			foreach($blog_post_list as $blog_post) {
	 				if($url = $this->prepare_url_element($xml, site_url($params->blogposts_path.'/'.$blog_post->url_title),  date('c', strtotime($blog_post->published_date)), $params->blogposts_changefreq, $params->blogposts_priority))
	 					$urlset->appendChild($url);
	 			}
 			}
 			
 			$wiki_installed = Core_ModuleManager::findById('wiki');
 			//$wiki_installed = Db_DbHelper::scalar('select count(*) from core_install_history where moduleId = ?', 'wiki');
 			if($wiki_installed && $params->include_wiki && class_exists('Wiki_Page')) {
 				$wiki_page_list = Wiki_Page::create()->limit(self::max_generated)->where('is_published=1')->order('wiki_pages.updated_at desc')->find_all(); 			
 				foreach($wiki_page_list as $wiki_page) {
 					if($url = $this->prepare_url_element($xml, site_url($params->wiki_path.'/'.$wiki_page->url_title), date('c', strtotime($wiki_page->updated_at)), $params->wiki_changefreq, $params->wiki_priority))
 						$urlset->appendChild($url);
 				}
 			} 			
 			
 			
			$xml->appendChild($urlset);
 			
 			echo $xml->saveXML();
		}
		
		private function prepare_url_element($xml, $page_url, $page_lastmod, $page_changefreq, $page_priority) {
			if($this->url_count < self::max_urls) {
			
				$url = $xml->createElement('url');
				
				$url->appendChild($xml->createElement('loc', $page_url));
				$url->appendChild($xml->createElement('lastmod', $page_lastmod));
				$url->appendChild($xml->createElement('changefreq', $page_changefreq));
				$url->appendChild($xml->createElement('priority', $page_priority));
										
				$this->url_count++;
										
				return $url;
			} 
			else return false;
		}
			
	}