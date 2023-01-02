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
        return 'build ok';
    }

    /**
     * @return Theme
     */
    public function getTheme(): Theme
    {
        return $this->theme;
    }
}