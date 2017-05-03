<?php
/**
 * Plugin Name: Migration Merge Tool
 * Plugin URI: https://github.com/WordPress-Phoenix/merge-migration-tool
 * Description: Migration of content from
 * Author: FanSided
 * Version: 2.0.0
 * Author URI: http://fansided.com
 * License: GPL V2
 * Text Domain: mmt
 *
 * GitHub Plugin URI: https://github.com/WordPress-Phoenix/merge-migration-tool
 * GitHub Branch: master
 *
 * @package  MMT
 * @category Plugin
 * @author   justintucker, scarstens, corycrowley, kyletheisen
 */

//avoid direct calls to this file, because now WP core and framework has been used
if ( ! function_exists( 'add_filter' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

// Create plugin instance on plugins_loaded action to maximize flexibility of wp hooks and filters system.
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app/class-merge-migration-tool.php';
add_action( 'plugins_loaded', array( 'MergeMigrationTool\\Init', 'run' ) );