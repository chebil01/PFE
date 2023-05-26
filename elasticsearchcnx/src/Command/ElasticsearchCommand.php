<?php 

namespace Dotit\Elasticsearchcnx\Command;

use Elasticsearch\ClientBuilder;
use Image;
use JetBrains\PhpStorm\Language;
use PrestaShop\PrestaShop\Adapter\Entity\Context;
use PrestaShop\PrestaShop\Adapter\Entity\Db;
use PrestaShop\PrestaShop\Adapter\Entity\Link;
use PrestaShopBundle\Entity\Shop;
use Product;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ElasticsearchCommand extends Command
{
    
    protected static $defaultName = 'cnx:elasticsearch';

    public function __construct()
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('cnx:elasticsearch')
            ->setDescription('Index or search data in Elasticsearch')
             ->addArgument('action', InputArgument::REQUIRED, 'The action to perform (index or search)');
        
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $client = ClientBuilder::create()
        ->setHosts(['localhost:9200'])
        ->build();
        $action = $input->getArgument('action');
        switch($action){
            case 'index':
                $results = Db::getInstance()->executeS('SELECT 
                ps_product_shop.*, ps_product.*, ps_shop.*, ps_category.*, ps_tax_rules_group.*, ps_lang.iso_code,
                ps_product_lang.name AS product_name, ps_shop.name AS shop_name, ps_category_lang.name AS category_name,
                ps_category_lang.description AS category_description, ps_product_lang.description AS product_description,
                ps_product_lang.description_short, ps_product_lang.id_lang, ps_product_lang.link_rewrite AS link_rewrite,
                ps_category_lang.link_rewrite AS category_rewrite, ps_layered_price_index.price_min, ps_layered_price_index.price_max,
                parent_category_lang.name AS parent_category_name, ps_image.id_image
            FROM ps_product_shop
            JOIN ps_lang
            JOIN ps_product ON ps_product.id_product = ps_product_shop.id_product
            JOIN ps_shop ON ps_product_shop.id_shop = ps_shop.id_shop
            JOIN ps_category ON ps_product_shop.id_category_default = ps_category.id_category AND ps_product.id_category_default = ps_category.id_category
            JOIN ps_tax_rules_group ON ps_product_shop.id_tax_rules_group = ps_tax_rules_group.id_tax_rules_group
            JOIN ps_category_lang ON ps_product_shop.id_category_default = ps_category_lang.id_category AND ps_category.id_category = ps_category_lang.id_category
            JOIN ps_product_lang ON ps_product_shop.id_product = ps_product_lang.id_product AND ps_product_lang.id_lang = ps_category_lang.id_lang
            JOIN ps_layered_price_index ON ps_product.id_product = ps_layered_price_index.id_product
            LEFT JOIN ps_category AS parent_category ON ps_category.id_parent = parent_category.id_category
            LEFT JOIN ps_category_lang AS parent_category_lang ON parent_category.id_category = parent_category_lang.id_category AND parent_category_lang.id_lang = ps_category_lang.id_lang
            LEFT JOIN ps_image ON ps_image.id_product = ps_product.id_product
                 ');

            
                $nblang=Db::getInstance()->getValue('SELECT COUNT(id_lang) FROM  `'._DB_PREFIX_.'lang`');
                $langs=Db::getInstance()->executeS('SELECT ps_lang.iso_code FROM `ps_lang` ');
                $n=1;
         
                foreach($results as $result){
                    $produit_id= $result['id_product'];
                    $id_shop=$result['id_shop'];
                    $shop_name=$result['shop_name'];
                
                    if($n==$produit_id){
                        $product_name[$result['id_lang']]=$result['product_name'];
                        $product_description[$result['id_lang']]=$result['product_description'];
                        $product_description_short[$result['id_lang']]=$result['description_short'];
                        $category_name[$result['id_lang']]=$result['category_name'];
                        $category_Parentname[$result['id_lang']] =  $result['parent_category_name'];
                        $category_description[$result['id_lang']]=$result['category_description'];
                    
                    }
                    else{
                        $n+=1;
                        $i=0;
                        $product_name[$result['id_lang']]=$result['product_name'];
                        $product_description[$result['id_lang']]=$result['product_description'];
                        $product_description_short[$result['id_lang']]=$result['description_short'];
                        $category_name[$result['id_lang']]=$result['category_name'];
                        $category_Parentname[$result['id_lang']] =  $result['parent_category_name'];
                        $category_description[$result['id_lang']]=$result['category_description'];
                    
                        
                    }
                    $price = $result['price_min'];
                    $price_max = $result['price_max'];
                    if($price !=$price_max){
                        $discount =true;
                        $discountp= 100*($price_max-$price)/$result['price'];

                    }else{
                        $discount =false;
                        $discountp='';
                    }
                //  $image_url = __PS_BASE_URI__ . 'img/p/' . substr($produit_id, -1) . '/' . substr($produit_id, -2, -1) . '/' . $produit_id . '-' . 'home_default' . '.jpg';
                
                    $date_add=$result['date_add'];
                    $reference=$result['reference'];
                    $product_type=$result['product_type'];
                    $quantity=$result['quantity'];
                   
                
                    switch ($result['id_lang']) { 
                        case 1:
                            $language_code = 'fr';
                            break;
                        case 2:
                            $language_code = 'ar';
                            break;
                        case 3:
                            $language_code = 'en';
                            break;
                    }
                    $image_id = $result['id_image'];
                    $image_url = __PS_BASE_URI__. $image_id .'-'. 'home_default'.'/'.$result['link_rewrite'].'.jpg';
                    $image_url_large = __PS_BASE_URI__. $image_id .'-'. 'large_default'.'/'.$result['link_rewrite'].'.jpg';
                    $product_link[$result['id_lang']] = __PS_BASE_URI__ . $language_code . '/' . $result['category_rewrite'] . '/' . $produit_id . '-' . $result['link_rewrite'] . '.html';
                //$image_url[$result['id_lang']] =  __PS_BASE_URI__. $n .'-'. 'home_default'.'/'.$result['link_rewrite'];
                
                    //var_dump($image_url);   
                    if($result['id_lang']==$nblang){
                            $params = [
                                'index' => 'logs',
                                'id'=>$produit_id,
                                'body' => [
                                    'doc'=>[
                                        'id_shop'=>$id_shop,
                                        'shop_name'=>$shop_name,
                                        'product_name'=>$product_name,
                                        'product_link'=> $product_link,
                                        'image'=> $image_url,
                                        'image_large'=> $image_url_large,
                                        'product_description'=>$product_description,
                                        'product_description_short'=>$product_description_short,
                                        'price_float' => (float)$price,
                                        'price_keyword' => (string)$price,
                                        'price_max'=>$price_max,
                                        'date_add'=>$date_add,
                                        'reference'=>$reference,
                                        'product_type'=>$product_type,
                                        'quantity'=>$quantity,
                                        'category_name'=>$category_name,
                                        'category_Parentname'=>$category_Parentname,
                                        'category_description'=>$category_description,
                                        'discount'=>$discount,
                                        'discountp'=>$discountp,
                                        'show_price'=>$result['show_price']
                                        ]
                                ]
                                
                            ];   
                            $params['body']['doc']['product_name']['type'] = 'keyword';
                            $response = $client->index($params); 
                                        }
                    
                }
            case 'update' :
                $results = Db::getInstance()->executeS('SELECT 
                    ps_product_shop.*, ps_product.*, ps_shop.*, ps_category.*, ps_tax_rules_group.*, ps_lang.iso_code,
                    ps_product_lang.name AS product_name, ps_shop.name AS shop_name, ps_category_lang.name AS category_name,
                    ps_category_lang.description AS category_description, ps_product_lang.description AS product_description,
                    ps_product_lang.description_short, ps_product_lang.id_lang, ps_product_lang.link_rewrite AS link_rewrite,
                    ps_category_lang.link_rewrite AS category_rewrite, ps_layered_price_index.price_min, ps_layered_price_index.price_max,
                    parent_category_lang.name AS parent_category_name, ps_image.id_image
                FROM ps_product_shop
                JOIN ps_lang
                JOIN ps_product ON ps_product.id_product = ps_product_shop.id_product
                JOIN ps_shop ON ps_product_shop.id_shop = ps_shop.id_shop
                JOIN ps_category ON ps_product_shop.id_category_default = ps_category.id_category AND ps_product.id_category_default = ps_category.id_category
                JOIN ps_tax_rules_group ON ps_product_shop.id_tax_rules_group = ps_tax_rules_group.id_tax_rules_group
                JOIN ps_category_lang ON ps_product_shop.id_category_default = ps_category_lang.id_category AND ps_category.id_category = ps_category_lang.id_category
                JOIN ps_product_lang ON ps_product_shop.id_product = ps_product_lang.id_product AND ps_product_lang.id_lang = ps_category_lang.id_lang
                JOIN ps_layered_price_index ON ps_product.id_product = ps_layered_price_index.id_product
                LEFT JOIN ps_category AS parent_category ON ps_category.id_parent = parent_category.id_category
                LEFT JOIN ps_category_lang AS parent_category_lang ON parent_category.id_category = parent_category_lang.id_category AND parent_category_lang.id_lang = ps_category_lang.id_lang
                LEFT JOIN ps_image ON ps_image.id_product = ps_product.id_product
                     ');

                
                    $nblang=Db::getInstance()->getValue('SELECT COUNT(id_lang) FROM  `'._DB_PREFIX_.'lang`');
                    $langs=Db::getInstance()->executeS('SELECT ps_lang.iso_code FROM `ps_lang` ');
                    $n=1;
             
                    foreach($results as $result){
                        $produit_id= $result['id_product'];
                        $id_shop=$result['id_shop'];
                        $shop_name=$result['shop_name'];
                    
                        if($n==$produit_id){
                            $product_name[$result['id_lang']]=$result['product_name'];
                            $product_description[$result['id_lang']]=$result['product_description'];
                            $product_description_short[$result['id_lang']]=$result['description_short'];
                            $category_name[$result['id_lang']]=$result['category_name'];
                            $category_Parentname[$result['id_lang']] =  $result['parent_category_name'];
                            $category_description[$result['id_lang']]=$result['category_description'];
                        
                        }
                        else{
                            $n+=1;
                            $i=0;
                            $product_name[$result['id_lang']]=$result['product_name'];
                            $product_description[$result['id_lang']]=$result['product_description'];
                            $product_description_short[$result['id_lang']]=$result['description_short'];
                            $category_name[$result['id_lang']]=$result['category_name'];
                            $category_Parentname[$result['id_lang']] =  $result['parent_category_name'];
                            $category_description[$result['id_lang']]=$result['category_description'];
                        
                            
                        }
                        $price = $result['price_min'];
                        $price_max = $result['price_max'];
                        if($price !=$price_max){
                            $discount =true;
                            $discountp= 100*($price_max-$price)/$result['price'];

                        }else{
                            $discount =false;
                            $discountp='';
                        }
                    //  $image_url = __PS_BASE_URI__ . 'img/p/' . substr($produit_id, -1) . '/' . substr($produit_id, -2, -1) . '/' . $produit_id . '-' . 'home_default' . '.jpg';
                    
                        $date_add=$result['date_add'];
                        $reference=$result['reference'];
                        $product_type=$result['product_type'];
                        $quantity=$result['quantity'];
                       
                    
                        switch ($result['id_lang']) { 
                            case 1:
                                $language_code = 'fr';
                                break;
                            case 2:
                                $language_code = 'ar';
                                break;
                            case 3:
                                $language_code = 'en';
                                break;
                        }
                        $image_id = $result['id_image'];
                        $image_url = __PS_BASE_URI__. $image_id .'-'. 'home_default'.'/'.$result['link_rewrite'].'.jpg';
                        $image_url_large = __PS_BASE_URI__. $image_id .'-'. 'large_default'.'/'.$result['link_rewrite'].'.jpg';
                        $product_link[$result['id_lang']] = __PS_BASE_URI__ . $language_code . '/' . $result['category_rewrite'] . '/' . $produit_id . '-' . $result['link_rewrite'] . '.html';
                    //$image_url[$result['id_lang']] =  __PS_BASE_URI__. $n .'-'. 'home_default'.'/'.$result['link_rewrite'];
                    
                        //var_dump($image_url);   
                        if($result['id_lang']==$nblang){
                                $params = [
                                    'index' => 'logs',
                                    'id'=>$produit_id,
                                    'body' => [
                                        'doc'=>[
                                            'id_shop'=>$id_shop,
                                            'shop_name'=>$shop_name,
                                            'product_name'=>$product_name,
                                            'product_link'=> $product_link,
                                            'image'=> $image_url,
                                            'image_large'=> $image_url_large,
                                            'product_description'=>$product_description,
                                            'product_description_short'=>$product_description_short,
                                            'price_float' => (float)$price,
                                            'price_keyword' => (string)$price,
                                            'price_max'=>$price_max,
                                            'date_add'=>$date_add,
                                            'reference'=>$reference,
                                            'product_type'=>$product_type,
                                            'quantity'=>$quantity,
                                            'category_name'=>$category_name,
                                            'category_Parentname'=>$category_Parentname,
                                            'category_description'=>$category_description,
                                            'discount'=>$discount,
                                            'discountp'=>$discountp,
                                            'show_price'=>$result['show_price']
                                            ]
                                    ]
                                    
                                ];   
                                $params['body']['doc']['product_name']['type'] = 'keyword';
                                $response = $client->update($params); 

                                            }
                                            
                                            
                                        }
            case 'cmsindex':
                   $query = "SELECT cc.*,cl.* ,cl.`id_lang`, cl.`link_rewrite` AS `lang_link_rewrite`, l.`iso_code`
                    FROM " . _DB_PREFIX_ . "cms_category cc
                    INNER JOIN " . _DB_PREFIX_ . "cms_category_lang cl ON cc.`id_cms_category` = cl.`id_cms_category`
                    INNER JOIN " . _DB_PREFIX_ . "lang l ON cl.`id_lang` = l.`id_lang`";
            $results = Db::getInstance()->executeS($query);

            $context = Context::getContext();
            $baseURL = $context->shop->getBaseURL();

            foreach ($results as $result) {
                $id_cms = $result['id_cms_category'];
                $id_shop = $result['id_shop'];
                $name = $result['name'];
                $langId = $result['id_lang'];
                $link_writer = $result['lang_link_rewrite'];
                $langISO = $result['iso_code'];

                // Manually construct the CMS page URL for each language
                $cmsLink = $baseURL .  $langISO .'/';

                $params = [
                    'index' => 'cmslog',
                    'id' => $langId,
                    'body' => [
                        'doc' => [
                            'id_shop' => $id_shop,
                            'id_cms' => $id_cms,
                            'name' => $name,
                            'link_writer' => $link_writer,
                            'cms_link' => $cmsLink // Add the CMS page link to the document
                        ]
                    ]
                ];

                $response = $client->index($params);
}

      



        }
        } 
        

       

                      
}
?>