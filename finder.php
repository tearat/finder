<?php
//     header('Content-Type: text/html; charset=Windows-1251');
    header('Content-Type: text/html; charset=utf-8');
    include 'head.html';

    $target = strval($_POST['input']);
    
    $check_details = $_POST['details'];
    $check_few = $_POST['few'];
    $check_maybe = $_POST['maybe'];

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

    //    В массив ДИРЕКТОРИЙ мы сразу помещаем элемент START с root-директорией,
    //    чтобы использовать её в первом цикле форыча
    $subdirs[0] = getcwd();

    // Здесь проверяем, введено ли что-нибудь вообще в INPUT
    if (isset($target)) 
    {
        finder();
    } else {
        echo "<h2>Не задано имя файла.</h2>";
    }

    function finder() {
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
            echo "<HR><p><b>POST-запрос поиска:</b> $target</p>";
            echo "<p><b>Выводить подробности:</b> $check_details</p>";
            echo "<p><b>Искать несколько файлов:</b> $check_details</p>";
            echo "<p><b>Искать возможные файлы:</b> $check_details</p>";
        }
        
        //Повторяем цикл до тех, пока массив директорий не станет пустым
        while (!empty($subdirs)) {  
            //Перебор каждого каталога в списке $subdirs  
            foreach ($subdirs as $dir) {     
                if ($check_details == true) { echo "<HR><p><B>Скрипт изучает каталог </B>$dir</p>"; }
                $key = array_search($dir, $subdirs);    //KEY это индекс текущего каталога в массиве 
                chdir($subdirs[$key]);                  //Директория для поиска меняется на текущий каталог форыча
                unset($subdirs[$key]);                  //Текущий каталог исключается из массива, чтобы не было повторений
                
                //Глоб ищет каждый файл или папку в текущей директории
                foreach (glob('*') as $file) {          
                    $iterations++;
                    //Если это директория, то она записывается в массив $subdirs
                    if (is_dir($file)) {
                        if ($check_details == true) { echo "<p>Обнаружен каталог <U>$file</U> "; }
//                        $subdirs[$file] = getcwd().'\\'.$file;
                        $subdirs[$file] = $dir.'\\'.$file;
                    }
                    //Если это файл, то он записывается в массив $files
                    else {                              
                        // Если происходит совпадение текущего файла и запроса,
                        // то мы действуем, в зависимости от выбранного режима поиска
                        if ($check_details == true) { echo "<p>Обнаружен файл <A href='".linkgen(getcwd()).'\\'.$file."' target='_blank'>".$file."</A>"; }
                        if ($file == $target) {
                            if ($check_details == true) { echo "<p>Требуемый файл <A href='".linkgen(getcwd().'\\'.$file)."' target='_blank'>".$file."</A> обнаружен</p>"; }
                            // Если мы ищем несколько файлов, то кладём их в отдельный массив
                            if ($check_few == true) {
                                $myfiles[] = getcwd().'\\'.$file;
                            // Если мы ищем один файл, то выходим из циклов    
                            } else {
                                $files[$file] = getcwd().'\\'.$file;
                                break 3;
                            }
                        }

                        // Этот блок находит неявные совпадения в именах файлов
                        // и пишет их в отдельный массив MAYBEFILES
                        // При этом он изначально проверяет, не происходит ли прямого совпадения,
                        // чтобы случайно не записать файл в оба массива
                        // Блок включается через чекбокс $check_maybe 
                        if ($check_maybe == true) {
                            if ($file !== $target) {
                                $pos = strpos($file, $target);
                                if (gettype($pos) == 'integer') {
                                    if ($check_details == true) { echo "<p><A href='".linkgen(getcwd().'\\'.$file)."' target='_blank'>".$file."</A> может быть подходящим файлом</p>"; }
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
        if ($check_details == true) { echo '<HR><p>Всего найдено объектов: '.$iterations.'</p>'; }
        
        // Ниже выводятся результаты поиска
        echo '<HR>';
        // Проверяем, есть ли в массиве $files нужный нам файл
        if ($files[$target]) {
            //Если файлов несколько
            if ($check_few == true and sizeof($myfiles)>1) { 
                $result = "<h2>Найдено несколько файлов:</h2>";
                foreach ($myfiles as $file) {
                    $result .= "<A href='".linkgen($file)."' target='_blank'><H3>$file</H3></A>";
                }
                echo $result;
            //Если файл один
            } else { 
                $result = "<h2>Файл был найден по адресу:</h2>";
                $result .= "<A href='".linkgen($files[$target])."' target='_blank'><H3>$files[$target]</H3></A>";
                echo $result;
                }
        // Если ничего нет
        } else {
            echo "<h2>Файл не найден</h2>";
        }
        // Далее выводим список ВОЗМОЖНЫХ файлов
        // Смотрим, есть ли что-то в массиве $maybefiles
        if (!empty($maybefiles)) {
            echo '<HR>';
            $result = "<h2>Следующие файлы могут быть подходящими:</h2>";
            foreach ($maybefiles as $file) {
                    $result .= "<A href='".linkgen($file)."' target='_blank'><H3>$file</H3></A>";
                } 
            echo $result;
        }
    }
    echo '<BR>';

    // Эта функция генерирует ссылки
    // А если точнее, то обрезает их начало
    function linkgen($file) {
        $a = $file;
        $b = str_replace("\\", '/', $a);
        
        //При хосте на OpenServer = 20
        //При хосте на XAMPP = 23
        //При хосте на CMD = 46
        $c = substr($b, 20);
        return $c;
    }

    include 'end.html';