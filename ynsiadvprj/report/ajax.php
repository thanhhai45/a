<?php
define("NOT_CHECK_PERMISSIONS", true);
define("STOP_STATISTICS", true);
define("NO_KEEP_STATISTIC", "Y");
define("NO_AGENT_STATISTIC","Y");
define("DisableEventsCheck", true);
$siteId = '';
if (isset($_REQUEST['site_id']) && is_string($_REQUEST['site_id']))
    $siteId = substr(preg_replace('/[^a-z0-9_]/i', '', $_REQUEST['site_id']), 0, 2);

if (!$siteId)
    define('SITE_ID', $siteId);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

/**
 * @global CUser $USER
 */

if ($_SERVER['REQUEST_METHOD'] != 'POST')
{
    die();
}
print_r($_REQUEST['ajax_action']);
CUtil::JSPostUnescape();
$action = !empty($_REQUEST['ajax_action']) ? $_REQUEST['ajax_action'] : null;

if (empty($action))
    die('Unknown action!');

$APPLICATION->ShowAjaxHead();
$action = strtoupper($action);

$sendResponse = function($data,$errors , $plain = false)
{
    if ($data instanceof Bitrix\Main\Result)
    {
        $errors = $data->getErrorMessages();
        $data = $data->getData();
    }

    $result = array('DATA' => $data, 'ERRORS' => $errors);
    if (is_array($errors)) {
        $result['SUCCESS'] = count($errors) === 0;
    } else {
        $result['SUCCESS'] = strlen($errors) === 0;
    }

    if(!defined('PUBLIC_AJAX_MODE'))
    {
        define('PUBLIC_AJAX_MODE', true);
    }
    $GLOBALS['APPLICATION']->RestartBuffer();
    Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

    if ($plain)
    {
        $result = $result['DATA'];
    }

    echo \Bitrix\Main\Web\Json::encode($result);
    CMain::FinalActions();
    die();
};
$sendError = function($error) use ($sendResponse)
{
    $sendResponse(array(), array($error));
};

switch ($action)
{
    // Sample Code
    case 'LIST':
        $arData = $_REQUEST;
        CBitrixComponent::includeComponentClass('ynsiadvprj:report');
        $ynsiadvprj = new YnsiAdvanceReport;
        $result = $ynsiadvprj->executeRangeAction($arData, SITE_ID);
        // if (intval($result) > 0) {
        //     $sendResponse($result);
        // }
        // else {
        //     $error = array("ERROR" => -1);
        //     $sendResponse($result, $error);
        // }
        break;
    default:
        die('Unknown action!');
        break;
}