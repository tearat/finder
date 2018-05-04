<?php

$target = $_POST['target'];
$check_details = $_POST['details'];
$check_few = $_POST['few'];
$check_maybe = $_POST['maybe'];

// Ban-list
$ban_dirs = array( "_smarty", "_app", "_img", "_style", "_views" );
$ban_files = array( "index.php", "README.md" );

//    $iterations - это INT, который считает число проходов по файлам. Он нужен только в научных целях
//    $subdirs - это массив, куда мы поместим все найденные директории
//    $files - это массив, куда мы поместим ВСЕ найденные файлы
//    $myfiles - это массив, куда мы поместим ПОДХОДЯЩИЕ нам файлы, он работает при выборе поиска нескольких файлов
//    $maybefiles - это массив, куда мы поместим ВОЗМОЖНЫЕ фалйы, имена которых содержат запрос частично
$iterations = 0;
$subdirs = array();
$files = array();
$myfiles = array();
$maybefiles = array();
$results = "";

//    В массив ДИРЕКТОРИЙ мы сразу помещаем элемент START с root-директорией,
//    чтобы использовать её в первом цикле форыча
$subdirs[0] = getcwd();

//    Здесь проверяем, введено ли что-нибудь вообще в INPUT
if ( $target != "" ) 
{
    finder();
} 
else 
{
    text( "Не задано имя файла.", "h2" );
}

function text( $text, $param=false, $dash=false )
{
    global $results;
    if ( $param=="p" )
    {
        $result = "<p><B>$text</B></p>";
    }
    if ( $param=="h2" )
    {
        $result = "<h2>$text</h2>";
    }
    if ($dash)
    {
        $result = "<hr>".$result;
    }
    $results .= $result;
}

function finder() 
{
    global $ban_dirs;
    global $ban_files;
    
    global $check_details;
    global $check_few;
    global $check_maybe;
    global $target;

    global $subdirs;
    global $files;
    global $iterations;
    global $myfiles;
    global $maybefiles;

    if ($check_details == true) 
    {
        text( "POST-запрос поиска: $target", "p", true );
        text( "Выводить подробности: $check_details", "p" );
        text( "Искать несколько файлов: $check_few", "p" );
        text( "Искать возможные файлы: $check_details", "p" );
    }

    while (!empty($subdirs))    //Повторяем до тех, пока массив директорий не станет пустым
    {  
        foreach ($subdirs as $dir)  //Перебор каждого каталога в списке $subdirs  
        {     
            if ($check_details == true) 
            { 
                text( "Скрипт изучает каталог $dir", "p", true );
            }
            $key = array_search($dir, $subdirs);     //KEY это индекс текущего каталога в массиве 
            chdir($subdirs[$key]);                          //Директория для поиска меняется на текущий каталог форыча
            unset($subdirs[$key]);                         //Текущий каталог исключается из массива, чтобы не было повторений

            foreach (glob('*') as $file)    //Глоб ищет каждый файл или папку в текущей директории
            {          
                $iterations++;
                if (is_dir($file))  //Если это директория, то она записывается в массив $subdirs
                {
                    if ( !in_array($file, $ban_dirs) )   // Директории из бан-листа будут проигнорированы
                    {
                        if ($check_details == true) 
                        { 
                            text( "Обнаружен каталог <U>$file</U>", "p" );
                        }
                        $subdirs[] = $dir.'\\'.$file;   // Здесь была ошибка с именем ключа, который должен быть уникальным (4.05.18)
                    }
                }
                else    //Если это файл, то он записывается в массив $files
                {
                    if ( !in_array($file, $ban_files) )   // Файлы из бан-листа будут проигнорированы
                    {
                        // Если происходит совпадение текущего файла и запроса,
                        // то мы действуем, в зависимости от выбранного режима поиска
                        if ($check_details == true) 
                        { 
                            text("Обнаружен файл <A href='".linkgen(getcwd()).'\\'.$file."' target='_blank'>".$file."</A>", "p"); 
                        }
                        if ($file == $target)       // Прямое совпадение по имени
                        {
                            if ($check_details == true) 
                            { 
                                text( "Требуемый файл <A href='".linkgen(getcwd().'\\'.$file)."' target='_blank'>".$file."</A> обнаружен", "p" );
                            }
                            if ($check_few == true)     // Если мы ищем несколько файлов, то кладём их в отдельный массив
                            {
                                $myfiles[] = getcwd().'\\'.$file;
                            } 
                            else    // Если мы ищем один файл, то выходим из циклов    
                            {
                                $files[$file] = getcwd().'\\'.$file;
                                break 3;
                            }
                        }

                        // Этот блок находит неявные совпадения в именах файлов
                        // и пишет их в отдельный массив MAYBEFILES
                        // При этом он изначально проверяет, не происходит ли прямого совпадения,
                        // чтобы случайно не записать файл в оба массива
                        // Блок включается через чекбокс $check_maybe 
                        if ($check_maybe == true) 
                        {
                            if ($file !== $target) 
                            {
                                $pos = strpos($file, $target);
                                if (gettype($pos) == 'integer') 
                                {
                                    if ($check_details == true) 
                                    { 
                                        text( "<A href='".linkgen(getcwd().'\\'.$file)."' target='_blank'>".$file."</A> может быть подходящим файлом", "p" );
                                    }
                                    $maybefiles[] = getcwd().'\\'.$file;
                                }
                            }
                        }

                        // В любом случае, файл пишется в массив $files, чтобы в случае одинарного поиска
                        // мы могли установить его напличие при помощи if($files[$target])
                        $files[$file] = getcwd().'\\'.$file;
                    }
                }
            }
        }
    }
    if ($check_details == true) 
    { 
        text( "Всего найдено объектов: $iterations", "p", true );
    }

    // ==========================================
    // Ниже выводятся результаты поиска

    if ($files[$target])    // Проверяем, есть ли в массиве $files нужный нам файл
    {
        if ($check_few == true and sizeof($myfiles)>1)      //Если файлов несколько
        { 
            text( "Найдено несколько файлов:", "h2", true );
            foreach ($myfiles as $file) 
            {
                text( "<A href='".linkgen($file)."' target='_blank'>$file</A>", "p" );
            }
        } 
        else    //Если файл один
        { 
            text( "Файл был найден по адресу:", "h2", true );
            text( "<A href='".linkgen($files[$target])."' target='_blank'>$files[$target]</A>", "p" );
        }
    } 
    else    // Если ничего нет
    {
        text( "Файл не найден", "h2", true );
    }
    // Далее выводим список ВОЗМОЖНЫХ файлов
    // Смотрим, есть ли что-то в массиве $maybefiles
    if ( !empty($maybefiles) ) 
    {
        text( "Следующие файлы могут быть подходящими:", "h2", true );
        foreach ($maybefiles as $file) 
        {
            text( "<A href='".linkgen($file)."' target='_blank'>$file</A>", "p" );
        } 
    }
}

// Эта функция генерирует ссылки
// А если точнее, то обрезает их начало
function linkgen($file) 
{
    $link = str_replace("\\", '/', $file);

    //При хосте на OpenServer = 20
    //При хосте на XAMPP = 23
    //При хосте на CMD = 46
    return substr($link, 20);
}

?>