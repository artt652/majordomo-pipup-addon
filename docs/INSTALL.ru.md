# Установка PiPup для MajorDoMo

## Способ 1. Маркет дополнений (рекомендуется)

1. Скачайте архив `dist/pipup.tgz` из репозитория.
2. В панели MajorDoMo откройте **Маркет дополнений → Загрузить додаток** (в русской локализации — «Загрузить дополнение»).
3. Выберите `pipup.tgz` и дождитесь завершения установки.
4. Обновите страницу: в списке модулей появится **PiPup — настройки**.
5. Откройте **Модули → PiPup — настройки** и нажмите «Сохранить настройки» — конфигурация по умолчанию будет записана в базу.

Маркет сам выполнит `install.php` (регистрация модуля), импортирует сценарий `pipup_intent` из `import/scripts` и скопирует файлы в корень MajorDoMo.

## Способ 2. Ручная установка

Архив не содержит родительской папки — распаковывать нужно прямо в корень MajorDoMo:

```bash
tar -tzf pipup.tgz              # посмотреть содержимое
tar -xzf pipup.tgz -C /var/www/mjmd
```

Минимальная проверка после распаковки (в корне MajorDoMo должны существовать):

```text
modules/terminals/tts/pipup.addon.php
modules/app_player/addons/pipup_play.addon.php
modules/pipup/pipup.lib.php
modules/pipup_admin/pipup_admin.class.php
templates/pipup_admin/pipup_admin.html
languages/pipup_default.php
languages/pipup_en.php
languages/pipup_ru.php
languages/pipup_ua.php
languages/pipup_uk.php
```

Если установка сводилась только к копированию двух addon-файлов (старый способ), плагин работает, но язык и настройки будут значениями по умолчанию: скопируйте ещё `languages/`, `modules/pipup/` и `modules/pipup_admin/`.

После распаковки откройте любую страницу панели: конструктор модуля `pipup_admin` вызовет `checkInstalled()` и зарегистрирует модуль в `project_modules`. Сценарий `pipup_intent` можно импортировать вручную: **Сценарии → Импорт**, выберите `import/scripts/pipup_intent.json`.

## Обновление

1. Сделайте резервную копию `modules/terminals/tts/pipup.addon.php`, `modules/app_player/addons/pipup_play.addon.php` и таблицы `project_modules`.
2. Установите новый архив тем же способом. Файлы дополнений перезаписываются; настройки модуля хранятся в `project_modules.DATA` и сохраняются.
3. Перезапустите циклы MajorDoMo, если в сценариях используются длительности и цвета из настроек: конфигурация кэшируется в памяти процесса.

## Откат

Удалите модуль `pipup_admin` через админку (или удалите строку `NAME='pipup_admin'` из `project_modules` и файл `cms/modules_installed/pipup_admin.installed`), затем восстановьте из резервной копии два addon-файла. Объект `SPipupNotifier` и строка в таблице `devices` при удалении модуля остаются — удалите их вручную, если они больше не нужны.

## Проверка после установки

- В настройках модуля нажмите «Отправить тестовое уведомление» — на телевизоре должно появиться окно.
- Откройте `http://<IP-телевизора>:7979/state` — там видны открытые окна и последняя ошибка медиа.
- Лог `cms/debmes`, канал `terminals`, покажет причину неудачной отправки.
