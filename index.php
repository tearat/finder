<?php

// ========================================================

// Приходящие запросы
$target = $_POST['target'];
$check_details = $_POST['details'];

// Ban-list, эти файлы и каталоги не нужно изучать скриптом
$ban_dirs = array( "_smarty", "_app", "_img", "_style", "_views" );
$ban_files = array( "index.php", "README.md" );

//    $iterations - это int, который считает число проходов по файлам. Он нужен только в научных целях
//    $subdirs - это массив, куда мы поместим все найденные директории
//    $myfiles - это массив, куда мы поместим результаты поиска
$iterations = 0;
$subdirs = array();
$myfiles = array();
$results = "";

//    В массив $subdirs мы сразу добавляем root, чтобы использовать его в первом цикле
$subdirs[0] = getcwd();

//    Здесь проверяем, введено ли что-нибудь вообще в input
if ( $target != "" ) 
{
    finder();
} 
else 
{
    text( "Не задано имя файла.", "h2", true );
}

// ========================================================

// Эта функция создаёт записи в переменной с результатами

function text( $text, $param=false, $dash=false )
{
    global $results;
    if ( $param=="p" )
    {
        $result = "<p>$text</p>";
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

// ========================================================

// Эта функция ищет

function finder() 
{
    global $ban_dirs;
    global $ban_files;
    
    global $target;
    global $check_details;

    global $iterations;
    global $subdirs;
    global $myfiles;

    if ($check_details == true) 
    {
        text( "POST-запрос поиска: $target", "p", true );
        text( "Выводить подробности: $check_details", "p" );
    }

    while (!empty($subdirs))    // Повторяем до тех, пока массив директорий не станет пустым
    {  
        foreach ($subdirs as $dir)  // Перебор каждого каталога в списке $subdirs, в первом цикле тут только root
        {     
            if ($check_details == true) 
            { 
                text( "Скрипт изучает каталог ".linkgen($dir), "p", true );
            }
            $key = array_search($dir, $subdirs);     // $key это индекс текущего каталога в массиве 
            chdir($subdirs[$key]);                          // Директория для поиска меняется на текущий каталог цикла
            unset($subdirs[$key]);                         // Этот каталог исключается из массива, чтобы не было повторений

            foreach (glob('*') as $file)    // Глоб ищет каждый файл или папку в текущей директории
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
                        $subdirs[] = $dir.'\\'.$file;   // Здесь была ошибка ($subdirs[$file]), но я не уверен (4.05.18)
                    }
                }
                else    //Если это файл, то он изучается на совпадение с запросом
                {
                    if ( !in_array($file, $ban_files) )   // Файлы из бан-листа будут проигнорированы
                    {
                        if ($check_details == true) 
                        { 
                            text("Обнаружен файл <A href='".linkgen(getcwd()).'\\'.$file."' target='_blank'>".$file."</A>", "p"); 
                        }
                        
                        foreach (glob('*'.$target.'*') as $eq)   // Глоб ищет совпадения как частичные, так и полные
                        {
                            if ($check_details == true) 
                            { 
                                text( "Подходящий файл <A href='".linkgen(getcwd().'\\'.$file)."' target='_blank'>".$file."</A> обнаружен", "p" );
                            }
                            $myfiles[] = linkgen(getcwd().'\\'.$eq);     // В массив добавляется полная ссылка
                        }
                    }
                }
            }
        }
    }
    
    // ==========================================
    
    if ($check_details == true) 
    { 
        text( "Всего найдено объектов: $iterations", "p", true );
    }

    // ==========================================

    if ( sizeof($myfiles)>0 )
    {
        text( "Найденные файлы:", "h2", true );
        foreach ($myfiles as $file) 
        {
            text( "<A href='".linkgen($file)."' target='_blank'>$file</A>", "p" );
        }
    }
    else    // Если ничего нет
    {
        text( "Файл не найден", "h2", true );
    }
}

// Эта функция генерирует ссылки
// А если точнее, то обрезает их начало
function linkgen($file) 
{
    $domain = "finder.am";
    $count = count($domain)-1;
    
    $link = str_replace( "\\", '/', $file );
    
    $pos = strpos( $link, $domain );
    
    return substr( $link, $pos+$count );
}

?>

<?php

require_once "_views/index.html";

?>