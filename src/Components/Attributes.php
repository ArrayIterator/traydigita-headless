<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Components;

use function array_keys;
use function array_values;
use function esc_attr;
use function explode;
use function implode;
use function is_array;
use function is_numeric;
use function is_scalar;
use function is_string;
use function preg_replace;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function strtolower;
use function substr;
use function trim;

class Attributes
{
    /**
     * @var array<string,string> HTML attributes list
     */
    public const HTML_ATTRIBUTES = [
        "accept" => "accept",
        "accept-charset" => "accept-charset",
        "accesskey" => "accesskey",
        "action" => "action",
        "align" => "align",
        "alt" => "alt",
        "async" => "async",
        "autocomplete" => "autocomplete",
        "autofocus" => "autofocus",
        "autoplay" => "autoplay",
        "bgcolor" => "bgcolor",
        "border" => "border",
        "charset" => "charset",
        "checked" => "checked",
        "cite" => "cite",
        "class" => "class",
        "color" => "color",
        "cols" => "cols",
        "colspan" => "colspan",
        "content" => "content",
        "contenteditable" => "contenteditable",
        "controls" => "controls",
        "coords" => "coords",
        "data" => "data",
        // "data-*" => "data-*",
        "datetime" => "datetime",
        "default" => "default",
        "defer" => "defer",
        "dir" => "dir",
        "dirname" => "dirname",
        "disabled" => "disabled",
        "download" => "download",
        "draggable" => "draggable",
        "enctype" => "enctype",
        "for" => "for",
        "form" => "form",
        "formaction" => "formaction",
        "headers" => "headers",
        "height" => "height",
        "hidden" => "hidden",
        "high" => "high",
        "href" => "href",
        "hreflang" => "hreflang",
        "http-equiv" => "http-equiv",
        "id" => "id",
        "ismap" => "ismap",
        "kind" => "kind",
        "label" => "label",
        "lang" => "lang",
        "list" => "list",
        "loop" => "loop",
        "low" => "low",
        "max" => "max",
        "maxlength" => "maxlength",
        "media" => "media",
        "method" => "method",
        "min" => "min",
        "multiple" => "multiple",
        "muted" => "muted",
        "name" => "name",
        "novalidate" => "novalidate",
        "onabort" => "onabort",
        "onafterprint" => "onafterprint",
        "onbeforeprint" => "onbeforeprint",
        "onbeforeunload" => "onbeforeunload",
        "onblur" => "onblur",
        "oncanplay" => "oncanplay",
        "oncanplaythrough" => "oncanplaythrough",
        "onchange" => "onchange",
        "onclick" => "onclick",
        "oncontextmenu" => "oncontextmenu",
        "oncopy" => "oncopy",
        "oncuechange" => "oncuechange",
        "oncut" => "oncut",
        "ondblclick" => "ondblclick",
        "ondrag" => "ondrag",
        "ondragend" => "ondragend",
        "ondragenter" => "ondragenter",
        "ondragleave" => "ondragleave",
        "ondragover" => "ondragover",
        "ondragstart" => "ondragstart",
        "ondrop" => "ondrop",
        "ondurationchange" => "ondurationchange",
        "onemptied" => "onemptied",
        "onended" => "onended",
        "onerror" => "onerror",
        "onfocus" => "onfocus",
        "onhashchange" => "onhashchange",
        "oninput" => "oninput",
        "oninvalid" => "oninvalid",
        "onkeydown" => "onkeydown",
        "onkeypress" => "onkeypress",
        "onkeyup" => "onkeyup",
        "onload" => "onload",
        "onloadeddata" => "onloadeddata",
        "onloadedmetadata" => "onloadedmetadata",
        "onloadstart" => "onloadstart",
        "onmousedown" => "onmousedown",
        "onmousemove" => "onmousemove",
        "onmouseout" => "onmouseout",
        "onmouseover" => "onmouseover",
        "onmouseup" => "onmouseup",
        "onmousewheel" => "onmousewheel",
        "onoffline" => "onoffline",
        "ononline" => "ononline",
        "onpagehide" => "onpagehide",
        "onpageshow" => "onpageshow",
        "onpaste" => "onpaste",
        "onpause" => "onpause",
        "onplay" => "onplay",
        "onplaying" => "onplaying",
        "onpopstate" => "onpopstate",
        "onprogress" => "onprogress",
        "onratechange" => "onratechange",
        "onreset" => "onreset",
        "onresize" => "onresize",
        "onscroll" => "onscroll",
        "onsearch" => "onsearch",
        "onseeked" => "onseeked",
        "onseeking" => "onseeking",
        "onselect" => "onselect",
        "onstalled" => "onstalled",
        "onstorage" => "onstorage",
        "onsubmit" => "onsubmit",
        "onsuspend" => "onsuspend",
        "ontimeupdate" => "ontimeupdate",
        "ontoggle" => "ontoggle",
        "onunload" => "onunload",
        "onvolumechange" => "onvolumechange",
        "onwaiting" => "onwaiting",
        "onwheel" => "onwheel",
        "open" => "open",
        "optimum" => "optimum",
        "pattern" => "pattern",
        "placeholder" => "placeholder",
        "poster" => "poster",
        "preload" => "preload",
        "readonly" => "readonly",
        "rel" => "rel",
        "required" => "required",
        "reversed" => "reversed",
        "rows" => "rows",
        "rowspan" => "rowspan",
        "sandbox" => "sandbox",
        "scope" => "scope",
        "selected" => "selected",
        "shape" => "shape",
        "size" => "size",
        "sizes" => "sizes",
        "span" => "span",
        "spellcheck" => "spellcheck",
        "src" => "src",
        "srcdoc" => "srcdoc",
        "srclang" => "srclang",
        "srcset" => "srcset",
        "start" => "start",
        "step" => "step",
        "style" => "style",
        "tabindex" => "tabindex",
        "target" => "target",
        "title" => "title",
        "translate" => "translate",
        "type" => "type",
        "usemap" => "usemap",
        "value" => "value",
        "width" => "width",
        "wrap" => "wrap"
    ];

    /**
     * Sanitizes an HTML class name.
     *
     * @param string $classname
     * @return string
     */
    public function sanitizeHTMLClass(string $classname): string
    {
        if (!$classname || !($classname = trim($classname))) {
            return '';
        }
//        // Strip out any percent-encoded characters.
//        $sanitized = str_contains($classname, '%')
//            ? preg_replace('|%[a-fA-F0-9][a-fA-F0-9]|', '', $classname)
//            : $classname;
        // Limit to A-Z, a-z, 0-9, '_', '-'.
        return preg_replace('/%[a-fA-F0-9][a-fA-F0-9]|[^A-Za-z0-9_-]/', '', $classname);
    }

    /**
     * Merges class names into a unique list.
     *
     * @param mixed $item The class name(s) to merge.
     * @param array<string, int> $classList The list of unique class names.
     */
    private function doMergeClasses(mixed $item, array &$classList): void
    {
        if (!$item) {
            return;
        }
        if (is_string($item)) {
            $item = trim($item);
            if (!$item) {
                return;
            }
            if (!str_contains($item, ' ')) {
                $item = $this->sanitizeHTMLClass($item);
                if ($item) {
                    $classList[$item] = 1;
                }
                return;
            }
            $item = explode(' ', $item);
        }
        if (!$item || !is_array($item)) {
            return;
        }
        foreach ($this->filterClass(...array_values($item)) as $i) {
            $classList[$i] = 1;
        }
    }

    /**
     * Filters and sanitizes a list of class names, returning a unique array of valid class names.
     *
     * @param mixed ...$classes
     * @return string[] unique sanitized class list
     */
    public function filterClass(mixed ...$classes): array
    {
        if (empty($classes)) {
            return [];
        }
        $classList = [];
        foreach ($classes as $item) {
            $this->doMergeClasses($item, $classList);
        }
        return array_values(array_keys($classList));
    }

    /**
     * Sanitizes an HTML attribute key.
     *
     * @param mixed $key The attribute key to sanitize.
     * @return string|null The sanitized attribute key, or null if invalid.
     */
    public function sanitizeKeyAttribute(mixed $key): ?string
    {
        if (!$key || !is_string($key)) {
            return null;
        }
        $key = trim($key);
        $key = $key ? str_replace(' ', '-', $key) : '';
        if (!$key) {
            return null;
        }
        $lower_key = strtolower($key);
        if (isset(self::HTML_ATTRIBUTES[$lower_key])) {
            return self::HTML_ATTRIBUTES[$lower_key];
        }
        if (str_starts_with($lower_key, 'data-')) {
            $key = 'data-' . substr($lower_key, 5);
        }
        return $key;
    }

    /**
     * Builds a string of HTML attributes from an associative array.
     *
     * @param array<string, mixed> $attributes The associative array of attributes.
     * @return string The string of HTML attributes.
     */
    public function buildAttributes(array $attributes): string
    {
        $attr = '';
        foreach ($attributes as $key => $item) {
            $key = $this->sanitizeKeyAttribute($key);
            if (!$key) {
                continue;
            }
            if ($key === 'class') {
                if (!$item) {
                    continue;
                }
                $item = implode(' ', $this->filterClass($item));
                if (!$item) {
                    continue;
                }
            } elseif ($key === 'id') {
                if (!is_string($item)) {
                    continue;
                }
                $item = self::sanitizeHTMLClass($item);
                if (!$item) {
                    continue;
                }
            } elseif (!is_scalar($item)) {
                continue;
            }
            $item = $item === true ? 'true' : ($item === false ? 'false' : $item);
            $attr .= $attr ? ' ' : '';
            $attr .= esc_attr($key) . '="' . ($item === '' || is_numeric($item) ? $item : esc_attr((string)$item)). '"';
        }
        return trim($attr);
    }
}
