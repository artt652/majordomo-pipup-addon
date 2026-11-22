<?php
/**
 * Установка пакета PiPup через Маркет дополнений MajorDoMo.
 *
 * MARKET подключает этот файл из распакованной временной папки ДО копирования
 * файлов в корень MajorDoMo, поэтому пути Relative к __DIR__.
 * Регистрация модуля pipup_admin выполняется его конструктором (checkInstalled).
 * После этого MARKET копирует остальные файлы и удаляет install.php.
 */
if (class_exists('module') && is_file(__DIR__ . '/modules/pipup_admin/pipup_admin.class.php')) {
    include_once(__DIR__ . '/modules/pipup/pipup.lib.php');
    include_once(__DIR__ . '/modules/pipup_admin/pipup_admin.class.php');
    $pipupInstaller = new pipup_admin();
}
