<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass.' ' : '').'pagetitle-toolbar-field-view tasks-pagetitle-view');

$defaultMenuTarget = SITE_TEMPLATE_ID === "bitrix24" ? "above_pagetitle" : "task_menu";
$isBitrix24Template = SITE_TEMPLATE_ID === "bitrix24";

if ($isBitrix24Template)
{
	$this->SetViewTarget('inside_pagetitle');
}

if (!isset($arParams['MENU_GROUP_ID']))
{
	$arParams['MENU_GROUP_ID'] = $arParams['GROUP_ID'];
}

if (isset($arParams['FILTER']) && is_array($arParams['FILTER']))
{
	$selectors = array();

	foreach ($arParams['FILTER'] as $filterItem)
	{
		if (!(isset($filterItem['type']) &&
			  $filterItem['type'] === 'custom_entity' &&
			  isset($filterItem['selector']) &&
			  is_array($filterItem['selector']))
		)
		{
			continue;
		}

		$selector = $filterItem['selector'];
		$selectorType = isset($selector['TYPE']) ? $selector['TYPE'] : '';
		$selectorData = isset($selector['DATA']) && is_array($selector['DATA']) ? $selector['DATA'] : null;
		$selectorData['MODE'] = $selectorType;
		$selectorData['MULTI'] = $filterItem['params']['multiple'] && $filterItem['params']['multiple'] == 'Y';

		if (!empty($selectorData) && $selectorType == 'user')
		{
			$selectors[] = $selectorData;
		}
		if (!empty($selectorData) && $selectorType == 'group')
		{
			$selectors[] = $selectorData;
		}
	}

	if (!empty($selectors))
	{
		\CUtil::initJSCore(
			array(
				'tasks_integration_socialnetwork'
			)
		);
	}

	if (!empty($selectors))
	{
		?>
		<script type="text/javascript"><?
		foreach ($selectors as $groupSelector)
		{
			$selectorID = $groupSelector['ID'];
			$selectorMode = $groupSelector['MODE'];
			$fieldID = $groupSelector['FIELD_ID'];
			$multi = $groupSelector['MULTI'];
			?>BX.ready(
				function()
				{
					BX.FilterEntitySelector.create(
						"<?= \CUtil::JSEscape($selectorID)?>",
						{
							fieldId: "<?= \CUtil::JSEscape($fieldID)?>",
							mode: "<?= \CUtil::JSEscape($selectorMode)?>",
							multi: <?= $multi ? 'true' : 'false'?>
						}
					);
				}
			);<?
			}
			?></script><?
	}
}
?>
<? if (!$isBitrix24Template): ?>

	<div class="ynsistock-interface-filter-container">
		<? endif ?>

		<div
			class="pagetitle-container<? if (!$isBitrix24Template): ?> pagetitle-container-light<? endif ?> pagetitle-flexible-space">
			<? $APPLICATION->IncludeComponent(
				"bitrix:main.ui.filter",
				"",
				array(
					"FILTER_ID"             => $arParams["FILTER_ID"],
					"GRID_ID"               => $arParams["GRID_ID"],
					"FILTER"                => $arParams["FILTER"],
					"FILTER_PRESETS"        => $arParams["PRESETS"],
					"ENABLE_LABEL"          => true,
					'ENABLE_LIVE_SEARCH'    => $arParams['USE_LIVE_SEARCH'] == 'Y',
					'RESET_TO_DEFAULT_MODE' => true
				),
				$component,
				array("HIDE_ICONS" => true)
			); ?>
		</div>

		<div class="pagetitle-container pagetitle-align-right-container">

			<?php if ($arParams['SHOW_USER_SORT'] == 'Y' ||
					  $arParams['USE_GROUP_BY_SUBTASKS'] == 'Y' ||
					  $arParams['USE_GROUP_BY_GROUPS'] == 'Y' ||
					  $arParams['USE_EXPORT'] == 'Y' ||
					  !empty($arParams['POPUP_MENU_ITEMS'])
			): ?>
				<div id="tasks-popupMenuOptions"
					 class="webform-small-button webform-small-button-transparent webform-cogwheel">
					<span class="webform-button-icon"></span>
				</div>
			<?php endif ?>

			<?php
			$taskUrlTemplate = $arParams['MENU_GROUP_ID'] > 0 ? $arParams['PATH_TO_GROUP_TASKS_TASK']
				: $arParams['PATH_TO_USER_TASKS_TASK'];
			$taskTemplateUrlTemplate = $arParams['PATH_TO_USER_TASKS_TEMPLATES'];
			$taskTemplateUrlTemplateAction = $arParams['PATH_TO_USER_TASKS_TEMPLATES_ACTION'];
			?>

			<?php
			if($arParams['SHOW_CREATE_TASK_BUTTON'] != 'N'):
			?>
			<span class="webform-small-button-separate-wrap">
				<a href="javascript:void(0)" class="webform-small-button webform-small-button-blue" id="tasks-buttonAdd" onClick="<?=$arParams['BTN_ADD_ACTION']?>">
				   <?=(strlen($arParams['YNSISTOCK_CREATE_TEXT'])>0)?$arParams['YNSISTOCK_CREATE_TEXT']:GetMessage('YNSISTOCK_BTN_CREATE')?>
				   	
				</a>
				<!-- <span id="tasks-popupMenuAdd" class="webform-small-button-right-part"></span> -->
			</span>
			<?php endif?>
		</div>

		<? if (!$isBitrix24Template): ?>
	</div>
<? endif ?>

<?php if ($arParams['SHOW_USER_SORT'] == 'Y' ||
		  $arParams['USE_GROUP_BY_SUBTASKS'] == 'Y' ||
		  $arParams['USE_GROUP_BY_GROUPS'] == 'Y' ||
		  $arParams['USE_EXPORT'] == 'Y' ||
		  !empty($arParams['POPUP_MENU_ITEMS'])
): ?>
	<script type="text/javascript">
		(function()
		{
			var menuItemsOptions = [];

			<?foreach($arParams['POPUP_MENU_ITEMS'] as $menuItem):?>
				menuItemsOptions.push({
					tabId: "popupMenuAdd",
					text: "<?=$menuItem['TEXT']?>",
					href: "<?=$menuItem['HREF']?>"
				});
			<?endforeach;?>
			
			if (menuItemsOptions.length > 0) {
				var buttonRect = BX("tasks-popupMenuOptions").getBoundingClientRect();
				var menu = BX.PopupMenu.create(
					"popupMenuOptions",
					BX("tasks-popupMenuOptions"),
					menuItemsOptions,
					{
						closeByEsc: true,
						offsetLeft: buttonRect.width / 2,
						angle: true
					}
				);

				BX.bind(BX("tasks-popupMenuOptions"), "click", BX.delegate(function()
				{
					if (BX.data(BX("tasks-popupMenuOptions"), "disabled") !== true)
					{
						menu.popupWindow.show();
					}
				}, this));
			}
			else {
				$('#tasks-popupMenuOptions').hide();
			}
			
		})();
	</script>
<?php endif ?>
<?php

if ($isBitrix24Template)
{
	$this->EndViewTarget();
}