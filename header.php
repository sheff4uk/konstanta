<?php
//ini_set('display_errors', 1);
//error_reporting(E_ALL);

include_once "checkrights.php";

// Функция делает ссылки кликабельными
function src_url($src) {
	$src = preg_replace('/((?:\w+:\/\/|www\.)[\w.\/%\d&?#+=-]+)/i', '<a href="\1" target="_blank" class="button">\1</a>', $src);
	return $src;
}

?>
<!DOCTYPE html>
<html lang="ru">
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?=$title?></title>
	<link rel="stylesheet" type='text/css' href="js/ui/jquery-ui.css?3">
	<link rel='stylesheet' type='text/css' href='css/style.css?37'>
	<link rel='stylesheet' type='text/css' href='css/buttons.css'>
	<link rel='stylesheet' type='text/css' href='css/animate.css'>
	<link rel='stylesheet' type='text/css' href='plugins/jReject-master/css/jquery.reject.css'>
	<link rel='stylesheet' type='text/css' href='css/loading.css'>
	<!-- <script src="https://kit.fontawesome.com/020f21ae61.js" crossorigin="anonymous"></script> -->
	<!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"> -->
		<link rel="stylesheet" type='text/css' href="assets/fontawesome/css/all.min.css">
	<script src="js/jquery-1.11.3.min.js"></script>
	<script src="js/ui/jquery-ui.js"></script>
	<script src="js/script.js?v=6" type="text/javascript"></script>
	<script src="js/jquery.printPage.js" type="text/javascript"></script>
	<script src="js/jquery.columnhover.js" type="text/javascript"></script>
	<script src="js/noty/packaged/jquery.noty.packaged.min.js" type="text/javascript"></script>
	<script src="js/Chart.min.js?1" type="text/javascript"></script>
	<!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script> -->
		<script src="js/moment.min.js" type="text/javascript"></script>
	<!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/locale/ru.min.js"></script> -->
		<script src="js/ru.min.js" type="text/javascript"></script>
	<!-- <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-moment@0.1.1"></script> -->
		<script src="js/chartjs-adapter-moment@0.1.1" type="text/javascript"></script>
	<script src="plugins/jReject-master/js/jquery.reject.js" type="text/javascript"></script>
	<!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/1.4.0/clipboard.min.js"></script> -->
		<script src="js/clipboard.min.js" type="text/javascript"></script>
	<!-- <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.8/css/select2.min.css" rel="stylesheet" /> -->
		<link rel="stylesheet" type='text/css' href="css/select2.min.css">
	<!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.8/js/select2.min.js"></script> -->
		<script src="js/select2.min.js" type="text/javascript"></script>
	<!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.8/js/i18n/ru.js" type="text/javascript"></script> -->
		<script src="js/ru.js" type="text/javascript"></script>
	<!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.js"></script> -->
		<script src="js/jquery.mask.js" type="text/javascript"></script>
	<script src="/js/jquery.ui.totop.js"></script>

	<script>
		$(document).ready(function(){
			var is_chrome = /chrome/i.test( navigator.userAgent );

			$('#body_wraper').fadeIn('slow');
			$('#loading').hide();

			//Проверка браузера
			$.reject({
				reject: {
					safari: true, // Apple Safari
					//chrome: true, // Google Chrome
					firefox: true, // Mozilla Firefox
					msie: true, // Microsoft Internet Explorer
					//opera: true, // Opera
					konqueror: true, // Konqueror (Linux)
					unknown: true // Everything else
				},
				close: false,
				display: ['chrome','opera'],
				header: 'Ваш браузер устарел',
				paragraph1: 'Вы пользуетесь устаревшим браузером, который не поддерживает современные веб-стандарты и представляет угрозу безопасности Ваших данных.',
				paragraph2: 'Пожалуйста, установите современный браузер:',
				closeMessage: ''
			});

//			// Принудительное перемещение к якорю после перезагрузки страницы
//			var loc = window.location.hash.replace("#","");
//			if (loc != "") {
//				location.replace(document.URL);
//			}

			$( 'input[type=submit], input[type=button], .button, button' ).button();

			// Календарь
			$( "input.date" ).datepicker({
				dateFormat: 'dd.mm.yy',
				onClose: function( selectedDate ) {
					if( $(this).hasClass( "from" ) ) {
						$(this).parents( "form" ).find( ".to" ).datepicker( "option", "minDate", selectedDate );
					}
					if( $(this).hasClass( "to" ) ) {
						$(this).parents( "form" ).find( ".from" ).datepicker( "option", "maxDate", selectedDate );
					}
				}
			});

			// Плавная прокрутка к якорю при загрузке страницы
			var loc = window.location.hash.replace("#","");
			if (loc == "") {loc = "main"}

			var nav = $("#"+loc);
			if (nav.length) {
				var destination = nav.offset().top - 300;
				$("body:not(:animated)").animate({ scrollTop: destination }, 200);
				$("html").animate({ scrollTop: destination }, 200);
			}

			// Плавная прокрутка к якорю при клике на ссылку якоря
			$('body').on('click', 'a[href*=#]', function() {
				if (location.pathname.replace(/^\//,'') == this.pathname.replace(/^\//,'') && location.hostname == this.hostname) {
					var $target = $(this.hash);
					//$target = $target.length && $target || $('[name=' + this.hash.slice(1) +']');
					if ($target.length) {
						var targetOffset = $target.offset().top - ($(".navbar").outerHeight(true)+ 150);
						$('html,body').animate({scrollTop: targetOffset}, 500);
						//return false;
					}
				}
			});
		});

		// Диалог подтверждения действия
		function confirm(text, href) {
			var self = this;
			self.dfd = $.Deferred();
			var n = noty({
				text		: text,
				dismissQueue: false,
				modal		: true,
				buttons		: [
					{addClass: 'btn btn-primary', text: 'Ok', onClick: function ($noty) {
						$noty.close();
						//noty({timeout: 3000, text: 'Вы нажали кнопку "Ok"', type: 'success'});
						if(href !== undefined) {window.location.href = href}
						self.dfd.resolve(true);
					}
					},
					{addClass: 'btn btn-danger', text: 'Отмена', onClick: function ($noty) {
						$noty.close();
						noty({timeout: 3000, text: 'Вы нажали кнопку "Отмена"', type: 'error'});
						self.dfd.resolve(false);
					}
					}
				],
				closable: false,
				timeout: false
			});
			return self.dfd.promise();
		}

		// Функция замены в строке спец символов
		var entityMap = {
			'&': '&amp;',
			'<': '&lt;',
			'>': '&gt;',
			'"': '&quot;',
			"'": '&#39;',
			'/': '&#x2F;',
			'`': '&#x60;',
			'=': '&#x3D;'
		};

		function escapeHtml(string) {
			return String(string).replace(/[&<>"'`=\/]/g, function (s) {
				return entityMap[s];
			});
		}
	</script>

	<style type="text/css" media="print">
		.main_table td {
			overflow: visible;
		}
	</style>

<?php
	// Выводим собранные в сесии сообщения через noty
	include "noty.php";
?>

</head>
<body>

	<div id="loading" class='uil-default-css' style='transform:scale(1); position: absolute; left: calc(50% - 100px); top: calc(50% - 100px); align-items: center; display: flex;'><div style='top:80px;left:93px;width:14px;height:40px;background:#fdce46;-webkit-transform:rotate(0deg) translate(0,-60px);transform:rotate(0deg) translate(0,-60px);border-radius:10px;position:absolute;'></div><div style='top:80px;left:93px;width:14px;height:40px;background:#fdce46;-webkit-transform:rotate(30deg) translate(0,-60px);transform:rotate(30deg) translate(0,-60px);border-radius:10px;position:absolute;'></div><div style='top:80px;left:93px;width:14px;height:40px;background:#fdce46;-webkit-transform:rotate(60deg) translate(0,-60px);transform:rotate(60deg) translate(0,-60px);border-radius:10px;position:absolute;'></div><div style='top:80px;left:93px;width:14px;height:40px;background:#fdce46;-webkit-transform:rotate(90deg) translate(0,-60px);transform:rotate(90deg) translate(0,-60px);border-radius:10px;position:absolute;'></div><div style='top:80px;left:93px;width:14px;height:40px;background:#fdce46;-webkit-transform:rotate(120deg) translate(0,-60px);transform:rotate(120deg) translate(0,-60px);border-radius:10px;position:absolute;'></div><div style='top:80px;left:93px;width:14px;height:40px;background:#fdce46;-webkit-transform:rotate(150deg) translate(0,-60px);transform:rotate(150deg) translate(0,-60px);border-radius:10px;position:absolute;'></div><div style='top:80px;left:93px;width:14px;height:40px;background:#fdce46;-webkit-transform:rotate(180deg) translate(0,-60px);transform:rotate(180deg) translate(0,-60px);border-radius:10px;position:absolute;'></div><div style='top:80px;left:93px;width:14px;height:40px;background:#fdce46;-webkit-transform:rotate(210deg) translate(0,-60px);transform:rotate(210deg) translate(0,-60px);border-radius:10px;position:absolute;'></div><div style='top:80px;left:93px;width:14px;height:40px;background:#fdce46;-webkit-transform:rotate(240deg) translate(0,-60px);transform:rotate(240deg) translate(0,-60px);border-radius:10px;position:absolute;'></div><div style='top:80px;left:93px;width:14px;height:40px;background:#fdce46;-webkit-transform:rotate(270deg) translate(0,-60px);transform:rotate(270deg) translate(0,-60px);border-radius:10px;position:absolute;'></div><div style='top:80px;left:93px;width:14px;height:40px;background:#fdce46;-webkit-transform:rotate(300deg) translate(0,-60px);transform:rotate(300deg) translate(0,-60px);border-radius:10px;position:absolute;'></div><div style='top:80px;left:93px;width:14px;height:40px;background:#fdce46;-webkit-transform:rotate(330deg) translate(0,-60px);transform:rotate(330deg) translate(0,-60px);border-radius:10px;position:absolute;'></div></div>

	<!-- NAVBAR -->
	<nav class="navbar">
		<div class="page">
		<div class="navbar-header" id="main">
			<a class="navbar-brand" href="/" title="На главную" style="position: relative;"><?=$company_name?> <sub>β</sub></a>
		</div>
<?php
	if( !empty($_SESSION['id']) ) {
		if( in_array('blanks', $Rights) ) {
			$menu["Бланки"] = "blanks.php";
		}
		if( in_array('plan_batch', $Rights) ) {
			$menu["Производство"]["<i class='fas fa-clipboard-list fa-lg'></i> График заливки"] = "plan_batch.php";
		}
		if( in_array('filling_opening', $Rights) ) {
			$menu["Производство"]["<i class='fas fa-fill-drip fa-lg'></i> Чек листы операторов"] = "filling.php";
			$menu["Производство"]["<i class='fas fa-expand fa-lg'></i> Расформовка"] = "opening.php";
			//$menu["Производство"]["<i class='fas fa-industry fa-lg'></i> Стадии производства"] = "manufacturing.php";
		}
		if( in_array('stock', $Rights) ) {
			$menu["Производство"]["<i class='fas fa-pallet fa-lg'></i> Склад продукции"] = "stock.php";
		}
		if( in_array('plan_shipment', $Rights) ) {
			$menu["Производство"]["<i class='fas fa-truck-ramp-box fa-lg'></i> График отгрузки"] = "plan_shipment.php";
		}
		if( in_array('shipment', $Rights) ) {
			$menu["Производство"]["<i class='fas fa-truck fa-lg'></i> Отгрузка"] = "shipment.php";
		}
		if( in_array('daily_production', $Rights) ) {
			$menu["Анализ"]["<i class='fas fa-cogs fa-lg'></i> Заливки по суткам"] = "daily_production.php";
		}
		if( in_array('history', $Rights) ) {
			$menu["Анализ"]["<i class='fas fa-history fa-lg'></i> История кассет"] = "history.php";
		}
		if( in_array('climate', $Rights) ) {
			$menu["Анализ"]["<i class='fas fa-thermometer-half fa-lg'></i> Климат"] = "climate.php";
		}
		if( in_array('consumption', $Rights) ) {
			$menu["Анализ"]["Расход сырья"] = "consumption.php";
		}
		if( in_array('reject', $Rights) ) {
			$menu["Анализ"]["Брак"] = "statistic.php";
			$menu["Анализ"]["<i class='fas fa-trash fa-lg'></i> Статистика брака"] = "daily_reject_stat.php";
		}
		if( in_array('cubetest', $Rights) ) {
			$menu["Доп. данные"]["<i class='fas fa-cube fa-lg'></i> Испытания кубов"] = "cubetest.php";
		}
		if( in_array('shift_log', $Rights) ) {
			$menu["Доп. данные"]["<i class='fas fa-user-clock fa-lg'></i> Журнал смен"] = "shift_log.php";
		}
		if( in_array('timesheet', $Rights) ) {
			$menu["Доп. данные"]["<i class='fas fa-table fa-lg'></i> Табель"] = "timesheet.php";
		}
		if( in_array('pallet_accounting', $Rights) ) {
			$menu["Учет"]["<i class='fas fa-stream fa-lg'></i> Поддоны"] = "pallet_accounting.php";
		}
		if( in_array('material_accounting', $Rights) ) {
			$menu["Учет"]["<i class='fas fa-shapes fa-lg'></i> Сырьё"] = "material_accounting.php";
		}
		if( in_array('shell_accounting', $Rights) ) {
			$menu["Учет"]["<i class='fas fa-th fa-lg'></i> Формы"] = "shell_accounting.php";
		}
		if( in_array('fuel_accounting', $Rights) ) {
			$menu["Учет"]["<i class='fas fa-gas-pump fa-lg'></i> Дизтопливо"] = "fuel_accounting.php";
		}
		if( in_array('overal_accounting', $Rights) ) {
			$menu["Учет"]["<i class='fas fa-sign-language fa-lg'></i> СИЗ/расходники"] = "overal_accounting.php";
		}
		if( in_array('users', $Rights) ) {
			$menu["<i class='fas fa-cog fa-lg'></i>"]["Пользователи"] = "users.php";
		}
		if( in_array('mix_formula', $Rights) ) {
			$menu["<i class='fas fa-cog fa-lg'></i>"]["Рецепты"] = "mix_formula.php";
		}
		if( in_array('cassettes', $Rights) ) {
			$menu["<i class='fas fa-cog fa-lg'></i>"]["Кассеты"] = "cassettes.php";
		}
		if( in_array('settings', $Rights) ) {
			$menu["<i class='fas fa-cog fa-lg'></i>"]["Настройки"] = "settings.php";
		}
		if( in_array('migrants', $Rights) ) {
			$menu["<i class='fas fa-cog fa-lg'></i>"]["Мигранты"] = "migrants.php";
		}
		$menu["Выход {$USR_Icon}"] = "exit.php";
	}

	// Формируем элементы меню
	$nav_buttons = "";
	foreach ($menu as $title=>$url) {
		// Если содержится подменю
		if (is_array($url)) {
			$sub_buttons = "";
			$class = "";
			foreach ($url as $sub_title=>$sub_url) {
				$pieces = explode("?", $sub_url);
				if( strpos($_SERVER["REQUEST_URI"], $pieces[0]) == 1 ) {
					$sub_class = "active";
					$class = "active";
				}
				else {
					$sub_class = "";
				}
				$sub_buttons .= "<li class='{$sub_class}'><a href='{$sub_url}'>{$sub_title}</a></li>";
			}
			$nav_buttons .= "<li class='parent {$class}'><a href='#'>{$title} <i class='fas fa-angle-down'></i></a><ul>{$sub_buttons}</ul></li>";
		}
		else {
			$pieces = explode("?", $url);
			$class = (strpos($_SERVER["REQUEST_URI"], $pieces[0]) == 1) ? "active" : "";
			$nav_buttons .= "<li class='{$class}'><a href='{$url}'>{$title}</a></li>";
		}
	}

	echo "<ul class='navbar-nav'>";
	echo $nav_buttons;
	echo "</ul>";
	echo "</div>";
	echo "</nav>";
	// END NAVBAR
	//die("<h1>Ведутся работы</h1>");
	$MONTHS = array(1=>'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь');
	$MONTHS_DATE = array(1=>'янв.', 'февр.', 'мар.', 'апр.', 'мая', 'июня', 'июля', 'авг.', 'сент.', 'окт.', 'нояб.', 'дек.');
?>
	<div id="body_wraper" style="display: none;" class="page">

<script>
	$(function() {
		//$("#mtel").mask("+7 (999) 999 99 99");
		$("#mtel").mask("+0000000000000");
	});
</script>
