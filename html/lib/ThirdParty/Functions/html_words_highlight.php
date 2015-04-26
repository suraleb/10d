<?php
/**
 * "���������" ��������� ���� ��� ����������� ��������� ������.
 * ���� ��� ��������� ���� ��� ����� ���� � html ���� � ��������� �� ��������� ������.
 * ����� ������ ���� � ��������� UTF-8.
 * �������������� ����������, �������, ���������, �������� �����.
 *
 * @param  string     $s               �����, � ������� ������
 * @param  array      $words           ������ ��������� ����
 * @param  bool       $is_match_case   ������ � ������ �� ��������?
 * @param  string     $tpl             ������ ��� ������
 *
 * @license  http://creativecommons.org/licenses/by-sa/3.0/
 * @author   Nasibullin Rinat, http://orangetie.ru/
 * @charset  ANSI
 * @version  3.0.15
 */
function html_words_highlight($s, array $words = null, $is_match_case = false, $tpl = '<span class="highlight">%s</span>')
{
    #����������� ��� ������ ��������
    if (! strlen($s) || ! $words) return $s;

    #�����������
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
    #����������� ���������� ���. ��������� ��� "�������������" ���� � ������� ��� ��������� �������
    static $func_cache = array();
    $cache_id = md5(serialize(array($words, $is_match_case)));
    if (! array_key_exists($cache_id, $func_cache))
    {
        #����� � ��������� UTF-8 ��� ������ ������:
        static $re_utf8_letter = '#���������� �������:
                                  [a-zA-Z]
                                  #������� ������� (A-�):
                                  | \xd0[\x90-\xbf\x81]|\xd1[\x80-\x8f\x91]
                                  #+ ��������� ����� �� ���������:
                                  | \xd2[\x96\x97\xa2\xa3\xae\xaf\xba\xbb]|\xd3[\x98\x99\xa8\xa9]
                                  #+ �������� ����� �� �������� (��������� ��������):
                                  | \xc3[\x84\xa4\x87\xa7\x91\xb1\x96\xb6\x9c\xbc]|\xc4[\x9e\x9f\xb0\xb1]|\xc5[\x9e\x9f]
                                  ';
        #� ���������� PCRE ��� PHP \s - ��� ����� ���������� ������, � ������ ����� �������� [\x09\x0a\x0c\x0d\x20\xa0] ���, �� �������, [\t\n\f\r \xa0]
        #���� \s ������������ � ������������� /u, �� \s ���������� ��� [\x09\x0a\x0c\x0d\x20]
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

            #���. ��������� ��� ������ ����� � ������ �������� ��� ����:
            $re_word = preg_quote($word, '/');

            #���. ��������� ��� ������ ����� ���������� �� ��������:
            if (! $is_match_case && ! $is_digit)
            {
                #��� ��������� ����
                if (preg_match('/^[a-zA-Z]++$/', $word)) $re_word = '(?i:' . $re_word . ')';
                #��� ������� � ��. ����
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
            #����� ��������� �����:
            $re_words['words'] = '(?<!' . $re_utf8_letter . ')  #�������� �����
                                  (?:' . implode("\r\n|\r\n", $re_words['words']) . ')
                                  ';
        }
        if (@$re_words['digits'])
        {
            #����� ��������� �����:
            $re_words['digits'] = '(?<!\d)  #�������� �����
                                   (?:' . implode("\r\n|\r\n", $re_words['digits']) . ')
                                   ';
        }
        #d($re_words);

        $func_cache[$cache_id] = '/(?> #���������� PHP, Perl, ASP ���
                                       <([\?\%]) .*? \\1>

                                       #����� CDATA
                                     | <\!\[CDATA\[ .*? \]\]>

                                       #MS Word ���� ���� "<![if! vml]>...<![endif]>",
                                       #�������� ���������� ���� ��� IE ���� "<!--[if lt IE 7]>...<![endif]-->":
                                     | <\! (?>--)?
                                           \[
                                           (?> [^\]"\']+ | "[^"]*" | \'[^\']*\' )*
                                           \]
                                           (?>--)?
                                       >

                                       #�����������
                                     | <\!-- .*? -->

                                       #������ ���� ������ � ����������
                                     | <((?i:noindex|script|style|comment|button|map|iframe|frameset|object|applet))' . $re_attrs_fast_safe . '(?<!\/)>
                                         .*?
                                       <\/(?i:\\2)>

                                       #������ � �������� ����
                                     | <[\/\!]?+[a-zA-Z][a-zA-Z\d]*+' . $re_attrs_fast_safe . '>

                                       #html �������� (&lt; &gt; &amp;) (+ ��������� ������������ ��� ���� &amp;amp;nbsp;)
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