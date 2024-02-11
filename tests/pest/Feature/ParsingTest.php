<?php

/**
 * Tests the parsing of HTML in components.
 */

use putyourlightson\sprig\Sprig;
use yii\web\Request;

beforeEach(function() {
    Sprig::bootstrap();
});

test('Parsing tag attributes', function() {
    $html = '<div sprig s-method="post" s-action="a/b/c" s-vals=\'{"limit":1}\'></div>';

    expect(Sprig::$core->components->parse($html))
        ->toContain(
            'data-hx-post=',
            'data-hx-headers="{&quot;' . Request::CSRF_HEADER . '&quot;',
            '&quot;limit&quot;:1',
            'data-sprig-parsed',
        );
});

test('Parsing tag attributes with a `data` prefix', function() {
    $html = '<div data-sprig></div>';

    expect(Sprig::$core->components->parse($html))
        ->toContain('data-hx-get=');
});

test('Parsing tag attributes with spaces', function(string $html) {
    expect(Sprig::$core->components->parse($html))
        ->toContain('data-hx-target="#id"');
})->with([
    '<div s-target = "#id" ></div>',
    '<div s-target = \'#id\' ></div>',
    '<div s-target = #id ></div>',
    '<div s-target = #id' . PHP_EOL . '></div>',
]);

test('Parsing tag attributes with tabs', function(string $html) {
    expect(Sprig::$core->components->parse($html))
        ->toContain('data-hx-target="#id"');
})->with([
    '<div    s-target = "#id"></div>',
    '<div    s-target = "#id"    ></div>',
]);

test('Parsing tag attributes with line breaks', function(string $html) {
    expect(Sprig::$core->components->parse($html))
        ->toContain('data-hx-get=');
})->with([
    '<div sprig class="a' . PHP_EOL . 'b"></div>',
]);

test('Parsing tag attribute values', function() {
    $html = '<div s-val:x-y-z="a" s-vals=\'{"limit":1}\'></div>';

    expect(Sprig::$core->components->parse($html))
        ->toContain('data-hx-vals="{&quot;xYZ&quot;:&quot;a&quot;,&quot;limit&quot;:1}"');
});

test('Parsing tag attribute values when empty', function() {
    $html = '<div s-val:x-y-z=""></div>';

    expect(Sprig::$core->components->parse($html))
        ->toContain('data-hx-vals="{&quot;xYZ&quot;:&quot;&quot;}"');
});

test('Parsing tag attribute values when encoded', function() {
    $html = '<div s-vals=\'{&quot;limit&quot;:1}\'></div>';

    expect(Sprig::$core->components->parse($html))
        ->toContain('data-hx-vals="{&quot;limit&quot;:1}"');
});

test('Parsing tag attribute values with square brackets', function() {
    $html = '<div s-val:fields[x-y-z]="a"></div>';

    expect(Sprig::$core->components->parse($html))
        ->toContain('data-hx-vals="{&quot;fields[xYZ]&quot;:&quot;a&quot;}"');
});

test('Parsed tag attribute values are encoded and sanitized', function() {
    $html = '<div s-val:x="alert(\'xss\')" s-val:z=\'alert("xss")\' s-vals=\'{"limit":1}\'></div>';

    expect(Sprig::$core->components->parse($html))
        ->toContain('data-hx-vals="{&quot;x&quot;:&quot;alert(\u0027xss\u0027)&quot;,&quot;z&quot;:&quot;alert(\u0022xss\u0022)&quot;,&quot;limit&quot;:1}"');
});

test('Parsing an `s-cache` tag attribute', function() {
    $html = '<div s-cache></div>';

    expect(Sprig::$core->components->parse($html))
        ->toContain('data-hx-headers="{&quot;S-Cache&quot;:true}"');
});

test('Parsing an `s-cache` tag attribute with a value', function() {
    $html = '<div s-cache="10"></div>';

    expect(Sprig::$core->components->parse($html))
        ->toContain('data-hx-headers="{&quot;S-Cache&quot;:&quot;10&quot;}"');
});

test('Parsing an `s-on` tag attribute', function() {
    $html = '<div s-on:htmx:before-request="a"></div>';

    expect(Sprig::$core->components->parse($html))
        ->toContain('data-hx-on:htmx:before-request="a"');
});

test('Parsing an `s-on` shorthand tag attribute', function() {
    $html = '<div s-on::before-request="a"></div>';

    expect(Sprig::$core->components->parse($html))
        ->toContain('data-hx-on::before-request="a"');
});

test('Parsing an `s-listen` tag attribute', function() {
    $html = '<div s-listen="#component1, #component2"></div>';

    expect(Sprig::$core->components->parse($html))
        ->toContain('data-hx-trigger="htmx:afterOnLoad from:#component1,htmx:afterOnLoad from:#component2"');
});

test('Parsing empty HTML', function() {
    $html = '';

    expect(Sprig::$core->components->parse($html))
        ->toBe($html);
});

test('Parsing non-empty HTML', function() {
    $html = '<div><p><span><template><h1>Hello</h1></template></span></p></div>';

    expect(Sprig::$core->components->parse($html))
        ->toBe($html);
});

test('Parsing HTML with duplicate IDs', function() {
    $html = '<div id="my-id"><p id="my-id"><span id="my-id"></span></p></div>';

    expect(Sprig::$core->components->parse($html))
        ->toBe($html);
});

test('Parsing HTML with a comment', function() {
    $html = '<!-- Comment mentioning sprig -->';

    expect(Sprig::$core->components->parse($html))
        ->toBe($html);
});

test('Parsing HTML with a script tag', function() {
    $html = '<script>if(i < 1) let sprig=1</script>';

    expect(Sprig::$core->components->parse($html))
        ->toBe($html);
});

test('Parsing HTML with UTF encoded characters', function() {
    $placeholder = 'ÆØÅäöü';
    $html = '<div sprig placeholder="' . $placeholder . '"></div>';

    expect(Sprig::$core->components->parse($html))
        ->toContain($placeholder);
});
