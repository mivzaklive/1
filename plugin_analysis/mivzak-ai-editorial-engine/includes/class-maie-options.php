<?php

if (!defined('ABSPATH')) {
    exit;
}

final class MAIE_Options
{
    public static function text_models(): array
    {
        return [
            'gpt-4o' => [
                'label' => 'GPT-4o',
                'badge' => 'מומלץ',
                'description' => 'האיכות הגבוהה ביותר לכתבות מורכבות, מחקר רשת וניסוח עיתונאי מדויק.',
                'cost' => 'קלט $2.50 / פלט $10 לכל 1M טוקנים',
                'recommended' => true,
            ],
            'gpt-4.1' => [
                'label' => 'GPT-4.1',
                'badge' => 'מאוזן',
                'description' => 'איכות גבוהה מאוד במחיר נמוך יותר. מתאים לקטגוריות חדשותיות רבות.',
                'cost' => 'קלט $2 / פלט $8 לכל 1M טוקנים',
                'recommended' => false,
            ],
            'gpt-4o-mini' => [
                'label' => 'GPT-4o Mini',
                'badge' => 'חסכוני',
                'description' => 'מהיר וזול יותר. מתאים לפרופילים בנפח גבוה או לכתבות פשוטות יחסית.',
                'cost' => 'קלט $0.15 / פלט $0.60 לכל 1M טוקנים',
                'recommended' => false,
            ],
            'gpt-4.1-mini' => [
                'label' => 'GPT-4.1 Mini',
                'badge' => 'מאוזן חסכוני',
                'description' => 'איזון טוב בין עלות לאיכות. מתאים לכתבות שגרתיות.',
                'cost' => 'קלט $0.40 / פלט $1.60 לכל 1M טוקנים',
                'recommended' => false,
            ],
            'gpt-4.1-nano' => [
                'label' => 'GPT-4.1 Nano',
                'badge' => 'הכי זול',
                'description' => 'מיועד למשימות פשוטות וזולות במיוחד. אינו מומלץ כברירת מחדל לכתבות ראשיות.',
                'cost' => 'קלט $0.10 / פלט $0.40 לכל 1M טוקנים',
                'recommended' => false,
            ],
        ];
    }

    public static function qa_models(): array
    {
        $models = self::text_models();
        foreach ($models as $slug => &$model) {
            $model['recommended'] = $slug === 'gpt-4o';
            if ($slug === 'gpt-4o') {
                $model['badge'] = 'מומלץ לבקרה';
                $model['description'] = 'בדיקה מחמירה במיוחד של אמינות, ניסוח, כפילויות והמצאות.';
            }
        }
        unset($model);
        return $models;
    }

    public static function vision_models(): array
    {
        return [
            'gpt-4o' => [
                'label' => 'GPT-4o',
                'badge' => 'מומלץ',
                'description' => 'בדיקת תמונה חזקה ומאוזנת: רלוונטיות לכתבה, זיהוי טקסט ולוגואים.',
                'cost' => 'קלט $2.50 / פלט $10 לכל 1M טוקנים',
                'recommended' => true,
            ],
            'gpt-4.1' => [
                'label' => 'GPT-4.1',
                'badge' => 'מחמיר יותר',
                'description' => 'בדיקה איכותית במיוחד. מתאים לפרופילים רגישים.',
                'cost' => 'קלט $2 / פלט $8 לכל 1M טוקנים',
                'recommended' => false,
            ],
            'gpt-4o-mini' => [
                'label' => 'GPT-4o Mini',
                'badge' => 'חסכוני',
                'description' => 'בדיקת תמונה זולה ומהירה יותר. יכולה להספיק בפרופילים פשוטים.',
                'cost' => 'קלט $0.15 / פלט $0.60 לכל 1M טוקנים',
                'recommended' => false,
            ],
            'gpt-4.1-mini' => [
                'label' => 'GPT-4.1 Mini',
                'badge' => 'חסכוני מאוד',
                'description' => 'אפשרות חסכונית לבדיקות בסיסיות. פחות מומלץ לזיהוי דק של בעיות תמונה.',
                'cost' => 'קלט $0.40 / פלט $1.60 לכל 1M טוקנים',
                'recommended' => false,
            ],
        ];
    }

    public static function image_models(): array
    {
        return [
            'gpt-image-1' => [
                'label' => 'GPT Image 1',
                'badge' => 'מומלץ',
                'description' => 'מודל יצירת התמונות המתקדם של OpenAI. תומך ב-WebP, JPEG ו-PNG עם דחיסה מותאמת.',
                'cost' => 'דוגמה: 1536×1024 באיכות Medium ≈ $0.04–$0.07 לתמונה',
                'recommended' => true,
            ],
            'dall-e-3' => [
                'label' => 'DALL·E 3',
                'badge' => 'אלטרנטיבי',
                'description' => 'מודל ישן יותר, יציב, מחזיר כתובת URL (לא base64). מתאים לסביבות שבהן gpt-image-1 לא זמין.',
                'cost' => 'כ-$0.04–$0.08 לתמונה לפי גודל ואיכות',
                'recommended' => false,
            ],
        ];
    }

    public static function image_sizes(): array
    {
        return [
            '1536x1024' => [
                'label' => '1536×1024 – נוף 3:2',
                'badge' => 'מומלץ',
                'description' => 'ברירת מחדל מומלצת: גדול מספיק לתמונה ראשית, יחס נוף רחב מתאים לעיתון.',
                'cost' => 'נתמך מלא ב-gpt-image-1',
                'recommended' => true,
            ],
            '1024x1536' => [
                'label' => '1024×1536 – דיוקן',
                'badge' => 'אנכי',
                'description' => 'מתאים לתמונות דיוקן או לתצוגות מובייל ייחודיות.',
                'cost' => 'נתמך מלא ב-gpt-image-1',
                'recommended' => false,
            ],
            '1024x1024' => [
                'label' => '1024×1024 – ריבוע',
                'badge' => 'אוניברסלי',
                'description' => 'מתאים לשימושים כלליים ולתצוגות רשתות חברתיות.',
                'cost' => 'נתמך ב-gpt-image-1 וב-DALL·E 3',
                'recommended' => false,
            ],
        ];
    }

    public static function image_qualities(): array
    {
        return [
            'medium' => [
                'label' => 'בינונית',
                'badge' => 'מומלץ',
                'description' => 'איזון טוב בין איכות, זמן יצירה ועלות.',
                'cost' => 'ברירת מחדל',
                'recommended' => true,
            ],
            'high' => [
                'label' => 'גבוהה',
                'badge' => 'איכות מקסימלית',
                'description' => 'מתאים לתמונות שבהן הפרטים חשובים במיוחד.',
                'cost' => 'יקר יותר',
                'recommended' => false,
            ],
            'low' => [
                'label' => 'נמוכה',
                'badge' => 'חסכונית',
                'description' => 'מתאים לניסויים או לקטגוריות שבהן התמונה משנית.',
                'cost' => 'זול ומהיר',
                'recommended' => false,
            ],
            'auto' => [
                'label' => 'אוטומטית',
                'badge' => 'לפי המודל',
                'description' => 'המודל בוחר איכות לפי הבקשה.',
                'cost' => 'עלות משתנה',
                'recommended' => false,
            ],
        ];
    }

    public static function web_search_modes(string $content_mode = 'news'): array
    {
        $recommended = $content_mode === 'news' ? 'enabled' : 'inherit';
        return [
            'inherit' => [
                'label' => 'לפי ההגדרה הכללית',
                'badge' => $recommended === 'inherit' ? 'מומלץ' : 'ברירת מחדל',
                'description' => 'הפרופיל ישתמש בהגדרה הכללית של המערכת.',
                'cost' => 'ללא שינוי',
                'recommended' => $recommended === 'inherit',
            ],
            'enabled' => [
                'label' => 'חיפוש רשת פעיל',
                'badge' => $recommended === 'enabled' ? 'מומלץ לחדשות' : 'מדויק יותר',
                'description' => 'מאפשר איתור נושאים עדכניים מהאינטרנט. מומלץ מאוד לחדשות.',
                'cost' => 'תוספת כלי Web Search לפי תמחור OpenAI',
                'recommended' => $recommended === 'enabled',
            ],
            'disabled' => [
                'label' => 'ללא חיפוש רשת',
                'badge' => 'חיסכון',
                'description' => 'חוסך עלויות חיפוש, אך מתאים בעיקר לתוכן שאינו תלוי בזמן אמת.',
                'cost' => 'ללא עלות Web Search',
                'recommended' => false,
            ],
        ];
    }

    public static function inherit_options(array $options, string $global_value, string $inherit_label = 'השתמש בברירת המחדל הכללית'): array
    {
        $result = [
            '' => [
                'label' => $inherit_label,
                'badge' => 'יורש: ' . self::label_for($options, $global_value),
                'description' => 'שימוש בהגדרת המערכת הכללית ללא חריגה בפרופיל הזה.',
                'cost' => '',
                'recommended' => true,
            ],
        ];

        foreach ($options as $slug => $option) {
            $option['recommended'] = false;
            $result[$slug] = $option;
        }

        return $result;
    }

    public static function label_for(array $options, string $value): string
    {
        return isset($options[$value]['label']) ? (string) $options[$value]['label'] : $value;
    }

    public static function allowed_text_model(string $value, string $fallback = 'gpt-4o'): string
    {
        return array_key_exists($value, self::text_models()) ? $value : $fallback;
    }

    public static function allowed_qa_model(string $value, string $fallback = 'gpt-4o'): string
    {
        return array_key_exists($value, self::qa_models()) ? $value : $fallback;
    }

    public static function allowed_vision_model(string $value, string $fallback = 'gpt-4o'): string
    {
        return array_key_exists($value, self::vision_models()) ? $value : $fallback;
    }

    public static function allowed_image_model(string $value, string $fallback = 'gpt-image-1'): string
    {
        return array_key_exists($value, self::image_models()) ? $value : $fallback;
    }

    public static function allowed_image_size(string $value, string $fallback = '1536x1024'): string
    {
        return array_key_exists($value, self::image_sizes()) ? $value : $fallback;
    }

    public static function allowed_image_quality(string $value, string $fallback = 'medium'): string
    {
        return array_key_exists($value, self::image_qualities()) ? $value : $fallback;
    }

    public static function allowed_web_search_mode(string $value, string $fallback = 'inherit'): string
    {
        return in_array($value, ['inherit', 'enabled', 'disabled'], true) ? $value : $fallback;
    }
}
