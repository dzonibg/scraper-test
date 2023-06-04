<?php

namespace App\Console\Commands;

use DOMDocument;
use DOMXPath;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class Scrape extends Command
{

    protected $signature = 'scrape:universal';
    protected $description = 'Srape a test website.';

    /**
     *  Website settings:
     */
    protected $path = 'universal';
    protected $siteUrl = 'https://www.tehnomanija.rs';

    protected $totalCategories = [];
    protected $categories = [];
    protected $categoryXpath = [];
    protected $products = [];



    public function __construct()
    {
        parent::__construct();
    }

    protected function getCategories()
    {

        $content = file_get_contents($this->siteUrl);
        $dom = new DOMDocument();
        @$dom->loadHTML($content);
        $xpath = new DOMXPath($dom);
        $categories = $xpath->query("//cx-generic-link[contains(@class, 'bottom-category-name')]");
        // cx-generic-link - tag name, bottom-category-name - class name defined by @
        foreach ($categories as $category) {
            echo $category->getElementsByTagName('a')->item(0)->nodeValue . ": " . $category->getElementsByTagName('a')->item(0)->getAttribute('href');
            $categoryName = $this->sanitizeData($category->getElementsByTagName('a')->item(0)->nodeValue);
            $categoryLink = $this->sanitizeData($category->getElementsByTagName('a')->item(0)->getAttribute('href'));
            $this->categories[$categoryName] = $categoryLink;
            $this->newLine();
            echo "Categories finished. Total: " . count($this->categories);
            $this->newLine();
        }

        $this->getCategoryPages();

        return $this->categories;
    }

    protected function getXPath($url) {
        $content = file_get_contents($url);
        $dom = new DOMDocument();
        @$dom->loadHTML($content);
        $xpath = new DOMXPath($dom);
        return $xpath;
    }

    private function sanitizeData($data) {
        return trim($data);
    }

    private function setXpathData(array $categoryXpath) {
        foreach ($categoryXpath as $key => $value) {
            $this->categoryXpath[$key] = $value;
        }
    }

    private function getProductDataFromPaginationByXpath($pageURL) { //TODO go through all products on a pagination page & fetch data about products
        $xpath = $this->getXPath($pageURL);

        //fetch singular product data via xpath k=>v
        foreach ($this->categoryXpath as $key => $value) {
            $product[$key] = $xpath->query($value);
            echo "$key count: " . count($product[$key]);
            $this->newLine();
        }

        //go through each product fetching each xpath element
        foreach ($product['productName'] as $item => $value) {
            $productItem = [];
            try {
                echo $value->nodeValue;

                foreach ($this->categoryXpath as $k => $v) {
                    echo $k . " => " . $product[$k][$item]->nodeValue;
                    $productItem[$k] = $product[$k][$item]->nodeValue;
                }
                $this->newLine();
                $this->products[] = $productItem;
            } catch (\RuntimeException $exception) {
                Log::error("Error parsing data.");
                echo "ERROR";
            }
        }

        Log::info("Product data from pagination on page finished. Moving on.");
        $this->newLine();
        echo "Product data from pagination on page finished. Moving on.";
        $this->newLine();
        echo "Finished page";
        Log::info("Category page finished.");
        return $product['productName'];
    }

    protected function getCategoryPages() {

        //define a category page URL suffix with numbers for pagination
        $urlParameter = "?currentPage=";

        foreach ($this->categories as $category => $value) {
            //$category => name, $value => absolute url

            for ($urlIncrement = 0; $urlIncrement < 16; $urlIncrement++) {

                if ($urlIncrement == 0) {
                    $categoryURL = $this->siteUrl . $value;
                    echo "Category URL first page: $categoryURL";
                    Log::info("Started: $category -> $categoryURL");
                    $products = $this->getProductDataFromPaginationByXpath($categoryURL);
                    echo "Finished first category page";
                    Log::info("First category page finished.");

                } if ($urlIncrement != 0) {
                    // going through rest of pagination pages
                    $this->newLine();
                    echo "Current increment: $urlIncrement";
                    $this->newLine();
                    Log::info("Current increment: $urlIncrement");

                    $categoryURL = $this->siteUrl . $value . $urlParameter .$urlIncrement;
                    Log::info("Loading page $urlIncrement: $categoryURL");
                    echo "Loading page $urlIncrement: $categoryURL";
                    $this->newLine();

                    $products = $this->getProductDataFromPaginationByXpath($categoryURL);

                    if (count($products) == 0) {
                        Log::info("No products found on this page. Skipping category.");
                        echo "No products found on this page. Skipping category.";
                        break;
                    }

//                    foreach ($products as $product => $productValue) {
//                        $productName = $productValue->nodeValue;
//                        $this->newLine();
//                        $discount = $discounts[$product]->nodeValue;
//                        $price = $prices[$product]->nodeValue;
//                        echo "$productName: $price - discount: $discount";
//                        $this->newLine();
//                    }
                }
            }
            $this->newLine();
            Log::info("End of category $category");
        }
    }

    public function handle(): bool
    {
        $xpaths = [
            'productName' => '//div[@class="product"]//a[@class="product-carousel--href"]',
//            'productDiscount' => '//div[@class="product"]//span[@class="product-carousel--discount"]',
            'productPrice' => '//div[@class="product"]//div[@class="product-carousel--info-newprice"]',
        ];
        $this->setXpathData($xpaths);

        $this->getCategories();
        file_put_contents('products.txt', serialize($this->products));
        return true;
    }


}
