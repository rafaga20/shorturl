<?php

namespace core;

class View
{
    private string $template, $template_parent;
    private array $data, $data_parent;
    private bool $is_repeat = false;
    private Request $request;

    public function __construct(Request $request = new Request(), string $template = '', array $data = [], string $parent = '')
    {
        $this->request = $request;
        $this->data = $data;
        $this->data_parent = [];
        $this->template = $this->prepareTemplate($template);
        $this->template_parent = $this->prepareTemplate($parent, true);

        $this->loadParentData();
    }

    private function loadParentData(): void
    {
        if (!$this->template_parent) {
            return;
        }

        $this->addVariable('title', Constant::HTML_HEADER_TITLE, true, true);
        $this->addVariable('description', Constant::HTML_HEADER_DESCRIPTION, true, true);
        $this->addVariable('version', Constant::ASSETS_VERSION, true, true);
        if (Constant::HTML_HEADER_ICON && file_exists(Constant::HTML_HEADER_ICON)) {
            $favicon = new Html('link', closed: false);
            $favicon->addAttribute('rel', 'icon');
            $favicon->addAttribute('type', 'image/x-icon');
            $favicon->addAttribute('href', Constant::HTML_HEADER_ICON);

            $this->addVariable('favicon', $favicon->getHtml(), true, true);
        }
    }

    private function prepareTemplate(string $template, bool $parent = false): string
    {
        $assets = function ($template): void {
            if (file_exists($filename = $this->filename($template, "assets.js", 'js'))) {
                $script = new Html('script');
                $script->addAttribute('src', "/$filename?v=" . Constant::ASSETS_VERSION);
                $this->addVariable('js', $script->getHtml(), true, true);
                $this->addVariable('js', $script->getHtml(), true);
            }

            if (file_exists($filename = $this->filename($template, "assets.css", 'css'))) {
                $link = new Html('link');
                $link->addAttribute('rel', 'stylesheet');
                $link->addAttribute('href', "/$filename?v=" . Constant::ASSETS_VERSION);
                $this->addVariable('css', $link->getHtml(), true, true);
                $this->addVariable('css', $link->getHtml(), true);
            }
        };

        if (file_exists($filename = $this->filename($template))) {
            $assets($template);
            return file_get_contents($filename);
        }

        return $template;
    }

    public function setTemplate(string $template): void
    {
        if ($this->is_repeat) {
            return;
        }

        $this->template = $this->prepareTemplate($template);
    }

    private function filename(string $filename, string $dir = 'view', string $ext = 'html'): string
    {
        if (!$filename) {
            return "$filename.none";
        }

        $filename = str_replace([' ', '.'], ['', DIRECTORY_SEPARATOR], $filename);
        $dir = str_replace('.', DIRECTORY_SEPARATOR, $dir);

        return join(DIRECTORY_SEPARATOR, [$dir, strtolower($filename)]) . ".$ext";
    }

    public function get(): string
    {
        if ($this->is_repeat) {
            return $this->template;
        }

        $this->loadVar();
        $this->loadInclude();
        $this->loadParent();

        return $this->template;
    }

    public function repeat(array $data): View
    {
        if ($this->is_repeat) {
            return $this;
        }

        $this->is_repeat = true;
        $view = new View();
        $this->template = join('', array_map(function ($values) use ($view) {
            $view->setTemplate($this->template);
            if (!is_array($values)) {
                return $view->get();
            }

            foreach ($values as $key => $value) {
                $view->addVariable($key, $value);
            }

            return $view->get();
        }, $data));

        return $this;
    }

    public function addVariable(string $key, $data, bool $system = false, bool $parent = false): void
    {
        if ($this->is_repeat) {
            return;
        }

        $var_name = 'data' . ($parent ? '_parent' : '');
        $this->{$var_name}[($system ? strtoupper(sprintf('__%s__', $key)) : $key)] = $data;
    }

    private function loadVar(): void
    {
        preg_match_all(Constant::PREG_VARIABLE, $this->template, $matches);

        foreach ($matches[0] as $key => $match) {
            $this->template = str_replace($match, $this->data[$matches[1][$key]] ?? '', $this->template);
        }
    }

    private function loadParent(): void
    {
        if (!$this->template_parent) {
            return;
        }

        $parent = new View($this->request, $this->template_parent, $this->data_parent);
        $parent_view = $parent->get();
        $this->template = preg_replace(Constant::PREG_PARENT, $this->template, $parent_view);
    }

    private function loadInclude(): void
    {
        if (!preg_match_all(Constant::PREG_INCLUDE, $this->template, $matches)) {
            return;
        }

        foreach ($matches[1] as $key => $controller) {
            if (!($controller = array_filter(explode('.', $controller)))) {
                continue;
            } elseif (count($controller) == 1) {
                $controller[] = 'index';
            }

            $method = array_pop($controller);
            $className = ucfirst(strtolower(array_pop($controller)));
            $class = join('\\', ['controller', ...$controller, $className]);
            if (class_exists($class) && method_exists($class, $method)) {
                $class = new $class($this->request, $this->data);
                $result = $class->{$method}($this->request, $this->data);
                $this->replace($key, $matches, $result?->get() ?? '');
                continue;
            }

            $view = null;
            if (file_exists($this->filename($filename = strtolower(join(DIRECTORY_SEPARATOR, [...$controller, $className, $method]))))) {
                $view = new View($this->request, $filename, $this->data);
            }

            if (!$view && file_exists($this->filename($filename = strtolower(join(DIRECTORY_SEPARATOR, [...$controller, $className]))))) {
                $view = new View($this->request, $filename, $this->data);
            }

            $this->replace($key, $matches, $view?->get() ?? '');
        }
    }

    private function replace(int $key, array $matches, string $value): void
    {
        $this->template = str_replace($matches[0][$key], $value, $this->template);
    }
}