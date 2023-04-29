<?php

namespace Elenyum\ApiDocBundle\Util;

use Transliterator;

class Slugify
{
    public static function create(string $text): string
    {
        // replace non letter or digits by -
        $text = preg_replace('~[^\\pL\d]+~u', '-', $text);

        // trim
        $text = trim($text, '-');

        // transliterate
        if (extension_loaded('intl')) {
            $translit = Transliterator::create('Any-Latin; Latin-ASCII');
            $text = $translit->transliterate($text);
        } else {
            $map = array(
                'а' => 'a',
                'б' => 'b',
                'в' => 'v',
                'г' => 'g',
                'д' => 'd',
                'е' => 'e',
                'ж' => 'zh',
                'з' => 'z',
                'и' => 'i',
                'й' => 'y',
                'к' => 'k',
                'л' => 'l',
                'м' => 'm',
                'н' => 'n',
                'о' => 'o',
                'п' => 'p',
                'р' => 'r',
                'с' => 's',
                'т' => 't',
                'у' => 'u',
                'ф' => 'f',
                'х' => 'h',
                'ц' => 'ts',
                'ч' => 'ch',
                'ш' => 'sh',
                'щ' => 'sht',
                'ъ' => 'y',
                'ы' => 'y',
                'ь' => '\'',
                'ю' => 'yu',
                'я' => 'ya',
                'А' => 'A',
                'Б' => 'B',
                'В' => 'V',
                'Г' => 'G',
                'Д' => 'D',
                'Е' => 'E',
                'Ж' => 'Zh',
                'З' => 'Z',
                'И' => 'I',
                'Й' => 'Y',
                'К' => 'K',
                'Л' => 'L',
                'М' => 'M',
                'Н' => 'N',
                'О' => 'O',
                'П' => 'P',
                'Р' => 'R',
                'С' => 'S',
                'Т' => 'T',
                'У' => 'U',
                'Ф' => 'F',
                'Х' => 'H',
                'Ц' => 'Ts',
                'Ч' => 'Ch',
                'Ш' => 'Sh',
                'Щ' => 'Sht',
                'Ъ' => 'Y',
                'Ь' => '\'',
                'Ю' => 'Yu',
                'Я' => 'Ya',
            );
            $text = strtr($text, $map);
        }

        // lowercase
        $text = strtolower($text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        if (empty($text)) {
            $text = 'n-a';
        }

        return $text;
    }
}