<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
 */
namespace Arikaim\Core\Validator\Filter;

use Arikaim\Core\Validator\Filter;

/**
 * HtmlChars filter
 */
class HtmlChars extends Filter
{  
    /**
     * Filter value, return filtered value
     *
     * @param mixed $value
     * @return mixed
     */
    public function filterValue($value) 
    {            
        if (\is_string($value) == true) {
           return \htmlspecialchars($value,ENT_HTML5 | ENT_QUOTES,'UTF-8');
        }
       
        if (\is_array($value) == true) {
            foreach ($value as $key => $item) {
                $value[$key] = \htmlspecialchars($item,ENT_HTML5 | ENT_QUOTES,'UTF-8');
            }
        }

        return $value;
    } 

    /**
     * Return filter type
     *
     * @return mixed
     */
    public function getType()
    {       
        return FILTER_CALLBACK;
    }
}
