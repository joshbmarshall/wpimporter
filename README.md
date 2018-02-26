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
		$wpimporter = new \Cognito\WPImporter\WPImporter($filename, $siteurl);

		// Get the posts
		while ($post = $wpimporter->getPost()) {
			var_dump($post);
		}

		// Get the pages
		while ($page = $wpimporter->getPage()) {
			var_dump($page);
		}

		// Get a custom post type, such as faq
		while ($faq = $wpimporter->getPostType('faq')) {
			var_dump($faq);
		}

## To-do

Grab image files from the live site
