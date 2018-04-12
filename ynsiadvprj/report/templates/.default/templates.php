<?
//if (!CModule::IncludeModule("tasks")) return;
//
//$res = CTasks::GetList( array( "TITLE" => "ASC" ), array (), array (), array () );
//while ($data = $res->Fetch()) {
//    echo '<pre>';
//    print_r($data);
//}
//
//die();
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
CBitrixComponent::includeComponentClass('ynsiadvprj:report');
?>

<?
CJSCore::Init('jquery');
global $APPLICATION;
$APPLICATION->setTitle("Report");
?>
<div class="container">
    <?
    $APPLICATION->IncludeComponent(
        'ynsistock:interface.header',
        "",
        array(
            'FILTER_ID' => $arResult["GRID_ID"],
            'GRID_ID'   => $arResult["GRID_ID"],
            'FILTER'    => $arResult['FILTER'],
            'SHOW_CREATE_TASK_BUTTON' => 'N' ,
            'YNSISTOCK_CREATE_TEXT' => 'N',
            'BTN_ADD_ACTION' => 'edit_product(0)',
            'POPUP_MENU_ITEMS' => $permImExport,
        ),
        $component,
        array('HIDE_ICONS' => 'Y')
      );
    ?>
    <? 
    // $APPLICATION->IncludeComponent(
    //     "bitrix:main.ui.filter",
    //     "",
    //     array(
    //         "FILTER_ID"             => $arResult["GRID_ID"],
    //         "GRID_ID"               => $arResult["GRID_ID"],
    //         "FILTER"                => $arResult['FILTER'],
    //         "FILTER_PRESETS"        => '',
    //         "ENABLE_LABEL"          => true,
    //         'ENABLE_LIVE_SEARCH'    => 'Y',
    //         'RESET_TO_DEFAULT_MODE' => true
    //     ),
    //     $component,
    //     array("HIDE_ICONS" => true)
    // );
    ?>
    <div>
        <div class="task-message-label error"></div>
        <form id="formChange" action="" method="POST">
            <select name="type" id="type">
                <option <?= ($_REQUEST['type']) == 'w' ? 'selected' : '' ?> value="w">Week</option>
                <option <?= ($_REQUEST['type']) == 'm' ? 'selected' : '' ?> value="m">Month</option>
                <option <?= ($_REQUEST['type']) == 'r' ? 'selected' : '' ?> value="r">Range</option>
            </select>
            <div class="range" style="<?= ($_REQUEST['type'] == 'r') ? 'display: block' : '' ?>">
                <input type="date" name="date_from" id="date_from" value="<?= ($_REQUEST['date_from'] != '') ? $_REQUEST['date_from'] :date('Y-m-d', strtotime(date('Y/m/d')));?>">
                <input type="date" name="date_to" id="date_to" value="<?= ($_REQUEST['date_to'] != '') ? $_REQUEST['date_to'] : date('Y-m-d', strtotime(date('Y/m/d')))?>">
            </div>
        </form>
        <input type="button" id="submit" value="Search">
    </div>
    <?
      
    $component = $this->getComponent();
    $APPLICATION->IncludeComponent(
        'bitrix:main.ui.grid',
        '',
        array(
            'GRID_ID' => $arResult['GRID_ID'],
            'HEADERS' => isset($arResult['HEADER']) ? $arResult['HEADER'] : array(),
            'SORT' => isset($arResult['SORT']) ? $arResult['SORT'] : array(),
            'SORT_VARS' => isset($arResult['SORT_VARS']) ? $arResult['SORT_VARS'] : array(),
            'ROWS' => $arResult['ROWS'],
            "SHOW_ROW_CHECKBOXES" => false,
            "SHOW_NAVIGATION_PANEL"     => false,
            "SHOW_GRID_SETTINGS_MENU"   => false,
            "ENABLE_COLLAPSIBLE_ROWS" => false,
        ),
        $component,
        array('HIDE_ICONS' => 'Y')
    );
    ?>  
    <style>
        #ynsiadvprj_report th, td{
            border: 0 !important;
            border-bottom: 0 !important;
        }
        #ynsiadvprj_report{
            border: 0 !important;
        }
    </style>
    <script>
    $(document).ready(function() {
        
        // #E2E2E2
        // $("#ynsiadvprj_report th").css({'background' : '#E2E2E2'});
        var today = new Date();
        var dd = today.getDate();
        var w = today.getDay();
        var mm = today.getMonth()+1;
        var yyyy = today.getFullYear();

        if(dd<10) {
            dd = '0'+dd
        } 

        if(mm<10) {
            mm = '0'+mm
        } 
        var weekend = $('#ynsiadvprj_report_table th[data-name]').size();
        for (var i = 0; i <= weekend; i++) {
            var day = $('#ynsiadvprj_report_table th:nth-child('+i+')').data('name');
            date = new Date(day);
            if (date.getDay() % 6 == 0) {
                var index = $('th').index($('#ynsiadvprj_report th[data-name="'+day+'"]'));
                $("#ynsiadvprj_report th:nth-child("+(index+1)+"), td:nth-child("+(index+1)+")").css({'background':'#ffeedd'});
            }
            
        }

        today = mm + '/' + dd + '/' + yyyy;
        var index = $('th').index($('#ynsiadvprj_report th[data-name="'+today+'"]'));
        
        $("#ynsiadvprj_report th:nth-child("+(index+1)+"), td:nth-child("+(index+1)+")").css({'background':'#ccffcc'});
        

        $('#formChange').change(function() {
            var type = $('#type').val();
            if (type != 'r') {
                $(this).submit();
            }
            else {
                $('.range').css({'display': 'block'});
                $('#submit').css({'display': 'block'});
            }
        });
        $('#submit').click(function() {
            var date_from = $('#date_from').val();
            var date_to = $('#date_to').val();
            var strDateFrom = new Date(date_from);
            var strDateTo = new Date(date_to);
            if (strDateFrom > strDateTo) {
                $('.error').css({'display': 'block'});
                $('.error').html("Loi Dinh Dang");
            }
            else if (strDateTo < strDateFrom) {
                $('.error').css({'display': 'block'});
                $('.error').html("Loi Dinh Dang");
            }
            else {
                $('#formChange').submit();
            }
            
        })
    })

    
    </script>