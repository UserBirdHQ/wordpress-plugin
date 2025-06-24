<?php
/**
 * Convert readme.txt to README.md
 * 
 * This script converts a WordPress plugin readme.txt file to GitHub-friendly README.md
 * Run manually with: php bin/update-readme.php
 */

// Make sure we're in the plugin root directory
$plugin_dir = dirname(__DIR__);
chdir($plugin_dir);

// File paths
$readme_txt = 'readme.txt';
$readme_md = 'README.md';

// Check if readme.txt exists
if (!file_exists($readme_txt)) {
    echo "Error: readme.txt not found\n";
    exit(1);
}

// Read the readme.txt file
$content = file_get_contents($readme_txt);

// Add a banner message at the top
$banner = "# UserBird

A WordPress plugin for website analytics tracking and reporting.

";

// Convert WordPress headings to Markdown headings
$content = preg_replace('/=== (.*) ===/i', '# $1', $content);
$content = preg_replace('/== (.*) ==/i', '## $1', $content);
$content = preg_replace('/= (.*) =/i', '### $1', $content);

// Convert WordPress bullet points to Markdown bullet points
$content = preg_replace('/^\* /m', '- ', $content);

// Add the banner at the top
$content = $banner . $content;

// Write the content to README.md
file_put_contents($readme_md, $content);

echo "Successfully converted readme.txt to README.md\n";