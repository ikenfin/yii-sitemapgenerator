<?php

	/*
		Console command for creating sitemaps

		@author ikenfin (ikenfin@gmail.com)
		2015
	*/
	class SitemapGeneratorCommand extends CConsoleCommand {

		// https://support.google.com/webmasters/answer/183668?hl=ru
		// items per file limit
		const MAX_ITEMS_PER_PAGE = 50000;

		const ONE_FILE_MODE = 0;
		const MULTIPLE_FILES_MODE = 1;

		protected $db, $mode;

		// generator => database field assignation
		// change with params:
		// field[url]=i.kenfin.ru field[lastmod]=date_update
		public 	$fields = array(
			   		'url'		 => 'alias',	// key url means <loc> data, alias -> value from database
			   		'lastmod'	 => NULL 		// NULL means that this key wontbe processed
			   	);

		// command parameters
		// change with params:
		// table=urls baseUrl=i.kenfin.ru saveAlias=webroot
		public  $params = array(
			'table' 	=> 'url_alias',			// from which data will be fetched
			'baseUrl'	=> '',					// base for each url (sitemap files, urls)
			'saveAlias'	=> 'webroot.xml-sitemap'// Yii alias to save xml files (directory creates automatically)
		);

		public function run($args) {
			// parse arguments
			foreach($args as $arg) {
				list($name, $value) = split('=', $arg);
				
				// params
				if(array_key_exists($name, $this->params))
					$this->params[$name] = $value;
				else {
				// fields
					$match = '';
					if(preg_match('/field\[(\w+)\]$/', $name, $match)) {
						if(count($match) > 1 && array_key_exists($match[1], $this->fields)) {
							$this->fields[$match[1]] = $value;
						}
					}
				}
			}

			$this->db = Yii::app()->db;

			if($this->db == NULL) {
				return 1;
			}

			$this->checkDir();

			$this->mode = self::ONE_FILE_MODE;

			$count = $this->count();
			$pages = $this->pages($count);

			// multifile sitemap
			if($pages > 1) {
				$this->mode = self::MULTIPLE_FILES_MODE;
			}

			$this->generate($count, $pages);

			return 0;
		}

		// make new dir or clear existing
		protected function checkDir() {
			$path = Yii::getPathOfAlias($this->params['saveAlias']);

			if(!is_dir($path)) {
				@mkdir($path, 0775, true);
			}
			else {
				$dir = opendir($path);
				while($item = readdir($dir)) {
					if($item != '.' && $item != '..') {
						$info = pathinfo($item);
						// remove only xml files with names starting `sitemap`!
						if(preg_match('/^sitemap/', $item) && $info['extension'] == 'xml') {
							@unlink($path . DIRECTORY_SEPARATOR . $item);
						}
					}
				}
			}
		}

		// count elements in table
		protected function count() {

			$cmd = $this->db->createCommand()
				->select('COUNT(*) AS count')
				->from($this->params['table']);

			return $cmd->queryScalar();

		}

		// calculate pages count
		protected function pages($count) {
			return ceil($count / self::MAX_ITEMS_PER_PAGE);
		}

		// fetch data from db
		protected function data($page = 0) {

			$select = array_map(function($key, $val) {
				if($val != NULL)
					return $val . ' AS ' . $key;
			}, array_keys($this->fields), $this->fields);

			$cmd = $this->db->createCommand()
				->select(array_filter($select))
				->from($this->params['table'])
				->limit(self::MAX_ITEMS_PER_PAGE)
				->offset($page * self::MAX_ITEMS_PER_PAGE);

			return $cmd->queryAll();

		}

		// generate sitemap files
		protected function generate($count, $pages) {

			if($this->mode === self::MULTIPLE_FILES_MODE) {
				for($i = 0; $i < $pages; $i++) {
					// generate file
					$this->generateSitemapFile($i);
				}

				$this->generateIndexFile($pages);
			}
			else $this->generateSitemapFile();
			
		}

		// generate sitemap index file (if urls > 50000)
		protected function generateIndexFile($sitemapCount) {
			$saveUrl = str_replace(Yii::getPathOfAlias('webroot'), '', Yii::getPathOfAlias($this->params['saveAlias']));
			
			$file = fopen(Yii::getPathOfAlias($this->params['saveAlias']) . DIRECTORY_SEPARATOR . 'sitemap.xml', 'w');

			fwrite($file, '<?xml version="1.0" encoding="UTF-8" ?>');

			fwrite($file, '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');

			for($i = 0; $i < $sitemapCount; $i++) {
				fwrite($file, '<sitemap>');
				fwrite($file, '<loc>' . $this->params['baseUrl'] . $saveUrl . '/sitemap' . $i . '.xml</loc>');
				fwrite($file, '<lastmod>' . date('Y-m-d') . '</lastmod>');
				fwrite($file, '</sitemap>');
			}

			fwrite($file, '</sitemapindex>');
			fclose($file);
		}

		// Write sitemap file
		protected function generateSitemapFile($page = '') {

			$file = fopen(Yii::getPathOfAlias($this->params['saveAlias']) . DIRECTORY_SEPARATOR . 'sitemap' . $page . '.xml', 'w');

			fwrite($file, '<?xml version="1.0" encoding="UTF-8" ?>');

			fwrite($file, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');

			foreach($this->data($page) as $item) {
				fwrite($file, '<url>');
				fwrite($file, '<loc>' . $this->params['baseUrl'] . '/' . $item['url'] . '</loc>');
				
				if($this->fields['lastmod'] != NULL) {
					fwrite($file, '<lastmod>' . $item['lastmod'] . '</lastmod>');
				}

				fwrite($file, '</url>');
			}

			fwrite($file, '</urlset>');
			fclose($file);
		}


	}