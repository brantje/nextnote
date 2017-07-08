<?php

OCP\User::checkAdminUser();

$tmpl = new OCP\Template('nextnote', 'admin');
$tmpl->assign('folder', OCP\Config::getAppValue('nextnote', 'folder', ''));
$tmpl->assign('sharemode', OCP\Config::getAppValue('nextnote', 'sharemode', ''));

return $tmpl -> fetchPage();

