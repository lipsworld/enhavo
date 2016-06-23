<?php
/**
 * Created by PhpStorm.
 * User: jhelbing
 * Date: 31.05.16
 * Time: 12:01
 */

namespace Enhavo\Bundle\SearchBundle\Search;

use Enhavo\Bundle\SearchBundle\Util\SearchUtil;
use Enhavo\Bundle\MediaBundle\Service\FileService;

class Highlight {

    protected $util;

    protected $pieces = array();

    protected $fileService;

    public function __construct(SearchUtil $util, FileService $fileService)
    {
        $this->util = $util;
        $this->fileService = $fileService;
    }

    public function highlight($text, $words)
    {
        $highlightedText = null;
        $countedCharacters = 0;
        $pieces = explode('. ', $text);

        foreach($pieces as $piece){
            $pieceWords = explode(" ", $piece);
            $wordsToHighlight = array();
            foreach ($pieceWords as $pieceWord) {
                $simplifiedWord = $this->util->searchSimplify($pieceWord);
                foreach ($words as $searchWord) {
                    $test = explode(' ', $simplifiedWord);
                    foreach ($test as $word) {
                        if ($searchWord == $word) {
                            $wordsToHighlight[$pieceWord] = $simplifiedWord;
                        }
                    }
                }
            }
            if(!empty($wordsToHighlight)){
                list($countedCharacters, $newWord) = $this->countCharacters(strip_tags($piece), $words, $countedCharacters);
                foreach ($wordsToHighlight as $key => $value) {
                    $newWord = preg_replace('/\b'.$key.'\b/u', '<b class="search_highlight">' . $key . '</b>', $newWord);
                }
                $highlightedText = $highlightedText.$newWord;
            }
        }

        return rtrim($highlightedText, ' · ');
    }

    public function highlightText($resource, $words)
    {
        //get belonging search yml
        $currentSearchYml = $this->getSearchYaml($resource);
        //get fields of search yml
        $fields = $this->getFieldsOfSearchYml($currentSearchYml, get_class($resource));
        //go over every field and check if one or more words are in it
        $accessor = PropertyAccess::createPropertyAccessor();
        $this->pieces = array();
        foreach ($fields as $field) {
            if (property_exists($resource, $field) && $field != 'title') {
                $text = $accessor->getValue($resource, $field);
                $pieces = array();
                if (is_string($text)) {
                    $this->pieces[] = $text;
                } else if (gettype($text) == 'object') {
                    $fieldValue = $this->getValueOfField($field, $currentSearchYml, get_class($resource));
                    $this->getTextPieces($text, $fieldValue);
                } else if(is_array($text)){
                    foreach($text as $currentText){
                        $this->pieces[] = $currentText;
                    }
                }
            }
        }
        $pieces = $this->pieces;
        foreach ($pieces as &$piece) {
            $piece = strip_tags($piece); // remove html tags
            $lastCharacter = substr($piece, -1, 1);
            if ($lastCharacter == '.') {
                $piece = rtrim($piece, '.');
            }
        }
        $pieces = array_filter($pieces); // remove keys with value ""
        $text = implode(". ", $pieces);
        $highlightedText = $this->highlight($text, $words);

        $highlightedResult = array();
        $highlightedResult['resource'] = $resource;
        $highlightedResult['highlightedText'] = $highlightedText;
        return $highlightedResult;
    }

    protected function countCharacters($sentence, $words, $charactersLength)
    {
        $collectedSentencesWithSearchword = "";
        //check if the current sentence has more than 20 words
        if(str_word_count($sentence) <= 20 && $sentence != ""){
            list($charactersLength, $collectedSentencesWithSearchword) = $this->addSentenceIfPossible($sentence, $charactersLength, $words, $collectedSentencesWithSearchword);
        } else {
            //yes there are more than 20 words
            //check if half of the current sentence is still to long
            list($firstPart, $secondPart) = $this->getDividedSentence($sentence);

            $beforeAddingFirstPart = $collectedSentencesWithSearchword;
            list($charactersLength, $collectedSentencesWithSearchword) = $this->addSentenceIfPossible($firstPart, $charactersLength, $words, $collectedSentencesWithSearchword, true, true);
            if($collectedSentencesWithSearchword == $beforeAddingFirstPart){
                $collectedSentencesWithSearchword = $collectedSentencesWithSearchword.' ... ';
            }

            $beforeAddingSecondPart = $collectedSentencesWithSearchword;
            list($charactersLength, $collectedSentencesWithSearchword) = $this->addSentenceIfPossible($secondPart, $charactersLength, $words, $collectedSentencesWithSearchword, false, true);
            if($collectedSentencesWithSearchword == $beforeAddingSecondPart){
                if($collectedSentencesWithSearchword == ' ... '){
                    $collectedSentencesWithSearchword = rtrim($collectedSentencesWithSearchword, ' ... ');
                } else {
                    $collectedSentencesWithSearchword = $collectedSentencesWithSearchword.' ... · ';
                }
            }
        }
        return array($charactersLength, $collectedSentencesWithSearchword);
    }

    protected function addSentenceIfPossible($sentence, $charactersLength, $words, $collectedSentencesWithSearchword, $newSentence = true, $devidedSentence = false)
    {
        //no there are less than 20 words --> everything is fine
        $simplifiedSentence = $this->util->searchSimplify($sentence);
        $length = strlen($simplifiedSentence); //lenght of the current sentence
        if($charactersLength + $length <= 160){ //check if there is still enough place to add the current sentence
            //sentence can be added
            //Check if a searchword is in the current sentence
            $wordIn = $this->wordInSentence($simplifiedSentence, $words);
            // if there is at least one searchword in the current sentence then add the sentence
            if($wordIn){
                if($newSentence){
                    if(!$devidedSentence){
                        $collectedSentencesWithSearchword = $collectedSentencesWithSearchword.$sentence.'. · ';
                    } else {
                        $collectedSentencesWithSearchword = $collectedSentencesWithSearchword.$sentence;

                    }
                } else {
                    $sentence = ' '.$sentence;
                    $collectedSentencesWithSearchword = $collectedSentencesWithSearchword.$sentence.'. · ';
                }
                $charactersLength += $length;
            }
        }
        return array($charactersLength, $collectedSentencesWithSearchword);
    }

    protected function getDividedSentence($sentence)
    {
        $pieces = explode(" ", $sentence);
        $pieces = array_filter($pieces); // remove keys with value ""
        $pieces = array_values($pieces);
        $countWords = count($pieces);

        $firstPart = $pieces;
        $firstPart = implode(" ", array_splice($firstPart, 0, $countWords / 2));

        $otherPart = $pieces;
        $otherPart = implode(" ", array_splice($otherPart, $countWords / 2));
        return array($firstPart, $otherPart);
    }

    protected function wordInSentence($sentence, $words)
    {
        foreach ($words as $word) {
            if (preg_match("/\b" . $word . "\b/i", $sentence)) {
                //yes there is at least one searchword in the sentence --> add sentence
                return true;
            }
        }
        return false;
    }

    public function getTextPieces($text, $type)
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        //check what kind of indexing should happen with the text, that means check what type it has (plain, html, ...)
        if (is_array($type[0])) {
            foreach ($type[0] as $key => $value) {
                if ($key == 'Plain' || $key == 'Html') {
                    $this->pieces[] = $text;
                } else if ($key == 'Collection') {

                    //type Collection
                    //get the right yaml file for collection
                    if (array_key_exists('entity', $value)) {
                        $bundlePath = null;
                        $splittedBundlePath = explode('\\', $value['entity']);
                        while (strpos(end($splittedBundlePath), 'Bundle') != true) {
                            array_pop($splittedBundlePath);
                        }
                        $bundlePath = implode('/', $splittedBundlePath);
                        $collectionPath = null;
                        foreach ($this->util->getSearchYamls() as $path) {
                            if (strpos($path, $bundlePath)) {
                                $collectionPath = $path;
                                break;
                            }
                        }
                        $yaml = new Parser();
                        $currentCollectionSearchYamls = $yaml->parse(file_get_contents($collectionPath));
                        $collectionFields = $this->util->getFieldsOfSearchYml($currentCollectionSearchYamls, $value['entity']);
                        if ($text != null) {
                            foreach ($text as $content) {
                                foreach ($collectionFields as $field) {
                                    if (property_exists($content, $field)) {
                                        $newText = $accessor->getValue($content, $field);
                                        $type = $this->util->getValueOfField($field, $currentCollectionSearchYamls, $value['entity']);
                                        $this->getTextPieces($newText, $type);
                                    }
                                }
                            }
                        }
                    } else if (array_key_exists('type', $value)) {
                        foreach ($text as $currentText) {
                            $this->getTextPieces($currentText, $value['type']);
                        }
                    }
                } else if($key == 'PDF'){
                    //get content of PDF
                    $pdfContent = $this->fileService->getPdfContent($text);
                    //now we can use the content as plain and add the given weight from the search.yml
                    $this->pieces[] = $pdfContent;
                }
            }
        } else {
            //Model
            $class = null;
            if ($text instanceof \Doctrine\Common\Persistence\Proxy) {
                $class = get_parent_class($text);
            } else {
                $class = get_class($text);
            }
            $currentModelSearchYaml = $this->util->getSearchYaml($text);
            $modelFields = $this->util->getFieldsOfSearchYml($currentModelSearchYaml, $class);
            $accessor = PropertyAccess::createPropertyAccessor();
            foreach ($modelFields as $field) {
                if (property_exists($text, $field)) {
                    $newText = $accessor->getValue($text, $field);
                    $type = $this->util->getValueOfField($field, $currentModelSearchYaml, $class);
                    $this->getTextPieces($newText, $type, $field, $text);
                }
            }
        }
    }
}