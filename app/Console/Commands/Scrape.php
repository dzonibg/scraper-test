<?php

namespace App\Console\Commands;

use DOMDocument;
use DOMXPath;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class Scrape extends Command
{

    protected $signature = 'scrape:test';
    protected $description = 'Srape a test website.';

    /**
     *  Website settings:
     */
    protected $path = 'universal';
    protected $siteUrl = 'https://www.tehnomanija.rs';
    protected $totalCategories = [];
    protected $categories = [];



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

                    $xpath = $this->getXPath($categoryURL);
                    // cx-generic-link - tag name, bottom-category-name - class name defined by @
                    $products = $xpath->query("//div[contains(@class, 'product')]");
                    echo "Product count: " . count($products);
                    $this->newLine();
                    foreach ($products as $product) {
                        $productName = $product->nodeValue;
                        echo $productName;
                        $this->newLine();
                        dd();
                    }
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

                    $xpath = $this->getXPath($categoryURL);
                    // cx-generic-link - tag name, bottom-category-name - class name defined by @
                    $products = $xpath->query("//a[contains(@class, 'product-carousel--href')]");
                    echo "Product count: " . count($products);
                    $this->newLine();

                    if (count($products) == 0) {
                        Log::info("No products found on this page. Skipping category.");
                        echo "No products found on this page. Skipping category.";
                        break;
                    }

                    foreach ($products as $product) {
                        $productName = $product->nodeValue;
                        echo $productName;
                        $this->newLine();
                    }
                }
            }
            $this->newLine();
            echo "End FOR loop. Category finished.";
            $this->newLine();
            Log::info("End of category $category");
            dd("End of category $category");
        }
    }

    public function handle(): bool
    {
        $this->getCategories();
        return true;
    }


}
