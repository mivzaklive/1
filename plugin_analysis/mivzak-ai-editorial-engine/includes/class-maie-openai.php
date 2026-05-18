<?php

if (!defined('ABSPATH')) {
    exit;
}

final class MAIE_OpenAI
{
    private array $settings;

    public function __construct(?array $settings = null)
    {
        $this->settings = $settings ?: MAIE_DB::get_settings();
    }

    public function has_api_key(): bool
    {
        return !empty($this->settings['api_key']);
    }

    public function responses_json(string $prompt, array $schema, array $options = []): array
    {
        $model = sanitize_text_field((string) ($options['model'] ?? $this->settings['text_model'] ?? 'gpt-4o'));
        $tools = [];
        if (!empty($options['web_search']) && !empty($this->settings['web_search_enabled'])) {
            // שם כלי חיפוש הרשת הנכון ב-Responses API של OpenAI
            $tools[] = ['type' => 'web_search_preview'];
        }

        $max_tokens = isset($options['max_output_tokens']) ? (int) $options['max_output_tokens'] : 4096;
        $payload = [
            'model'             => $model,
            'input'             => $prompt,
            'store'             => false,
            'max_output_tokens' => $max_tokens,
            'text'              => [
                'format' => [
                    'type'   => 'json_schema',
                    'name'   => sanitize_key((string) ($options['schema_name'] ?? 'maie_response')),
                    'strict' => true,
                    'schema' => $schema,
                ],
            ],
        ];

        if ($tools) {
            $payload['tools'] = $tools;
        }

        if (!empty($options['reasoning_effort']) && self::model_supports_reasoning($model)) {
            $payload['reasoning'] = ['effort' => sanitize_key((string) $options['reasoning_effort'])];
        }

        $response = $this->request('/v1/responses', $payload);
        if (is_wp_error($response)) {
            return [
                'ok' => false,
                'error' => $response->get_error_message(),
                'raw' => null,
                'data' => null,
                'usage' => [],
            ];
        }

        $json = json_decode((string) wp_remote_retrieve_body($response), true);
        if (!is_array($json)) {
            return [
                'ok' => false,
                'error' => 'OpenAI החזיר JSON לא תקין.',
                'raw' => wp_remote_retrieve_body($response),
                'data' => null,
                'usage' => [],
            ];
        }

        $api_error = $this->extract_api_error($json);
        if ($api_error !== '') {
            return [
                'ok' => false,
                'error' => $api_error,
                'raw' => $json,
                'data' => null,
                'usage' => $json['usage'] ?? [],
            ];
        }

        $text = self::extract_output_text($json);
        if ($text === '') {
            return [
                'ok' => false,
                'error' => 'לא נמצא טקסט פלט בתשובת OpenAI.',
                'raw' => $json,
                'data' => null,
                'usage' => $json['usage'] ?? [],
            ];
        }

        $parsed = json_decode($text, true);
        if (!is_array($parsed)) {
            return [
                'ok' => false,
                'error' => 'הפלט המובנה שהוחזר אינו JSON תקין.',
                'raw' => $json,
                'data' => null,
                'usage' => $json['usage'] ?? [],
                'output_text' => $text,
            ];
        }

        return [
            'ok' => true,
            'error' => '',
            'raw' => $json,
            'data' => $parsed,
            'usage' => $json['usage'] ?? [],
            'output_text' => $text,
        ];
    }

    public function responses_vision_json(string $prompt, string $mime_type, string $base64_image, array $schema, array $options = []): array
    {
        $model = sanitize_text_field((string) ($options['model'] ?? $this->settings['vision_model'] ?? $this->settings['text_model'] ?? 'gpt-4o'));
        $image_url = 'data:' . $mime_type . ';base64,' . $base64_image;

        $payload = [
            'model' => $model,
            'store' => false,
            'input' => [
                [
                    'role' => 'user',
                    'content' => [
                        ['type' => 'input_text', 'text' => $prompt],
                        ['type' => 'input_image', 'image_url' => $image_url],
                    ],
                ],
            ],
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => sanitize_key((string) ($options['schema_name'] ?? 'maie_vision_review')),
                    'strict' => true,
                    'schema' => $schema,
                ],
            ],
        ];

        if (!empty($options['reasoning_effort']) && self::model_supports_reasoning($model)) {
            $payload['reasoning'] = ['effort' => sanitize_key((string) $options['reasoning_effort'])];
        }

        $response = $this->request('/v1/responses', $payload);
        if (is_wp_error($response)) {
            return [
                'ok' => false,
                'error' => $response->get_error_message(),
                'raw' => null,
                'data' => null,
                'usage' => [],
            ];
        }

        $json = json_decode((string) wp_remote_retrieve_body($response), true);
        if (!is_array($json)) {
            return [
                'ok' => false,
                'error' => 'OpenAI החזיר JSON לא תקין בבדיקת התמונה.',
                'raw' => wp_remote_retrieve_body($response),
                'data' => null,
                'usage' => [],
            ];
        }

        $api_error = $this->extract_api_error($json);
        if ($api_error !== '') {
            return [
                'ok' => false,
                'error' => $api_error,
                'raw' => $json,
                'data' => null,
                'usage' => $json['usage'] ?? [],
            ];
        }

        $text = self::extract_output_text($json);
        if ($text === '') {
            return [
                'ok' => false,
                'error' => 'לא נמצא טקסט פלט בבדיקת התמונה.',
                'raw' => $json,
                'data' => null,
                'usage' => $json['usage'] ?? [],
            ];
        }

        $parsed = json_decode($text, true);
        if (!is_array($parsed)) {
            return [
                'ok' => false,
                'error' => 'פלט בדיקת התמונה אינו JSON תקין.',
                'raw' => $json,
                'data' => null,
                'usage' => $json['usage'] ?? [],
                'output_text' => $text,
            ];
        }

        return [
            'ok' => true,
            'error' => '',
            'raw' => $json,
            'data' => $parsed,
            'usage' => $json['usage'] ?? [],
            'output_text' => $text,
        ];
    }

    public function generate_image(string $prompt, array $options = []): array
    {
        $model = MAIE_Options::allowed_image_model(
            sanitize_text_field((string) ($options['model'] ?? $this->settings['image_model'] ?? 'gpt-image-1')),
            'gpt-image-1'
        );
        $size = MAIE_Options::allowed_image_size(
            sanitize_text_field((string) ($options['size'] ?? $this->settings['image_size'] ?? '1536x1024')),
            '1536x1024'
        );
        $quality = MAIE_Options::allowed_image_quality(
            sanitize_text_field((string) ($options['quality'] ?? $this->settings['image_quality'] ?? 'medium')),
            'medium'
        );

        // DALL·E 3 תומך בגדלים שונים ובפרמטרים שונים מ-gpt-image-1
        if ($model === 'dall-e-3') {
            return $this->generate_image_dalle3($prompt, $size, $quality, $options);
        }

        $payload = [
            'model' => $model,
            'prompt' => $prompt,
            'n' => 1,
            'size' => $size,
            'quality' => $quality,
            'output_format' => sanitize_text_field((string) ($this->settings['image_output_format'] ?? 'webp')),
        ];

        $compression = absint($this->settings['image_output_compression'] ?? 90);
        if (in_array($payload['output_format'], ['webp', 'jpeg'], true)) {
            $payload['output_compression'] = min(100, max(1, $compression));
        }

        $response = $this->request('/v1/images/generations', $payload);
        if (is_wp_error($response)) {
            return [
                'ok' => false,
                'error' => $response->get_error_message(),
                'raw' => null,
                'image_b64' => '',
                'format' => $payload['output_format'],
                'usage' => [],
            ];
        }

        $json = json_decode((string) wp_remote_retrieve_body($response), true);
        if (!is_array($json)) {
            return [
                'ok' => false,
                'error' => 'OpenAI החזיר JSON לא תקין בעת יצירת תמונה.',
                'raw' => wp_remote_retrieve_body($response),
                'image_b64' => '',
                'format' => $payload['output_format'],
                'usage' => [],
            ];
        }

        $api_error = $this->extract_api_error($json);
        if ($api_error !== '') {
            return [
                'ok' => false,
                'error' => $api_error,
                'raw' => $json,
                'image_b64' => '',
                'format' => $payload['output_format'],
                'usage' => $json['usage'] ?? [],
            ];
        }

        $image_b64 = (string) ($json['data'][0]['b64_json'] ?? '');
        if ($image_b64 === '') {
            return [
                'ok' => false,
                'error' => 'לא התקבלה תמונה מקודדת מ-OpenAI.',
                'raw' => $json,
                'image_b64' => '',
                'format' => $payload['output_format'],
                'usage' => $json['usage'] ?? [],
            ];
        }

        return [
            'ok' => true,
            'error' => '',
            'raw' => $json,
            'image_b64' => $image_b64,
            'format' => (string) ($json['output_format'] ?? $payload['output_format']),
            'usage' => $json['usage'] ?? [],
        ];
    }

    // תמיכה ב-DALL·E 3 – מחזיר URL, לא base64; ממיר ל-base64 דרך HTTP
    private function generate_image_dalle3(string $prompt, string $size, string $quality, array $options): array
    {
        $dalle3_sizes = ['1024x1024', '1792x1024', '1024x1792'];
        if (!in_array($size, $dalle3_sizes, true)) {
            $size = '1792x1024';
        }
        $dalle3_quality = in_array($quality, ['standard', 'hd'], true) ? $quality : 'standard';

        $payload = [
            'model' => 'dall-e-3',
            'prompt' => $prompt,
            'n' => 1,
            'size' => $size,
            'quality' => $dalle3_quality,
            'response_format' => 'b64_json',
        ];

        $response = $this->request('/v1/images/generations', $payload);
        if (is_wp_error($response)) {
            return ['ok' => false, 'error' => $response->get_error_message(), 'raw' => null, 'image_b64' => '', 'format' => 'png', 'usage' => []];
        }

        $json = json_decode((string) wp_remote_retrieve_body($response), true);
        if (!is_array($json)) {
            return ['ok' => false, 'error' => 'OpenAI החזיר JSON לא תקין בעת יצירת תמונה (DALL·E 3).', 'raw' => wp_remote_retrieve_body($response), 'image_b64' => '', 'format' => 'png', 'usage' => []];
        }

        $api_error = $this->extract_api_error($json);
        if ($api_error !== '') {
            return ['ok' => false, 'error' => $api_error, 'raw' => $json, 'image_b64' => '', 'format' => 'png', 'usage' => $json['usage'] ?? []];
        }

        $image_b64 = (string) ($json['data'][0]['b64_json'] ?? '');
        if ($image_b64 === '') {
            return ['ok' => false, 'error' => 'לא התקבלה תמונה מ-DALL·E 3.', 'raw' => $json, 'image_b64' => '', 'format' => 'png', 'usage' => $json['usage'] ?? []];
        }

        return ['ok' => true, 'error' => '', 'raw' => $json, 'image_b64' => $image_b64, 'format' => 'png', 'usage' => $json['usage'] ?? []];
    }

    private function request(string $path, array $payload)
    {
        $api_key = trim((string) ($this->settings['api_key'] ?? ''));
        if ($api_key === '') {
            return new WP_Error('maie_missing_api_key', 'לא הוגדר מפתח OpenAI API.');
        }

        $response = wp_remote_post('https://api.openai.com' . $path, [
            'timeout' => 180,
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json; charset=utf-8',
            ],
            'body' => wp_json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'data_format' => 'body',
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        if ($code < 200 || $code >= 300) {
            $body = (string) wp_remote_retrieve_body($response);
            $json = json_decode($body, true);
            $message = is_array($json) ? (string) ($json['error']['message'] ?? '') : '';
            if ($message === '') {
                $message = 'בקשת OpenAI נכשלה עם קוד HTTP ' . $code . '.';
            }
            return new WP_Error('maie_openai_http_error', $message, ['status' => $code, 'body' => $body]);
        }

        return $response;
    }

    private function extract_api_error(array $json): string
    {
        if (!empty($json['error']['message'])) {
            return sanitize_text_field((string) $json['error']['message']);
        }

        if (!empty($json['status']) && $json['status'] === 'failed' && !empty($json['incomplete_details']['reason'])) {
            return sanitize_text_field((string) $json['incomplete_details']['reason']);
        }

        return '';
    }

    // רק מודלי o-series (o1, o3, o4...) תומכים בפרמטר reasoning.effort
    private static function model_supports_reasoning(string $model): bool
    {
        return (bool) preg_match('/^o\d/i', $model);
    }

    public static function extract_output_text(array $json): string
    {
        if (!empty($json['output_text']) && is_string($json['output_text'])) {
            return trim($json['output_text']);
        }

        $chunks = [];
        if (!empty($json['output']) && is_array($json['output'])) {
            foreach ($json['output'] as $item) {
                if (!is_array($item) || empty($item['content']) || !is_array($item['content'])) {
                    continue;
                }
                foreach ($item['content'] as $content) {
                    if (!is_array($content)) {
                        continue;
                    }
                    if (!empty($content['text']) && is_string($content['text'])) {
                        $chunks[] = $content['text'];
                    }
                    if (!empty($content['output_text']) && is_string($content['output_text'])) {
                        $chunks[] = $content['output_text'];
                    }
                }
            }
        }

        return trim(implode("\n", $chunks));
    }
}
