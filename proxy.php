<?php
switch ($_GET["doc"]) {
    case "waybill":
		$url = "https://service-online.su/forms/auto/ttn/";
        break;
    case "upd":
        $url = "https://service-online.su/forms/buh/upd/";
        break;
    case "schet":
        $url = "https://service-online.su/forms/buh/schet/";
        break;
    case "doverennost":
        $url = "https://service-online.su/forms/doverennost_TMC/";
        break;
    default:
        $url = "";
}

echo $_GET["cookie"];
die;

if ( $url != "" ) {
	$data = http_build_query($_POST);
	// Счет с печатью или без
	if( $_POST["stamped"] == 1 ) {
		$service_online = $_GET["cookie"];
		$headers = stream_context_create(array(
			'http' => array(
				'method' => 'POST',
				'header' => array(
					'Cookie: ' . $service_online,
					'Referer: ' . $url
				),
				'content' => $data
			)
		));
	}
	else {
		$headers = stream_context_create(array(
			'http' => array(
				'method' => 'POST',
				'header' => array('Referer: ' . $url),
				'content' => $data
			)
		));
	}

	$content = file_get_contents($url . 'blanc.php', false, $headers);

	// Извлечение пути к файлу из заголовков
	if (isset($http_response_header)) {
		foreach ($http_response_header as $header) {
			if (strpos(strtolower($header), 'pdfhandoff') !== false) {

				// 1. Извлекаем закодированное значение куки pdfhandoff
				preg_match('/pdfhandoff=([^;]+)/', $header, $matches);
					
				// 2. Декодируем URL-символы (%7B -> {, %22 -> " и т.д.)
				$json_string = urldecode($matches[1]);
				
				// 3. Декодируем полученную JSON-строку в массив
				$data = json_decode($json_string, true);
				
				// 4. Забираем нужный ключ
				$file_path = $data['file'] ?? null;
				
				echo file_get_contents('https://service-online.su'.$file_path, false, null);
			}
		}
	}
}
?>