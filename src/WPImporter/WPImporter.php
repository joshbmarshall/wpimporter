<?php

namespace Cognito\WPImporter;

/**
 * Read a Wordpress export file and present posts in a way that can be easily loaded into a custom CMS or system
 *
 * @package WPImporter
 * @author Josh Marshall <josh@jmarshall.com.au>
 */
class WPImporter {

	private $siteurl = NULL;
	private $filename = NULL;

	public function __construct($filename, $siteurl) {
		$this->siteurl = $siteurl;
		$this->filename = $filename;
	}

	public function posts() {
		$reader = new XMLReader();
		if (!$reader->open("data.xml")) {
			die("Failed to open 'data.xml'");
		}
		while ($reader->read()) {
			$node = $reader->expand();
			// process $node...
			dump($node);
		}
		$reader->close();
	}

}
