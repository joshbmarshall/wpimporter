# WPImporter

This package reads a wordpress export file and formats the posts in a generic way that can be imported into a custom system or CMS.

## Installation

Installation is very easy with composer:

	composer require cognito/wpimporter

## Process

In the Wordpress administration area, go to Tools > Export

Export all content and download the export file.

Transfer the file to the server and parse it by calling

	<?php
		$filename = '/the/path/to/the/file.xml';
		$siteurl = 'https://www.sitename.com';
		$wpimporter = new \Cognito\WPImporter($filename, $siteurl);

		// Get the posts
		foreach ($wpimporter->posts() as $post) {
			var_dump($post);
		}

		// Get the pages
		foreach ($wpimporter->pages() as $page) {
			var_dump($page);
		}


