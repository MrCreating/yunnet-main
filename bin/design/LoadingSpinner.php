<?php

namespace unt\design;

use unt\objects\BaseObject;

class LoadingSpinner extends BaseObject
{
    protected int $height;
    protected int $width;

    public function __construct(int $height, int $width)
    {
        parent::__construct();

        $this->height = $height;
        $this->width = $width;
    }

    /**
     * @param int $height
     * @return LoadingSpinner
     */
    public function setHeight(int $height): LoadingSpinner
    {
        $this->height = $height;
        return $this;
    }

    /**
     * @param int $width
     * @return LoadingSpinner
     */
    public function setWidth(int $width): LoadingSpinner
    {
        $this->width = $width;
        return $this;
    }

    public function render (): string
    {
        return '<svg width="' . $this->width . '" height="' . $this->height . '" viewBox="0 0 38 38" xmlns="http://www.w3.org/2000/svg" stroke="#42A5F5">
                                    <g fill="none" fill-rule="evenodd">
                                        <g transform="translate(1 1)" stroke-width="2">
                                            <circle stroke-opacity=".3" cx="18" cy="18" r="18"/>
                                            <path d="M36 18c0-9.94-8.06-18-18-18">
                                                <animateTransform
                                                        attributeName="transform"
                                                        type="rotate"
                                                        from="0 18 18"
                                                        to="360 18 18"
                                                        dur="0.5s"
                                                        repeatCount="indefinite"/>
                                            </path>
                                        </g>
                                    </g>
                                </svg>';
    }

    public function show (): void
    {
        echo $this->render();
    }

    /////////////////////////////////////
    public static function create (int $height = 38, int $width = 38): LoadingSpinner
    {
        return new static($height, $width);
    }
}