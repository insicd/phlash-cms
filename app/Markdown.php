<?php
namespace Phlash;

/**
 * Parser Markdown minimale, pensato per hosting shared.
 * Escapa l'HTML in ingresso e consente solo markup sicuro.
 */
class Markdown
{
    public static function parse(string $text, bool $images = true): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        if (!$images) {
            $text = preg_replace('/\[!\[[^\]]*\]\([^)]+\)\]\([^)]+\)/', '', $text) ?? $text;
            $text = preg_replace('/!\[[^\]]*\]\([^)]+\)/', '', $text) ?? $text;
            $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;
        }
        $text = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $codes = [];
        $text = preg_replace_callback('/```(?:[a-z0-9_-]+)?\n(.*?)```/s', function ($m) use (&$codes) {
            $i = count($codes);
            $codes[$i] = '<pre class="md-code"><code>' . $m[1] . '</code></pre>';
            return "\n%%CODE{$i}%%\n";
        }, $text) ?? $text;

        $parts = preg_split("/\n{2,}/", trim($text)) ?: [];
        $html = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            if (preg_match('/^%%CODE(\d+)%%$/', $part, $m)) {
                $html[] = $codes[(int) $m[1]] ?? '';
                continue;
            }
            if (preg_match('/^&gt; /', $part)) {
                $q = preg_replace('/^&gt; /m', '', $part);
                $html[] = '<blockquote>' . self::inline(str_replace("\n", '<br>', $q)) . '</blockquote>';
                continue;
            }
            if (preg_match('/^### /', $part)) {
                $html[] = '<h3>' . self::inline(preg_replace('/^### /', '', $part)) . '</h3>';
                continue;
            }
            if (preg_match('/^## /', $part)) {
                $html[] = '<h2>' . self::inline(preg_replace('/^## /', '', $part)) . '</h2>';
                continue;
            }
            if (preg_match('/^# /', $part)) {
                $html[] = '<h2>' . self::inline(preg_replace('/^# /', '', $part)) . '</h2>';
                continue;
            }
            if (preg_match('/^[-*] /', $part)) {
                $items = preg_split("/\n/", $part) ?: [];
                $lis = '';
                foreach ($items as $item) {
                    $lis .= '<li>' . self::inline(preg_replace('/^[-*] /', '', $item)) . '</li>';
                }
                $html[] = '<ul>' . $lis . '</ul>';
                continue;
            }
            if (preg_match('/^\d+\. /', $part)) {
                $items = preg_split("/\n/", $part) ?: [];
                $lis = '';
                foreach ($items as $item) {
                    $lis .= '<li>' . self::inline(preg_replace('/^\d+\. /', '', $item)) . '</li>';
                }
                $html[] = '<ol>' . $lis . '</ol>';
                continue;
            }
            $html[] = '<p>' . self::inline(str_replace("\n", '<br>', $part)) . '</p>';
        }

        $out = implode("\n", $html);
        foreach ($codes as $i => $block) {
            $out = str_replace("%%CODE{$i}%%", $block, $out);
        }
        if (!$images) {
            $out = preg_replace('#<p>(?:\s|&nbsp;|<br\s*/?>)*</p>#i', '', $out) ?? $out;
        }
        return $out;
    }

    public static function inlineComment(string $text): string
    {
        $text = htmlspecialchars(str_replace(["\r\n", "\r"], "\n", $text), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;
        return self::inline(nl2br($text, false));
    }

    public static function plain(string $text): string
    {
        $text = preg_replace('/```.*?```/s', ' ', $text) ?? $text;
        $text = preg_replace('/\[(.*?)\]\((.*?)\)/', '$1', $text) ?? $text;
        $text = preg_replace('/[*_`#>]/', '', $text) ?? $text;
        return $text;
    }

    private static function inline(string $text): string
    {
        $text = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text) ?? $text;
        $text = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/s', '<em>$1</em>', $text) ?? $text;
        $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text) ?? $text;
        $text = preg_replace_callback('/!\[([^\]]*)\]\(([^)]+)\)/', function ($m) {
            $href = html_entity_decode($m[2], ENT_QUOTES, 'UTF-8');
            if (!preg_match('#^(https?://|/uploads/)#i', $href)) {
                return $m[1];
            }
            $alt = $m[1];
            return '<img src="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '" alt="' . $alt . '">';
        }, $text) ?? $text;
        $text = preg_replace_callback('/\[([^\]]+)\]\(([^)]+)\)/', function ($m) {
            $href = html_entity_decode($m[2], ENT_QUOTES, 'UTF-8');
            if (!preg_match('#^(https?://|mailto:)#i', $href)) {
                return $m[1];
            }
            return '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '" rel="nofollow noopener" target="_blank">' . $m[1] . '</a>';
        }, $text) ?? $text;
        $text = preg_replace(
            '#(?<!["\'>])(https?://[^\s<]+)#i',
            '<a href="$1" rel="nofollow noopener" target="_blank">$1</a>',
            $text
        ) ?? $text;
        return $text;
    }
}
