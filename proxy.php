<?php
$data = http_build_query($_POST);
$headers = stream_context_create(array(
	'http' => array(
		'method' => 'POST',
		'header' => array('Referer: https://service-online.su/forms/auto/ttn/'),
		'content' => $data
	)
));

$content = file_get_contents('https://service-online.su/forms/auto/ttn/blanc.php', false, $headers);

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
				
				header('Content-Type: application/pdf');
				echo file_get_contents('https://service-online.su'.$file_path, false, null);
			}
		}
	}
?>