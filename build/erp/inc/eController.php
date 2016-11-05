<?php
namespace build\erp\inc;

use mwce\ModuleController;
use mwce\Tools;

class eController extends ModuleController
{

    public function __construct(\mwce\content $view, $pages)
    {
        parent::__construct($view, $pages);

        $this->view
            ->setName($this->className)
            ->add_dict('titles')
            ->add_dict($this->className) //подключаем словарь к модулю (если он, конечно, есть)
            ->set('currentPage',$this->className);

        if(!empty($this->pages[$this->className]["title"])){
            $this->view->replace($this->pages[$this->className]["title"],"title");
        } //выставляем заголовок текущего модуля заместо |title|
    }
}