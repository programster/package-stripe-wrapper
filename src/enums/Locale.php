<?php

/*
 * Enum of all the currencies Stripe accepts.
 * https://docs.stripe.com/currencies
 */

namespace Programster\Stripe\Enums;
enum Locale: string
{
    case BULGARIAN = "bg";
    case CROATIAN = "hr";
    case CZECH = "cs";
    case DANISH = "da";
    case DUTCH = "nl";
    case ENGLISH = "en";
    case ESTONIAN = "et";
    case FINNISH = "fi";
    case FILIPINO = "fil";
    case FRENCH = "fr";
    case GERMAN = "de";
    case GREEK = "el";
    case HUNGARIAN = "hu";
    case INDONESIAN = "id";
    case ITALIAN = "it";
    case JAPANESE = "ja";
    case KOREAN = "ko";
    case LATVIAN = "lv";
    case LITHUANIAN = "lt";
    case MALAY = "ms";
    case MALTESE = "mt";
    case NORWEGIAN = "nb";
    case POLISH = "pl";
    case PORTUGUESE = "pt";
    case ROMANIAN = "ro";
    case RUSSIAN = "ru";
    case CHINESE_SIMPLIFIED = "zh";
    case SLOVAK = "sk";
    case SLOVENIAN = "sl";
    case SPANISH = "es";
    case Swedish = "sv";
    case THAI = "th";
    case TURKISH = "tr";
    case VIETNAMESE = "vi";
}