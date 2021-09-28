<?php

// Autoload files using Composer autoload
require_once __DIR__ . '/vendor/autoload.php';

use DiDom\Document;

class Check301s {
    public $return_links = [];
    public $bad_urls = [];
    protected $domain;
    protected $new_domain;
    protected $max_depth;

    public function __construct($domain, $new_domain, $max_depth = 4) {
        $this->domain = $domain;
        $this->new_domain = $new_domain;
        $this->max_depth = $max_depth;
    }

    public function linkScanner($url, $current_depth = 0) {
        try {
            if (strpos($url, 'http') === 0) {
                $document = new Document($url, true);
            } else {
                $document = new Document($this->domain . $url, true);
            }

            $links = $document('a');
            $this_level_links = [];
            foreach ($links as $link) {
                $href = $link->getAttribute('href');
                // get rid of bad links
                if (
                    strpos($href, '#') !== 0 &&
                    strpos($href, 'mailto:') !== 0 &&
                    strpos($href, 'tel:') !== 0 &&
                    strpos($href, 'javascript:') !== 0 &&
                    $href !== null &&
                    $href !== '/'
                ) {
                    // get rid of links to other domains
                    if (strpos($href, 'http') === 0) {
                        if (strpos($href, $this->domain) !== false) {
                            if (!array_key_exists($href, $this->return_links)) {
                                $this_level_links[$href] = $href;
                                $this->return_links[$href] = $href;
                            }
                        }
                    } else {
                        if (!array_key_exists($href, $this->return_links)) {
                            $this_level_links[$href] = $href;
                            $this->return_links[$href] = $href;
                        }
                    }
                }
            }

            if ($current_depth < $this->max_depth) {
                $test = '';
                foreach ($this_level_links as $link) {
                    $this->linkScanner($link, $current_depth + 1);
                }
            }
        } catch (Exception $e) {
            print_r($e);
        }
    }

    public function checkLinks() {
        foreach ($this->return_links as $url) {
            try {
                if (strpos($url, 'http') === 0) {
                    $document = new Document(str_replace($this->domain, $this->new_domain, $url), true);
                } else {
                    $document = new Document($this->new_domain . $url, true);
                }
            } catch (Exception $e) {
                $this->bad_urls[$url] = [
                    $url
                ];
            }
        }
    }

    public function export301s() {
        $file = fopen("./301s.csv", "w");
        foreach ($this->bad_urls as $row) {
            fputcsv($file, $row);
        }
        fclose($file);
    }
}

$original_website = 'https://codekoalas.com';
$new_website = 'https://d9.codekoalas.com';
$check_301s = new Check301s($original_website, $new_website);
$check_301s->linkScanner('/');
$check_301s->checkLinks();
$check_301s->export301s();

