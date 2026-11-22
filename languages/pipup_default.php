<?php
/**
 * PiPup: словарь строк по умолчанию (английский).
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
);
