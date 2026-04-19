# Сайт-портфолио Елены Булавинцевой

Риэлтор, Комсомольск-на-Амуре

## Технологии
- HTML5, CSS3, JavaScript (ES6)
- Адаптивный дизайн (Mobile First)
- PHP backend для обработки форм
- Telegram Bot API интеграция

## Деплой на reg.ru

1. Скопируйте файлы в `public_html` (сохраняя структуру):
   ```
   index.html
   css/
   js/
   assets/
   legal/
   backend/
   ```

2. Создайте `backend/config.php` из `backend/config.php.example`:
   ```php
   define('BOT_TOKEN', 'ВАШ_ТОКЕН_ИЗ_BOTFATHER');
   define('ADMIN_CHAT_ID', '5026462041');
   ```

3. Убедитесь что домен булавинская.рф привязан к хостингу в reg.ru.
