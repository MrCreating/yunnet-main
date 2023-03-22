<?php

namespace unt\design;

use unt\objects\BaseObject;

class Template extends BaseObject
{
    /**
     * @var string $file_path ;
     */
    protected string $file_path;

    /**
     * @var array $vars
     */
    protected array $vars = [];

    /**
     * @var string $content
     */
    protected string $content = '';

    /**
     * @throws \Exception
     */
    public function __construct (string $templatePath)
    {
        parent::__construct();

        $file_path = PROJECT_ROOT . '/templates/' . ltrim($templatePath, '/') . '.php';

        if (file_exists($file_path)) {
            return $this->file_path = $file_path;
        }

        throw new \Exception('Template not found in ' . $file_path);
    }

    public function variables (array $vars): Template
    {
        $this->vars = $vars;

        return $this;
    }

    /**
     * Для внутренних шаблонов
     */
    public function begin (): Template
    {
        ob_start();
        return $this;
    }

    /**
     * Завершаем сбор внутреннего контента
     */
    public function end (): Template
    {
        $content = ob_get_clean();
        $this->content = $content;
        return $this;
    }

    /**
     * Создает готовое представление шаблона и возвращает его
     * @return string
     */
    public function render (): string
    {
        $this->vars['content'] = $this->content;

        ob_start();
        extract($this->vars);
        require $this->file_path;

        return ob_get_clean();
    }

    /**
     * Отображает шаблон у пользователя
     */
    public function show (): void
    {
        echo $this->render();
    }

    ////////////////////////////////
    public static function get (string $path): ?Template
    {
        try {
            return new static($path);
        } catch (\Exception $e)
        {
            return NULL;
        }
    }
}