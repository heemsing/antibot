# 📊 Analytics Service

Полноценный сервис веб-аналитики с админ-панелью, API для сбора событий и MySQL базой данных. Альтернатива Яндекс.Метрике и Google Analytics с полным контролем над данными.

## ✨ Возможности

### 🔍 Трекинг событий
- **Page Views** - просмотр страниц
- **Клики** - email, телефоны, внешние ссылки, кнопки
- **Скроллы** - глубина прокрутки (25%, 50%, 75%, 100%)
- **Время на странице** - активное время с учётом видимости вкладки
- **Формы** - отслеживание отправок форм
- **UTM-метки** - автоматический сбор всех параметров

### 📈 Аналитика
- **Дашборд** - ключевые метрики в реальном времени
- **Сессии** - длительность, bounce rate, глубина просмотра
- **Цели** - конструктор целей без кода
- **Воронки** - анализ конверсии по этапам
- **События** - детальный лог всех событий с фильтрами
- **Проекты** - управление несколькими сайтами

### 👥 Пользователи и роли
- **Admin** - полный доступ ко всем проектам и настройкам
- **Client** - доступ только к своим проектам

### 🔒 Безопасность
- CSRF защита всех форм
- Prepared statements (защита от SQL-инъекций)
- Хеширование паролей (bcrypt)
- Session fixation protection
- CORS с проверкой доменов
- Content Security Policy (CSP)
- .htaccess защита чувствительных файлов

## 🚀 Быстрый старт

### 1. База данных

```bash
mysql -u root -p -e "CREATE DATABASE analytics_service CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p analytics_service < sql/schema.sql
mysql -u root -p analytics_service < sql/seed_sample_data.sql  # опционально
```

### 2. Конфигурация

Отредактируйте `includes/config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'analytics_service');
define('DB_USER', 'your_user');
define('DB_PASS', 'your_password');
define('SITE_URL', 'https://your-domain.com');
```

### 3. Права доступа

```bash
chmod 600 includes/config.php
chmod 644 *.php admin/*.php api/*.php includes/*.php
```

### 4. Вход в админку

1. Откройте `https://your-domain.com/admin/login.php`
2. Войдите: `admin@example.com` / `admin123`
3. **Смените пароль немедленно!**

### 5. Установка трекера

1. Создайте проект в разделе **Projects**
2. Скопируйте код из **Tracking Code**
3. Вставьте перед `</body>` на вашем сайте

## 📁 Структура проекта

```
/workspace
├── admin/                  # Админ-панель
│   ├── index.php          # Дашборд
│   ├── login.php          # Вход
│   ├── projects.php       # Управление проектами
│   ├── goals.php          # Цели
│   ├── sessions.php       # Сессии
│   ├── events.php         # События
│   ├── funnels.php        # Воронки
│   ├── users.php          # Пользователи
│   ├── settings.php       # Настройки
│   └── tracking-code.php  # Код трекера
├── api/
│   └── track.php          # API для приёма событий
├── includes/
│   ├── config.php         # Конфигурация БД
│   ├── Database.php       # PDO singleton
│   └── auth.php           # Аутентификация
├── sql/
│   ├── schema.sql         # Схема БД
│   └── seed_sample_data.sql
├── assets/                # CSS/JS (CDN в демо)
├── bot.php               # JS-трекер (шаблон)
├── .htaccess             # Защита файлов
├── INSTALL.md            # Подробная инструкция
└── CHANGELOG.md          # История изменений
```

## 🗄️ Схема базы данных

### Таблицы
- `users` - пользователи (admin/client)
- `projects` - отслеживаемые сайты
- `goals` - цели конверсии
- `events` - сырые данные событий (партиционирована по годам)
- `sessions` - пользовательские сессии (партиционирована)
- `daily_stats` - агрегированная статистика
- `funnels` / `funnel_results` - воронки
- `api_keys` - API ключи
- `activity_log` - аудит действий

## 🔧 API

### POST /api/track.php

Принимает события от трекера:

```json
{
  "tracking_code": "abc123",
  "session_id": "sess_1234567890_xyz",
  "event_type": "page_view",
  "event_name": "page_load",
  "page_url": "https://example.com/page",
  "page_title": "Page Title",
  "referrer": "https://google.com",
  "device_type": "desktop",
  "timestamp": "2024-01-01T12:00:00Z",
  "utm_source": "google",
  "utm_medium": "cpc"
}
```

Ответ:
```json
{
  "success": true,
  "event_id": 12345,
  "message": "Event tracked successfully"
}
```

## 🛡️ Безопасность

### Реализованные меры
| Мера | Статус | Файл |
|------|--------|------|
| CSRF защита | ✅ | admin/login.php, auth.php |
| Session regeneration | ✅ | admin/login.php |
| Prepared statements | ✅ | Database.php |
| Password hashing | ✅ | auth.php |
| CORS validation | ✅ | api/track.php |
| CSP headers | ✅ | admin/.htaccess |
| File access restriction | ✅ | .htaccess везде |
| Input validation | ✅ | Все формы |

### Рекомендации для продакшена
- [ ] Включить HTTPS (Let's Encrypt)
- [ ] Сменить пароль админа
- [ ] Настроить fail2ban
- [ ] Включить бэкапы БД
- [ ] Мониторинг логов
- [ ] Обновлять PHP и зависимости

## 🐛 Известные ограничения

1. **GeoIP** - заглушка, нужен MaxMind GeoIP2 для определения страны/города
2. **Партиции БД** - требуют ежегодного добавления новых партиций
3. **Real-time** - дашборд показывает данные с задержкой ~1 мин
4. **Масштабирование** - для >1M событий/день нужен ClickHouse

## 📝 Changelog

См. [CHANGELOG.md](CHANGELOG.md)

## 📄 Лицензия

MIT License

## 🤝 Поддержка

Вопросы и баги: создавайте Issues на GitHub

---

**⚠️ Важно**: Это демо-версия. Для продакшена рекомендуется:
- Включить HTTPS
- Сменить все пароли по умолчанию
- Настроить мониторинг и бэкапы
- Провести security audit
