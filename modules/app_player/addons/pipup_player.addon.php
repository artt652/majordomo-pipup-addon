<?php

/*
	Addon PiPup (pipup_player) for app_player
	Воспроизводит аудио во всплывающем окне PiPup на Android TV / планшете
	(форк mhoogenbosch/PiPup, поле sound). Отправку выполняет дополнение
	терминалов modules/terminals/tts/pipup.addon.php.
*/

class pipup_player extends app_player_addon
{

    // Constructor
    function __construct($terminal)
    {
        $this->title = 'PiPup (всплывающее окно)';
        $this->description = 'Проигрывает аудио по ссылке во всплывающем окне PiPup на Android TV или планшете. ';
        $this->description .= 'Нужен форк PiPup (mhoogenbosch) версии 0.18+. Порт по умолчанию 7979 (можно указать в HOST как ip:port). ';
        $this->description .= 'Управление воспроизведением (стоп, пауза, громкость, статус) не поддерживается.';
        $this->description .= '<br><b>Имя пользователя</b> — генератор голоса для этого терминала: метка в имени файла — префикс (sanotts, piper_tts, rh, sapi) или суффикс (yandex, google, ivona, tilde), можно через запятую. Пусто — любой генератор.';
        parent::__construct($terminal);
    }

    // Play
    function play($input)
    {
        $this->reset_properties();
        $input = trim((string)$input);
        if ($input === '') {
            $this->success = FALSE;
            $this->message = 'Input is missing!';
            return $this->success;
        }

        include_once(DIR_MODULES . 'terminals/tts_addon.class.php');
        include_once(DIR_MODULES . 'terminals/tts/pipup.addon.php');
        $pipup = new pipup($this->terminal);

        $url = $pipup->fileUrl($input);
        if ($url === false) {
            $this->success = FALSE;
            $this->message = 'Cannot build URL for ' . $input . ' (file must be inside web root, or pass http(s) URL)';
            return $this->success;
        }

        // Длительность окна: длина файла + запас, если файл локальный
        $duration = 15;
        $local = $pipup->localPath($input);
        if ($local !== false && function_exists('getMediaDurationSeconds')) {
            $seconds = (int)getMediaDurationSeconds($local);
            if ($seconds > 0) {
                $duration = $seconds + 2;
            }
        }

        if ($pipup->sendSound($url, '', '', $duration)) {
            $this->success = TRUE;
            $this->message = 'OK';
        } else {
            $this->success = FALSE;
            $this->message = 'PiPup did not accept the sound (see cms/debmes, channel terminals)';
        }
        return $this->success;
    }
}
