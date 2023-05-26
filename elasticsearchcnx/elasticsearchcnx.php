<?php

 use Elasticsearch\ClientBuilder;
 use Symfony\Component\Process\Exception\ProcessFailedException;
 use Symfony\Component\Process\Process;
if (!defined('_PS_VERSION_')) {
    exit;
}

//require_once __DIR__ . '/vendor/autoload.php';

class Elasticsearchcnx extends Module {

    public function __construct()
    {
        $this->name ='elasticsearchcnx';
        $this->version = '1.0.0';
        $this->author = 'chebil adam';
        $this->ps_versions_compliancy = [
            'min' => '1.7.0.0',
            'max' => '8.99.99',
        ];
        $this->bootstrap=true;
        parent::__construct();
        $this->displayName = $this->l('Elastic-Search');
        $this->description = $this->l('Module de recherche innovante qui intègre "elasticsearch" pour des solutions des recherches pertinentes et précis
         ');
      
    }
    public function install(){
        
      
        return parent::install()    &&$this->registerHook('displayTop') && $this->registerHook('displayHeader') &&  $this->registerHook('displayMySearchResults');
    }
    

    public function uninstall()
    {
    return parent::uninstall();
    }
    public function runCommand()
    {
        $workingDirectory = $this->getLocalPath();
        $context = Context::getContext();

// Get the shop's base URL
        $moduleDirectory = __DIR__;

// Get the directory path of the shop
        $shopDirectory = realpath($moduleDirectory . '/../..');
        $command1 = 'php ' . $shopDirectory . '/bin/console cnx:elasticsearch index';

    
        // Create a new process to run the commands
        $process = new Process($command1);
        $process->setWorkingDirectory($workingDirectory);
    
        // Run the process
        $process->run();
    
        // Check if the process was successful
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    
        // Get the output of the command
        $output = $process->getOutput();
    
        // Do something with the output
        // ...
    }    
public function getcontent (){
   
    return $this->display(__FILE__,'views/templates/admin/configure.tpl');
}
public function createTabLink(){
    $tab=new Tab;
    foreach(Language::getLanguages() as $lang)
    {
        $tab->name[$lang['id_lang']] = $this->l('Origin');
    }
    $tab->class_name = 'MyModuleConfigure';
    $tab ->module = $this->name;
    $tab->id_parent=0;
    $tab->add();
    return true;
}
public function hookDisplayTop()
{
    $this->context->controller->addCSS($this->_path . 'views/css/searchbar.css', 'all');
    $this->context->controller->addJS($this->_path . 'views/js/jquery-ui.js');
    $this->context->controller->addJS($this->_path . 'views/js/jquery-ui.min.js');
    $this->context->controller->addJS($this->_path . 'views/js/searchbar.js');
    $search_controller_url = $this->getSearchUrl();
    $resultsearch_controller_url = $this->getResultSearchUrl();
    $search_string = Tools::getValue('search_string');
    $this->context->smarty->assign('search_string', $search_string);
    $this->context->smarty->assign('search_controller_url', $search_controller_url);
    $this->context->smarty->assign('resultsearch_controller_url', $resultsearch_controller_url);
    $searchbar = $this->display(__FILE__, 'views/templates/hook/search.tpl');
  // $this->runCommand();
    return $searchbar;
}
public function hookDisplayHeader()
{    $this->context->controller->addCSS($this->_path . 'views/css/searchbar.css', 'all');
    $this->context->controller->addCSS($this->_path . 'views/css/result.css', 'all');
    $this->context->controller->addJS($this->_path . 'views/js/jquery-ui.js');
    $this->context->controller->addJS($this->_path . 'views/js/jquery-ui.min.js');
    $this->context->controller->addJS($this->_path . 'views/js/searchbar.js');
    $this->context->controller->addJS($this->_path . 'views/js/searchresults.js');
}
    public function getSearchUrl()
    {
        return $this->context->link->getModuleLink('elasticsearchcnx', 'search', array(), true);
    }
    public function getResultSearchUrl()
    {
        return $this->context->link->getModuleLink('elasticsearchcnx', 'resultsearch', array(), true);
    }
    
}
