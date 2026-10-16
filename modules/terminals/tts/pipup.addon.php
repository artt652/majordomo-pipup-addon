<?php

class pipup extends tts_addon
{
    // Собственное свойство: в базовом tts_addon $title объявлен private,
    // запись в него из наследника создаёт динамическое свойство (Deprecated в PHP 8.2+).
    public $title = 'PiPup';

    // Порт встроенного веб-сервера PiPup по умолчанию
    const DEFAULT_PORT = 7979;

    function __construct($terminal)
    {
        $this->title = 'PiPup';
        parent::__construct($terminal);
    }

    function say($phrase, $level = 0)
    {
        return $this->sendCommand($phrase, 'Уведомление', $this->messageId($phrase));
    }

    function ask($phrase, $level = 0)
    {
        return $this->sendCommand($phrase, 'Вопрос', $this->messageId($phrase));
    }

    /**
     * Готовый аудиофайл от генератора речи (SAY_CACHED_READY → terminals → sayCached).
     * То же окно, что открыл say() (тот же id), перерисовывается со значком 🔊 и звуком.
     */
    function sayCached($phrase, $level = 0, $cached_file = '')
    {
        // Выбор генератора голоса: поле «Имя пользователя» плеера в карточке терминала.
        // Значение — метка генератора в имени файла: префикс (sanotts, piper_tts, rh, sapi)
        // или суффикс (yandex, google, ivona, tilde); можно несколько через запятую.
        // Пусто — принимать файлы любого генератора.
        if (!$this->engineAllowed($cached_file)) {
            DebMes('PiPup: skip ' . basename((string)$cached_file) . ' (terminal voice: ' . $this->terminal['PLAYER_USERNAME'] . ')', 'terminals');
            return 0;
        }
        $soundUrl = $this->fileUrl($cached_file);
        if ($soundUrl === false) {
            DebMes("PiPup: cannot build URL for cached file '$cached_file'", 'terminals');
            return 0;
        }
        $duration = 30;
        $local = $this->localPath($cached_file);
        if ($local !== false && function_exists('getMediaDurationSeconds')) {
            $duration = max(30, (int)getMediaDurationSeconds($local) + 2);
        }
        return $this->sendSound($soundUrl, $phrase, '🔊 Уведомление', $duration, null, $this->messageId($phrase));
    }

    // Подходит ли файл генератору, выбранному для терминала
    private function engineAllowed($cached_file)
    {
        $filter = isset($this->terminal['PLAYER_USERNAME']) ? trim((string)$this->terminal['PLAYER_USERNAME']) : '';
        if ($filter === '') {
            return true;
        }
        // Имя файла без расширения. Генераторы метят файлы по-разному:
        // префиксом (sanotts_<md5>, piper_tts_<md5>, rh_<md5>, sapi_<md5>)
        // или суффиксом (<md5>_yandex, <md5>_google, <md5>_ivona, <md5>_tilde).
        $name = strtolower(pathinfo((string)$cached_file, PATHINFO_FILENAME));
        foreach (explode(',', $filter) as $engine) {
            $engine = strtolower(trim($engine));
            if ($engine === '') {
                continue;
            }
            $tail = '_' . $engine;
            if (strpos($name, $engine . '_') === 0
                || (strlen($name) > strlen($tail) && substr($name, -strlen($tail)) === $tail)) {
                return true;
            }
        }
        return false;
    }

    // Один id для say() и sayCached() одной фразы: окно одно, звук «догоняет» текст
    private function messageId($phrase)
    {
        $tid = isset($this->terminal['ID']) ? $this->terminal['ID'] : '';
        return 'mdm_' . md5($tid . '|' . (string)$phrase);
    }

    function sendCommand($phrase, $title = 'Уведомление', $id = '')
    {
        if (empty($this->terminal['HOST'])) {
            return 0;
        }
        if (!function_exists('curl_init')) {
            DebMes('PiPup: PHP extension curl is not available', 'terminals');
            return 0;
        }

        // HOST может быть указан как "ip" или "ip:port"
        $host = trim($this->terminal['HOST']);
        if (strpos($host, ':') === false) {
            $host .= ':' . self::DEFAULT_PORT;
        }
        $url = 'http://' . $host . '/notify';

        DebMes("Sending notification '$phrase' to " . $host, 'terminals');

        $data = array(
            'duration' => 30,
            'position' => 0,
            'title' => $title,
            'titleColor' => '#0066cc',
            'titleSize' => 20,
            'message' => (string)$phrase,
            'messageColor' => '#000000',
            'messageSize' => 14,
            'backgroundColor' => '#ffffff',
        );

        // Форк PiPup (mhoogenbosch): уведомления без id занимают один слот и заменяют друг друга.
        // Уникальный id — отдельное окно, stack — выстраивать окна в столбик, а не друг на друга.
        $forkFields = array(
            'id' => ($id != '') ? (string)$id : 'mdm_' . str_replace('.', '_', uniqid('', true)),
            'stack' => 'vertical',
        );

        $httpCode = $this->post($url, array_merge($data, $forkFields), $error);
        if ($httpCode === 400) {
            // Оригинальный PiPup не знает полей id/stack и отвечает 400 — повторяем без них.
            $httpCode = $this->post($url, $data, $error);
        }

        if ($httpCode !== 200) {
            DebMes("PiPup: notify to $host failed (HTTP $httpCode) $error", 'terminals');
            return 0;
        }

        return 1;
    }

    /**
     * Показать картинку (для вызова из сценариев).
     * $image — URL (http/https, телевизор должен до него достучаться)
     *          или локальный путь к файлу (отправляется файлом через multipart).
     */
    function sendImage($image, $text = '', $title = '', $width = 480)
    {
        if (empty($this->terminal['HOST']) || $image == '') {
            return 0;
        }
        if (!function_exists('curl_init')) {
            DebMes('PiPup: PHP extension curl is not available', 'terminals');
            return 0;
        }
        $host = trim($this->terminal['HOST']);
        if (strpos($host, ':') === false) {
            $host .= ':' . self::DEFAULT_PORT;
        }
        $url = 'http://' . $host . '/notify';
        if ($title == '') {
            $title = 'Уведомление';
        }

        $data = array(
            'duration' => 30,
            'position' => 0,
            'title' => $title,
            'titleColor' => '#0066cc',
            'titleSize' => 20,
            'message' => (string)$text,
            'messageColor' => '#000000',
            'messageSize' => 14,
            'backgroundColor' => '#ffffff',
        );
        $forkFields = array(
            'id' => 'mdm_' . str_replace('.', '_', uniqid('', true)),
            'stack' => 'vertical',
        );

        if (preg_match('/^https?:\/\//i', $image)) {
            $data['media'] = array('image' => array('uri' => $image, 'width' => (int)$width));
            $multipart = false;
        } elseif (is_file($image) && is_readable($image)) {
            if (!class_exists('CURLFile')) {
                DebMes('PiPup: CURLFile is not available', 'terminals');
                return 0;
            }
            $data['image'] = new CURLFile($image);
            $data['imageWidth'] = (int)$width;
            $multipart = true;
        } else {
            DebMes("PiPup: image not found: $image", 'terminals');
            return 0;
        }

        DebMes("Sending image '$image' to " . $host, 'terminals');

        $httpCode = $this->post($url, array_merge($data, $forkFields), $error, $multipart);
        if ($httpCode === 400) {
            // Оригинальный PiPup не знает полей id/stack — повторяем без них.
            $httpCode = $this->post($url, $data, $error, $multipart);
        }
        if ($httpCode !== 200) {
            DebMes("PiPup: image to $host failed (HTTP $httpCode) $error", 'terminals');
            return 0;
        }
        return 1;
    }

    /**
     * Показать видео (для вызова из сценариев). Видео передаётся только ссылкой,
     * телевизор подключается к источнику сам.
     * $type:    'video' — RTSP/HLS/MP4 (ExoPlayer), 'web' — веб-страница/MJPEG,
     *           'whep' — WebRTC из go2rtc (/api/webrtc?src=...), только форк >= 0.25
     * $options: width, height, poster (URL снимка до первого кадра), muted (по умолчанию true),
     *           duration (0 — пока не вызовут cancel()), id (тот же id — обновить окно на месте)
     * Поля poster/whep/height понимает только форк PiPup.
     */
    function sendVideo($uri, $text = '', $title = '', $type = 'video', $options = array())
    {
        if (!in_array($type, array('video', 'web', 'whep'), true) || $uri == '') {
            DebMes("PiPup: bad video type or empty uri ($type)", 'terminals');
            return 0;
        }
        if (!is_array($options)) {
            $options = array();
        }
        $url = $this->notifyUrl('/notify');
        if ($url === false) {
            return 0;
        }

        $media = array(
            'uri' => $uri,
            'width' => isset($options['width']) ? (int)$options['width'] : 640,
            'muted' => isset($options['muted']) ? (bool)$options['muted'] : true,
        );
        if (isset($options['height'])) {
            $media['height'] = (int)$options['height'];
        }
        if (!empty($options['poster'])) {
            $media['poster'] = $options['poster'];
        }

        $data = array(
            'duration' => isset($options['duration']) ? (int)$options['duration'] : 30,
            'position' => 0,
            'title' => ($title == '') ? 'Камера' : $title,
            'titleColor' => '#0066cc',
            'titleSize' => 20,
            'message' => (string)$text,
            'messageColor' => '#000000',
            'messageSize' => 14,
            'backgroundColor' => '#ffffff',
            'media' => array($type => $media),
        );
        $forkFields = array(
            'id' => !empty($options['id']) ? (string)$options['id'] : 'mdm_' . str_replace('.', '_', uniqid('', true)),
            'stack' => 'vertical',
        );

        DebMes("Sending $type '$uri' to " . $url, 'terminals');
        $httpCode = $this->post($url, array_merge($data, $forkFields), $error);
        if ($httpCode === 400) {
            $httpCode = $this->post($url, $data, $error);
        }
        if ($httpCode !== 200) {
            DebMes("PiPup: $type to $url failed (HTTP $httpCode) $error", 'terminals');
            return 0;
        }
        return 1;
    }

    /**
     * Окно со звуком по ссылке (поле sound, только форк PiPup >= 0.18).
     * Используется плеером app_player «pipup_player». $volume — 0..1 или null.
     */
    function sendSound($soundUrl, $text = '', $title = '', $duration = 15, $volume = null, $id = '')
    {
        $url = $this->notifyUrl('/notify');
        if ($url === false || $soundUrl == '') {
            return 0;
        }
        $data = array(
            'duration' => max(3, (int)$duration),
            'position' => 0,
            'title' => ($title == '') ? '🔊' : $title,
            'titleColor' => '#0066cc',
            'titleSize' => 20,
            'message' => (string)$text,
            'messageColor' => '#000000',
            'messageSize' => 14,
            'backgroundColor' => '#ffffff',
            'sound' => $soundUrl,
        );
        if ($volume !== null) {
            $data['soundVolume'] = max(0, min(1, (float)$volume));
        }
        // По умолчанию id новый: при том же id и том же содержимом PiPup звук не проигрывает
        $forkFields = array(
            'id' => ($id != '') ? (string)$id : 'mdm_' . str_replace('.', '_', uniqid('', true)),
            'stack' => 'vertical',
        );

        DebMes("Sending sound '$soundUrl' to " . $url, 'terminals');
        $httpCode = $this->post($url, array_merge($data, $forkFields), $error);
        if ($httpCode === 400) {
            $httpCode = $this->post($url, $data, $error);
        }
        if ($httpCode !== 200) {
            DebMes("PiPup: sound to $url failed (HTTP $httpCode) $error", 'terminals');
            return 0;
        }
        return 1;
    }

    /**
     * Закрыть окно: с $id — только окно с этим id (форк), без $id — окно, отправленное без id.
     */
    function cancel($id = '')
    {
        $url = $this->notifyUrl('/cancel' . ($id != '' ? '?id=' . rawurlencode($id) : ''));
        if ($url === false) {
            return 0;
        }
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, '');
        $httpCode = $this->doRequest($ch, $error, 4);
        if ($httpCode !== 200) {
            DebMes("PiPup: cancel $url failed (HTTP $httpCode) $error", 'terminals');
            return 0;
        }
        return 1;
    }

    /**
     * Локальный файл внутри веб-корня (realpath) или false.
     */
    public function localPath($input)
    {
        $input = trim((string)$input);
        if ($input === '' || preg_match('/^https?:\/\//i', $input)) {
            return false;
        }
        $path = $input;
        if (!is_file($path) && is_file(ROOT . ltrim($path, '/'))) {
            $path = ROOT . ltrim($path, '/');
        }
        if (!is_file($path)) {
            return false;
        }
        $real = realpath($path);
        $root = realpath(ROOT);
        if ($real === false || $root === false || strpos($real, $root . DIRECTORY_SEPARATOR) !== 0) {
            return false;
        }
        return $real;
    }

    /**
     * URL, по которому устройство заберёт файл (LAN-IP сервера + порт из BASE_URL), или false.
     * Ссылки http(s) возвращаются как есть.
     */
    public function fileUrl($input)
    {
        $input = trim((string)$input);
        if (preg_match('/^https?:\/\//i', $input)) {
            return $input;
        }
        $real = $this->localPath($input);
        if ($real === false) {
            return false;
        }
        $ip = getLocalIp();
        if (!$ip) {
            return false;
        }
        $port = parse_url(BASE_URL, PHP_URL_PORT);
        $rel = str_replace(DIRECTORY_SEPARATOR, '/', substr($real, strlen(realpath(ROOT)) + 1));
        $rel = implode('/', array_map('rawurlencode', explode('/', $rel)));
        return 'http://' . $ip . ($port ? ':' . $port : '') . rtrim(ROOTHTML, '/') . '/' . $rel;
    }

    // Адрес PiPup для пути $path, false — нет HOST или curl
    private function notifyUrl($path)
    {
        if (empty($this->terminal['HOST'])) {
            return false;
        }
        if (!function_exists('curl_init')) {
            DebMes('PiPup: PHP extension curl is not available', 'terminals');
            return false;
        }
        $host = trim($this->terminal['HOST']);
        if (strpos($host, ':') === false) {
            $host .= ':' . self::DEFAULT_PORT;
        }
        return 'http://' . $host . $path;
    }

    // POST JSON или multipart, возвращает HTTP-код (0 — нет соединения)
    private function post($url, $data, &$error, $multipart = false)
    {
        $error = '';
        if ($multipart) {
            // multipart: все поля, кроме файла, передаются строками
            foreach ($data as $k => $v) {
                if (!is_object($v)) {
                    $data[$k] = (string)$v;
                }
            }
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data); // Content-Type с boundary curl выставит сам
            // NanoHTTPD декодирует текстовые поля multipart в кодировке из заголовка запроса
            // (по умолчанию US-ASCII — отсюда «кракозябры»). boundary curl допишет сам.
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data; charset=UTF-8'));
            return $this->doRequest($ch, $error, 10); // с запасом на передачу файла
        }
        $flags = JSON_UNESCAPED_UNICODE;
        if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $flags |= JSON_INVALID_UTF8_SUBSTITUTE;
        }
        $postData = json_encode($data, $flags);
        if ($postData === false) {
            $error = 'json_encode failed: ' . json_last_error_msg();
            return 0;
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8',
            'Content-Length: ' . strlen($postData),
        ));
        return $this->doRequest($ch, $error, 4);
    }

    private function doRequest($ch, &$error, $timeout)
    {
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, (int)$timeout);

        $result = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($result === false) {
            $error = curl_error($ch);
            $httpCode = 0;
        }
        if (PHP_VERSION_ID < 80000) {
            curl_close($ch); // с PHP 8.0 не нужен, в PHP 8.5 — Deprecated
        }
        return $httpCode;
    }
}
