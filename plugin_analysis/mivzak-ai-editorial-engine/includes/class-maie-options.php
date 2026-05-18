<?php

if (!defined('ABSPATH')) {
    exit;
}

final class MAIE_Options
{
    public static function text_models(): array
    {
        return [
            'gpt-5.5' => [
                'label' => 'GPT-5.5',
                'badge' => 'מומלץ',
                'description' => 'האיכות הגבוהה ביותר לכתבות מורכבות, מחקר רשת וניסוח עיתונאי מדויק.',
                'cost' => 'קלט $5 / פלט $30 לכל 1M טוקנים',
                'recommended' => true,
            ],
            'gpt-5.4' => [
                'label' => 'GPT-5.4',
                'badge' => 'מאוזן',
                'description' => 'איכות גבוהה מאוד במחיר נמוך יותר. מתאים לקטגוריות חדשותיות רבות.',
                'cost' => 'קלט $2.50 / פלט $15 לכל 1M טוקנים',
                'recommended' => false,
            ],
            'gpt-5.4-mini' => [
                'label' => 'GPT-5.4 Mini',
                'badge' => 'חסכוני',
                'description' => 'מהיר וזול יותר. מתאים לפרופילים בנפח גבוה או לכתבות פשוטות יחסית.',
                'cost' => 'קלט $0.75 / פלט $4.50 לכל 1M טוקנים',
                'recommended' => false,
            ],
            'gpt-5.4-nano' => [
                'label' => 'GPT-5.4 Nano',
                'badge' => 'הכי זול',
                'description' => 'מיועד למשימות פשוטות וזולות במיוחד. אינו מומלץ כברירת מחדל לכתבות ראשיות.',
                'cost' => 'קלט $0.20 / פלט $1.25 לכל 1M טוקנים',
                'recommended' => false,
            ],
            'gpt-4.1' => [
                'label' => 'GPT-4.1',
                'badge' => 'דור קודם',
                'description' => 'מודל יציב ללא שכבת reasoning מובנית. עשוי להתאים לפרופילים פשוטים יותר.',
                'cost' => 'קלט $2 / פלט $8 לכל 1M טוקנים',
                'recommended' => false,
            ],
        ];
    }

    public static function qa_models(): array
    {
        $models = self::text_models();
        foreach ($models as $slug => &$model) {
            $model['recommended'] = $slug === 'gpt-5.5';
            if ($slug === 'gpt-5.5') {
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
            'gpt-5.4' => [
                'label' => 'GPT-5.4',
                'badge' => 'מומלץ',
                'description' => 'בדיקת תמונה חזקה ומאוזנת: רלוונטיות לכתבה, זיהוי טקסט ולוגואים.',
                'cost' => 'קלט $2.50 / פלט $15 לכל 1M טוקנים',
                'recommended' => true,
            ],
            'gpt-5.5' => [
                'label' => 'GPT-5.5',
                'badge' => 'מחמיר יותר',
                'description' => 'בדיקה איכותית במיוחד, במחיר גבוה יותר. מתאים לפרופילים רגישים.',
                'cost' => 'קלט $5 / פלט $30 לכל 1M טוקנים',
                'recommended' => false,
            ],
            'gpt-5.4-mini' => [
                'label' => 'GPT-5.4 Mini',
                'badge' => 'חסכוני',
                'description' => 'בדיקת תמונה זולה ומהירה יותר. יכולה להספיק בפרופילים פשוטים.',
                'cost' => 'קלט $0.75 / פלט $4.50 לכל 1M טוקנים',
                'recommended' => false,
            ],
            'gpt-5-mini' => [
                'label' => 'GPT-5 Mini',
                'badge' => 'חסכוני מאוד',
                'description' => 'אפשרות חסכונית לבדיקות בסיסיות. פחות מומלץ לזיהוי דק של בעיות תמונה.',
                'cost' => 'קלט $0.25 / פלט $2 לכל 1M טוקנים',
                'recommended' => false,
            ],
        ];
    }

    public static function image_models(): array
    {
        return [
            'gpt-image-2' => [
                'label' => 'GPT Image 2',
                'badge' => 'מומלץ',
                'description' => 'המודל החדש והאיכותי ביותר. מאפשר גדלים גמישים, כולל 16:9 מדויק.',
                'cost' => 'דוגמה: נוף 1536×1024 באיכות Medium ≈ $0.041 לתמונה',
                'recommended' => true,
            ],
            'gpt-image-1.5' => [
                'label' => 'GPT Image 1.5',
                'badge' => 'איכותי',
                'description' => 'איכות גבוהה והיצמדות טובה לפרומפט, אך פחות גמיש בגדלי תמונה.',
                'cost' => 'דוגמה: נוף 1536×1024 באיכות Medium ≈ $0.05 לתמונה',
                'recommended' => false,
            ],
            'gpt-image-1-mini' => [
                'label' => 'GPT Image 1 Mini',
                'badge' => 'חסכוני',
                'description' => 'אפשרות זולה יותר לקטגוריות שבהן איכות התמונה פחות קריטית.',
                'cost' => 'דוגמה: נוף 1536×1024 באיכות Medium ≈ $0.015 לתמונה',
                'recommended' => false,
            ],
        ];
    }

    public static function image_sizes(): array
    {
        return [
            '1280x720' => [
                'label' => '1280×720 – 16:9',
                'badge' => 'מומלץ Discover / News',
                'description' => 'ברירת מחדל מומלצת: רוחב מעל 1200px ויחס 16:9 המתאים מאוד לתמונה ראשית.',
                'cost' => 'זמין באופן מלא עם GPT Image 2',
                'recommended' => true,
            ],
            '1536x864' => [
                'label' => '1536×864 – 16:9',
                'badge' => 'חד יותר',
                'description' => 'גרסת 16:9 חדה יותר לתמונות ראשיות. מתאימה ל-GPT Image 2.',
                'cost' => 'עלות גבוהה יותר מ-1280×720',
                'recommended' => false,
            ],
            '2048x1152' => [
                'label' => '2048×1152 – 2K 16:9',
                'badge' => 'פרימיום',
                'description' => 'חדה יותר, אך כבדה ויקרה יותר. מתאימה רק כשיש צורך ממשי.',
                'cost' => 'עלות גבוהה משמעותית יותר',
                'recommended' => false,
            ],
            '1536x1024' => [
                'label' => '1536×1024 – נוף 3:2',
                'badge' => 'תאימות רחבה',
                'description' => 'גודל נתמך גם במודלי GPT Image 1.5 ו-1 Mini.',
                'cost' => 'גודל השוואה נוח בין מודלים',
                'recommended' => false,
            ],
            '1024x1024' => [
                'label' => '1024×1024 – ריבוע',
                'badge' => 'לא מומלץ לתמונה ראשית',
                'description' => 'מתאים בעיקר לשימושים אחרים, לא כברירת מחדל לתמונת כתבה.',
                'cost' => 'לעיתים זול יותר',
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

    public static function allowed_text_model(string $value, string $fallback = 'gpt-5.5'): string
    {
        return array_key_exists($value, self::text_models()) ? $value : $fallback;
    }

    public static function allowed_qa_model(string $value, string $fallback = 'gpt-5.5'): string
    {
        return array_key_exists($value, self::qa_models()) ? $value : $fallback;
    }

    public static function allowed_vision_model(string $value, string $fallback = 'gpt-5.4'): string
    {
        return array_key_exists($value, self::vision_models()) ? $value : $fallback;
    }

    public static function allowed_image_model(string $value, string $fallback = 'gpt-image-2'): string
    {
        return array_key_exists($value, self::image_models()) ? $value : $fallback;
    }

    public static function allowed_image_size(string $value, string $fallback = '1280x720'): string
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
