<?php
/**
 * PiPup: русский словарь строк.
 */

$PIPUP_STRINGS = array(

    // Заголовки окон PiPup.
    'PIPUP_TITLE_NOTIFICATION' => 'Уведомление',
    'PIPUP_TITLE_QUESTION'     => 'Вопрос',
    'PIPUP_TITLE_CAMERA'       => 'Камера',
    'PIPUP_TITLE_SOUND'        => "\xF0\x9F\x94\x8A Уведомление", // 🔊
    'PIPUP_TITLE_IMAGE'        => "\xF0\x9F\x96\xBC",             // 🖼
    'PIPUP_TITLE_VIDEO'        => "\xE2\x96\xB6",                 // ▶

    // Описание плеера pipup_play.
    'PIPUP_PLAYER_TITLE'        => 'PiPup (всплывающее окно)',
    'PIPUP_PLAYER_DESC_SHORT'   => 'Показывает звук, картинки, видео, WebRTC и веб-страницы во всплывающем окне PiPup на Android-устройстве (ТВ, приставка, планшет, панель, телефон); текст и голос сообщений — через тип TTS <b>pipup</b>.',
    'PIPUP_PLAYER_DETAILS'      => 'Подробнее',
    'PIPUP_PLAYER_DESC_TYPES'   => 'Тип окна выбирается по ссылке или расширению: <b>звук</b> (mp3, wav, ogg, m4a…), <b>картинка</b> (jpg, png, gif, webp; локальный файл уходит файлом), <b>SVG</b>, <b>видео</b> (mp4, webm, m3u8, rtsp://…), <b>WebRTC</b> go2rtc (…/api/webrtc?src=…), <b>веб-страница / MJPEG</b> (прочие http-ссылки).',
    'PIPUP_PLAYER_DESC_FILES'   => 'Файлы — из веб-корня MajorDoMo или по ссылке. Звук и видео показываются на время файла, потоки — 60 с, картинки — 30 с.',
    'PIPUP_PLAYER_DESC_STOP'    => '«Стоп» закрывает все окна PiPup (звук по ссылке доигрывает); пауза, громкость и статус недоступны.',
    'PIPUP_PLAYER_DESC_REQ'     => '<b>Требования:</b> Android 6.0.1+ с форком PiPup (mhoogenbosch) 0.27+ — <a href="https://github.com/mhoogenbosch/PiPup/releases/latest/download/PiPup.apk" target="_blank">скачать APK</a>; PHP с curl, BASE_URL в config.php с портом веб-сервера. В поле «Адрес» — только IP или имя устройства, порт PiPup — в поле «Порт PiPup» (пусто — 7979).',
    'PIPUP_PLAYER_DESC_VOICE'   => '<b>Текст и голос сообщений</b> (say, sayTo, ask) выводит тип TTS <b>pipup</b>: включите «Может произносить» и выберите pipup. Его настройка задаётся здесь, в поле <b>«Голос сообщений»</b> (у других плееров это поле «Имя пользователя»):',
    'PIPUP_PLAYER_VOICE_EMPTY'  => 'пусто — голос любого генератора речи;',
    'PIPUP_PLAYER_VOICE_TAGS'   => 'метка генератора — только его голос: <code>sanotts</code>, <code>piper_tts</code>, <code>rh</code>, <code>sapi</code>, <code>yandex</code>, <code>google</code>, <code>ivona</code>, <code>tilde</code>; можно несколько через запятую;',
    'PIPUP_PLAYER_VOICE_DEVICE' => '<code>device</code> — говорить синтезатором самого устройства, файлы генераторов не использовать.',
    'PIPUP_PLAYER_DESC_TIME'    => 'Длительность окна сообщения считается по длине текста (10–60 с).',
    'PIPUP_PLAYER_DESC_HINT'    => 'Если текстовые окна не появляются, проверьте порог уровня сообщений терминала и приоритет модуля terminals (модули с BREAK, например yandex_tts, останавливают рассылку события).',

    // Подписи полей карточки терминала.
    'PIPUP_FIELD_VOICE'  => 'Голос сообщений:',
    'PIPUP_FIELD_PORT'   => 'Порт PiPup:',
    'PIPUP_HINT_VOICE'   => 'пусто — любой голос; sanotts, yandex… — только эти; device — голос устройства',
    'PIPUP_HINT_PORT'    => 'пусто — 7979',
    'PIPUP_VOICE_ANY'    => 'Любой голос — первый готовый файл генератора',
    'PIPUP_VOICE_DEVICE' => 'Голос устройства (device)',
    'PIPUP_VOICE_CACHE'  => '%s — %d в кеше',
    'PIPUP_VOICE_PICK'   => 'Выбрать из списка',

    // Генераторы речи.
    'PIPUP_ENGINE_SANOTTS'   => 'sanoTTS',
    'PIPUP_ENGINE_PIPER_TTS' => 'Piper',
    'PIPUP_ENGINE_SAPI'      => 'Windows SAPI',
    'PIPUP_ENGINE_RH'        => 'RHVoice',
    'PIPUP_ENGINE_YANDEX'    => 'Яндекс',
    'PIPUP_ENGINE_GOOGLE'    => 'Google',
    'PIPUP_ENGINE_IVONA'     => 'Ivona',
    'PIPUP_ENGINE_TILDE'     => 'Tilde',

    // Ответы разборщика голосовых команд.
    'PIPUP_INTENT_UNKNOWN'        => 'Команда не распознана',
    'PIPUP_INTENT_SAY_DONE'       => 'Сообщение показано',
    'PIPUP_INTENT_CAMERA_DONE'    => 'Камера показана',
    'PIPUP_INTENT_IMAGE_DONE'     => 'Картинка показана',
    'PIPUP_INTENT_CLOSE_DONE'     => 'Окна закрыты',
    'PIPUP_INTENT_CAMERA_UNKNOWN' => 'Неизвестная камера',
    'PIPUP_INTENT_ERROR'          => 'Ошибка PiPup',

    // Простое устройство.
    'PIPUP_DEVICE_TITLE'      => 'Экран PiPup',
    'PIPUP_DEVICE_ALT_TITLES' => 'пипап, экран уведомлений, всплывающее окно, поп-ап окно',
);
