<?php
/**
 * PiPup — общая библиотека дополнения.
 *
 * Подключается обоими addon-файлами (TTS и плеер) и модулем настроек
 * pipup_admin. Здесь собрано то, что должно работать и без установленного
 * модуля настроек: языки, конфигурация со значениями по умолчанию, разбор
 * голосовых команд и интеграция с модулем «Простые устройства» (devices).
 *
 * Файл не объявляет классы и не зависит от ядра на этапе include: все функции
 * MajorDoMo вызываются через function_exists(). Это позволяет использовать
 * библиотеку в тестах и в сценариях с минимальным окружением.
 *
 * @package pipup
 * @license MIT
 */

if (!defined('PIPUP_LIB_LOADED')) {

    define('PIPUP_LIB_LOADED', true);
    define('PIPUP_LIB_VERSION', '1.1.0');

    /** Значения конфигурации по умолчанию (используются и модулем настроек). */
    function pipup_defaults()
    {
        return array(
            // Язык текста окон и интерфейса: auto | en | ru | uk.
            'LANGUAGE'            => 'auto',
            // Язык синтезатора устройства: auto или произвольный BCP-47.
            'TTS_LANGUAGE'        => 'auto',
            // Метка генератора речи по умолчанию (для всех терминалов).
            'VOICE_FILTER'        => '',
            // Автодлительность текстовых окон.
            'DURATION_TEXT_BASE'  => 10,
            'DURATION_TEXT_CHARS' => 15,
            'DURATION_TEXT_MAX'   => 60,
            // Длительности остальных окон.
            'DURATION_SOUND'      => 15,
            'DURATION_IMAGE'      => 30,
            'DURATION_VIDEO'      => 60,
            // Раскладка и позиция окон.
            'STACK'               => 'vertical',
            'POSITION'            => 0,
            // Повтор запроса без полей форка при HTTP 400.
            'FALLBACK_400'        => 1,
            // Подробный лог в канал terminals.
            'LOG_DEBUG'           => 0,
            // Терминал по умолчанию для сценариев и голосовых команд.
            'TERMINAL'            => '',
            // Карта камер: JSON {"default": "...", "door": "..."}.
            'CAMERAS'             => '',
            'CAMERA_TYPE'         => 'auto',
            // Простое устройство.
            'DEVICE_OBJECT'       => 'PipupNotifier',
            'DEVICE_ON_ACTION'    => 'camera',
            // Цвета текстовых и медиа-окон.
            'COLOR_TITLE'         => '#0066cc',
            'COLOR_MESSAGE'       => '#000000',
            'COLOR_BACKGROUND'    => '#ffffff',
            'MEDIA_COLOR_TITLE'   => '#8ab4f8',
            'MEDIA_COLOR_MESSAGE' => '#e8eaed',
            'MEDIA_COLOR_BACKGROUND' => '#202124',

            // Дополнительные поля /notify форка PiPup ('' — не отправлять).
            'URGENCY'             => '',
            'BORDER_COLOR'        => '',
            'BORDER_WIDTH'        => 0,
            'CORNER_RADIUS'       => 0,
            'ICON'                => '',
            'ICON_POSITION'       => '',
            'ICON_WIDTH'          => 0,
            'SHOW_PROGRESS'       => 0,
            'BUTTONS'             => '',
            'CALLBACK'            => '',
            'BUTTON_SIZE'         => 0,
            'ANIMATION'           => '',
            'OPACITY'             => '',
            'PADDING'             => '',
            'BRING_TO_FRONT'      => 0,
            'DISMISS_SCREENSAVER' => '',
            'FULLSCREEN'          => 0,
            'MEDIA_FULL_BLEED'    => 0,
            'BUTTONS_INLINE'      => 0,
            // Поля внутри media (video/web/whep).
            'TRANSPARENT'         => '',
            'POSTER_MODE'         => '',
            'SOFTWARE_DECODER'    => '',
            'BUFFER_MS'           => 0,
        );
    }

    /**
     * Дополнительные поля /notify из настроек модуля.
     *
     * Возвращает array('top' => ..., 'media' => ...). Поля top уходят отдельно
     * от базовых, чтобы при HTTP 400 от оригинального PiPup можно было
     * повторить запрос без них.
     */
    function pipup_extra_notify_fields()
    {
        $top = array();
        $media = array();

        $map = array(
            'URGENCY' => 'urgency', 'BORDER_COLOR' => 'borderColor',
            'BORDER_WIDTH' => 'borderWidth', 'CORNER_RADIUS' => 'cornerRadius',
            'ICON' => 'icon', 'ICON_POSITION' => 'iconPosition', 'ICON_WIDTH' => 'iconWidth',
            'SHOW_PROGRESS' => 'showProgress', 'BUTTONS' => 'buttons', 'CALLBACK' => 'callback',
            'BUTTON_SIZE' => 'buttonSize', 'ANIMATION' => 'animation', 'OPACITY' => 'opacity',
            'PADDING' => 'padding', 'BRING_TO_FRONT' => 'bringToFront',
            'DISMISS_SCREENSAVER' => 'dismissScreensaver', 'FULLSCREEN' => 'fullscreen',
            'MEDIA_FULL_BLEED' => 'mediaFullBleed', 'BUTTONS_INLINE' => 'buttonsInline',
        );
        foreach ($map as $key => $field) {
            $value = pipup_cfg($key, '');
            if ($value === '' || $value === null) {
                continue;
            }
            if ($field === 'buttons') {
                $decoded = is_array($value) ? $value : json_decode((string)$value, true);
                if (!is_array($decoded)) {
                    continue;
                }
                $value = array_values($decoded);
            } elseif (in_array($field, array('borderWidth', 'cornerRadius', 'iconWidth',
                'buttonSize', 'padding'), true)) {
                $value = (int)$value;
                if ($field !== 'padding' && $value <= 0 && $field !== 'borderWidth') {
                    continue;
                }
                if ($field === 'borderWidth' && $value < 0) {
                    continue;
                }
            } elseif ($field === 'opacity') {
                $value = max(0, min(1, (float)$value));
            } elseif (in_array($field, array('showProgress', 'bringToFront', 'fullscreen',
                'mediaFullBleed', 'buttonsInline'), true)) {
                $value = (bool)$value;
            } elseif ($field === 'dismissScreensaver') {
                $value = (bool)(int)$value;
            }
            $top[$field] = $value;
        }

        $mediaMap = array(
            'TRANSPARENT' => 'transparent', 'POSTER_MODE' => 'posterMode',
            'SOFTWARE_DECODER' => 'softwareDecoder', 'BUFFER_MS' => 'bufferMs',
        );
        foreach ($mediaMap as $key => $field) {
            $value = pipup_cfg($key, '');
            if ($value === '' || $value === null) {
                continue;
            }
            if ($field === 'transparent' || $field === 'softwareDecoder') {
                $media[$field] = (bool)(int)$value;
            } elseif ($field === 'bufferMs') {
                $media[$field] = max(100, min(10000, (int)$value));
            } else {
                $media[$field] = $value;
            }
        }
        return array('top' => $top, 'media' => $media);
    }

    /** Язык интерфейса/окен: en, ru или uk. */
    function pipup_lang()
    {
        if (isset($GLOBALS['pipup_lang_cache'])) {
            return $GLOBALS['pipup_lang_cache'];
        }
        $configured = strtolower(trim((string)pipup_cfg('LANGUAGE', 'auto')));
        if (in_array($configured, array('en', 'ru', 'uk'), true)) {
            $GLOBALS['pipup_lang_cache'] = $configured;
            return $configured;
        }
        $site = defined('SETTINGS_SITE_LANGUAGE') ? strtolower((string)SETTINGS_SITE_LANGUAGE) : 'en';
        // В ядре MajorDoMo украинский язык исторически называется ua.
        if ($site === 'ua') {
            $site = 'uk';
        }
        $lang = in_array($site, array('en', 'ru', 'uk'), true) ? $site : 'en';
        $GLOBALS['pipup_lang_cache'] = $lang;
        return $lang;
    }

    /** Перевод по ключу. $fallback — значение на случай отсутствующего словаря. */
    function pipup_t($key, $fallback = '')
    {
        if (!isset($GLOBALS['pipup_dict_cache']) || !is_array($GLOBALS['pipup_dict_cache'])) {
            $dict = array();
            $lang = pipup_lang();
            $roots = array();
            if (defined('ROOT')) {
                $roots[] = rtrim(ROOT, '/\\') . '/languages';
            }
            // Локальная копия нужна при ручной установке только папки modules/.
            $roots[] = dirname(__FILE__) . '/languages';
            foreach ($roots as $root) {
                foreach (array('pipup_' . $lang . '.php', 'pipup_default.php') as $file) {
                    $path = $root . '/' . $file;
                    if (!is_file($path)) {
                        continue;
                    }
                    $PIPUP_STRINGS = array();
                    include $path;
                    if (is_array($PIPUP_STRINGS)) {
                        $dict = array_merge($dict, $PIPUP_STRINGS);
                    }
                    break 2;
                }
            }
            $GLOBALS['pipup_dict_cache'] = $dict;
        }
        $dict = $GLOBALS['pipup_dict_cache'];
        if (isset($dict[$key])) {
            return $dict[$key];
        }
        return $fallback !== '' ? $fallback : $key;
    }

    /** Сброс кэшей после сохранения настроек в админке. */
    function pipup_cache_reset()
    {
        unset($GLOBALS['pipup_dict_cache'], $GLOBALS['pipup_lang_cache'], $GLOBALS['pipup_cfg_cache']);
        return true;
    }

    /** Чтение конфигурации модуля pipup_admin со значениями по умолчанию. */
    function pipup_cfg($key, $default = null)
    {
        if (!isset($GLOBALS['pipup_cfg_cache']) || !is_array($GLOBALS['pipup_cfg_cache'])) {
            $cfg = array();
            if (function_exists('SQLSelectOne')) {
                $rec = SQLSelectOne("SELECT DATA FROM project_modules WHERE NAME='pipup_admin'");
                if (is_array($rec) && !empty($rec['DATA'])) {
                    $data = @unserialize($rec['DATA']);
                    if (is_array($data)) {
                        $cfg = $data;
                    }
                }
            }
            $GLOBALS['pipup_cfg_cache'] = $cfg;
        }
        $cfg = $GLOBALS['pipup_cfg_cache'];
        if (array_key_exists($key, $cfg)) {
            return $cfg[$key];
        }
        if ($default !== null) {
            return $default;
        }
        $defaults = pipup_defaults();
        return isset($defaults[$key]) ? $defaults[$key] : null;
    }

    /** Язык, который понимает синтезатор Android (BCP-47). */
    function pipup_tts_language()
    {
        $code = strtolower(trim((string)pipup_cfg('TTS_LANGUAGE', 'auto')));
        if ($code === 'auto' || $code === '') {
            $map = array('en' => 'en-US', 'ru' => 'ru-RU', 'uk' => 'uk-UA');
            $lang = pipup_lang();
            return isset($map[$lang]) ? $map[$lang] : 'en-US';
        }
        return $code;
    }

    /** Автодлительность текстового окна: base + 1 с на N символов, не больше max. */
    function pipup_auto_duration($text)
    {
        $text = (string)$text;
        $len = function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);
        $base = (int)pipup_cfg('DURATION_TEXT_BASE', 10);
        $chars = max(1, (int)pipup_cfg('DURATION_TEXT_CHARS', 15));
        $max = (int)pipup_cfg('DURATION_TEXT_MAX', 60);
        $value = $base + ceil($len / $chars);
        if ($max > 0) {
            $value = min($max, $value);
        }
        return (int)$value;
    }

    /** Цвета окна: text|media. */
    function pipup_colors($kind = 'text')
    {
        if ($kind === 'media') {
            return array(
                'title' => (string)pipup_cfg('MEDIA_COLOR_TITLE', '#8ab4f8'),
                'message' => (string)pipup_cfg('MEDIA_COLOR_MESSAGE', '#e8eaed'),
                'background' => (string)pipup_cfg('MEDIA_COLOR_BACKGROUND', '#202124'),
            );
        }
        return array(
            'title' => (string)pipup_cfg('COLOR_TITLE', '#0066cc'),
            'message' => (string)pipup_cfg('COLOR_MESSAGE', '#000000'),
            'background' => (string)pipup_cfg('COLOR_BACKGROUND', '#ffffff'),
        );
    }

    /** Раскладка окон: vertical | horizontal | none. */
    function pipup_stack()
    {
        $stack = (string)pipup_cfg('STACK', 'vertical');
        return in_array($stack, array('vertical', 'horizontal', 'none'), true) ? $stack : 'vertical';
    }

    /** Запись в лог terminals (только если включено или это ошибка). */
    function pipup_log($message, $always = false)
    {
        if (!$always && !(int)pipup_cfg('LOG_DEBUG', 0)) {
            return;
        }
        if (function_exists('DebMes')) {
            DebMes('PiPup: ' . $message, 'terminals');
        }
    }

    /** Поиск терминала по имени/адресу. */
    function pipup_terminal($name = '')
    {
        $name = trim((string)$name);
        if ($name === '') {
            $name = trim((string)pipup_cfg('TERMINAL', ''));
        }
        if (function_exists('getTerminalsByName') && $name !== '') {
            $found = getTerminalsByName($name, 1);
            if (is_array($found) && !empty($found[0])) {
                return $found[0];
            }
        }
        if (function_exists('SQLSelectOne') && $name !== '') {
            $sql = "SELECT * FROM terminals WHERE NAME='" . pipup_sql($name) . "' OR HOST='" . pipup_sql($name) . "' ORDER BY ID LIMIT 1";
            $rec = SQLSelectOne($sql);
            if (is_array($rec) && !empty($rec['ID'])) {
                return $rec;
            }
        }
        return null;
    }

    /** Экземпляр TTS-дополнения pipup для указанного терминала. */
    function pipup_addon($terminal)
    {
        $modules = defined('DIR_MODULES') ? DIR_MODULES : (defined('ROOT') ? ROOT . 'modules/' : dirname(__FILE__) . '/../');
        include_once $modules . 'terminals/tts_addon.class.php';
        include_once $modules . 'terminals/tts/pipup.addon.php';
        if (!class_exists('pipup')) {
            return null;
        }
        return new pipup($terminal);
    }

    /** Экранирование строки для SQL без зависимости от DBSafe(). */
    function pipup_sql($value)
    {
        if (function_exists('DBSafe')) {
            return DBSafe((string)$value);
        }
        return str_replace(array('\\', "'"), array('\\\\', "\\'"), (string)$value);
    }

    // ---------------------------------------------------------------- intents

    /** Приведение фразы к сопоставимому виду. */
    function pipup_intent_normalize($phrase)
    {
        $phrase = (string)$phrase;
        $phrase = str_replace(array('ё', 'Ё'), array('е', 'Е'), $phrase);
        if (function_exists('mb_strtolower')) {
            $phrase = mb_strtolower($phrase, 'UTF-8');
        } else {
            $phrase = strtolower($phrase);
        }
        $phrase = preg_replace('/[!?.,:;]+/u', ' ', $phrase);
        $phrase = preg_replace('/[\s]+/u', ' ', $phrase);
        return trim($phrase);
    }

    /**
     * Разбор голосовой команды. Возвращает:
     * array('action' => ..., 'arg' => ..., 'lang' => ..., 'raw' => ...).
     *
     * Действия: say, camera, image, close_message, close_all, repeat, unknown.
     * Порядок правил важен: сначала «закрой всё», затем остальные.
     */
    function pipup_intent_parse($phrase)
    {
        $raw = (string)$phrase;
        $text = pipup_intent_normalize($raw);
        $out = array('action' => 'unknown', 'arg' => $raw, 'lang' => '', 'raw' => $raw);

        // Правила: язык => действие => массив ключевых фраз (регулярные выражения без ~).
        $rules = array(
            'ru' => array(
                'close_all'     => array('закрой (все|вс[её]) (окна|окон|уведомлени[яй])', 'закрой все', 'убери (все |)окна', 'закрыть (все|вс[её]) (окна|уведомлени[яй])', 'закрой пипап'),
                'close_message' => array('закрой (уведомление|окно|сообщение)', 'убери (уведомление|окно|сообщение)'),
                'camera'        => array('покажи (камеру|видео|поток)', 'открой камеру', 'выведи камеру', 'покажи на экране'),
                'image'         => array('покажи (картинку|изображение|снимок|фото)', 'выведи картинку'),
                'say'           => array('скажи', 'объяви', 'сообщи', 'уведоми', 'произнеси', 'говори'),
                'repeat'        => array('повтори', 'еще раз', 'ще раз'),
            ),
            'en' => array(
                'close_all'     => array('close (all|every) (windows|popups|notifications)', 'close all', 'dismiss all', 'hide all'),
                'close_message' => array('close (the )?(notification|window|message)', 'dismiss (the )?(notification|window|message)'),
                'camera'        => array('show (the )?(camera|video|stream)', 'open (the )?camera', 'display (the )?camera'),
                'image'         => array('show (the )?(image|picture|photo|snapshot)'),
                'say'           => array('say', 'announce', 'notify', 'speak', 'tell'),
                'repeat'        => array('repeat', 'once more', 'again'),
            ),
            'uk' => array(
                'close_all'     => array('закрий (всі|все) (вікна|повідомлення)', 'закрий все', 'прибери (всі |)вікна', 'закрити всі вікна', 'закрий піпап'),
                'close_message' => array('закрий (повідомлення|вікно)', 'прибери (повідомлення|вікно)'),
                'camera'        => array('покажи (камеру|відео|потік)', 'відкрий камеру', 'виведи камеру'),
                'image'         => array('покажи (картинку|зображення|знімок|фото)'),
                'say'           => array('скажи', 'оголоси', 'повідом', 'виголось'),
                'repeat'        => array('повтори', 'ще раз'),
            ),
        );

        // Сначала определяем язык по наличию любого ключевого слова.
        $lang = '';
        foreach ($rules as $langCode => $actions) {
            foreach ($actions as $patterns) {
                foreach ($patterns as $pattern) {
                    if (preg_match('/' . $pattern . '/u', $text)) {
                        $lang = $langCode;
                        break 3;
                    }
                }
            }
        }
        if ($lang === '') {
            // Фраза без глагола считается названием камеры (см. pipup_camera_url).
            $out['arg'] = $text;
            return $out;
        }
        $out['lang'] = $lang;

        foreach ($rules[$lang] as $action => $patterns) {
            foreach ($patterns as $pattern) {
                if (!preg_match('/' . $pattern . '/u', $text, $m)) {
                    continue;
                }
                $arg = preg_replace('/' . $pattern . '/u', ' ', $text, 1);
                $arg = trim(preg_replace('/\s+/u', ' ', (string)$arg));
                $arg = trim($arg, " \t\n\r\0\x0B-—:,.;");
                $out['action'] = $action;
                $out['arg'] = $arg;
                return $out;
            }
        }
        return $out;
    }

    /** URL камеры по псевдониму из настроек. */
    function pipup_camera_url($alias)
    {
        $map = pipup_cfg('CAMERAS', '');
        $cameras = array();
        if (is_string($map) && trim($map) !== '') {
            $decoded = json_decode($map, true);
            if (is_array($decoded)) {
                $cameras = $decoded;
            }
        } elseif (is_array($map)) {
            $cameras = $map;
        }
        $alias = trim((string)$alias);
        if ($alias !== '') {
            $needle = pipup_intent_normalize($alias);
            foreach ($cameras as $key => $url) {
                if ($key === 'default') {
                    continue;
                }
                if (pipup_intent_normalize($key) === $needle) {
                    return $url;
                }
            }
            return false;
        }
        return isset($cameras['default']) ? $cameras['default'] : false;
    }

    /** Тип окна для URL камеры. */
    function pipup_camera_type($url)
    {
        $configured = (string)pipup_cfg('CAMERA_TYPE', 'auto');
        if (in_array($configured, array('video', 'whep', 'web'), true)) {
            return $configured;
        }
        if (stripos($url, '/api/webrtc') !== false) {
            return 'whep';
        }
        if (preg_match('~^https?://~i', $url)) {
            return 'web';
        }
        return 'video';
    }

    /** Выполнение разобранной команды. Возвращает array(ok, message, action, arg, lang). */
    function pipup_intent_execute($phrase, $terminalName = '')
    {
        $parsed = pipup_intent_parse($phrase);
        $result = array(
            'ok' => false,
            'message' => '',
            'action' => $parsed['action'],
            'arg' => $parsed['arg'],
            'lang' => $parsed['lang'],
        );
        if ($parsed['action'] === 'unknown') {
            $result['message'] = pipup_t('PIPUP_INTENT_UNKNOWN', 'Command not recognized');
            return $result;
        }

        $terminal = pipup_terminal($terminalName);
        if (!$terminal || empty($terminal['HOST'])) {
            $result['message'] = 'PiPup: terminal not found (' . $terminalName . ')';
            pipup_log($result['message'], true);
            return $result;
        }
        $addon = pipup_addon($terminal);
        if (!$addon) {
            $result['message'] = 'PiPup: TTS addon class not found';
            pipup_log($result['message'], true);
            return $result;
        }

        $ok = 0;
        switch ($parsed['action']) {
            case 'say':
                $text = $parsed['arg'];
                if ($text === '') {
                    $result['message'] = pipup_t('PIPUP_INTENT_UNKNOWN', 'Command not recognized');
                    return $result;
                }
                $ok = $addon->say($text);
                $result['message'] = pipup_t('PIPUP_INTENT_SAY_DONE', 'Message shown') . ': ' . $text;
                break;

            case 'camera':
                $url = pipup_camera_url($parsed['arg']);
                if ($url === false) {
                    $result['message'] = pipup_t('PIPUP_INTENT_CAMERA_UNKNOWN', 'Unknown camera') . ': ' . $parsed['arg'];
                    return $result;
                }
                $type = pipup_camera_type($url);
                $options = array(
                    'width' => 640,
                    'height' => 360,
                    'duration' => (int)pipup_cfg('DURATION_VIDEO', 60),
                    'id' => 'intent_' . md5($url),
                );
                $ok = $addon->sendVideo($url, $parsed['arg'], pipup_t('PIPUP_TITLE_CAMERA', 'Camera'), $type, $options);
                $result['message'] = pipup_t('PIPUP_INTENT_CAMERA_DONE', 'Camera shown') . ': ' . $parsed['arg'];
                break;

            case 'image':
                $url = pipup_camera_url($parsed['arg']);
                if ($url === false && preg_match('~^https?://~i', $parsed['arg'])) {
                    $url = $parsed['arg'];
                }
                if ($url === false) {
                    $result['message'] = pipup_t('PIPUP_INTENT_CAMERA_UNKNOWN', 'Unknown camera') . ': ' . $parsed['arg'];
                    return $result;
                }
                $ok = $addon->sendImage($url, $parsed['arg'], pipup_t('PIPUP_TITLE_IMAGE', '🖼'));
                $result['message'] = pipup_t('PIPUP_INTENT_IMAGE_DONE', 'Image shown');
                break;

            case 'close_message':
                $ok = $addon->cancelMessage($parsed['arg']);
                $result['message'] = pipup_t('PIPUP_INTENT_CLOSE_DONE', 'Windows closed');
                break;

            case 'close_all':
                $ok = $addon->cancelAll();
                $result['message'] = pipup_t('PIPUP_INTENT_CLOSE_DONE', 'Windows closed');
                break;

            case 'repeat':
                $last = function_exists('gg') ? (string)gg('pipupLastIntent') : '';
                // Защита от рекурсии, если последней фразой тоже было «повтори».
                if ($last === '' || pipup_intent_parse($last)['action'] === 'repeat') {
                    $result['message'] = pipup_t('PIPUP_INTENT_UNKNOWN', 'Command not recognized');
                    return $result;
                }
                return pipup_intent_execute($last, $terminalName);
        }
        $result['ok'] = $ok ? true : false;
        if (!$ok) {
            $result['message'] = pipup_t('PIPUP_INTENT_ERROR', 'PiPup error') . ': ' . $result['message'];
        }
        if ($ok && function_exists('sg')) {
            sg('pipupLastIntent', $phrase);
        }
        pipup_log('intent ' . $parsed['action'] . ' (' . $parsed['lang'] . '): ' . $result['message']);
        return $result;
    }

    /** Удобная обёртка для сценариев и методов простого устройства. */
    function pipup_handle_intent($phrase, $terminalName = '')
    {
        return pipup_intent_execute($phrase, $terminalName);
    }

    // ------------------------------------------------------- simple devices

    /**
     * Создать (или обновить) «Простое устройство» PiPup и объект SPipupNotifier.
     * Возвращает array(ok, object, device_id, message).
     */
    function pipup_provision_simple_device($terminalName = '')
    {
        $out = array('ok' => false, 'object' => '', 'device_id' => 0, 'message' => '');
        if (!function_exists('SQLInsert') || !function_exists('addClass') || !function_exists('addClassObject')) {
            $out['message'] = 'MajorDoMo core functions are not available';
            return $out;
        }
        $object = trim((string)pipup_cfg('DEVICE_OBJECT', 'PipupNotifier'));
        if ($object === '' || !preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $object)) {
            $out['message'] = 'Invalid DEVICE_OBJECT name';
            return $out;
        }
        $terminal = trim((string)$terminalName);
        if ($terminal === '') {
            $terminal = trim((string)pipup_cfg('TERMINAL', ''));
        }

        addClass('SPipupNotifier');
        // status — свойство, которое показывает интерфейс «Простых устройств»;
        // STATUS дублирует его для сценариев (совместимость с первыми версиями).
        foreach (array('TERMINAL', 'STATUS', 'status', 'LAST_INTENT', 'LAST_ERROR') as $property) {
            addClassProperty('SPipupNotifier', $property);
        }
        // Методы объекта вызываются ядром в контексте событий, где модуль
        // настроек и TTS-дополнение могут быть не загружены — подключаем библиотеку явно.
        $boot = "if (!function_exists('pipup_handle_intent') && defined('DIR_MODULES')) "
            . "include_once(DIR_MODULES . 'pipup/pipup.lib.php');\n";
        $ot = '$ot = $this->object_title;';
        addClassMethod('SPipupNotifier', 'intent',
            $boot . $ot . "\n"
            . "$phrase = isset(\$params['phrase']) ? (string)\$params['phrase'] : '';\n"
            . "if (\$phrase === '' && isset(\$params['message'])) \$phrase = (string)\$params['message'];\n"
            . "if (\$phrase === '') \$phrase = (string)\$this->getProperty('LAST_INTENT');\n"
            . "\$term = (string)\$this->getProperty('TERMINAL');\n"
            . "\$res = pipup_handle_intent(\$phrase, \$term);\n"
            . "\$this->setProperty('LAST_INTENT', \$phrase);\n"
            . "\$this->setProperty('LAST_ERROR', \$res['message']);\n"
            . "\$this->setProperty('STATUS', \$res['ok'] ? 1 : 0);\n"
            . "return \$res['ok'] ? 1 : 0;",
            'pipup');
        addClassMethod('SPipupNotifier', 'turnOn',
            $boot . $ot . "\n"
            . "\$action = '" . pipup_sql((string)pipup_cfg('DEVICE_ON_ACTION', 'camera')) . "';\n"
            . "\$term = (string)\$this->getProperty('TERMINAL');\n"
            . "if (\$action === 'last') {\n"
            . "  \$phrase = (string)\$this->getProperty('LAST_INTENT');\n"
            . "  \$res = \$phrase !== '' ? pipup_handle_intent(\$phrase, \$term) : array('ok'=>false,'message'=>'no last intent');\n"
            . "} else {\n"
            . "  \$res = pipup_camera_url('') !== false ? pipup_intent_execute('camera', \$term) : array('ok'=>false,'message'=>'no default camera');\n"
            . "}\n"
            . "\$this->setProperty('STATUS', \$res['ok'] ? 1 : 0);\n"
            . "\$this->setProperty('status', \$res['ok'] ? 1 : 0);\n"
            . "\$this->setProperty('LAST_ERROR', \$res['message']);\n"
            . "return \$res['ok'] ? 1 : 0;",
            'pipup');
        addClassMethod('SPipupNotifier', 'turnOff',
            $boot . $ot . "\n"
            . "\$addon = pipup_addon(pipup_terminal((string)\$this->getProperty('TERMINAL')));\n"
            . "\$ok = \$addon ? \$addon->cancelAll() : 0;\n"
            . "\$this->setProperty('STATUS', 0);\n"
            . "\$this->setProperty('status', 0);\n"
            . "return \$ok ? 1 : 0;",
            'pipup');

        $objectId = addClassObject('SPipupNotifier', $object);
        if (!$objectId) {
            $out['message'] = 'Cannot create object ' . $object;
            return $out;
        }
        if ($terminal !== '' && function_exists('sg')) {
            sg($object . '.TERMINAL', $terminal);
        }

        $existing = SQLSelectOne("SELECT ID FROM devices WHERE LINKED_OBJECT='" . pipup_sql($object) . "' ORDER BY ID LIMIT 1");
        // relay — единственный тип, для которого ядро devices вызывает turnOn/turnOff
        // по голосовым командам «включи/выключи <устройство>».
        $rec = array(
            'TYPE' => 'relay',
            'TITLE' => pipup_t('PIPUP_DEVICE_TITLE', 'PiPup screen'),
            'LINKED_OBJECT' => $object,
            'ALT_TITLES' => pipup_t('PIPUP_DEVICE_ALT_TITLES', 'pipup, notification screen'),
        );
        if (is_array($existing) && !empty($existing['ID'])) {
            $rec['ID'] = $existing['ID'];
            SQLUpdate('devices', $rec);
            $deviceId = $existing['ID'];
        } else {
            // Не используем devices::addDevice(): ядро сбрасывает LINKED_OBJECT.
            $deviceId = SQLInsert('devices', $rec);
        }
        $out['ok'] = $deviceId ? true : false;
        $out['object'] = $object;
        $out['device_id'] = $deviceId;
        $out['message'] = $out['ok'] ? 'OK' : 'Cannot create simple device';
        return $out;
    }

    /** Обновить состояния простого устройства (для вызова из сценариев). */
    function pipup_publish_simple_device($status, $error = '')
    {
        $object = trim((string)pipup_cfg('DEVICE_OBJECT', 'PipupNotifier'));
        if ($object === '' || !function_exists('sg')) {
            return false;
        }
        sg($object . '.STATUS', (int)$status ? 1 : 0);
        sg($object . '.status', (int)$status ? 1 : 0);
        if ($error !== '') {
            sg($object . '.LAST_ERROR', (string)$error);
        }
        return true;
    }

} // PIPUP_LIB_LOADED
