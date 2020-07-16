<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c) Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
 */
namespace Arikaim\Core\Validator\Rule;

use Arikaim\Core\Validator\Rule;

/**
 * Regexp validation rule
 */
class Regexp extends Rule
{   
    /**
     * Constructor
     *
     * @param array $params
     */
    public function __construct($params = []) 
    {
        parent::__construct($params);

        $this->setError("REGEXP_NOT_VALID_ERROR");
    }
    
    /**
     * Validate regexp value 
     *
     * @param mixed $value
     * @return boolean
     */
    public function validate($value) 
    {
        $exp = $this->params->get('exp');
        $exp = (is_array($exp) == true) ? $exp[0] : $exp;
           
        return preg_match($exp,$value);
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
