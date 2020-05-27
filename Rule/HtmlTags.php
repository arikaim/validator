<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
 */
namespace Arikaim\Core\Validator\Rule;

use Arikaim\Core\Validator\Rule;

/**
 * HtmlTags rule 
 */
class HtmlTags extends Rule
{    
    /**
     * Constructor
     *
     * @param array $params
     */
    public function __construct($params = []) 
    {
        parent::__construct($params);

        $this->setError("TEXT_NOT_VALID_ERROR");
    }

    /**
     * Verify if value is valid
     *
     * @param string $value
     * @return boolean
     */
    public function validate($value) 
    {      
        $tags = $this->params->get('tags',null);
     
        return ($value == strip_tags($value,$tags));
    } 

    /**
     * Return filter type
     *
     * @return int
     */
    public function getType()
    {       
        return FILTER_CALLBACK;
    }
}
