<?php

class MyModuleConfigureController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    public function initContent()
    {
        parent::initContent();
        $this->setTemplate('module:mymodule/views/templates/admin/configure.tpl');
    }
}
