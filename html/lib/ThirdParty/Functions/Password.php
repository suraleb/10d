<?php
/**
 * Библиотека с набором статических методов для работы с паролем.
 *
 * Возможности:
 *   * Проверка качества (сложности) пароля
 *   * Генерация качественного и стойкого к взлому пароля
 *   * Метод, который возвращает производные от пароля, чтобы иметь возможность авторизоваться
 *     независимо от раскладки клавиатуры (языка ввода) и нажатия клавиши [Caps Lock] или [Shift]
 *
 * @created  2009-08-10
 * @license  http://creativecommons.org/licenses/by-sa/3.0/
 * @author   Nasibullin Rinat, http://orangetie.ru/
 * @charset  UTF-8
 * @version  1.0.3
 */
class Password
{
    /**
     * Проверяет качество (сложность) пароля.
     *
     * Пароли, которые функция считает плохими:
     *   * длина < 6 или > 20 символов
     *   * символы выходят за пределы диапазона \x20-\x7e
     *     (русские буквы использовать нельзя, т.к. это может породить проблемы с кодировками)
     *   * не содержит одновременно латинские буквы и цифры
     *   * последовательность символов как на клавиатуре (123456, qwerty)
     *   * половинки, как на клавиатуре (qazxsw),
     *     повторные (werwer, 12341234) и "отражённые" (123321, qweewq) последовательности сюда включаются
     *   * процент уникальности символов меньше 46 (wwwfff, 000000)
     *
     * За основу был взят оригинальный скрипт Tronyx (http://forum.dklab.ru/viewtopic.php?t=7014),
     * который был модифицирован и улучшен.
     *
     * @param   string  $password          пароль
     * @param   bool    $is_check_digits   проверять существование цифр?
     * @param   bool    $is_check_letters  проверять существование латинских букв?
     * @return  bool                       TRUE, если пароль хороший и FALSE, если пароль слабый или произошла ошибка
     */
    public static function quality_check($password, $is_check_digits = true, $is_check_letters = true)
    {
        if (! assert('is_string($password) && is_bool($is_check_digits) && is_bool($is_check_letters)')) return false;

        #проверка минимальной длины и допустимых символов
        if (! preg_match('/^[\x20-\x7e]{6,20}$/sSX', $password)) return false;

        #проверка на цифры
        if ($is_check_digits && ! preg_match('/\d/sSX', $password)) return false;

        #проверка на латинские буквы
        if ($is_check_letters && ! preg_match('/[a-zA-Z]/sSX', $password)) return false;

        #последовательность символов как на клавиатуре (123456, qwerty, qazwsx, abcdef)
        $chars = '`1234567890-=\\'.  #второй ряд клавиш, [Shift] off
                 '~!@#$%^&*()_+|'.   #второй ряд клавиш, [Shift] on
                 'qwertyuiop[]asdfghjkl;\'zxcvbnm,./'.  #по горизонтали (расшир. диапазон)
                 'QWERTYUIOP{}ASDFGHJKL:"ZXCVBNM<>?'.   #по горизонтали (расшир. диапазон)
                 'qwertyuiopasdfghjklzxcvbnm'.  #по горизонтали
                 'QWERTYUIOPASDFGHJKLZXCVBNM'.  #по горизонтали
                 'qazwsxedcrfvtgbyhnujmikolp'.  #по диагонали
                 'QAZWSXEDCRFVTGBYHNUJMIKOLP'.  #по диагонали
                 'abcdefghijklmnopqrstuvwxyz'.  #по алфавиту
                 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';  #по алфавиту

        if (strpos($chars, $password)         !== false) return false;
        if (strpos($chars, strrev($password)) !== false) return false;

        $length = strlen($password);

        #половинки, как на клавиатуре (повторные и "отражённые" последовательности сюда включаются)
        if ($length > 5 && $length % 2 == 0)
        {
            $c = $length / 2;
            $left  = substr($password, 0, $c);  #первая половина пароля
            $right = substr($password, $c);     #вторая половина пароля

            $is_left  = (strpos($chars, $left)  !== false or strpos($chars, strrev($left))  !== false);
            $is_right = (strpos($chars, $right) !== false or strpos($chars, strrev($right)) !== false);

            if ($is_left && $is_right) return false;
        }

        #процент уникальности символов
        $k = strlen(count_chars($password, 3)) / $length;
        if ($k < 0.46) return false;

        return true;
    }

    /**
     * Генерирует качественный и стойкий к взлому пароль
     *
     * @param    int/digit  $length   длина строки на выходе
     * @param    string     $chars    алфавит, на основе которого создается псевдослучайная строка
     *                                по умолчанию алфавит состоит из [2-9a-zA-NP-Z]
     *                                (кроме 0O1l, т.к. эти буквы и цифры трудно различить визуально)
     * @return   string/bool          returns FALSE if error occured
     */
    public static function generate($length = 8, $chars = '23456789abcdefghijkmnopqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ')
    {
        if (! assert('is_int($length) || ctype_digit($length)')) return false;
        if (! assert('is_string($chars)')) return false;

        #36 ^ 6 = 2 176 782 336 unique combinations minimum
        if ($length < 6)
        {
            trigger_error('Minimum length of password is 6 chars, ' . $length . ' given!', E_USER_WARNING);
            return false;
        }
        $chars = count_chars($chars, $mode = 3); #gets unique chars
        $len = strlen($chars);
        if ($len < 36)
        {
            trigger_error('Minimum length of alphabet chars is 36 unique chars (e. g. [a-z\d] in regexp terms), ' . $len . ' given!', E_USER_WARNING);
            return false;
        }

        mt_srand((double)microtime() * 1000000);  #initialize
        $c = 0;
        do
        {
            for ($password = '', $i = 0; $i < $length; $i++) $password .= substr($chars, mt_rand(0, $len - 1), 1);
            $c++;
            if ($c > 100)
            {
                #protects for endless cycle
                trigger_error(__CLASS__ . '::' . __FUNCTION__ . ' endless cycle found!', E_USER_WARNING);
                return false;
            }
        }
        while (! self::quality_check($password));
        return $password;
    }

    /**
     * Возвращает производные от пароля, чтобы иметь возможность авторизоваться
     * независимо от раскладки клавиатуры (языка ввода) и нажатия клавиши [Caps Lock] или [Shift].
     * Кодировка символов -- UTF-8. Если русские буквы в пароле не используются, то любая однобайтовая.
     *
     * ЗАМЕЧАНИЕ
     *   Функция возвращает всего 4 варианта для подбора пароля и возможности успешной авторизации.
     *   Это слишком мало на фоне миллиардов комбинаций качественных паролей.
     *   Тем не менее, полезность этой функции может быть осуждена некоторыми критиками.
     *
     * ПРИМЕР ПРОИЗВОДНЫХ ПАРОЛЯ
     *   En, [Caps Lock] off: abCD1%
     *   En, [Caps Lock] on:  ABcd1%
     *   Ru, [Caps Lock] off: фиСВ1%
     *   Ru, [Caps Lock] on:  ФИсв1%
     *
     * РЕКОМЕНДАЦИИ
     *   Рекомендуется "солить" и хэшировать пароль для усиления безопасности, см. параметры функции.
     *   Рекомендуется проверять качество (сложность) новых паролей методом self::quality_check().
     *   Не рекомендуется использовать в пароле русские буквы, т. к. могут возникнуть проблемы при переходе с одной кодировки на другую.
     *
     * @param   string    $password     пароль
     * @param   string    $secret_key   "соль" -- секретный ключ, который подмешивается в пароль для усиления безопасности
     * @param   callback  $hash_func    функция хэширования (md5, sha1, ...)
     * @return  array/bool              комбинация преобразований пароля выдаёт 4 варианта в массиве
     *                                  возвращает FALSE в случае ошибки
     */
    public static function keyboard_forms($password, $secret_key = null, $hash_func = null)
    {
        if (! assert('is_string($password)')) return false;
        if (! assert('is_string($secret_key) || $secret_key === null')) return false;
        if (! assert('is_callable($hash_func) || $hash_func === null')) return false;

        $passwords = array(
            //$password,
            self::_keyboard_layout_conv($password, 'ru', 'en'),
            self::_keyboard_layout_conv($password, 'en', 'ru'),
        );

        for ($c = count($passwords), $i = 0; $i < $c; $i++)
        {
            $passwords[] = self::_keyboard_capslock_invert($passwords[$i]/*, $is_capslock_on = true*/);
            //$passwords[] = self::_keyboard_capslock_invert($passwords[$i], $is_capslock_on = false);
        }

        foreach ($passwords as $k => $v)
        {
            if (is_string($secret_key)) $passwords[$k] .= $secret_key;
            if ($hash_func !== null)    $passwords[$k] = call_user_func($hash_func, $passwords[$k]);
        }

        return $passwords;
    }

    /**
     * Конвертирует символы нижнего регистра в верхний и наоборот.
     * Кодировка символов -- UTF-8.
     *
     * @param    string   $s                текст в кодировке UTF-8
     * @return   string
     */
    private static function _keyboard_capslock_invert($s/*, $is_capslock_on = true*/)
    {
        //if (! assert('is_string($s) && is_bool($is_capslock_on)')) return false;
        if (! assert('is_string($s)')) return false;

        Func::load('utf8_convert_case');
        $trans = utf8_convert_case('', $mode = -1);

        /*
        #DEPRECATED этот блок лишний
        #if [Caps Lock] off and [Shift] on
        if (! $is_capslock_on) $trans += array(
            #various ANSI chars
            #CASE_UPPER => case_lower
            '~' => '`',
            '!' => '1',
            '@' => '2',
            '#' => '3',
            '$' => '4',
            '%' => '5',
            '^' => '6',
            '&' => '7',
            '*' => '8',
            '(' => '9',
            ')' => '0',
            '_' => '-',
            '+' => '=',
            '|' => '\\',
            '{' => '[',
            '}' => ']',
            ':' => ';',
            '"' => "'",
            '<' => ',',
            '>' => '.',
            '?' => '/',
        );
        */

        #add invert transform: case_lower => CASE_UPPER
        $trans += array_flip($trans);
        return strtr($s, $trans);
    }

    /**
     * Конвертирует текст из одной раскладки клавиатуры в другую.
     * Кодировка символов -- UTF-8.
     *
     * Globalize your On Demand Business : logical keyboard layout registry index
     * Keyboard layouts for countries and regions around the world.
     * http://www-306.ibm.com/software/globalization/topics/keyboards/registry_index.jsp
     *
     * @param    string   $s       текст в кодировке UTF-8
     * @param    string   $input   раскладка на входе  (en, ru)
     * @param    string   $output  раскладка на выходе (en, ru)
     * @return   string/bool       строка в случае успеха и FALSE в случае ошибки
     */
    private static function _keyboard_layout_conv($s, $input, $output)
    {
        if (! assert('is_string($s) && is_string($input) && is_string($output)')) return false;

        #раскладка клавиатуры для русского и английского языка
        static $trans_en_ru = array(
            #[CapsLock] off
            '`' => 'ё',
            'q' => 'й',
            'w' => 'ц',
            'e' => 'у',
            'r' => 'к',
            't' => 'е',
            'y' => 'н',
            'u' => 'г',
            'i' => 'ш',
            'o' => 'щ',
            'p' => 'з',
            '[' => 'х',
            ']' => 'ъ',
            'a' => 'ф',
            's' => 'ы',
            'd' => 'в',
            'f' => 'а',
            'g' => 'п',
            'h' => 'р',
            'j' => 'о',
            'k' => 'л',
            'l' => 'д',
            ';' => 'ж',
            '\'' => 'э',
            'z' => 'я',
            'x' => 'ч',
            'c' => 'с',
            'v' => 'м',
            'b' => 'и',
            'n' => 'т',
            'm' => 'ь',
            ',' => 'б',
            '.' => 'ю',
            '/' => '.',

            #[CapsLock] on
            '~' => 'Ё',
            '@' => '"',
            '#' => '№',
            '$' => ';',
            '^' => ':',
            '&' => '?',
            '|' => '/',
            'Q' => 'Й',
            'W' => 'Ц',
            'E' => 'У',
            'R' => 'К',
            'T' => 'Е',
            'Y' => 'Н',
            'U' => 'Г',
            'I' => 'Ш',
            'O' => 'Щ',
            'P' => 'З',
            '{' => 'Х',
            '}' => 'Ъ',
            'A' => 'Ф',
            'S' => 'Ы',
            'D' => 'В',
            'F' => 'А',
            'G' => 'П',
            'H' => 'Р',
            'J' => 'О',
            'K' => 'Л',
            'L' => 'Д',
            ':' => 'Ж',
            '"' => 'Э',
            'Z' => 'Я',
            'X' => 'Ч',
            'C' => 'С',
            'V' => 'М',
            'B' => 'И',
            'N' => 'Т',
            'M' => 'Ь',
            '<' => 'Б',
            '>' => 'Ю',
            '?' => ',',
        );
        if ($input === 'en' && $output === 'ru') return strtr($s, $trans_en_ru);
        if ($input === 'ru' && $output === 'en') return strtr($s, array_flip($trans_en_ru));
        trigger_error('Unsupported input and output keyboard layouts!', E_USER_WARNING);
        return false;
    }
}
?>
