<?php

/*
	Addon PiPup (pipup_play) for app_player
	Воспроизводит аудио во всплывающем окне PiPup на Android TV / планшете
	(форк mhoogenbosch/PiPup, поле sound). Отправку выполняет дополнение
	терминалов modules/terminals/tts/pipup.addon.php.
*/

class pipup_play extends app_player_addon
{

    // Constructor
    function __construct($terminal)
    {
        $this->title = 'PiPup (всплывающее окно)';
        $this->description = 'Показывает медиа во всплывающем окне PiPup на Android TV или планшете; тип окна выбирается по ссылке или расширению: '
            . '<b>звук</b> (mp3, wav, ogg, m4a…), <b>картинка</b> (jpg, png, gif, webp; локальный файл уходит файлом), <b>SVG</b>, '
            . '<b>видео</b> (mp4, webm, m3u8, rtsp://…), <b>WebRTC</b> go2rtc (…/api/webrtc?src=…), <b>веб-страница / MJPEG</b> (прочие http-ссылки). '
            . 'Файлы — из веб-корня MajorDoMo или по ссылке. Звук и видео показываются на время файла, потоки — 60 с, картинки — 30 с. '
            . '«Стоп» закрывает все окна PiPup (звук по ссылке доигрывает); пауза, громкость и статус недоступны.'
            . '<br><b>Требования:</b> форк PiPup (mhoogenbosch) 0.27+, порт 7979 '
            . '(другой порт — в поле «Адрес» как ip:порт), PHP с curl, BASE_URL в config.php с портом веб-сервера.'
            . '<br><b>Текст и голос сообщений</b> (say, sayTo, ask) выводит тип TTS <b>pipup</b>: включите «Может произносить» и выберите pipup. '
            . 'Его настройка задаётся здесь, в поле <b>«Имя пользователя»</b>:'
            . '<ul style="margin:4px 0 4px 18px;padding:0">'
            . '<li>пусто — голос любого генератора речи;</li>'
            . '<li>метка генератора — только его голос: <code>sanotts</code>, <code>piper_tts</code>, <code>rh</code>, <code>sapi</code>, '
            . '<code>yandex</code>, <code>google</code>, <code>ivona</code>, <code>tilde</code>; можно несколько через запятую;</li>'
            . '<li><code>device</code> — говорить синтезатором речи самого устройства, файлы генераторов не использовать.</li>'
            . '</ul>'
            . 'Длительность окна сообщения считается по длине текста (10–60 с). '
            . 'Если текстовые окна не появляются, проверьте порог уровня сообщений терминала и приоритет модуля terminals '
            . '(модули с BREAK, например yandex_tts, останавливают рассылку события).';
        parent::__construct($terminal);
    }

    // Play: тип окна выбирается по ссылке или расширению файла
    function play($input)
    {
        $this->reset_properties();
        $input = trim((string)$input);
        if ($input === '') {
            $this->success = FALSE;
            $this->message = 'Input is missing!';
            return $this->success;
        }

        $pipup = $this->pipup();
        $type = $this->mediaType($input);

        // Потоки rtsp/rtmp передаются как есть, остальное — через адрес, доступный устройству
        if (preg_match('/^(rtsp|rtsps|rtmp):\/\//i', $input)) {
            $url = $input;
        } else {
            $url = $pipup->fileUrl($input);
        }
        if ($url === false) {
            $this->success = FALSE;
            $this->message = 'Cannot build URL for ' . $input . ' (file must be inside web root, or pass a URL)';
            return $this->success;
        }

        $local = $pipup->localPath($input);
        $seconds = 0;
        if ($local !== false && ($type == 'audio' || $type == 'video') && function_exists('getMediaDurationSeconds')) {
            $seconds = (int)getMediaDurationSeconds($local);
        }

        switch ($type) {
            case 'image':
                // Локальный файл уходит файлом, ссылка — ссылкой
                $ok = $pipup->sendImage(($local !== false) ? $local : $url, '', '🖼', 480, 30);
                break;
            case 'svg':
                $ok = $pipup->sendWebImage(($local !== false) ? $local : $url, '', '🖼', 300, 30);
                break;
            case 'video':
            case 'whep':
            case 'web':
                $options = array('width' => 640, 'duration' => ($seconds > 0) ? $seconds + 2 : 60, 'muted' => false);
                if ($type == 'web') {
                    $options['height'] = 360;
                }
                // Постоянный id для адреса: повторный playMedia() того же потока продлевает
                // открытое окно (PiPup только перезапускает таймер), а не открывает новое
                $options['id'] = 'play_' . md5($url);
                $ok = $pipup->sendVideo($url, '', '▶', $type, $options);
                break;
            default: // audio
                $ok = $pipup->sendSound($url, '', '', ($seconds > 0) ? $seconds + 2 : 15);
        }

        if ($ok) {
            $this->success = TRUE;
            $this->message = 'OK (' . $type . ')';
        } else {
            $this->success = FALSE;
            $this->message = 'PiPup did not accept ' . $type . ' (see cms/debmes, channel terminals)';
        }
        return $this->success;
    }

    // Stop: закрыть все окна PiPup (видео и веб-страницы останавливаются, звук по ссылке доиграет)
    function stop()
    {
        $this->reset_properties();
        $this->success = (bool)$this->pipup()->cancelAll();
        $this->message = $this->success ? 'OK' : 'PiPup did not accept cancel';
        return $this->success;
    }

    private function pipup()
    {
        include_once(DIR_MODULES . 'terminals/tts_addon.class.php');
        include_once(DIR_MODULES . 'terminals/tts/pipup.addon.php');
        return new pipup($this->terminal);
    }

    // Тип содержимого: audio | video | whep | image | svg | web
    private function mediaType($input)
    {
        if (preg_match('/^(rtsp|rtsps|rtmp):\/\//i', $input)) {
            return 'video';
        }
        if (stripos($input, '/api/webrtc') !== false) {
            return 'whep'; // go2rtc
        }
        $path = (string)parse_url($input, PHP_URL_PATH);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($ext, array('mp3', 'wav', 'ogg', 'oga', 'opus', 'm4a', 'aac', 'flac'), true)) {
            return 'audio';
        }
        if (in_array($ext, array('mp4', 'mkv', 'webm', 'mov', 'avi', 'm3u8', 'ts', 'mpd'), true)) {
            return 'video';
        }
        if (in_array($ext, array('jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'), true)) {
            return 'image';
        }
        if ($ext == 'svg') {
            return 'svg';
        }
        if (preg_match('/^https?:\/\//i', $input) && in_array($ext, array('', 'html', 'htm', 'php', 'cgi', 'mjpg', 'mjpeg'), true)) {
            return 'web'; // страница, MJPEG-поток и т.п.
        }
        return 'audio'; // как раньше: неизвестный локальный файл считаем звуком
    }
}
