<?php

class DataGenerator {

    /**
     * @var \DOMDocument     $domCrawler
     */
    protected $dom;
    protected $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    protected $output;

    public function __construct() {
        $this->dom = new DOMDocument('1.0', 'UTF-8');
    }

    public function generate($numberOfNodes = 10000) {
        $entityManager = DatabaseAccess::getInstance();
        for ($i = 0; $i < $numberOfNodes; $i++) {
            $randomString = $this->generateRandomString();
            $item = new Item();
            $item->setName($randomString);
            $entityManager->persist($item);
        }
        $entityManager->flush();
    }

    protected function generateRandomString($length = 12) {
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $this->characters[rand(0, strlen($this->characters) -1)];
        }
        return $randomString;
    }
} 