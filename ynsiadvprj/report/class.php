<?
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

Loc::loadMessages(__FILE__);

class YnsiAdvanceReport extends \CBitrixComponent
{
    var $arVariables = array();

    protected function getAction()
    {
        $arDefaultUrlTemplates404 = array(
            'list' => 'list'
        );
        $action = '';

        if ($this->arParams["SEF_MODE"] == "Y") {
            $engine = new CComponentEngine($this);
            $arUrlTemplates = CComponentEngine::MakeComponentUrlTemplates($arDefaultUrlTemplates404, $this->arParams["SEF_URL_TEMPLATES"]);
            $action = CComponentEngine::ParseComponentPath($this->arParams["SEF_FOLDER"], $arUrlTemplates, $this->arVariables);
        }
        
        return (strlen($action) > 0) ? strtoupper($action) : 'LIST';
    }

    public function executeComponent()
    {
        if (!Main\Loader::includeModule('ynsiadvprj'))
        {
            ShowError(Loc::getMessage('MODULE_NOT_INSTALLED'));
            return;
        }

        $action = $this->getAction();
        switch ($action)
        {
            case 'LIST':
                $this->executeRangeAction();
                break;
            default:
                $this->executeWeekAction();
                break;
        }
    }

    protected function executeMonthAction()
    {

    }

    public function executeRangeAction($data, $site)
    {
        $this->arResult = $this->prepareListResult();
        $this->includeComponentTemplate('templates');
    }

    protected function prepareListResult()
    {
        global $USER;
        $arRow = array();
        $arFilterRange = array("w" => "Week","m" => "Month"); 
        $arfinish = array('Y' => GetMessage("YNSISTOCK_YES"),'N'=>GetMessage("YNSISTOCK_NO"));
        $arResult['HEADER'] = array(
            array("id"=>"NAME", "name"=> "Task", "sort"=>"name", "default"=>true)
        );
        
        $arFilter = array();
        $arResult["GRID_ID"] = "ynsiadvprj_report";
        $grid_options = new CGridOptions($arResult["GRID_ID"]);
        $aSort = $grid_options->GetSorting(array("sort"=>array("id"=>"desc"), "vars"=>array("by"=>"by", "order"=>"order")));
        $navParams = $grid_options->getNavParams(array('nPageSize'=>10));
        $npageSize = $navParams['nPageSize'];
        $aSortVal = $aSort['sort'];
        $sort_order = current($aSortVal);
        $sort_by = key($aSortVal);
        $startWeek = '';
        $endWeek = '';

        switch ($_REQUEST['type']) {
            case 'm':
                $d = getdate(strtotime("today"));
                $fullDay = $this->checkFullDayMonth($d['mon']);
                $year = $d['year'];
                $startWeek = $d['mon']."/01/".$year;
                $endWeek = $d['mon'].'/'.$fullDay.'/'.$year;
                break;
            case 'r':
                $date_from = explode('-', $_REQUEST['date_from']);    
                $startWeek = $date_from[1].'/'.$date_from[2].'/'.$date_from[0];
                $date_to = explode('-', $_REQUEST['date_to']);
                $endWeek = $date_to[1].'/'.$date_to[2].'/'.$date_to[0];
                break;
            default:
                $previous_week = strtotime("today");
                $startWeek = date('m/d/Y' ,strtotime("last monday",$previous_week));
                $endWeek = date('m/d/Y', strtotime("next sunday", $previous_week));
                break; 
        }
        $arWeek = $this->getRange($startWeek, $endWeek);
        foreach($arWeek as $item) {
            array_push($arResult["HEADER"], $item);
        }
        array_push($arResult["HEADER"], array("id"=>"TOTAL", "name"=> "Total", "sort"=>"total", "default"=>true));
        CModule::IncludeModule("tasks");
        $arFilter = array(">=CREATED_DATE" => $startWeek.' 00:00:00 am', "<=CREATED_DATE" => $endWeek.' 23:59:59 pm');
        $dbProduct = CTaskElapsedTime::GetList( array(), $arFilter, array (), array () );
        $arElapsedTime = array();
        $arTask = array();
        while ($p = $dbProduct->Fetch()) {
            // echo '<pre>';
            // print_r($p);
            array_push($arElapsedTime, $p);
            $dbTask = CTasks::GetList(array("ID" => "DESC"), array("ID" => $p["TASK_ID"]));
            while ($t = $dbTask->Fetch()) {
                array_push($arTask, $t);
            }
        }
        $arData = $this->mapDataReport($arTask, $arElapsedTime);
        foreach ($arData as $item) {
            $arRow[] = array("data" =>  $item);
        }

        $arReport = $this->mapTotalReport($arData);
        $arTotal = array("ID" => 'total', "NAME" => "Total");
        $arRow[] = array("data" => $arReport);

        $arResult["SORT"] = $aSort["sort"];
        $arResult["SORT_VARS"] = $aSort["vars"];
        $arResult["TOTAL_ROWS_COUNT"] = $dbProduct->SelectedRowsCount();
        $arResult["NAV_OBJECT"] = $dbProduct;
        $arResult['ROWS'] = $arRow;
        return $arResult;
    }

    public function convertMinutesToHour($m)
    {
        $h = $m/60;
        return $h;
    }

    public function mapDataReport($arTask, $arElapse)
    {
        $arData = array();
        $total = 0;
        $taskCountLog=0;
        foreach ($arTask as $task) {
            $arData[$task["ID"]] = array( "ID" => $task["ID"], "NAME" => $task["TITLE"]);
            foreach ($arElapse as $item) {
                if ($item["TASK_ID"] == $task["ID"]) {
                    $date = explode(' ',$item['CREATED_DATE']);
                    $dateString = explode("/", $date[0]);
                    $strDate = $dateString[0].'/'.$dateString[1].'/'.$dateString[2];
                    $arData[$task["ID"]][$strDate] += $this->convertMinutesToHour($item["MINUTES"]);
                    $arData[$task["ID"]][$strDate] = $arData[$task["ID"]][$strDate].'h';
                    array_push($arData[$task["ID"]][$strDate] , $arData[$task["ID"]][$strDate]);
                    $arData[$task["ID"]]["TOTAL"] += $this->convertMinutesToHour($item["MINUTES"]);
                    $arData[$task["ID"]]["TOTAL"] = $arData[$task["ID"]]["TOTAL"].'h';
                    array_push($arData[$task["ID"]]["TOTAL"] ,$arData[$task["ID"]]["TOTAL"]);
                }
            }
        }
        return $arData;
    }

    public function mapTotalReport($arData) 
    {
        $arReport = array();
        foreach ($arData as $keydata => $valuedata) {
            foreach ($valuedata as $key => $value) {
                if ($key !== "NAME" && $key !== "TOTAL" && $key !== 'ID') {
                    $arReport[$key] += $value;
                    $arReport[$key] =$arReport[$key].'h';
                    $arReport["TOTAL"] += $value;
                    $arReport["TOTAL"] =$arReport["TOTAL"].'h';
                }    
            }
        }
        $arReport["ID"] = "total";
        $arReport["NAME"] =" Total";

        return $arReport;
    }

    public function checkFullDayMonth($month, $year) 
    {
        $fullDay = 0;
        if ($month < 1 || $month > 12) return;
        switch($month){
            case '1' || '3' || '5' || '7' || '8' || '10' || '12':
                $fullDay = 31;
                break;
            case '4' || '6' || '9' || '11':
                $fullDay = 30;
                break;
            default:
                $fullDay = ($year % 400 == 0) ? 29 : 28;
                break;
        }
        return $fullDay;
    }

    public function getRange($startDate, $endDate) 
    {
        $array = array();
        $arData= array(); 
        $period = new DatePeriod(
            new DateTime($startDate),
            new DateInterval('P1D'),
            new DateTime($endDate)
        );
        foreach ($period as $key => $value) {
            array_push($array,  $value->format('m/d/Y'));     
        }
        array_push($array, $endDate);
        foreach($array as $key => $value) {
            $dateTime = strtotime($value);
            $nameWeek = getdate($dateTime);
            $dateExplode = explode('/', $value);
            $date = $dateExplode[0].'/'.$dateExplode[1].'/'.$dateExplode[2];
            $formatMonthDay = date("F d", $dateTime);
            array_push($arData, array("id" => $date, 
                                        "name" => substr($nameWeek["weekday"],0,3) . ' / '. $formatMonthDay , 
                                        "sort"=>$nameWeek["weekday"], 
                                        "default"=>true, 
                                        "data-edit"=> ($date == date('dmY', strtotime('today'))) ? 'current' : ''
                                    ));
        }

        return $arData;
    }

    public function checkCurrentDay()
    {

    }

}
?>