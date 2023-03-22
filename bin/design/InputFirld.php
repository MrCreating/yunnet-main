<?php

namespace unt\design;

use unt\objects\BaseObject;

class InputFirld extends BaseObject
{
    protected string $text = '';
    protected string $type = 'text';

    protected string $id = '';

    protected string $value = '';

    protected bool $disabled = false;

    public function __construct(string $text, string $type)
    {
        parent::__construct();

        $this->text = $text;
        $this->type = $type;
    }

    public function setId (string $id): InputFirld
    {
        $this->id = $id;
        return $this;
    }

    public function setValue (string $value): InputFirld
    {
        $this->value = $value;

        return $this;
    }

    public function disable (): InputFirld
    {
        $this->disabled = true;
        return $this;
    }

    public function render (): string
    {
        return '
        <div class="input-field">
            <input type="' . $this->type . '" id="' . $this->id . '" name="' . $this->id . '">
            <label for="'. $this->id .'" class="">
                ' . $this->text . '
            </label>
        </div>';
    }

    public function show (): void
    {
        echo $this->render();
    }

    /////////////////////////////////////
    public static function create (string $text, string $type = 'text'): InputFirld
    {
        return new static($text, $type);
    }
}