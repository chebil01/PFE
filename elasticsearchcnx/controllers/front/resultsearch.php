<?php
class ElasticsearchcnxResultsearchModuleFrontController extends ModuleFrontController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function init()
    {
        parent::init();
    }

    public function initContent()
    {
        parent::initContent();
    $search_string = Tools::getValue('search_string');
    $search_url = $this->context->link->getModuleLink('elasticsearchcnx', 'resultsearch');
    $categories = Category::getCategories(Context::getContext()->language->id, true, false);

    $this->context->smarty->assign(array(
        'search_string' => $search_string,
        'search_url' => $search_url,
        'categories'=>$categories
    ));
    $accueilData = $this->getAccueilData();
    if ($accueilData !== null) {
        $accueilName = $accueilData['name'];
        $accueilLink = $accueilData['cms_link'];
        
        $this->context->smarty->assign(array(
            'search_string' => $search_string,
            'search_url' => $search_url,
            'categories' => $categories,
            'accueil_name' => $accueilName,
            'accueil_link' => $accueilLink,
            
            
        ));
    }

    $this->setTemplate('module:elasticsearchcnx/views/templates/front/search_results.tpl');
    if (Tools::isSubmit('ajax')) {
        $search_results = $this->search($search_string);
        
        header('Content-Type: application/json');
        echo json_encode($search_results);
        exit();
    } else {
        $this->setTemplate('module:elasticsearchcnx/views/templates/front/search_results.tpl');
    }
      
       
    }
    public function getAccueilData()
{
    $es_client = Elasticsearch\ClientBuilder::create()->build();
    $id_lang = Context::getContext()->language->id;
    
    $params = [
        'index' => 'cmslog',
        'body' => [
            'query' => [
                'match' => [
                    '_id' => $id_lang,
                ],
            ],
        ],
    ];
    
    $result = $es_client->search($params);

    if (isset($result['hits']['hits'][0]['_source']['doc'])) {
        return $result['hits']['hits'][0]['_source']['doc'];
    }

    return null;
}

    
    public function search($search_string)
    {
        $es_client = Elasticsearch\ClientBuilder::create()->build();
        $id_lang = Context::getContext()->language->id;
        $chartData = array();
        $sortOption = Tools::getValue('sort');    
        $category = Tools::getValue('category');  

        $params = [
            'index' => 'logs',
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [],
                    ],
                ],
            ],
        ];

        if (!empty($search_string)) {
            $searchQuery = [
                'bool' => [
                    'should' => [
                        [
                            'query_string' => [
                                'default_field' => 'product_name.*',
                                'query' => $search_string . '*',
                                'analyze_wildcard' => true
                            ]
                        ],
                        [
                            'wildcard' => [
                                'product_name.*' => '*' . $search_string . '*'
                            ]
                        ],
                        [
                            'term' => [
                                'reference' =>  $search_string 
                            ]
                        ]
                    ]
                ]
            ];

            $params['body']['query']['bool']['must'][] = $searchQuery;
        }
        
        if (!empty($category)) {
            $categoryFilter = [
                'bool' => [
                    'should' => [
                        [
                            'match' => [
                                'category_name.'.$id_lang => $category,
                            ],
                        ],
                        [
                            'match' => [
                                'category_Parentname.'.$id_lang => $category,
                            ],
                        ],
                    ],
                ],
            ];
        
        
            $params['body']['query']['bool']['must'][] = $categoryFilter;
        }
        
        if ($sortOption === 'asc' || $sortOption === 'desc') {
            $params['body']['sort'] = [
                'price_float' => [
                    'order' => $sortOption,
                    'unmapped_type' => 'float',
                ],
            ];
        }
        
        $search_results = $es_client->search($params);
        
        
            $products = array();
            if (isset($search_results['hits']['hits'])) {
                foreach ($search_results['hits']['hits'] as $hit) {
                    $product = array(
                        'product_name' => $hit['_source']['product_name'][$id_lang],
                        'category_name' => $hit['_source']['category_name'][$id_lang],
                        'category_Parentname' => $hit['_source']['category_Parentname'][$id_lang],
                        'price' => $hit['_source']['price_float'],
                        'product_description' => $hit['_source']['product_description_short'][$id_lang],
                        'product_link' => $hit['_source']['product_link'][$id_lang],
                        'image' => $hit['_source']['image']
                    );
                    $products[] = $product;
                }
            }
            
            foreach ($products as $product) {
                $chartData[] = array(
                    'product_name' => $product['product_name'],
                    'category_name'=> $product['category_name'],
                    'countp' => 1 // Each product contributes to the count
                );
            }
        
            $indexParams = [
                'index' => 'chart_data',
                'body' => [
                    'data' => $chartData
                ]
            ];
        
            $es_client->index($indexParams);
            header('Content-Type: application/json'); 
            echo json_encode($products); 
            exit(); 
    }
  
}

