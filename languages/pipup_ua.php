<?php
/**
 * PiPup: український словник рядків (код ua (за сумісністю з ядром MajorDoMo, де мова України називається ua)).
 */

$PIPUP_STRINGS = array(

    // Заголовки вікон PiPup.
    'PIPUP_TITLE_NOTIFICATION' => 'Повідомлення',
    'PIPUP_TITLE_QUESTION'     => 'Запитання',
    'PIPUP_TITLE_CAMERA'       => 'Камера',
    'PIPUP_TITLE_SOUND'        => "\xF0\x9F\x94\x8A Повідомлення", // 🔊
    'PIPUP_TITLE_IMAGE'        => "\xF0\x9F\x96\xBC",             // 🖼
    'PIPUP_TITLE_VIDEO'        => "\xE2\x96\xB6",                 // ▶

    // Опис плеєра pipup_play.
    'PIPUP_PLAYER_TITLE'        => 'PiPup (спливаюче вікно)',
    'PIPUP_PLAYER_DESC_SHORT'   => 'Показує звук, зображення, відео, WebRTC та вебсторінки у спливаючому вікні PiPup на Android-пристрої (ТВ, приставка, планшет, панель, телефон); текст і голос повідомлень — через тип TTS <b>pipup</b>.',
    'PIPUP_PLAYER_DETAILS'      => 'Детальніше',
    'PIPUP_PLAYER_DESC_TYPES'   => 'Тип вікна обирається за посиланням або розширенням: <b>звук</b> (mp3, wav, ogg, m4a…), <b>зображення</b> (jpg, png, gif, webp; локальний файл надсилається файлом), <b>SVG</b>, <b>відео</b> (mp4, webm, m3u8, rtsp://…), <b>WebRTC</b> go2rtc (…/api/webrtc?src=…), <b>вебсторінка / MJPEG</b> (інші http-посилання).',
    'PIPUP_PLAYER_DESC_FILES'   => 'Файли — з вебкореня MajorDoMo або за посиланням. Звук і відео показуються протягом файлу, потоки — 60 с, зображення — 30 с.',
    'PIPUP_PLAYER_DESC_STOP'    => '«Стоп» закриває всі вікна PiPup (звук за посиланням дограє); пауза, гучність і статус недоступні.',
    'PIPUP_PLAYER_DESC_REQ'     => '<b>Вимоги:</b> Android 6.0.1+ з форком PiPup (mhoogenbosch) 0.27+ — <a href="https://github.com/mhoogenbosch/PiPup/releases/latest/download/PiPup.apk" target="_blank">завантажити APK</a>; PHP з curl, BASE_URL у config.php з портом вебсервера. У полі «Адреса» — лише IP або ім’я пристрою, порт PiPup — у полі «Порт PiPup» (порожньо — 7979).',
    'PIPUP_PLAYER_DESC_VOICE'   => '<b>Текст і голос повідомлень</b> (say, sayTo, ask) виводить тип TTS <b>pipup</b>: увімкніть «Може говорити» та оберіть pipup. Його налаштування задається тут, у полі <b>«Голос повідомлень»</b> (в інших плеєрів це поле «Ім’я користувача»):',
    'PIPUP_PLAYER_VOICE_EMPTY'  => 'порожньо — голос будь-якого генератора мовлення;',
    'PIPUP_PLAYER_VOICE_TAGS'   => 'мітка генератора — лише його голос: <code>sanotts</code>, <code>piper_tts</code>, <code>rh</code>, <code>sapi</code>, <code>yandex</code>, <code>google</code>, <code>ivona</code>, <code>tilde</code>; кілька через кому;',
    'PIPUP_PLAYER_VOICE_DEVICE' => '<code>device</code> — говорити синтезатором самого пристрою, файли генераторів не використовувати.',
    'PIPUP_PLAYER_DESC_TIME'    => 'Тривалість вікна повідомлення обчислюється за довжиною тексту (10–60 с).',
    'PIPUP_PLAYER_DESC_HINT'    => 'Якщо текстові вікна не з’являються, перевірте поріг рівня повідомлень термінала та пріоритет модуля terminals (модулі з BREAK, наприклад yandex_tts, зупиняють подію).',

    // Підписи полів картки термінала.
    'PIPUP_FIELD_VOICE'  => 'Голос повідомлень:',
    'PIPUP_FIELD_PORT'   => 'Порт PiPup:',
    'PIPUP_HINT_VOICE'   => 'порожньо — будь-який голос; sanotts, yandex… — лише ці; device — голос пристрою',
    'PIPUP_HINT_PORT'    => 'порожньо — 7979',
    'PIPUP_VOICE_ANY'    => 'Будь-який голос — перший готовий файл генератора',
    'PIPUP_VOICE_DEVICE' => 'Голос пристрою (device)',
    'PIPUP_VOICE_CACHE'  => '%s — %d у кеші',
    'PIPUP_VOICE_PICK'   => 'Обрати зі списку',

    // Генератори мовлення.
    'PIPUP_ENGINE_SANOTTS'   => 'sanoTTS',
    'PIPUP_ENGINE_PIPER_TTS' => 'Piper',
    'PIPUP_ENGINE_SAPI'      => 'Windows SAPI',
    'PIPUP_ENGINE_RH'        => 'RHVoice',
    'PIPUP_ENGINE_YANDEX'    => 'Яндекс',
    'PIPUP_ENGINE_GOOGLE'    => 'Google',
    'PIPUP_ENGINE_IVONA'     => 'Ivona',
    'PIPUP_ENGINE_TILDE'     => 'Tilde',

    // Відповіді розборщика голосових команд.
    'PIPUP_INTENT_UNKNOWN'        => 'Команду не розпізнано',
    'PIPUP_INTENT_SAY_DONE'       => 'Повідомлення показано',
    'PIPUP_INTENT_CAMERA_DONE'    => 'Камеру показано',
    'PIPUP_INTENT_IMAGE_DONE'     => 'Зображення показано',
    'PIPUP_INTENT_CLOSE_DONE'     => 'Вікна закрито',
    'PIPUP_INTENT_CAMERA_UNKNOWN' => 'Невідома камера',
    'PIPUP_INTENT_ERROR'          => 'Помилка PiPup',

    // Простий пристрій.
    'PIPUP_DEVICE_TITLE'      => 'Екран PiPup',
    'PIPUP_DEVICE_ALT_TITLES' => 'піпап, екран повідомлень, спливаюче вікно, поп-ап вікно',
);
