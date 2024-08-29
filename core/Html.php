<?php

namespace core;

class Html
{
    private bool $tag_close;
    private string $tag_name, $tag_inner_text;
    private array $attributes;

    public function __construct(string $name, string $text = '', array $attributes = [], bool $closed = true)
    {
        $this->tag_close = $closed;
        $this->tag_name = $name;
        $this->tag_inner_text = $closed ? htmlentities($text) : '';
        $this->attributes = $attributes;
    }

    public function addAttribute(string $name, string $value): void
    {
        $this->attributes[$name] = $value;
    }

    public function innerText(string $text, bool $html = false): void
    {
        if (!$this->tag_close) {
            return;
        }
        $this->tag_inner_text = !$html ? htmlentities($text) : $text;
    }

    public function appendChild(Html $html): void
    {
        $this->innerText(join('', [$this->tag_inner_text, $html->getHtml()]), true);
    }

    public function getHtml(): string
    {
        $tag = new View();
        $tag->setTemplate($this->getFormatTag());
        $tag->addVariable('tag', $this->tag_name, true);
        $tag->addVariable('text', $this->tag_inner_text, true);

        $attr = array_map(fn($key, $value) => ['key' => $key, 'value' => $value], array_keys($this->attributes), $this->attributes);
        $tag->addVariable('attribute', (new View(template: ' {{key}}="{{value}}"'))->repeat($attr)->get(), true);

        return $tag->get();
    }

    private function getFormatTag(): string
    {
        if (!$this->tag_close) {
            return '<{{__TAG__}}{{__ATTRIBUTE__}}/>';
        }

        return '<{{__TAG__}}{{__ATTRIBUTE__}}>{{__TEXT__}}</{{__TAG__}}>';
    }
}