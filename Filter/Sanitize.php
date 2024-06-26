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
use Arikaim\Core\Utils\Html;

/**
 * Sanitize filter
 */
class Sanitize extends Filter
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
           return $this->filterText($value);
        }
       
        if (\is_array($value) == true) {
            foreach ($value as $key => $item) {
                $value[$key] = $this->filterText($item);
            }
        }

        return $value;
    } 

    /**
     * Filter text value
     *
     * @param string $text
     * @return void
     */
    protected function filterText(string $text)
    {
        $tags = (count($this->params) === 0) ? ['script','iframe','style','embed','applet'] : $this->params;
        return Html::removeTags($text,$tags);
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
