<?php

namespace Cognito\WPImporter;

/**
 * Read a Wordpress export file and present posts in a way that can be easily loaded into a custom CMS or system
 *
 * @package WPImporter
 * @author Josh Marshall <josh@jmarshall.com.au>
 */
class WPImporter {

	public $siteurl = NULL;
	private $filename = NULL;
	private $reader = NULL;

	public function __construct($filename, $siteurl = NULL) {
		$this->filename = $filename;
		$this->reader = new \XMLReader();
		if (!$this->reader->open($this->filename)) {
			throw new Exception('Failed to open ' . $this->filename);
		}

		if ($siteurl) {
			$this->siteurl = trim($siteurl, '/');
		} else {
			// Find the blog url
			while ($this->reader->read()) {
				if ($this->reader->prefix != 'wp' || $this->reader->localName != 'base_site_url') {
					continue;
				}
				$xml = simplexml_load_string($this->reader->readOuterXml());
				$arrayData = $this->xmlToArray($xml);
				if (is_array($arrayData) && array_key_exists('base_site_url', $arrayData)) {
					$this->siteurl = $arrayData['base_site_url'];
					break;
				}
			}
		}
	}

	public function __destruct() {
		$this->reader->close();
	}

	/**
	 * The next post in the file
	 * Call this function repeatedly to get each post
	 * @return array
	 */
	public function getPost() {
		$post = $this->getPostType('post');
		if ($this->siteurl) {
			// Strip out the site url from the content
			if ($post['content:encoded']) {
				$post['content:encoded'] = str_replace($this->siteurl, '', $post['content:encoded']);
			}
		}
		return $post;
	}

	/**
	 * The next page in the file
	 * Call this function repeatedly to get each page
	 * @return array
	 */
	public function getPage() {
		$page = $this->getPostType('page');
		if ($this->siteurl) {
			// Strip out the site url from the content
			if ($page['content:encoded']) {
				$page['content:encoded'] = str_replace($this->siteurl, '', $page['content:encoded']);
			}
		}
		return $page;
	}

	/**
	 * Get list of images referenced by posts from the current site
	 *
	 * @return string[]
	 */
	public function postImageList() {
		$imagelist = array();

		while ($post = $this->getPost()) {
			if (!$post['content:encoded']) {
				continue;
			}

			preg_match_all('/src=\"(.*?)\"/', $post['content:encoded'], $srcs);
			if (is_array($srcs) && array_key_exists(1, $srcs)) {
				foreach ($srcs[1] as $imgurl) {
					if (substr($imgurl, 0, 1) == '/') {
						$imagelist[$imgurl] = 1;
					}
				}
			}
		}

		return array_keys($imagelist);
	}

	/**
	 * Get list of images referenced by pages from the current site
	 *
	 * @return string[]
	 */
	public function pageImageList() {
		$imagelist = array();

		while ($page = $this->getPage()) {
			if (!$page['content:encoded']) {
				continue;
			}

			preg_match_all('/src=\"(.*?)\"/', $page['content:encoded'], $srcs);
			if (is_array($srcs) && array_key_exists(1, $srcs)) {
				foreach ($srcs[1] as $imgurl) {
					if (substr($imgurl, 0, 1) == '/') {
						$imagelist[$imgurl] = 1;
					}
				}
			}
		}

		return array_keys($imagelist);
	}

	/**
	 * The next item of this type in the file
	 * Call this function repeatedly to get each page
	 * @var string $post_type
	 * @return array
	 */
	public function getPostType($post_type) {
		static $last_post_type = NULL;

		if (is_null($last_post_type)) {
			$last_post_type = $post_type;
		}

		if ($post_type != $last_post_type) {
			$this->reader->open($this->filename);
		}

		while ($this->reader->read()) {

			if ($this->reader->name != 'item') {
				continue;
			}
			$xml = simplexml_load_string($this->reader->readOuterXml());
			$arrayData = $this->xmlToArray($xml);

			$this->reader->next();

			if (!$arrayData) {
				continue;
			}

			if (!is_array($arrayData)) {
				continue;
			}

			if (!array_key_exists('item', $arrayData)) {
				continue;
			}

			if (!array_key_exists('wp:post_type', $arrayData['item'])) {
				continue;
			}

			if ($arrayData['item']['wp:post_type'] != $post_type) {
				continue;
			}

			return($arrayData['item']);
		}
		return false;
	}

	/**
	 * Convert an xml element into an array
	 *
	 * @url https://outlandish.com/blog/tutorial/xml-to-json/
	 * @param \SimpleXMLElement $xml
	 * @param array $options
	 * @return array
	 */
	public function xmlToArray($xml, $options = array()) {
		$defaults = array(
			'namespaceSeparator' => ':', //you may want this to be something other than a colon
			'attributePrefix' => '@', //to distinguish between attributes and nodes with the same name
			'alwaysArray' => array(), //array of xml tag names which should always become arrays
			'autoArray' => true, //only create arrays for tags which appear more than once
			'textContent' => '$', //key used for the text content of elements
			'autoText' => true, //skip textContent key if node has no attributes or child nodes
			'keySearch' => false, //optional search and replace on tag and attribute names
			'keyReplace' => false //replace values for above search values (as passed to str_replace())
		);
		$options = array_merge($defaults, $options);
		$namespaces = $xml->getDocNamespaces();
		$namespaces[''] = null; //add base (empty) namespace
		//get attributes from all namespaces
		$attributesArray = array();
		foreach ($namespaces as $prefix => $namespace) {
			foreach ($xml->attributes($namespace) as $attributeName => $attribute) {
				//replace characters in attribute name
				if ($options['keySearch']) {
					$attributeName = str_replace($options['keySearch'], $options['keyReplace'], $attributeName);
				}
				$attributeKey = $options['attributePrefix']
						. ($prefix ? $prefix . $options['namespaceSeparator'] : '')
						. $attributeName;
				$attributesArray[$attributeKey] = (string) $attribute;
			}
		}

		//get child nodes from all namespaces
		$tagsArray = array();
		foreach ($namespaces as $prefix => $namespace) {
			foreach ($xml->children($namespace) as $childXml) {
				//recurse into child nodes
				$childArray = $this->xmlToArray($childXml, $options);
				list($childTagName, $childProperties) = each($childArray);

				//replace characters in tag name
				if ($options['keySearch']) {
					$childTagName = str_replace($options['keySearch'], $options['keyReplace'], $childTagName);
				}
				//add namespace prefix, if any
				if ($prefix) {
					$childTagName = $prefix . $options['namespaceSeparator'] . $childTagName;
				}

				if (!isset($tagsArray[$childTagName])) {
					//only entry with this key
					//test if tags of this type should always be arrays, no matter the element count
					$tagsArray[$childTagName] = in_array($childTagName, $options['alwaysArray']) || !$options['autoArray'] ? array(
						$childProperties) : $childProperties;
				} elseif (
						is_array($tagsArray[$childTagName]) && array_keys($tagsArray[$childTagName]) === range(0, count($tagsArray[$childTagName]) - 1)
				) {
					//key already exists and is integer indexed array
					$tagsArray[$childTagName][] = $childProperties;
				} else {
					//key exists so convert to integer indexed array with previous value in position 0
					$tagsArray[$childTagName] = array(
						$tagsArray[$childTagName],
						$childProperties);
				}
			}
		}

		//get text content of node
		$textContentArray = array();
		$plainText = trim((string) $xml);
		if ($plainText !== '') {
			$textContentArray[$options['textContent']] = $plainText;
		}

		//stick it all together
		$propertiesArray = !$options['autoText'] || $attributesArray || $tagsArray || ($plainText === '') ? array_merge($attributesArray, $tagsArray, $textContentArray) : $plainText;

		//return node as array
		return array(
			$xml->getName() => $propertiesArray
		);
	}

}
