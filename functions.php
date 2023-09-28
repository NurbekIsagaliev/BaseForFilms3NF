<?php
//Извлекает информацию о фильмах из файлов
//Главная  функция
function extractFilmInfo($folderPath)
{
    $films = [];

    $files = scandir($folderPath);

    foreach ($files as $file) {
        if ($file !== "." && $file !== "..") {//Проверяем, что текущее имя файла не является ссылкой на текущий (`.`) 
            //или родительский (`..`) каталог
            // и если это не так, то получаем путь к файлу и его содержимое.
            $filePath = $folderPath . $file;
            $fileContents = file_get_contents($filePath);

            // Парсим страну производства
            $countryPattern = '/<td class="l"><h2>Страна<\/h2>:<\/td>\s*<td>(.*?)<\/td>/s';
            if (preg_match($countryPattern, $fileContents, $countryMatches)) {
                $fullCountryName = trim(strip_tags($countryMatches[1]));
            } else {
                $fullCountryName = 'нет информации о стране';
            }

            // Парсим актеров
            $actorsPattern = '/<h2>В ролях актеры<\/h2>:\s*(.*?)<\/span>/sU';
            if (preg_match($actorsPattern, $fileContents, $actorsMatches)) {
                $actorsText = strip_tags($actorsMatches[1]);
                $actorsText = str_replace('и другие', '', $actorsText);
                $actors = array_map('trim', explode(',', $actorsText));
            } else {
                $actors = [];
            }

            // Парсим жанры
            $genrePattern = '/<h2>Жанр<\/h2>:\s*<\/td>\s*<td>(.*?)<\/td>/s';
            if (preg_match($genrePattern, $fileContents, $genreMatches)) {
                $genreText = strip_tags($genreMatches[1]);
                $genre = array_map('trim', explode(',', $genreText));
            } else {
                $genre = [];
            }

            // Получаем имя фильма из имени файла
            $fileInfo = pathinfo($file);
            $fileNameParts = explode('-', $fileInfo['filename']);
            $filmName = implode('-', array_slice($fileNameParts, 1, -1));
            if (empty($filmName)) {
                $filmName = 'нет имени';
            }

            // Собираем информацию о фильме в массив
            $filmInfo = [
                'name' => $filmName,
                'production_countries' => $fullCountryName,
                'actors' => $actors,
                'genre' => $genre
            ];

            $films[] = $filmInfo;
        }
    }

    return $films;
}
//Функции для получения массивов уникальных стран производителей,уникальных  жанров,
//уникальных актеров, фильмов
//Получает уникальные страны производителей из массива фильмов
function getUniqueProductionCountries($filmsData) 
{
    $uniqueProductionCountries = []; // Создаем пустой массив для уникальных стран производителей
// Итерируемся по массиву фильмов
foreach ($filmsData as $film) {
    $countries = explode(', ', $film['production_countries']); // Разделяем страны, если их несколько
    foreach ($countries as $country) {
        $uniqueProductionCountries[$country] = true; // Используем страну в качестве ключа в хэш-массиве
    }
}
// Преобразуем ключи хэш-массива в массив уникальных стран и возвращаем его
return array_keys($uniqueProductionCountries);
}
//Получает уникальные жанры из массива фильмов
function getUniqueGenres($filmsData) 
{
    $uniqueGenres = []; // Создаем пустой массив для уникальных жанров
// Итерируемся по массиву фильмов
foreach ($filmsData as $film) {
    $genres = $film['genre'];  // Получаем массив жанров для текущего фильма
    foreach ($genres as $genre) {
        $trimmedGenre = trim($genre); // Удаляем пробелы в начале и конце строки
        $uniqueGenres[] = $trimmedGenre; // Добавляем жанр в массив уникальных жанров
    }
}
// удаляем дубликаты и преобразуем массив обратно в индексированный массив
$uniqueGenres = array_values(array_unique($uniqueGenres));
return $uniqueGenres;
}
//Получает уникальных актеров из массива фильмов
function getUniqueActors($filmsData)
{
    $actors = []; // Массив актеров
    // Собираем уникальных актеров в одномерный массив
    foreach ($filmsData as $film) {
        foreach ($film['actors'] as $actor) {
            $actors[] = $actor;
        }
    }
    // Используем array_unique для удаления дубликатов имен актеров, 
    // а затем array_values для переиндексации массива
    $uniqueActors = array_values(array_unique($actors));
    return $uniqueActors;
}
//Получает названия всех фильмов из массива фильмов
function getFilms($filmsData)
{
    $films = []; // Создаем пустой массив для хранения названий фильмов
    // Итерируемся по массиву фильмов
    foreach ($filmsData as $film) {
        $films[] = $film['name']; // Добавляем название фильма в массив $films
    }
    // Возвращаем массив, содержащий названия всех фильмов
    return $films;
}


//Проверочная  функция
//Проверяет, пуста ли указанная таблица в базе данных
function checkTableIsEmpty($pdo,$tableName)
{
   // Формируем SQL-запрос для подсчета количества записей в таблице.
   $sql = 'SELECT COUNT(*) FROM `' . $tableName . '`';
   // Подготавливаем SQL-запрос.
   $stmt = $pdo->prepare($sql);
   // Выполняем SQL-запрос.
   $stmt->execute();
   // Получаем количество записей (количество строк) в таблице.
   $rowCount = $stmt->fetchColumn();
   // Возвращаем количество записей. Если оно равно 0, то таблица пуста.
   return $rowCount;
}


//Функции для  вставки  в  бд уникальных жанров,фильмов,уникальных  актеров,
//уникальных  стран  производителей  в  соответствующие таблицы  genres,films,
//actors, unique_production_countries
// Вставляет уникальные жанры в таблицу genres
function insertUniqueGenres($pdo, $uniqueGenres)
{
     // Формируем SQL-запрос для вставки жанров.
     $sql = 'INSERT INTO `genres` (`name`) VALUES (:name)';
     // Подготавливаем SQL-запрос.
     $stmt = $pdo->prepare($sql);
     // Итерируемся по массиву уникальных жанров и вставляем каждый жанр.
     foreach ($uniqueGenres as $uniqueGenre) {
         // Привязываем параметр :name к значению уникального жанра.
         $stmt->bindParam(':name', $uniqueGenre);
         // Выполняем SQL-запрос для вставки жанра.
         $stmt->execute();
     }
}
//Вставляет информацию о фильмах в таблицу films,точнее  названия фильмов
function insertFilms($pdo, $films)
{
   // Формируем SQL-запрос для вставки фильмов.
   $sql = 'INSERT INTO `films` (`name`) VALUES (:name)';
   // Подготавливаем SQL-запрос.
   $stmt = $pdo->prepare($sql);
   
   foreach ($films as $film) {
       // Привязываем параметр :name к имени фильма.
       $stmt->bindParam(':name', $film);
       // Выполняем SQL-запрос для вставки фильма.
       $stmt->execute();
   }
}
//Вставляет уникальных актеров в таблицу actors
function insertUniqueActors($pdo, $uniqueActors)
{
     // Подготавливаем запрос для вставки
     $sql = 'INSERT INTO `actors` (`full_name`) VALUES (:full_name)';
     $stmt = $pdo->prepare($sql);
     foreach ($uniqueActors as $uniqueActor) {
         // Привязываем параметр к запросу и выполняем его для каждого актера
         $stmt->bindParam(':full_name', $uniqueActor);
         $stmt->execute();
     }
}
//Вставляет уникальные страны производителей в таблицу unique_production_countries
function insertUniqueProductionCountries($pdo, $uniqueProductionCountries)
{
    // Подготавливаем запрос для вставки
    $sqlInsert = 'INSERT INTO `unique_production_countries` (`country_name`) VALUES (:country_name)';
    $stmtInsert = $pdo->prepare($sqlInsert);
    // Итерируемся по уникальным странам производителям и вставляем их
    foreach ($uniqueProductionCountries as $country_name) {
        $stmtInsert->bindParam(':country_name', $country_name);
        $stmtInsert->execute();
    }
}

//Вставки данных  в  таблицу  по третьей  нормальной форме
//Вставляет соответствия между жанрами и фильмами в таблицу genres_films
function insertGenresFilms($pdo,$filmsData) {
   // Подготовить SQL-запрос для вставки записей в таблицу genres_films
   $stmt = $pdo->prepare("INSERT INTO genres_films (genre_id, film_id) VALUES (?, ?)");

   // Пройдтись по массиву filmsData и вставить соответствующие значения
   foreach ($filmsData as $film) {
       $filmName = $film['name'];
       $genreNames = $film['genre'];

       // Получить идентификатор фильма по его имени
       $stmtFilmId = $pdo->prepare("SELECT id FROM films WHERE name = ?");
       $stmtFilmId->execute([$filmName]);
       $filmId = $stmtFilmId->fetchColumn();

       // Пройдтись по жанрам и вставить соответствующие записи
       foreach ($genreNames as $genreName) {
           // Получить идентификатор жанра по его имени
           $stmtGenreId = $pdo->prepare("SELECT id FROM genres WHERE name = ?");
           $stmtGenreId->execute([$genreName]);
           $genreId = $stmtGenreId->fetchColumn();

           // Вставить запись в таблицу genres_films
           if ($filmId && $genreId) {
               $stmt->execute([$genreId, $filmId]);
           }
       }
   }
}
//Вставляет соответствия между актерами и фильмами в таблицу actors_films 
function insertActorsFilms($pdo,$filmsData) {
    // Подготовить SQL-запрос для вставки записей в таблицу actors_films
    $stmt = $pdo->prepare("INSERT INTO actors_films (actor_id, film_id) VALUES (?, ?)");

    // Пройдтись по массиву filmsData и вставить соответствующие значения
    foreach ($filmsData as $film) {
        $filmName = $film['name'];
        $actors = $film['actors'];

        // Получить идентификатор фильма по его имени
        $stmtFilmId = $pdo->prepare("SELECT id FROM films WHERE name = ?");
        $stmtFilmId->execute([$filmName]);
        $filmId = $stmtFilmId->fetchColumn();
    
           // Пройдтись по актерам  и вставить соответствующие записи
           foreach ($actors as $actor) {
            // Получить идентификатор актера по его имени
            $stmtActorId = $pdo->prepare("SELECT id FROM actors WHERE full_name = ?");
            $stmtActorId->execute([$actor]);
            $actorId = $stmtActorId->fetchColumn();
 
            // Вставить запись в таблицу genres_films
            if ($filmId && $actorId) {
                $stmt->execute([$actorId, $filmId]);
            }
        }
      
    }
   
}
//Вставляет соответствия между фильмами и уникальными странами производства в таблицу films_unique_production_countries
function insertFilmsUniqueProductionCountries($pdo,$filmsData) {
 // Подготовить SQL-запрос для вставки записей в таблицу films_unique_production_countries
 $stmt = $pdo->prepare("INSERT INTO films_unique_production_countries (film_id, country_id) VALUES (?, ?)");

 // Пройдтись по массиву filmsData и вставить соответствующие значения
 foreach ($filmsData as $film) {
     $filmName = $film['name'];
     $productionCountries = $film['production_countries'];
     // Преобразовать строку в массив, разделяя по запятым
    $productionCountriesArray = explode(', ', $productionCountries);

     // Получить идентификатор фильма по его имени
     $stmtFilmId = $pdo->prepare("SELECT id FROM films WHERE name = ?");
     $stmtFilmId->execute([$filmName]);
     $filmId = $stmtFilmId->fetchColumn();

     // Пройдтись по странам производства и вставить соответствующие записи
     foreach ($productionCountriesArray as $countryName) {
         // Получить идентификатор страны по её имени
         $stmtCountryId = $pdo->prepare("SELECT id FROM unique_production_countries WHERE country_name = ?");
         $stmtCountryId->execute([$countryName]);
         $countryId = $stmtCountryId->fetchColumn();

         // Вставить запись в таблицу films_unique_production_countries
         if ($filmId && $countryId) {
             $stmt->execute([$filmId, $countryId]);
         }
     }
 }

}


