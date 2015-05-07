<?php
/**
 * "Подсветка" найденных слов для результатов поисковых систем.
 * Ищет все вхождения цифр или целых слов в html коде и обрамляет их заданными тагами.
 * Текст должен быть в кодировке UTF-8.
 * Поддерживаются английский, русский, татарский, турецкий языки.
 *
 * @param  string     $s               текст, в котором искать
 * @param  array      $words           массив поисковых слов
 * @param  bool       $is_match_case   искать с учётом от регистра?
 * @param  string     $tpl             шаблон для замены
 *
 * @license  http://creativecommons.org/licenses/by-sa/3.0/
 * @author   Nasibullin Rinat, http://orangetie.ru/
 * @charset  ANSI
 * @version  3.0.15
 */
function html_words_highlight($s, array $words = null, $is_match_case = false, $tpl = '<span class="highlight">%s</span>')
{
    #оптимизация для пустых значений
    if (! strlen($s) || ! $words) return $s;

    #оптимизация
    #{{{
    
    $s2 = UTF8::convert_case($s, CASE_LOWER);
    foreach ($words as $k => $word)
    {
        $word = UTF8::convert_case(trim($word, "\x00..\x20\x7f*"), CASE_LOWER);
        if ($word == '' || strpos($s2, $word) === false) unset($words[$k]);
    }
    if (! $words) return $s;
    #}}}

    #d($words);
    #кеширование построения рег. выражения для "подсвечивания" слов в функции при повторных вызовах
    static $func_cache = array();
    $cache_id = md5(serialize(array($words, $is_match_case)));
    if (! array_key_exists($cache_id, $func_cache))
    {
        #буквы в кодировке UTF-8 для разных языков:
        static $re_utf8_letter = '#английский алфавит:
                                  [a-zA-Z]
                                  #русский алфавит (A-я):
                                  | \xd0[\x90-\xbf\x81]|\xd1[\x80-\x8f\x91]
                                  #+ татарские буквы из кириллицы:
                                  | \xd2[\x96\x97\xa2\xa3\xae\xaf\xba\xbb]|\xd3[\x98\x99\xa8\xa9]
                                  #+ турецкие буквы из латиницы (татарский латиница):
                                  | \xc3[\x84\xa4\x87\xa7\x91\xb1\x96\xb6\x9c\xbc]|\xc4[\x9e\x9f\xb0\xb1]|\xc5[\x9e\x9f]
                                  ';
        #в библиотеке PCRE для PHP \s - это любой пробельный символ, а именно класс символов [\x09\x0a\x0c\x0d\x20\xa0] или, по другому, [\t\n\f\r \xa0]
        #если \s используется с модификатором /u, то \s трактуется как [\x09\x0a\x0c\x0d\x20]
        #regular expression for tag attributes
        #correct processes dirty and broken HTML in a singlebyte or multibyte UTF-8 charset!
        static $re_attrs_fast_safe =  '(?![a-zA-Z\d])  #statement, which follows after a tag
                                       #correct attributes
                                       (?>
                                           [^>"\']++
                                         | (?<=[\=\x20\r\n\t]|\xc2\xa0) "[^"]*+"
                                         | (?<=[\=\x20\r\n\t]|\xc2\xa0) \'[^\']*+\'
                                       )*
                                       #incorrect attributes
                                       [^>]*+';

        $re_words = array();
        foreach ($words as $word)
        {
            if ($is_mask = (substr($word, -1) === '*')) $word = rtrim($word, '*');

            $is_digit = ctype_digit($word);

            #рег. выражение для поиска слова с учётом регистра или цифр:
            $re_word = preg_quote($word, '/');

            #рег. выражение для поиска слова НЕЗАВИСИМО от регистра:
            if (! $is_match_case && ! $is_digit)
            {
                #для латинских букв
                if (preg_match('/^[a-zA-Z]++$/', $word)) $re_word = '(?i:' . $re_word . ')';
                #для русских и др. букв
                else
                {
                    
                    $re_word_cases = array(
                        'lowercase' => UTF8::convert_case($re_word, CASE_LOWER),  #word
                        'ucfirst'   => UTF8::ucfirst($re_word),                   #Word
                        'uppercase' => UTF8::convert_case($re_word, CASE_UPPER),  #WORD
                    );
                    $re_word = '(?:' . implode('|', $re_word_cases) . ')';
                }
            }

            #d($re_word);
            if ($is_digit) $append = $is_mask ? '\d*+' : '(?!\d)';
            else $append = $is_mask ? '(?>' . $re_utf8_letter . ')*' : '(?! ' . $re_utf8_letter . ')';
            $re_words[$is_digit ? 'digits' : 'words'][] = $re_word . $append;
        }#foreach
        #d($re_words);

        if (@$re_words['words'])
        {
            #поиск вхождения слова:
            $re_words['words'] = '(?<!' . $re_utf8_letter . ')  #просмотр назад
                                  (?:' . implode("\r\n|\r\n", $re_words['words']) . ')
                                  ';
        }
        if (@$re_words['digits'])
        {
            #поиск вхождения цифры:
            $re_words['digits'] = '(?<!\d)  #просмотр назад
                                   (?:' . implode("\r\n|\r\n", $re_words['digits']) . ')
                                   ';
        }
        #d($re_words);

        $func_cache[$cache_id] = '/(?> #встроенный PHP, Perl, ASP код
                                       <([\?\%]) .*? \\1>

                                       #блоки CDATA
                                     | <\!\[CDATA\[ .*? \]\]>

                                       #MS Word таги типа "<![if! vml]>...<![endif]>",
                                       #условное выполнение кода для IE типа "<!--[if lt IE 7]>...<![endif]-->":
                                     | <\! (?>--)?
                                           \[
                                           (?> [^\]"\']+ | "[^"]*" | \'[^\']*\' )*
                                           \]
                                           (?>--)?
                                       >

                                       #комментарии
                                     | <\!-- .*? -->

                                       #парные таги вместе с содержимым
                                     | <((?i:noindex|script|style|comment|button|map|iframe|frameset|object|applet))' . $re_attrs_fast_safe . '(?<!\/)>
                                         .*?
                                       <\/(?i:\\2)>

                                       #парные и непарные таги
                                     | <[\/\!]?+[a-zA-Z][a-zA-Z\d]*+' . $re_attrs_fast_safe . '>

                                       #html сущности (&lt; &gt; &amp;) (+ корректно обрабатываем код типа &amp;amp;nbsp;)
                                     | &(?> [a-zA-Z][a-zA-Z\d]++
                                          | \#(?> \d{1,4}+
                                                | x[\da-fA-F]{2,4}+
                                              )
                                        );
                                   )+
                                   \K

                                   | ' . implode("\r\n|\r\n", $re_words) . '
                                  /sxSX';
        #d($func_cache[$cache_id]);
    }
    $GLOBALS['HTML_WORDS_HIGHLIGHT_TPL'] = $tpl;
    $s = preg_replace_callback($func_cache[$cache_id], '_html_words_highlight_callback', $s);
    unset($GLOBALS['HTML_WORDS_HIGHLIGHT_TPL']);
    return $s;
}

function _html_words_highlight_callback(array &$m)
{
    return (strlen($m[0]) > 0) ? sprintf($GLOBALS['HTML_WORDS_HIGHLIGHT_TPL'], $m[0]) : $m[0];
}

?>