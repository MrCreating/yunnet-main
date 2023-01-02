<?php

namespace unt\objects;

class UTHTheme extends BaseObject
{
    private Theme $theme;

    public function __construct(Theme $contextTheme)
    {
        $this->theme = $contextTheme;

        parent::__construct();
    }

    public function build (): string
    {
        return json_encode(serialize(serialize([
            'title'       => $this->theme->getTitle(),
            'description' => $this->theme->getDescription(),

            'data' => [
                'css' => $this->theme->getCSSCode(),
                'js'  => $this->theme->getJSCode()
            ]
        ])));
    }

    public function length (): int
    {
        return strlen($this->build());
    }

    /**
     * @return Theme
     */
    public function getTheme(): Theme
    {
        return $this->theme;
    }
}