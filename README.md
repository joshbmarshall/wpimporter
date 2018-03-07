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
		$siteurl = 'https://www.sitename.com'; // optional, if left blank it will auto-detect from the xml file (recommended)
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

		// Get the list of images in posts
		$post_img_urls = $wpimporter->postImageList();

		// Get the list of images in pages
		$page_img_urls = $wpimporter->pageImageList();


## Image list

The list of images for posts and pages are provided using the `postImageList()` and `pageImageList()` functions respectively.
This gives a list of urls relative to the root of the siteurl.

The site url is stored in `$wpimporter->siteurl` so if autodetected this should be used.

All post content has the siteurl stripped so the links are all relative to root rather than including the site url.
These images in the list are the relative links as pulled from the posts, so you will have to re-assemble to get the full url to download.

An example would be:

	<?php
		$basepath = '/path/to/wwwroot';

		foreach ($post_img_urls as $img_url) {
			// Ensure folder exists
			$folder = $basepath . dirname($img_url);
			if (!file_exists($folder)) {
				mkdir($folder, 0750, true);
			}

			$filename = $folder . '/' . basename($img_url);
			if (file_exists($filename) && filesize($filename) > 0) {
				// Do not overwrite
				continue;
			}

			// Download the file
			$src = $wpimporter->siteurl . $img_url;

			// e.g. using GuzzleHttp
			$client = new \GuzzleHttp\Client();
			$client->request('GET', $src, array(
				'sink' => $filename,
			));
		}

For larger imports you may prefer to export this as a list and use ajax to give progress to the user.

## Featured Images on Posts

To get a list of featured images, call the `featuredPostImageList()` function.

This returns an array containing the post id and the full url to the image.
You should record the post id when importing the posts so that you can download the image into the system and relate it to the imported post.
