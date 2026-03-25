<?php
global $gBitSystem, $gBitSmarty;

$pRegisterHash = [
	'package_name' => 'rss',
	'package_path' => dirname( dirname( __FILE__ ) ) . '/',
];

// fix to quieten down VS Code which can't see the dynamic creation of these ...
define( 'RSS_PKG_NAME', $pRegisterHash['package_name'] );
define( 'RSS_PKG_URL', BIT_ROOT_URL . basename( $pRegisterHash['package_path'] ) . '/' );
define( 'RSS_PKG_PATH', BIT_ROOT_PATH . basename( $pRegisterHash['package_path'] ) . '/' );
define( 'RSS_PKG_INCLUDE_PATH', BIT_ROOT_PATH . basename( $pRegisterHash['package_path'] ) . '/includes/' );
define( 'RSS_PKG_CLASSES_PATH', BIT_ROOT_PATH . basename( $pRegisterHash['package_path'] ) . '/includes/classes/' );
define( 'RSS_PKG_ADMIN_PATH', BIT_ROOT_PATH . basename( $pRegisterHash['package_path'] ) . '/admin/' );

$gBitSystem->registerPackage( $pRegisterHash );

if( $gBitSystem->isPackageActive( 'rss' ) ) {
	$menuHash = [
		'package_name'  => RSS_PKG_NAME,
		'index_url'     => RSS_PKG_URL . 'index.php',
		'menu_template' => 'bitpackage:rss/menu_rss.tpl',
	];
	$gBitSystem->registerAppMenu( $menuHash );
}
