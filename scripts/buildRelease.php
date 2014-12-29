<?php

include('scripts/tools/common.php');
use rk\scripts\tools;

include('lib/rk/autoloader.class.php');
\rk\autoloader::init();

tools\common::checkLauncher();

//exec('rm -rf cache/*');


$params = \rk\helper\cli::parseArgs($argc, $argv);
if(!empty($params['dest'])) {
	$destinationFolder = $params['dest'];
} else {
	$destinationFolder = '/tmp';
	
	echo tools\common::$PRINT_GREEN . "\n" .'Possible param : --dest=<destination/folder>' . "\n" . tools\common::$PRINT_STD;
	
}

$TMPDIR = '/tmp/rkCopy';

echo tools\common::$PRINT_CYAN . "\n" .'Emptying temp dir ' . $TMPDIR . "\n" . tools\common::$PRINT_STD;
exec('rm -rf ' . $TMPDIR);

echo tools\common::$PRINT_CYAN . "\n" .'Copying files into ' . $TMPDIR . "\n" . tools\common::$PRINT_STD;
exec('cp -R . ' . $TMPDIR);

echo tools\common::$PRINT_CYAN . "\n" .'Removing useless files ' . "\n" . tools\common::$PRINT_STD;
exec('rm -rf ' . $TMPDIR  . '/.buildpath');
exec('rm -rf ' . $TMPDIR  . '/.externalToolBuilders');
exec('rm -rf ' . $TMPDIR  . '/.git');
exec('rm -rf ' . $TMPDIR  . '/.gitignore');
exec('rm -rf ' . $TMPDIR  . '/.project');
exec('rm -rf ' . $TMPDIR  . '/.settings');
exec('rm -rf ' . $TMPDIR  . '/TODO');
exec('rm -rf ' . $TMPDIR  . '/web/phpinfo.php');

echo tools\common::$PRINT_CYAN . "\n" .'Removing user files ' . "\n" . tools\common::$PRINT_STD;
exec('rm -rf ' . $TMPDIR  . '/app/*');
exec('rm -rf ' . $TMPDIR  . '/cache/*');
exec('rm -rf ' . $TMPDIR  . '/lib/user/*');
exec('rm -rf ' . $TMPDIR  . '/ressources/diagrams');
exec('rm -rf ' . $TMPDIR  . '/ressources/i18n/user/*');
exec('rm -rf ' . $TMPDIR  . '/ressources/sql/*');
exec('rm -rf ' . $TMPDIR  . '/ressources/templates/user/*');
exec('rm -rf ' . $TMPDIR  . '/web/css/app/*');
exec('rm -rf ' . $TMPDIR  . '/web/js/user/*');
exec('rm -rf ' . $TMPDIR  . '/web/uploads/*');

echo tools\common::$PRINT_CYAN . "\n" .'Creating default application ' . "\n" . tools\common::$PRINT_STD;
exec('mkdir ' . $TMPDIR  . '/app/front');
\rk\helper\fileSystem::file_put_contents($TMPDIR  . '/app/front/application.class.php', file_get_contents('scripts/tools/buildRelease/templates/front.application.tpl'));
exec('mkdir ' . $TMPDIR  . '/app/front/modules');
exec('mkdir ' . $TMPDIR  . '/app/front/modules/home');
\rk\helper\fileSystem::file_put_contents($TMPDIR  . '/app/front/modules/home/home.class.php', file_get_contents('scripts/tools/buildRelease/templates/front.home.module.tpl'));
exec('mkdir ' . $TMPDIR  . '/app/front/modules/home/templates');
\rk\helper\fileSystem::file_put_contents($TMPDIR  . '/app/front/modules/home/templates/index.php', file_get_contents('scripts/tools/buildRelease/templates/front.home.index.tpl'));

echo tools\common::$PRINT_CYAN . "\n" .'Setting default config ' . "\n" . tools\common::$PRINT_STD;
\rk\helper\fileSystem::file_put_contents($TMPDIR  . '/app/config.ini', file_get_contents('scripts/tools/buildRelease/templates/config.ini.tpl'));

echo tools\common::$PRINT_CYAN . "\n" .'Setting default user ' . "\n" . tools\common::$PRINT_STD;
\rk\helper\fileSystem::file_put_contents($TMPDIR  . '/lib/user/user.class.php', file_get_contents('scripts/tools/buildRelease/templates/user.class.php.tpl'));

echo tools\common::$PRINT_CYAN . "\n" .'Creating .zip' . "\n" . tools\common::$PRINT_STD;
exec('cd ' . $TMPDIR . ' && zip -r ' . $destinationFolder . '/release.zip .');

echo tools\common::$PRINT_YELLOW . "\n" .'Release archive created : ' . $destinationFolder . '/release.zip' .  "\n" . tools\common::$PRINT_STD;

