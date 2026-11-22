# API PiPup в модуле MajorDoMo

Модуль отправляет HTTP-запросы встроенному серверу PiPup (NanoHTTPD, порт 7979). Ниже — все поля и эндпоинты, которые поддерживает форк [mhoogenbosch/PiPup](https://github.com/mhoogenbosch/PiPup), и методы дополнения MajorDoMo.

## Совместимость

Поля, неизвестные оригинальному PiPup (`rogro82/PiPup`), отправляются отдельно от базовых. Если устройство отвечает HTTP 400, запрос повторяется только с базовыми полями — текст и картинка продолжают работать. Повтор можно отключить настройкой «Повтор без полей форка».

Поля применяются, только если они явно заданы в настройках модуля или в аргументе `options`. Поэтому старые сценарии продолжают работать без изменений.

## Поля /notify

### Базовые (понимает и оригинальный PiPup)

| Поле | Метод/настройка | Значение |
| --- | --- | --- |
| `duration` | `$duration`, `DURATION_*` | Секунды; `0` — бессрочно |
| `position` | `POSITION` | 0–8: 0 top_right, 1 top_left, 2 bottom_right, 3 bottom_left, 4 center, 5 top_center, 6 bottom_center, 7 center_left, 8 center_right |
| `title`, `titleColor`, `titleSize` | `$title`, `COLOR_TITLE` | Заголовок и его оформление |
| `message`, `messageColor`, `messageSize` | `$phrase`, `COLOR_MESSAGE` | Текст и его оформление |
| `backgroundColor` | `COLOR_BACKGROUND` | Фон окна, `[AA]RRGGBB` |
| `media` | `sendImage()`, `sendVideo()`, `notify()` | `image`, `video`, `web`, `whep`, `grid` |
| `image`, `imageWidth` | `sendImage()` | Загрузка файла через multipart |

### Расширенные поля окна (форк)

| Поле | Настройка / `$options` | Описание |
| --- | --- | --- |
| `id` | `$id`, `$options['id']` | Своё окно; тот же id обновляет окно на месте |
| `stack` | `STACK` | `vertical`, `horizontal`, `none` |
| `tts`, `ttsLanguage` | `deviceTts`, `sayDevice()`, `TTS_LANGUAGE` | Речь синтезатором устройства, язык BCP-47 |
| `sound`, `soundVolume` | `sendSound()`, `$volume` | `default` — встроенный звук, иначе ссылка; громкость 0–1 |
| `urgency` | `URGENCY` | `info`, `warning`, `critical` — цвет рамки |
| `borderColor`, `borderWidth` | `BORDER_COLOR`, `BORDER_WIDTH` | Толщина и цвет рамки, `0` выключает рамку |
| `cornerRadius` | `CORNER_RADIUS` | Радиус углов, px |
| `icon`, `iconPosition`, `iconWidth` | `ICON*`, `$options` | Иконка рядом с текстом |
| `showProgress` | `SHOW_PROGRESS` | Полоса обратного отсчёта |
| `buttons`, `callback` | `BUTTONS`, `CALLBACK` | Кнопки и URL, куда PiPup отправляет нажатие |
| `buttonSize` | `BUTTON_SIZE` | Размер кнопок, sp |
| `animation` | `ANIMATION` | `fade`, `slide_left`, `slide_right`, `slide_top`, `slide_bottom` |
| `opacity` | `OPACITY` | Прозрачность всего окна 0–1 |
| `padding` | `PADDING` | Внешний отступ, px; `0` — почти без рамки |
| `bringToFront` | `BRING_TO_FRONT` | Поднять окно при обновлении |
| `dismissScreensaver` | `DISMISS_SCREENSAVER` | Снять заставку перед показом |
| `fullscreen` | `FULLSCREEN` | Окно на весь экран (≥ 0.29.1) |
| `mediaFullBleed` | `MEDIA_FULL_BLEED` | Медиа до краёв окна |
| `buttonsInline` | `BUTTONS_INLINE` | Кнопки в строке заголовка |
| `liveBadge*` | `$options['liveBadge*']` | Плашка Live поверх видео |
| `posterMode` | `POSTER_MODE` | `fit`, `fill`, `stretch`, `center` |
| `transparent` | `TRANSPARENT` | Прозрачный WebView (только `web`) |
| `softwareDecoder` | `SOFTWARE_DECODER` | Программный декодер для `video` |
| `bufferMs` | `BUFFER_MS` | Буфер RTSP 100–10000 мс (≥ 0.31.0) |

### Произвольные поля

Передайте любой набор напрямую:

```php
include_once(DIR_MODULES . 'terminals/tts_addon.class.php');
include_once(DIR_MODULES . 'terminals/tts/pipup.addon.php');
$t = getTerminalsByName('televizor', 1);
$pipup = new pipup($t[0]);

$pipup->notify(array(
    'title' => 'Дверь',
    'message' => 'Движение',
    'duration' => 0,
    'urgency' => 'critical',
    'showProgress' => true,
    'buttons' => array(array('id' => 'open', 'label' => 'Открыть')),
    'callback' => 'http://192.168.1.5/pipup_button.php',
    'media' => array('whep' => array(
        'uri' => 'http://192.168.1.10:1984/api/webrtc?src=door',
        'width' => 640, 'height' => 360,
        'poster' => 'http://192.168.1.10:1984/api/frame.jpeg?src=door',
        'posterMode' => 'fit',
    )),
), array('animation' => 'fade'));
```

Тот же результат можно получить через `sendVideo(..., $options)` — все новые ключи `options` пробрасываются в запрос:

```php
$pipup->sendVideo('rtsp://192.168.1.20/stream', 'Двор', 'Камера', 'video', array(
    'width' => 640, 'bufferMs' => 200, 'softwareDecoder' => true,
    'liveBadge' => true, 'poster' => 'http://192.168.1.20/snap.jpg',
));
```

Камеральная стена (≥ 0.29):

```php
$pipup->sendVideo('', 'Камеры', 'Стена', 'grid', array(
    'items' => array(
        array('uri' => 'http://go2rtc:1984/api/webrtc?src=door', 'label' => 'Дверь'),
        array('uri' => 'http://go2rtc:1984/api/webrtc?src=yard', 'label' => 'Двор'),
    ),
    'columns' => 2, 'width' => 1280, 'height' => 720,
));
```

Звук по умолчанию:

```php
$pipup->sendSound('default', 'Внимание', '🔔', 8);
```

## Другие эндпоинты

| Метод дополнения | Запрос | Что делает |
| --- | --- | --- |
| `state()` | GET `/state` | Массив состояния: окна, экран, версия, ошибки медиа; `false` при ошибке |
| `power('on'\|'off'\|'toggle')` | POST `/power?state=…` | Включить/выключить экран; требует разрешения для `off` |
| `key('play_pause', $package)` | POST `/key?key=…` | Медиа-клавиша или навигация; список: play_pause, play, pause, stop, next, previous, rewind, fast_forward, home, back |
| `volume(array('level' => 30))` | POST `/volume?level=…` | Громкость: `level` 0–100, `step` up/down или `mute` on/off/toggle; можно `ui => false` |
| `launch($package, $uri, $options)` | POST `/launch` | Запустить приложение или deep link; опции `wake`, `click`, `method`, `wait` |
| `apps()` | GET `/apps` | Список приложений с package/label |
| `settingsWebhook($url)` | POST `/settings?webhook=…` | Push состояния PiPup на ваш URL |
| `mediaState()` | GET `/media` | Что играет: package, state, positionMs, title, actions (≥ 1.0) |
| `mediaSeek($ms, $by, $package)` | POST `/media/seek` | Перемотка на позицию или на delta |
| `click($package, $id, $options)` | POST `/click` | Нажать кнопку в разрешённом приложении (≥ 1.0) |
| `cancel($id)`, `cancelAll()` | POST `/cancel` | Закрыть окно по id или все окна |

Все методы возвращают `1`/`0`, кроме `state()`, `apps()` и `mediaState()` — они возвращают разобранный JSON или `false`. Причина ошибки пишется в лог `terminals`.

> Эндпоинты `/power` (off), `/key` (home/back), `/click`, `/media` и `/settings` могут быть недоступны на устройстве без соответствующих разрешений — PiPup отвечает 501. Модуль это не скрывает и пишет ошибку в лог.
