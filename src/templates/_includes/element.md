The variable `{{ name|e }}` {{ isArray ? 'is an array that contains' : 'is' }} an element of type `{{ value.className|e }}` and cannot be passed into a Sprig component.

Instead of passing an element, consider passing its `id`.

---

Before:
```
{{ '{{' }} sprig('_components/my-component', { '{{ name }}': {{ name }} }) {{ '}}' }}
```

After:
```
{{ '{{' }} sprig('_components/my-component', { '{{ name }}Id': {{ name }}.id }) {{ '}}' }}
```

---

Then in your component you can fetch the element using its `id`.

{% if value.className starts with 'craft\\' -%}
```
{{ '{%' }} set {{ name }} = craft.{{ value.pluralDisplayName|camel }}.id({{ name }}Id).one() {{ '%}' }}
```
{%- endif %}
