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

    /**
     * @return Theme
     */
    public function getTheme(): Theme
    {
        return $this->theme;
    }
}