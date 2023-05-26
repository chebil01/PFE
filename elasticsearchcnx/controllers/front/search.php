<?php
use PrestaShop\PrestaShop\Adapter\Entity\ModuleFrontController;
use Symfony\Component\Validator\Constraints\Length;

class ElasticsearchcnxSearchModuleFrontController extends ModuleFrontController
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
        $get_products = Tools::getValue('get_products');

    if (!empty($get_products) && $get_products === 'true') {
        $this->getMostFrequentProducts();
    }
        $search_string = Tools::getValue('search_string');
        $products = array();
        $this->context->smarty->assign(array(
            'products' => $products,
            'search_string' => $search_string,
        ));

        if (!empty($search_string)) {
            $this->searchSuggest($search_string);
        }

        
    }


    public function searchSuggest($search_string)
{
    header('Content-Type: application/json');
    $chartData = array();
    $es_client = Elasticsearch\ClientBuilder::create()->build();
    $id_lang = Context::getContext()->language->id;

    $params = [
        'index' => 'logs',
        'body' => [
            'query' => [
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
                                'reference' => $search_string 
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ];

    $search_results = $es_client->search($params);

    $products = array();
    if (isset($search_results['hits']['hits'])) {
        foreach ($search_results['hits']['hits'] as $hit) {
            $product = array(
                'product_name' => $hit['_source']['product_name'][$id_lang],
                'price' => $hit['_source']['price_float'],
                'product_link' => $hit['_source']['product_link'][$id_lang],
                'image' => $hit['_source']['image']
            );
            $products[] = $product;
        }
    }

  
    foreach ($products as $product) {
        $chartData[] = array(
            'product_name' => $product['product_name'],
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
    echo json_encode(array('products' => $products));
}
public function getMostFrequentProducts()
{
    header('Content-Type: application/json');
    $es_client = Elasticsearch\ClientBuilder::create()->build();
    $id_lang = Context::getContext()->language->id;

    $params = [
        'index' => 'chart_data',
        'body' => [
            'size' => 0,
            'aggs' => [
                'product_names' => [
                    'terms' => [
                        'field' => 'data.product_name.keyword',
                        'size' => 5, // Retrieve top 5 most frequent products
                        'order' => [
                            '_count' => 'desc' // Sort by descending count
                        ]
                    ]
                ]
            ]
        ]
    ];

    $response = $es_client->search($params);

    $buckets = $response['aggregations']['product_names']['buckets'];
    $products = [];
    $productImages = [];
    foreach ($buckets as $bucket) {
        $productName = $bucket['key'];
        $productCount = $bucket['doc_count'];

        // Query the "logs" index for additional information
        $logsParams = [
            'index' => 'logs',
            'body' => [
                'query' => [
                    'bool' => [
                        'should' => [
                            [
                                'query_string' => [
                                    'default_field' => 'product_name.*',
                                    'query' => $productName ,
                                    'analyze_wildcard' => false
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    

        $logsResponse = $es_client->search($logsParams);
      
        if (isset($logsResponse['hits']['hits'][0])) {
            $logHit = $logsResponse['hits']['hits'][0];
            $image = $logHit['_source']['image'];
             if (!in_array($image, $productImages)) {
            $product = [
                'product_name' => $logHit['_source']['product_name'][$id_lang],
                'count' => $productCount,
                'price' => $logHit['_source']['price_float'],
                'product_link' => $logHit['_source']['product_link'][$id_lang],
                'image' => $logHit['_source']['image']
            ];
            $productImages[] = $image;
            $products[] = $product;
             }
         }
    }

    echo json_encode(array('suproducts' => $products));
}




}