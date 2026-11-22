<?php
/**
 * Модуль MajorDoMo «PiPup — настройки».
 *
 * Отдельный модуль нужен, потому что класс TTS-дополнения уже называется
 * pipup: класс модуля с таким же именем привёл бы к фатальной ошибке при
 * загрузке страницы «Терминалы». Настройки сохраняются в project_modules.DATA
 * и читаются общей библиотекой modules/pipup/pipup.lib.php.
 *
 * @package pipup
 * @license MIT
 * @version 1.0.0
 */

require_once(dirname(__FILE__) . '/../pipup/pipup.lib.php');

class pipup_admin extends module
{
    const VERSION = '1.1.0';

    /** Название в меню: словари модуля загружаются позже конструктора. */
    static function localizedTitle()
    {
        $lang = defined('SETTINGS_SITE_LANGUAGE') ? strtolower((string)SETTINGS_SITE_LANGUAGE) : 'en';
        $map = array(
            'en' => 'PiPup — Settings',
            'ru' => 'PiPup — Настройки',
            'ua' => 'PiPup — Налаштування',
            'uk' => 'PiPup — Налаштування',
        );
        return isset($map[$lang]) ? $map[$lang] : $map['en'];
    }

    function pipup_admin()
    {
        $this->name            = 'pipup_admin';
        $this->title           = self::localizedTitle();
        $this->module_category = '<#LANG_SECTION_SYSTEM#>';
        $this->checkInstalled();
    }

    function __construct()
    {
        $this->pipup_admin();
    }

    function install($parent_name = '')
    {
        parent::install($parent_name);
        $cfg = $this->configAll();
        $this->config = $cfg;
        $this->saveConfig();
    }

    function saveParams($data = 0)
    {
        $p = array();
        if (isset($this->mode)) $p['mode'] = $this->mode;
        return parent::saveParams($p);
    }

    function getParams()
    {
        global $mode;
        if (isset($mode)) {
            $this->mode = $mode;
        }
    }

    /** Конфигурация со значениями по умолчанию. */
    function configAll()
    {
        $cfg = $this->getConfig();
        if (!is_array($cfg)) {
            $cfg = array();
        }
        return array_merge(pipup_defaults(), $cfg);
    }

    /** Список строк, которые нужны шаблону. */
    function stringKeys()
    {
        return array(
            'PIPUP_ADMIN_TITLE', 'PIPUP_ADMIN_SUBTITLE', 'PIPUP_ADMIN_SAVED',
            'PIPUP_ADMIN_RESET_DONE', 'PIPUP_ADMIN_ERROR', 'PIPUP_ADMIN_SAVE',
            'PIPUP_ADMIN_RESET', 'PIPUP_ADMIN_PROVISION', 'PIPUP_ADMIN_TEST',
            'PIPUP_ADMIN_GROUP_LANG', 'PIPUP_ADMIN_LANGUAGE', 'PIPUP_ADMIN_LANGUAGE_HINT',
            'PIPUP_ADMIN_TTS_LANGUAGE', 'PIPUP_ADMIN_TTS_HINT', 'PIPUP_ADMIN_VOICE_FILTER',
            'PIPUP_ADMIN_VOICE_HINT', 'PIPUP_ADMIN_GROUP_DURATION', 'PIPUP_ADMIN_TEXT_BASE',
            'PIPUP_ADMIN_TEXT_CHARS', 'PIPUP_ADMIN_TEXT_MAX', 'PIPUP_ADMIN_SOUND_DURATION',
            'PIPUP_ADMIN_IMAGE_DURATION', 'PIPUP_ADMIN_VIDEO_DURATION', 'PIPUP_ADMIN_GROUP_WINDOW',
            'PIPUP_ADMIN_STACK', 'PIPUP_ADMIN_POSITION', 'PIPUP_ADMIN_FALLBACK',
            'PIPUP_ADMIN_DEBUG', 'PIPUP_ADMIN_GROUP_COLORS', 'PIPUP_ADMIN_COLOR_TITLE',
            'PIPUP_ADMIN_COLOR_MESSAGE', 'PIPUP_ADMIN_COLOR_BG', 'PIPUP_ADMIN_MEDIA_COLOR_TITLE',
            'PIPUP_ADMIN_MEDIA_COLOR_MESSAGE', 'PIPUP_ADMIN_MEDIA_COLOR_BG',
            'PIPUP_ADMIN_GROUP_DEVICE', 'PIPUP_ADMIN_TERMINAL', 'PIPUP_ADMIN_TERMINAL_HINT',
            'PIPUP_ADMIN_CAMERAS', 'PIPUP_ADMIN_CAMERAS_HINT', 'PIPUP_ADMIN_CAMERA_TYPE',
            'PIPUP_ADMIN_ON_ACTION', 'PIPUP_ADMIN_DEVICE_OBJECT', 'PIPUP_ADMIN_PROVISION_HINT',
            'PIPUP_ADMIN_DEVICE_OK', 'PIPUP_ADMIN_DEVICE_FAIL', 'PIPUP_ADMIN_GROUP_HELP',
            'PIPUP_ADMIN_HELP', 'PIPUP_ADMIN_VERSION', 'PIPUP_ADMIN_AUTO',
            'PIPUP_ADMIN_YES', 'PIPUP_ADMIN_NO', 'PIPUP_ADMIN_TEST_OK', 'PIPUP_ADMIN_TEST_FAIL',
            'PIPUP_ADMIN_GROUP_API', 'PIPUP_ADMIN_API_HINT', 'PIPUP_ADMIN_URGENCY',
            'PIPUP_ADMIN_BORDER_COLOR', 'PIPUP_ADMIN_BORDER_WIDTH', 'PIPUP_ADMIN_CORNER_RADIUS',
            'PIPUP_ADMIN_ICON', 'PIPUP_ADMIN_ICON_POSITION', 'PIPUP_ADMIN_ICON_WIDTH',
            'PIPUP_ADMIN_SHOW_PROGRESS', 'PIPUP_ADMIN_BUTTONS', 'PIPUP_ADMIN_BUTTONS_HINT',
            'PIPUP_ADMIN_CALLBACK', 'PIPUP_ADMIN_BUTTON_SIZE', 'PIPUP_ADMIN_ANIMATION',
            'PIPUP_ADMIN_OPACITY', 'PIPUP_ADMIN_PADDING', 'PIPUP_ADMIN_BRING_TO_FRONT',
            'PIPUP_ADMIN_DISMISS_SCREENSAVER', 'PIPUP_ADMIN_FULLSCREEN',
            'PIPUP_ADMIN_MEDIA_FULL_BLEED', 'PIPUP_ADMIN_BUTTONS_INLINE',
            'PIPUP_ADMIN_TRANSPARENT', 'PIPUP_ADMIN_POSTER_MODE',
            'PIPUP_ADMIN_SOFTWARE_DECODER', 'PIPUP_ADMIN_BUFFER_MS', 'PIPUP_ADMIN_NOT_SET',
        );
    }

    /** Приведение POST-полей к безопасным значениям. */
    function sanitize($post, $current)
    {
        $errors = array();
        $cfg = array_merge(pipup_defaults(), $current);
        foreach ($cfg as $key => $value) {
            if (array_key_exists($key, $post)) {
                $cfg[$key] = $post[$key];
            }
        }

        if (!in_array($cfg['LANGUAGE'], array('auto', 'en', 'ru', 'uk'), true)) {
            $cfg['LANGUAGE'] = 'auto';
        }
        $tts = strtolower(trim((string)$cfg['TTS_LANGUAGE']));
        if ($tts !== 'auto' && !preg_match('/^[a-z]{2,3}(-[a-z0-9]{2,8})*$/', $tts)) {
            $errors[] = 'TTS_LANGUAGE: invalid BCP-47 code';
            $tts = 'auto';
        } elseif ($tts !== 'auto') {
            // Приводим к обычному виду BCP-47: en-US, uk-UA, ru-RU.
            $parts = explode('-', $tts);
            $tts = array_shift($parts);
            foreach ($parts as $part) {
                $tts .= '-' . (strlen($part) === 2 && ctype_alpha($part) ? strtoupper($part) : $part);
            }
        }
        $cfg['TTS_LANGUAGE'] = $tts;

        foreach (array('DURATION_TEXT_BASE', 'DURATION_TEXT_CHARS', 'DURATION_TEXT_MAX',
            'DURATION_SOUND', 'DURATION_IMAGE', 'DURATION_VIDEO') as $key) {
            $cfg[$key] = max(0, min(3600, (int)$cfg[$key]));
        }
        $cfg['DURATION_TEXT_CHARS'] = max(1, (int)$cfg['DURATION_TEXT_CHARS']);
        $cfg['POSITION'] = max(0, min(20, (int)$cfg['POSITION']));
        if (!in_array($cfg['STACK'], array('vertical', 'horizontal', 'none'), true)) {
            $cfg['STACK'] = 'vertical';
        }
        if (!in_array($cfg['CAMERA_TYPE'], array('auto', 'video', 'whep', 'web'), true)) {
            $cfg['CAMERA_TYPE'] = 'auto';
        }
        if (!in_array($cfg['DEVICE_ON_ACTION'], array('camera', 'last'), true)) {
            $cfg['DEVICE_ON_ACTION'] = 'camera';
        }
        $cfg['FALLBACK_400'] = !empty($cfg['FALLBACK_400']) ? 1 : 0;
        $cfg['LOG_DEBUG'] = !empty($cfg['LOG_DEBUG']) ? 1 : 0;

        foreach (array('COLOR_TITLE', 'COLOR_MESSAGE', 'COLOR_BACKGROUND',
            'MEDIA_COLOR_TITLE', 'MEDIA_COLOR_MESSAGE', 'MEDIA_COLOR_BACKGROUND') as $key) {
            $color = trim((string)$cfg[$key]);
            if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
                $defaults = pipup_defaults();
                $errors[] = $key . ': invalid color';
                $color = $defaults[$key];
            }
            $cfg[$key] = $color;
        }

        $cfg['VOICE_FILTER'] = trim((string)$cfg['VOICE_FILTER']);
        if ($cfg['VOICE_FILTER'] !== '' && !preg_match('/^[a-zA-Z0-9_, ]+$/', $cfg['VOICE_FILTER'])) {
            $errors[] = 'VOICE_FILTER: invalid characters';
            $cfg['VOICE_FILTER'] = '';
        }
        $cfg['TERMINAL'] = preg_replace('/[^A-Za-z0-9_\-]/', '', (string)$cfg['TERMINAL']);
        $cfg['DEVICE_OBJECT'] = trim((string)$cfg['DEVICE_OBJECT']);
        if (!preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $cfg['DEVICE_OBJECT'])) {
            $errors[] = 'DEVICE_OBJECT: invalid name';
            $cfg['DEVICE_OBJECT'] = 'PipupNotifier';
        }

        $cameras = trim((string)$cfg['CAMERAS']);
        if ($cameras !== '') {
            $decoded = json_decode($cameras, true);
            if (!is_array($decoded)) {
                $errors[] = 'CAMERAS: invalid JSON';
            } else {
                $cfg['CAMERAS'] = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }

        // ---------------------- дополнительные поля API PiPup (шаг 9)
        $enums = array(
            'URGENCY' => array('', 'info', 'warning', 'critical'),
            'ANIMATION' => array('', 'fade', 'slide_left', 'slide_right', 'slide_top', 'slide_bottom'),
            'ICON_POSITION' => array('', 'left', 'right'),
            'POSTER_MODE' => array('', 'fit', 'fill', 'stretch', 'center'),
        );
        foreach ($enums as $key => $allowed) {
            $cfg[$key] = strtolower(trim((string)$cfg[$key]));
            if (!in_array($cfg[$key], $allowed, true)) {
                $errors[] = $key . ': invalid value';
                $cfg[$key] = '';
            }
        }
        foreach (array('BORDER_COLOR', 'ICON') as $key) {
            $cfg[$key] = trim((string)$cfg[$key]);
        }
        if ($cfg['BORDER_COLOR'] !== '' && !preg_match('/^#(?:[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $cfg['BORDER_COLOR'])) {
            $errors[] = 'BORDER_COLOR: invalid color';
            $cfg['BORDER_COLOR'] = '';
        }
        if ($cfg['ICON'] !== '' && !preg_match('/^https?:\/\//i', $cfg['ICON'])) {
            $errors[] = 'ICON: URL required';
            $cfg['ICON'] = '';
        }
        foreach (array('BORDER_WIDTH', 'CORNER_RADIUS', 'ICON_WIDTH', 'BUTTON_SIZE',
            'PADDING', 'BUFFER_MS', 'CALLBACK') as $key) {
            if ($key === 'CALLBACK') {
                $cfg[$key] = trim((string)$cfg[$key]);
                continue;
            }
            $cfg[$key] = max(0, min(10000, (int)$cfg[$key]));
            if ($key === 'BUFFER_MS' && $cfg[$key] > 0 && $cfg[$key] < 100) {
                $cfg[$key] = 100;
            }
        }
        if ($cfg['OPACITY'] !== '') {
            $cfg['OPACITY'] = max(0, min(1, (float)$cfg['OPACITY']));
        }
        foreach (array('SHOW_PROGRESS', 'BRING_TO_FRONT', 'FULLSCREEN',
            'MEDIA_FULL_BLEED', 'BUTTONS_INLINE', 'TRANSPARENT', 'SOFTWARE_DECODER') as $key) {
            $cfg[$key] = (int)!empty($cfg[$key]);
        }
        $cfg['DISMISS_SCREENSAVER'] = $cfg['DISMISS_SCREENSAVER'] === '' ? '' : ((int)!empty($cfg['DISMISS_SCREENSAVER']));
        $buttons = trim((string)$cfg['BUTTONS']);
        if ($buttons !== '') {
            $decoded = json_decode($buttons, true);
            if (!is_array($decoded)) {
                $errors[] = 'BUTTONS: invalid JSON';
                $cfg['BUTTONS'] = '';
            } else {
                $cfg['BUTTONS'] = json_encode(array_values($decoded), JSON_UNESCAPED_UNICODE);
            }
        }
        return array($cfg, $errors);
    }

    function admin(&$out)
    {
        $this->getParams();
        $cfg = $this->configAll();
        $message = '';
        $error = '';
        $mode = '';
        if (isset($_POST['mode'])) {
            $mode = (string)$_POST['mode'];
        } elseif (isset($_GET['mode'])) {
            $mode = (string)$_GET['mode'];
        }

        if ($mode === 'update' && isset($_POST['cfg']) && is_array($_POST['cfg'])) {
            $result = $this->sanitize($_POST['cfg'], $cfg);
            $cfg = $result[0];
            $this->config = $cfg;
            $this->saveConfig();
            pipup_cache_reset();
            $message = pipup_t('PIPUP_ADMIN_SAVED', 'Settings saved.');
            if (count($result[1])) {
                $error = implode('; ', $result[1]);
            }
        } elseif ($mode === 'reset') {
            $cfg = pipup_defaults();
            $this->config = $cfg;
            $this->saveConfig();
            pipup_cache_reset();
            $message = pipup_t('PIPUP_ADMIN_RESET_DONE', 'Default settings restored.');
        } elseif ($mode === 'provision') {
            $res = pipup_provision_simple_device((string)$cfg['TERMINAL']);
            if ($res['ok']) {
                $message = sprintf(pipup_t('PIPUP_ADMIN_DEVICE_OK', 'Simple device created/updated: %s'), $res['object']);
            } else {
                $error = sprintf(pipup_t('PIPUP_ADMIN_DEVICE_FAIL', 'Failed to create Simple device: %s'), $res['message']);
            }
        } elseif ($mode === 'test') {
            $terminal = pipup_terminal((string)$cfg['TERMINAL']);
            $addon = $terminal ? pipup_addon($terminal) : null;
            $ok = $addon ? $addon->sendCommand('PiPup test', pipup_t('PIPUP_TITLE_NOTIFICATION', 'Notification')) : 0;
            $message = $ok ? pipup_t('PIPUP_ADMIN_TEST_OK', 'Test notification sent') : pipup_t('PIPUP_ADMIN_TEST_FAIL', 'Test notification failed');
        }

        $strings = array();
        foreach ($this->stringKeys() as $key) {
            $strings[$key] = pipup_t($key, $key);
        }
        $out['S'] = $strings;
        $out['CFG'] = $cfg;
        $out['MESSAGE'] = $message;
        $out['ERROR'] = $error;
        $out['FALLBACK_ON'] = !empty($cfg['FALLBACK_400']) ? 1 : 0;
        $out['DEBUG_ON'] = !empty($cfg['LOG_DEBUG']) ? 1 : 0;
        $out['VERSION'] = self::VERSION;
        $out['LANG_NOW'] = pipup_lang();
        $out['TTS_NOW'] = pipup_tts_language();
        $selected = function ($key, $value) use ($cfg) {
            return (string)$cfg[$key] === (string)$value ? 1 : 0;
        };
        $out['LANG_OPTIONS'] = array(
            array('VALUE' => 'auto', 'TITLE' => $strings['PIPUP_ADMIN_AUTO'], 'SELECTED' => $selected('LANGUAGE', 'auto')),
            array('VALUE' => 'en', 'TITLE' => 'English', 'SELECTED' => $selected('LANGUAGE', 'en')),
            array('VALUE' => 'ru', 'TITLE' => 'Русский', 'SELECTED' => $selected('LANGUAGE', 'ru')),
            array('VALUE' => 'uk', 'TITLE' => 'Українська', 'SELECTED' => $selected('LANGUAGE', 'uk')),
        );
        $out['TTS_OPTIONS'] = array(
            array('VALUE' => 'auto', 'TITLE' => $strings['PIPUP_ADMIN_AUTO'], 'SELECTED' => $selected('TTS_LANGUAGE', 'auto')),
            array('VALUE' => 'en-US', 'TITLE' => 'en-US', 'SELECTED' => $selected('TTS_LANGUAGE', 'en-US')),
            array('VALUE' => 'en-GB', 'TITLE' => 'en-GB', 'SELECTED' => $selected('TTS_LANGUAGE', 'en-GB')),
            array('VALUE' => 'ru-RU', 'TITLE' => 'ru-RU', 'SELECTED' => $selected('TTS_LANGUAGE', 'ru-RU')),
            array('VALUE' => 'uk-UA', 'TITLE' => 'uk-UA', 'SELECTED' => $selected('TTS_LANGUAGE', 'uk-UA')),
        );
        $out['STACK_OPTIONS'] = array(
            array('VALUE' => 'vertical', 'TITLE' => 'vertical', 'SELECTED' => $selected('STACK', 'vertical')),
            array('VALUE' => 'horizontal', 'TITLE' => 'horizontal', 'SELECTED' => $selected('STACK', 'horizontal')),
            array('VALUE' => 'none', 'TITLE' => 'none', 'SELECTED' => $selected('STACK', 'none')),
        );
        $out['CAMERA_TYPE_OPTIONS'] = array(
            array('VALUE' => 'auto', 'TITLE' => $strings['PIPUP_ADMIN_AUTO'], 'SELECTED' => $selected('CAMERA_TYPE', 'auto')),
            array('VALUE' => 'whep', 'TITLE' => 'WebRTC (whep)', 'SELECTED' => $selected('CAMERA_TYPE', 'whep')),
            array('VALUE' => 'video', 'TITLE' => 'Video (ExoPlayer)', 'SELECTED' => $selected('CAMERA_TYPE', 'video')),
            array('VALUE' => 'web', 'TITLE' => 'Web view', 'SELECTED' => $selected('CAMERA_TYPE', 'web')),
        );
        $out['ON_ACTION_OPTIONS'] = array(
            array('VALUE' => 'camera', 'TITLE' => 'camera', 'SELECTED' => $selected('DEVICE_ON_ACTION', 'camera')),
            array('VALUE' => 'last', 'TITLE' => 'last intent', 'SELECTED' => $selected('DEVICE_ON_ACTION', 'last')),
        );

        // ---------------------- опции дополнительных полей API
        $enumOptions = function ($key, $values, $titles = array()) use ($cfg, $strings) {
            $result = array();
            foreach ($values as $value) {
                if ($value === '') {
                    $title = $strings['PIPUP_ADMIN_NOT_SET'];
                } else {
                    $title = isset($titles[$value]) ? $titles[$value] : (string)$value;
                }
                $result[] = array(
                    'VALUE' => $value,
                    'TITLE' => $title,
                    'SELECTED' => (string)$cfg[$key] === (string)$value ? 1 : 0,
                );
            }
            return $result;
        };
        $out['URGENCY_OPTIONS'] = $enumOptions('URGENCY',
            array('', 'info', 'warning', 'critical'));
        $out['ANIMATION_OPTIONS'] = $enumOptions('ANIMATION',
            array('', 'fade', 'slide_left', 'slide_right', 'slide_top', 'slide_bottom'));
        $out['ICON_POSITION_OPTIONS'] = $enumOptions('ICON_POSITION', array('', 'left', 'right'));
        $out['POSTER_MODE_OPTIONS'] = $enumOptions('POSTER_MODE',
            array('', 'fit', 'fill', 'stretch', 'center'));
        $tri = array('' => $strings['PIPUP_ADMIN_NOT_SET'],
            1 => $strings['PIPUP_ADMIN_YES'], 0 => $strings['PIPUP_ADMIN_NO']);
        $out['TRI_OPTIONS'] = $enumOptions('DISMISS_SCREENSAVER', array('', 1, 0), $tri);
        foreach (array('SHOW_PROGRESS', 'BRING_TO_FRONT', 'FULLSCREEN',
            'MEDIA_FULL_BLEED', 'BUTTONS_INLINE', 'TRANSPARENT', 'SOFTWARE_DECODER') as $flag) {
            $out['FLAG_' . $flag] = !empty($cfg[$flag]) ? 1 : 0;
        }
    }

    function usual(&$out)
    {
        $this->admin($out);
    }

    function run()
    {
        $out = array();
        if ($this->action == 'admin') {
            $this->admin($out);
        } else {
            $this->usual($out);
        }
        $out['ACTION'] = $this->action;
        $out['MODE'] = isset($this->mode) ? $this->mode : '';

        $templates = array();
        if (defined('DIR_TEMPLATES')) {
            $templates[] = DIR_TEMPLATES . 'pipup_admin/pipup_admin.html';
        }
        if (defined('ROOT')) {
            $templates[] = ROOT . 'templates/pipup_admin/pipup_admin.html';
        }
        $templates[] = dirname(__FILE__) . '/templates/pipup_admin.html';
        $template = '';
        foreach ($templates as $candidate) {
            if (is_file($candidate)) {
                $template = $candidate;
                break;
            }
        }
        if ($template === '') {
            $this->result = 'PiPup: template pipup_admin.html not found';
            return;
        }
        $p = new parser($template, $out, $this);
        $this->result = $p->result;
    }
}
