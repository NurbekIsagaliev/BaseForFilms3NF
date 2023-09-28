<?php
// Подключаем необходимые файлы и функции
include './functions.php'; // Подключение файла, где находятся функции
include './db.php'; // Подключение файла с настройками базы данных

$folderPath = 'films/'; // Путь к папке, где лежат файлы с информацией о фильмах
$pdo = getPDO(); // Создаем объект PDO для соединения с базой данных
// Извлекаем общую информацию о фильмах в массив
//в этом  массиве хранятся имя  фильма, страны производства, актеры,жанры
$filmsData = extractFilmInfo($folderPath); 

// Получаем  массив фильмов,точнее массив  имен фильмов
//Стоит  отметить что фильмы  которые хранятся у  меня  в  папке films все уникальные 
//Они уникальные и по параметрам 'name','actors' в  массиве  $filmsData то есть по имени фильма и 
//по составам актеров(по составам  массива  актеров),в  моей  папке нет одинаковых  имен  файлов
//В  массиве  $filmsData нет одинаковых фильма  у которых имя фильма  и  массив  актеров  совпадает 
//Это отражает  реальность, потому  что нету  таких фильмов где одинаковые имена и состав актеров тоже 
//одинаковый
$films = getFilms($filmsData);

// Получаем массив уникальных актеров
$uniqueActors = getUniqueActors($filmsData);

// Получаем массив уникальных стран производителей
$uniqueProductionCountries = getUniqueProductionCountries($filmsData);

// Получаем массив уникальных жанров
$uniqueGenres = getUniqueGenres($filmsData);

//Проверяем каждую таблицу и, если она пуста, вставляем соответствующие данные
//Вставка соответствующих данных  в таблицы  фильмов,уникальных актеров,уникальных стран производителей,
// уникальных жанров
if (checkTableIsEmpty($pdo, 'films') == 0) {
    insertFilms($pdo, $films);
}

if (checkTableIsEmpty($pdo, 'actors') == 0) {
    insertUniqueActors($pdo, $uniqueActors);
}

if (checkTableIsEmpty($pdo, 'unique_production_countries') == 0) {
    insertUniqueProductionCountries($pdo, $uniqueProductionCountries);
}

if (checkTableIsEmpty($pdo, 'genres') == 0) {
    insertUniqueGenres($pdo, $uniqueGenres);
}


//Проверяем пусты  ли таблицы 'таблицы-отношения'
//Вставка в таблиц  в  бд по третей  нормальной  форме 
if (checkTableIsEmpty($pdo, 'films_unique_production_countries') == 0) {
    insertFilmsUniqueProductionCountries($pdo,$filmsData);
}

if (checkTableIsEmpty($pdo, 'actors_films') == 0) {
    insertActorsFilms($pdo,$filmsData);
}

if (checkTableIsEmpty($pdo, 'genres_films') == 0) {
    insertGenresFilms($pdo,$filmsData);
}

$pdo = null;

print_r($filmsData);


















