<?php

namespace unt\design;

use unt\objects\BaseObject;

class Icon extends BaseObject
{
    protected string $path;

    protected int $width = 24;
    protected int $height = 24;

    public function __construct(string $name)
    {
        parent::__construct();

        $this->path = __DIR__ . '/icons/' . basename($name) . '.php';
    }

    /**
     * @param int $width
     * @return Icon
     */
    public function setWidth(int $width): Icon
    {
        $this->width = $width;
        return $this;
    }

    /**
     * @param int $height
     * @return Icon
     */
    public function setHeight(int $height): Icon
    {
        $this->height = $height;
        return $this;
    }

    public function render (bool $with_html = true): string
    {
        ob_start();
        include $this->path;
        return ($with_html === true ? '<i style="padding-top: 2px">' : '') . ob_get_clean() . ($with_html === true ? '</i>' : '');
    }

    public function show (bool $with_html = true): void
    {
        echo $this->render($with_html);
    }

    /////////////////////////////
    public static function get (string $name): ?Icon
    {
        return new static($name);
    }
}