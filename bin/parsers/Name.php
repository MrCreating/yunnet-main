<?php

namespace unt\parsers;

use unt\objects\BaseObject;

class Name extends BaseObject
{
    private bool $is_valid = false;

    private string $last_name = '';
    private string $first_name = '';
    private string $middle_name = '';

    private string $gender;

    private bool $is_full_name = false;

    function __construct (string $last_name = '', ?string $first_name = NULL, ?string $middle_name = NULL, ?string $sex = NULL)
    {
        parent::__construct();

        if ($first_name === NULL)
        {
            $m = [];
            preg_match("/^\s*(\S+)(\s+(\S+)(\s+(\S+))?)?\s*$/", $last_name, $m);

            if ($m)
            {
                $this->is_valid = true;

                if ($m[5] && preg_match("/(ич|на)$/", $m[3]) && !preg_match("/(ич|на)$/", $m[5]))
                {
                    $this->last_name = $m[5];
                    $this->first_name = $m[1];
                    $this->middle_name = $m[3];

                    $this->is_full_name = true;
                } else
                {
                    $this->last_name = $m[1];
                    $this->first_name = $m[3];
                    $this->middle_name = $m[5];
                }
            }
        } else {
            $this->last_name = $last_name;
            $this->first_name = $first_name;
            $this->middle_name = $middle_name;

            $this->is_full_name = true;

            $this->is_valid = true;
        }

        $this->gender = $sex ?: $this->getGender();
    }

    function getGender ()
    {
        if ($this->gender)
            return $this->gender;

        if (strlen($this->middle_name) > 2)
        {
            switch (substr($this->middle_name, strlen($this->middle_name) - 2))
            {
                case 'ич':
                    return 'm';
                case 'на':
                    return 'f';
                default:
                    break;
            }
        }

        return '';
    }

    function getFirstName (): ?string
    {
        return $this->first_name;
    }

    function getLastName (): ?string
    {
        return $this->last_name;
    }

    function getMiddleName (): ?string
    {
        return $this->middle_name;
    }

    function work ($mode, $case)
    {
        $worker = new RussianNameWorker();

        if ($mode === 1) return iconv('UTF-8', 'UTF-8//IGNORE', $worker->word($this->getFirstName(), $this->getGender(), 'first_name', $case));
        if ($mode === 2) return iconv('UTF-8', 'UTF-8//IGNORE', $worker->word($this->getLastName(), $this->getGender(), 'last_name', $case));
        if ($mode === 3) return iconv('UTF-8', 'UTF-8//IGNORE', $worker->word($this->getMiddleName(), $this->getGender(), 'middle_name', $case));

        return '';
    }

    function valid (): bool
    {
        return $this->is_valid;
    }
}