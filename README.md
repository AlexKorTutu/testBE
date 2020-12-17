# testBE



## Подготовка к локальному запуску:
стартуем rabbitmq:
```bash
docker run -it --rm --name rabbitmq -p 5672:5672 -p 15672:15672 rabbitmq:3-management
```

стартуем mysql:
```bash
docker run -e MYSQL_ROOT_PASSWORD=1234 -d -p 3306:3306 mysql:5.7.13
```

Создаем таблицы в БД
```bash
cd src/setup
php initBD.php
```

Стартуем сервис
```bash
cd src/public
php -S localhost:8080
```

Запускаем сколько нам надо воркеров
```bash
cd src/workers
php worker.php
```

Шлем запросы и кайфуем