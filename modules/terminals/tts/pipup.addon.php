<?php

/**
 * TTS-дополнение pipup.
 *
 * Языки, настройки и разбор интентов живут в modules/pipup/pipup.lib.php.
 * Если установлены только два addon-файла (старый способ), ниже определены
 * минимальные запасные функции — дополнение продолжает работать как раньше.
 */
$pipup_lib = dirname(dirname(dirname(__FILE__))) . '/pipup/pipup.lib.php';
if (!function_exists('pipup_t') && is_file($pipup_lib)) {
    include_once $pipup_lib;
}
if (!function_exists('pipup_t')) {
    function pipup_t($key, $fallback = '')
    {
        return $fallback !== '' ? $fallback : $key;
    }

    function pipup_cfg($key, $default = null)
    {
        $defaults = array(
            'TTS_LANGUAGE' => 'auto', 'DURATION_TEXT_BASE' => 10,
            'DURATION_TEXT_CHARS' => 15, 'DURATION_TEXT_MAX' => 60,
            'DURATION_SOUND' => 15, 'DURATION_IMAGE' => 30, 'DURATION_VIDEO' => 60,
            'COLOR_TITLE' => '#0066cc', 'COLOR_MESSAGE' => '#000000',
            'COLOR_BACKGROUND' => '#ffffff', 'MEDIA_COLOR_TITLE' => '#8ab4f8',
            'MEDIA_COLOR_MESSAGE' => '#e8eaed', 'MEDIA_COLOR_BACKGROUND' => '#202124',
            'STACK' => 'vertical', 'POSITION' => 0, 'FALLBACK_400' => 1,
        );
        return array_key_exists($key, $defaults) ? $defaults[$key] : $default;
    }

    function pipup_tts_language()
    {
        $site = defined('SETTINGS_SITE_LANGUAGE') ? strtolower((string)SETTINGS_SITE_LANGUAGE) : 'en';
        $map = array('en' => 'en-US', 'ru' => 'ru-RU', 'ua' => 'uk-UA', 'uk' => 'uk-UA');
        return isset($map[$site]) ? $map[$site] : 'en-US';
    }

    function pipup_auto_duration($text)
    {
        $len = function_exists('mb_strlen') ? mb_strlen((string)$text, 'UTF-8') : strlen((string)$text);
        return (int)min(60, 10 + ceil($len / 15));
    }

    function pipup_colors($kind = 'text')
    {
        if ($kind === 'media') {
            return array('title' => '#8ab4f8', 'message' => '#e8eaed', 'background' => '#202124');
        }
        return array('title' => '#0066cc', 'message' => '#000000', 'background' => '#ffffff');
    }

    function pipup_stack()
    {
        return 'vertical';
    }

    function pipup_log($message, $always = false)
    {
        if (function_exists('DebMes')) {
            DebMes('PiPup: ' . $message, 'terminals');
        }
    }
}

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
        return $this->sendCommand($phrase, pipup_t('PIPUP_TITLE_NOTIFICATION', 'Уведомление'), $this->messageId($phrase), $this->deviceVoice());
    }

    function ask($phrase, $level = 0)
    {
        return $this->sendCommand($phrase, pipup_t('PIPUP_TITLE_QUESTION', 'Вопрос'), $this->messageId($phrase), $this->deviceVoice());
    }

    /**
     * Окно с текстом, который произносит синтезатор речи самого устройства (поле tts PiPup).
     * Для вызова из сценариев, независимо от настройки терминала.
     */
    function sayDevice($phrase, $title = '', $duration = 0)
    {
        if ($title === '') {
            $title = pipup_t('PIPUP_TITLE_NOTIFICATION', 'Уведомление');
        }
        return $this->sendCommand($phrase, $title, $this->messageId($phrase), true, $duration);
    }

    /**
     * Длительность окна по длине текста: 10 с + 1 с на каждые 15 символов, не больше 60 с.
     */
    public function autoDuration($text)
    {
        return pipup_auto_duration($text);
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
        // device — говорить синтезатором самого устройства, файлы генераторов не использовать.
        // Пусто — принимать файлы любого генератора.
        if ($this->deviceVoice()) {
            // Терминал говорит голосом устройства — файлы генераторов не нужны
            return 0;
        }
        if (!$this->engineAllowed($cached_file)) {
            DebMes('PiPup: skip ' . basename((string)$cached_file) . ' (terminal voice: ' . $this->terminal['PLAYER_USERNAME'] . ')', 'terminals');
            return 0;
        }
        $soundUrl = $this->fileUrl($cached_file);
        if ($soundUrl === false) {
            DebMes("PiPup: cannot build URL for cached file '$cached_file'", 'terminals');
            return 0;
        }
        // Окно висит не меньше, чем нужно на чтение текста, и не меньше длины звука + 2 с
        $duration = $this->autoDuration($phrase);
        $local = $this->localPath($cached_file);
        if ($local !== false && function_exists('getMediaDurationSeconds')) {
            $duration = max($duration, (int)getMediaDurationSeconds($local) + 2);
        }
        return $this->sendSound($soundUrl, $phrase, pipup_t('PIPUP_TITLE_SOUND', '🔊 Уведомление'), $duration, null, $this->messageId($phrase));
    }

    // Голос устройства: в поле «Имя пользователя» указано device
    private function deviceVoice()
    {
        $filter = isset($this->terminal['PLAYER_USERNAME']) ? strtolower((string)$this->terminal['PLAYER_USERNAME']) : '';
        foreach (explode(',', $filter) as $engine) {
            if (trim($engine) === 'device') {
                return true;
            }
        }
        return false;
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

    function sendCommand($phrase, $title = '', $id = '', $deviceTts = false, $duration = 0, $options = array())
    {
        if (empty($this->terminal['HOST'])) {
            return 0;
        }
        if (!function_exists('curl_init')) {
            DebMes('PiPup: PHP extension curl is not available', 'terminals');
            return 0;
        }

        $host = $this->hostPort();
        $url = 'http://' . $host . '/notify';
        if ($title === '') {
            $title = pipup_t('PIPUP_TITLE_NOTIFICATION', 'Уведомление');
        }
        $colors = pipup_colors('text');

        pipup_log("Sending notification '$phrase' to " . $host);

        $data = array(
            'duration' => ((int)$duration > 0) ? (int)$duration : $this->autoDuration($phrase),
            'position' => (int)pipup_cfg('POSITION', 0),
            'title' => $title,
            'titleColor' => $colors['title'],
            'titleSize' => 20,
            'message' => (string)$phrase,
            'messageColor' => $colors['message'],
            'messageSize' => 14,
            'backgroundColor' => $colors['background'],
        );

        // Форк PiPup (mhoogenbosch): уведомления без id занимают один слот и заменяют друг друга.
        // Уникальный id — отдельное окно, stack — выстраивать окна в столбик, а не друг на друга.
        $forkFields = array(
            'id' => ($id != '') ? (string)$id : 'mdm_' . str_replace('.', '_', uniqid('', true)),
            'stack' => pipup_stack(),
        );
        $extra = $this->extraFields($options);
        $forkFields = array_merge($forkFields, $extra['top']);
        if ($deviceTts) {
            // Произнести текст синтезатором устройства (только форк PiPup).
            // Язык — BCP-47: en-US, ru-RU или uk-UA (настройка модуля pipup_admin).
            $forkFields['tts'] = (string)$phrase;
            $forkFields['ttsLanguage'] = pipup_tts_language();
        }

        $httpCode = $this->post($url, array_merge($data, $forkFields), $error);
        if ($httpCode === 400 && pipup_cfg('FALLBACK_400', 1)) {
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
     * $duration = 0 — взять значение DURATION_IMAGE из настроек модуля.
     */
    function sendImage($image, $text = '', $title = '', $width = 480, $duration = 0, $options = array())
    {
        if (empty($this->terminal['HOST']) || $image == '') {
            return 0;
        }
        if (!function_exists('curl_init')) {
            DebMes('PiPup: PHP extension curl is not available', 'terminals');
            return 0;
        }
        $host = $this->hostPort();
        $url = 'http://' . $host . '/notify';
        if ($title === '') {
            $title = pipup_t('PIPUP_TITLE_IMAGE', '🖼');
        }
        $colors = pipup_colors('text');

        $data = array(
            'duration' => max(1, (int)$duration > 0 ? (int)$duration : (int)pipup_cfg('DURATION_IMAGE', 30)),
            'position' => (int)pipup_cfg('POSITION', 0),
            'title' => $title,
            'titleColor' => $colors['title'],
            'titleSize' => 20,
            'message' => (string)$text,
            'messageColor' => $colors['message'],
            'messageSize' => 14,
            'backgroundColor' => $colors['background'],
        );
        $forkFields = array(
            'id' => 'mdm_' . str_replace('.', '_', uniqid('', true)),
            'stack' => pipup_stack(),
        );
        $extra = $this->extraFields($options);
        $forkFields = array_merge($forkFields, $extra['top']);

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
        if ($httpCode === 400 && pipup_cfg('FALLBACK_400', 1)) {
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
     *           duration (0 — пока не вызовут cancel(); по умолчанию DURATION_VIDEO), id (тот же id — обновить окно на месте),
     *           liveBadge (true — плашка «Live» поверх видео), liveBadgeColor (#RRGGBB),
     *           liveBadgePosition (top_left|top_right|bottom_left|bottom_right)
     * Поля poster/whep/height/liveBadge* понимает только форк PiPup.
     */
    function sendVideo($uri, $text = '', $title = '', $type = 'video', $options = array())
    {
        if (!in_array($type, array('video', 'web', 'whep', 'grid'), true) || ($type !== 'grid' && $uri == '')) {
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

        if ($type === 'grid') {
            // «Камеральная стена»: до девяти WHEP-потоков в одном окне (форк ≥ 0.29).
            $items = isset($options['items']) && is_array($options['items']) ? array_values($options['items']) : array();
            if (!$items) {
                DebMes('PiPup: grid requires items', 'terminals');
                return 0;
            }
            $media = array('items' => array_slice($items, 0, 9));
            foreach (array('columns', 'width', 'height', 'gap', 'labels', 'muted', 'fullscreen') as $key) {
                if (array_key_exists($key, $options)) {
                    $media[$key] = $options[$key];
                }
            }
        } else {
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
        }

        $colors = pipup_colors('media');
        $data = array(
            'duration' => isset($options['duration']) ? (int)$options['duration'] : (int)pipup_cfg('DURATION_VIDEO', 60),
            'position' => (int)pipup_cfg('POSITION', 0),
            'title' => ($title === '') ? pipup_t('PIPUP_TITLE_CAMERA', 'Камера') : $title,
            'titleColor' => $colors['title'],
            'titleSize' => 20,
            'message' => (string)$text,
            'messageColor' => $colors['message'],
            'messageSize' => 14,
            // тёмный фон: до первого кадра окно видео не белое, рамка не спорит с картинкой
            'backgroundColor' => $colors['background'],
            'media' => array($type => $media),
        );
        $forkFields = array(
            'id' => !empty($options['id']) ? (string)$options['id'] : 'mdm_' . str_replace('.', '_', uniqid('', true)),
            'stack' => pipup_stack(),
        );
        $extra = $this->extraFields($options);
        $forkFields = array_merge($forkFields, $extra['top']);
        $forkData = $data;
        foreach ($extra['media'] as $mediaKey => $mediaValue) {
            $forkData['media'][$type][$mediaKey] = $mediaValue;
        }
        // Плашка «Live» поверх видео (только форк), появляется с первым кадром
        if (!empty($options['liveBadge'])) {
            $forkFields['liveBadge'] = true;
            if (!empty($options['liveBadgeColor'])) {
                $forkFields['liveBadgeColor'] = (string)$options['liveBadgeColor'];
            }
            if (!empty($options['liveBadgePosition'])
                && in_array($options['liveBadgePosition'], array('top_left', 'top_right', 'bottom_left', 'bottom_right'), true)) {
                $forkFields['liveBadgePosition'] = $options['liveBadgePosition'];
            }
        }

        pipup_log("Sending $type '$uri' to " . $url);
        $httpCode = $this->post($url, array_merge($forkData, $forkFields), $error);
        if ($httpCode === 400 && pipup_cfg('FALLBACK_400', 1)) {
            // Оригинальный PiPup не понимает поля форка — отправляем базовый media-блок.
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
     * Используется плеером app_player «pipup_play». $volume — 0..1 или null.
     * $duration = 0 — взять значение DURATION_SOUND из настроек модуля.
     */
    function sendSound($soundUrl, $text = '', $title = '', $duration = 0, $volume = null, $id = '', $options = array())
    {
        $url = $this->notifyUrl('/notify');
        if ($url === false || $soundUrl == '') {
            return 0;
        }
        // «default» — встроенный звук PiPup (не ссылка на файл).
        if (strtolower((string)$soundUrl) === 'default') {
            $soundUrl = 'default';
        }
        $colors = pipup_colors('text');
        $data = array(
            'duration' => max(3, (int)$duration > 0 ? (int)$duration : (int)pipup_cfg('DURATION_SOUND', 15)),
            'position' => (int)pipup_cfg('POSITION', 0),
            'title' => ($title === '') ? "\xF0\x9F\x94\x8A" : $title,
            'titleColor' => $colors['title'],
            'titleSize' => 20,
            'message' => (string)$text,
            'messageColor' => $colors['message'],
            'messageSize' => 14,
            'backgroundColor' => $colors['background'],
            'sound' => $soundUrl,
        );
        if ($volume !== null) {
            $data['soundVolume'] = max(0, min(1, (float)$volume));
        }
        // По умолчанию id новый: при том же id и том же содержимом PiPup звук не проигрывает
        $forkFields = array(
            'id' => ($id != '') ? (string)$id : 'mdm_' . str_replace('.', '_', uniqid('', true)),
            'stack' => pipup_stack(),
        );
        $extra = $this->extraFields($options);
        $forkFields = array_merge($forkFields, $extra['top']);

        pipup_log("Sending sound '$soundUrl' to " . $url);
        $httpCode = $this->post($url, array_merge($data, $forkFields), $error);
        if ($httpCode === 400 && pipup_cfg('FALLBACK_400', 1)) {
            $httpCode = $this->post($url, $data, $error);
        }
        if ($httpCode !== 200) {
            DebMes("PiPup: sound to $url failed (HTTP $httpCode) $error", 'terminals');
            return 0;
        }
        return 1;
    }

    /**
     * Картинка, которую обычный загрузчик PiPup не показывает (SVG, в т.ч. анимированный),
     * через встроенный браузер PiPup. Картинка вписывается в квадрат $size x $size px.
     * $image — локальный путь внутри веб-корня или ссылка http(s).
     */
    function sendWebImage($image, $text = '', $title = '', $size = 220, $duration = 0, $id = '', $extraOptions = array())
    {
        $imgUrl = $this->fileUrl($image);
        if ($imgUrl === false) {
            DebMes("PiPup: image not found: $image", 'terminals');
            return 0;
        }
        $page = $this->viewerPage();
        if ($page === false) {
            return 0;
        }
        // Своя картинка сервера — относительным путём (тот же адрес, что у страницы)
        $src = ($this->localPath($image) !== false) ? parse_url($imgUrl, PHP_URL_PATH) : $imgUrl;
        $options = is_array($extraOptions) ? $extraOptions : array();
        $options['width'] = (int)$size;
        $options['height'] = (int)$size;
        $options['duration'] = ((int)$duration > 0) ? (int)$duration : $this->autoDuration($text);
        if ($id != '') {
            $options['id'] = $id;
        }
        return $this->sendVideo($page . '?src=' . rawurlencode($src), $text, ($title === '') ? pipup_t('PIPUP_TITLE_NOTIFICATION', 'Уведомление') : $title, 'web', $options);
    }

    // Страница-обёртка для sendWebImage(): без отступов, картинка вписана в окно
    private function viewerPage()
    {
        $file = ROOT . 'cms/cached/pipup_view.html';
        $html = '<!doctype html><html><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<style>html,body{margin:0;padding:0;width:100%;height:100%;overflow:hidden;background:#fff}'
            . 'img{display:block;width:100%;height:100%;object-fit:contain}</style></head>'
            . '<body><img id="i" alt=""><script>'
            . 'var s=new URLSearchParams(location.search).get("src")||"";'
            . 'if(/^(\/|https?:\/\/)/.test(s))document.getElementById("i").src=s;'
            . '</script></body></html>';
        if (!is_file($file) || file_get_contents($file) !== $html) {
            if (@file_put_contents($file, $html) === false) {
                DebMes("PiPup: cannot write $file", 'terminals');
                return false;
            }
        }
        return $this->fileUrl($file);
    }

    /**
     * Закрыть все окна PiPup на устройстве (форк >= 0.24; оригинал закрывает текущее окно).
     */
    function cancelAll()
    {
        return $this->cancelRequest('/cancel?all=true');
    }

    /**
     * Закрыть окно сообщения, показанного say()/sayTo()/ask()/sayDevice(), по его тексту.
     */
    function cancelMessage($phrase)
    {
        return $this->cancel($this->messageId($phrase));
    }

    /**
     * Закрыть окно: с $id — только окно с этим id (форк), без $id — окно, отправленное без id.
     */
    function cancel($id = '')
    {
        return $this->cancelRequest('/cancel' . ($id != '' ? '?id=' . rawurlencode($id) : ''));
    }

    private function cancelRequest($path)
    {
        $url = $this->notifyUrl($path);
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
        if ($input === '' || preg_match('/^[a-z][a-z0-9+.\-]*:\/\//i', $input)) {
            return false; // любая ссылка (http, rtsp, ...) — не локальный файл
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

    // ip:порт PiPup. «Адрес» терминала — только IP или имя устройства (порт, если вписан, отбрасывается),
    // порт PiPup — поле «Порт» плеера, пусто — 7979.
    // Старая регулярка /:\d*$/ ломала IPv6 (fe80::1 → fe80:), поэтому разбор явный.
    private function hostPort()
    {
        $host = trim((string)$this->terminal['HOST']);
        if (preg_match('/^\[([^\]]+)\](?::\d+)?$/', $host, $m)) {
            $host = '[' . $m[1] . ']'; // [fe80::1]:7979
        } elseif (preg_match('/^([^:]+):\d+$/', $host, $m)) {
            $host = $m[1]; // 192.168.1.50:7979 или tv.local:7979
        }
        $port = isset($this->terminal['PLAYER_PORT']) ? (int)$this->terminal['PLAYER_PORT'] : 0;
        return $host . ':' . (($port > 0 && $port < 65536) ? $port : self::DEFAULT_PORT);
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
        $host = $this->hostPort();
        return 'http://' . $host . $path;
    }

    // -------------------------------------------------- расширенный API PiPup

    /** Дополнительные поля /notify: из настроек модуля и из аргумента вызова. */
    private function extraFields($options)
    {
        $extra = function_exists('pipup_extra_notify_fields')
            ? pipup_extra_notify_fields()
            : array('top' => array(), 'media' => array());
        if (!is_array($options)) {
            $options = array();
        }
        $topKeys = array('urgency', 'borderColor', 'borderWidth', 'cornerRadius',
            'icon', 'iconPosition', 'iconWidth', 'showProgress', 'buttons', 'callback',
            'buttonSize', 'animation', 'opacity', 'padding', 'bringToFront',
            'dismissScreensaver', 'fullscreen', 'mediaFullBleed', 'buttonsInline',
            'soundVolume', 'tts', 'ttsLanguage');
        $mediaKeys = array('transparent', 'posterMode', 'softwareDecoder', 'bufferMs');
        foreach ($topKeys as $key) {
            if (array_key_exists($key, $options)) {
                $extra['top'][$key] = $options[$key];
            }
        }
        foreach ($mediaKeys as $key) {
            if (array_key_exists($key, $options)) {
                $extra['media'][$key] = $options[$key];
            }
        }
        if (!empty($options['fields']) && is_array($options['fields'])) {
            $extra['top'] = array_merge($extra['top'], $options['fields']);
        }
        return $extra;
    }

    /** Базовые поля /notify, понятные также оригинальному PiPup. */
    private function basicNotifyData($data)
    {
        $basic = array('duration', 'position', 'title', 'titleColor', 'titleSize',
            'message', 'messageColor', 'messageSize', 'backgroundColor', 'media',
            'image', 'imageWidth');
        $out = array();
        foreach ($basic as $key) {
            if (array_key_exists($key, $data)) {
                $out[$key] = $data[$key];
            }
        }
        if (isset($out['media']) && is_array($out['media'])) {
            foreach ($out['media'] as $type => $media) {
                $clean = array();
                foreach (array('uri', 'width', 'height', 'muted') as $key) {
                    if (isset($media[$key])) {
                        $clean[$key] = $media[$key];
                    }
                }
                $out['media'][$type] = $clean;
            }
        }
        return $out;
    }

    /**
     * Универсальная отправка /notify с произвольными полями API PiPup.
     *
     * $payload — ассоциативный массив полей /notify (в том числе media).
     * $options — дополнительные поля и переопределения (см. extraFields).
     */
    function notify($payload, $options = array())
    {
        $url = $this->notifyUrl('/notify');
        if ($url === false || !is_array($payload)) {
            return 0;
        }
        $basicKeys = array('duration', 'position', 'title', 'titleColor', 'titleSize',
            'message', 'messageColor', 'messageSize', 'backgroundColor', 'media',
            'image', 'imageWidth');
        $data = array();
        foreach ($basicKeys as $key) {
            if (array_key_exists($key, $payload)) {
                $data[$key] = $payload[$key];
            }
        }
        if (!isset($data['duration'])) {
            $data['duration'] = $this->autoDuration(isset($payload['message']) ? $payload['message'] : '');
        }
        $fork = array();
        foreach ($payload as $key => $value) {
            if (!in_array($key, $basicKeys, true)) {
                $fork[$key] = $value;
            }
        }
        $extra = $this->extraFields($options);
        $fork = array_merge($fork, $extra['top']);
        if (!isset($fork['id'])) {
            $fork['id'] = 'mdm_' . str_replace('.', '_', uniqid('', true));
        }
        if (!isset($fork['stack'])) {
            $fork['stack'] = pipup_stack();
        }
        $forkData = $data;
        if (!empty($extra['media']) && isset($forkData['media']) && is_array($forkData['media'])) {
            foreach ($forkData['media'] as $type => $media) {
                foreach ($extra['media'] as $key => $value) {
                    $forkData['media'][$type][$key] = $value;
                }
            }
        }
        $httpCode = $this->post($url, array_merge($forkData, $fork), $error);
        if ($httpCode === 400 && pipup_cfg('FALLBACK_400', 1)) {
            $httpCode = $this->post($url, $this->basicNotifyData($data), $error);
        }
        if ($httpCode !== 200) {
            DebMes("PiPup: notify to $url failed (HTTP $httpCode) $error", 'terminals');
            return 0;
        }
        return 1;
    }

    /** Произвольный запрос к API PiPup; $body получает текст ответа. */
    private function request($path, $params = array(), $method = 'POST', &$body = null)
    {
        $url = $this->notifyUrl($path);
        if ($url === false) {
            return 0;
        }
        if (!empty($params)) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($params);
        }
        $ch = curl_init($url);
        if (strtoupper($method) === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, '');
        }
        $httpCode = $this->doRequest($ch, $error, 4, $body);
        if ($httpCode !== 200) {
            pipup_log('request ' . $path . ' failed (HTTP ' . $httpCode . ') ' . $error, true);
        }
        return $httpCode;
    }

    /** GET /state — состояние телевизора и список окон. */
    function state()
    {
        $body = '';
        if ($this->request('/state', array(), 'GET', $body) !== 200) {
            return false;
        }
        $data = json_decode($body, true);
        return is_array($data) ? $data : false;
    }

    /** POST /power?state=on|off|toggle — включить/выключить экран. */
    function power($state)
    {
        $state = strtolower((string)$state);
        if (!in_array($state, array('on', 'off', 'toggle'), true)) {
            DebMes('PiPup: bad power state', 'terminals');
            return 0;
        }
        return $this->request('/power', array('state' => $state)) === 200 ? 1 : 0;
    }

    /** POST /key?key=... — медиа-клавиши и навигация. */
    function key($key, $package = '')
    {
        $allowed = array('play_pause', 'play', 'pause', 'stop', 'next', 'previous',
            'rewind', 'fast_forward', 'home', 'back');
        $key = strtolower((string)$key);
        if (!in_array($key, $allowed, true)) {
            DebMes('PiPup: bad key ' . $key, 'terminals');
            return 0;
        }
        $params = array('key' => $key);
        if ($package !== '') {
            $params['package'] = $package;
        }
        return $this->request('/key', $params) === 200 ? 1 : 0;
    }

    /** POST /volume — level, step или mute; $params['ui'] = false скрывает шкалу. */
    function volume($params = array())
    {
        if (!is_array($params)) {
            return 0;
        }
        $out = array();
        $count = 0;
        if (isset($params['level'])) {
            $out['level'] = max(0, min(100, (int)$params['level']));
            $count++;
        }
        if (isset($params['step'])) {
            $step = strtolower((string)$params['step']);
            if (!in_array($step, array('up', 'down'), true)) {
                return 0;
            }
            $out['step'] = $step;
            $count++;
        }
        if (isset($params['mute'])) {
            $mute = strtolower((string)$params['mute']);
            if (!in_array($mute, array('on', 'off', 'toggle'), true)) {
                return 0;
            }
            $out['mute'] = $mute;
            $count++;
        }
        if ($count !== 1) {
            DebMes('PiPup: volume requires exactly one of level/step/mute', 'terminals');
            return 0;
        }
        if (array_key_exists('ui', $params)) {
            $out['ui'] = $params['ui'] ? 'true' : 'false';
        }
        return $this->request('/volume', $out) === 200 ? 1 : 0;
    }

    /** POST /launch — запустить приложение или открыть deep link. */
    function launch($package = '', $uri = '', $options = array())
    {
        $params = array();
        if ($package !== '') {
            $params['package'] = $package;
        }
        if ($uri !== '') {
            $params['uri'] = $uri;
        }
        if (!$params) {
            return 0;
        }
        if (!is_array($options)) {
            $options = array();
        }
        foreach (array('wake', 'click', 'method', 'wait') as $key) {
            if (array_key_exists($key, $options)) {
                $params[$key] = $options[$key];
            }
        }
        return $this->request('/launch', $params) === 200 ? 1 : 0;
    }

    /** GET /apps — список установленных приложений. */
    function apps()
    {
        $body = '';
        if ($this->request('/apps', array(), 'GET', $body) !== 200) {
            return false;
        }
        $data = json_decode($body, true);
        return is_array($data) ? $data : false;
    }

    /** POST /settings?webhook=... — push-уведомления о состоянии PiPup. */
    function settingsWebhook($url)
    {
        if ($url === '') {
            return 0;
        }
        return $this->request('/settings', array('webhook' => $url)) === 200 ? 1 : 0;
    }

    /** GET /media — что играет в любом приложении с медиа-сессией. */
    function mediaState()
    {
        $body = '';
        if ($this->request('/media', array(), 'GET', $body) !== 200) {
            return false;
        }
        $data = json_decode($body, true);
        return is_array($data) ? $data : false;
    }

    /** POST /media/seek?ms=... или ?by=... — перемотка. */
    function mediaSeek($ms = null, $by = null, $package = '')
    {
        $params = array();
        if ($ms !== null) {
            $params['ms'] = (int)$ms;
        } elseif ($by !== null) {
            $params['by'] = (int)$by;
        } else {
            return 0;
        }
        if ($package !== '') {
            $params['package'] = $package;
        }
        return $this->request('/media/seek', $params) === 200 ? 1 : 0;
    }

    /** POST /click — нажать кнопку в разрешённом приложении (форк ≥ 1.0). */
    function click($package, $id, $options = array())
    {
        if ($package === '' || $id === '') {
            return 0;
        }
        $params = array('package' => $package, 'id' => $id);
        if (is_array($options)) {
            foreach (array('wait', 'method') as $key) {
                if (array_key_exists($key, $options)) {
                    $params[$key] = $options[$key];
                }
            }
        }
        return $this->request('/click', $params) === 200 ? 1 : 0;
    }

    // POST JSON или multipart, возвращает HTTP-код (0 — нет соединения)
    private function post($url, $data, &$error, $multipart = false)
    {
        $error = '';
        if ($multipart) {
            // multipart: все поля, кроме файла, передаются строками; массивы (buttons) — JSON
            foreach ($data as $k => $v) {
                if (is_object($v)) {
                    continue;
                }
                if (is_array($v)) {
                    $data[$k] = json_encode($v, JSON_UNESCAPED_UNICODE);
                } else {
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

    private function doRequest($ch, &$error, $timeout, &$body = null)
    {
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, (int)$timeout);

        $result = curl_exec($ch);
        if ($body !== null) {
            $body = $result;
        }
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
