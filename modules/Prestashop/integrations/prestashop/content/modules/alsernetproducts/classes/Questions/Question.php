<?php
class Question extends ObjectModel
{

    public $id;
    public $product_id;
    public $parent_id;
    public $question;
    public $date_question;
    public $client_name;
    public $client_email;
    public $id_lang;
    public $response_text;
    public $response_date;
    public $approved;
    public $removed;
    
    public static $definition = [
        'table'     => 'product_questions',
        'primary'   => 'id',
        'multilang' => false,        
        'fields'    => [
        'product_id'       => ['type' => self::TYPE_INT, 'required'=>true],
        'parent_id'       => ['type' => self::TYPE_INT, 'required'=>true],
	    'question'       => ['type' => self::TYPE_STRING, 'required'=>true],	
	    'date_question'       => ['type' => self::TYPE_DATE, 'required'=>true],	
	    'client_name'       => ['type' => self::TYPE_STRING, 'required'=>true, 'size' => 128],		
	    'client_email'       => ['type' => self::TYPE_STRING, 'required'=>true, 'size' => 128],		
	    'id_lang'       => ['type' => self::TYPE_INT, 'required'=>true],	
	    'response_text'       => ['type' => self::TYPE_STRING],	
	    'response_date'       => ['type' => self::TYPE_DATE],
	    'approved'       => ['type' => self::TYPE_BOOL, 'validate' => 'isBool',],		
	    'removed'       => ['type' => self::TYPE_BOOL, 'validate' => 'isBool',],			
        ],
    ];

    public function getProducts($product,$lang) {
        $sql = "SELECT * FROM " . _DB_PREFIX_ . "product_questions where product_id=".$product."  and id_lang=".$lang." order by id DESC";
        return Db::getInstance()->ExecuteS($sql);
    }

    
    public function getProductQuestionsDetails() {

        $product = Context::getContext()->controller->getProduct()->id;
        $lang = Context::getContext()->language->id;

        $sql = "SELECT * FROM aalv_product_questions WHERE product_id=".$product."  and id_lang=".$lang." and approved=1 and removed=0 order by id desc";
        $questions =  Db::getInstance()->ExecuteS($sql);


        $data = array (
            'questions' => $questions,
        );

        return $data;

    }

    
}
