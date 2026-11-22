<?php

/*
	Addon PiPup (pipup_play) for app_player
	Показывает медиа во всплывающем окне PiPup на Android-устройстве
	(форк mhoogenbosch/PiPup, поле sound). Отправку выполняет дополнение
	терминалов modules/terminals/tts/pipup.addon.php.
  Язык интерфейса и длительности берутся из modules/pipup/pipup.lib.php.
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

class pipup_play extends app_player_addon
{

    // Constructor
    function __construct($terminal)
    {
        $this->title = pipup_t('PIPUP_PLAYER_TITLE', 'PiPup (всплывающее окно)');
        // Коротко — сразу, остальное свёрнуто под «Подробнее»
        $this->description = pipup_t('PIPUP_PLAYER_DESC_SHORT', 'Показывает медиа во всплывающем окне PiPup.')
            // отступы: после «Подробнее» — побольше; у строки «Поддерживаемые функции», которую дописывает ядро, снизу — поменьше
            . '<style>#pipup_play_description > p:last-child{margin-bottom:2px}</style>'
            . '<details style="margin:4px 0 10px"><summary style="cursor:pointer;display:list-item">' . pipup_t('PIPUP_PLAYER_DETAILS', 'Подробнее') . '</summary>'
            . pipup_t('PIPUP_PLAYER_DESC_TYPES', '')
            . ' ' . pipup_t('PIPUP_PLAYER_DESC_FILES', '')
            . ' ' . pipup_t('PIPUP_PLAYER_DESC_STOP', '')
            . '<br>' . pipup_t('PIPUP_PLAYER_DESC_REQ', '')
            . '<br>' . pipup_t('PIPUP_PLAYER_DESC_VOICE', '')
            . '<ul style="margin:4px 0 4px 18px;padding:0">'
            . '<li>' . pipup_t('PIPUP_PLAYER_VOICE_EMPTY', '') . '</li>'
            . '<li>' . pipup_t('PIPUP_PLAYER_VOICE_TAGS', '') . '</li>'
            . '<li>' . pipup_t('PIPUP_PLAYER_VOICE_DEVICE', '') . '</li>'
            . '</ul>'
            . pipup_t('PIPUP_PLAYER_DESC_TIME', '') . ' '
            . pipup_t('PIPUP_PLAYER_DESC_HINT', '')
            . '</details>'
            // Страница терминала создаёт плеер без терминала (NULL): только тогда нужен скрипт формы и список голосов
            . (($terminal === null) ? self::formScript(self::voiceLabels()) : '');
        parent::__construct($terminal);
    }

    // Генераторы речи, чьи файлы есть в кеше cms/cached/voice (и его подпапках): метка → число файлов.
    // Метка — то, что остаётся от имени файла без md5: префикс (sanotts_<md5>) или суффикс (<md5>_yandex).
    private static function voiceLabels()
    {
        $names = array(
            'sanotts' => pipup_t('PIPUP_ENGINE_SANOTTS', 'sanoTTS'),
            'piper_tts' => pipup_t('PIPUP_ENGINE_PIPER_TTS', 'Piper'),
            'sapi' => pipup_t('PIPUP_ENGINE_SAPI', 'Windows SAPI'),
            'rh' => pipup_t('PIPUP_ENGINE_RH', 'RHVoice'),
            'yandex' => pipup_t('PIPUP_ENGINE_YANDEX', 'Яндекс'),
            'google' => pipup_t('PIPUP_ENGINE_GOOGLE', 'Google'),
            'ivona' => pipup_t('PIPUP_ENGINE_IVONA', 'Ivona'),
            'tilde' => pipup_t('PIPUP_ENGINE_TILDE', 'Tilde'),
        );
        // Служебные файлы генераторов с другой меткой: piper_dict_<md5> — прослушивание фраз
        // в словаре piper_tts (кнопка ▶). Это тот же голос, считаем их вместе с piper_tts.
        $aliases = array('piper_dict' => 'piper_tts');
        $dir = ROOT . 'cms/cached/voice';
        $counts = array();
        $left = 20000; // не читать бесконечно большой кеш
        $stack = array($dir);
        while ($stack && $left > 0) {
            $d = array_pop($stack);
            $list = @scandir($d);
            if (!is_array($list)) {
                continue;
            }
            foreach ($list as $f) {
                if ($f == '.' || $f == '..' || --$left <= 0) {
                    continue;
                }
                $path = $d . DIRECTORY_SEPARATOR . $f;
                if (is_dir($path)) {
                    if ($d == $dir) {
                        $stack[] = $path; // подпапки первого уровня
                    }
                    continue;
                }
                $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
                if (!in_array($ext, array('mp3', 'wav', 'ogg', 'opus', 'm4a', 'aac', 'flac'), true)) {
                    continue;
                }
                $name = strtolower(pathinfo($f, PATHINFO_FILENAME));
                if (!preg_match('/^(?:([a-z0-9_]+?)_)?[0-9a-f]{32}(?:_([a-z0-9_]+))?$/', $name, $m)) {
                    continue;
                }
                $label = !empty($m[1]) ? $m[1] : (isset($m[2]) ? $m[2] : '');
                if (isset($aliases[$label])) {
                    $label = $aliases[$label];
                }
                if ($label !== '') {
                    $counts[$label] = isset($counts[$label]) ? $counts[$label] + 1 : 1;
                }
            }
        }
        arsort($counts);
        $out = array();
        foreach ($counts as $label => $count) {
            $out[] = array('label' => $label, 'title' => isset($names[$label]) ? $names[$label] . ' (' . $label . ')' : $label, 'count' => $count);
        }
        return $out;
    }

    // Поля карточки терминала, пока выбран этот плеер: «Имя пользователя» подписано «Голос сообщений»,
    // «Порт» — «Порт PiPup», у обоих подсказки; ненужное поле «Пароль» скрыто. Описание выводится на странице как HTML,
    // поэтому скрипт работает без правки шаблона ядра; при выборе другого плеера всё возвращается.
    private static function formScript($voices)
    {
        $flags = defined('JSON_UNESCAPED_UNICODE') ? JSON_UNESCAPED_UNICODE : 0;
        $i18n = array(
            'hint' => pipup_t('PIPUP_HINT_VOICE', 'пусто — любой голос; device — голос устройства'),
            'caption' => pipup_t('PIPUP_FIELD_VOICE', 'Голос сообщений:'),
            'portCaption' => pipup_t('PIPUP_FIELD_PORT', 'Порт PiPup:'),
            'portHint' => pipup_t('PIPUP_HINT_PORT', 'пусто — 7979'),
            'pick' => pipup_t('PIPUP_VOICE_PICK', 'Выбрать из списка'),
            'any' => pipup_t('PIPUP_VOICE_ANY', 'Любой голос'),
            'device' => pipup_t('PIPUP_VOICE_DEVICE', 'Голос устройства (device)'),
            'cache' => pipup_t('PIPUP_VOICE_CACHE', '%s — %d в кеше'),
        );
        return '<script>(function () {'
            . 'var voices = ' . json_encode($voices, $flags) . ';'
            . 'var t = ' . json_encode($i18n, $flags) . ';'
            . 'function apply() {'
            . 'var s = document.querySelector("select[name=player_type]"), u = document.querySelector("input[name=player_username]");'
            . 'if (!s || !u) return;'
            . 'var on = s.value == "pipup_play";'
            . 'var pw = document.getElementById("player_password"), p = document.querySelector("input[name=player_port]");'
            . 'relabel("#player_name label", on ? t.caption : null);'
            . 'relabel("#player_port label", on ? t.portCaption : null);'
            . 'tip(u, t.hint, on); if (p) tip(p, t.portHint, on);'
            . 'if (on && pw) pw.style.display = "none";'
            . 'voiceSelect(u, on);'
            . '}'
            // стрелка выпадающего списка прямо в поле: выбор пишет метку в поле, свой список через запятую вводится руками
            . 'function voiceSelect(u, on) {'
            . 'var arrow = document.getElementById("pipup_voice_arrow"), menu = document.getElementById("pipup_voice_menu");'
            . 'if (!arrow) {'
            . 'var wrap = document.createElement("div"); wrap.style.position = "relative";'
            . 'u.parentNode.insertBefore(wrap, u); wrap.appendChild(u);'
            . 'arrow = document.createElement("span"); arrow.id = "pipup_voice_arrow"; arrow.title = t.pick;'
            . 'arrow.innerHTML = "<svg width=\\"14\\" height=\\"14\\" viewBox=\\"0 0 14 14\\" style=\\"display:block\\"><path d=\\"M3 5l4 4 4-4\\" fill=\\"none\\" stroke=\\"currentColor\\" stroke-width=\\"2\\" stroke-linecap=\\"round\\" stroke-linejoin=\\"round\\"/></svg>";'
            . 'arrow.style.cssText = "position:absolute;top:0;bottom:0;right:1px;width:34px;display:flex;align-items:center;justify-content:center;cursor:pointer;opacity:.7";'
            . 'menu = document.createElement("ul"); menu.id = "pipup_voice_menu"; menu.className = "dropdown-menu";'
            . 'menu.style.cssText = "display:none;width:100%;max-height:280px;overflow:auto";'
            . 'wrap.appendChild(arrow); wrap.appendChild(menu);'
            . 'arrow.addEventListener("mousedown", function (e) { e.preventDefault(); });'
            . 'arrow.addEventListener("click", function () { if (menu.style.display == "none") { fill(menu, u); menu.style.display = "block"; } else menu.style.display = "none"; });'
            . 'document.addEventListener("click", function (e) { if (!wrap.contains(e.target)) menu.style.display = "none"; });'
            . 'u.addEventListener("keydown", function (e) { if (e.key == "Escape") menu.style.display = "none"; if (e.key == "ArrowDown" && e.altKey) arrow.click(); });'
            . '}'
            . 'u.style.paddingRight = on ? "36px" : ""; arrow.style.display = on ? "flex" : "none"; if (!on) menu.style.display = "none";'
            . 'if (on && u.offsetHeight) { arrow.style.top = u.offsetTop + "px"; arrow.style.height = u.offsetHeight + "px"; arrow.style.bottom = "auto"; arrow.style.color = getComputedStyle(u).color; }'
            . '}'
            . 'function fill(menu, u) {'
            . 'menu.innerHTML = ""; var cur = u.value.trim().toLowerCase();'
            . 'function add(v, t) {'
            . 'var li = document.createElement("li"), a = document.createElement("a"); a.href = "#"; a.textContent = t;'
            . 'if (v == cur) { li.className = "active"; }'
            . 'a.addEventListener("click", function (e) { e.preventDefault(); u.value = v; menu.style.display = "none"; u.focus(); });'
            . 'li.appendChild(a); menu.appendChild(li);'
            . '}'
            . 'add("", t.any);'
            . 'for (var i = 0; i < voices.length; i++) add(voices[i].label, t.cache.replace("%s", voices[i].title).replace("%d", voices[i].count));'
            . 'add("device", t.device);'
            . '}'
            // подпись поля: своя для этого плеера, при выборе другого — исходная
            . 'function relabel(sel, text) {'
            . 'var l = document.querySelector(sel); if (!l) return;'
            . 'if (l.getAttribute("data-orig") === null) l.setAttribute("data-orig", l.textContent);'
            . 'l.textContent = (text !== null) ? text : l.getAttribute("data-orig");'
            . '}'
            // подсказка в поле: ставим для этого плеера, убираем только свою
            . 'function tip(el, text, on) {'
            . 'if (on) { el.placeholder = text; el.title = text; }'
            . 'else if (el.placeholder == text) { el.placeholder = ""; el.title = ""; }'
            . '}'
            // шаблон сам показывает/скрывает поля при загрузке и смене типа — применяем после него
            . 'function later() { apply(); setTimeout(apply, 0); setTimeout(apply, 300); }'
            . 'if (document.readyState == "loading") document.addEventListener("DOMContentLoaded", later); else later();'
            . 'window.addEventListener("load", apply);'
            . 'document.addEventListener("change", function (e) { if (e.target && e.target.name == "player_type") later(); });'
            . '})();</script>';
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

        $imageDuration = (int)pipup_cfg('DURATION_IMAGE', 30);
        switch ($type) {
            case 'image':
                // Локальный файл уходит файлом, ссылка — ссылкой
                $ok = $pipup->sendImage(($local !== false) ? $local : $url, '', pipup_t('PIPUP_TITLE_IMAGE', '🖼'), 480, $imageDuration);
                break;
            case 'svg':
                $ok = $pipup->sendWebImage(($local !== false) ? $local : $url, '', pipup_t('PIPUP_TITLE_IMAGE', '🖼'), 300, $imageDuration);
                break;
            case 'video':
            case 'whep':
            case 'web':
                $options = array('width' => 640, 'duration' => ($seconds > 0) ? $seconds + 2 : (int)pipup_cfg('DURATION_VIDEO', 60), 'muted' => false);
                if ($type == 'web') {
                    $options['height'] = 360;
                }
                // Постоянный id для адреса: повторный playMedia() того же потока продлевает
                // открытое окно (PiPup только перезапускает таймер), а не открывает новое
                $options['id'] = 'play_' . md5($url);
                $ok = $pipup->sendVideo($url, '', pipup_t('PIPUP_TITLE_VIDEO', '▶'), $type, $options);
                break;
            default: // audio
                $ok = $pipup->sendSound($url, '', '', ($seconds > 0) ? $seconds + 2 : (int)pipup_cfg('DURATION_SOUND', 15));
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
