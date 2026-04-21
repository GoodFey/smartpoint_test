# SmartPoint - Blog Monitoring System

Система мониторинга блогов с синхронизацией данных, управлением уведомлениями и обработкой сбоев.

## Используемые паттерны

- **Service Pattern** — бизнес-логика инкапсулирована в Services (BlogMonitorService, BlogSyncService)
- **Data Transfer Object (DTO)** — BlogDto, PostDto для передачи данных из API
- **Factory Pattern** — BlogApiClientFactory для создания клиентов под разные источники
- **Repository Pattern** — через Eloquent ORM и Model классы
- **Job Queue Pattern** — MonitorBlogJob обрабатывается через очередь с retry механизмом
- **Circuit Breaker Pattern** — защита от сбоев API в CircuitBreaker компоненте
- **Rate Limiter Pattern** — RateLimiter для обработки 429 ошибок
- **Dependency Injection** — через конструкторы Services и Jobs
- **Upsert Pattern** — для синхронизации постов без дублирования (Post::upsert)
- **Eloquent Relationships** — HasMany, BelongsTo для связи моделей

## Архитектура проекта

### Модели данных (наследование)

```
Blog (Model)
├── posts: HasMany -> Post
└── notifications: HasMany -> Notification

Post (Model)
└── blog: BelongsTo -> Blog

Notification (Model)
└── blog: BelongsTo -> Blog

User (Authenticatable)
```

**Ключевые поля:**
- **Blog**: `resource`, `external_id`, `title`, `author`, `rating`, `monitoring_interval`, `next_check_at`, `is_checking_active`
- **Post**: `blog_id`, `external_id`, `title`, `content`, `rating`, `reactions` (JSON)
- **Notification**: `blog_id`, `new_posts` (JSON), `created_at`

### Слой услуг (Services)

#### `BlogMonitorService`
- Основной сервис мониторинга блогов
- **Основной метод**: `monitor(Blog $blog): void`
  1. Создает API клиент через `BlogApiClientFactory`
  2. Получает данные блога и посты через API
  3. Синхронизирует данные через `BlogSyncService`
  4. Создает уведомление при наличии новых постов
  5. Логирует ошибки при сбое

#### `BlogSyncService`
- Синхронизация данных между API и базой
- **Основной метод**: `sync(Blog $blog, BlogDto $blogDto, array $postDtos): array`
  1. Синхронизирует метаданные блога
  2. Синхронизирует посты (upsert по `blog_id` + `external_id`)
  3. Удаляет посты, которые больше не существуют в API
  4. Обновляет `next_check_at` для следующей проверки
  5. Возвращает список новых постов для уведомления

### Очередные задачи (Jobs)

#### `MonitorBlogJob`
- Реализует `ShouldQueue`
- **Параметры retry**:
  - `tries = 3` попытки
  - `backoff = [3, 10, 30]` секунд между попытками
- **Логика**:
  1. Получает блог из БД по `blogId`
  2. Вызывает `BlogMonitorService::monitor()`
  3. Обрабатывает ошибки (429 Rate Limit, Circuit Breaker)
  4. Повторно выбрасывает исключение для retry механизма

### Интеграция API (Integrations)

Структура: `app/Integrations/BlogApi/`

- **Factories**: `BlogApiClientFactory` - создание клиентов для разных источников
- **Clients**: Реализация API клиентов для конкретных ресурсов
- **Contracts**: Интерфейсы для унификации
- **DTO**: `BlogDto`, `PostDto` - объекты передачи данных из API
- **RateLimiter**: Управление rate limiting (429 ошибки)
- **CircuitBreaker**: Паттерн circuit breaker для обработки сбоев API

### Поток обработки запроса (Data Flow)

```
1. Мониторинг инициируется
   └─> MonitorBlogJob создается в очереди

2. Job обработчик вызывает BlogMonitorService::monitor()
   └─> BlogMonitorService получает API клиент
       └─> BlogApiClientFactory::make($resource)
       └─> Получает BlogDto и PostDto[] из API

3. BlogSyncService::sync() обновляет БД
   └─> syncPostMetadata() - обновляет метаданные блога
   └─> syncPosts() - upsert постов, удаление удаленных
   └─> Обновляет next_check_at

4. При новых постах создается Notification
   └─> Notification с JSON массивом новых постов сохраняется

5. Обработка ошибок
   └─> Rate Limit (429) -> логирование + retry
   └─> Circuit Breaker OPEN -> логирование + retry
   └─> Другие ошибки -> логирование + retry (max 3 раза)
```

### База данных (Миграции)

**Таблицы:**
- `blogs` - основные данные блогов
- `posts` - посты блогов (FK -> blogs)
- `notifications` - уведомления о новых постах (FK -> blogs)
- `users` - пользователи (базовая)

**Индексы:**
- `blogs`: `(is_checking_active, next_check_at)` - для поиска блогов, требующих мониторинга
- `blogs`: `(resource, external_id)` - уникальность источника
