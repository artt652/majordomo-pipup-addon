<?php
/**
 * PiPup: английский словарь строк (код en).
 *
 * Файл копируется в ROOT/languages/pipup_default.php и подключается
 * библиотекой modules/pipup/pipup.lib.php. Ключи имеют префикс PIPUP_,
 * чтобы не пересекаться с словарями ядра и других модулей.
 */

$PIPUP_STRINGS = array(

    // Заголовки окон PiPup (emoji намеренно не переводятся).
    'PIPUP_TITLE_NOTIFICATION' => 'Notification',
    'PIPUP_TITLE_QUESTION'     => 'Question',
    'PIPUP_TITLE_CAMERA'       => 'Camera',
    'PIPUP_TITLE_SOUND'        => "\xF0\x9F\x94\x8A Notification", // 🔊
    'PIPUP_TITLE_IMAGE'        => "\xF0\x9F\x96\xBC",             // 🖼
    'PIPUP_TITLE_VIDEO'        => "\xE2\x96\xB6",                 // ▶

    // Описание плеера pipup_play (HTML выводится на странице терминала).
    'PIPUP_PLAYER_TITLE'        => 'PiPup (popup window)',
    'PIPUP_PLAYER_DESC_SHORT'   => 'Shows audio, images, video, WebRTC and web pages in a PiPup popup on an Android device (TV, box, tablet, panel, phone); message text and voice are provided by the <b>pipup</b> TTS type.',
    'PIPUP_PLAYER_DETAILS'      => 'Show more',
    'PIPUP_PLAYER_DESC_TYPES'   => 'The window type is chosen from the URL or file extension: <b>audio</b> (mp3, wav, ogg, m4a…), <b>image</b> (jpg, png, gif, webp; a local file is uploaded), <b>SVG</b>, <b>video</b> (mp4, webm, m3u8, rtsp://…), <b>WebRTC</b> go2rtc (…/api/webrtc?src=…), <b>web page / MJPEG</b> (other http links).',
    'PIPUP_PLAYER_DESC_FILES'   => 'Files come from the MajorDoMo web root or from a URL. Audio and video are shown for the file duration, streams for 60 s, images for 30 s.',
    'PIPUP_PLAYER_DESC_STOP'    => '“Stop” closes all PiPup windows (a sound played from a URL finishes); pause, volume and status are not available.',
    'PIPUP_PLAYER_DESC_REQ'     => '<b>Requirements:</b> Android 6.0.1+ with the PiPup fork (mhoogenbosch) 0.27+ — <a href="https://github.com/mhoogenbosch/PiPup/releases/latest/download/PiPup.apk" target="_blank">download APK</a>; PHP with curl, BASE_URL in config.php containing the web server port. Put only the IP or hostname in “Address”, and the PiPup port in “PiPup port” (empty — 7979).',
    'PIPUP_PLAYER_DESC_VOICE'   => '<b>Message text and voice</b> (say, sayTo, ask) are produced by the <b>pipup</b> TTS type: enable “Can speak” and select pipup. Its settings are defined here in the <b>“Message voice”</b> field (called “Username” for other players):',
    'PIPUP_PLAYER_VOICE_EMPTY'  => 'empty — any speech engine file;',
    'PIPUP_PLAYER_VOICE_TAGS'   => 'engine label — only this engine: <code>sanotts</code>, <code>piper_tts</code>, <code>rh</code>, <code>sapi</code>, <code>yandex</code>, <code>google</code>, <code>ivona</code>, <code>tilde</code>; several labels separated by commas;',
    'PIPUP_PLAYER_VOICE_DEVICE' => '<code>device</code> — use the device\'s own speech synthesizer, ignore generator files.',
    'PIPUP_PLAYER_DESC_TIME'    => 'The message window stays open from 10 to 60 s depending on the text length.',
    'PIPUP_PLAYER_DESC_HINT'    => 'If text windows do not appear, check the terminal message level and the priority of the terminals module (modules with BREAK, e.g. yandex_tts, stop the event).',

    // Подписи полей карты терминала.
    'PIPUP_FIELD_VOICE'  => 'Message voice:',
    'PIPUP_FIELD_PORT'   => 'PiPup port:',
    'PIPUP_HINT_VOICE'   => 'empty — any voice; sanotts, yandex… — only these; device — device voice',
    'PIPUP_HINT_PORT'    => 'empty — 7979',
    'PIPUP_VOICE_ANY'    => 'Any voice — first generated file',
    'PIPUP_VOICE_DEVICE' => 'Device voice (device)',
    'PIPUP_VOICE_CACHE'  => '%s — %d in cache',
    'PIPUP_VOICE_PICK'   => 'Choose from the list',

    // Человекочитаемые названия генераторов речи.
    'PIPUP_ENGINE_SANOTTS'   => 'sanoTTS',
    'PIPUP_ENGINE_PIPER_TTS' => 'Piper',
    'PIPUP_ENGINE_SAPI'      => 'Windows SAPI',
    'PIPUP_ENGINE_RH'        => 'RHVoice',
    'PIPUP_ENGINE_YANDEX'    => 'Yandex',
    'PIPUP_ENGINE_GOOGLE'    => 'Google',
    'PIPUP_ENGINE_IVONA'     => 'Ivona',
    'PIPUP_ENGINE_TILDE'     => 'Tilde',

    // Ответы разборщика голосовых команд.
    'PIPUP_INTENT_UNKNOWN'       => 'Command not recognized',
    'PIPUP_INTENT_SAY_DONE'      => 'Message shown',
    'PIPUP_INTENT_CAMERA_DONE'   => 'Camera shown',
    'PIPUP_INTENT_IMAGE_DONE'    => 'Image shown',
    'PIPUP_INTENT_CLOSE_DONE'    => 'Windows closed',
    'PIPUP_INTENT_CAMERA_UNKNOWN'=> 'Unknown camera',
    'PIPUP_INTENT_ERROR'         => 'PiPup error',

    // Простое устройство.
    'PIPUP_DEVICE_TITLE'      => 'PiPup screen',
    'PIPUP_DEVICE_ALT_TITLES' => 'pipup, notification screen, pipup screen, pop-up window',

    // Страница настроек модуля pipup_admin.
    'PIPUP_ADMIN_TITLE' => 'PiPup — settings',
    'PIPUP_ADMIN_SUBTITLE' => 'Notifications, voice and intents for Android devices',
    'PIPUP_ADMIN_SAVED' => 'Settings saved.',
    'PIPUP_ADMIN_RESET_DONE' => 'Default settings restored.',
    'PIPUP_ADMIN_ERROR' => 'Error',
    'PIPUP_ADMIN_SAVE' => 'Save settings',
    'PIPUP_ADMIN_RESET' => 'Restore defaults',
    'PIPUP_ADMIN_PROVISION' => 'Create/update Simple device',
    'PIPUP_ADMIN_TEST' => 'Send test notification',
    'PIPUP_ADMIN_GROUP_LANG' => 'Language and voice',
    'PIPUP_ADMIN_LANGUAGE' => 'Window text language',
    'PIPUP_ADMIN_LANGUAGE_HINT' => 'auto — use the MajorDoMo site language (ua is treated as uk)',
    'PIPUP_ADMIN_TTS_LANGUAGE' => 'Device speech language',
    'PIPUP_ADMIN_TTS_HINT' => 'auto — en-US / ru-RU / uk-UA; any BCP-47 code is allowed',
    'PIPUP_ADMIN_VOICE_FILTER' => 'Default speech generator',
    'PIPUP_ADMIN_VOICE_HINT' => 'Empty — any generator; labels separated by comma; device — device synthesizer',
    'PIPUP_ADMIN_GROUP_DURATION' => 'Window duration',
    'PIPUP_ADMIN_TEXT_BASE' => 'Text: base, s',
    'PIPUP_ADMIN_TEXT_CHARS' => 'Text: seconds per N characters',
    'PIPUP_ADMIN_TEXT_MAX' => 'Text: maximum, s',
    'PIPUP_ADMIN_SOUND_DURATION' => 'Sound, s',
    'PIPUP_ADMIN_IMAGE_DURATION' => 'Image, s',
    'PIPUP_ADMIN_VIDEO_DURATION' => 'Video/stream, s',
    'PIPUP_ADMIN_GROUP_WINDOW' => 'Windows',
    'PIPUP_ADMIN_STACK' => 'Stacking',
    'PIPUP_ADMIN_POSITION' => 'Position',
    'PIPUP_ADMIN_FALLBACK' => 'Retry without fork fields on HTTP 400',
    'PIPUP_ADMIN_DEBUG' => 'Detailed log (terminals channel)',
    'PIPUP_ADMIN_GROUP_COLORS' => 'Colors',
    'PIPUP_ADMIN_COLOR_TITLE' => 'Title (text windows)',
    'PIPUP_ADMIN_COLOR_MESSAGE' => 'Text (text windows)',
    'PIPUP_ADMIN_COLOR_BG' => 'Background (text windows)',
    'PIPUP_ADMIN_MEDIA_COLOR_TITLE' => 'Title (media windows)',
    'PIPUP_ADMIN_MEDIA_COLOR_MESSAGE' => 'Text (media windows)',
    'PIPUP_ADMIN_MEDIA_COLOR_BG' => 'Background (media windows)',
    'PIPUP_ADMIN_GROUP_DEVICE' => 'Simple device and intents',
    'PIPUP_ADMIN_TERMINAL' => 'Default terminal',
    'PIPUP_ADMIN_TERMINAL_HINT' => 'Name from the Terminals card, used by scenarios and voice commands',
    'PIPUP_ADMIN_CAMERAS' => 'Camera map (JSON)',
    'PIPUP_ADMIN_CAMERAS_HINT' => '{"default": "http://.../api/webrtc?src=door", "door": "..."}',
    'PIPUP_ADMIN_CAMERA_TYPE' => 'Camera window type',
    'PIPUP_ADMIN_ON_ACTION' => '“Turn on” action of the Simple device',
    'PIPUP_ADMIN_DEVICE_OBJECT' => 'Object name of the Simple device',
    'PIPUP_ADMIN_PROVISION_HINT' => 'Creates the SPipupNotifier object, its methods and a row in the devices table. Safe to repeat.',
    'PIPUP_ADMIN_DEVICE_OK' => 'Simple device created/updated: %s',
    'PIPUP_ADMIN_DEVICE_FAIL' => 'Failed to create Simple device: %s',
    'PIPUP_ADMIN_GROUP_HELP' => 'How to use',
    'PIPUP_ADMIN_HELP' => 'In the terminal card select pipup as TTS type and pipup_play as player type. Voice commands are handled by the SPipupNotifier.intent method and the COMMAND event of the devices module.',
    'PIPUP_ADMIN_VERSION' => 'Version',
    'PIPUP_ADMIN_AUTO' => 'auto (site language)',
    'PIPUP_ADMIN_YES' => 'yes',
    'PIPUP_ADMIN_NO' => 'no',
    'PIPUP_ADMIN_TEST_OK' => 'Test notification sent',
    'PIPUP_ADMIN_TEST_FAIL' => 'Test notification failed',
);
