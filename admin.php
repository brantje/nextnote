<?php

OCP\User::checkAdminUser();

$tmpl = new OCP\Template('ownnote', 'admin');
$tmpl->assign('folder', OCP\Config::getAppValue('ownnote', 'folder', ''));
$tmpl->assign('sharemode', OCP\Config::getAppValue('ownnote', 'sharemode', ''));

return $tmpl -> fetchPage();

