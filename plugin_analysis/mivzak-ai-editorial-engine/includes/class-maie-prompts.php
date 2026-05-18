<?php

if (!defined('ABSPATH')) {
    exit;
}

final class MAIE_Prompts
{
    public static function default_settings(): array
    {
        return [
            'api_key' => '',
            'text_model' => 'gpt-4o',
            'qa_model' => 'gpt-4o',
            'vision_model' => 'gpt-4o',
            'image_model' => 'gpt-image-1',
            'default_post_author' => 1,
            'auto_cron_enabled' => 0,
            'web_search_enabled' => 1,
            'qa_enabled' => 1,
            'image_qa_enabled' => 1,
            'strict_publish_requires_image' => 1,
            'debug_enabled' => 0,
            'max_jobs_per_cron' => 5,
            'max_image_regeneration_attempts' => 2,
            'image_quality' => 'medium',
            'image_size' => '1536x1024',
            'image_output_format' => 'webp',
            'image_output_compression' => 90,
            'jobs_retention_days' => 30,
        ];
    }

    public static function default_profile_blueprints(): array
    {
        $news_domains = "reuters.com, apnews.com, bbc.com, cnn.com, theguardian.com, politico.eu, france24.com, dw.com, nytimes.com, washingtonpost.com";
        $israel_domains = "ynet.co.il, mako.co.il, n12.co.il, kan.org.il, israelhayom.co.il, maariv.co.il, globes.co.il, calcalist.co.il, themarker.com";
        $official_israel = "gov.il, police.gov.il, idf.il, mod.gov.il, mda.org.il, fire.gov.il, court.gov.il, bankisrael.gov.il, cbs.gov.il";

        return [
            [
                'category_name' => 'חדשות הארץ',
                'menu_group' => 'חדשות',
                'content_mode' => 'news',
                'search_window_hours' => 12,
                'generation_interval_minutes' => 180,
                'max_posts_per_day' => 4,
                'prefer_israel' => 1,
                'preferred_domains' => $israel_domains . ', ' . $official_israel,
                'topic_keywords' => "חדשות ישראל, אירוע אזרחי משמעותי, משבר ציבורי, התפתחות לאומית, אסון, החלטת ממשלה, אירוע חריג",
                'research_focus' => 'בחר התפתחות חדשותית חדשה ובולטת בישראל, בעלת עניין ציבורי רחב, שאינה שייכת במובהק לקטגוריית משנה צרה יותר.',
            ],
            [
                'category_name' => 'מבזקים',
                'menu_group' => 'חדשות',
                'content_mode' => 'news',
                'search_window_hours' => 6,
                'generation_interval_minutes' => 60,
                'max_posts_per_day' => 8,
                'prefer_israel' => 1,
                'preferred_domains' => $israel_domains . ', ' . $official_israel,
                'topic_keywords' => "אירוע חדשותי מהשעות האחרונות\nהתפתחות מיידית בישראל\nדיווח ראשוני מאומת\nאירוע ביטחוני, פלילי, מדיני, תחבורתי או אזרחי בעל עניין ציבורי",
                'research_focus' => 'מצא מבזק חדשותי קצר, טרי, חד וברור, שהתרחש או נחשף לראשונה בשעות האחרונות. העדף נושא ישראלי משמעותי, בעל ערך מיידי לציבור, שאפשר לנסח עליו ידיעה קצרה ומדויקת.',
            ],
            [
                'category_name' => 'כלכלה',
                'menu_group' => 'חדשות',
                'content_mode' => 'news',
                'search_window_hours' => 24,
                'generation_interval_minutes' => 240,
                'max_posts_per_day' => 2,
                'prefer_israel' => 1,
                'preferred_domains' => "globes.co.il, calcalist.co.il, themarker.com, bankisrael.gov.il, cbs.gov.il, treasury.gov.il, reuters.com, bloomberg.com, ft.com",
                'topic_keywords' => "ריבית, בנק ישראל, יוקר המחיה, חברות ציבוריות, שוק ההון, נדל\"ן, תעסוקה, אינפלציה, דו\"חות כספיים, רגולציה כלכלית",
                'research_focus' => 'בחר התפתחות כלכלית חדשה ובעלת משמעות לציבור בישראל או לשווקים, בדגש על החלטה, נתון, דו״ח, משבר, צעד רגולטורי, שינוי מחירים או מהלך של חברה גדולה.',
            ],
            [
                'category_name' => 'ספורט',
                'menu_group' => 'חדשות',
                'content_mode' => 'news',
                'search_window_hours' => 24,
                'generation_interval_minutes' => 240,
                'max_posts_per_day' => 2,
                'prefer_israel' => 1,
                'preferred_domains' => "uefa.com, fifa.com, olympics.com, nba.com, euroleaguebasketball.net, one.co.il, sport5.co.il, ynet.co.il",
                'topic_keywords' => "נבחרת ישראל, ליגת העל, אירופה, כדורגל, כדורסל, טניס, העברות, פציעות, משחק דרמטי, זכייה, הפסד משמעותי",
                'research_focus' => 'בחר אירוע ספורט טרי ובעל עניין גבוה: תוצאה דרמטית, הכרעה, הישג ישראלי, שינוי משמעותי בקבוצה בכירה או הודעה רשמית חשובה.',
            ],
            [
                'category_name' => 'תאונות עבודה',
                'menu_group' => 'חדשות',
                'content_mode' => 'news',
                'search_window_hours' => 12,
                'generation_interval_minutes' => 180,
                'max_posts_per_day' => 3,
                'prefer_israel' => 1,
                'preferred_domains' => "mda.org.il, police.gov.il, gov.il, ynet.co.il, mako.co.il, n12.co.il, kan.org.il",
                'topic_keywords' => "תאונת עבודה, פועל נפצע, נפילה מגובה, אתר בנייה, מפעל, פציעה קשה, הרוג, חילוץ",
                'research_focus' => 'בחר אירוע תאונת עבודה חדש בישראל מהשעות האחרונות, הנשען על דיווח רשמי או על כמה פרסומים אמינים. הקפד על ניסוח זהיר ביחס לנסיבות שנבדקות.',
            ],
            [
                'category_name' => 'תנועה ותחבורה',
                'menu_group' => 'חדשות',
                'content_mode' => 'news',
                'search_window_hours' => 12,
                'generation_interval_minutes' => 180,
                'max_posts_per_day' => 3,
                'prefer_israel' => 1,
                'preferred_domains' => "gov.il, police.gov.il, israelrailways.co.il, ynet.co.il, kan.org.il, n12.co.il, mako.co.il",
                'topic_keywords' => "עומסי תנועה, חסימות כבישים, תחבורה ציבורית, רכבת ישראל, כביש סגור, שינויים בתנועה, שביתה תחבורתית",
                'research_focus' => 'בחר התפתחות תחבורתית עדכנית בעלת משמעות לציבור בישראל: חסימות, שינויי תנועה, שיבושי רכבות, אירוע חריג או החלטה חדשה.',
            ],
            [
                'category_name' => 'תאונות דרכים',
                'menu_group' => 'חדשות',
                'content_mode' => 'news',
                'search_window_hours' => 12,
                'generation_interval_minutes' => 120,
                'max_posts_per_day' => 5,
                'prefer_israel' => 1,
                'preferred_domains' => "mda.org.il, police.gov.il, fire.gov.il, ynet.co.il, n12.co.il, mako.co.il, kan.org.il",
                'topic_keywords' => "תאונת דרכים, רוכב אופנוע, הולך רגל, פצוע קשה, הרוג, התנגשות, כביש, רכב פרטי, משאית",
                'research_focus' => 'בחר תאונת דרכים משמעותית ועדכנית בישראל, רק אם יש פרטים מאומתים על מיקום, מצב נפגעים ופעולות כוחות ההצלה.',
            ],
            [
                'category_name' => 'הודעות דוברות',
                'menu_group' => 'חדשות',
                'content_mode' => 'press_release',
                'search_window_hours' => 24,
                'generation_interval_minutes' => 180,
                'max_posts_per_day' => 4,
                'prefer_israel' => 1,
                'preferred_domains' => $official_israel,
                'topic_keywords' => "הודעה רשמית, דוברות, משטרה, מד\"א, כבאות והצלה, צה\"ל, משרד ממשלתי, רשות ציבורית",
                'research_focus' => 'בחר הודעה רשמית חדשה ובעלת עניין ציבורי מאתרי דוברות או מקורות רשמיים, והפוך אותה לכתבה עיתונאית עצמאית ולא להעתקה של הודעת יח״צ.',
            ],
            [
                'category_name' => 'פוליטי',
                'menu_group' => 'ראשי',
                'content_mode' => 'news',
                'search_window_hours' => 18,
                'generation_interval_minutes' => 240,
                'max_posts_per_day' => 3,
                'prefer_israel' => 1,
                'preferred_domains' => $israel_domains . ', knesset.gov.il, gov.il',
                'topic_keywords' => "ממשלה, כנסת, קואליציה, אופוזיציה, חוק, הצבעה, השרים, ראש הממשלה, משבר פוליטי, החלטת ממשלה",
                'research_focus' => 'בחר התפתחות פוליטית או מדינית עדכנית בישראל שיש לה ערך ציבורי ברור: החלטה, עימות, שינוי עמדה, הצבעה, מינוי, חקיקה או משבר.',
            ],
            [
                'category_name' => 'ביטחוני',
                'menu_group' => 'ראשי',
                'content_mode' => 'news',
                'search_window_hours' => 12,
                'generation_interval_minutes' => 180,
                'max_posts_per_day' => 4,
                'prefer_israel' => 1,
                'preferred_domains' => "idf.il, mod.gov.il, shinbet.gov.il, police.gov.il, reuters.com, apnews.com, ynet.co.il, n12.co.il, kan.org.il",
                'topic_keywords' => "צה\"ל, חמאס, חיזבאללה, איראן, ירי, תקיפה, חיסול, כוננות, גבול, מודיעין, חטופים, ביטחון ישראל",
                'research_focus' => 'בחר התפתחות ביטחונית טרייה, משמעותית ומאומתת, עם עדיפות לזיקה ישירה לישראל. הבדל היטב בין דיווח, הערכה, טענה רשמית ואישור מבצעי.',
            ],
            [
                'category_name' => 'משפטי',
                'menu_group' => 'ראשי',
                'content_mode' => 'news',
                'search_window_hours' => 24,
                'generation_interval_minutes' => 240,
                'max_posts_per_day' => 2,
                'prefer_israel' => 1,
                'preferred_domains' => "court.gov.il, gov.il, justice.gov.il, police.gov.il, ynet.co.il, kan.org.il, n12.co.il, reuters.com",
                'topic_keywords' => "בית משפט, כתב אישום, צו מעצר, מעצר, עתירה, פסק דין, היועמ\"שית, חקירה, חשוד, נאשם",
                'research_focus' => 'בחר התפתחות משפטית או פלילית עדכנית בעלת משמעות ציבורית. הקפד במיוחד על מונחים מדויקים: חשוד, נאשם, הורשע, נטען, על פי החשד.',
            ],
            [
                'category_name' => 'בריאות',
                'menu_group' => 'ראשי',
                'content_mode' => 'evergreen',
                'search_window_hours' => 168,
                'generation_interval_minutes' => 720,
                'max_posts_per_day' => 1,
                'prefer_israel' => 0,
                'preferred_domains' => "who.int, cdc.gov, nih.gov, health.gov.il, nejm.org, nature.com, thelancet.com",
                'topic_keywords' => "בריאות הציבור, רפואה, מחקר חדש, תזונה, מניעת מחלות, תרופות, בדיקות, מחלות כרוניות, אורח חיים",
                'research_focus' => 'בחר נושא בריאותי בעל עניין ציבורי רחב, הנשען על מקורות רפואיים אמינים ומעודכנים. הכתבה צריכה להיות מסבירה, זהירה, לא אבחונית ולא להחליף ייעוץ רפואי.',
            ],
            [
                'category_name' => 'צרכנות',
                'menu_group' => 'ראשי',
                'content_mode' => 'evergreen',
                'search_window_hours' => 168,
                'generation_interval_minutes' => 720,
                'max_posts_per_day' => 1,
                'prefer_israel' => 1,
                'preferred_domains' => "gov.il, moit.gov.il, consumer.org.il, reuters.com, ynet.co.il, calcalist.co.il, globes.co.il",
                'topic_keywords' => "מחירים, מדריך צרכני, זכויות צרכן, תיירות, רכב, קניות, טיסות, חשמל, תקשורת, רפורמה צרכנית",
                'research_focus' => 'בחר נושא צרכני עדכני או עונתי שנותן ערך אמיתי לקורא הישראלי: שינוי מחירים, זכויות, השוואות, מדריך מעשי או תקלה רחבה בשירות.',
            ],
            [
                'category_name' => 'אינטרנט',
                'menu_group' => 'ראשי',
                'content_mode' => 'news',
                'search_window_hours' => 24,
                'generation_interval_minutes' => 360,
                'max_posts_per_day' => 2,
                'prefer_israel' => 0,
                'preferred_domains' => "openai.com, developers.openai.com, googleblog.com, microsoft.com, meta.com, x.com, tiktok.com, reuters.com, theverge.com, techcrunch.com",
                'topic_keywords' => "רשתות חברתיות, אפליקציות, פלטפורמות דיגיטליות, אינטרנט, שינוי מדיניות, תקלה עולמית, פרטיות, תוכן מקוון",
                'research_focus' => 'בחר התפתחות חדשה ומשמעותית בעולם האינטרנט והרשתות: שינוי בפלטפורמה גדולה, תקלה רחבה, מהלך רגולטורי, סוגיית פרטיות או תופעה דיגיטלית מרכזית.',
            ],
            [
                'category_name' => 'טכנולוגיה',
                'menu_group' => 'אינטרנט',
                'content_mode' => 'news',
                'search_window_hours' => 24,
                'generation_interval_minutes' => 360,
                'max_posts_per_day' => 2,
                'prefer_israel' => 0,
                'preferred_domains' => "openai.com, developers.openai.com, googleblog.com, apple.com, microsoft.com, meta.com, reuters.com, theverge.com, techcrunch.com",
                'topic_keywords' => "בינה מלאכותית, סייבר, אפליקציות, טכנולוגיה, מוצר חדש, אבטחת מידע, תקלה עולמית, השקה, פיצ\"ר חדש",
                'research_focus' => 'בחר התפתחות טכנולוגית חדשה ובעלת עניין רחב: השקה משמעותית, שינוי בפלטפורמה גדולה, אירוע סייבר, כלי AI חדש או תקלה שמשפיעה על משתמשים רבים.',
            ],
            [
                'category_name' => 'חדשות חוץ',
                'menu_group' => 'ראשי',
                'content_mode' => 'news',
                'search_window_hours' => 12,
                'generation_interval_minutes' => 180,
                'max_posts_per_day' => 3,
                'prefer_israel' => 1,
                'preferred_domains' => $news_domains,
                'topic_keywords' => "אירוע חדשותי בינלאומי משמעותי\nהתפתחות דרמטית חדשה מהעולם\nאירוע מדיני, ביטחוני, משפטי או גיאופוליטי חריג\nנושא עולמי בעל השלכה ישירה או עקיפה על ישראל",
                'research_focus' => 'בחר רק אירוע בינלאומי חשוב שהתרחש או נחשף לראשונה במהלך חלון הזמן המוגדר. העדף עניינים דרמטיים, אסטרטגיים או בעלי זיקה לישראל, איראן, ארה״ב, רוסיה, אירופה או המזרח התיכון. אל תבחר ידיעה ממוחזרת.',
            ],
            [
                'category_name' => 'העולם הערבי',
                'menu_group' => 'אזורי',
                'content_mode' => 'news',
                'search_window_hours' => 18,
                'generation_interval_minutes' => 240,
                'max_posts_per_day' => 3,
                'prefer_israel' => 1,
                'preferred_domains' => "reuters.com, apnews.com, al-monitor.com, arabnews.com, france24.com, bbc.com, timesofisrael.com",
                'topic_keywords' => "המזרח התיכון, מדינות ערב, סעודיה, קטאר, ירדן, עיראק, תימן, מצרים, לבנון, סוריה, עזה, איראן",
                'research_focus' => 'בחר התפתחות משמעותית בעולם הערבי או במזרח התיכון, תוך העדפה ברורה לידיעות שיש להן עניין לישראלים או השפעה אזורית.',
            ],
            [
                'category_name' => 'דאעש',
                'menu_group' => 'אזורי',
                'content_mode' => 'news',
                'search_window_hours' => 48,
                'generation_interval_minutes' => 720,
                'max_posts_per_day' => 1,
                'prefer_israel' => 0,
                'preferred_domains' => "reuters.com, apnews.com, bbc.com, france24.com, un.org, state.gov",
                'topic_keywords' => "דאעש, ISIS, טרור ג'יהאדיסטי, סיכול טרור, תא טרור, פיגוע, מעצר מחבלים",
                'research_focus' => 'בחר רק התפתחות ממשית ועדכנית הקשורה לדאעש או לארגוני ג׳יהאד, ולא סקירה כללית ישנה.',
            ],
            [
                'category_name' => 'רצועת עזה',
                'menu_group' => 'אזורי',
                'content_mode' => 'news',
                'search_window_hours' => 12,
                'generation_interval_minutes' => 180,
                'max_posts_per_day' => 4,
                'prefer_israel' => 1,
                'preferred_domains' => "idf.il, gov.il, reuters.com, apnews.com, ynet.co.il, n12.co.il, kan.org.il",
                'topic_keywords' => "רצועת עזה, חמאס, ג'יהאד האיסלאמי, חטופים, הפסקת אש, תקיפות, סיוע הומניטרי, מעבר רפיח",
                'research_focus' => 'בחר התפתחות חדשה ומשמעותית ברצועת עזה שיש בה ערך חדשותי ברור. שמור על זהירות בהבדלה בין הודעות ישראליות, דיווחים פלסטיניים ונתונים שלא אומתו.',
            ],
            [
                'category_name' => 'איראן',
                'menu_group' => 'אזורי',
                'content_mode' => 'news',
                'search_window_hours' => 12,
                'generation_interval_minutes' => 180,
                'max_posts_per_day' => 3,
                'prefer_israel' => 1,
                'preferred_domains' => "reuters.com, apnews.com, bbc.com, france24.com, iaea.org, state.gov, whitehouse.gov",
                'topic_keywords' => "איראן, גרעין, משמרות המהפכה, טהרן, סנקציות, טראמפ, ישראל, ארה\"ב, טילים, כטב\"מים",
                'research_focus' => 'בחר התפתחות דרמטית ועדכנית הנוגעת לאיראן, בדגש על סוגיית הגרעין, יחסי איראן-ישראל, יחסי איראן-ארה״ב, שלוחות אזוריות או איום ביטחוני.',
            ],
            [
                'category_name' => 'לבנון',
                'menu_group' => 'אזורי',
                'content_mode' => 'news',
                'search_window_hours' => 12,
                'generation_interval_minutes' => 180,
                'max_posts_per_day' => 3,
                'prefer_israel' => 1,
                'preferred_domains' => "reuters.com, apnews.com, bbc.com, france24.com, idf.il, ynet.co.il, n12.co.il",
                'topic_keywords' => "לבנון, חיזבאללה, ביירות, דרום לבנון, צה\"ל, הסדרה, ארגון טרור, תקיפות",
                'research_focus' => 'בחר התפתחות חדשה בלבנון או בזירת חיזבאללה שיש לה משמעות לישראל, לדיפלומטיה או ליציבות האזורית.',
            ],
            [
                'category_name' => 'מצרים',
                'menu_group' => 'אזורי',
                'content_mode' => 'news',
                'search_window_hours' => 24,
                'generation_interval_minutes' => 360,
                'max_posts_per_day' => 2,
                'prefer_israel' => 1,
                'preferred_domains' => "reuters.com, apnews.com, bbc.com, france24.com, egypttoday.com, al-monitor.com",
                'topic_keywords' => "מצרים, קהיר, רפיח, תיווך, עזה, כלכלה מצרית, משבר גבול, יחסי ישראל-מצרים",
                'research_focus' => 'בחר התפתחות עדכנית במצרים שיש לה משמעות אזורית, ביטחונית, כלכלית או זיקה ישירה לישראל.',
            ],
            [
                'category_name' => 'סוריה',
                'menu_group' => 'אזורי',
                'content_mode' => 'news',
                'search_window_hours' => 24,
                'generation_interval_minutes' => 360,
                'max_posts_per_day' => 2,
                'prefer_israel' => 1,
                'preferred_domains' => "reuters.com, apnews.com, bbc.com, france24.com, al-monitor.com, idf.il",
                'topic_keywords' => "סוריה, דמשק, גבול ישראל-סוריה, מיליציות, תקיפה, בסיסים איראניים, משטר סורי",
                'research_focus' => 'בחר התפתחות עדכנית בסוריה בעלת עניין אזורי או זיקה לישראל, ורק אם יש די מידע אמין ומאומת.',
            ],
            [
                'category_name' => 'בידור',
                'menu_group' => 'פנאי',
                'content_mode' => 'news',
                'search_window_hours' => 48,
                'generation_interval_minutes' => 720,
                'max_posts_per_day' => 1,
                'prefer_israel' => 1,
                'preferred_domains' => "variety.com, deadline.com, hollywoodreporter.com, bbc.com, ynet.co.il, mako.co.il",
                'topic_keywords' => "בידור, טלוויזיה, מוזיקה, קולנוע, סדרה חדשה, זמר, שחקן, ריאליטי, פרס, הופעה",
                'research_focus' => 'בחר נושא בידורי חדש ובולט עם עניין ציבורי אמיתי בישראל או בעולם, ולא רכילות דלה או שמועה לא מאומתת.',
            ],
            [
                'category_name' => 'גלריות',
                'menu_group' => 'פנאי',
                'content_mode' => 'evergreen',
                'search_window_hours' => 336,
                'generation_interval_minutes' => 1440,
                'max_posts_per_day' => 1,
                'prefer_israel' => 1,
                'preferred_domains' => "mivzaklive.co.il",
                'topic_keywords' => "גלריה, תמונות, אירוע, טקס, טבע, מזג אוויר, תיעוד שטח",
                'research_focus' => 'פרופיל שמור לכתבות תמונה או אוספי תוכן. מומלץ להפעיל ידנית בלבד ולא לאוטומציה מלאה.',
            ],
            [
                'category_name' => 'סרטונים',
                'menu_group' => 'VOD',
                'content_mode' => 'news',
                'search_window_hours' => 48,
                'generation_interval_minutes' => 720,
                'max_posts_per_day' => 1,
                'prefer_israel' => 1,
                'preferred_domains' => "youtube.com, gov.il, idf.il, police.gov.il, mda.org.il, kan.org.il, reuters.com",
                'topic_keywords' => "סרטון, תיעוד, וידאו, תיעוד רשמי, תיעוד שטח, אירוע מצולם, תיעוד יוצא דופן",
                'research_focus' => 'בחר רק נושא שלגביו קיים תיעוד וידאו ממשי ובעל עניין ציבורי. הכתבה צריכה להתבסס על האירוע עצמו ולא על תיאור קליקבייטי של הסרטון.',
            ],
            [
                'category_name' => 'טלוויזיה',
                'menu_group' => 'VOD',
                'content_mode' => 'evergreen',
                'search_window_hours' => 720,
                'generation_interval_minutes' => 1440,
                'max_posts_per_day' => 1,
                'prefer_israel' => 1,
                'preferred_domains' => "kan.org.il, mako.co.il, 13tv.co.il, now14.co.il, yes.co.il, hot.net.il, netflix.com",
                'topic_keywords' => "טלוויזיה, לוח שידורים, תוכניות בולטות, ערוצי טלוויזיה, סדרות, ריאליטי, שידור חי",
                'research_focus' => 'בחר נושא טלוויזיוני שימושי או עדכני לקהל הישראלי, כגון תוכנית בולטת, שינוי שידור, מגמת צפייה או מדריך תוכן.',
            ],
            [
                'category_name' => 'הצגות ילדים',
                'menu_group' => 'VOD',
                'content_mode' => 'evergreen',
                'search_window_hours' => 720,
                'generation_interval_minutes' => 1440,
                'max_posts_per_day' => 1,
                'prefer_israel' => 1,
                'preferred_domains' => "culture.gov.il, theatre.org.il, ynet.co.il, mako.co.il",
                'topic_keywords' => "הצגות ילדים, מופעים לילדים, חופשות, תרבות, בילוי משפחתי",
                'research_focus' => 'בחר נושא שימושי ועדכני להורים ולמשפחות סביב הצגות ילדים, המלצה, מדריך עונתי או פעילות תרבותית.',
            ],
            [
                'category_name' => 'סדרות טלוויזיה',
                'menu_group' => 'VOD',
                'content_mode' => 'evergreen',
                'search_window_hours' => 720,
                'generation_interval_minutes' => 1440,
                'max_posts_per_day' => 1,
                'prefer_israel' => 1,
                'preferred_domains' => "netflix.com, disneyplus.com, yes.co.il, hot.net.il, kan.org.il, variety.com, deadline.com",
                'topic_keywords' => "סדרות טלוויזיה, סטרימינג, נטפליקס, דיסני, פרק סיום, סדרה חדשה, המלצה לצפייה",
                'research_focus' => 'בחר נושא תרבותי-טלוויזיוני שמתאים לכתבה שירותית או אקטואלית, הנשענת על מידע מעודכן ומקורות אמינים.',
            ],
            [
                'category_name' => 'אוכל',
                'menu_group' => 'אוכל',
                'content_mode' => 'evergreen',
                'search_window_hours' => 720,
                'generation_interval_minutes' => 1440,
                'max_posts_per_day' => 1,
                'prefer_israel' => 1,
                'preferred_domains' => "gov.il, health.gov.il, משרד הבריאות, מאגרי מתכונים אמינים",
                'topic_keywords' => "אוכל, בישול, מתכונים, עונה, אירוח, מטבח ביתי, מדריך קולינרי",
                'research_focus' => 'בחר נושא אוכל שימושי, עונתי ואיכותי לקהל הישראלי, במבנה של מדריך או כתבת השראה עניינית.',
            ],
            [
                'category_name' => 'בישולים',
                'menu_group' => 'אוכל',
                'content_mode' => 'evergreen',
                'search_window_hours' => 720,
                'generation_interval_minutes' => 1440,
                'max_posts_per_day' => 1,
                'prefer_israel' => 1,
                'preferred_domains' => '',
                'topic_keywords' => "בישול ביתי, ארוחת ערב, ארוחת שישי, מתכון, טיפים במטבח",
                'research_focus' => 'בחר נושא קולינרי שימושי שמתאים למדריך בישול ביתי או כתבה עם ערך פרקטי לקוראים.',
            ],
            [
                'category_name' => 'מאפים – פיצות – לחמים',
                'menu_group' => 'אוכל',
                'content_mode' => 'evergreen',
                'search_window_hours' => 720,
                'generation_interval_minutes' => 1440,
                'max_posts_per_day' => 1,
                'prefer_israel' => 1,
                'preferred_domains' => '',
                'topic_keywords' => "מאפים, פיצה, לחם, בצק, אפייה ביתית, מתכונים",
                'research_focus' => 'בחר נושא איכותי ומעשי בתחום האפייה הביתית, פיצות, לחמים או מאפים עונתיים.',
            ],
            [
                'category_name' => 'מרקים',
                'menu_group' => 'אוכל',
                'content_mode' => 'evergreen',
                'search_window_hours' => 720,
                'generation_interval_minutes' => 1440,
                'max_posts_per_day' => 1,
                'prefer_israel' => 1,
                'preferred_domains' => '',
                'topic_keywords' => "מרקים, חורף, מתכון חם, קטניות, ירקות, מרק ביתי",
                'research_focus' => 'בחר נושא למדריך או כתבה שימושית בתחום המרקים, עם ערך עונתי או קולינרי ברור.',
            ],
            [
                'category_name' => 'סלטים',
                'menu_group' => 'אוכל',
                'content_mode' => 'evergreen',
                'search_window_hours' => 720,
                'generation_interval_minutes' => 1440,
                'max_posts_per_day' => 1,
                'prefer_israel' => 1,
                'preferred_domains' => '',
                'topic_keywords' => "סלטים, ארוחה קלה, ירקות, תוספות, מתכון בריא, אירוח",
                'research_focus' => 'בחר נושא שימושי בתחום הסלטים, מתאים לכתבה שירותית או מדריך קולינרי נגיש.',
            ],
            [
                'category_name' => 'מתוקים – עוגות – עוגיות',
                'menu_group' => 'אוכל',
                'content_mode' => 'evergreen',
                'search_window_hours' => 720,
                'generation_interval_minutes' => 1440,
                'max_posts_per_day' => 1,
                'prefer_israel' => 1,
                'preferred_domains' => '',
                'topic_keywords' => "עוגות, עוגיות, קינוחים, אפייה מתוקה, מתכון, אירוח",
                'research_focus' => 'בחר נושא איכותי בתחום הקינוחים והאפייה המתוקה, עם ערך מעשי ועניין רחב.',
            ],
            [
                'category_name' => 'פנאי',
                'menu_group' => 'פנאי',
                'content_mode' => 'evergreen',
                'search_window_hours' => 720,
                'generation_interval_minutes' => 1440,
                'max_posts_per_day' => 1,
                'prefer_israel' => 1,
                'preferred_domains' => "tourism.gov.il, parks.org.il, gov.il, culture.gov.il, reuters.com",
                'topic_keywords' => "פנאי, בילוי, משפחות, חופשה, אטרקציות, סופי שבוע, חגים, המלצות",
                'research_focus' => 'בחר נושא פנאי שימושי ורלוונטי לקהל הישראלי: בילוי משפחתי, מדריך עונתי, אטרקציה, פעילות לחג או המלצה בעלת ערך.',
            ],
            [
                'category_name' => 'טיולים',
                'menu_group' => 'פנאי',
                'content_mode' => 'evergreen',
                'search_window_hours' => 720,
                'generation_interval_minutes' => 1440,
                'max_posts_per_day' => 1,
                'prefer_israel' => 1,
                'preferred_domains' => "parks.org.il, tourism.gov.il, gov.il, weather.com, israel.travel",
                'topic_keywords' => "טיולים, מסלולים, משפחות, חופשה, אתר טבע, מזג אוויר, אטרקציות בישראל",
                'research_focus' => 'בחר נושא טיולים שימושי ועונתי לקהל הישראלי: מסלול, יעד, המלצה, הכנה לחופשה או רעיון לטיול משפחתי.',
            ],
        ];
    }

    public static function build_profile_defaults(array $blueprint): array
    {
        $mode = $blueprint['content_mode'] ?? 'news';
        $category_name = (string) ($blueprint['category_name'] ?? 'כללי');
        $research_focus = (string) ($blueprint['research_focus'] ?? '');

        return [
            'category_id' => 0,
            'category_name' => $category_name,
            'category_slug' => sanitize_title($category_name),
            'menu_group' => (string) ($blueprint['menu_group'] ?? 'כללי'),
            'content_mode' => $mode,
            'enabled' => 1,
            'post_status' => 'draft',
            'search_window_hours' => absint($blueprint['search_window_hours'] ?? 12),
            'generation_interval_minutes' => absint($blueprint['generation_interval_minutes'] ?? 180),
            'max_posts_per_day' => absint($blueprint['max_posts_per_day'] ?? 1),
            'prefer_israel' => !empty($blueprint['prefer_israel']) ? 1 : 0,
            'preferred_domains' => (string) ($blueprint['preferred_domains'] ?? ''),
            'blocked_domains' => '',
            'topic_keywords' => (string) ($blueprint['topic_keywords'] ?? ''),
            'research_brief' => self::research_brief($category_name, $mode, $research_focus),
            'title_prompt' => self::title_prompt($category_name, $mode),
            'article_prompt' => self::article_prompt($category_name, $mode),
            'image_prompt' => self::image_prompt($category_name, $mode),
            'web_search_mode' => 'inherit',
            'text_model_override' => '',
            'qa_model_override' => '',
            'vision_model_override' => '',
            'image_model_override' => '',
            'image_size_override' => '',
            'image_quality_override' => '',
        ];
    }

    public static function research_brief(string $category_name, string $mode, string $focus): string
    {
        $mode_instruction = match ($mode) {
            'evergreen' => 'בחר נושא איכותי ושימושי המתאים לכתבת מגזין או מדריך מקצועי, אך ודא שהוא נשען על מידע מעודכן ולא על קלישאות ריקות.',
            'press_release' => 'בחר הודעה רשמית חדשה ובעלת עניין לציבור, אך מטרת הכתיבה הסופית היא להפוך אותה לכתבה עצמאית ולא לשמר סגנון דוברותי.',
            default => 'בחר רק אירוע חדשותי חדש, טרי ומובהק שנחשף לראשונה בתוך חלון הזמן שהוגדר.',
        };

        return trim(<<<PROMPT
אתה עורך משימות בכיר באתר חדשות ארצי בשם "חדשות מבזק לייב".

המשימה שלך היא לבחור נושא אחד בלבד עבור קטגוריית "{$category_name}".

{$mode_instruction}

דגש ייעודי לקטגוריה:
{$focus}

כללי בחירה מחייבים:
1. בחר נושא בעל ערך ציבורי ברור, לא נושא שולי או חלש.
2. עבור תוכן חדשותי, האירוע או ההתפתחות המרכזית חייבים להיות חדשים בתוך חלון הזמן שהוגדר. אין לבחור סיפור ישן רק כי פורסם מחדש.
3. העדף נושאים שיש עליהם בסיס עובדתי מוצק, לפחות שני מקורות אמינים כאשר הדבר אפשרי.
4. אם יש זיקה לישראל והיא רלוונטית לקטגוריה, תן לכך עדיפות.
5. אם אין נושא טוב מספיק, החזר החלטה לדלג.
6. אל תבחר נושא שכבר פורסם באתר לאחרונה או דומה מאוד לכותרות האחרונות שנמסרו לך.
7. הימנע מתוכן שמסתמך על שמועה אחת לא מאומתת, על רשתות חברתיות בלבד או על פרשנות חסרת בסיס.
8. עבור פרשות משפטיות, ביטחוניות, מדיניות או רפואיות, הקפד במיוחד על דיוק וזהירות.
PROMPT);
    }

    public static function title_prompt(string $category_name, string $mode): string
    {
        $mode_line = $mode === 'evergreen'
            ? 'הכותרת יכולה להיות מגזינית ומסקרנת, אך עליה להישאר רצינית, מדויקת ונטולת הבטחות שווא.'
            : 'הכותרת צריכה להיות חדשותית, חדה, מסקרנת ומדויקת.';

        return trim(<<<PROMPT
אתה עורך כותרות בכיר באתר חדשות ארצי.

על בסיס המידע העובדתי שאספק לך, כתוב כותרת אחת בלבד לכתבה בקטגוריית "{$category_name}".

{$mode_line}

כללים מחייבים:
1. החזר כותרת אחת בלבד, ללא הסברים וללא טקסט נוסף.
2. הכותרת חייבת להיות נאמנה לעובדות. אין להמציא, להקצין או לרמוז למידע שלא קיים.
3. אורך הכותרת עד 70 תווים, אלא אם נדרש חריג קטן לצורך עברית טבעית.
4. אין להשתמש בנקודה־פסיק ; בשום מצב.
5. אין נקודה בסוף הכותרת.
6. מותר להשתמש בנקודתיים, פסיק או שאלה מסקרנת כאשר הדבר מחזק את הכותרת.
7. מותר להשתמש בציטוט קצר רק אם הוא הופיע במידע שסופק והוא מרכזי לכתבה.
8. הימנע מכותרות זולות כמו "לא תאמינו" או "העולם בהלם".
9. אם יש מתח בין שתי עובדות, שאלה פתוחה, החלטה דרמטית או פרט שמייצר סקרנות אמיתית, מותר לבנות עליו כותרת קליקבילית אך מקצועית.
10. אם בכותרת קיים ניסוח שעלול לגרום לקורא להבין קביעה חמורה מדי ביחס לעובדות, רכך אותו.

המידע שעליו תתבסס:
{{FACTS}}
PROMPT);
    }

    public static function article_prompt(string $category_name, string $mode): string
    {
        $mode_section = match ($mode) {
            'evergreen' => <<<TXT
הכתבה היא כתבת מגזין/שירות מקצועית, לא ידיעה מבזקית. עליה להיות שימושית, ממוקדת, מסבירה ובעלת ערך ממשי לקורא, בלי להפוך לפרסומת ובלי לייצר קביעות שאינן מבוססות.
TXT,
            'press_release' => <<<TXT
הטקסט מבוסס על הודעה רשמית, אך אסור לכתוב אותו בסגנון דוברותי. כתוב ככתב שטח או כעיתונאי בכיר באתר חדשות ארצי. פתח בסיפור עצמו, לא בגוף שפרסם את ההודעה. ציטוטים שסופקו יש לשמור במדויק.
TXT,
            default => <<<TXT
הכתבה היא כתבה חדשותית מקצועית. פתח מיד בעיקר ההתפתחות, הסבר את התמונה המלאה והוסף רקע רק כאשר הוא נחוץ ומבוסס.
TXT,
        };

        return trim(<<<PROMPT
אתה כתב בכיר ועורך ותיק באתר חדשות ארצי בישראל.

כתוב כתבה מלאה, מקצועית ומלוטשת לקטגוריית "{$category_name}".

{$mode_section}

פלט נדרש:
1. החזר גוף כתבה בלבד, ללא כותרת וללא הערות מערכת.
2. הכתבה תיכתב בפסקאות HTML עטופות בתגיות <p>...</p> בלבד.
3. אין להוסיף כותרות משנה, רשימות או טבלאות, אלא אם הדבר נדרש במפורש במידע שסופק.

מבנה וסגנון:
1. הפסקה הראשונה חייבת להיכנס מיד לעיקר.
2. אם האירוע התרחש היום, אתמול או מחר, השתמש בניסוחים "היום", "אתמול" או "מחר" במקום תאריך מלא, אלא אם התאריך חיוני להבנה.
3. כתוב בעברית תקנית, זורמת, מקצועית וחדה.
4. הפסקאות יהיו מלאות ומסודרות, לא משפטים בודדים.
5. אין להשתמש במקף ארוך.
6. אין להזכיר שמות של כלי תקשורת, סוכנויות ידיעות או אתרי חדשות בתוך הכתבה, אלא אם הדבר חיוני לנושא עצמו.
7. מספרים ייכתבו בספרות.
8. ציטוטים ייכתבו במרכאות עבריות ״ ״.
9. אל תכתוב ניסוחים שמסגירים בינה מלאכותית או הוראות פרומפט.

כללי דיוק מחייבים:
1. אין להוסיף מידע שלא נמסר בחבילת העובדות.
2. אין להמציא מומחים, גורמים, שמות, תאריכים, ציטוטים, נתונים או תגובות.
3. כל ציטוט חייב להופיע במפורש במידע שסופק.
4. כאשר מדובר בחשדות, טענות, בדיקות, הליכים, דיווחים לא מאומתים או מידע שאינו סופי, השתמש בניסוח זהיר.
5. אם כמות המידע המאומת מוגבלת, כתוב כתבה קצרה ומדויקת יותר במקום לנפח אותה בכוח.
6. אל תהפוך ידיעה ממוקדת לכתבת עומק ארוכה מדי. העדף חדות, ריכוז וערך חדשותי.
7. כל פסקה חייבת להוסיף מידע אמיתי, הקשר נחוץ או הבהרה לקורא.

אורך מומלץ:
- ידיעה קצרה: 4 עד 6 פסקאות.
- כתבה חדשותית משמעותית: 6 עד 9 פסקאות.
- כתבת מגזין או שירות: 7 עד 10 פסקאות, רק אם החומר מצדיק זאת.

הכותרת שאושרה:
{{TITLE}}

חבילת העובדות המאומתת:
{{FACTS}}
PROMPT);
    }

    public static function image_prompt(string $category_name, string $mode): string
    {
        return trim(<<<PROMPT
אתה עורך ויזואלי בכיר באתר חדשות ישראלי.

על בסיס הכותרת וגוף הכתבה, צור תיאור מדויק לתמונה ראשית עבור כתבה בקטגוריית "{$category_name}".

מטרת התמונה:
ליצור תמונה חזקה, אמינה, רלוונטית מאוד לסיפור, המתאימה כתמונה ראשית לרוחב באתר וורדפרס.

כללים לתיאור התמונה:
1. התיאור ליצירת התמונה חייב להיות באנגלית.
2. התמונה חייבת להיות אופקית, ביחס 16:9, מתאימה לתמונה ראשית.
3. התמונה צריכה להציג סצנה אחת ברורה, לא קולאז', לא מסך מפוצל ולא באנר.
4. הסגנון: photorealistic editorial news illustration, high resolution, documentary atmosphere, credible and serious.
5. התמונה חייבת להיות רלוונטית ממש לנושא הכתבה ולזווית המרכזית שלה.
6. אם אין דרך ליצור תמונה ספציפית בלי להמציא פרטים, בחר המחשה ניטרלית, אמינה ומדויקת לרוח האירוע.
7. חל איסור מוחלט על כל טקסט גלוי בתמונה, בכל שפה ובכל צורה:
   no words, no letters, no numbers, no captions, no headlines, no signs with readable writing, no labels, no banners, no logos, no watermarks, no pseudo-text, no typography.
8. אין ליצור פוסטר, כרטיס רשת חברתית, שער מגזין, גרפיקה חדשותית, אינפוגרפיקה או תמונת thumbnail עם כותרת.
9. הימנע מדמויות ציבוריות מזוהות ומפנים ברורות, אלא אם הדבר הכרחי לחלוטין.
10. אין ליצור גופות, דם, פציעות גרפיות או מראות קשים.
11. בנושאי ביטחון, טרור, צבא או תקיפה, העדף דימוי מרומז, מדויק ומאופק, לא סצנה פרובוקטיבית או מוגזמת.
12. התאם את תאורת היום או הלילה להקשר אם הוא ידוע.

פלט נדרש:
החזר מידע שיאפשר ליצור את התמונה ולמלא את שדות המדיה:
- IMAGE_PROMPT באנגלית
- CAPTION בערך המדויק: אילוסטרציה. חדשות מבזק לייב AI
- ALT_TEXT בעברית
- MEDIA_KEYWORDS בעברית, 6 עד 12 ביטויים קצרים

כותרת הכתבה:
{{TITLE}}

גוף הכתבה:
{{ARTICLE}}
PROMPT);
    }

    public static function editorial_review_prompt(): string
    {
        return trim(<<<PROMPT
אתה עורך ראשי קשוח באתר חדשות ארצי. בדוק את הכותרת והכתבה לפני פרסום.

בדוק במיוחד:
1. האם הכותרת מדויקת ואינה מגזימה ביחס לעובדות.
2. האם הכתבה נשענת רק על חבילת העובדות שסופקה.
3. האם יש ציטוטים או גורמים שלא נמסרו.
4. האם יש ניסוחים שנשמעים מלאכותיים, עמומים או דוברתיים מדי.
5. האם הכתבה מנופחת מעבר לצורך או סוטה לנושאי רקע לא רלוונטיים.
6. האם יש זליגת הוראות פרומפט לתוך הכתבה.
7. האם יש בעיה משפטית בסיסית בניסוח טענות, חשדות או אחריות פלילית.
8. האם הכתבה מתאימה לסגנון אתר חדשות ישראלי מקצועי.

אם אפשר לתקן בלי לפגוע בעובדות, הצע נוסח מתוקן לכותרת ולכתבה. אם הבעיה מהותית או שהחומר לא אמין מספיק, דחה את הפרסום.
PROMPT);
    }

    public static function image_review_prompt(): string
    {
        return trim(<<<PROMPT
אתה עורך ויזואלי ובודק איכות לפני פרסום באתר חדשות.

בדוק את התמונה המצורפת מול כותרת הכתבה ותמצית הסיפור.

פסול את התמונה אם מתקיים אחד מאלה:
1. יש בתמונה טקסט גלוי מכל סוג, בכל שפה, כולל אותיות, מספרים, כותרות, שלטים קריאים, סימון AI, לוגו או watermark.
2. התמונה אינה קשורה באופן ברור לנושא הכתבה.
3. התמונה מטעה חזותית או מייצרת פרט עובדתי שלא מופיע בכתבה.
4. יש פנים מזוהות או דמות ציבורית לא הכרחית.
5. היא נראית כמו פוסטר, thumbnail, באנר או גרפיקה ולא כמו תמונת כתבה אמינה.

אשר רק תמונה נקייה, רלוונטית, אופקית ומתאימה לתמונה ראשית באתר חדשות.
PROMPT);
    }
}
